# Registro de decisiones técnicas

Cada decisión indica la fecha, las opciones evaluadas, lo que se decidió y por qué. Las pendientes están al final y se completan cuando se confirman.

---

## Decididas

### 2026-10-03 · Tipo de sitio
- **Opciones:** sitio estático (HTML, CSS y JS) · framework (React, Astro, etc.) · WordPress.
- **Decisión:** sitio estático, sin frameworks ni compilación, con la misma estructura que la página personal del desarrollador.
- **Motivo:** es simple de mantener sin ser programador, funciona en cualquier hosting compartido y no necesita un proceso de compilación.

### 2026-10-03 · Estructura de páginas
- **Opciones:** home de una sola página + blog aparte · home + página "Sumate" + blog · multipágina completa.
- **Decisión:** home de una sola página (con el formulario en `#sumate`) + páginas aparte para el blog.
- **Motivo:** es lo más parecido a la página de referencia y tiene menos partes repetidas que mantener.

### 2026-10-03 · Tema claro/oscuro
- **Opciones:** un solo tema · interruptor claro/oscuro.
- **Decisión:** interruptor. Por defecto respeta la preferencia del sistema y la elección se guarda entre páginas. El botón muestra el modo al que lleva ("Modo claro" / "Modo oscuro").
- **Motivo:** pedido del desarrollador. Duplica las verificaciones de contraste: cada color se mide en los dos temas.

### 2026-10-03 · Paleta de colores
- **Decisión:** rojo, negro, blanco y gris, definidos como variables en `:root`. Todas las combinaciones de texto y fondo se midieron para cumplir WCAG AA.
- **Motivo:** el rojo `#D7141A` no alcanza 4,5:1 sobre negro, así que en el tema oscuro solo se usa en textos grandes, fondos y bordes. Para el texto chico se usa un rojo más claro. El rojo se va a ajustar cuando llegue el logo.

### 2026-10-03 · Edad, altura y peso en el formulario
- **Opciones:** un solo campo de texto (como el Google Form original) · tres campos separados.
- **Decisión:** tres campos, cada uno con su unidad visible.
- **Motivo:** se puede validar cada dato, los errores son más claros y las respuestas llegan ordenadas.

### 2026-10-03 · Envío del formulario "Sumate al equipo"
- **Opciones:** formulario propio + mail con FormSubmit · guardar en la base de datos · seguir con el Google Form, enlazado o embebido.
- **Decisión:** formulario propio + FormSubmit, enviado sin recargar la página.
- **Motivo:** es el más accesible, mantiene el diseño del sitio y el desarrollador ya lo conoce. El Google Form embebido tiene doble scroll en el celular y no respeta el tema.
- **Contras aceptadas:** las respuestas llegan solo por mail y los datos pasan por un servicio externo. Además, FormSubmit no valida los campos del lado del servidor: ver la decisión pendiente "Procesar el formulario con PHP".

### 2026-10-03 · Blog y login de administradores
- **Opciones:**
  - Sanity: panel y login listos, pero es un servicio externo. Se llegó a configurar y se eliminó.
  - Supabase o Firebase: hay que programar el panel. Supabase pausa los proyectos gratuitos sin actividad.
  - **Login y panel propios en PHP + MariaDB en el hosting.**
- **Decisión:** login y panel propios.
- **Motivo:**
  - No depender de servicios externos.
  - Lo usan como mucho 2 personas, con muy poco tráfico.
  - El hosting candidato (plan Inicio de Duplika) tiene PHP 8.3, MariaDB 11.4, una base de datos y Apache con `.htaccess`.
  - El mantenimiento lo asumen el desarrollador y Claude.
- **Etapas:**
  1. Login ✅
  2. Panel de entradas
  3. Blog público conectado a la base

### 2026-10-03 · Seguridad del login
- **Decisión:**
  - Contraseñas con `password_hash`.
  - Mensaje de error genérico.
  - Bloqueo de 15 minutos tras 5 fallos por mail (o 20 por IP).
  - Sesión que vence a los 60 minutos de inactividad y a las 8 horas.
  - Token CSRF en todos los formularios.
  - CSP estricta.
  - Carpeta `privado/` bloqueada para la web.
  - Cuentas creadas solo por consola, sin registro público.
- **Motivo:** son las protecciones básicas recomendadas para un login, sin agregar librerías.

