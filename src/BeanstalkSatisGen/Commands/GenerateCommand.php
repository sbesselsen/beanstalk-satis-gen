<?php

namespace BeanstalkSatisGen\Commands;

use BeanstalkSatisGen\Beanstalk\Api;
use BeanstalkSatisGen\Beanstalk\ChangesetAnalyser;
use BeanstalkSatisGen\BeanstalkReader;
use BeanstalkSatisGen\File\Config;
use BeanstalkSatisGen\SatisFile;
use BeanstalkSatisGen\Updater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends Command
{

    protected function configure()
    {
        $this->setName('generate')
            ->addArgument(
                'config',
                InputArgument::REQUIRED,
                'Path to the config file, the config file should be a JSON file according to the documentation.'
            )
            ->addArgument('satis', InputArgument::REQUIRED, 'Path to the satis file.')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Whether to force generating the satis.json');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new ConsoleLogger($output);

        $config = Config::fromFile($input->getArgument('config'));
        $satisFile = SatisFile::fromFile($input->getArgument('satis'));

        $beanstalkApi = new Api($config->subdomain, $config->username, $config->token, $logger);
        $reader = new BeanstalkReader($config, $beanstalkApi, $logger);
        $changesetAnalyser = new ChangesetAnalyser($beanstalkApi);

        $parsedTo = $config->getParsedTo();
        if (false !== $parsedTo && false === $input->getOption('force')) {
            throw new \Exception(
                'parsed_to is set in the config, are you sure you want to parse?'
                . ' Parsing beanstalk fully will take a long time.'
                . ' To force parse use the --force flag.'
                . ' To parse from the given hash use the update command.'
            );
        }

        $lastChangeset = $changesetAnalyser->getLastChangeset();

        $updater = new Updater($config);
        $updater->logFunction = function ($msg) {
            echo $msg . "\n";
        };
        $updater->updateSatisFile($satisFile, $reader);

        $satisFile->saveToFile($input->getArgument('satis'));

        $config->setParsedTo($lastChangeset->hash_id);
        $config->saveToFile($input->getArgument('config'));

        return 0;
    }
}
