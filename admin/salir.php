<?php
// Cierre de sesión. Solo por POST y con token CSRF: así ningún link de otro sitio
// puede cerrar la sesión de alguien sin que lo sepa.
declare(strict_types=1);

require __DIR__ . '/../privado/arranque.php';

iniciarSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfValido()) {
    redirigir('index.php');
}

cerrarSesion();
redirigir('ingresar.php?motivo=salida');
