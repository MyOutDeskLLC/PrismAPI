<?php

namespace Myoutdesk\PrismApi;

use GuzzleHttp\Client;
use Myoutdesk\PrismApi\Services\ClientService;
use Myoutdesk\PrismApi\Services\CsvService;
use Myoutdesk\PrismApi\Services\EmployeeService;
use Myoutdesk\PrismApi\Services\LoginService;
use Myoutdesk\PrismApi\Services\PayrollService;
use Myoutdesk\PrismApi\Services\TimesheetUploadService;

class PrismApi
{
    protected $client;
    protected $session;
    protected $config = [];
    protected $validVersions = ['1.18','1.19','1.20','1.21','1.22'];

    /**
     * PrismApi constructor.
     *
     * @param string $version api version to use (without api-)
     * @param string $hostname defaults to api.prismhr.com, change for on-prem
     */
    public function __construct($version = '1.22', $hostname = 'https://api.prismhr.com/' )
    {
        $this->config = [
            'base_uri' => $this->generateConfiguration($hostname, $version),
            'headers' => []
        ];
        $this->client = new Client($this->config);
    }

    /**
     * Returns the payroll service for direct use
     *
     * @return PayrollService
     */
    public function getPayrollService()
    {
        return new PayrollService($this->client);
    }

    /**
     * Returns the timesheet upload service for direct use
     *
     * @return TimesheetUploadService
     */
    public function getTimesheetUploadService()
    {
        return new TimesheetUploadService($this->client);
    }

    /**
     * Authenticates the user specified and updates headers
     *
     * @param $username string
     * @param $password string
     * @param $peoId string from the backend of PrismHR, 350*HSG for infiniti
     * @return bool TRUE if successful, false otherwise
     * @throws Exceptions\ApiException
     */
    public function authenticate(string $username, string $password, string $peoId)
    {
        $loginService = new LoginService($this->client);
        $this->setSession($loginService->login($username, $password, $peoId));
        return true;
    }

    /**
     * Used to set the session to allow for session reuse
     *
     * @param string $sessionId
     */
    public function setSession(string $sessionId)
    {
        $this->session = $sessionId;
        $this->config['headers']['sessionId'] = $this->session;
        $this->client = new Client($this->config);
    }

    /**
     * Returns the session ID of the currently authenticated user
     *
     * @return string
     */
    public function getSession()
    {
        return $this->session;
    }

    public function getEmployee($id, string $clientId)
    {
        $employeeService = new EmployeeService($this->client);
        return $employeeService->getEmployee($id, $clientId);
    }

    /**
     * Returns an employee, or, if an array, returns an array of employees
     *
     * @param $id
     * @return array|Entities\Employee|Entities\Employee[]
     * @throws Exceptions\ApiException
     */
    public function getEmployees($id, string $clientId)
    {
        $employeeService = new EmployeeService($this->client);
        return $employeeService->getEmployees($id, $clientId);
    }

    /**
     * Returns all employees in a given payroll batch (by their prism id)
     *
     * @param string $batchId
     * @param string $clientId
     */
    public function getEmployeesInPayrollBatch(string $batchId, string $clientId)
    {
        $payrollService = new PayrollService($this->client);
        return $payrollService->getEmployeesForBatch($batchId, $clientId);
    }

    /**
     * Updates employees in the given payroll batch
     *
     * @param string $batchId
     * @param string $clientId
     * @param array $employees
     * @return array|mixed
     * @throws Exceptions\ApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateEmployeesInPayrollBatch(string $batchId, string $clientId, array $employees)
    {
        $payrollService = new PayrollService($this->client);
        $batchInfo = $this->getPayrollBatch($batchId, $clientId);
        return $payrollService->updateEmployeesForPayrollBatch($batchId, $clientId, $employees, $batchInfo);
    }


    /**
     * Returns a list of employees this user account can access
     *
     * @param string $clientId
     * @return array|mixed|Entities\Employee|Entities\Employee[]
     * @throws Exceptions\ApiException
     */
    public function getAllEmployees(string $clientId)
    {
        $employeeService = new EmployeeService($this->client);
        return $employeeService->getAllEmployees($clientId);
    }

    /**
     * Attempts to locate an MTI created batch in Prism or creates one
     *
     * @param \DateTimeInterface $date
     * @param string $clientId
     * @param array $employeeIds
     *
     * @return string the ID of the batch found or created
     *
     * @throws Exceptions\ApiException
     */
    public function findOrCreatePayrollBatch(\DateTimeInterface $date, string $clientId, array $employeeIds)
    {
        $payrollService = new PayrollService($this->client);
        $firstFoundBatch = $payrollService->getBatchListByDate($date, $clientId);
        if(empty($firstFoundBatch)) {
            return $payrollService->createBatch($date, $clientId, $employeeIds);
        }
        return $firstFoundBatch;
    }

