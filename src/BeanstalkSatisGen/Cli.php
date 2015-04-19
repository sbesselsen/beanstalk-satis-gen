<?php
namespace BeanstalkSatisGen;

class Cli {
  /**
   * The current working directory
   * @var string
   */
  protected $workingDirectory;
  
  /**
   * Set up a CLI handler for the specified working directory
   * @param string $workingDirectory
   */
  function __construct($workingDirectory) {
    $this->workingDirectory = $workingDirectory;
  }
  
  /**
   * Run the update CLI script
   * @param array $args Shell args
   */
  function update(array $args) {
    if (sizeof($args) < 2) {
      $this->helpUpdate();
      exit;
    }
    
    $configFileName = array_shift($args);
    try {
      $config = Config::fromJSONFile($configFileName);
    } catch (\Exception $e) {
      $this->error("Can't read config file: {$e->getMessage()}");
      exit;
    }
    
    $satisFileName = array_shift($args);
    try {
      $satisFile = SatisFile::fromFile($satisFileName);
    } catch (\Exception $e) {
      $this->error("Can't read Satis file: {$e->getMessage()}");
      exit;
    }
    
    $reader = new BeanstalkReader($config);
    
    $updater = new Updater($config);
    $updater->logFunction = function ($msg) {
      echo $msg . "\n";
    };
    $updater->updateSatisFile($satisFile, $reader);
    
    $satisFile->saveToFile($satisFileName);
  }
  
  protected function helpUpdate() {
    echo "Usage: update [path/to/config.json] [path/to/satis.json]\n";
    echo "The config file should be a JSON file containing an object with these keys:\n";
    echo "  - subdomain\n";
    echo "  - username\n";
    echo "  - token\n";
    echo "And optional keys (see README.md):\n";
    echo "  - repository_filters\n";
    echo "The Satis file should be a regular JSON Satis file.\n";
    exit;
  }
  
  protected function error($msg) {
    echo "Error: {$msg}\n";
  }
}