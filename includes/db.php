<?php
/**
 * Conexión a la Base de Datos mediante PDO
 * Configurada para MySQL en Hostinger
 */
require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host = defined('DB_HOST') ? DB_HOST : 'localhost';
            $port = defined('DB_PORT') ? DB_PORT : '3306';
            $name = defined('DB_NAME') ? DB_NAME : '';
            $user = defined('DB_USER') ? DB_USER : '';
            $password = defined('DB_PASSWORD') ? DB_PASSWORD : (defined('DB_PASS') ? DB_PASS : '');

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $host,
                $port,
                $name
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$instance = new PDO($dsn, $user, $password, $options);
            } catch (PDOException $e) {
                // Registrar detalle en log del servidor para depuración
                error_log('Error de conexión a la BD Hostinger: ' . $e->getMessage());

                if (php_sapi_name() === 'cli') {
                    throw $e;
                }

                while (ob_get_level() > 0) {
                    ob_end_clean();
                }

                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'error' => 'No fue posible conectar con el servidor de base de datos MySQL de Hostinger. Verifique los parámetros en includes/config.php.'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
        }

        return self::$instance;
    }
}
