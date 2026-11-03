<?php
/**
 * Endpoints de Gestión de Profesionales en Salud (Admin)
 * - GET   /api/profesionales              (Listar profesionales e IPS)
 * - POST  /api/profesionales              (Crear nuevo profesional)
 * - PATCH /api/profesionales/:id/foto     (Actualizar foto de perfil)
 * - PATCH /api/profesionales/:id/ips      (Cambiar IPS asignada)
 * - PATCH /api/profesionales/:id/estado   (Activar / desactivar)
 * - PATCH /api/profesionales/:id/password (Restablecer contraseña)
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

// Obtener el ID de la URL si viene en REQUEST_URI (ej. /api/profesionales/3/foto)
$uri = $_SERVER['REQUEST_URI'] ?? '';
if (preg_match('#profesionales/(\d+)(?:/([a-zA-Z]+))?#', $uri, $matches)) {
    $id = (int)$matches[1];
    if (!empty($matches[2])) {
        $action = $matches[2];
    }
}

// --- 1. Listar Profesionales e IPS (GET) ---
if ($method === 'GET') {
    $stmtIps = $pdo->query('SELECT id, nombre, activo FROM ips WHERE activo = 1 ORDER BY nombre ASC');
    $ips = $stmtIps->fetchAll();

    $stmtProf = $pdo->query('
        SELECT u.*, i.nombre AS ips_nombre
        FROM usuarios u
        LEFT JOIN ips i ON u.ips_id = i.id
        WHERE u.rol = "PROFESIONAL"
        ORDER BY u.nombre ASC
    ');
    $profRaw = $stmtProf->fetchAll();

    $profesionales = [];
    foreach ($profRaw as $p) {
        $f = new DateTime($p['fecha_creacion']);
        $profesionales[] = [
            'id' => (int)$p['id'],
            'nombre' => $p['nombre'],
            'usuario' => $p['usuario'],
            'especialidad' => $p['especialidad'],
            'especialidadTexto' => $p['especialidad'] && isset(ESPECIALIDADES[$p['especialidad']])
                ? ESPECIALIDADES[$p['especialidad']]
                : '',
            'ipsId' => $p['ips_id'] ? (int)$p['ips_id'] : null,
            'ipsNombre' => $p['ips_nombre'] ?? '',
            'foto' => $p['foto'] ?: null,
            'activo' => (bool)$p['activo'],
            'fechaCreacion' => $f->format('c'),
            'fechaCreacionTexto' => $f->format('Y-m-d H:i:s'),
        ];
    }

    jsonResponse([
        'ips' => $ips,
        'profesionales' => $profesionales,
    ]);
}

// --- 2. Crear Nuevo Profesional (POST) ---
if ($method === 'POST') {
    $input = getJsonInput();
    $nombre = trim((string)($input['nombre'] ?? ''));
    $usuario = trim((string)($input['usuario'] ?? ''));
    $password = (string)($input['password'] ?? '');
    $confirmar = (string)($input['confirmar'] ?? '');
    $especialidad = trim((string)($input['especialidad'] ?? ''));
    $ipsId = (int)($input['ipsId'] ?? $input['ips_id'] ?? 0);
    $foto = !empty($input['foto']) && is_string($input['foto']) ? trim($input['foto']) : null;

    if ($nombre === '') jsonError('Debe ingresar el nombre completo del profesional.', 400);
    if ($usuario === '') jsonError('Debe ingresar un nombre de usuario.', 400);
    if (strlen($password) < 8) jsonError('La contraseña debe tener mínimo 8 caracteres.', 400);
    if ($password !== $confirmar) jsonError('Las contraseñas no coinciden.', 400);
    if (!array_key_exists($especialidad, ESPECIALIDADES)) jsonError('Debe seleccionar una especialidad válida.', 400);
    if ($ipsId <= 0) jsonError('Debe seleccionar la IPS a la que pertenece el profesional.', 400);

    // Validar IPS activa
    $stIps = $pdo->prepare('SELECT id FROM ips WHERE id = ? AND activo = 1 LIMIT 1');
    $stIps->execute([$ipsId]);
    if (!$stIps->fetch()) {
        jsonError('La sede IPS seleccionada no es válida o se encuentra inactiva.', 400);
    }

    // Validar usuario único
    $stUser = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = ? LIMIT 1');
    $stUser->execute([$usuario]);
    if ($stUser->fetch()) {
        jsonError('Ese nombre de usuario ya se encuentra registrado en el sistema.', 409);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $stIns = $pdo->prepare('
        INSERT INTO usuarios (nombre, usuario, password, rol, especialidad, ips_id, foto, activo, fecha_creacion)
        VALUES (?, ?, ?, "PROFESIONAL", ?, ?, ?, 1, NOW())
    ');
    $stIns->execute([$nombre, $usuario, $hash, $especialidad, $ipsId, $foto]);

    jsonResponse(['mensaje' => 'Profesional de la salud creado exitosamente.'], 201);
}

// --- 3. Actualizaciones (PATCH) ---
if ($method === 'PATCH' || ($method === 'POST' && in_array($action, ['foto', 'ips', 'estado', 'password']))) {
    if ($id <= 0) {
        jsonError('ID de profesional no válido.', 400);
    }

    $stCheck = $pdo->prepare('SELECT id, activo FROM usuarios WHERE id = ? AND rol = "PROFESIONAL" LIMIT 1');
    $stCheck->execute([$id]);
    $prof = $stCheck->fetch();

    if (!$prof) {
        jsonError('Profesional no encontrado en el sistema.', 404);
    }

    $input = getJsonInput();

    // Actualizar foto
    if ($action === 'foto') {
        $foto = !empty($input['foto']) && is_string($input['foto']) ? trim($input['foto']) : null;
        $stUp = $pdo->prepare('UPDATE usuarios SET foto = ? WHERE id = ?');
        $stUp->execute([$foto, $id]);
        jsonResponse(['mensaje' => 'Foto de perfil actualizada correctamente.']);
    }

    // Actualizar IPS asignada
    if ($action === 'ips') {
        $ipsId = (int)($input['ipsId'] ?? $input['ips_id'] ?? 0);
        if ($ipsId <= 0) jsonError('Debe seleccionar una sede IPS válida.', 400);

        $stIps = $pdo->prepare('SELECT id FROM ips WHERE id = ? AND activo = 1 LIMIT 1');
        $stIps->execute([$ipsId]);
        if (!$stIps->fetch()) jsonError('La IPS seleccionada no es válida.', 400);

        $stUp = $pdo->prepare('UPDATE usuarios SET ips_id = ? WHERE id = ?');
        $stUp->execute([$ipsId, $id]);
        jsonResponse(['mensaje' => 'Sede IPS del profesional actualizada correctamente.']);
    }

    // Cambiar estado activo / inactivo
    if ($action === 'estado') {
        $activo = isset($input['activo']) ? (bool)$input['activo'] : !(bool)$prof['activo'];
        $stUp = $pdo->prepare('UPDATE usuarios SET activo = ? WHERE id = ?');
        $stUp->execute([(int)$activo, $id]);
        jsonResponse(['mensaje' => 'Estado del profesional actualizado correctamente.']);
    }

    // Restablecer contraseña
    if ($action === 'password') {
        $password = (string)($input['password'] ?? '');
        $confirmar = (string)($input['confirmar'] ?? '');

        if (strlen($password) < 8) jsonError('La contraseña debe tener mínimo 8 caracteres.', 400);
        if ($password !== $confirmar) jsonError('Las contraseñas no coinciden.', 400);

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stUp = $pdo->prepare('UPDATE usuarios SET password = ? WHERE id = ?');
        $stUp->execute([$hash, $id]);
        jsonResponse(['mensaje' => 'La contraseña fue actualizada correctamente.']);
    }

    jsonError('Acción no reconocida.', 400);
}

jsonError('Método no permitido.', 405);
