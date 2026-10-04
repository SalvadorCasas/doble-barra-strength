<?php
// Confirmación antes de borrar una entrada. El borrado se hace solo por POST y con token CSRF.
declare(strict_types=1);

require __DIR__ . '/../privado/arranque.php';
require __DIR__ . '/../privado/entradas.php';

iniciarSesion();
enviarCabecerasPanel();
$usuario = requerirUsuario();

$entrada = buscarEntrada((int) ($_GET['id'] ?? 0));
if (!$entrada) {
    redirigir('index.php?aviso=no-encontrada');
}

$errores = [];
$errorGeneral = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (csrfValido()) {
        borrarEntrada($entrada['id']);
        redirigir('index.php?aviso=borrada');
    }
    $errorGeneral = 'El formulario venció. Volvé a tocar "Sí, borrar la entrada".';
}

$hayErrores = (bool) $errorGeneral;
$tituloPagina = 'Borrar entrada';
require __DIR__ . '/../privado/plantillas/encabezado.php';
?>

    <!-- ===================== CONFIRMAR BORRADO ===================== -->
    <section class="acceso">
      <div class="acceso__tarjeta">
        <div>
          <p class="antetitulo">Blog</p>
          <h1 class="acceso__titulo">¿Borrar esta entrada?</h1>
        </div>

        <?php
        $tituloErrores = 'No pudimos borrarla';
        require __DIR__ . '/../privado/plantillas/resumen-errores.php';
        ?>

        <p class="borrar__titulo">"<?= e($entrada['titulo']) ?>"</p>
        <p>
          Se borra para siempre y no se puede recuperar. Si solo querés que deje de verse en el sitio,
          <a href="entrada.php?id=<?= $entrada['id'] ?>">editala</a> y pasala a borrador.
        </p>

        <form class="borrar__acciones" method="post" action="borrar.php?id=<?= $entrada['id'] ?>">
          <?= campoCsrf() ?>
          <button type="submit" class="boton boton--primario">Sí, borrar la entrada</button>
          <a class="boton boton--secundario" href="index.php">Cancelar</a>
        </form>
      </div>
    </section>

<?php require __DIR__ . '/../privado/plantillas/pie.php'; ?>
