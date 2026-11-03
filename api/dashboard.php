<?php
/**
 * Endpoints de Dashboard y Estadísticas
 * - GET /api/admin/resumen        (Resumen para panel de administrador)
 * - GET /api/profesional/mis-stats (Métricas individuales del profesional en turno)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

sendCorsHeaders();

$action = $_GET['action'] ?? '';
$uri = $_SERVER['REQUEST_URI'] ?? '';
$pdo = Database::getConnection();

// --- 1. Resumen Administrador ---
if ($action === 'resumen' || str_contains($uri, 'admin/resumen')) {
    $user = requireAdmin();

    $inicioHoy = date('Y-m-d 00:00:00');

    // Totales generales
    $totalPacientes = (int)$pdo->query('SELECT COUNT(*) FROM pacientes')->fetchColumn();
    $totalProfesionales = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'PROFESIONAL' AND activo = 1")->fetchColumn();
    
    $stmtHoy = $pdo->prepare('SELECT COUNT(*) FROM pacientes WHERE fecha_atencion >= ?');
    $stmtHoy->execute([$inicioHoy]);
    $atencionesHoy = (int)$stmtHoy->fetchColumn();

    // Agrupación por especialidad
    $stmtEsp = $pdo->query('SELECT especialidad, COUNT(*) as total FROM pacientes GROUP BY especialidad');
    $totalesEsp = [];
    while ($row = $stmtEsp->fetch()) {
        $totalesEsp[$row['especialidad']] = (int)$row['total'];
    }

    $especialidadesResumen = [];
    foreach (ESPECIALIDADES as $val => $title) {
        $especialidadesResumen[] = [
            'value' => $val,
            'title' => $title,
            'total' => $totalesEsp[$val] ?? 0,
        ];
    }

    // Atenciones por IPS
    $stmtIps = $pdo->query('
        SELECT p.ips_id AS ipsId, COALESCE(i.nombre, "Sin IPS") AS ipsNombre, COUNT(*) AS total
        FROM pacientes p
        LEFT JOIN ips i ON p.ips_id = i.id
        GROUP BY p.ips_id, i.nombre
        ORDER BY total DESC
    ');
    $atencionesPorIps = [];
    while ($row = $stmtIps->fetch()) {
        $atencionesPorIps[] = [
            'ipsId' => $row['ipsId'] ? (int)$row['ipsId'] : null,
            'ipsNombre' => $row['ipsNombre'],
            'total' => (int)$row['total'],
        ];
    }

    jsonResponse([
        'nombre' => $user['nombre'],
        'totalPacientes' => $totalPacientes,
        'totalProfesionales' => $totalProfesionales,
        'atencioneHoy' => $atencionesHoy,
        'especialidades' => $especialidadesResumen,
        'atencionesPorIps' => $atencionesPorIps,
    ]);
}

// --- 2. Estadísticas para Profesional ---
if ($action === 'mis-stats' || str_contains($uri, 'profesional/mis-stats')) {
    $user = requireProfesional();
    $nombreProfesional = $user['nombre'];

    $inicioHoy = date('Y-m-d 00:00:00');
    $inicioSemana = date('Y-m-d 00:00:00', strtotime('monday this week'));
    $inicioMes = date('Y-m-01 00:00:00');

    // Total General
    $stGen = $pdo->prepare('SELECT COUNT(*) FROM pacientes WHERE profesional = ?');
    $stGen->execute([$nombreProfesional]);
    $totalGeneral = (int)$stGen->fetchColumn();

    // Total Hoy
    $stHoy = $pdo->prepare('SELECT COUNT(*) FROM pacientes WHERE profesional = ? AND fecha_atencion >= ?');
    $stHoy->execute([$nombreProfesional, $inicioHoy]);
    $totalHoy = (int)$stHoy->fetchColumn();

    // Total Semana
    $stSem = $pdo->prepare('SELECT COUNT(*) FROM pacientes WHERE profesional = ? AND fecha_atencion >= ?');
    $stSem->execute([$nombreProfesional, $inicioSemana]);
    $totalSemana = (int)$stSem->fetchColumn();

    // Total Mes
    $stMes = $pdo->prepare('SELECT COUNT(*) FROM pacientes WHERE profesional = ? AND fecha_atencion >= ?');
    $stMes->execute([$nombreProfesional, $inicioMes]);
    $totalMes = (int)$stMes->fetchColumn();

    // Últimos 5 pacientes
    $stUlt = $pdo->prepare('
        SELECT id, nombre_completo AS nombreCompleto, cedula, fecha_atencion AS fechaAtencion
        FROM pacientes
        WHERE profesional = ?
        ORDER BY fecha_atencion DESC
        LIMIT 5
    ');
    $stUlt->execute([$nombreProfesional]);
    $ultimosRaw = $stUlt->fetchAll();

    $ultimosPacientes = [];
    foreach ($ultimosRaw as $p) {
        $fecha = new DateTime($p['fechaAtencion']);
        $ultimosPacientes[] = [
            'id' => (int)$p['id'],
            'nombreCompleto' => $p['nombreCompleto'],
            'cedula' => $p['cedula'],
            'fechaTexto' => $fecha->format('d/m/Y h:i A'),
        ];
    }

    jsonResponse([
        'totalGeneral' => $totalGeneral,
        'totalHoy' => $totalHoy,
        'totalSemana' => $totalSemana,
        'totalMes' => $totalMes,
        'ultimosPacientes' => $ultimosPacientes,
    ]);
}

jsonError('Recurso no encontrado.', 404);
