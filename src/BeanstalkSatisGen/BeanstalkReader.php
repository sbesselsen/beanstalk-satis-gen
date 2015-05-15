<?php
namespace BeanstalkSatisGen;

use BeanstalkSatisGen\Beanstalk\Api;
use Psr\Log\NullLogger;

class ReaderException extends \RuntimeException
{
}

class BeanstalkReader
{
    const OPT_EXCLUDE_URLS = 'excludeUrls';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Api
     */
    protected $beanstalkApi;

    /**
     * Create a BeanstalkReader with the specified configuration
     *
     * @param Config $config
     */
    public function __construct(Config $config, Api $beanstalkApi)
    {
        $this->config = $config;
        $this->beanstalkApi = $beanstalkApi;
    }

    /**
     * Map a function over all Composer package URLs that the specified Beanstalk account can access
     *
     * Options:
     * OPT_EXCLUDE_URLS: array of URLs to exclude from output
     *
     * @param callback $f
     * @param array    $options
     */
    public function mapComposerPackageURLs($f, array $options = [])
    {
        $options += [
            self::OPT_EXCLUDE_URLS => [],
        ];
        $excludeUrls = array_flip($options[self::OPT_EXCLUDE_URLS]);
        $output      = [];
        $this->mapGitRepositories(function ($repository) use (
            &$output,
            $f,
            $excludeUrls
        ) {
            if (!isset ($repository->repository_url)) {
                return;
            }
            if (isset ($excludeUrls[$repository->repository_url])) {
                return;
            }
            if (!$this->repositoryMatchesFilters($repository)) {
                return;
            }
            if (!$this->isComposerPackageRepository($repository)) {
                return;
            }
            $f($repository->repository_url);
        });
    }

    protected function repositoryMatchesFilters($repository)
    {
        foreach ($this->config->repository_filters as $name => $value) {
            if (!$this->repositoryMatchesFilter($repository, $name, $value)) {
                return false;
            }
        }

        return true;
    }

    protected function repositoryMatchesFilter($repository, $name, $value)
    {
        switch ($name) {
            case 'last_commit_within':
                $max_last_commit = @strtotime('now -' . $value);
                if ($max_last_commit <= 0) {
                    throw new ReaderException("Invalid filter value for last_commit_within: {$value}");
                }
                if (empty ($repository->last_commit_at)) {
                    return false;
                }

                return $max_last_commit <= strtotime($repository->last_commit_at);
            default:
                throw new ReaderException("Unknown filter type: {$name}");
        }
    }

    protected function isComposerPackageRepository($repository)
    {
        try {
            $composerNode = $this->beanstalkApi->loadJson(
                "repositories/{$repository->id}/node",
                array(
                    'path'     => 'composer.json',
                    'contents' => 'true',
                )
            );
        } catch (ReaderException $e) {
            return false;
        }
        if (empty ($composerNode->contents)) {
            return false;
        }
        $data = @json_decode($composerNode->contents);
        if (!$data) {
            return false;
        }
        if (empty ($data->name)) {
            return false;
        }

        if (isset($data->type) && ! $this->isAllowableComposerType($data->type)) {
            return false;
        }

        return true;
    }

    /**
     * Whether or not this type should be parsed
     *
     * @param string $type
     *
     * @return boolean
     */
    protected function isAllowableComposerType($type)
    {
        $allowableTypes = array(
            'library',
            'wordpress-plugin',
            'symfony-bundle',
            'drupal-module'
        );

        return in_array($type, $allowableTypes);
    }

    /**
     * Call given function for all git repositories in beanstalk
     *
     * @param callable $callback
     */
    protected function mapGitRepositories($callback)
    {
        $allRepositories = $this->beanstalkApi->loadJson('repositories', array());

        // Remove the inception from the beanstalk API
        $allRepositories = array_map(function ($repository) {
            return $repository->repository;
        }, $allRepositories);

        // Remove all non-git repositories
        $allRepositories = array_filter(
            $allRepositories,
            function ($repository) {
                return 'git' === $repository->vcs;
            }
        );

        foreach ($allRepositories as $repository) {
            $callback($repository);
        }
    }
}
