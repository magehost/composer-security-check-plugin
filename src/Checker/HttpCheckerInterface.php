<?php

namespace FancyGuy\Composer\SecurityCheck\Checker;

interface HttpCheckerInterface extends CheckerInterface
{

    const DEFAULT_ENDPOINT = 'https://security.symfony.com/check_lock';
    const DEFAULT_TIMEOUT = 20;

    /**
     * Sets the HTTP timeout in seconds
     *
     * @param string $timeout The HTTP timeout in seconds
     */
    public function setTimeout($timeout);

    /**
     * Sets the security checker service URL
     *
     * @param string $endpoint The HTTP endpoint for the security checker service
     */
    public function setEndpoint($endpoint);

    public function testConnection();
}
