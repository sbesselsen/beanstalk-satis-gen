<?php
namespace BeanstalkSatisGen;

class InvalidIndexException extends \OutOfRangeException
{
}

class SatisFile
{
    /**
     * Raw JSON data of the Satis file
     * @var \stdClass
     */
    protected $rawData;

    /**
     * Create a new, empty Satis file
     */
    function __construct()
    {
        $this->rawData = (object) [
            'name'         => 'BeanstalkSatisGen generated file',
            'homepage'     => '',
            'repositories' => [],
            'require-all'  => true,
        ];
    }

    /**
     * Save the Satis file to the specified path
     *
     * @param string $filename
     */
    function saveToFile($filename)
    {
        $data = json_encode($this->rawData, JSON_PRETTY_PRINT);
        file_put_contents($filename, $data);
    }

    /**
     * Load the Satis file from an existing path
     *
     * @param string $filename
     *
     * @returns SatisFile
     * @throws FileNotReadableException
     */
    static function fromFile($filename)
    {
        $file = new self();
        $file->loadFromFilename($filename);

        return $file;
    }

    /**
     * Get an array of repositories as stdClass objects
     * @returns array
     */
    function rawRepositories()
    {
        return $this->rawData->repositories;
    }

    /**
     * Set repositories as an array of stdClass objects
     *
     * @param array $repositories
     *
     * @retuns SatisFile
     */
    function setRawRepositories(array $repositories)
    {
        $this->rawData->repositories = $repositories;
    }

    /**
     * Add a repository at the end of the repository array
     *
     * @param \stdClass $repository
     *
     * @returns SatisFile
     * @throws InvalidIndexException
     */
    function addRawRepository($object)
    {
        $this->rawData->repositories[] = $object;

        return $this;
    }

    /**
     * Remove the repository at the specified index
     *
     * @param int $index
     *
     * @returns SatisFile
     * @throws InvalidIndexException
     */
    function removeRepositoryAtIndex($index)
    {
        if (!isset ($this->rawData->repositories[$index])) {
            throw new InvalidIndexException("No repository at index {$index}");
        }
        array_splice($this->rawData->repositories, $index, 1);

        return $this;
    }

    /**
     * Load data from an existing filename
     *
     * @param string $filename
     *
     * @throws FileNotReadableException
     */
    protected function loadFromFilename($filename)
    {
        $this->rawData = $this->readFromFilename($filename);
        if (!isset ($this->rawData->repositories)) {
            $this->rawData->repositories = [];
        }
    }

    /**
     * Read data from an existing filename
     *
     * @param string $filename
     *
     * @returns \stdClass
     * @throws FileNotReadableException
     */
    protected function readFromFilename($filename)
    {
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
        if (isset ($output->repositories) && ! is_array($output->repositories)) {
            throw new FileNotReadableException("Key 'repositories' in {$filename} should be an array");
        }

        return $output;
    }
}