### 2026-10-03 · Dónde guardar las credenciales
- **Opciones:** archivo `.env` (necesita una librería en PHP) · archivo de configuración en PHP excluido del repositorio.
- **Decisión:**
  - **Archivo de configuración:** `config.php`, que devuelve un array. La plantilla sin valores es `privado/config.ejemplo.php` (el equivalente a `.env.example`).
  - **En el hosting:** el archivo va **fuera** de `public_html` (`doblebarra-config.php`).
  - **En la PC:** va en `privado/config.php`, que está en el `.gitignore`.
- **Motivo:**
  - Es nativo de PHP y no suma dependencias.
  - Fuera de la carpeta pública, el archivo no se puede pedir desde la web aunque falle otra protección.

### 2026-10-03 · Entorno local
- **Decisión:** PHP 8.3 y MariaDB 11.4 instalados con winget (las mismas versiones que el hosting). El servidor de pruebas es el de PHP, con un router que imita el bloqueo de `privado/`.
- **Motivo:** probar con versiones iguales a las del hosting evita sorpresas al publicar.

### 2026-10-03 · Contenido faltante
- **Decisión:** el sitio no muestra marcas tipo "[COMPLETAR]". Si falta un dato real del equipo (bios, servicios, plazos), ese bloque se oculta y queda comentado en el HTML.
- **Motivo:** pedido del desarrollador, para que el sitio se vea terminado sin inventar datos del cliente. Por eso la sección de servicios está oculta hasta que el equipo la defina.

### 2026-10-03 · Videos de YouTube
- **Decisión:** se muestran los 3 videos más recientes del canal, con miniatura. El reproductor se carga recién al tocar "reproducir"; sin JavaScript, son links a YouTube.
- **Motivo:** un reproductor de YouTube es pesado, y así la página carga rápido. El equipo puede elegir otros videos.

### 2026-10-03 · Acceso al panel desde el sitio
- **Opciones:** link en el footer · ícono en el header · sin link.
- **Decisión:** link discreto "Acceso del equipo" en el footer.
- **Motivo:** el equipo lo encuentra fácil y no distrae a los visitantes. Ocultarlo no agrega seguridad: la protección la da el login.

### 2026-10-03 · Dónde publicar la demo
- **Contexto:** la demo se había publicado en Netlify, que no ejecuta PHP. El login se descargaba como archivo, y el código del panel quedaba a la vista. No se expuso ninguna contraseña, porque `config.php` nunca estuvo en el repositorio.
- **Opciones:**
  - subcarpeta o subdominio en el hosting Duplika del desarrollador;
  - hosting PHP gratuito;
  - seguir en Netlify sin login;
  - volver a Sanity.
- **Decisión:** publicar la demo completa en el Duplika del desarrollador, en un subdominio. El repositorio se clona con Git Version Control de cPanel y la configuración queda fuera de la carpeta pública.
- **Motivo:**
  - Es el mismo entorno que el definitivo y no cuesta nada extra.
  - El código no cambia.
  - Funciona todo, incluido el login.
- **Además:**
  - Se agregó `netlify.toml` para que Netlify deje de entregar el código del panel mientras siga publicado.
  - Se agregó un `.htaccess` en la raíz que fuerza HTTPS, activa la compresión y la caché, y bloquea `.git`, `docs/` y el `README`.

### 2026-10-03 · Control de versiones
- **Decisión:** Git y GitHub. `main` es la versión estable y los cambios se trabajan en ramas aparte (por ejemplo, `feature/login`), con commits chicos y descriptivos.
- **Motivo:** permite volver atrás y revisar cada cambio antes de pasarlo a la versión estable.

---

## Pendientes

| Tema | Opciones | Estado |
|---|---|---|
| **Hosting definitivo del equipo** | Duplika plan Inicio (verificado: PHP 8.3, MariaDB 11.4, 1 base de datos, Apache) · otro proveedor con PHP y MySQL. **No sirven los hostings estáticos** (Netlify, GitHub Pages) | El equipo tiene que contratarlo. Mientras tanto, la demo está en el Duplika del desarrollador |
| **Procesar el formulario con PHP** | Seguir con FormSubmit · procesarlo en el propio hosting con PHP (valida del lado del servidor, puede guardar las solicitudes en la base para verlas en el panel y no depende de un tercero) | A evaluar ahora que hay PHP |
| **Campos obligatorios del formulario** | Propuesta: nombre, mail, objetivo, entrenador y teléfono | A confirmar por el equipo |
| **Sección de testimonios** | Incluirla solo con testimonios reales y con permiso · no incluirla | A confirmar por el equipo |
| **Fuentes tipográficas** | Google Fonts (hoy) · alojarlas en el propio sitio | Propuesta: alojarlas en el sitio, porque elimina una dependencia externa y mejora la velocidad |
