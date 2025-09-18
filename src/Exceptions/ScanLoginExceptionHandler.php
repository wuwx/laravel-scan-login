<?php

namespace Wuwx\LaravelScanLogin\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Exception handler for scan login exceptions
 */
class ScanLoginExceptionHandler
{
    /**
     * Handle scan login exceptions and return appropriate responses
     */
    public static function handle(ScanLoginException $exception, Request $request): JsonResponse|Response
    {
        // For API requests, return JSON response
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($exception->toArray(), $exception->getCode() ?: 500);
        }

        // For web requests, redirect back with error message
        return redirect()->back()
            ->withErrors(['scan_login' => $exception->getMessage()])
            ->withInput();
    }

    /**
     * Convert exception to JSON response
     */
    public static function toJsonResponse(ScanLoginException $exception): JsonResponse
    {
        return response()->json($exception->toArray(), $exception->getCode() ?: 500);
    }

    /**
     * Get error response for AJAX requests
     */
    public static function getAjaxErrorResponse(ScanLoginException $exception): array
    {
        return [
            'success' => false,
            'error' => [
                'code' => $exception->getErrorCode(),
                'message' => $exception->getMessage(),
                'details' => $exception->getDetails(),
            ],
        ];
    }
}