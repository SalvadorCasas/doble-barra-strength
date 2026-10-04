<?php
// Inicio de cada página del panel. Antes de incluirlo, definir:
//   $tituloPagina (texto del <title>), $usuario (array o null) y opcionalmente $hayErrores (bool)
//   y $conEditor (true en la página que tiene el editor de entradas).
declare(strict_types=1);

$prefijoTitulo = !empty($hayErrores) ? 'Error: ' : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($prefijoTitulo . $tituloPagina) ?> | Panel de Doble Barra Strength</title>
  <link rel="icon" href="../favicon-48.png" type="image/png">
  <link rel="apple-touch-icon" href="../apple-touch-icon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Oswald:wght@700&display=swap">
  <link rel="stylesheet" href="../css/styles.css">
  <script src="../js/tema.js"></script>
  <script src="../js/main.js" defer></script>
  <script src="../js/admin.js" defer></script>
  <?php if (!empty($conEditor)): ?>
  <script src="../js/editor.js" defer></script>
  <?php endif; ?>
</head>
<body>

  <a href="#contenido" class="saltar-contenido">Saltar al contenido</a>

  <!-- ===================== CABECERA DEL PANEL ===================== -->
  <header class="header">
    <div class="contenedor header__contenido">
      <a href="<?= $usuario ? 'index.php' : '../index.html' ?>" class="marca">
        <img class="marca__logo logo-adaptable" src="../img/marca/marca-db-160.webp" alt="" width="44" height="44">
        <span class="marca__texto">
          <span class="marca__nombre">Doble Barra</span>
          <span class="marca__sufijo"><?= $usuario ? 'Panel' : 'Strength' ?></span>
        </span>
      </a>

      <div class="panel-acciones">
        <?php if ($usuario): ?>
          <p class="panel-acciones__usuario"><span class="oculto-accesible">Sesión iniciada como </span><?= e($usuario['nombre']) ?></p>
          <form method="post" action="salir.php">
            <?= campoCsrf() ?>
            <button type="submit" class="boton boton--secundario panel-acciones__salir">Cerrar sesión</button>
          </form>
        <?php else: ?>
          <a class="enlace-flecha" href="../index.html">
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
            Volver al sitio
          </a>
        <?php endif; ?>
        <button type="button" class="boton-tema" hidden>
          <svg class="icono icono-luna" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
          <svg class="icono icono-sol" viewBox="0 0 24 24" aria-hidden="true" hidden><circle cx="12" cy="12" r="4.5"/><path d="M12 1.5v2.5M12 20v2.5M4.6 4.6l1.8 1.8M17.6 17.6l1.8 1.8M1.5 12H4M20 12h2.5M4.6 19.4l1.8-1.8M17.6 6.4l1.8-1.8"/></svg>
          <span class="boton-tema__texto oculto-accesible">Modo oscuro</span>
        </button>
      </div>
    </div>
  </header>

  <main id="contenido" tabindex="-1">
