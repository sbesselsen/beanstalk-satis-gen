<?php

namespace BeanstalkSatisGen\Commands;

use BeanstalkSatisGen\Beanstalk\Api;
use BeanstalkSatisGen\Beanstalk\ChangesetAnalyser;
use BeanstalkSatisGen\Beanstalk\ChangesetSearch;
use BeanstalkSatisGen\Config;
use BeanstalkSatisGen\File\Composer;
use BeanstalkSatisGen\SatisFile;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends Command
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('update')
            ->setDescription("Command to update the satis.json with the latest changesets")
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

        $logger = new ConsoleLogger($output);

        $config = Config::fromJSONFile($input->getArgument('config'));
        $satisFile = SatisFile::fromFile($input->getArgument('satis'));

        $beanstalkApi = new Api($config->subdomain, $config->username, $config->token, $logger);
        $analyser = new ChangesetAnalyser($beanstalkApi);

        $searchUntil = 'fb5ff5f121c6729ac4a5b2cfcf1777c1e797da99'; // 1st page

        $changesetSearch = new ChangesetSearch();
        $changesetSearch->files = array('composer.json');
        $changesetSearch->methods = array('edit', 'add');
        $allChangesets = $analyser->analyse($searchUntil, $changesetSearch);

        $repositoriesToAdd = array();

        foreach ($allChangesets as $changeset) {
            $changes = $beanstalkApi->loadJson('repositories/' . $changeset->repository_id . '/node', array(
                'path' => 'composer.json',
                'revision' => $changeset->hash_id,
                'contents' => 'true',
            ));

            $composerFile = new Composer();
            $composerFile->setContent($changes->contents);

            if ($composerFile->isComposerPackage()) {
                $repositoriesToAdd[] = $changes->repository;
            }
        }

        $repositoriesToAdd = array_map(function($repository) {
            return $repository->repository_url;
        }, $repositoriesToAdd);
        $repositoriesToAdd = array_unique($repositoriesToAdd);

        foreach ($repositoriesToAdd as $repository) {
            $satisFile->addRawRepository((object)[
                'type'  => 'vcs',
                'url'   => $repository,
            ]);
        }

        $satisFile->saveToFile($input->getArgument('satis'));
    }
}
