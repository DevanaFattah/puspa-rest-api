<?php

namespace App\Http\Helpers;

use Illuminate\Http\JsonResponse;

trait ResponseFormatter
{
    protected function successResponse(
        $data = [],
        string $message = 'Success',
        int $status = 200,
        $pagination = null
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ];

        if ($pagination !== null) {
            $response['data'] = [
                'items' => $data,
                'meta'  => is_object($pagination) ? [
                    'current_page' => $pagination->currentPage(),
                    'last_page'    => $pagination->lastPage(),
                    'per_page'     => $pagination->perPage(),
                    'total'        => $pagination->total(),
                    'from'         => $pagination->firstItem(),
                    'to'           => $pagination->lastItem(),
                ] : ($pagination['meta'] ?? []),
                'links' => is_object($pagination) ? [
                    'first' => $pagination->url(1),
                    'last'  => $pagination->url($pagination->lastPage()),
                    'prev'  => $pagination->previousPageUrl(),
                    'next'  => $pagination->nextPageUrl(),
                ] : ($pagination['links'] ?? []),
            ];
        }

        return response()->json($response, $status);
    }

    protected function errorResponse(string $message, array $errors = [], int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }
}
