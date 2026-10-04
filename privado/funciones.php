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

/**
 * Convierte un texto en una dirección legible para la web: "¿Cómo armar tu bloque?" → "como-armar-tu-bloque".
 * Se usa para la dirección de cada entrada y para el nombre de las imágenes.
 */
function crearSlug(string $texto, int $largoMaximo = 100): string
{
    $texto = mb_strtolower($texto);
    $texto = strtr($texto, ['á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
        'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i', 'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u', 'ñ' => 'n', 'ç' => 'c']);
    $texto = trim((string) preg_replace('/[^a-z0-9]+/', '-', $texto), '-');
    return rtrim(substr($texto, 0, $largoMaximo), '-');
}

/** "2026-10-04" → "4 de octubre de 2026" */
function fechaLegible(string $fecha): string
{
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto',
        'septiembre', 'octubre', 'noviembre', 'diciembre'];
    [$anio, $mes, $dia] = array_map('intval', explode('-', substr($fecha, 0, 10)));
    return "$dia de {$meses[$mes - 1]} de $anio";
}

/** Lee un límite de tamaño del php.ini ("8M", "2G"…) y lo devuelve en bytes. */
function bytesDeIni(string $opcion): int
{
    $valor = trim((string) ini_get($opcion));
    $numero = (int) $valor;
    if ($numero <= 0) {
        return PHP_INT_MAX; // 0 o vacío: sin límite
    }
    return $numero * match (strtoupper(substr($valor, -1))) {
        'G' => 1024 ** 3,
        'M' => 1024 ** 2,
        'K' => 1024,
        default => 1,
    };
}

/**
 * Cuando lo que se envía supera el post_max_size del servidor, PHP descarta TODO el formulario
 * (llega vacío, sin token CSRF). Con esto se avisa el motivo real en vez de "el formulario venció".
 */
function superoLimiteDeEnvio(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST' && !$_POST && !$_FILES
        && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > bytesDeIni('post_max_size');
}

// ---------- Formularios del panel ----------

/** Párrafo con el error de un campo (oculto si no tiene). El campo lo vincula con aria-describedby. */
function errorDeCampo(array $errores, string $campo): string
{
    if (!isset($errores[$campo])) {
        return '<p class="campo__error" id="' . e($campo) . '-error" hidden></p>';
    }
    return '<p class="campo__error" id="' . e($campo) . '-error"><span class="oculto-accesible">Error: </span>'
        . e($errores[$campo]) . '</p>';
}

/** Marca el campo como inválido para los lectores de pantalla. */
function marcaInvalido(array $errores, string $campo): string
{
    return isset($errores[$campo]) ? ' aria-invalid="true"' : '';
}
