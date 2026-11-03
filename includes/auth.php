<?php
/**
 * Helpers de Autenticación y Autorización por Roles
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/db.php';

function getAuthUser(): ?array {
    if (empty($_SESSION['usuario_id'])) {
        return null;
    }

    $pdo = Database::getConnection();
    $stmt = $pdo->prepare('
        SELECT u.id, u.nombre, u.usuario, u.rol, u.especialidad, u.ips_id, u.foto, u.activo, i.nombre AS ips_nombre
        FROM usuarios u
        LEFT JOIN ips i ON u.ips_id = i.id
        WHERE u.id = ?
        LIMIT 1
    ');
    $stmt->execute([$_SESSION['usuario_id']]);
    $user = $stmt->fetch();

    if (!$user || !(bool)$user['activo']) {
        // Sesión inválida o usuario desactivado
        session_unset();
        session_destroy();
        return null;
    }

    return [
        'id' => (int)$user['id'],
        'nombre' => $user['nombre'],
        'usuario' => $user['usuario'],
        'rol' => $user['rol'],
        'especialidad' => $user['especialidad'],
        'especialidadTexto' => !empty($user['especialidad']) && isset(ESPECIALIDADES[$user['especialidad']]) 
            ? ESPECIALIDADES[$user['especialidad']] 
            : null,
        'ipsId' => $user['ips_id'] ? (int)$user['ips_id'] : null,
        'ipsNombre' => $user['ips_nombre'] ?? '',
        'foto' => $user['foto'] ?: null,
    ];
}

function requireAuth(): array {
    sendCorsHeaders();
    $user = getAuthUser();
    if (!$user) {
        jsonError('Sesión no válida o no autenticado.', 401);
    }
    return $user;
}

function requireAdmin(): array {
    $user = requireAuth();
    if ($user['rol'] !== 'ADMIN') {
        jsonError('Acceso no autorizado. Se requiere rol de Administrador.', 403);
    }
    return $user;
}

function requireProfesional(): array {
    $user = requireAuth();
    if ($user['rol'] !== 'PROFESIONAL') {
        jsonError('Acceso no autorizado. Se requiere rol de Profesional.', 403);
    }
    return $user;
}
