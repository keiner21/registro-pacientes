<?php
/**
 * Router / Front Controller de la API REST
 * Compatible con servidores Apache (.htaccess), Nginx y servidor integrado de PHP
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/response.php';

sendCorsHeaders();

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Detectar y aislar la ruta relativa después de /api/ (o /api/index.php/)
$subPath = '';
if (preg_match('#/api/(?:index\.php/)?(.*)$#', $path, $m)) {
    $subPath = trim($m[1], '/');
} else {
    // Si se accede directamente al index.php (ej. /api/index.php/me)
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    if (str_starts_with($path, $scriptName)) {
        $subPath = trim(substr($path, strlen($scriptName)), '/');
    } else {
        $subPath = trim($path, '/');
    }
}

// Normalización de ruta
if ($subPath === 'me' || $subPath === 'auth/me') {
    $_GET['action'] = 'me';
    require __DIR__ . '/auth.php';
    exit;
}

if ($subPath === 'login' || $subPath === 'auth/login') {
    $_GET['action'] = 'login';
    require __DIR__ . '/auth.php';
    exit;
}

if ($subPath === 'logout' || $subPath === 'auth/logout') {
    $_GET['action'] = 'logout';
    require __DIR__ . '/auth.php';
    exit;
}

if ($subPath === 'publico/info') {
    $_GET['action'] = 'info';
    require __DIR__ . '/catalogos.php';
    exit;
}

if ($subPath === 'catalogos') {
    require __DIR__ . '/catalogos.php';
    exit;
}

if ($subPath === 'admin/resumen' || $subPath === 'dashboard/resumen') {
    $_GET['action'] = 'resumen';
    require __DIR__ . '/dashboard.php';
    exit;
}

if ($subPath === 'profesional/mis-stats' || $subPath === 'dashboard/mis-stats') {
    $_GET['action'] = 'mis-stats';
    require __DIR__ . '/dashboard.php';
    exit;
}

if ($subPath === 'registrar/contexto' || $subPath === 'pacientes/contexto') {
    $_GET['action'] = 'contexto';
    require __DIR__ . '/pacientes.php';
    exit;
}

if ($subPath === 'pacientes') {
    require __DIR__ . '/pacientes.php';
    exit;
}

if (str_starts_with($subPath, 'profesionales')) {
    require __DIR__ . '/profesionales.php';
    exit;
}

if (str_starts_with($subPath, 'ips')) {
    require __DIR__ . '/ips.php';
    exit;
}

if ($subPath === 'exportar') {
    require __DIR__ . '/exportar.php';
    exit;
}

// 404 para rutas API no reconocidas
jsonError('Recurso no encontrado en la API.', 404);
