<?php
/**
 * Script de Diagnóstico y Verificación de Conexión MySQL - Hostinger
 * Alianza Fundación ALES & IPS Restaurando Vidas
 * 
 * Este archivo permite validar si la conexión entre PHP y la base de datos
 * de Hostinger está operativa y si las tablas de database.sql existen.
 * 
 * USO:
 * - Desde el navegador: https://tudominio.com/test_conexion.php
 * - O localmente: php test_conexion.php
 * 
 * NOTA DE SEGURIDAD:
 * Elimine o bloquee el acceso a este archivo una vez verificado el funcionamiento en producción.
 */

// Desactivar buffer para ver salida en tiempo real
ini_set('display_errors', '1');
error_reporting(E_ALL);

$isCli = (php_sapi_name() === 'cli');

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$resultados = [
    'conexion' => false,
    'host' => DB_HOST,
    'puerto' => DB_PORT,
    'base_datos' => DB_NAME,
    'usuario' => DB_USER,
    'version_mysql' => null,
    'tablas' => [],
    'error' => null,
    'recomendacion' => null
];

try {
    // 1. Validar que no se haya dejado el placeholder por defecto
    if (DB_PASSWORD === 'TU_PASSWORD_AQUI') {
        throw new Exception("Debes configurar tu contraseña real de Hostinger en el archivo includes/config.php en la directiva DB_PASSWORD.");
    }

    // 2. Intentar conexión PDO
    $pdo = Database::getConnection();
    $resultados['conexion'] = true;

    // 3. Obtener versión del motor MySQL / MariaDB
    $stmtVer = $pdo->query("SELECT VERSION() AS ver");
    $resultados['version_mysql'] = $stmtVer->fetchColumn();

    // 4. Validar tablas esperadas según database.sql
    $tablasEsperadas = ['ips', 'usuarios', 'pacientes'];
    foreach ($tablasEsperadas as $tabla) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM `{$tabla}`");
            $conteo = (int)$stmt->fetchColumn();
            $resultados['tablas'][$tabla] = [
                'existe' => true,
                'registros' => $conteo
            ];
        } catch (PDOException $eTable) {
            $resultados['tablas'][$tabla] = [
                'existe' => false,
                'error' => $eTable->getMessage()
            ];
        }
    }

} catch (PDOException $e) {
    $resultados['conexion'] = false;
    $resultados['error'] = $e->getMessage();
    $codigo = $e->getCode();

    if ($e->getCode() == 1045) {
        $resultados['recomendacion'] = "Error de autenticación (1045): Verifica que DB_USER y DB_PASSWORD sean exactamente los configurados en Hostinger (hPanel -> Bases de datos MySQL). Recuerda que Hostinger añade un prefijo al usuario (ej. u123456789_usuario).";
    } elseif ($e->getCode() == 1049) {
        $resultados['recomendacion'] = "Base de datos no encontrada (1049): Verifica DB_NAME en includes/config.php. Debe incluir el prefijo completo de Hostinger (ej. u123456789_clinica1).";
    } elseif (str_contains($e->getMessage(), 'Connection refused') || str_contains($e->getMessage(), 'timed out') || $codigo == 2002) {
        $resultados['recomendacion'] = "No se pudo alcanzar el host MySQL: Si estás ejecutando este script dentro del propio servidor de Hostinger, DB_HOST debe ser 'localhost' o '127.0.0.1'. Si estás probando desde tu computadora hacia Hostinger, Hostinger bloquea conexiones externas a menos que actives 'MySQL Remoto' en hPanel.";
    } else {
        $resultados['recomendacion'] = "Revisa los datos de conexión en includes/config.php y confirma que la base de datos esté creada y activa en el panel de Hostinger.";
    }
} catch (Exception $e) {
    $resultados['conexion'] = false;
    $resultados['error'] = $e->getMessage();
    $resultados['recomendacion'] = "Abre el archivo includes/config.php y reemplaza 'TU_PASSWORD_AQUI' por la contraseña que le asignaste al usuario de la base de datos en Hostinger.";
}

// Salida en formato consola para CLI
if ($isCli) {
    echo "====================================================\n";
    echo " PRUEBA DE CONEXIÓN MYSQL (HOSTINGER)\n";
    echo "====================================================\n";
    echo "Host: " . $resultados['host'] . ":" . $resultados['puerto'] . "\n";
    echo "Base de datos: " . $resultados['base_datos'] . "\n";
    echo "Usuario: " . $resultados['usuario'] . "\n";
    echo "----------------------------------------------------\n";

    if ($resultados['conexion']) {
        echo "ESTADO: [ CONECTADO EXITOSAMENTE ]\n";
        echo "Versión MySQL: " . $resultados['version_mysql'] . "\n\n";
        echo "Verificación de tablas (database.sql):\n";
        foreach ($resultados['tablas'] as $t => $info) {
            if ($info['existe']) {
                echo "  ✓ Tabla '{$t}': OK ({$info['registros']} registros)\n";
            } else {
                echo "  ✗ Tabla '{$t}': NO EXISTE (Falta importar database.sql)\n";
            }
        }
    } else {
        echo "ESTADO: [ ERROR DE CONEXIÓN ]\n";
        echo "Detalle: " . $resultados['error'] . "\n";
        if ($resultados['recomendacion']) {
            echo "\nSugerencia: " . $resultados['recomendacion'] . "\n";
        }
    }
    echo "====================================================\n";
    exit;
}

