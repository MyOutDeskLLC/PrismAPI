<?php

namespace Myoutdesk\PrismApi\Services;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Myoutdesk\PrismApi\Exceptions\ApiException;
use Myoutdesk\PrismApi\Traits\HandlesRestResponse;

class ClientService
{
    use HandlesRestResponse;

    protected $client;
    protected $config;
    protected $headers = [
        'content-type' => 'application/json',
        'accept' => 'application/json'
    ];

    public function __construct(ClientInterface $client = null, array $config = [])
    {
        $this->config = $config ?? [];
        $this->client = $client ?? new Client($config);
    }

    public function getAllClients()
    {
        try {
            return $this->decodeRestResponse($this->executeGetAllClients());
        } catch (ClientException $exception) {
            $responseBody = $this->decodeRestResponse($exception->getResponse());
            $statusCode = $exception->getResponse()->getStatusCode();
            throw new ApiException("Received $statusCode: '{$responseBody['errorMessage']}' when contacting API");
        }
    }

    public function executeGetAllClients()
    {
        return $this->client->request('GET', 'clientMaster/getClientList');
    }
}