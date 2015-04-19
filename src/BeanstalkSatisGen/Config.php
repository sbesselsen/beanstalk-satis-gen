<?php
namespace BeanstalkSatisGen;

class Config {
  /**
   * Beanstalk subdomain, i.e. the "foo" in https://foo.beanstalkapp.com/
   * @var string
   */
  public $subdomain;
  
  /**
   * Beanstalk username
   * @var string
   */
  public $username;
  
  /**
   * Beanstalk API access token
   * @var string
   */
  public $token;
  
  /**
   * Filters for selecting repositories
   * @var array
   */
  public $repository_filters = [];
  
  /**
   * Load config from a JSON file
   * @param string $filename
   * @returns Config
   * @throws FileNotReadableEception
   */
  public static function fromJSONFile($filename) {
    $config = new self;
    $config->loadFromFilename($filename);
    return $config;
  }
  
  /**
   * Load data from a config file
   * @param string $filename
   * @throws FileNotReadableException
   */
  protected function loadFromFilename($filename) {
    $data = $this->readFromFilename($filename);
    if (!isset ($data->subdomain)) {
      throw new FileNotReadableException("Config file should contain subdomain");
    }
    $this->subdomain = $data->subdomain;
    if (!isset ($data->username)) {
      throw new FileNotReadableException("Config file should contain username");
    }
    $this->username = $data->username;
    if (!isset ($data->token)) {
      throw new FileNotReadableException("Config file should contain token");
    }
    $this->token = $data->token;
    if (isset ($data->repository_filters)) {
      if (is_object($data->repository_filters)) {
        $this->repository_filters = get_object_vars($data->repository_filters);
      } else if ($data->repository_filters) {
        throw new FileNotReadableException("If config file contains filters, filters should be an object");
      }
    }
  }
  
  /**
   * Read data from a config file
   * @param string $filename
   * @returns stdClass
   * @throws FileNotReadableException
   */
  protected function readFromFilename($filename) {
    if (!file_exists($filename)) {
      throw new FileNotReadableException("File {$filename} does not exist");
    }
    if (!is_readable($filename)) {
      throw new FileNotReadableException("File {$filename} is not readable");
    }
    $data = file_get_contents($filename);
    if (!$output = @json_decode($data)) {
      throw new FileNotReadableException("File {$filename} does not contain valid JSON");
    }
    if (!is_object($output)) {
      throw new FileNotReadableException("File {$filename} should contain a JSON object");
    }
    return $output;
  }
}