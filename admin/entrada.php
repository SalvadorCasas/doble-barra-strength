<?php
// Crear o editar una entrada del blog. Sin "id" en la dirección es una entrada nueva.
// "Vista previa" muestra cómo va a quedar sin guardar; "Guardar" la guarda como borrador o publicada.
declare(strict_types=1);

require __DIR__ . '/../privado/arranque.php';
require __DIR__ . '/../privado/entradas.php';

iniciarSesion();
enviarCabecerasPanel();
$usuario = requerirUsuario();

$id = (int) ($_GET['id'] ?? 0);
$entrada = $id ? buscarEntrada($id) : entradaNueva($usuario);
if (!$entrada) {
    redirigir('index.php?aviso=no-encontrada');
}
$entradaGuardada = $entrada; // lo que está en la base (el formulario puede traer cambios sin guardar)

$errores = [];
$errorGeneral = null;
$vistaPrevia = false;
$avisos = [
    'borrador' => 'Guardaste la entrada como borrador. No se ve en el sitio hasta que la publiques.',
    'publicada' => 'Guardaste la entrada como publicada.',
];
$aviso = $avisos[$_GET['aviso'] ?? ''] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aviso = null;
    if (superoLimiteDeEnvio()) {
        $errorGeneral = 'Lo que enviaste pesa más de lo que acepta el servidor y no se pudo guardar. '
            . 'Volvé a cargar los cambios con una imagen de hasta ' . pesoLegible(pesoMaximoImagen()) . '.';
    } else {
        $entrada = entradaDelFormulario($entrada);
        if (!csrfValido()) {
            $errorGeneral = 'El formulario venció. Revisá los datos y volvé a tocar "Guardar".';
        } else {
            $entrada['imagen'] = imagenDelFormulario($entrada, (int) $usuario['id'], $errores);
            $errores += validarEntrada($entrada);
            $vistaPrevia = ($_POST['accion'] ?? '') === 'vista-previa';
            if (!$errores && !$vistaPrevia) {
                $id = guardarEntrada($entrada, (int) $usuario['id']);
                redirigir("entrada.php?id=$id&aviso={$entrada['estado']}");
            }
        }
    }
}

