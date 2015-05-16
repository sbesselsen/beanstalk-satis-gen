<?php
namespace BeanstalkSatisGen;

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
     * Create an Updater with the specified configuration
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
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
        $reader->mapComposerPackageURLs(function ($url) use ($file) {
            $file->addRawRepository((object) [
                'type' => 'vcs',
                'url'  => $url,
            ]);
            $this->log("Added: {$url}");
        }, $readerOptions);
    }

    protected function log()
    {
        if (isset ($this->logFunction)) {
            $args = func_get_args();
            call_user_func_array($this->logFunction, $args);
        }
    }
}
