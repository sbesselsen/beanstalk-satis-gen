#!/usr/bin/env php
<?php

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once(__DIR__ . '/vendor/autoload.php');
}

$application = new \Symfony\Component\Console\Application('Beanstalk Satis Generator', '0.2.0');
$application->addCommands(array(
    new \BeanstalkSatisGen\Commands\GenerateCommand(),
    new \BeanstalkSatisGen\Commands\UpdateCommand(),
));
$application->run();
