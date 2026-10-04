<?php
// Formato simple del cuerpo de las entradas. El panel guarda el texto tal como se escribe
// (nunca HTML) y acá se convierte en bloques: la misma estructura que lee js/blog.js.
//
// Marcas que se entienden (cada línea es un bloque; las líneas vacías se ignoran):
//   ## Subtítulo        (### y #### para subtítulos más chicos)
//   - punto de lista    (también "* ")
//   1. paso numerado    (también "1) ")
//   > cita destacada
//   ![descripción](nombre-de-la-imagen "epígrafe opcional")
// Dentro de una línea: **negrita**, *cursiva* y [texto del link](https://…).
declare(strict_types=1);

const PATRON_IMAGEN = '/^!\[(.*?)\]\(([a-z0-9-]+)(?:\s+"(.*)")?\)$/u';
// Los asteriscos pegados a letras o números no cuentan como marca ("3*5" no es cursiva)
const PATRON_EN_LINEA = '/(?<![\p{L}\p{N}])\*\*(?=\S)(.+?)(?<=\S)\*\*(?![\p{L}\p{N}*])'
    . '|(?<![\p{L}\p{N}*])\*(?=[^\s*])(.+?)(?<=[^\s*])\*(?![\p{L}\p{N}*])'
    . '|\[([^\]]+)\]\(([^)\s]+)\)/u';

/** Convierte el texto del cuerpo en una lista de bloques. */
function textoABloques(string $texto): array
{
    $bloques = [];
    foreach (preg_split('/\R/u', $texto) as $linea) {
        $linea = trim($linea);
        if ($linea === '') {
            continue;
        }

        if (preg_match(PATRON_IMAGEN, $linea, $partes)) {
            $bloques[] = [
                '_type' => 'image',
                'nombre' => $partes[2],
                'alt' => trim($partes[1]),
                'leyenda' => trim($partes[3] ?? ''),
            ];
            continue;
        }

        $extra = ['style' => 'normal'];
        if (preg_match('/^(#{1,4})\s+(.+)$/u', $linea, $partes)) {
            // Un solo "#" también es subtítulo: el título de la entrada es el único h1
            $extra['style'] = 'h' . max(2, strlen($partes[1]));
            $linea = $partes[2];
        } elseif (preg_match('/^>\s*(.+)$/u', $linea, $partes)) {
            $extra['style'] = 'blockquote';
            $linea = $partes[1];
        } elseif (preg_match('/^[-*]\s+(.+)$/u', $linea, $partes)) {
            $extra += ['listItem' => 'bullet', 'level' => 1];
            $linea = $partes[1];
        } elseif (preg_match('/^\d+[.)]\s+(.+)$/u', $linea, $partes)) {
            $extra += ['listItem' => 'number', 'level' => 1];
            $linea = $partes[1];
        }

        $enlaces = [];
        $hijos = partesEnLinea($linea, [], $enlaces);
        $bloques[] = ['_type' => 'block'] + $extra + ['markDefs' => $enlaces, 'children' => $hijos];
    }
    return $bloques;
}

/**
 * Separa una línea en tramos de texto con sus marcas (negrita, cursiva o link).
 * Es recursiva para permitir combinaciones, por ejemplo un link en negrita.
 */
function partesEnLinea(string $texto, array $marcas, array &$enlaces): array
{
    $partes = [];
    $agregarTexto = function (string $tramo) use (&$partes, $marcas): void {
        if ($tramo !== '') {
            $partes[] = ['_type' => 'span', 'text' => $tramo, 'marks' => $marcas];
        }
    };

    $posicion = 0;
    preg_match_all(PATRON_EN_LINEA, $texto, $coincidencias, PREG_SET_ORDER | PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL);
    foreach ($coincidencias as $coincidencia) {
        [$completo, $inicio] = $coincidencia[0];
        $agregarTexto(substr($texto, $posicion, $inicio - $posicion));
        $posicion = $inicio + strlen($completo);

        if ($coincidencia[1][0] !== null) {
            array_push($partes, ...partesEnLinea($coincidencia[1][0], [...$marcas, 'strong'], $enlaces));
        } elseif ($coincidencia[2][0] !== null) {
            array_push($partes, ...partesEnLinea($coincidencia[2][0], [...$marcas, 'em'], $enlaces));
        } elseif (esUrlSegura($coincidencia[4][0])) {
            $clave = 'enlace' . (count($enlaces) + 1);
            $enlaces[] = ['_key' => $clave, '_type' => 'link', 'href' => $coincidencia[4][0]];
            array_push($partes, ...partesEnLinea($coincidencia[3][0], [...$marcas, $clave], $enlaces));
        } else {
            $agregarTexto($completo); // link con una dirección no permitida: queda como texto
        }
    }
    $agregarTexto(substr($texto, $posicion));
    return $partes;
}

