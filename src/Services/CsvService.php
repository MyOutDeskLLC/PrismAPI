<?php

namespace Myoutdesk\PrismApi\Services;

use League\Csv\Reader;
use League\Csv\Writer;

/**
 * Class CsvService
 * @package Myoutdesk\PrismApi\Services
 *
 * Service class to help users generate CSV data for PrismHR API
 */
class CsvService {

    public function __construct() {

    }

    public function getHeaders() {
        return [
            'Employee ID',
            'Date',
            'Hours',
            'Code'
        ];
    }

    public function createFromData(array $data) {
        $writer = Writer::createFromFileObject(new \SplTempFileObject());
        $writer->setDelimiter(',');
        $writer->insertOne($this->getHeaders());
        foreach($data as $index => $datum) {
            if(!is_array($datum)) {
                throw new \InvalidArgumentException("Invalid entry at $index - not an array.");
            }
            if(count(array_keys($datum)) !== count($this->getHeaders())) {
                throw new \InvalidArgumentException("Missing required data at $index.");
            }
            $writer->insertOne($datum);
        }
        return $writer->__toString();
    }
}