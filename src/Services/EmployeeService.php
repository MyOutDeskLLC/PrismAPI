<?php

namespace Myoutdesk\PrismApi\Services;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Myoutdesk\PrismApi\Entities\Employee;
use Myoutdesk\PrismApi\Exceptions\ApiException;
use Myoutdesk\PrismApi\Traits\HandlesRestResponse;

class EmployeeService
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

    /**
     * Returns a single employee from the API
     *
     * @param string $id
     * @param string $clientId
     * @return Employee
     * @throws ApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getEmployee(string $id, string $clientId)
    {
        if(empty($id)) {
            throw new \InvalidArgumentException('$id cannot be empty');
        }
        if(empty($clientId)) {
            throw new \InvalidArgumentException('$clientId cannot be empty');
        }
        try {
            $employeeFound = $this->decodeRestResponse($this->executeGetEmployee([$id], $clientId))['employee'];
            if(empty($employeeFound)) {
                return null;
            }
            return Employee::createFromApiResponse($employeeFound[0]);
        } catch (ClientException $exception) {
            $responseBody = json_decode($exception->getResponse()->getBody(), true);
            $statusCode = $exception->getResponse()->getStatusCode();
            throw new ApiException("Received $statusCode: '{$responseBody['errorMessage']}' when contacting API");
        }
    }

    /**
     * Returns one or more employees
     * If an array of more than 20 Id's is passed in, it will chunk them into sets of 20
     *
     * @param array $ids
     * @param string $clientId
     * @return array|Employee|Employee[]
     * @throws ApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getEmployees(array $ids, string $clientId)
    {
        $finalEmployeeList = [];
        foreach(array_chunk($ids, 15) as $chunk) {
            try {
                $employees = $this->decodeRestResponse($this->executeGetEmployee($chunk, $clientId))['employee'];
                $employeesInChunk = array_map(function($employee) {
                    return Employee::createFromApiResponse($employee);
                }, $employees);
            } catch (ClientException $exception) {
                $responseBody = json_decode($exception->getResponse()->getBody(), true);
                $statusCode = $exception->getResponse()->getStatusCode();
                throw new ApiException("Received $statusCode: '{$responseBody['errorMessage']}' when contacting API");
            }
            foreach($employeesInChunk as $employee) {
                $finalEmployeeList[] = $employee;
            }
        }
        return $finalEmployeeList;
    }

    /**
     * Returns an array of all employees assigned to this specific client id
     *
     * @param string $clientId
     * @return mixed
     * @throws ApiException
     */
    public function getAllEmployees(string $clientId)
    {
        try {
            $employees = $this->decodeRestResponse($this->executeGetAllEmployees($clientId))['employeeList']['employeeId'];
            return $this->getEmployees($employees, $clientId);
        } catch (ClientException $exception) {
            $responseBody = json_decode($exception->getResponse()->getBody(), true);
            $statusCode = $exception->getResponse()->getStatusCode();
            throw new ApiException("Received $statusCode: '{$responseBody['errorMessage']}' when contacting API");
        }
    }

    /**
     * Executes getEmployee against PrismHR's API
     *
     * We need to build this manually or it just breaks on the multi-dimensional array
     *
     * @param array $employeeIds
     * @param string $clientId
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function executeGetEmployee(array $employeeIds, string $clientId)
    {
        $resultingUrl = $this->client->getConfig('base_uri').'employee/getEmployee?';
        $resultingUrl .= preg_replace('/%5B[0-9]+%5D=/', '=', http_build_query(['employeeId' => $employeeIds], '', '&'));
        $resultingUrl .= '&clientId='.$clientId;
        $resultingUrl .= '&options=Person';
        return $this->client->request('GET', $resultingUrl, ['debug']);
    }

    /**
     * Returns a single dimensional array of all employees
     *
     * @param string $clientId
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function executeGetAllEmployees(string $clientId)
    {
        return $this->client->request('GET', 'employee/getEmployeeList', [
            'query' => [
                'clientId' => $clientId
            ]
        ]);
    }

    /**
     * Gets the employer of a given employee
     *
     * @param string $id employee id
     * @return mixed
     * @throws ApiException
     */
    public function getEmployer(string $id)
    {
        try {
            return $this->decodeRestResponse($this->executeGetEmployer($id)->getBody());
        } catch (ClientException $exception) {
            $responseBody = json_decode($exception->getResponse()->getBody(), true);
            $statusCode = $exception->getResponse()->getStatusCode();
            throw new ApiException("Received $statusCode: '{$responseBody['errorMessage']}' when contacting API");
        }
    }

    /**
     * Execute getEmployer against PrismHR's API
     *
     * @param string $id
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function executeGetEmployer(string $id)
    {
        return $this->client->request('GET', 'employee/getEmployersInfo', [
            'query' => [
                'employeeId' => $id
            ]
        ]);
    }
}