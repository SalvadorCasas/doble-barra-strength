<?php
// Imágenes del blog: recibe la foto subida desde el panel, la endereza (las fotos del celular
// guardan el giro aparte), la achica y la guarda en WebP en dos anchos.
// Al volver a generarla se descartan los datos internos de la foto (EXIF), incluida la ubicación GPS.
// La original nunca se guarda.
declare(strict_types=1);

const CARPETA_IMAGENES_BLOG = __DIR__ . '/../img/blog';
const RUTA_IMAGENES_BLOG = 'img/blog/';     // desde la raíz del sitio
const ANCHO_CHICO_IMAGEN = 800;
const ANCHO_MAXIMO_IMAGEN = 1600;          // el texto del blog mide 44rem: alcanza para pantallas de alta densidad
const CALIDAD_WEBP = 80;
const PESO_MAXIMO_IMAGEN = 10 * 1024 * 1024;
const PIXELES_MAXIMOS_IMAGEN = 30_000_000; // más que eso no entra en la memoria del hosting
const HORAS_GRACIA_IMAGENES = 24;          // una imagen sin usar se borra recién después de este tiempo

/** Error con un mensaje pensado para mostrarle a quien sube la imagen. */
final class ImagenInvalida extends RuntimeException
{
}

/** Peso máximo real: el menor entre el del panel y los límites del servidor (php.ini). */
function pesoMaximoImagen(): int
{
    return min(PESO_MAXIMO_IMAGEN, bytesDeIni('upload_max_filesize'), bytesDeIni('post_max_size'));
}

/** "10 MB" para mostrar en los textos de ayuda y errores. */
function pesoLegible(int $bytes): string
{
    return round($bytes / 1024 / 1024, 1) . ' MB';
}

/** Anchos en los que se guarda una imagen, según el ancho más grande que se generó. */
function anchosDeImagen(int $ancho): array
{
    return $ancho > ANCHO_CHICO_IMAGEN ? [ANCHO_CHICO_IMAGEN, $ancho] : [$ancho];
}

/**
 * Procesa una imagen subida y la registra en la base. Devuelve su nombre.
 * La descripción se usa para que el nombre del archivo sea legible (mejor para buscadores).
 */
function guardarImagenSubida(?array $archivo, string $descripcion, int $usuarioId): string
{
    $error = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) {
        throw new ImagenInvalida('Elegí una imagen.');
    }
    if (in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $archivo['size'] > pesoMaximoImagen()) {
        throw new ImagenInvalida('La imagen pesa más de ' . pesoLegible(pesoMaximoImagen()) . '. Elegí una más liviana.');
    }
    if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) {
        throw new ImagenInvalida('No pudimos recibir la imagen. Probá de nuevo.');
    }

    $ruta = $archivo['tmp_name'];
    $info = @getimagesize($ruta);
    $tipos = [IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_WEBP => 'imagecreatefromwebp'];
    if (!$info || !isset($tipos[$info[2]])) {
        throw new ImagenInvalida('Solo se aceptan imágenes JPG, PNG o WebP.');
    }
    if ($info[0] * $info[1] > PIXELES_MAXIMOS_IMAGEN) {
        throw new ImagenInvalida('La imagen tiene demasiados píxeles. Achicala a menos de 5000 px de ancho y volvé a subirla.');
    }

    // Abrir una foto grande ocupa mucha memoria (unos 4 bytes por píxel)
    ini_set('memory_limit', '256M');
    $original = @$tipos[$info[2]]($ruta);
    if (!$original) {
        throw new ImagenInvalida('No pudimos abrir la imagen. Puede estar dañada: probá con otra.');
    }
    if ($info[2] === IMAGETYPE_JPEG) {
        $original = enderezar($original, orientacionJpeg($ruta));
    }

    $anchoOriginal = imagesx($original);
    $altoOriginal = imagesy($original);
    $anchoFinal = min($anchoOriginal, ANCHO_MAXIMO_IMAGEN);
    $nombre = (crearSlug($descripcion, 50) ?: 'imagen') . '-' . bin2hex(random_bytes(3));

    if (!is_dir(CARPETA_IMAGENES_BLOG) && !mkdir(CARPETA_IMAGENES_BLOG, 0755, true)) {
        throw new RuntimeException('No se pudo crear la carpeta ' . CARPETA_IMAGENES_BLOG);
    }
    foreach (anchosDeImagen($anchoFinal) as $ancho) {
        $alto = max(1, (int) round($altoOriginal * $ancho / $anchoOriginal));
        $copia = imagecreatetruecolor($ancho, $alto);
        imagealphablending($copia, false); // conserva la transparencia de los PNG
        imagesavealpha($copia, true);
        imagecopyresampled($copia, $original, 0, 0, 0, 0, $ancho, $alto, $anchoOriginal, $altoOriginal);
        if (!imagewebp($copia, CARPETA_IMAGENES_BLOG . "/$nombre-$ancho.webp", CALIDAD_WEBP)) {
            throw new RuntimeException("No se pudo guardar la imagen $nombre-$ancho.webp");
        }
    }

    bd()->prepare('INSERT INTO imagenes (nombre, ancho, alto, subida_por) VALUES (?, ?, ?, ?)')
        ->execute([$nombre, $anchoFinal, (int) round($altoOriginal * $anchoFinal / $anchoOriginal), $usuarioId]);
    return $nombre;
}

/**
 * Lee el giro guardado en los datos EXIF de un JPG (1 = normal). Se lee "a mano" porque la
 * extensión exif de PHP no siempre está instalada: se busca la etiqueta 0x0112 del bloque EXIF.
 */
