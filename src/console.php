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
					id int,
					show_title var_char(128),
					episode_title var_char(128),
					duration int,
					date datetime,
					description text,
					channel int,
					station var_char(16),
					hd var_char(4),
					episode_number int,
					url text,
					ts datetime
				)';
			$app['db']->query($showsSQL);
		});
$console->register('db-destroy')
		->setDescription('Destroy the SQLite Database Tables')
		->setCode(function (InputInterface $input, OutputInterface $output) use ($app) {
			$showsSQL = 'DROP TABLE shows';
			$app['db']->query($showsSQL);
		});
$console->register('get-shows-data')
		->setDescription('Get all show data from the TiVo')
		->setCode(function (InputInterface $input, OutputInterface $output) use ($app) {
			$showList  = $app['tivo_now_playing']->download();
			$timeStamp = new DateTime('now');
			foreach ($showList as $show) {
				$transaction = $show->writeToDatabase($app['db'], $timeStamp);
				if ($transaction == TiVo\Show::INSERT) {
					// TODO
					// TWEET
				}
			}
		});

return $console;
