<?php
/**
 * Endpoints de Gestión de Sedes IPS (Admin)
 * - GET   /api/ips            (Listar todas las IPS con conteo de profesionales)
 * - POST  /api/ips            (Crear nueva sede IPS)
 * - PATCH /api/ips/:id        (Editar nombre de sede IPS)
 * - PATCH /api/ips/:id/estado (Activar / desactivar sede IPS)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

sendCorsHeaders();

$admin = requireAdmin();
$pdo = Database::getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$uri = $_SERVER['REQUEST_URI'] ?? '';
if (preg_match('#ips/(\d+)(?:/([a-zA-Z]+))?#', $uri, $matches)) {
    $id = (int)$matches[1];
    if (!empty($matches[2])) {
        $action = $matches[2];
    }
}

// --- 1. Listar IPS con conteo de profesionales (GET) ---
if ($method === 'GET') {
    $stmt = $pdo->query('
        SELECT i.id, i.nombre, i.activo, COUNT(u.id) AS total_profesionales
        FROM ips i
        LEFT JOIN usuarios u ON u.ips_id = i.id AND u.rol = "PROFESIONAL"
        GROUP BY i.id, i.nombre, i.activo
        ORDER BY i.nombre ASC
    ');
    $rows = $stmt->fetchAll();

    $ips = [];
    foreach ($rows as $r) {
        $ips[] = [
            'id' => (int)$r['id'],
            'nombre' => $r['nombre'],
            'activo' => (bool)$r['activo'],
            'totalProfesionales' => (int)$r['total_profesionales'],
        ];
    }

    jsonResponse(['ips' => $ips]);
}

// --- 2. Crear IPS (POST) ---
if ($method === 'POST') {
    $input = getJsonInput();
    $nombre = trim((string)($input['nombre'] ?? ''));

    if ($nombre === '') {
        jsonError('Debe ingresar el nombre de la sede IPS.', 400);
    }

    $stExist = $pdo->prepare('SELECT id FROM ips WHERE nombre = ? LIMIT 1');
    $stExist->execute([$nombre]);
    if ($stExist->fetch()) {
        jsonError('Ya existe una sede IPS con ese nombre.', 409);
    }

    $stIns = $pdo->prepare('INSERT INTO ips (nombre, activo, fecha_creacion) VALUES (?, 1, NOW())');
    $stIns->execute([$nombre]);
    $newId = (int)$pdo->lastInsertId();

    jsonResponse([
        'mensaje' => 'Sede IPS creada correctamente.',
        'ips' => [
            'id' => $newId,
            'nombre' => $nombre,
            'activo' => true,
            'totalProfesionales' => 0
        ]
    ], 201);
}

// --- 3. Actualizaciones (PATCH) ---
if ($method === 'PATCH' || ($method === 'POST' && ($id > 0 || in_array($action, ['estado'])))) {
    if ($id <= 0) {
        jsonError('ID de sede IPS no válido.', 400);
    }

    $stCheck = $pdo->prepare('SELECT id, nombre, activo FROM ips WHERE id = ? LIMIT 1');
    $stCheck->execute([$id]);
    $actual = $stCheck->fetch();

    if (!$actual) {
        jsonError('Sede IPS no encontrada.', 404);
    }

    $input = getJsonInput();

    // Activar / desactivar
    if ($action === 'estado') {
        $activo = isset($input['activo']) ? (bool)$input['activo'] : !(bool)$actual['activo'];
        $stUp = $pdo->prepare('UPDATE ips SET activo = ? WHERE id = ?');
        $stUp->execute([(int)$activo, $id]);

        jsonResponse([
            'mensaje' => sprintf('Sede IPS %s correctamente.', $activo ? 'activada' : 'desactivada'),
            'ips' => [
                'id' => $id,
                'nombre' => $actual['nombre'],
                'activo' => $activo
            ]
        ]);
    }

    // Editar nombre
    $nombre = trim((string)($input['nombre'] ?? ''));
    if ($nombre === '') {
        jsonError('Debe ingresar un nombre para la sede IPS.', 400);
    }

    $stDup = $pdo->prepare('SELECT id FROM ips WHERE nombre = ? AND id != ? LIMIT 1');
    $stDup->execute([$nombre, $id]);
    if ($stDup->fetch()) {
        jsonError('Ya existe otra sede IPS con ese nombre.', 409);
    }

    $stUp = $pdo->prepare('UPDATE ips SET nombre = ? WHERE id = ?');
    $stUp->execute([$nombre, $id]);

    jsonResponse([
        'mensaje' => 'Sede IPS actualizada correctamente.',
        'ips' => [
            'id' => $id,
            'nombre' => $nombre,
            'activo' => (bool)$actual['activo']
        ]
    ]);
}

jsonError('Método no permitido.', 405);
