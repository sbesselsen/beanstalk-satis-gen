<?php

namespace BeanstalkSatisGen\Beanstalk;

use BeanstalkSatisGen\ReaderException;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class Api implements LoggerAwareInterface
{

    use LoggerAwareTrait;

    /**
     * @var string
     */
    protected $subdomain;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $token;

    /**
     * @param string $subdomain The subdomain of the beanstalk account
     * @param string $username The username of the beanstalk account
     * @param string $token A token that has access to the given account
     * @param LoggerInterface $logger
     */
    public function __construct($subdomain, $username, $token, LoggerInterface $logger)
    {
        $this->subdomain = $subdomain;
        $this->username = $username;
        $this->token = $token;
        $this->setLogger($logger);
    }

    /**
     * @param string $path The path to load of the beanstalk API
     * @param array $query The query to send to the URL
     *
     * @return mixed
     */
    public function loadJson($path, array $query)
    {
        $auth = rawurlencode($this->username) . ':' . rawurlencode($this->token);
        $url = sprintf('https://%s@%s.beanstalkapp.com/api/%s.json', $auth, $this->subdomain, $path);

        if ($query) {
            $url .= '?' . http_build_query($query);
        }

        $this->logger->debug('Calling: ' . $url);

        $data = @file_get_contents($url);

        if (!$data) {
            throw new ReaderException("No response from the server.");
        }

        if (($output = json_decode($data)) === null) {
            throw new ReaderException("Response from the server is not valid JSON.");
        }

        return $output;
    }
}
