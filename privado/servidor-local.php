<?php
// Solo para probar el sitio en la PC con el servidor que trae PHP:
//   php -S localhost:3000 privado/servidor-local.php
// Ese servidor no lee los .htaccess, así que acá se imita lo que hace el hosting:
// bloquear el acceso web a la carpeta privado/. En el hosting este archivo no se usa.
$ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (preg_match('#^/privado(/|$)#i', $ruta)) {
    http_response_code(403);
    exit('Acceso prohibido');
}
return false; // el resto se sirve normalmente
