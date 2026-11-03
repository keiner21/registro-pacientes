<?php
/**
 * Helpers para Respuestas JSON y Utilidades HTTP Seguras
 */

function sendCorsHeaders(): void
{
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
        header('Access-Control-Allow-Credentials: true');
    }

    header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function jsonResponse(mixed $data, int $statusCode = 200): void
{
    // Limpiar cualquier búfer de salida para garantizar que nada preceda al JSON
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

function jsonError(string $message, int $statusCode = 400): void
{
    jsonResponse([
        'error' => $message
    ], $statusCode);
}

function getJsonInput(): array
{
    $raw = file_get_contents('php://input');

    if (!$raw) {
        return $_POST;
    }

    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : $_POST;
}
