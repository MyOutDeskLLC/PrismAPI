<?php

namespace Myoutdesk\PrismApi\Services;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Myoutdesk\PrismApi\Exceptions\ApiException;
use Myoutdesk\PrismApi\Exceptions\AuthenticationException;
use Myoutdesk\PrismApi\Traits\HandlesRestResponse;

class LoginService
{
    use HandlesRestResponse;

    protected $client;
    protected $config;
    protected $headers = [
        'content-type' => 'application/x-www-form-urlencoded',
        'accept' => 'application/json'
    ];

    public function __construct(ClientInterface $client = null, array $config = [])
    {
        $this->config = $config ?? [];
        $this->client = $client ?? new Client($config);
    }

    /**
     * Performs a login by calling createPeoSession
     *
     * @param $username
     * @param $password
     * @param $peoId
     * @return mixed
     * @throws ApiException
     */
    public function login(string $username, string $password, string $peoId)
    {
        try {
            $session = $this->decodeRestResponse($this->createPeoSession($username, $password, $peoId));
            return $session['sessionId'];
        } catch (ClientException $exception) {
            $response = $exception->getResponse();
            $status = $response->getStatusCode();
            throw new AuthenticationException(
                "Received $status: '{$response->getBody()}' when authenticating with API."
            );
        }
    }


    /**
     * Creates the authenticated PrismHR session
     *
     * @param $username
     * @param $password
     * @param $peoId
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function createPeoSession($username, $password, $peoId)
    {
        if(empty($username)) {
            throw new \InvalidArgumentException('Missing parameter $username');
        }
        if(empty($password)) {
            throw new \InvalidArgumentException('Missing parameter $password');
        }
        if(empty($peoId)) {
            throw new \InvalidArgumentException('Missing parameter $peoId');
        }
        if(empty($this->client->getConfig('base_uri'))) {
            throw new \InvalidArgumentException('Missing host from configuration');
        }

        return $this->client->request('POST', 'login/createPeoSession', [
            'form_params' => [
                'username' => $username,
                'password' => $password,
                'peoId' => $peoId
            ],
            'headers' => $this->headers
        ]);
    }
}