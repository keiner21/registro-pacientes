<?php
/**
 * Endpoints de Catálogos e Información Pública
 * - GET /api/publico/info (Información para landing page)
 * - GET /api/catalogos    (Especialidades y sedes IPS activas)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

sendCorsHeaders();

$action = $_GET['action'] ?? '';
$pdo = Database::getConnection();

$especialidadesLista = [];
foreach (ESPECIALIDADES as $val => $title) {
    $especialidadesLista[] = [
        'value' => $val,
        'title' => $title
    ];
}

if ($action === 'info' || (!empty($_SERVER['REQUEST_URI']) && str_contains($_SERVER['REQUEST_URI'], 'publico/info'))) {
    try {
        $stmtSedes = $pdo->query('SELECT id, nombre FROM ips WHERE activo = 1 ORDER BY nombre ASC');
        $sedes = $stmtSedes->fetchAll();

        $stmtPac = $pdo->query('SELECT COUNT(*) AS total FROM pacientes');
        $totalAtenciones = (int)$stmtPac->fetch()['total'];

        $stmtProf = $pdo->query("SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'PROFESIONAL' AND activo = 1");
        $totalProfesionales = (int)$stmtProf->fetch()['total'];

        jsonResponse([
            'sedes' => $sedes,
            'totalAtenciones' => $totalAtenciones,
            'totalProfesionales' => $totalProfesionales,
            'especialidades' => $especialidadesLista,
        ]);
    } catch (Exception $e) {
        jsonError('No fue posible obtener la información pública.', 500);
    }
}

// Catálogos generales (requiere autenticación)
requireAuth();

$stmtIps = $pdo->query('SELECT id, nombre, activo FROM ips WHERE activo = 1 ORDER BY nombre ASC');
$ips = $stmtIps->fetchAll();

jsonResponse([
    'especialidades' => $especialidadesLista,
    'ips' => $ips,
]);
