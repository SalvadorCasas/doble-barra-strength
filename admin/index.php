<?php
// Inicio del panel (solo con sesión iniciada): listado de las entradas del blog.
declare(strict_types=1);

require __DIR__ . '/../privado/arranque.php';
require __DIR__ . '/../privado/entradas.php';

iniciarSesion();
enviarCabecerasPanel();
$usuario = requerirUsuario();

$avisos = [
    'borrada' => 'Borraste la entrada.',
    'no-encontrada' => 'No encontramos esa entrada. Puede que la haya borrado otra persona del equipo.',
];
$aviso = $avisos[$_GET['aviso'] ?? ''] ?? null;
$entradas = listarEntradas();

$tituloPagina = 'Entradas';
$primerNombre = explode(' ', $usuario['nombre'])[0];
require __DIR__ . '/../privado/plantillas/encabezado.php';
?>

    <!-- ===================== INICIO DEL PANEL ===================== -->
    <section class="seccion panel">
      <div class="contenedor">
        <p class="antetitulo">Panel del equipo</p>
        <h1 class="panel__titulo">Hola, <?= e($primerNombre) ?></h1>

        <?php if ($aviso): ?>
          <p class="aviso panel__aviso" role="status"><?= e($aviso) ?></p>
        <?php endif; ?>

        <!-- ===================== ENTRADAS DEL BLOG ===================== -->
        <div class="panel__encabezado">
          <div>
            <h2 class="panel__subtitulo">Entradas del blog</h2>
            <p class="panel__intro">
              Escribí, editá y publicá las entradas. Las publicadas se ven en el blog del sitio;
              los borradores, solo acá.
            </p>
          </div>
          <a class="boton boton--primario" href="entrada.php">
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            Nueva entrada
          </a>
        </div>

        <?php if (!$entradas): ?>
          <p class="panel__vacio">Todavía no hay entradas. Tocá "Nueva entrada" para escribir la primera.</p>
        <?php else: ?>
          <ul class="panel-entradas">
            <?php foreach ($entradas as $entrada): ?>
              <li class="panel-entradas__item">
                <div>
                  <h3 class="panel-entradas__titulo">
                    <a href="entrada.php?id=<?= (int) $entrada['id'] ?>"><?= e($entrada['titulo']) ?></a>
                  </h3>
                  <p class="panel-entradas__meta">
                    <span class="estado estado--<?= e($entrada['estado']) ?>"><?= e(ESTADOS_ENTRADA[$entrada['estado']]) ?></span>
                    <span><time datetime="<?= e($entrada['fecha']) ?>"><?= e(fechaLegible($entrada['fecha'])) ?></time> · Por <?= e($entrada['autor']) ?></span>
                  </p>
                </div>
                <div class="panel-entradas__acciones">
                  <?php if ($entrada['estado'] === 'publicada'): ?>
                    <a class="boton boton--secundario boton--chico" href="../blog/entrada.php?slug=<?= e($entrada['slug']) ?>">
                      Ver<span class="oculto-accesible"> "<?= e($entrada['titulo']) ?>" en el sitio</span>
                    </a>
                  <?php endif; ?>
                  <a class="boton boton--secundario boton--chico" href="entrada.php?id=<?= (int) $entrada['id'] ?>">
                    Editar<span class="oculto-accesible"> "<?= e($entrada['titulo']) ?>"</span>
                  </a>
                  <a class="boton boton--secundario boton--chico" href="borrar.php?id=<?= (int) $entrada['id'] ?>">
                    Borrar<span class="oculto-accesible"> "<?= e($entrada['titulo']) ?>"</span>
                  </a>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <div class="panel__nota">
          <h2 class="panel__nota-titulo">Sobre tu sesión</h2>
          <p>
            Por seguridad, la sesión se cierra sola después de
            <?= (int) config('sesion.minutos_inactividad', 60) ?> minutos sin actividad.
            Si usás una computadora compartida, acordate de tocar "Cerrar sesión" al terminar.
          </p>
        </div>
      </div>
    </section>

<?php require __DIR__ . '/../privado/plantillas/pie.php'; ?>
