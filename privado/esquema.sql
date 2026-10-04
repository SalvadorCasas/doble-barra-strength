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

-- Imágenes subidas desde el panel (portada de una entrada o fotos dentro del texto).
-- Cada una se guarda en img/blog/ como {nombre}-{ancho}.webp: una de 800 px y otra del
-- ancho guardado acá (hasta 1600 px). Si la original es más chica, solo hay una.
CREATE TABLE IF NOT EXISTS imagenes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(80) NOT NULL,
  ancho SMALLINT UNSIGNED NOT NULL,
  alto SMALLINT UNSIGNED NOT NULL,
  subida_por INT UNSIGNED NULL,
  creada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY imagenes_nombre (nombre),
  KEY imagenes_creada (creada_en),
  CONSTRAINT imagenes_usuario FOREIGN KEY (subida_por) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Entradas del blog. El cuerpo se guarda como texto con marcas simples (**negrita**,
-- ## Subtítulo, - lista, etc.), nunca como HTML: se convierte al mostrarlo (privado/formato.php).
CREATE TABLE IF NOT EXISTS entradas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  titulo VARCHAR(150) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  fecha DATE NOT NULL,
  autor_id INT UNSIGNED NOT NULL,
  resumen VARCHAR(200) NOT NULL DEFAULT '',
  cuerpo MEDIUMTEXT NOT NULL,
  imagen VARCHAR(80) NULL,
  imagen_alt VARCHAR(250) NOT NULL DEFAULT '',
  estado ENUM('borrador', 'publicada') NOT NULL DEFAULT 'borrador',
  creada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY entradas_slug (slug),
  KEY entradas_publicadas (estado, fecha),
  CONSTRAINT entradas_autor FOREIGN KEY (autor_id) REFERENCES usuarios (id),
  CONSTRAINT entradas_imagen FOREIGN KEY (imagen) REFERENCES imagenes (nombre) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
