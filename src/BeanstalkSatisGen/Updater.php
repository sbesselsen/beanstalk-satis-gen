<?php
namespace BeanstalkSatisGen;

use BeanstalkSatisGen\File\Config;
use Psr\Log\LoggerInterface;

class Updater
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * Function to send log messages to
     * @var callback
     */
    public $logFunction;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Create an Updater with the specified configuration
     *
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(Config $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Update the Satis file using the data from the BeanstalkReader
     *
     * @param SatisFile       $file
     * @param BeanstalkReader $reader
     */
    public function updateSatisFile(SatisFile $file, BeanstalkReader $reader)
    {
        $repositoryURLs = [];
        foreach ($file->rawRepositories() as $repository) {
            if (isset ($repository->url)) {
                $repositoryURLs[] = $repository->url;
            }
        }
        $readerOptions = [
            BeanstalkReader::OPT_EXCLUDE_URLS => $repositoryURLs,
        ];
        $count = 0;
        $reader->mapComposerPackageURLs(function ($url) use ($file, &$count) {
            $file->addRawRepository((object) [
                'type' => 'vcs',
                'url'  => $url,
            ]);
            $this->logger->notice(sprintf('Will add repository url "%s" to satis', $url));
            $count++;
        }, $readerOptions);

        $this->logger->notice(sprintf('Added %d repository url\'s to satis', $count));
    }
}
