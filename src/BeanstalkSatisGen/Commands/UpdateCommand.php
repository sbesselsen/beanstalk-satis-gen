<?php

namespace BeanstalkSatisGen\Commands;

use BeanstalkSatisGen\BeanstalkReader;
use BeanstalkSatisGen\Config;
use BeanstalkSatisGen\SatisFile;
use BeanstalkSatisGen\Updater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends Command
{

    protected function configure()
    {
        $this->setName('update')
            ->addArgument(
                'config',
                InputArgument::REQUIRED,
                'Path to the config file, the config file should be a JSON file according to the documentation.'
            )
            ->addArgument('satis', InputArgument::REQUIRED, 'Path to the satis file.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = Config::fromJSONFile($input->getArgument('config'));
        $satisFile = SatisFile::fromFile($input->getArgument('satis'));

        $reader = new BeanstalkReader($config);

        $updater = new Updater($config);
        $updater->logFunction = function ($msg) {
            echo $msg . "\n";
        };
        $updater->updateSatisFile($satisFile, $reader);

        $satisFile->saveToFile($input->getArgument('satis'));

        return 0;
    }
}