/** Solo se aceptan links web, de mail, de teléfono o a otra página del propio sitio ("/…"). */
function esUrlSegura(string $direccion): bool
{
    if (preg_match('#^/(?!/)#', $direccion)) {
        return true;
    }
    $esquema = strtolower((string) parse_url($direccion, PHP_URL_SCHEME));
    if (in_array($esquema, ['http', 'https'], true)) {
        return (string) parse_url($direccion, PHP_URL_HOST) !== '';
    }
    return in_array($esquema, ['mailto', 'tel'], true);
}

/** Nombres de las imágenes que aparecen dentro de un cuerpo. */
function imagenesDelTexto(string $texto): array
{
    $bloques = array_filter(textoABloques($texto), fn (array $bloque) => $bloque['_type'] === 'image');
    return array_values(array_unique(array_column($bloques, 'nombre')));
}

// ---------- Bloques → HTML (vista previa del panel) ----------
// Todo el texto pasa por e(): lo que se escribe en el panel nunca se interpreta como código.

function bloquesAHtml(array $bloques, string $raiz): string
{
    $html = '';
    $listaAbierta = null;

    foreach ($bloques as $bloque) {
        $tipoLista = isset($bloque['listItem']) ? ($bloque['listItem'] === 'number' ? 'ol' : 'ul') : null;
        if ($listaAbierta && $listaAbierta !== $tipoLista) {
            $html .= "</$listaAbierta>\n";
            $listaAbierta = null;
        }
        if ($tipoLista && !$listaAbierta) {
            $html .= "<$tipoLista>\n";
            $listaAbierta = $tipoLista;
        }

        if ($tipoLista) {
            $html .= '<li>' . partesAHtml($bloque) . "</li>\n";
        } elseif ($bloque['_type'] === 'image') {
            $html .= figuraAHtml($bloque, $raiz) . "\n";
        } else {
            $etiqueta = ['h2' => 'h2', 'h3' => 'h3', 'h4' => 'h4', 'blockquote' => 'blockquote'][$bloque['style']] ?? 'p';
            $html .= "<$etiqueta>" . partesAHtml($bloque) . "</$etiqueta>\n";
        }
    }
    return $html . ($listaAbierta ? "</$listaAbierta>\n" : '');
}

function partesAHtml(array $bloque): string
{
    $enlaces = array_column($bloque['markDefs'], 'href', '_key');
    $html = '';
    foreach ($bloque['children'] as $parte) {
        $tramo = e($parte['text']);
        foreach ($parte['marks'] as $marca) {
            if ($marca === 'strong' || $marca === 'em') {
                $tramo = "<$marca>$tramo</$marca>";
            } elseif (isset($enlaces[$marca]) && preg_match('#^https?://#i', $enlaces[$marca])) {
                $tramo = '<a href="' . e($enlaces[$marca]) . '" target="_blank" rel="noopener noreferrer">'
                    . $tramo . '<span class="oculto-accesible"> (se abre en una pestaña nueva)</span></a>';
            } elseif (isset($enlaces[$marca])) {
                $tramo = '<a href="' . e($enlaces[$marca]) . '">' . $tramo . '</a>';
            }
        }
        $html .= $tramo;
    }
    return $html;
}

function figuraAHtml(array $bloque, string $raiz): string
{
    $imagen = htmlDeImagen($bloque['nombre'], $bloque['alt'], $raiz);
    if ($imagen === '') {
        return '<p class="aviso">No encontramos la imagen "' . e($bloque['nombre']) . '". Volvé a subirla con el botón "Imagen".</p>';
    }
    $leyenda = $bloque['leyenda'] !== '' ? '<figcaption>' . e($bloque['leyenda']) . '</figcaption>' : '';
    return "<figure>$imagen$leyenda</figure>";
}
