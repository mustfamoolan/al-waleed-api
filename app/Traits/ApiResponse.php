<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Return a success JSON response.
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $code
     * @return JsonResponse
     */
    protected function successResponse($data = null, ?string $message = null, int $code = 200): JsonResponse
    {
        $response = [
            'status' => 'success',
        ];

        if ($message) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * Return an error JSON response.
     *
     * @param string|null $message
     * @param int $code
     * @param mixed $errors
     * @return JsonResponse
     */
    protected function errorResponse(?string $message = null, int $code = 400, $errors = null): JsonResponse
    {
        $response = [
            'status' => 'error',
        ];

        if ($message) {
            $response['message'] = $message;
        }

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a validation error JSON response.
     *
     * @param mixed $errors
     * @param string|null $message
     * @return JsonResponse
     */
    protected function validationErrorResponse($errors, ?string $message = 'Validation failed'): JsonResponse
    {
        return $this->errorResponse($message, 422, $errors);
    }

    /**
     * Return an unauthorized JSON response.
     *
     * @param string|null $message
     * @return JsonResponse
     */
    protected function unauthorizedResponse(?string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Return a not found JSON response.
     *
     * @param string|null $message
     * @return JsonResponse
     */
    protected function notFoundResponse(?string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }
}