function orientacionJpeg(string $ruta): int
{
    $datos = (string) file_get_contents($ruta, false, null, 0, 131072);
    $posicion = 2; // después de la marca de inicio del JPG
    while ($posicion + 4 <= strlen($datos) && $datos[$posicion] === "\xFF") {
        $marcador = ord($datos[$posicion + 1]);
        $largo = unpack('n', $datos, $posicion + 2)[1];
        if ($marcador === 0xE1 && substr($datos, $posicion + 4, 6) === "Exif\0\0") {
            $tiff = $posicion + 10;
            $intel = substr($datos, $tiff, 2) === 'II'; // orden de los bytes de los números
            $leer16 = fn (int $en) => unpack($intel ? 'v' : 'n', $datos, $en)[1] ?? 0;
            $leer32 = fn (int $en) => unpack($intel ? 'V' : 'N', $datos, $en)[1] ?? 0;
            $directorio = $tiff + $leer32($tiff + 4);
            if ($directorio + 2 > strlen($datos)) {
                return 1;
            }
            $cantidad = $leer16($directorio);
            for ($i = 0; $i < $cantidad && $directorio + 14 + $i * 12 <= strlen($datos); $i++) {
                $etiqueta = $directorio + 2 + $i * 12;
                if ($leer16($etiqueta) === 0x0112) {
                    return $leer16($etiqueta + 8);
                }
            }
            return 1;
        }
        if ($marcador === 0xDA) { // empieza la imagen en sí: ya no hay datos EXIF
            break;
        }
        $posicion += 2 + $largo;
    }
    return 1;
}

/** Aplica el giro de la foto para que se vea derecha (GD gira en sentido antihorario). */
function enderezar(GdImage $imagen, int $orientacion): GdImage
{
    if (in_array($orientacion, [5, 6, 7], true)) {
        $imagen = imagerotate($imagen, 270, 0); // 90° en sentido horario
    } elseif ($orientacion === 8) {
        $imagen = imagerotate($imagen, 90, 0);
    } elseif ($orientacion === 3) {
        $imagen = imagerotate($imagen, 180, 0);
    }
    if (in_array($orientacion, [2, 5], true)) {
        imageflip($imagen, IMG_FLIP_HORIZONTAL);
    } elseif (in_array($orientacion, [4, 7], true)) {
        imageflip($imagen, IMG_FLIP_VERTICAL);
    }
    return $imagen;
}

/** Datos de una imagen para mostrarla (src, srcset y medidas), o null si no existe. */
function datosDeImagen(string $nombre, string $raiz): ?array
{
    static $cache = [];
    if (!array_key_exists($nombre, $cache)) {
        $consulta = bd()->prepare('SELECT nombre, ancho, alto FROM imagenes WHERE nombre = ?');
        $consulta->execute([$nombre]);
        $cache[$nombre] = $consulta->fetch() ?: null;
    }
    $imagen = $cache[$nombre];
    if (!$imagen) {
        return null;
    }

    $ruta = fn (int $ancho) => $raiz . RUTA_IMAGENES_BLOG . "{$imagen['nombre']}-$ancho.webp";
    $anchos = anchosDeImagen((int) $imagen['ancho']);
    return [
        'src' => $ruta(end($anchos)),
        'srcset' => implode(', ', array_map(fn (int $ancho) => $ruta($ancho) . " {$ancho}w", $anchos)),
        'ancho' => (int) $imagen['ancho'],
        'alto' => (int) $imagen['alto'],
    ];
}

/** $prioritaria: la imagen se ve al abrir la página (se carga enseguida y antes que el resto). */
function htmlDeImagen(string $nombre, string $alt, string $raiz, string $clase = '', bool $prioritaria = false): string
{
    $datos = datosDeImagen($nombre, $raiz);
    if (!$datos) {
        return '';
    }
    return sprintf(
        '<img%s src="%s" srcset="%s" sizes="(min-width: 768px) 44rem, 100vw" width="%d" height="%d" alt="%s" %s decoding="async">',
        $clase ? ' class="' . e($clase) . '"' : '',
        e($datos['src']),
        e($datos['srcset']),
        $datos['ancho'],
        $datos['alto'],
        e($alt),
        $prioritaria ? 'fetchpriority="high"' : 'loading="lazy"'
    );
}

/**
 * Borra las imágenes que no usa ninguna entrada (portadas reemplazadas, fotos quitadas del texto,
 * entradas borradas o subidas que nunca se guardaron). Solo las de más de HORAS_GRACIA_IMAGENES,
 * para no borrar una que se acaba de subir y todavía no se guardó en la entrada.
 */
function limpiarImagenesSinUso(): void
{
    $usadas = [];
    foreach (bd()->query('SELECT imagen, cuerpo FROM entradas') as $entrada) {
        foreach ([$entrada['imagen'], ...imagenesDelTexto($entrada['cuerpo'])] as $nombre) {
            if ($nombre) {
                $usadas[$nombre] = true;
            }
        }
    }

    $viejas = bd()->query('SELECT id, nombre, ancho FROM imagenes WHERE creada_en < NOW() - INTERVAL '
        . HORAS_GRACIA_IMAGENES . ' HOUR');
    foreach ($viejas->fetchAll() as $imagen) {
        if (isset($usadas[$imagen['nombre']])) {
            continue;
        }
        foreach (anchosDeImagen((int) $imagen['ancho']) as $ancho) {
            @unlink(CARPETA_IMAGENES_BLOG . "/{$imagen['nombre']}-$ancho.webp");
        }
        bd()->prepare('DELETE FROM imagenes WHERE id = ?')->execute([$imagen['id']]);
    }
}
