<?php
// Punto de partida de todas las páginas del panel y de las herramientas de consola:
// carga la configuración, prepara el manejo de errores y las funciones comunes.
declare(strict_types=1);

require __DIR__ . '/funciones.php';

if (rutaConfig() === null) {
    http_response_code(500);
    exit('Falta la configuración: copiá privado/config.ejemplo.php como privado/config.php (en la PC) '
        . 'o como doblebarra-config.php al lado de public_html (en el hosting) y completá los datos.' . PHP_EOL);
}

require __DIR__ . '/base-de-datos.php';
require __DIR__ . '/sesion.php';

$esDesarrollo = config('entorno') === 'desarrollo';
error_reporting(E_ALL);
ini_set('display_errors', $esDesarrollo ? '1' : '0');
ini_set('log_errors', '1');
date_default_timezone_set('America/Argentina/Cordoba');

// Si algo falla sin control, se registra el detalle en el log del servidor y la persona ve
// un mensaje claro (el detalle técnico solo se muestra en desarrollo).
set_exception_handler(function (Throwable $error) use ($esDesarrollo): void {
    error_log((string) $error);
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'Error: ' . $error->getMessage() . PHP_EOL);
        exit(1);
    }
    http_response_code(500);
    $detalle = $esDesarrollo ? '<pre>' . e((string) $error) . '</pre>' : '';
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Error | Panel de Doble Barra Strength</title></head>'
        . '<body><h1>Algo salió mal</h1><p>No pudimos completar la operación. Probá de nuevo en unos minutos.</p>'
        . $detalle . '</body></html>';
});
