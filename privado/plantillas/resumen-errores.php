<?php
// Resumen de errores al principio del formulario. Antes de incluirlo, definir:
//   $errores (campo => mensaje), $errorGeneral (texto o null) y $tituloErrores.
// Cada error enlaza a su campo; el id del campo tiene que ser igual a la clave del error.
declare(strict_types=1);
?>
<?php if ($errores || $errorGeneral): ?>
  <!-- El foco se mueve acá al cargar (js/admin.js) para que el lector de pantalla lo lea primero -->
  <div class="resumen-errores" id="resumen-errores" tabindex="-1" data-enfocar>
    <h2 class="resumen-errores__titulo"><?= e($tituloErrores) ?></h2>
    <?php if ($errorGeneral): ?>
      <p><?= e($errorGeneral) ?></p>
    <?php endif; ?>
    <?php if ($errores): ?>
      <ul class="resumen-errores__lista">
        <?php foreach ($errores as $campo => $mensaje): ?>
          <li><a href="#<?= e($campo) ?>"><?= e($mensaje) ?></a></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
<?php endif; ?>
