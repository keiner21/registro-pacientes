# Sistema de Registro Clínico de Pacientes
### Alianza Estratégica: Fundación ALES & IPS Restaurando Vidas

Sistema web completo para la gestión y registro de atenciones clínicas de salud mental, control de profesionales, sedes IPS, reportes oficiales y estadísticas en tiempo real.

---

## 🛠️ Stack Tecnológico

* **Frontend:** HTML5 semántico, CSS3 moderno con soporte para Tema Claro/Oscuro, JavaScript Vanilla (ES6+ sin dependencias de frameworks).
* **Diseño e Interfaz:** Bootstrap 5.3.3, Boxicons 2.1.4, ApexCharts para gráficos estadísticos interactivos.
* **Backend:** PHP 8.2+ con arquitectura REST limpia y sesiones seguras.
* **Base de Datos:** MySQL / MariaDB (UTF-8 mb4) con consultas preparadas vía PDO para máxima seguridad contra inyección SQL.
* **Despliegue:** 100% compatible con hostings compartidos como Hostinger, cPanel, Nginx, Apache, XAMPP y Laragon.

---

## 📁 Estructura del Proyecto

```text
registro-pacientes/
│
├── index.html                  # Landing Page oficial institucional
├── login.html                  # Portal de autenticación con logos y efectos visuales
├── dashboard.html              # Panel administrativo principal (ADMIN)
├── exportar.html               # Enlace directo al módulo de exportación
├── exportar.php                # Endpoint para descarga directa de CSV
├── mis-estadisticas.html       # Enlace directo para estadísticas del profesional
├── database.sql                # Script completo DDL + DML con datos iniciales
├── .htaccess                   # Configuración de URLs amigables y seguridad en Apache
│
├── pacientes/
│   ├── registrar.html          # Formulario de consulta clínica (PROFESIONAL)
│   ├── consultar.html          # Búsqueda avanzada con filtros y exportación rápida (ADMIN)
│   └── exportar.html           # Módulo oficial de generación de archivos CSV (ADMIN)
│
├── profesionales/
│   └── index.html              # Gestión de médicos/terapeutas, fotos, IPS y claves (ADMIN)
│
├── ips/
│   └── index.html              # Gestión y habilitación de sedes asistenciales (ADMIN)
│
├── estadisticas/
│   └── index.html              # Métricas cuantitativas del profesional en turno (PROFESIONAL)
│
├── perfil/
│   └── index.html              # Información de cuenta y credencial del usuario
│
├── assets/
│   ├── css/
│   │   └── styles.css          # Estilos globales, temas claro/oscuro y componentes
│   ├── js/
│   │   └── main.js             # Núcleo JS: autenticación, toasts, temas y sidebar dinámico
│   └── img/
│       ├── logo-ales.png       # Logo oficial Fundación ALES
│       ├── logo-ips.png        # Logo oficial IPS Restaurando Vidas
│       └── logo.svg            # Emblema gráfico de la alianza
│
├── includes/
│   ├── config.php              # Parámetros de base de datos, constantes y sesiones
│   ├── db.php                  # Conexión Singleton mediante PDO seguro
│   ├── auth.php                # Middlewares y control de acceso por roles (ADMIN/PROFESIONAL)
│   └── response.php            # Respuestas estandarizadas en formato JSON y CORS
│
└── api/
    ├── index.php               # Enrutador central de la API REST
    ├── auth.php                # Iniciar sesión, cerrar sesión y validar usuario activo (/api/me)
    ├── catalogos.php           # Especialidades, sedes IPS e información pública
    ├── dashboard.php           # KPIs del dashboard y estadísticas individuales
    ├── pacientes.php           # Registro de atención clínica y consulta con filtros
    ├── profesionales.php       # CRUD de personal, fotos en Base64, contraseñas y estado
    ├── ips.php                 # CRUD y activación/desactivación de sedes IPS
    └── exportar.php            # Generador de CSV en UTF-8 con BOM y separador regional (;)
```

---

## 🔑 Credenciales de Acceso Iniciales

| Rol | Usuario | Contraseña | Especialidad / Sede |
| :--- | :--- | :--- | :--- |
| **Administrador** | `admin` | `admin1234` | Gestión global del sistema |
| **Profesional** | `vospina` | `profesional1234` | Psiquiatría (Sede Principal) |
| **Profesional** | `cramirez` | `profesional1234` | Psicología (Sede Norte) |
| **Profesional** | `lmartinez` | `profesional1234` | Terapia Ocupacional (Sede Principal) |
| **Profesional** | `dcastro` | `profesional1234` | Trabajo Social (Sede Bienestar) |

---

## 🚀 Instalación y Puesta en Marcha

### 1. Creación de la Base de Datos
1. Abre **phpMyAdmin** o tu gestor de base de datos MySQL (MySQL Workbench, DBeaver, etc.).
2. Importa el archivo `database.sql` incluido en la raíz del proyecto.
3. El script creará automáticamente la base de datos `registro_pacientes`, las tablas con sus relaciones foráneas y los datos iniciales de prueba.

### 2. Configuración de Conexión
Edita el archivo `includes/config.php` si tus credenciales de MySQL son diferentes a las predeterminadas:

```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'registro_pacientes');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', '3306');
```

### 3. Ejecución Local (XAMPP / Laragon / Servidor PHP)
* **Con XAMPP / Wamp / Laragon:** Copia la carpeta del proyecto dentro de `htdocs` (o `www`) e ingresa en tu navegador a `http://localhost/registro-pacientes`.
* **Con Servidor Integrado de PHP:**
  Abre una terminal en la carpeta del proyecto y ejecuta:
  ```bash
  php -S localhost:8000
  ```
  Luego ingresa a: `http://localhost:8000`

---

## 🌐 Despliegue en Hostinger / Hosting cPanel

1. **Subir Archivos:**
   Sube todos los archivos y carpetas del proyecto a la carpeta `public_html` de tu hosting mediante el Administrador de Archivos o FTP.
2. **Crear Base de Datos MySQL:**
   * En tu panel de Hostinger (hPanel) o cPanel, ve a **Bases de datos MySQL**.
   * Crea una nueva base de datos y un usuario con todos los privilegios.
3. **Importar Tablas:**
   * Abre **phpMyAdmin** desde tu panel de hosting.
   * Selecciona la base de datos recién creada y haz clic en la pestaña **Importar**.
   * Selecciona el archivo `database.sql` y presiona **Ejecutar**.
4. **Configurar Credenciales en Hostinger:**
   * Abre el archivo `includes/config.php` en el editor de archivos y actualiza `DB_NAME`, `DB_USER` y `DB_PASS` con los valores que configuraste en tu hosting:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'u123456789_pacientes');
   define('DB_USER', 'u123456789_usuario');
   define('DB_PASS', 'TuClaveSegura123!');
   define('DB_PORT', '3306');
   ```
5. **¡Listo!** Tu aplicación ya estará disponible en tu dominio web con certificados SSL y sesiones protegidas. hola
