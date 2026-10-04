<?php
// El editor (js/editor.js) consulta esta página cada tanto MIENTRAS se escribe: así la sesión no
// vence en medio de una entrada larga. Si la persona deja de escribir, la sesión vence igual.
// Responde 204 si la sesión sigue activa o 401 si ya se cerró (el editor lo avisa).
declare(strict_types=1);

require __DIR__ . '/../privado/arranque.php';

iniciarSesion();
enviarCabecerasPanel();
http_response_code(usuarioActual() ? 204 : 401);
