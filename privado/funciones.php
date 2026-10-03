<?php
// Funciones comunes: configuración, escape de texto, redirecciones y cabeceras de seguridad.
declare(strict_types=1);

/**
 * Dónde está el archivo de configuración (tiene las contraseñas). Se busca primero FUERA de la
 * carpeta pública del sitio (en el hosting: /home/usuario/doblebarra-config.php, al lado de
 * public_html), así nunca queda al alcance de la web. Si no está ahí, se usa privado/config.php.
 */
function rutaConfig(): ?string
{
    foreach ([dirname(__DIR__, 2) . '/doblebarra-config.php', __DIR__ . '/config.php'] as $ruta) {
        if (is_file($ruta)) {
            return $ruta;
        }
    }
    return null;
}

/** Lee un valor de la configuración con la forma "grupo.clave" (ej.: config('bd.host')). */
function config(string $ruta, mixed $porDefecto = null): mixed
{
    static $config = null;
    $config ??= require rutaConfig();

    $valor = $config;
    foreach (explode('.', $ruta) as $parte) {
        if (!is_array($valor) || !array_key_exists($parte, $valor)) {
            return $porDefecto;
        }
        $valor = $valor[$parte];
    }
    return $valor;
}

/** Escapa texto para mostrarlo en HTML. Todo dato que se imprime en una página pasa por acá. */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Redirige a otra página y corta la ejecución (303: después de un POST se carga con GET). */
function redirigir(string $destino): never
{
    header('Location: ' . $destino, true, 303);
    exit;
}

function esHttps(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

/** IP de quien hace el pedido. No se usan cabeceras como X-Forwarded-For porque se pueden falsificar. */
function ipCliente(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? 'desconocida');
}

/**
 * Cabeceras de seguridad de las páginas del panel:
 * - no se pueden mostrar dentro de otro sitio (evita engaños con marcos invisibles),
 * - solo cargan scripts y estilos propios (más las fuentes de Google),
 * - no se guardan en caché ni las indexan los buscadores.
 */
function enviarCabecerasPanel(): void
{
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' https://fonts.googleapis.com; "
        . "font-src https://fonts.gstatic.com; img-src 'self' data:; object-src 'none'; base-uri 'self'; "
        . "form-action 'self'; frame-ancestors 'none'");
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, nofollow');
}