$esNueva = !$entrada['id'];
$hayErrores = $errores || $errorGeneral;
$tituloPagina = $esNueva ? 'Nueva entrada' : 'Editar entrada';
$conEditor = true;
$pesoMaximo = pesoMaximoImagen();
$accion = $esNueva ? 'entrada.php' : 'entrada.php?id=' . $entrada['id'];
require __DIR__ . '/../privado/plantillas/encabezado.php';
?>

    <!-- ===================== EDITOR DE ENTRADA ===================== -->
    <section class="seccion panel">
      <div class="contenedor contenedor--angosto">
        <a class="enlace-flecha panel__volver" href="index.php">
          <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
          Volver a las entradas
        </a>
        <p class="antetitulo">Blog</p>
        <h1 class="panel__titulo"><?= e($tituloPagina) ?></h1>
        <?php if (!$esNueva): ?>
          <p class="panel__intro">
            Por <?= e($entradaGuardada['autor']) ?> · <?= e(ESTADOS_ENTRADA[$entradaGuardada['estado']]) ?>
            <?php if ($entradaGuardada['estado'] === 'publicada'): ?>
              · <a href="../blog/entrada.php?slug=<?= e($entradaGuardada['slug']) ?>">Ver en el sitio</a>
            <?php endif; ?>
          </p>
        <?php endif; ?>

        <?php if ($aviso): ?>
          <p class="aviso panel__aviso" role="status"><?= e($aviso) ?></p>
        <?php endif; ?>

        <div class="editor">
          <?php
          $tituloErrores = 'Revisá la entrada';
          require __DIR__ . '/../privado/plantillas/resumen-errores.php';
          ?>

          <?php if ($vistaPrevia): ?>
            <!-- ===================== VISTA PREVIA ===================== -->
            <section class="vista-previa" aria-labelledby="vista-previa-titulo" tabindex="-1"<?= $hayErrores ? '' : ' data-enfocar' ?>>
              <h2 class="vista-previa__titulo" id="vista-previa-titulo">Vista previa</h2>
              <p class="vista-previa__aviso">Así se va a ver la entrada. Todavía no está guardada: tocá "Guardar" para no perder los cambios.</p>
              <article class="vista-previa__entrada">
                <h3 class="entrada__titulo"><?= e($entrada['titulo'] ?: 'Sin título') ?></h3>
                <p class="entrada__meta">
                  Por <?= e($entrada['autor']) ?>
                  <?php if (!isset($errores['fecha'])): ?>
                    · <time datetime="<?= e($entrada['fecha']) ?>"><?= e(fechaLegible($entrada['fecha'])) ?></time>
                  <?php endif; ?>
                </p>
                <?php if ($entrada['imagen']): ?>
                  <?= htmlDeImagen($entrada['imagen'], $entrada['imagen_alt'], '../', 'entrada__imagen') ?>
                <?php endif; ?>
                <div class="contenido-enriquecido">
                  <?= bloquesAHtml(textoABloques($entrada['cuerpo']), '../') ?>
                </div>
              </article>
            </section>
          <?php endif; ?>

          <form class="formulario editor__formulario" method="post" action="<?= e($accion) ?>"
                enctype="multipart/form-data" novalidate data-editor data-peso-maximo="<?= $pesoMaximo ?>"<?= $_SERVER['REQUEST_METHOD'] === 'POST' ? ' data-sin-guardar' : '' ?>>
            <?= campoCsrf() ?>
            <p class="campo__ayuda">Para guardar un borrador solo hace falta el título. Para publicar, también el resumen y el texto.</p>

            <div class="campo">
              <label class="campo__etiqueta" for="titulo">Título <span class="campo__obligatorio">(obligatorio)</span></label>
              <input class="campo__control" type="text" id="titulo" name="titulo" value="<?= e($entrada['titulo']) ?>"
                     maxlength="<?= LARGO_TITULO ?>" required aria-describedby="titulo-error"<?= marcaInvalido($errores, 'titulo') ?>>
              <?= errorDeCampo($errores, 'titulo') ?>
            </div>

            <div class="campo-fila">
              <div class="campo">
                <label class="campo__etiqueta" for="fecha">Fecha <span class="campo__obligatorio">(obligatoria)</span></label>
                <input class="campo__control" type="date" id="fecha" name="fecha" value="<?= e($entrada['fecha']) ?>"
                       required aria-describedby="fecha-error"<?= marcaInvalido($errores, 'fecha') ?>>
                <?= errorDeCampo($errores, 'fecha') ?>
              </div>

              <div class="campo">
                <label class="campo__etiqueta" for="slug">Dirección <span class="campo__obligatorio">(opcional)</span></label>
                <input class="campo__control" type="text" id="slug" name="slug" value="<?= e($entrada['slug']) ?>"
                       maxlength="<?= LARGO_SLUG ?>" spellcheck="false" autocomplete="off" autocapitalize="none"
                       aria-describedby="slug-ayuda slug-error"<?= marcaInvalido($errores, 'slug') ?>>
                <p class="campo__ayuda" id="slug-ayuda">
                  La parte final del link de la entrada. Si la dejás vacía, se arma sola con el título.
                  <?php if (!$esNueva): ?>Si la cambiás después de publicar, los links que ya se compartieron dejan de funcionar.<?php endif; ?>
                </p>
                <?= errorDeCampo($errores, 'slug') ?>
              </div>
            </div>

            <div class="campo">
              <label class="campo__etiqueta" for="resumen">Resumen <span class="campo__obligatorio">(obligatorio para publicar)</span></label>
              <p class="campo__ayuda" id="resumen-ayuda">Una o dos frases, hasta <?= LARGO_RESUMEN ?> caracteres. Se muestra en las tarjetas del blog y en Google.</p>
              <textarea class="campo__control editor__resumen" id="resumen" name="resumen" rows="3" maxlength="<?= LARGO_RESUMEN ?>"
                        aria-describedby="resumen-ayuda resumen-error"<?= marcaInvalido($errores, 'resumen') ?>><?= e($entrada['resumen']) ?></textarea>
              <?= errorDeCampo($errores, 'resumen') ?>
            </div>

            <!-- Imagen principal: arriba del texto y en las tarjetas del blog -->
            <fieldset class="campo editor__portada">
              <legend class="campo__etiqueta">Imagen principal <span class="campo__obligatorio">(opcional)</span></legend>
              <input type="hidden" name="imagen_actual" value="<?= e($entrada['imagen'] ?? '') ?>">

              <?php if ($entrada['imagen']): ?>
                <div class="editor__portada-actual">
                  <?= htmlDeImagen($entrada['imagen'], $entrada['imagen_alt'], '../', 'editor__portada-imagen') ?>
                  <label class="opcion">
                    <input type="checkbox" name="quitar_imagen" value="1">
                    Quitar la imagen
                  </label>
                </div>
              <?php endif; ?>

              <div class="campo">
                <label class="campo__etiqueta" for="imagen"><?= $entrada['imagen'] ? 'Cambiar la imagen' : 'Elegir una imagen' ?></label>
                <p class="campo__ayuda" id="imagen-ayuda">JPG, PNG o WebP de hasta <?= e(pesoLegible($pesoMaximo)) ?>. Preferentemente horizontal.</p>
                <input class="campo__control" type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp"
                       aria-describedby="imagen-ayuda imagen-error"<?= marcaInvalido($errores, 'imagen') ?>>
                <?= errorDeCampo($errores, 'imagen') ?>
              </div>

              <div class="campo">
                <label class="campo__etiqueta" for="imagen_alt">Descripción de la imagen <span class="campo__obligatorio">(obligatoria si hay imagen)</span></label>
                <p class="campo__ayuda" id="imagen_alt-ayuda">Contá qué se ve, para quienes usan lector de pantalla o no pueden cargar la imagen.</p>
                <input class="campo__control" type="text" id="imagen_alt" name="imagen_alt" value="<?= e($entrada['imagen_alt']) ?>"
                       maxlength="<?= LARGO_ALT ?>" aria-describedby="imagen_alt-ayuda imagen_alt-error"<?= marcaInvalido($errores, 'imagen_alt') ?>>
                <?= errorDeCampo($errores, 'imagen_alt') ?>
              </div>
            </fieldset>

            <!-- Texto de la entrada: los botones (js/editor.js) agregan las marcas de formato -->
            <div class="campo">
              <label class="campo__etiqueta" for="cuerpo">Texto <span class="campo__obligatorio">(obligatorio para publicar)</span></label>
              <p class="campo__ayuda" id="cuerpo-ayuda">Cada vez que apretás Enter empieza un párrafo nuevo. Más abajo se explica cómo dar formato.</p>
              <div class="editor__barra" role="group" aria-label="Formato del texto" data-editor-barra hidden>
                <button type="button" class="editor__boton editor__boton--negrita" data-formato="negrita" aria-keyshortcuts="Control+B">Negrita</button>
                <button type="button" class="editor__boton editor__boton--cursiva" data-formato="cursiva" aria-keyshortcuts="Control+I">Cursiva</button>
                <button type="button" class="editor__boton" data-formato="subtitulo">Subtítulo</button>
                <button type="button" class="editor__boton" data-formato="lista">Lista</button>
                <button type="button" class="editor__boton" data-formato="numerada">Lista numerada</button>
                <button type="button" class="editor__boton" data-formato="cita">Cita</button>
                <button type="button" class="editor__boton" data-formato="enlace" aria-haspopup="dialog" aria-keyshortcuts="Control+K">Link</button>
                <button type="button" class="editor__boton" data-formato="imagen" aria-haspopup="dialog">Imagen</button>
              </div>
              <textarea class="campo__control editor__texto" id="cuerpo" name="cuerpo" rows="18"
                        aria-describedby="cuerpo-ayuda cuerpo-error"<?= marcaInvalido($errores, 'cuerpo') ?>><?= e($entrada['cuerpo']) ?></textarea>
              <?= errorDeCampo($errores, 'cuerpo') ?>

              <details class="editor__ayuda">
                <summary>Cómo dar formato al texto</summary>
                <p>Podés usar los botones o escribir las marcas a mano:</p>
                <ul>
                  <li><code>**texto**</code> → <strong>negrita</strong></li>
                  <li><code>*texto*</code> → <em>cursiva</em></li>
                  <li><code>## Texto</code> al principio de la línea → subtítulo (<code>###</code> para uno más chico)</li>
                  <li><code>- texto</code> al principio de la línea → punto de una lista</li>
                  <li><code>1. texto</code> al principio de la línea → paso de una lista numerada</li>
                  <li><code>&gt; texto</code> al principio de la línea → cita destacada</li>
                  <li><code>[texto](https://…)</code> → link</li>
                  <li>Las imágenes se suben con el botón "Imagen" y quedan como <code>![descripción](nombre-de-la-imagen)</code>.</li>
                </ul>
              </details>
            </div>

            <fieldset class="campo">
              <legend class="campo__etiqueta">Estado</legend>
              <div class="opciones opciones--anchas">
                <?php foreach (ESTADOS_ENTRADA as $valor => $texto): ?>
                  <label class="opcion">
                    <input type="radio" id="estado-<?= e($valor) ?>" name="estado" value="<?= e($valor) ?>"
                           <?= $entrada['estado'] === $valor ? 'checked' : '' ?>>
                    <?= e($valor === 'borrador' ? 'Borrador (no se ve en el sitio)' : $texto) ?>
                  </label>
                <?php endforeach; ?>
              </div>
            </fieldset>

            <p class="formulario__alerta" role="alert" data-editor-alerta></p>

            <!-- El primer botón es el que se usa al apretar Enter en un campo: guardar -->
            <div class="editor__acciones">
              <button type="submit" class="boton boton--primario boton--grande" name="accion" value="guardar">Guardar</button>
              <button type="submit" class="boton boton--secundario boton--grande" name="accion" value="vista-previa">Vista previa</button>
            </div>
          </form>
        </div>
      </div>
    </section>

    <!-- ===================== VENTANAS DEL EDITOR (solo con JavaScript) ===================== -->
    <dialog class="dialogo" id="dialogo-enlace" aria-labelledby="dialogo-enlace-titulo">
      <form class="dialogo__formulario" method="dialog" novalidate data-dialogo="enlace">
        <h2 class="dialogo__titulo" id="dialogo-enlace-titulo">Agregar un link</h2>
        <div class="campo">
          <label class="campo__etiqueta" for="enlace-texto">Texto del link</label>
          <input class="campo__control" type="text" id="enlace-texto" name="texto" required aria-describedby="enlace-texto-error">
          <p class="campo__error" id="enlace-texto-error" hidden></p>
        </div>
        <div class="campo">
          <label class="campo__etiqueta" for="enlace-direccion">Dirección</label>
          <p class="campo__ayuda" id="enlace-direccion-ayuda">Copiala de la barra del navegador. Empieza con https://</p>
          <input class="campo__control" type="url" id="enlace-direccion" name="direccion" inputmode="url" spellcheck="false"
                 autocomplete="off" required aria-describedby="enlace-direccion-ayuda enlace-direccion-error">
          <p class="campo__error" id="enlace-direccion-error" hidden></p>
        </div>
        <div class="dialogo__acciones">
          <button type="submit" class="boton boton--primario">Agregar link</button>
          <button type="button" class="boton boton--secundario" data-cerrar>Cancelar</button>
        </div>
      </form>
    </dialog>

    <dialog class="dialogo" id="dialogo-imagen" aria-labelledby="dialogo-imagen-titulo">
      <form class="dialogo__formulario" method="dialog" novalidate data-dialogo="imagen">
        <h2 class="dialogo__titulo" id="dialogo-imagen-titulo">Agregar una imagen al texto</h2>
        <div class="campo">
          <label class="campo__etiqueta" for="imagen-archivo">Imagen</label>
          <p class="campo__ayuda" id="imagen-archivo-ayuda">JPG, PNG o WebP de hasta <?= e(pesoLegible($pesoMaximo)) ?>.</p>
          <input class="campo__control" type="file" id="imagen-archivo" name="archivo" accept="image/jpeg,image/png,image/webp"
                 required aria-describedby="imagen-archivo-ayuda imagen-archivo-error">
          <p class="campo__error" id="imagen-archivo-error" hidden></p>
        </div>
        <div class="campo">
          <label class="campo__etiqueta" for="imagen-alt">Descripción de la imagen</label>
          <p class="campo__ayuda" id="imagen-alt-ayuda">Contá qué se ve, para quienes usan lector de pantalla o no pueden cargar la imagen.</p>
          <input class="campo__control" type="text" id="imagen-alt" name="alt" maxlength="<?= LARGO_ALT ?>" required
                 aria-describedby="imagen-alt-ayuda imagen-alt-error">
          <p class="campo__error" id="imagen-alt-error" hidden></p>
        </div>
        <div class="campo">
          <label class="campo__etiqueta" for="imagen-leyenda">Epígrafe <span class="campo__obligatorio">(opcional)</span></label>
          <p class="campo__ayuda" id="imagen-leyenda-ayuda">Texto chico que se ve debajo de la imagen.</p>
          <input class="campo__control" type="text" id="imagen-leyenda" name="leyenda" maxlength="<?= LARGO_ALT ?>"
                 aria-describedby="imagen-leyenda-ayuda">
        </div>
        <p class="formulario__estado" role="status" data-dialogo-estado></p>
        <p class="formulario__alerta" role="alert" data-dialogo-alerta></p>
        <div class="dialogo__acciones">
          <button type="submit" class="boton boton--primario">Subir y agregar</button>
          <button type="button" class="boton boton--secundario" data-cerrar>Cancelar</button>
        </div>
      </form>
    </dialog>

<?php require __DIR__ . '/../privado/plantillas/pie.php'; ?>
