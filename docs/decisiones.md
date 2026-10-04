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
- **Motivo:** el rojo `#D7141A` no alcanzaba 4,5:1 sobre negro, así que en el tema oscuro solo se usaba en textos grandes, fondos y bordes. **Reemplazada el 2026-10-04** por la identidad en negro y blanco (ver más abajo).

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
  2. Panel de entradas ✅
  3. Blog público conectado a la base ✅

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

### 2026-10-04 · Identidad visual en negro y blanco
- **Contexto:** el equipo entregó el logo, la firma, una foto de Ivan, el video de intro y una foto de una barra con discos. Pidió que los colores principales sean el negro y el blanco y que se saque el rojo por completo.
- **Decisión:**
  - **Paleta:** negro, blanco y grises. El acento es el negro en el tema claro y el blanco en el oscuro, sin rojo en la interfaz.
  - **Logo y marca "DB":** en el header y en el footer. Son blancos y se invierten a negro en el tema claro.
  - **Firma:** en el footer.
  - **Video de intro:** en el hero, recortado antes de los destellos blancos, sin audio. Se reproduce una vez y no se repite.
  - **Foto de Ivan:** en su tarjeta, sin la marca de agua, con permiso del fotógrafo.
  - **Foto de la barra:** de fondo en la banda CTA, con permiso de uso.
  - **Fotos:** a color, por pedido del equipo.
- **Motivo:**
  - Pedido del equipo.
  - Todas las combinaciones de texto y fondo se volvieron a medir: la más baja es 6.98:1.
  - Los errores del formulario, sin color de alerta, se identifican con ícono, texto y borde.
  - El video no se reproduce con movimiento reducido.
- **Ajuste (mismo día):**
  - **Humo en movimiento:** a pedido del equipo, después de la intro el humo sigue moviéndose con un fragmento que se repite. La primera versión era de ida y vuelta, y se notaba cuando el humo retrocedía. Se reemplazó por cámara lenta con cuadros intermedios calculados por movimiento y un bucle siempre hacia adelante, con un fundido entre el final y el principio. Así el humo se ve fluido, como al comienzo del video.
  - **Botón de pausa:** como ese movimiento no termina, se agregó un botón para pausarlo (WCAG 2.2.2) y el video se pausa solo cuando el hero no se ve.
  - **Videos regenerados:** se rehicieron en 1920 px para escritorio, porque la primera versión se veía borrosa, y sin las franjas negras del original.

### 2026-10-04 · Editor de entradas del blog (etapa 2)
- **Opciones:**
  - texto con marcas simples (`**negrita**`, `## Subtítulo`, `- lista`) + botones que las agregan + vista previa;
  - editor visual propio (se ve el formato al escribir);
  - librería de editor (Quill, TinyMCE).
- **Decisión:** texto con marcas simples, botones y vista previa. Alcance completo: listado, crear, editar, borrar, borrador/publicada, imagen principal e imágenes dentro del texto.
- **Motivo:**
  - Sin librerías ni cambios en la CSP, y accesible con teclado y lector de pantalla (es un campo de texto común).
  - El texto se guarda tal cual, **nunca como HTML**: se convierte al mostrarlo y todo pasa por `e()`. No hace falta limpiar etiquetas con una lista blanca (reemplaza lo previsto en CLAUDE.md para la etapa 2).
  - El servidor convierte el texto a los mismos bloques que ya lee `js/blog.js`, así la etapa 3 solo tiene que entregarlos.
- **Contras aceptadas:** mientras escriben se ven los símbolos, no el formato final (para eso está la vista previa).
- **Detalles:**
  - **Borrador:** solo pide título. **Publicar:** también resumen y texto.
  - **Dirección (slug):** se arma sola con el título y no se repite ("…-2"). Se puede cambiar a mano.
  - **Imágenes:** JPG, PNG o WebP de hasta 10 MB (o el límite del servidor si es menor). Se enderezan según el dato de giro de las fotos de celular, se guardan en WebP de 800 y 1600 px en `img/blog/` y se descartan los datos internos (incluida la ubicación GPS). Descripción (alt) obligatoria.
  - **Limpieza:** al guardar o borrar, se eliminan las imágenes que no usa ninguna entrada y tienen más de 24 horas.
  - **Sesión:** mientras se escribe, el editor avisa al servidor cada 5 minutos para que la sesión no venza en medio de una entrada larga. Si igual venció, avisa antes de enviar para no perder el texto.
  - **Cambios sin guardar:** el navegador pregunta antes de salir de la página.

### 2026-10-04 · Blog público conectado a la base (etapa 3)
- **Opciones para la página de cada entrada:** que la arme PHP en el servidor · que la arme el JavaScript del navegador (como hasta ahora).
- **Decisión:** la arma PHP (`blog/entrada.php`, reemplaza a `blog/entrada.html`). Las tarjetas de la home y del listado siguen con JavaScript, leyendo `api/entradas.php`.
- **Motivo:**
  - Google y las redes leen el título, el texto y la imagen; al compartir el link aparece la vista previa (Open Graph).
  - Carga más rápido y se lee aunque falle el JavaScript.
- **Además:**
  - Solo se ven las entradas publicadas; un borrador o una dirección inexistente dan "No encontramos esta entrada" (error 404).
  - Se sacó el modo `?ejemplos` y las imágenes de ejemplo: ya hay entradas reales.
  - El panel tiene un botón "Ver en el sitio" para las publicadas.
  - Netlify (que no ejecuta PHP) redirige `blog/entrada.php` al listado y bloquea `api/`.

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
