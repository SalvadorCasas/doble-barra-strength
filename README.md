# Doble Barra Strength — sitio web

Sitio del equipo de powerlifting **Doble Barra Strength** (entrenadores Ivan Casas y Luca Bettinalio): presentación del equipo, contenido de YouTube, blog y formulario "Sumate al equipo". Incluye un panel privado para que el equipo publique en el blog.

- **Sitio público:** HTML + CSS + JavaScript sin frameworks ni compilación. Diseño mobile-first, tema claro/oscuro y accesibilidad WCAG 2.2 AA.
- **Panel del equipo** (`admin/`): PHP 8.2+ y MariaDB/MySQL, alojado en el mismo hosting. Sin servicios externos.
- **Formulario "Sumate al equipo":** se envía por mail con [FormSubmit](https://formsubmit.co).

Las decisiones técnicas y sus motivos están en [`docs/decisiones.md`](docs/decisiones.md).

## Estructura

```
index.html            Home (una sola página con todas las secciones)
blog/                 Listado de entradas y página de cada entrada
css/styles.css        Todos los estilos (colores definidos como variables en :root)
js/                   tema.js, main.js, formulario.js, blog.js y admin.js (uno por función)
img/                  Imágenes (img/placeholders/ = provisorias)
admin/                Panel: ingresar.php, index.php, salir.php
privado/              Código interno del panel; bloqueado para la web con .htaccess
  config.ejemplo.php  Plantilla de configuración (nombres sin valores reales)
  esquema.sql         Tablas de la base de datos
  usuarios.php        Herramienta de consola para administrar cuentas
  servidor-local.php  Router para el servidor de PHP en la PC (no se usa en el hosting)
docs/                 Documentación del proyecto
```

## Correrlo en la PC

**Requisitos:** PHP 8.2 o superior con las extensiones `pdo_mysql`, `mbstring`, `openssl` y `fileinfo`, y MariaDB 11 o MySQL 8.

1. Crear la base de datos y un usuario para el sitio, y cargar las tablas:
   ```sql
   CREATE DATABASE doblebarra CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'doblebarra'@'localhost' IDENTIFIED BY 'una-contraseña-larga';
   GRANT SELECT, INSERT, UPDATE, DELETE ON doblebarra.* TO 'doblebarra'@'localhost';
   ```
   Después importar `privado/esquema.sql` en esa base (por ejemplo: `mariadb -u root -p doblebarra < privado/esquema.sql`).
2. Copiar `privado/config.ejemplo.php` como `privado/config.php` y completar los datos de la base. En la PC, usar `'entorno' => 'desarrollo'`.
3. Crear una cuenta para el panel: `php privado/usuarios.php crear`.
4. Levantar el servidor desde la carpeta del proyecto:
   ```
   php -S localhost:3000 privado/servidor-local.php
   ```
   - Sitio: http://localhost:3000/index.html
   - Panel: http://localhost:3000/admin/ingresar.php
   - Para ver el blog con entradas de ejemplo: http://localhost:3000/index.html?ejemplos

## Configuración

Las contraseñas **nunca** van en el código ni en el repositorio. La configuración vive en un archivo PHP que devuelve un array; es el equivalente a un `.env`, pero no necesita librerías. La plantilla con los nombres es `privado/config.ejemplo.php`.

| Clave | Para qué sirve |
|---|---|
| `entorno` | `desarrollo` muestra los errores en pantalla; `produccion` los guarda solo en el log |
| `bd.host`, `bd.puerto`, `bd.nombre`, `bd.usuario`, `bd.clave` | Conexión a la base de datos |
| `sesion.minutos_inactividad` | Minutos sin actividad hasta que se cierra la sesión del panel (60) |
| `sesion.horas_maximas` | Duración máxima de una sesión (8) |

El sitio busca la configuración en este orden:
1. `doblebarra-config.php`, **fuera** de la carpeta pública (en el hosting, al lado de `public_html`). Es la opción recomendada en producción.
2. `privado/config.php`, dentro del proyecto. Se usa en la PC.

## Cuentas del panel

No hay registro público: las cuentas se crean desde la consola (en el hosting, cPanel → Terminal).

```
php privado/usuarios.php crear        crea una cuenta (con Enter genera una contraseña segura)
php privado/usuarios.php clave        cambia la contraseña
php privado/usuarios.php desactivar   bloquea una cuenta (y cierra su sesión)
php privado/usuarios.php activar      vuelve a habilitarla
php privado/usuarios.php listar       muestra las cuentas
```

## Publicar en el hosting (cPanel)

1. **Bases de datos MySQL:** crear la base y un usuario. Asignarle solo los permisos `SELECT`, `INSERT`, `UPDATE` y `DELETE`.
2. **phpMyAdmin:** elegir la base → Importar → `privado/esquema.sql`.
3. **Subir los archivos del sitio** a `public_html`, incluidos `admin/` y `privado/` con su `.htaccess`. No hace falta subir `privado/servidor-local.php`, `docs/` ni `README.md`.
4. **Crear la configuración real** a partir de `config.ejemplo.php` como `doblebarra-config.php`, en la carpeta de la cuenta **al lado de `public_html`** (no adentro), con `'entorno' => 'produccion'`.
5. **Forzar HTTPS** y activar compresión y caché en el `.htaccess` de la raíz.
6. **Terminal:** `php public_html/privado/usuarios.php crear` para cada persona del equipo.
7. **Probar:** ingreso, contraseña incorrecta, bloqueo tras 5 intentos y cierre de sesión.

Formulario "Sumate al equipo": activar el mail de destino en FormSubmit y reemplazar `COMPLETAR-alias-formsubmit` en el `action` del formulario de `index.html` por el alias que da FormSubmit.

## Seguridad del panel

- Contraseñas con `password_hash`.
- Sesión con cookie `HttpOnly`, `SameSite=Strict` y `Secure`, que vence por inactividad.
- Token CSRF en todos los formularios.
- Bloqueo temporal tras varios intentos fallidos.
- Consultas preparadas y salida escapada.
- Política de seguridad de contenido (CSP) estricta.
- La carpeta `privado/` no se puede abrir desde la web.

**Si una contraseña o credencial llega a subirse al repositorio:** hay que cambiarla de inmediato en el servicio (base de datos, FormSubmit, etc.). Borrar el commit no alcanza, porque queda en el historial.
