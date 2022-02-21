<?php

namespace ECSPrefix20220221\React\Socket;

/**
 * Decorates an existing Connector to always use a fixed, preconfigured URI
 *
 * This can be useful for consumers that do not support certain URIs, such as
 * when you want to explicitly connect to a Unix domain socket (UDS) path
 * instead of connecting to a default address assumed by an higher-level API:
 *
 * ```php
 * $connector = new React\Socket\FixedUriConnector(
 *     'unix:///var/run/docker.sock',
 *     new React\Socket\UnixConnector()
 * );
 *
 * // destination will be ignored, actually connects to Unix domain socket
 * $promise = $connector->connect('localhost:80');
 * ```
 */
class FixedUriConnector implements \ECSPrefix20220221\React\Socket\ConnectorInterface
{
    private $uri;
    private $connector;
    /**
     * @param string $uri
     * @param ConnectorInterface $connector
     */
    public function __construct($uri, \ECSPrefix20220221\React\Socket\ConnectorInterface $connector)
    {
        $this->uri = $uri;
        $this->connector = $connector;
    }
    public function connect($_)
    {
        return $this->connector->connect($this->uri);
    }
}
