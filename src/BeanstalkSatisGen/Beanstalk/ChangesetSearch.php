<?php

namespace BeanstalkSatisGen\Beanstalk;

class ChangesetSearch
{

    /**
     * @var string[] array of files to search for
     */
    public $files;

    /**
     * @var string[] array of methods to search for, can be edit/add/delete
     */
    public $methods;

    /**
     * Returns whether or not the given files validate according to this changeset search
     *
     * @param string $file The file to validate
     * @param string $method The method to validate
     * @return boolean
     */
    public function validates($file, $method)
    {
        return in_array($file, $this->files) && in_array($method, $this->methods);
    }
}
