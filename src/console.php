<?php

use JimLind\TiVo;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

$console = new Application('TiVampyre', '2.0');
$console->register('db-setup')
		->setDescription('Setup the SQLite Database Tables')
		->setCode(function (InputInterface $input, OutputInterface $output) use ($app) {
			$showsSQL = '
				CREATE TABLE shows (
					id             INTEGER PRIMARY KEY,
					show_title     TEXT,
					episode_title  TEXT,
					episode_number INTEGER,
					duration       INTEGER,
					date           TEXT,
					description    TEXT,
					channel        INTEGER,
					station        TEXT,
					hd             TEXT,
					url            TEXT,
					ts             TEXT
				)';
			$app['db']->query($showsSQL);
			$jobQueueSQL = '
				CREATE TABLE job_queue (
					id      INTEGER PRIMARY KEY AUTOINCREMENT,
					show_id INTEGER,
					status  INTEGER
					ts      TEXT
				)';
			$app['db']->query($jobQueueSQL);
			$jobStatusSQL = '
				CREATE TABLE job_status (
					id   INTEGER PRIMARY KEY,
					name TEXT
				);
				INSERT INTO job_status(id, name) VALUES (1,"QUEUED");
				INSERT INTO job_status(id, name) VALUES (2,"DOWNLOADING");
				INSERT INTO job_status(id, name) VALUES (3,"DOWNLOADED");
				INSERT INTO job_status(id, name) VALUES (4,"DOWNLOADED");
				INSERT INTO job_status(id, name) VALUES (5,"ENCODING");
				INSERT INTO job_status(id, name) VALUES (6,"COMPLETE");
				INSERT INTO job_status(id, name) VALUES (13,"ERROR");
				';
			$app['db']->query($jobStatusSQL);

		});
$console->register('db-destroy')
		->setDescription('Destroy the SQLite Database Tables')
		->setCode(function (InputInterface $input, OutputInterface $output) use ($app) {
			$dropSQL = '
				DROP TABLE shows;
				DROP TABLE job_queue;
				DROP TABLE job_status;
				';
			$app['db']->query($dropSQL);
		});
$console->register('get-shows-data')
		->setDescription('Get all show data from the TiVo')
		->setCode(function (InputInterface $input, OutputInterface $output) use ($app) {
			$showList  = $app['tivo_now_playing']->download();
			$timeStamp = new DateTime('now');
			foreach ($showList as $showXML) {
				$show = new TiVo\Show($showXML);
				$transaction = $show->writeToDatabase($app['db'], $timeStamp);
				if ($transaction == TiVo\Show::INSERT) {
					$app['twitter']->send($show->startedRecordingMessage());
				}
			}
		});

return $console;
