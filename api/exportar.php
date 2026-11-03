<?php
/**
 * Generador y Descarga Oficial de Archivos CSV
 * - GET /exportar?descargar=1
 * - GET /api/exportar
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Exigir rol de administrador
requireAdmin();

$pdo = Database::getConnection();

$especialidad = trim((string)($_GET['especialidad'] ?? ''));
$profesionalId = (int)($_GET['profesional_id'] ?? $_GET['profesionalId'] ?? 0);
$ipsId = (int)($_GET['ips_id'] ?? $_GET['ipsId'] ?? 0);
$fechaDesde = trim((string)($_GET['fecha_desde'] ?? $_GET['fechaDesde'] ?? ''));
$fechaHasta = trim((string)($_GET['fecha_hasta'] ?? $_GET['fechaHasta'] ?? ''));

$sql = '
    SELECT p.*, COALESCE(i.nombre, "Sin Sede") AS ips_nombre
    FROM pacientes p
    LEFT JOIN ips i ON p.ips_id = i.id
    WHERE 1=1
';
$params = [];

if ($especialidad !== '') {
    $sql .= ' AND p.especialidad = ?';
    $params[] = $especialidad;
}

if ($ipsId > 0) {
    $sql .= ' AND p.ips_id = ?';
    $params[] = $ipsId;
}

if ($profesionalId > 0) {
    $stProf = $pdo->prepare('SELECT nombre FROM usuarios WHERE id = ? AND rol = "PROFESIONAL" LIMIT 1');
    $stProf->execute([$profesionalId]);
    $profNombre = $stProf->fetchColumn();
    $sql .= ' AND p.profesional = ?';
    $params[] = $profNombre ?: '__ningun_profesional__';
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
$pacientes = $stmt->fetchAll();

function escapeCsv(string $val): string {
    if (str_contains($val, ';') || str_contains($val, '"') || str_contains($val, "\n") || str_contains($val, "\r")) {
        return '"' . str_replace('"', '""', $val) . '"';
    }
    return $val;
}

$marcaTiempo = date('Y-m-d_H-i-s');
$nombreArchivo = "registro_pacientes_{$marcaTiempo}.csv";

header('Content-Type: text/csv; charset=UTF-8');
header("Content-Disposition: attachment; filename=\"{$nombreArchivo}\"");
header('Pragma: no-cache');
header('Expires: 0');

// Escribir BOM UTF-8 para compatibilidad nativa con Microsoft Excel en español
echo "\xEF\xBB\xBF";

// Cabecera oficial
$headers = ['Nombre completo', 'Cédula', 'Teléfono', 'Sede IPS', 'Profesional', 'Especialidad', 'Fecha y hora'];
echo implode(';', array_map('escapeCsv', $headers)) . "\r\n";

foreach ($pacientes as $p) {
    $espTexto = ESPECIALIDADES[$p['especialidad']] ?? $p['especialidad'];
    $fecha = new DateTime($p['fecha_atencion']);
    
    $linea = [
        $p['nombre_completo'],
        $p['cedula'],
        $p['telefono'] ?: '',
        $p['ips_nombre'],
        $p['profesional'],
        $espTexto,
        $fecha->format('Y-m-d H:i:s'),
    ];

    echo implode(';', array_map('escapeCsv', $linea)) . "\r\n";
}
exit;
