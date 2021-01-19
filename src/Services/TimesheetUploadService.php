<?php

namespace Myoutdesk\PrismApi\Services;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Myoutdesk\PrismApi\Entities\TimesheetUpload;
use Myoutdesk\PrismApi\Exceptions\ApiException;
use Myoutdesk\PrismApi\Traits\HandlesRestResponse;

class TimesheetUploadService
{
    use HandlesRestResponse;

    protected $client;
    protected $config;
    protected $headers = [
        'content-type' => 'application/x-www-form-urlencoded',
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

    public function getTimesheetData(string $batchId, string $clientId)
    {
        try {
            return $this->decodeRestResponse($this->executeGetTimesheetData($batchId, $clientId));
        } catch  (ClientException $exception) {
            $response = $exception->getResponse();
            $status = $response->getStatusCode();
            throw new ApiException(
                "Received $status: '{$response->getBody()}' from API."
            );
        }
    }

    /**
     * Uploads the raw CSV data to the prismHR API
     *
     * @param string $batchId
     * @param string $clientId
     * @param string $userId
     * @param string $rawData
     */
    public function upload(string $batchId, string $clientId, string $userId, string $rawData)
    {
        if(empty($clientId)) {
            throw new \InvalidArgumentException('$clientId cannot be empty');
        }
        if(empty($userId)) {
            throw new \InvalidArgumentException('$userId cannot be empty');
        }
        if(empty($batchId)) {
            throw new \InvalidArgumentException('$batchId cannot be empty');
        }
        if(empty($rawData)) {
            throw new \InvalidArgumentException('$rawData cannot be empty');
        }

        try {
            return TimesheetUpload::createFromApiResponse($this->decodeRestResponse($this->executeUpload([$batchId], $clientId, $userId, $rawData)));
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

    public function finalizeUpload(string $batchId, string $clientId, string $checksum)
    {
        if(empty($clientId)) {
            throw new \InvalidArgumentException('$clientId cannot be empty');
        }
        if(empty($batchId)) {
            throw new \InvalidArgumentException('$batchId cannot be empty');
        }
        if(empty($checksum)) {
            throw new \InvalidArgumentException('$checksum cannot be empty');
        }
        try {
            return $this->decodeRestResponse($this->executeFinalizePrismBatchEntry($batchId, $clientId, $checksum));
        } catch  (ClientException $exception) {
            $response = $exception->getResponse();
            $status = $response->getStatusCode();
            throw new ApiException(
                "Received $status: '{$response->getBody()}' from API."
            );
        }
    }

    public function getBatchStatus(string $batchId, string $clientId)
    {
        if(empty($clientId)) {
            throw new \InvalidArgumentException('$clientId cannot be empty');
        }
        if(empty($batchId)) {
            throw new \InvalidArgumentException('$batchId cannot be empty');
        }
        try {
            return $this->decodeRestResponse($this->executeGetBatchStatus($batchId, $clientId));
        } catch  (ClientException $exception) {
            $response = $exception->getResponse();
            $status = $response->getStatusCode();
            throw new ApiException(
                "Received $status: '{$response->getBody()}' from API."
            );
        }
    }

    public function getAvailableTemplates(string $clientId)
    {
        if(empty($clientId)) {
            throw new \InvalidArgumentException('$clientId cannot be empty');
        }
        try {
            return $this->decodeRestResponse($this->executeGetParamData($clientId))['paramData']['template'];
        } catch  (ClientException $exception) {
            $response = $exception->getResponse();
            $status = $response->getStatusCode();
            throw new ApiException(
                "Received $status: '{$response->getBody()}' from API."
            );
        }
    }

    public function getParamData(string $clientId)
    {
        if(empty($clientId)) {
            throw new \InvalidArgumentException('$clientId cannot be empty');
        }
        try {
            return $this->decodeRestResponse($this->executeGetParamData($clientId));
        } catch  (ClientException $exception) {
            $response = $exception->getResponse();
            $status = $response->getStatusCode();
            throw new ApiException(
                "Received $status: '{$response->getBody()}' from API."
            );
        }
    }

    /**
     * @param array $batchList
     * @param string $clientId
     * @param string $userId
     * @param string $uploadId importResult -> uploadKey from previous response
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function approveUpload(array $batchList, string $clientId, string $userId, string $uploadId)
    {
        try {
            return $this->decodeRestResponse($this->executeApproval($uploadId, $clientId, $userId, $batchList));
        } catch  (ClientException $exception) {
            $response = $exception->getResponse();
            $status = $response->getStatusCode();
            throw new ApiException(
                "Received $status: '{$response->getBody()}' from API."
            );
        }
    }

    protected function executeApproval(string $uploadId, string $clientId, string $userId, array $batchList)
    {
        return $this->client->request('POST', 'timesheet/accept', [
            'form_params' => [
                'clientId' => $clientId,
                'templateId' => 'MyTimeIn',
                'userId' => $userId,
                'batchList' => $batchList[0],
                'uploadId' => $uploadId
            ]
        ]);
    }

    protected function executeGetParamData(string $clientId)
    {
        return $this->client->request('GET', 'timesheet/getParamData', [
            'query' => [
                'clientId' => $clientId
            ]
        ]);
    }

    protected function executeGetBatchStatus(string $batchId, string $clientId)
    {
        return $this->client->request('GET', 'timesheet/getBatchStatus', [
            'query' => [
                'batchId' => $batchId,
                'clientId' => $clientId
            ]
        ]);
    }

    public function executeGetTimesheetData(string $batchId, string $clientId)
    {
        return $this->client->request('GET', 'timesheet/getTimeSheetData', [
            'query' => [
                'batchId' => $batchId,
                'clientId' => $clientId
            ]
        ]);
    }

    public function executeFinalizePrismBatchEntry(string $batchId, string $clientId, string $checkSum)
    {
        return $this->client->request('POST', 'timesheet/finalizePrismBatchEntry', [
            'form_params' => [
                'clientId' => $clientId,
                'batchId' => $batchId,
                'checksum' => $checkSum
            ]
        ]);
    }

    protected function executeUpload(array $batchList, string $clientId, string $userId, string $rawData)
    {
        return $this->client->request('POST', 'timesheet/upload', [
            'form_params' => [
                'clientId' => $clientId,
                'templateId' => 'MyTimeIn',
                'userId' => $userId,
                'batchList' => $batchList[0],
                'fileData' => $rawData
            ]
        ]);
    }
}