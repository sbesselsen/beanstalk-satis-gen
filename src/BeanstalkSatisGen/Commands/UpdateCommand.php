<?php

namespace BeanstalkSatisGen\Commands;

use BeanstalkSatisGen\Beanstalk\Api;
use BeanstalkSatisGen\Beanstalk\ChangesetAnalyser;
use BeanstalkSatisGen\Beanstalk\ChangesetSearch;
use BeanstalkSatisGen\File\Composer;
use BeanstalkSatisGen\File\Config;
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

        $config = Config::fromFile($input->getArgument('config'));
        $satisFile = SatisFile::fromFile($input->getArgument('satis'));

        $beanstalkApi = new Api($config->subdomain, $config->username, $config->token, $logger);
        $analyser = new ChangesetAnalyser($beanstalkApi);

        $searchUntil = $config->getParsedTo();
        if (false === $searchUntil) {
            throw new \Exception(
                'To update satis.json a parsed_to hash needs to be defined in the config.'
                . ' This command will the parse beanstalk from that hash until now.'
            );
        }

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

        $lastChangeset = $analyser->getLastChangeset();
        if (!empty($lastChangeset->hash_id)) {
            $config->setParsedTo($lastChangeset->hash_id);
        }

        $config->saveToFile($input->getArgument('config'));
    }
}
