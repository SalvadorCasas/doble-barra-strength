<?php
// Entradas publicadas del blog en JSON, para las tarjetas de la home y del listado (js/blog.js).
// Solo lectura: no recibe datos ni muestra borradores.
//   api/entradas.php             todas las publicadas, de la más nueva a la más vieja
//   api/entradas.php?cantidad=3  solo las 3 últimas
// Las rutas de las imágenes son relativas a la raíz del sitio (blog.js les agrega la que corresponde).
declare(strict_types=1);

require __DIR__ . '/../privado/arranque.php';
require __DIR__ . '/../privado/entradas.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=60'); // una entrada recién publicada aparece en menos de un minuto

$cantidad = min(max((int) ($_GET['cantidad'] ?? 0), 0), 50);

$entradas = array_map(function (array $entrada): array {
    $imagen = $entrada['imagen'] ? datosDeImagen($entrada['imagen'], '') : null;
    return [
        'titulo' => $entrada['titulo'],
        'slug' => $entrada['slug'],
        'fecha' => $entrada['fecha'],
        'autor' => $entrada['autor'],
        'resumen' => $entrada['resumen'],
        'imagen' => $imagen ? [
            'local' => $imagen['src'],
            'srcset' => $imagen['srcset'],
            'ancho' => $imagen['ancho'],
            'alto' => $imagen['alto'],
            'alt' => $entrada['imagen_alt'],
        ] : null,
    ];
}, entradasPublicadas($cantidad));

echo json_encode(['entradas' => $entradas], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
