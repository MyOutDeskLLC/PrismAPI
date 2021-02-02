<?php


namespace Myoutdesk\PrismApi\Services;


use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Myoutdesk\PrismApi\Exceptions\ApiException;
use Myoutdesk\PrismApi\Traits\HandlesRestResponse;

class PayrollService
{
    use HandlesRestResponse;

    protected $client;
    protected $config;
    protected $headers = [
        'content-type' => 'application/json',
        'accept' => 'application/json'
    ];

    /**
     * PayrollService constructor.
     *
     * @param ClientInterface|null $client
     * @param array $config MUST CONTAIN AUTHENTICATION IF SET BY HAND
     */
    public function __construct(ClientInterface $client = null, array $config = [])
    {
        $this->config = $config ?? [];
        $this->client = $client ?? new Client($config);
    }

    /**
     * Returns the payroll batches between the start date, end date
     *
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     * @param string $clientId
     * @return array|mixed
     * @throws ApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getBatchListByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate, string $clientId)
    {
        if(empty($clientId)) {
            throw new \InvalidArgumentException('Client ID cannot be empty');
        }
        $startDate = $startDate->format('Y-m-d');
        $endDate = $endDate->format('Y-m-d');
        try {
            $batches = $this->decodeRestResponse($this->executeGetBatchListByDate($startDate, $endDate, $clientId))['batchList'];
            // The server responds when creating with "batchNum" but when querying it's called "batchId" instead
            return $batches[0]['batchId'];
        } catch (ClientException $exception) {
            $response = $exception->getResponse();
            $status = $response->getStatusCode();
            // If none exist, just return an empty array instead of a 404 error
            if($status === 404) {
                return [];
            }
            throw new ApiException(
                "Received $status: '{$response->getBody()}' when authenticating with API."
            );
        }
    }

    /**
     * Returns the first batchlist found for a given date, client OR creates one
     *
     * @param \DateTimeInterface $date
     * @param string $clientId client(company) id
     * @return array|mixed
     * @throws ApiException
     */
    public function getBatchListByDate(\DateTimeInterface $date, string $clientId)
    {
        if(empty($clientId)) {
            throw new \InvalidArgumentException('Client ID cannot be empty');
        }
        $endDate = clone $date;
        $startDate = $date->format('Y-m-d');
        $endDate = $endDate->format('Y-m-d');
        try {
            $batches = $this->decodeRestResponse($this->executeGetBatchListByDate($startDate, $endDate, $clientId))['batchList'];
            // The server responds when creating with "batchNum" but when querying it's called "batchId" instead
            return $batches['batchId'];
        } catch (ClientException $exception) {
            $response = $exception->getResponse();
            $status = $response->getStatusCode();
            // If none exist, just return an empty array instead of a 404 error
            if($status === 404) {
                return [];
            }
            throw new ApiException(
                "Received $status: '{$response->getBody()}' when authenticating with API."
            );
        }
    }

    public function getPayrollBatch(string $batchId, string $clientId)
    {
        if(empty($clientId)) {
            throw new \InvalidArgumentException('$clientId cannot be empty');
        }
        if(empty($batchId)) {
            throw new \InvalidArgumentException('$batchId cannot be empty');
        }
        try {
            return $this->decodeRestResponse($this->executeGetBatchInfo($clientId, $batchId));
        } catch (ClientException $exception) {
            $response = $exception->getResponse();
            $status = $response->getStatusCode();
            // If none exist, just return an empty array instead of a 404 error
            if($status === 404) {
                return [];
            }
            throw new ApiException(
                "Received $status: '{$response->getBody()}' when authenticating with API."
            );
        }
    }

    public function executeGetBatchInfo(string $clientId, string $batchId)
    {
        return $this->client->request('GET', 'payroll/getBatchInfo', [
            'query' => [
                'clientId' => $clientId,
                'batchId' => $batchId
            ]
        ]);
    }

    /**
     * Creates a given payroll batch to upload timesheets against. Requires employee ID's be set.
     *
     * @param \DateTimeInterface $date
     * @param string $clientId
     * @param array $employeeIds
     * @return mixed
     * @throws ApiException
     */
    public function createBatch(\DateTimeInterface $date, string $clientId, array $employeeIds)
    {
        if(empty($clientId)) {
            throw new \InvalidArgumentException('Client ID cannot be empty');
        }
        if(empty($employeeIds)) {
            throw new \InvalidArgumentException('At least 1 employee must be specified');
        }
        $endDate = clone $date;
        $startDate = $date->format('Y-m-d');
        $endDate->modify('+1 week');
        $endDate = $endDate->format('Y-m-d');
        try {
            $response = $this->decodeRestResponse($this->executeCreateBatch($startDate, $endDate, $clientId, $employeeIds));
            return $response['batchNum'];
        } catch (ClientException $exception) {
            $response = $exception->getResponse();
            $status = $response->getStatusCode();
            throw new ApiException(
                "Received $status: '{$response->getBody()}' from API."
            );
        } catch (ServerException $exception) {
            $response = $exception->getResponse();
            $status = $response->getStatusCode();
            throw new ApiException(
                "Received $status: '{$response->getBody()}' from API."
            );
        }
    }

    /**
     * Executes the create batch against PrismHR's API
     *
     * @param $startDate
     * @param $endDate
     * @param $clientId
     * @param $employeeIds
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function executeCreateBatch($startDate, $endDate, $clientId, $employeeIds)
    {
        $employeeData = [];
        foreach($employeeIds as $employee) {
            $employeeData[] = [
                'employeeId' => $employee,
                'periodStart' => $startDate,
                'periodEnd' => $endDate,
            ];
        }
        return $this->client->request('POST', 'payroll/createPayrollBatches', [
            'json' => [
                'clientId' => $clientId,
                'payDate' => $endDate,
                'batchType' => 'M',
                'employee' => $employeeData
            ]
        ]);
    }

    /**
     * Executes the getBatchlistByDate against PrismHR's API
     *
     * @param string $startDate
     * @param string $endDate
     * @param string $clientId
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function executeGetBatchListByDate(string $startDate, string $endDate, string $clientId)
    {
        return $this->client->request('GET', 'payroll/getBatchListByDate', [
            'query' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'clientId' => $clientId,
                'dateType' => 'PAY'
            ]
        ]);
    }
}