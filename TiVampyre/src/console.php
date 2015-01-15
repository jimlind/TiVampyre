<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

use Symfony\Component\Console\Output\OutputInterface;

$console = new Application('TiVampyre', '2.0');

$console->register('db-setup')
        ->setDescription('Setup the SQLite database tables.')
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
            $tubeSQL = '
                CREATE TABLE tube_log (
                    id      INTEGER PRIMARY KEY AUTOINCREMENT,
                    show_id INTEGER,
                    tube    TEXT
                )';
            $app['db']->query($tubeSQL);
        });

$console->register('db-destroy')
        ->setDescription('Destroy the SQLite database tables.')
        ->setCode(function() use ($app) {
            $showSQL = 'DROP TABLE show';
            $app['db']->query($showSQL);
            $tubeSQL = 'DROP TABLE tube_log';
            $app['db']->query($tubeSQL);
        });

$console->register('db-truncate')
        ->setDescription('Truncate the SQLite database tables')
        ->setCode(function() use ($app) {
            $showSQL = 'DELETE FROM show';
            $app['db']->query($showSQL);
            $tubeSQL = 'DELETE FROM tube_log';
            $app['db']->query($tubeSQL);
        });

$console->register('get-shows')
        ->setDescription('Get all show data from the TiVo.')
        ->setCode(function() use ($app) {
            $twitterStatus = false;
            if (isset($app['twitter_production']) && $app['twitter_production']) {
                $twitterStatus = true;
            }
            $showService = $app['sync_service'];
            $showService->rebuildLocalIndex();
        });

$console->register('list-shows')
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

$console->register('queue')
        ->setDefinition(
            array(
                new InputArgument('Show Id', InputArgument::REQUIRED, 'The unique TiVo Id for the show.'),
                new InputOption('skip', 's', InputOption::VALUE_NONE, 'Skip video transcoding, keep MPEG file.'),
                new InputOption('keep', 'k', InputOption::VALUE_NONE, 'Keep the original MPEG file after transcoding.'),
                new InputOption('auto', 'a', InputOption::VALUE_NONE, 'Autocrop black borders.'),
                new InputOption('cut', 'c', InputOption::VALUE_NONE, 'Cut commericials from file while transcoding.'),
                new InputOption('dvd', 'd', InputOption::VALUE_NONE, 'Transcode to NTSC DVD file (NOT IMPLMENTED).'),
            )
        )
        ->setDescription('Download and convert a show.')
        ->setCode(function(InputInterface $input, OutputInterface $output) use ($app){
            $optionList         = $input->getOptions();
            $optionList['show'] = $input->getArgument('Show Id');


            $app['queue']->useTube('download')
                         ->put(json_encode($optionList));
        });

$console->register('queue-status')
        ->setCode(function(InputInterface $input, OutputInterface $output) use ($app){
            $pheanstalk = $app['queue'];
            $listening  = $pheanstalk->getConnection()->isServiceListening();
            if ($listening) {
                $output->write('Beanstalkd is listening.', true);
            } else {
                $output->write('Beanstalkd is NOT listening.', true);
            }

            $tubeList = $pheanstalk->listTubes();
            foreach($tubeList as $tube) {
                $tubeStats = $pheanstalk->statsTube($tube);
                $output->write($tubeStats['current-jobs-ready'] . ' items in ' . $tube . ' tube.', true);
            }
        });

$console->register('download-worker')
        ->setCode(function(InputInterface $input, OutputInterface $output) use ($app){
            $pheanstalk = $app['queue'];
            $pheanstalk->watch('download');
            while($job = $pheanstalk->reserve()) {
                $data       = json_decode($job->getData(), true);
                $downloader = new TiVampyre\Downloader($app);
                $downloader->process($data);

                if ($data['skip']) {
                    $app['monolog']->info('Downloaded. Skipping Encoding.');
                } else {
                    $app['queue']->useTube('transcode')
                                 ->put(json_encode($data));
                }

                $pheanstalk->delete($job);
            }
        });

$console->register('transcode-worker')
        ->setCode(function(InputInterface $input, OutputInterface $output) use ($app){
            $pheanstalk = $app['queue'];
            $pheanstalk->watch('transcode');
            while($job = $pheanstalk->reserve()) {
                $data       = json_decode($job->getData(), true);
                $transcoder = new TiVampyre\Transcoder($app);
                $transcoder->process($data);

                $pheanstalk->delete($job);
            }
        });

return $console;
