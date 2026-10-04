<?php
// Recibe una imagen para el texto de una entrada (la envía js/editor.js) y responde en JSON
// con el nombre que hay que poner en el texto: { "nombre": "…" } o { "error": "…" }.
declare(strict_types=1);

require __DIR__ . '/../privado/arranque.php';
require __DIR__ . '/../privado/entradas.php';

iniciarSesion();
enviarCabecerasPanel();
header('Content-Type: application/json; charset=utf-8');

function responder(int $codigo, array $datos): never
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, ['error' => 'Método no permitido.']);
}
// Acá no se usa requerirUsuario(): redirigiría al ingreso y el editor necesita una respuesta en JSON
$usuario = usuarioActual();
if (!$usuario) {
    responder(401, ['error' => 'Tu sesión se cerró. Copiá tu texto, volvé a ingresar en otra pestaña y probá de nuevo.']);
}
if (superoLimiteDeEnvio()) {
    responder(413, ['error' => 'La imagen pesa más de ' . pesoLegible(pesoMaximoImagen()) . '. Elegí una más liviana.']);
}
if (!csrfValido()) {
    responder(403, ['error' => 'El formulario venció. Recargá la página y probá de nuevo.']);
}

$alt = trim((string) ($_POST['alt'] ?? ''));
if ($alt === '') {
    responder(422, ['error' => 'Escribí la descripción de la imagen.']);
}

try {
    responder(200, ['nombre' => guardarImagenSubida($_FILES['imagen'] ?? null, $alt, (int) $usuario['id'])]);
} catch (ImagenInvalida $error) {
    responder(422, ['error' => $error->getMessage()]);
}