// Salida HTML moderna para Navegador
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobación de Conexión - MySQL Hostinger</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: system-ui, -apple-system, sans-serif; }
        .card-test { border-radius: 12px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.06); }
        .badge-status { font-size: 0.95rem; padding: 0.5rem 1rem; border-radius: 8px; }
    </style>
</head>
<body class="py-5">
<div class="container" style="max-width: 760px;">
    <div class="card card-test p-4 p-md-5 bg-white">
        <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
            <i class='bx bxs-data fs-1 text-primary me-3'></i>
            <div>
                <h3 class="mb-0 fw-bold">Diagnóstico de Base de Datos MySQL</h3>
                <small class="text-muted">Alianza Fundación ALES & IPS Restaurando Vidas | Hostinger</small>
            </div>
        </div>

        <!-- Estado General -->
        <div class="mb-4">
            <h5 class="fw-semibold mb-3">Estado de la Conexión</h5>
            <?php if ($resultados['conexion']): ?>
                <div class="alert alert-success d-flex align-items-center" role="alert">
                    <i class='bx bxs-check-circle fs-3 me-3 text-success'></i>
                    <div>
                        <strong>¡Conexión exitosa con Hostinger!</strong><br>
                        PHP se conectó correctamente al servidor MySQL mediante PDO.
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-danger d-flex align-items-start" role="alert">
                    <i class='bx bxs-x-circle fs-3 me-3 text-danger mt-1'></i>
                    <div>
                        <strong>Fallo al conectar con la Base de Datos</strong><br>
                        <code><?= htmlspecialchars($resultados['error']) ?></code>
                        <?php if ($resultados['recomendacion']): ?>
                            <div class="mt-2 text-dark bg-light p-2 rounded small">
                                <strong>💡 Solución sugerida:</strong> <?= htmlspecialchars($resultados['recomendacion']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Parámetros Configurados -->
        <div class="mb-4">
            <h5 class="fw-semibold mb-3">Parámetros en <code>includes/config.php</code></h5>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Variable</th>
                            <th>Valor Configurado</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>DB_HOST</code></td>
                            <td><strong><?= htmlspecialchars($resultados['host']) ?></strong></td>
                            <td class="text-muted small">Servidor MySQL (en Hostinger suele ser <code>localhost</code>)</td>
                        </tr>
                        <tr>
                            <td><code>DB_PORT</code></td>
                            <td><code><?= htmlspecialchars($resultados['puerto']) ?></code></td>
                            <td class="text-muted small">Puerto MySQL</td>
                        </tr>
                        <tr>
                            <td><code>DB_NAME</code></td>
                            <td><code><?= htmlspecialchars($resultados['base_datos']) ?></code></td>
                            <td class="text-muted small">Nombre de la base de datos</td>
                        </tr>
                        <tr>
                            <td><code>DB_USER</code></td>
                            <td><code><?= htmlspecialchars($resultados['usuario']) ?></code></td>
                            <td class="text-muted small">Usuario MySQL de Hostinger</td>
                        </tr>
                        <tr>
                            <td><code>DB_PASSWORD</code></td>
                            <td>
                                <?= DB_PASSWORD === 'TU_PASSWORD_AQUI' 
                                    ? '<span class="badge bg-danger">Falta configurar contraseña</span>' 
                                    : '<span class="badge bg-success">Configurada (protegida)</span>' ?>
                            </td>
                            <td class="text-muted small">Contraseña del usuario</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Verificación de Tablas de database.sql -->
        <?php if ($resultados['conexion']): ?>
            <div class="mb-4">
                <h5 class="fw-semibold mb-3">Tablas de <code>database.sql</code></h5>
                <ul class="list-group">
                    <?php foreach ($resultados['tablas'] as $nombreTabla => $detalles): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class='bx <?= $detalles['existe'] ? 'bx-table text-primary' : 'bx-error text-danger' ?> me-2'></i>
                                <strong><?= htmlspecialchars($nombreTabla) ?></strong>
                            </div>
                            <?php if ($detalles['existe']): ?>
                                <span class="badge bg-primary rounded-pill">
                                    <?= $detalles['registros'] ?> registros
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger">
                                    No encontrada (Importar database.sql)
                                </span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Aviso de Seguridad -->
        <div class="alert alert-warning border-0 d-flex align-items-center mb-0 mt-3" role="alert">
            <i class='bx bxs-shield-error fs-3 me-3 text-warning'></i>
            <small>
                <strong>Nota de seguridad para producción:</strong> Una vez que hayas comprobado la conexión en Hostinger, elimina o renombra este archivo (<code>test_conexion.php</code>) para evitar exponer información de diagnóstico pública.
            </small>
        </div>

        <div class="mt-4 text-center">
            <a href="login.html" class="btn btn-primary px-4">
                <i class='bx bx-log-in me-1'></i> Ir al Login del Sistema
            </a>
        </div>
    </div>
</div>
</body>
</html>
