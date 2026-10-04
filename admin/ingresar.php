<?php
// Pantalla de ingreso al panel (solo administradores; no hay registro público).
declare(strict_types=1);

require __DIR__ . '/../privado/arranque.php';

iniciarSesion();
enviarCabecerasPanel();

if (usuarioActual()) {
    redirigir('index.php');
}

$email = '';
$errores = [];          // errores de cada campo: ['email' => '…', 'clave' => '…']
$errorGeneral = null;   // error que no es de un campo (datos incorrectos, bloqueo, formulario vencido)

$avisos = [
    'salida' => 'Cerraste sesión. ¡Hasta la próxima!',
    'vencida' => 'Tu sesión se cerró por inactividad. Volvé a ingresar para seguir.',
];
$aviso = $avisos[$_GET['motivo'] ?? ''] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aviso = null;
    $email = trim((string) ($_POST['email'] ?? ''));
    $clave = (string) ($_POST['clave'] ?? '');

    if (!csrfValido()) {
        $errorGeneral = 'El formulario venció. Volvé a escribir tus datos e intentá de nuevo.';
    } else {
        if ($email === '') {
            $errores['email'] = 'Ingresá tu mail.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores['email'] = 'Ingresá un mail válido, por ejemplo nombre@ejemplo.com.';
        }
        if ($clave === '') {
            $errores['clave'] = 'Ingresá tu contraseña.';
        }
        if (!$errores) {
            $errorGeneral = intentarIngreso($email, $clave);
            if ($errorGeneral === null) {
                redirigir('index.php');
            }
        }
    }
}

$hayErrores = $errores || $errorGeneral;
$tituloPagina = 'Ingresar';
$usuario = null;
require __DIR__ . '/../privado/plantillas/encabezado.php';
?>

    <!-- ===================== INGRESO ===================== -->
    <section class="acceso">
      <div class="acceso__tarjeta">
        <div>
          <p class="antetitulo">Panel del equipo</p>
          <h1 class="acceso__titulo">Ingresar</h1>
          <p class="acceso__intro">Acceso para quienes publican en el blog de Doble Barra Strength.</p>
        </div>

        <?php if ($aviso): ?>
          <p class="aviso" role="status"><?= e($aviso) ?></p>
        <?php endif; ?>

        <?php
        $tituloErrores = $errorGeneral ? 'No pudimos ingresar' : 'Revisá los datos';
        require __DIR__ . '/../privado/plantillas/resumen-errores.php';
        ?>

        <form class="formulario-acceso" method="post" action="ingresar.php" novalidate>
          <?= campoCsrf() ?>

          <div class="campo">
            <label class="campo__etiqueta" for="email">Mail</label>
            <input class="campo__control" type="email" id="email" name="email" value="<?= e($email) ?>"
                   autocomplete="username" spellcheck="false" required aria-describedby="email-error"<?= marcaInvalido($errores, 'email') ?>>
            <?= errorDeCampo($errores, 'email') ?>
          </div>

          <div class="campo">
            <label class="campo__etiqueta" for="clave">Contraseña</label>
            <div class="campo-clave">
              <input class="campo__control" type="password" id="clave" name="clave"
                     autocomplete="current-password" required aria-describedby="clave-error"<?= marcaInvalido($errores, 'clave') ?>>
              <button type="button" class="boton-ver-clave" aria-controls="clave" aria-pressed="false" hidden>
                Mostrar<span class="oculto-accesible"> contraseña</span>
              </button>
            </div>
            <?= errorDeCampo($errores, 'clave') ?>
          </div>

          <button type="submit" class="boton boton--primario boton--grande boton--ancho">
            Ingresar
            <svg class="icono boton__flecha" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          </button>
        </form>

        <p class="acceso__ayuda">¿Olvidaste tu contraseña? Contactá al administrador del sitio para que te genere una nueva.</p>
      </div>
    </section>

<?php require __DIR__ . '/../privado/plantillas/pie.php'; ?>
