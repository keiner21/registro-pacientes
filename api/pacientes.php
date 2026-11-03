<?php
/**
 * Endpoints de Pacientes y Registro Clínico
 * - GET  /api/pacientes          (Listado con filtros avanzados - Admin)
 * - POST /api/pacientes          (Crear nuevo registro de atención - Profesional)
 * - GET  /api/registrar/contexto (Información de la credencial en turno - Profesional)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

sendCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$uri = $_SERVER['REQUEST_URI'] ?? '';
$pdo = Database::getConnection();

// --- 1. Contexto de Registro (Profesional en turno) ---
if ($action === 'contexto' || str_contains($uri, 'registrar/contexto')) {
    $user = requireProfesional();

    if (empty($user['ipsId']) || empty($user['especialidad'])) {
        jsonError('El profesional no tiene una sede IPS asignada o la IPS se encuentra inactiva.', 400);
    }

    // Verificar que la IPS siga activa
    $stmtIps = $pdo->prepare('SELECT nombre, activo FROM ips WHERE id = ? LIMIT 1');
    $stmtIps->execute([$user['ipsId']]);
    $ips = $stmtIps->fetch();

    if (!$ips || !(bool)$ips['activo']) {
        jsonError('La sede IPS asignada no está activa en el sistema.', 400);
    }

    jsonResponse([
        'profesional' => $user['nombre'],
        'especialidad' => $user['especialidad'],
        'especialidadTexto' => $user['especialidadTexto'],
        'nombreIps' => $ips['nombre'],
    ]);
}

// --- 2. Registrar Paciente (POST - Profesional) ---
if ($method === 'POST') {
    $user = requireProfesional();

    if (empty($user['ipsId']) || empty($user['especialidad'])) {
        jsonError('El profesional no tiene una sede IPS asignada o la IPS no está activa.', 400);
    }

    $input = getJsonInput();
    $nombreCompleto = trim((string)($input['nombreCompleto'] ?? $input['nombre_completo'] ?? ''));
    $cedula = trim((string)($input['cedula'] ?? ''));
    $telefono = trim((string)($input['telefono'] ?? ''));

    if ($nombreCompleto === '' || $cedula === '') {
        jsonError('El nombre completo y el documento de identidad (cédula) son obligatorios.', 400);
    }

    $stmtInsert = $pdo->prepare('
        INSERT INTO pacientes (nombre_completo, cedula, telefono, profesional, especialidad, ips_id, fecha_atencion)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ');
    $stmtInsert->execute([
        $nombreCompleto,
        $cedula,
        $telefono !== '' ? $telefono : null,
        $user['nombre'],
        $user['especialidad'],
        $user['ipsId'],
    ]);

    $id = (int)$pdo->lastInsertId();

    $stmtGet = $pdo->prepare('SELECT * FROM pacientes WHERE id = ?');
    $stmtGet->execute([$id]);
    $paciente = $stmtGet->fetch();

    $fecha = new DateTime($paciente['fecha_atencion']);

    jsonResponse([
        'mensaje' => 'Paciente registrado exitosamente en el sistema.',
        'paciente' => [
            'id' => $id,
            'nombreCompleto' => $paciente['nombre_completo'],
            'cedula' => $paciente['cedula'],
            'telefono' => $paciente['telefono'] ?? '',
            'profesional' => $paciente['profesional'],
            'especialidad' => $paciente['especialidad'],
            'especialidadTexto' => ESPECIALIDADES[$paciente['especialidad']] ?? $paciente['especialidad'],
            'ipsId' => $paciente['ips_id'] ? (int)$paciente['ips_id'] : null,
            'fechaAtencion' => $fecha->format('c'),
            'fechaAtencionTexto' => $fecha->format('Y-m-d H:i:s'),
        ]
    ], 201);
}

// --- 3. Consultar Pacientes (GET - Admin) ---
if ($method === 'GET') {
    requireAdmin();

    $cedula = trim((string)($_GET['cedula'] ?? ''));
    $nombre = trim((string)($_GET['nombre'] ?? ''));
    $especialidad = trim((string)($_GET['especialidad'] ?? ''));
    $profesional = trim((string)($_GET['profesional'] ?? ''));
    $fechaDesde = trim((string)($_GET['fechaDesde'] ?? $_GET['fecha_desde'] ?? ''));
    $fechaHasta = trim((string)($_GET['fechaHasta'] ?? $_GET['fecha_hasta'] ?? ''));

    $sql = 'SELECT p.*, i.nombre AS ips_nombre FROM pacientes p LEFT JOIN ips i ON p.ips_id = i.id WHERE 1=1';
    $params = [];

    if ($cedula !== '') {
        $sql .= ' AND p.cedula LIKE ?';
        $params[] = "%$cedula%";
    }
    if ($nombre !== '') {
        $sql .= ' AND p.nombre_completo LIKE ?';
        $params[] = "%$nombre%";
    }
    if ($especialidad !== '') {
        $sql .= ' AND p.especialidad = ?';
        $params[] = $especialidad;
    }
    if ($profesional !== '') {
        $sql .= ' AND p.profesional LIKE ?';
        $params[] = "%$profesional%";
    }
    if ($fechaDesde !== '') {
        $sql .= ' AND p.fecha_atencion >= ?';
        $params[] = "$fechaDesde 00:00:00";
    }
    if ($fechaHasta !== '') {
        $sql .= ' AND p.fecha_atencion <= ?';
        $params[] = "$fechaHasta 23:59:59";
    }

    $sql .= ' ORDER BY p.fecha_atencion DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $pacientes = [];
    foreach ($rows as $r) {
        $f = new DateTime($r['fecha_atencion']);
        $pacientes[] = [
            'id' => (int)$r['id'],
            'nombreCompleto' => $r['nombre_completo'],
            'cedula' => $r['cedula'],
            'telefono' => $r['telefono'] ?? '',
            'profesional' => $r['profesional'],
            'especialidad' => $r['especialidad'],
            'especialidadTexto' => ESPECIALIDADES[$r['especialidad']] ?? $r['especialidad'],
            'ipsId' => $r['ips_id'] ? (int)$r['ips_id'] : null,
            'ipsNombre' => $r['ips_nombre'] ?? '',
            'fechaAtencion' => $f->format('c'),
            'fechaAtencionTexto' => $f->format('Y-m-d H:i:s'),
        ];
    }

    jsonResponse([
        'filtros' => [
            'cedula' => $cedula,
            'nombre' => $nombre,
            'especialidad' => $especialidad,
            'profesional' => $profesional,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
        ],
        'pacientes' => $pacientes,
    ]);
}

jsonError('Método no permitido.', 405);
