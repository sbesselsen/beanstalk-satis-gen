<?php
namespace BeanstalkSatisGen;

class ReaderException extends \RuntimeException {}

class BeanstalkReader {
  const OPT_EXCLUDE_URLS = 'excludeUrls';
  
  /**
   * @var Config
   */
  protected $config;
  
  /**
   * Create a BeanstalkReader with the specified configuration
   * @param Config $config
   */
  function __construct(Config $config) {
    $this->config = $config;
  }
  
  /**
   * Map a function over all Composer package URLs that the specified Beanstalk account can access
   * 
   * Options:
   * OPT_EXCLUDE_URLS: array of URLs to exclude from output
   * 
   * @param callback $f
   * @param array $options
   */
  function mapComposerPackageURLs($f, array $options = []) {
    $options += [
      self::OPT_EXCLUDE_URLS => [],
    ];
    $excludeUrls = array_flip($options[self::OPT_EXCLUDE_URLS]);
    $output = [];
    $this->mapGitRepositories(function ($repository) use (&$output, $f, $excludeUrls) {
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
  
  protected function repositoryMatchesFilters($repository) {
    foreach ($this->config->repository_filters as $name => $value) {
      if (!$this->repositoryMatchesFilter($repository, $name, $value)) {
        return false;
      }
    }
    return true;
  }
  
  protected function repositoryMatchesFilter($repository, $name, $value) {
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
  
  protected function isComposerPackageRepository($repository) {
    try {
      $composerNode = $this->loadAPIJson("repositories/{$repository->id}/node", array (
        'path' => 'composer.json', 
        'contents' => 'true',
      ));
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
    return true;
  }
  
  protected function mapGitRepositories($f) {
    $page = 1;
    $perPage = 50;
    
    while (true) {
      $pageData = $this->loadAPIJson('repositories', array ('page' => $page, 'per_page' => $perPage));
      $hasData = false;
      foreach ($pageData as $item) {
        if ($item->repository->vcs == 'git') {
          $f($item->repository);
        }
        $hasData = true;
      }
      if (!$hasData) {
        break;
      }
      $page++;
    }
  }
  
  protected function loadAPIJson($path, array $query) {
    $auth = rawurlencode($this->config->username) . ':' . rawurlencode($this->config->token) . '@';
    $url = "https://{$auth}{$this->config->subdomain}.beanstalkapp.com/api/{$path}.json";
    if ($query) {
      $url .= '?' . http_build_query($query);
    }
    $data = @file_get_contents($url);
    if (!$data) {
      throw new ReaderException("No response from server");
    }
    if (($output = @json_decode($data)) === null) {
      throw new ReaderException("Response from server is not valid JSON");
    }
    return $output;
  }
}