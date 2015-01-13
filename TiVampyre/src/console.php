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
        ->setDescription('Destroy the SQLite database tables.')
        ->setCode(function() use ($app) {
            $dropShow = 'DROP TABLE show';
            $app['db']->query($dropShow);
            $dropQueue = 'DROP TABLE job_queue';
            $app['db']->query($dropQueue);
            $dropStatus = 'DROP TABLE job_status';
            $app['db']->query($dropStatus);
        });

$console->register('db-truncate')
        ->setDescription('Truncate the SQLite database tables')
        ->setCode(function() use ($app) {
            $truncateShow = 'DELETE FROM show';
            $app['db']->query($truncateShow);
            $truncateQueue = 'DELETE FROM job_queue';
            $app['db']->query($truncateQueue);
            $truncateStatus = 'DELETE FROM job_status';
            $app['db']->query($truncateStatus);
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

$console->register('download')
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
            $showId     = $input->getArgument('Show Id');
            $optionList = $input->getOptions();

            $repository = $app['orm.em']->getRepository('TiVampyre\Entity\Show');
            $showEntity = $repository->find($showId);
            if (!$showEntity) {
                $output->write('Show Not Found', true);
                return;
            }

            $rawFilename = $app['tivampyre_working_directory'] . $showEntity->getId();

            $output->write('Downloading...', true);
            $app['tivo_downloader']->store(
                $showEntity->getURL(),
                $rawFilename . '.tivo'
            );

            $output->write('Decoding...', true);
            $app['tivo_decoder']->decode(
                $rawFilename . '.tivo',
                $rawFilename . '.mpeg'
            );
            unlink($rawFilename . '.tivo');

            if ($optionList['skip']) {
                $output->write('Downloaded to ' . $rawFilename . '.mpeg', true);
                return;
            }

            $chapterList = array();
            if ($optionList['cut']) {
                $output->write('Looking for Commercials...', true);
                $chapterList = $app['comskip']->getChapterList($rawFilename . '.mpeg');
            }

            $output->write('Transcoding...', true);
            $fileList = $app['video_transcoder']->transcode(
                $rawFilename . '.mpeg',
                $chapterList,
                $optionList['auto']
            );

            $output->write('Cleaning MP4...', true);
            $app['video_cleaner']->clean(
                $fileList,
                $rawFilename . '.m4v'
            );

            $app['video_labeler']->addMetadata($showEntity, $rawFilename . '.m4v');
            $cleanFilename = $app['video_labeler']->renameFile($showEntity, $rawFilename . '.m4v');

            $output->write('Downloaded to ' . $cleanFilename, true);

            if ($optionList['keep']) {
                $output->write('Original MPEG written to ' . $rawFilename . '.mpeg', true);
            } else {
                unlink($rawFilename . '.mpeg');
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
