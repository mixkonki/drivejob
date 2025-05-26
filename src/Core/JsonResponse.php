<?php

namespace Drivejob\Core;

/**
 * JSON Response Helper Class
 */
class JsonResponse
{
    /**
     * Send success response
     * 
     * @param mixed $data
     * @param int $statusCode
     */
    public static function success($data = null, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');

        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        exit;
    }

    /**
     * Send error response
     * 
     * @param string $message
     * @param int $statusCode
     * @param array $errors
     */
    public static function error($message, $statusCode = 400, $errors = [])
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'message' => $message
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        echo json_encode($response);
        exit;
    }

    /**
     * Send paginated response
     * 
     * @param array $data
     * @param int $total
     * @param int $page
     * @param int $perPage
     */
    public static function paginated($data, $total, $page, $perPage)
    {
        http_response_code(200);
        header('Content-Type: application/json');

        echo json_encode([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $total)
            ]
        ]);
        exit;
    }
}
