# Doble Barra Strength — sitio web

Sitio del equipo de powerlifting **Doble Barra Strength** (entrenadores Ivan Casas y Luca Bettinalio): presentación del equipo, contenido de YouTube, blog y formulario "Sumate al equipo". Incluye un panel privado para que el equipo publique en el blog.

- **Sitio público:** HTML + CSS + JavaScript sin frameworks ni compilación. Diseño mobile-first, tema claro/oscuro y accesibilidad WCAG 2.2 AA.
- **Panel del equipo** (`admin/`): PHP 8.2+ y MariaDB/MySQL, alojado en el mismo hosting. Sin servicios externos.
- **Formulario "Sumate al equipo":** se envía por mail con [FormSubmit](https://formsubmit.co).

Las decisiones técnicas y sus motivos están en [`docs/decisiones.md`](docs/decisiones.md).

## Estructura

```
index.html            Home (una sola página con todas las secciones)
blog/                 Listado de entradas (index.html) y página de cada entrada (entrada.php, armada por PHP)
api/                  entradas.php: entradas publicadas en JSON para las tarjetas del blog (solo lectura)
css/styles.css        Todos los estilos (colores definidos como variables en :root)
js/                   tema.js, main.js, formulario.js, blog.js, admin.js y editor.js (uno por función)
img/                  Imágenes (img/blog/ = las que se suben desde el panel; no está en el repositorio)
admin/                Panel: ingreso, listado de entradas (index.php), editor (entrada.php), borrado y subida de imágenes
privado/              Código interno del panel; bloqueado para la web con .htaccess
  config.ejemplo.php  Plantilla de configuración (nombres sin valores reales)
  esquema.sql         Tablas de la base de datos
  entradas.php        Guardar, validar y borrar entradas del blog
  formato.php         Convierte el texto con marcas (**negrita**, ## Subtítulo…) en bloques
  imagenes.php        Procesa las imágenes subidas (WebP, giro de la foto, limpieza)
  usuarios.php        Herramienta de consola para administrar cuentas
  servidor-local.php  Router para el servidor de PHP en la PC (no se usa en el hosting)
docs/                 Documentación del proyecto
```

## Correrlo en la PC

**Requisitos:** PHP 8.2 o superior con las extensiones `pdo_mysql`, `mbstring`, `openssl`, `fileinfo` y `gd` (con soporte WebP, para las imágenes del blog), y MariaDB 11 o MySQL 8.

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
   php -d upload_max_filesize=10M -d post_max_size=12M -S localhost:3000 privado/servidor-local.php
   ```
   - Sitio: http://localhost:3000/index.html
   - Panel: http://localhost:3000/admin/ingresar.php
   - El blog muestra las entradas publicadas desde el panel (sin ninguna, se ve el aviso "Muy pronto")

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

## Dónde se puede publicar

El panel necesita **PHP y MySQL/MariaDB**. Los hostings que solo publican archivos estáticos (Netlify, GitHub Pages, Vercel) no ejecutan PHP: entregan los `.php` como descarga. Ahí solo funciona la parte pública. Para esos casos, `netlify.toml` evita que se entregue el código del panel.

## Publicar en el hosting (cPanel)

**Forma recomendada:** clonar el repositorio con cPanel → **Git™ Version Control**, en una carpeta **fuera** de `public_html` (por ejemplo `/home/usuario/doblebarra`), y crear un subdominio cuya carpeta raíz sea esa. Para actualizar después: Git Version Control → Administrar → "Update from Remote". La carpeta `.git` queda bloqueada para la web por el `.htaccess` de la raíz.


1. **Bases de datos MySQL:** crear la base y un usuario. Asignarle solo los permisos `SELECT`, `INSERT`, `UPDATE` y `DELETE`.
2. **phpMyAdmin:** elegir la base → Importar → `privado/esquema.sql`.
3. **Archivos del sitio:** clonarlos como se explica arriba, o subirlos a mano incluidos `admin/`, `privado/` y los `.htaccess`. `docs/`, `README.md` y `.git` quedan bloqueados para la web.
4. **Crear la configuración real** a partir de `config.ejemplo.php` como `doblebarra-config.php`, **dos carpetas arriba de `privado/`** (con la estructura recomendada: `/home/usuario/doblebarra-config.php`, fuera de toda carpeta pública), con `'entorno' => 'produccion'`. Si el sitio está en una subcarpeta de `public_html`, usar `privado/config.php` (protegido por su `.htaccess`).
5. **HTTPS:** el `.htaccess` de la raíz ya fuerza HTTPS y activa la compresión y la caché. Revisar en "SSL/TLS Status" que el dominio o subdominio tenga certificado; si todavía no lo tiene, comentar las líneas de HTTPS hasta que esté.
6. **Terminal:** entrar a la carpeta del sitio (`cd ~/doblebarra`) y ejecutar `php privado/usuarios.php crear` para cada persona del equipo.
7. **Límite de subida de imágenes:** en cPanel → "MultiPHP INI Editor" (o "Select PHP Version" → Opciones), poner `upload_max_filesize = 10M` y `post_max_size = 12M`. Si no se cambia, el panel funciona igual y avisa el límite real (muchas veces 2 MB, poco para fotos de celular).
8. **Probar:** ingreso, contraseña incorrecta, bloqueo tras 5 intentos, cierre de sesión, crear una entrada con imagen y borrarla.

**Al actualizar con cambios en las tablas** (por ejemplo, al sumar el blog): volver a importar `privado/esquema.sql` en phpMyAdmin. Solo crea lo que falta; no borra datos.

**Respaldo:** las entradas viven en la base de datos y sus imágenes en `img/blog/`, que no está en el repositorio. Para respaldar el blog hay que guardar las dos cosas (phpMyAdmin → Exportar, y descargar la carpeta desde el Administrador de archivos).

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
