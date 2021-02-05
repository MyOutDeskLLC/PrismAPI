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
            return $this->decodeRestResponse($this->executeGetBatchListByDate($startDate, $endDate, $clientId))['batchList'];
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

    public function getEmployeesForBatch(string $batchId, string $clientId)
    {
        if(empty($clientId)) {
            throw new \InvalidArgumentException('Client ID cannot be empty');
        }
        if(empty($batchId)) {
            throw new \InvalidArgumentException('Client ID cannot be empty');
        }
        try {
            return $this->decodeRestResponse($this->executeGetEmployeeListByBatch($batchId, $clientId))['employeeIdList']['employeeId'];
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

    /**
     * Updates the employees in a given payroll batch
     *
     * @param string $batchId
     * @param string $clientId
     * @param array $employees
     * @param $originalBatch
     * @return array|mixed
     * @throws ApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateEmployeesForPayrollBatch(string $batchId, string $clientId, array $employees, $originalBatch)
    {
        if(empty($clientId)) {
            throw new \InvalidArgumentException('$clientId cannot be empty');
        }
        if(empty($batchId)) {
            throw new \InvalidArgumentException('$batchId cannot be empty');
        }
        try {
            return $this->decodeRestResponse($this->executeUpdateEmployeesInBatch($batchId, $clientId, $employees, $originalBatch));
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
        return $this->client->request('GET', 'payroll/getPayrollBatchWithOptions', [
            'query' => [
                'clientId' => $clientId,
                'batchId' => $batchId
            ]
        ]);
    }

    /**
     * Creates a given payroll batch to upload timesheets against. Requires employee ID's be set.
     *
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     * @param string $clientId
     * @param array $employeeIds
     * @return mixed
     * @throws ApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createBatch(\DateTimeInterface $startDate, \DateTimeInterface $endDate, string $clientId, array $employeeIds)
    {
        if(empty($clientId)) {
            throw new \InvalidArgumentException('Client ID cannot be empty');
        }
        if(empty($employeeIds)) {
            throw new \InvalidArgumentException('At least 1 employee must be specified');
        }
        $startDate = $startDate->format('Y-m-d');
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

    /**
     * Gets a list of employees by batch date
     *
     * @param string $batchId
     * @param string $clientId
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function executeGetEmployeeListByBatch(string $batchId, string $clientId)
    {
        return $this->client->request('GET', 'payroll/getEmployeeForBatch', [
            'query' => [
                'batchId' => $batchId,
                'clientId' => $clientId
            ]
        ]);
    }

    /**
     * Updates the employees present in a manual batch
     *
     * @param string $batchId
     * @param string $clientId
     * @param array $employees
     * @param $originalBatch
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function executeUpdateEmployeesInBatch(string $batchId, string $clientId, array $employees, $originalBatch)
    {
        $checksum = $originalBatch['batchControl']['checksum'];
        $periodStart = $originalBatch['batchControl']['manualBatch'][0]['periodStart'];
        $periodEnd = $originalBatch['batchControl']['manualBatch'][0]['periodEnd'];
        $weeksWorked = $originalBatch['batchControl']['manualBatch'][0]['weeksWorked'];
        $deductPeriod = (int)$originalBatch['batchControl']['manualBatch'][0]['deductPeriod'];
        $processor = $originalBatch['batchControl']['processor'];

        $employeeData = [];
        foreach($employees as $employee) {
            $employeeData[] = [
                'employeeId' => $employee,
                'periodStart' => $periodStart,
                'periodEnd' => $periodEnd,
                'weeksWorked' => $weeksWorked,
                'deductPeriod' => $deductPeriod > 0 ? $deductPeriod : 1
            ];
        }

        return $this->client->request('POST', 'payroll/updatePayrollBatchWithOptions', [
            'json' => [
                'clientId' => $clientId,
                'batchControl' => [
                    'checksum' => $checksum,
                    'batchId' => $batchId,
                    'batchType' => 'M',
                    'processor' => $processor,
                    'payDate' => $originalBatch['batchControl']['payDate'],
                    'remoteCutoffDate' => $originalBatch['batchControl']['remoteCutoffDate'],
                    'deliveryDate' => $originalBatch['batchControl']['deliveryDate'],
                    'manualBatch' => $employeeData
                ]
            ]
        ]);
    }
}