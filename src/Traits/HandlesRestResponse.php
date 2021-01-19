<?php

namespace Myoutdesk\PrismApi\Traits;

use GuzzleHttp\Psr7\Response;

trait HandlesRestResponse
{
    /**
     * Decodes a guzzle response (json) into a PHP array
     *
     * @param Response $response
     * @param string $className
     * @return mixed
     */
    public function decodeRestResponse(Response $response, string $className = '')
    {
        return json_decode($response->getBody(), true);
    }
}