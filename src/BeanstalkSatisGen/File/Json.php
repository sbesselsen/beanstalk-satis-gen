<?php

namespace BeanstalkSatisGen\File;

use BeanstalkSatisGen\FileNotReadableException;

class Json
{
    protected $content;

    /**
     * Constructs an object from a file
     *
     * @param string $path Path to the file
     *
     * @return static
     *
     * @throws FileNotReadableException
     */
    public static function fromFile($path)
    {
        $file = new static();
        $file->loadFromFilename($path);
        return $file;
    }

    /**
     * Save the Satis file to the specified path
     *
     * @param string $path Path to save to
     */
    public function saveToFile($path)
    {
        $data = json_encode($this->content, JSON_PRETTY_PRINT);
        file_put_contents($path, $data);
    }

    /**
     * Set content for this object
     *
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = json_decode($content);
    }

    /**
     * Load data from an existing filename
     *
     * @param string $path The path to the file
     *
     * @throws FileNotReadableException
     */
    protected function loadFromFilename($path)
    {
        $this->content = $this->readFromFilename($path);
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
