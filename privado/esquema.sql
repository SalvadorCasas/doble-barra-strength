-- Tablas del panel de Doble Barra Strength (MariaDB / MySQL).
-- En el hosting: cPanel → phpMyAdmin → elegir la base → pestaña "Importar" → este archivo.
-- Se puede ejecutar más de una vez: solo crea lo que falta.

-- Cuentas de administración del panel. No hay registro público: las crea
-- el administrador del sitio con la herramienta privado/usuarios.php.
CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL,
  clave_hash VARCHAR(255) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ultimo_ingreso DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Intentos de ingreso de las últimas 24 horas, para bloquear a quien prueba contraseñas
CREATE TABLE IF NOT EXISTS intentos_ingreso (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(190) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  exitoso TINYINT(1) NOT NULL,
  momento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY intentos_email (email, momento),
  KEY intentos_ip (ip, momento),
  KEY intentos_momento (momento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
