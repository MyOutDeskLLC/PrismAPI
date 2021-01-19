<?php

namespace Myoutdesk\PrismApi\Entities;

use Myoutdesk\PrismApi\Interfaces\PrismResponse;

class Employee implements PrismResponse
{
    public $id;
    public $lastName;
    public $firstName;
    public $emailAddress;

    public static function createFromApiResponse(array $responseData)
    {
        $employee = new self();
        $employee->id = $responseData['id'] ?? '';
        $employee->firstName = $responseData['firstName'] ?? '';
        $employee->lastName = $responseData['lastName'] ?? '';
        $employee->emailAddress = $responseData['emailAddress'] ?? '';
        return $employee;
    }
}