    /**
     * Returns the payroll batches between the given dates
     *
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     * @param string $clientId
     */
    public function getPayrollBatches(\DateTimeInterface $startDate, \DateTimeInterface $endDate, string $clientId)
    {
        $payrollService = new PayrollService($this->client);
        return $payrollService->getBatchListByDateRange($startDate, $endDate, $clientId);
    }

    /**
     * Returns the payroll batch with the given id
     *
     * @param string $batchId
     * @param string $clientId
     * @return array|mixed
     * @throws Exceptions\ApiException
     */
    public function getPayrollBatch(string $batchId, string $clientId)
    {
        $payrollService = new PayrollService($this->client);
        return $payrollService->getPayrollBatch($batchId, $clientId);
    }

    /**
     * Returns an instance of the CSV service
     *
     * @return CsvService
     */
    public function getCsvService()
    {
        return new CsvService();
    }

    /**
     * Returns a list of available templates for this user account to use
     *
     * @param string $clientId
     * @return mixed
     * @throws Exceptions\ApiException
     */
    public function getAvailableTimesheetTemplates(string $clientId)
    {
        $timesheetService = new TimesheetUploadService($this->client);
        return $timesheetService->getAvailableTemplates($clientId);
    }

    public function getTimesheetData(string $batchId, string $clientId)
    {
        $timesheetService = new TimesheetUploadService($this->client);
        return $timesheetService->getTimesheetData($batchId, $clientId);
    }

    /**
     * Uploads an array of Timesheet data
     *
     * @param string $batchId - the open batch ID in PrismHR
     * @param string $clientId - the client ID the batch was opened against (company, basically)
     * @param string $userId - the uploader. Used to keep track of concurrency information
     * @param string $rawData - raw CSV data from file_get_contents or a data stream
     */
    public function uploadTimesheets(string $batchId, string $clientId, string $userId, string $rawData)
    {
        $timesheetService = new TimesheetUploadService($this->client);
        return $timesheetService->upload($batchId, $clientId, $userId, $rawData);
    }

    /**
     * Get param data (batches, pay groups,etc)
     *
     * @param string $clientId
     * @return mixed
     * @throws Exceptions\ApiException
     */
    public function getTimesheetParamData(string $clientId)
    {
        $timesheetService = new TimesheetUploadService($this->client);
        return $timesheetService->getParamData($clientId);
    }

    /**
     * This operation returns the current status of the specified payroll batch. The returned checksum is used with /timesheet/finalizePrismBatchEntry in order to ensure that the payroll batch has not been changed since it was last read
     *
     * @param string $batchId
     * @param string $clientId
     */
    public function getTimesheetBatchStatus(string $batchId, string $clientId)
    {
        $timesheetService = new TimesheetUploadService($this->client);
        return $timesheetService->getBatchStatus($batchId, $clientId);
    }

    public function finalizeTimesheetUpload(string $batchId, string $clientId) {
        $timesheetService = new TimesheetUploadService($this->client);
        $batchStatus = $timesheetService->getBatchStatus($batchId, $clientId);
        if($batchStatus['batchStatus'] !== 'TS.READY' && $batchStatus['errorCode'] !== "0") {
            return false;
        }
        return $timesheetService->finalizeUpload($batchId, $clientId, $batchStatus['checksum']);
    }

    /**
     * Flags the payroll upload on the given batch id as approved
     *
     * @param string $batchId
     * @param string $clientId
     * @param string $userId
     * @param string $uploadId
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function approveTimesheetUpload(string $batchId, string $clientId, string $userId, string $uploadId)
    {
        $timesheetService = new TimesheetUploadService($this->client);
        return $timesheetService->approveUpload([$batchId], $clientId, $userId, $uploadId);
    }

    /**
     * Creates a payroll batch with the given date, client id, and employee id's
     *
     * @param \DateTimeInterface $date the date for this payroll batch
     * @param string $clientId the client(company) specified - example: 1111 for DEMO
     * @param array $employeeIds the employee ID's eligible to be put into this batch
     * @return string the ID of the batch created
     * @throws Exceptions\ApiException
     */
    public function createPayrollBatch(\DateTimeInterface $date, string $clientId, array $employeeIds)
    {
        $payrollService = new PayrollService($this->client);
        return $payrollService->createBatch($date, $clientId, $employeeIds);
    }

    /**
     * Returns all clients found (companies)
     *
     * @return mixed
     * @throws Exceptions\ApiException
     */
    public function getAllClients()
    {
        $clientService = new ClientService($this->client);
        return $clientService->getAllClients();
    }

    /**
     * @param string $hostname
     * @param string $version
     * @return string
     */
    protected function generateConfiguration(string $hostname, string $version): string
    {
        if(empty($hostname) || !filter_var($hostname, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid $hostname given');
        }
        if(empty($version) || !in_array($version, $this->validVersions, true)) {
            throw new \InvalidArgumentException('Invalid $version given');
        }
        return $hostname . 'api-' . $version . '/services/rest/';
    }
}