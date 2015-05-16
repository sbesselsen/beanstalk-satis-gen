<?php
namespace BeanstalkSatisGen\File;

use BeanstalkSatisGen\FileNotReadableException;

class Config extends Json
{
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
     * {@inheritdoc}
     */
    protected function loadFromFilename($filename)
    {
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
            } elseif ($data->repository_filters) {
                throw new FileNotReadableException("If config file contains filters, filters should be an object");
            }
        }
    }
}
