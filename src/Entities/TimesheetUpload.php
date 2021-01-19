<?php

namespace Myoutdesk\PrismApi\Entities;

use Myoutdesk\PrismApi\Interfaces\PrismResponse;

class TimesheetUpload implements PrismResponse
{
    protected $batchId;
    protected $paramData;
    protected $rejectResult;
    protected $acceptResult;
    protected $errorCode;
    protected $errorMessage;
    protected $extension;

    protected $importedRecords;
    protected $importFailures;

    protected $uploadKey;

    public static function createFromApiResponse(array $responseData)
    {
        $timesheetUpload = new self();
        $timesheetUpload->batchId = $responseData['batchId'];
        $timesheetUpload->paramData = $responseData['paramData'];
        $timesheetUpload->rejectResult = $responseData['rejectResult'];
        $timesheetUpload->acceptResult = $responseData['acceptResult'];
        $timesheetUpload->errorCode = $responseData['errorCode'];
        $timesheetUpload->errorMessage = $responseData['errorMessage'];
        $timesheetUpload->extension = $responseData['extension'];

        $timesheetUpload->importedRecords = (int)$responseData['importResult']['importFileRecords'];
        $timesheetUpload->importFailures = $responseData['importResult']['importFailure'];
        $timesheetUpload->uploadKey = $responseData['importResult']['uploadKey'];
        return $timesheetUpload;
    }

    public function getUploadKey()
    {
        return $this->uploadKey;
    }

    public function getUploadFailures()
    {
        return $this->importFailures;
    }

    public function getNumberOfImportRecords()
    {
        return $this->importedRecords;
    }

    public function hasErrors()
    {
        return is_array($this->importFailures) && count($this->importFailures) > 0;
    }

    public function getErrors()
    {
        return $this->importFailures;
    }
}