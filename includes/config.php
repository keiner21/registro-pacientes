<?php
/**
 * Configuración del Sistema de Registro de Pacientes
 * Alianza Fundación ALES & IPS Restaurando Vidas
 */

// Iniciar búfer de salida temprano para evitar que cualquier advertencia,
// espacio o carácter previo contamine las respuestas JSON de la API
if (ob_get_level() === 0) {
    ob_start();
}

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

date_default_timezone_set('America/Bogota');

/*
 * ============================================================
 * CONFIGURACIÓN DE CONEXIÓN A LA BASE DE DATOS MYSQL (HOSTINGER)
 * ============================================================
 *
 * En Hostinger (hPanel -> Bases de datos MySQL):
 * 1. DB_HOST: Cuando tu web PHP corre en Hostinger, el servidor MySQL
 *    suele ser 'localhost' o '127.0.0.1'. Si usas conexión remota,
 *    coloca el host o IP especificada en tu panel.
 * 2. DB_NAME: El nombre completo de la BD (ej. u493325161_clinica1).
 * 3. DB_USER: El usuario de la BD (ej. u493325161_keiner).
 * 4. DB_PASSWORD: La contraseña que le asignaste al usuario de la BD.
 * 5. DB_PORT: Puerto estándar de MySQL, normalmente 3306.
 */

define('DB_HOST', 'srv2103.hstgr.io');
define('DB_NAME', 'u493325161_clinica');
define('DB_USER', 'u493325161_keiner');
define('DB_PASSWORD', 'Yamada2005@');
define('DB_PORT', '3306');

// Retrocompatibilidad con scripts que hagan referencia a DB_PASS
if (!defined('DB_PASS')) {
    define('DB_PASS', DB_PASSWORD);
}

/*
 * Catálogo de Especialidades Médicas
 */
const ESPECIALIDADES = [
    'Psiquiatria' => 'Psiquiatría',
    'TerapiaOcupacional' => 'Terapia Ocupacional',
    'Psicologia' => 'Psicología',
    'TrabajoSocial' => 'Trabajo Social'
];

/*
 * Configuración de Sesiones Seguras
 */
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');

    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }

    session_name('sid_rp');
    session_start();
}
