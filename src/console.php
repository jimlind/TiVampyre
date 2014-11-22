<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

$console = new Application('TiVampyre', '2.0');

$console->register('db-setup')
        ->setDescription('Setup the SQLite Database Tables')
        ->setCode(function() use ($app) {
            $showSQL = '
                CREATE TABLE show (
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
            $app['db']->query($showSQL);
            $jobQueueSQL = '
                CREATE TABLE job_queue (
                    id      INTEGER PRIMARY KEY AUTOINCREMENT,
                    show_id INTEGER,
                    status  INTEGER,
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
        ->setCode(function() use ($app) {
            $dropShow = 'DROP TABLE show';
            $app['db']->query($dropShow);
            $dropQueue = 'DROP TABLE job_queue';
            $app['db']->query($dropQueue);
            $dropStatus = 'DROP TABLE job_status';
            $app['db']->query($dropStatus);
        });

$console->register('db-truncate')
        ->setDescription('Truncate the SQLite Database Tables')
        ->setCode(function() use ($app) {
            $truncateShow = 'DELETE FROM show';
            $app['db']->query($truncateShow);
            $truncateQueue = 'DELETE FROM job_queue';
            $app['db']->query($truncateQueue);
            $truncateStatus = 'DELETE FROM job_status';
            $app['db']->query($truncateStatus);
        });

$console->register('get-shows')
        ->setDescription('Get all show data from the TiVo')
        ->setCode(function() use ($app) {
            $twitterStatus = false;
            if (isset($app['twitter_production']) && $app['twitter_production']) {
                $twitterStatus = true;
            }
            $showService = $app['sync_service'];
            $showService->rebuildLocalIndex();
        });

$console->register('display-shows')
        ->setDescription('Display all TiVo shows locally indexed.')
        ->setCode(function() use ($app){
            $repository = $app['orm.em']->getRepository('TiVampyre\Entity\Show');
            $showList   = $repository->getAllSortedEpisodes();
            foreach ($showList as $show) {
                echo $show->getId() . ' : ' . $show->getShowTitle();
                if ($show->getEpisodeNumber()) {
                    echo ' #' . $show->getEpisodeNumber();
                }
                if ($show->getEpisodeTitle()) {
                    echo " - " . $show->getEpisodeTitle();
                }
                echo PHP_EOL;
            }
        });

return $console;
