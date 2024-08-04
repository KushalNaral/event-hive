<?php

use Illuminate\Http\JsonResponse;

/**
 * Success response with data
 *
 * @param mixed $data
 * @param string|null $message
 * @param int $statusCode
 * @return JsonResponse
 */
function successResponse($data = null, $message = null, $statusCode = 200): JsonResponse
{
    return response()->json([
        'success' => $statusCode,
        'message' => $message,
        'data'    => $data
    ], $statusCode);
}

/**
 * Error response
 *
 * @param string $message
 * @param int $statusCode
 * @param mixed $errors
 * @return JsonResponse
 */
function errorResponse($message, $statusCode = 400, $errors = null): JsonResponse
{
    return response()->json([
        'success' => false,
        'message' => $message,
        'errors'  => $errors
    ], $statusCode);
}
