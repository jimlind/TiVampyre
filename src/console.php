<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

$console = new Application('TiVampyre', '2.0');
$console->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev'));
$console
    ->register('get-shows-data')
    ->setDescription('Get all show data from the TiVo')
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($app) {
		$output->write("Finding the TiVo...\n");
		$ip = $app['tivo_locater']->find();
		if ($ip === false) {
			$output->write("TiVo not found. Check the logs.\n");
			return false;
		}
		echo $ip;
    })
;

return $console;
