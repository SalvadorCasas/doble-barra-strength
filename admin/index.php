<?php
// Inicio del panel (solo con sesión iniciada). En la próxima etapa: gestión de entradas del blog.
declare(strict_types=1);

require __DIR__ . '/../privado/arranque.php';

iniciarSesion();
enviarCabecerasPanel();
$usuario = requerirUsuario();

$tituloPagina = 'Inicio';
$primerNombre = explode(' ', $usuario['nombre'])[0];
require __DIR__ . '/../privado/plantillas/encabezado.php';
?>

    <!-- ===================== INICIO DEL PANEL ===================== -->
    <section class="seccion panel">
      <div class="contenedor">
        <p class="antetitulo">Panel del equipo</p>
        <h1 class="panel__titulo">Hola, <?= e($primerNombre) ?></h1>
        <p class="seccion__intro">
          Desde este panel vas a poder crear, editar y publicar las entradas del blog.
          Esa parte se está construyendo y va a aparecer acá muy pronto.
        </p>

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
