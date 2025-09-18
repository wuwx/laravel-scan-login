<?php

namespace Wuwx\LaravelScanLogin\Exceptions;

use Exception;

/**
 * Base exception class for scan login functionality
 */
class ScanLoginException extends Exception
{
    /**
     * Error code for API responses
     */
    protected string $errorCode;

    /**
     * Additional error details
     */
    protected array $details;

    public function __construct(
        string $message = '',
        string $errorCode = 'SCAN_LOGIN_ERROR',
        array $details = [],
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->details = $details;
    }

    /**
     * Get the error code for API responses
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get additional error details
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * Convert exception to array format for API responses
     */
    public function toArray(): array
    {
        return [
            'success' => false,
            'error' => [
                'code' => $this->getErrorCode(),
                'message' => $this->getMessage(),
                'details' => $this->getDetails(),
            ],
        ];
    }
}