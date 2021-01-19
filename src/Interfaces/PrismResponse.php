<?php

namespace Myoutdesk\PrismApi\Interfaces;

interface PrismResponse {
    public static function createFromApiResponse(array $responseData);
}