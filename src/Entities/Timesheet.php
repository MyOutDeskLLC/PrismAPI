<?php

namespace Myoutdesk\PrismApi\Entities;

use Myoutdesk\PrismApi\Interfaces\PrismResponse;

class Timesheet implements PrismResponse
{
    public $chargeDate;
    public $payCode;
    public $hoursPaid;
    public $hoursWorked;
    public $payRate;
    public $payAmount;

    public static function createFromApiResponse(array $responseData)
    {
        $timesheet = new self();
        $timesheet->chargeDate = $responseData['charge_date'] ?? null;
        $timesheet->payCode = $responseData['pay_code'] ?? '';
        $timesheet->hoursPaid = $responseData['hrs_units_paid'] ?? 0.00;
        $timesheet->hoursWorked = $responseData['hours_worked'] ?? 0.00;
        $timesheet->payRate = $responseData['pay_rate'] ?? 0.00;
        $timesheet->payAmount = $responseData['pay_amount'] ?? 0.00;
        return $timesheet;
    }
}