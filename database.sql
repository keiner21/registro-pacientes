-- ====================================================================
-- SISTEMA DE REGISTRO CLÍNICO DE PACIENTES - ALIANZA ALES & IPS RESTAURANDO VIDAS
-- Base de Datos: MySQL / MariaDB (UTF-8 mb4)
-- ====================================================================

CREATE DATABASE IF NOT EXISTS `registro_pacientes` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `registro_pacientes`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `pacientes`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `ips`;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------------------
-- Tabla: ips (Sedes autorizadas de atención médica)
-- --------------------------------------------------------------------
CREATE TABLE `ips` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(150) NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_ips_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Tabla: usuarios (Administradores y Profesionales de la salud)
-- --------------------------------------------------------------------
CREATE TABLE `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(150) NOT NULL,
  `usuario` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `rol` ENUM('ADMIN', 'PROFESIONAL') NOT NULL,
  `especialidad` ENUM('Psiquiatria', 'TerapiaOcupacional', 'Psicologia', 'TrabajoSocial') NULL,
  `ips_id` INT NULL,
  `foto` MEDIUMTEXT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_usuario` (`usuario`),
  KEY `idx_usuario_rol` (`rol`),
  KEY `idx_usuario_activo` (`activo`),
  CONSTRAINT `fk_usuarios_ips` FOREIGN KEY (`ips_id`) REFERENCES `ips` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Tabla: pacientes (Historial de atenciones clínicas registradas)
-- --------------------------------------------------------------------
CREATE TABLE `pacientes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre_completo` VARCHAR(200) NOT NULL,
  `cedula` VARCHAR(50) NOT NULL,
  `telefono` VARCHAR(50) NULL,
  `profesional` VARCHAR(150) NOT NULL,
  `especialidad` ENUM('Psiquiatria', 'TerapiaOcupacional', 'Psicologia', 'TrabajoSocial') NOT NULL,
  `ips_id` INT NULL,
  `fecha_atencion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_pacientes_cedula` (`cedula`),
  KEY `idx_pacientes_nombre` (`nombre_completo`),
  KEY `idx_pacientes_fecha` (`fecha_atencion`),
  KEY `idx_pacientes_especialidad` (`especialidad`),
  KEY `idx_pacientes_ips` (`ips_id`),
  CONSTRAINT `fk_pacientes_ips` FOREIGN KEY (`ips_id`) REFERENCES `ips` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- DATOS INICIALES Y SEMILLA DE PRUEBA
-- ====================================================================

-- 1. Sedes IPS
INSERT INTO `ips` (`id`, `nombre`, `activo`, `fecha_creacion`) VALUES
(1, 'Sede Principal', 1, NOW()),
(2, 'Sede Norte', 1, NOW()),
(3, 'Sede Bienestar', 1, NOW());

-- 2. Usuarios
-- Credenciales verificadas con password_hash() de PHP:
-- admin: admin1234
-- profesionales: profesional1234
INSERT INTO `usuarios` (`id`, `nombre`, `usuario`, `password`, `rol`, `especialidad`, `ips_id`, `foto`, `activo`, `fecha_creacion`) VALUES
(1, 'Administrador General', 'admin', '$2y$10$Cn/wYIxUQaGtZdJgCG92OervCGlry5O36oQsSJ7AaSoxDByjao2Tu', 'ADMIN', NULL, 1, NULL, 1, NOW()),
(2, 'Dra. Valeria Ospina', 'vospina', '$2y$10$bXSCXU91ULSIUaRhR3os6uzNZWO4/0Ax502uX9xtxo6nBq4P4xJsq', 'PROFESIONAL', 'Psiquiatria', 1, NULL, 1, NOW()),
(3, 'Lic. Carlos Ramírez', 'cramirez', '$2y$10$bXSCXU91ULSIUaRhR3os6uzNZWO4/0Ax502uX9xtxo6nBq4P4xJsq', 'PROFESIONAL', 'Psicologia', 2, NULL, 1, NOW()),
(4, 'Lic. Luisa Martínez', 'lmartinez', '$2y$10$bXSCXU91ULSIUaRhR3os6uzNZWO4/0Ax502uX9xtxo6nBq4P4xJsq', 'PROFESIONAL', 'TerapiaOcupacional', 1, NULL, 1, NOW()),
(5, 'Dra. Diana Castro', 'dcastro', '$2y$10$bXSCXU91ULSIUaRhR3os6uzNZWO4/0Ax502uX9xtxo6nBq4P4xJsq', 'PROFESIONAL', 'TrabajoSocial', 3, NULL, 1, NOW());

-- 3. Pacientes de prueba
INSERT INTO `pacientes` (`id`, `nombre_completo`, `cedula`, `telefono`, `profesional`, `especialidad`, `ips_id`, `fecha_atencion`) VALUES
(1, 'Sofía Rodríguez Gómez', '1045234567', '3001234567', 'Dra. Valeria Ospina', 'Psiquiatria', 1, NOW()),
(2, 'Mateo Vargas López', '1098765432', '3109876543', 'Lic. Carlos Ramírez', 'Psicologia', 2, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 'Mariana Restrepo Ruiz', '1023456789', '3204567890', 'Lic. Luisa Martínez', 'TerapiaOcupacional', 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(4, 'Santiago Morales Vélez', '1056789012', '3157890123', 'Dra. Diana Castro', 'TrabajoSocial', 3, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(5, 'Valentina Torres Silva', '1087654321', '3012345678', 'Dra. Valeria Ospina', 'Psiquiatria', 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(6, 'Juan Camilo Correa', '1034567891', '3123456789', 'Lic. Carlos Ramírez', 'Psicologia', 2, DATE_SUB(NOW(), INTERVAL 6 DAY));
