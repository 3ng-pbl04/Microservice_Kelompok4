<?php

namespace App\Helpers;

class ApiResponse
{
    public static function success(string $service, $data, int $code = 200)
    {
        return response()->json([
            'status' => 'success',
            'service' => $service,
            'data' => $data,
        ], $code);
    }

    public static function error(string $message, int $code = 503, $detail = null)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'detail' => $detail,
        ], $code);
    }
}
