<?php
// Entradas del blog: lectura, validación, guardado y borrado desde el panel.
// Un borrador solo necesita título; para publicar también hacen falta el resumen y el texto.
declare(strict_types=1);

require __DIR__ . '/formato.php';
require __DIR__ . '/imagenes.php';

const LARGO_TITULO = 150;
const LARGO_SLUG = 100;
const LARGO_RESUMEN = 200;
const LARGO_ALT = 250;
const ESTADOS_ENTRADA = ['borrador' => 'Borrador', 'publicada' => 'Publicada'];

function listarEntradas(): array
{
    return bd()->query('SELECT e.id, e.titulo, e.slug, e.fecha, e.estado, u.nombre AS autor
        FROM entradas e JOIN usuarios u ON u.id = e.autor_id
        ORDER BY e.fecha DESC, e.id DESC')->fetchAll();
}

function buscarEntrada(int $id): ?array
{
    $consulta = bd()->prepare('SELECT e.*, u.nombre AS autor FROM entradas e JOIN usuarios u ON u.id = e.autor_id WHERE e.id = ?');
    $consulta->execute([$id]);
    $entrada = $consulta->fetch() ?: null;
    if ($entrada) {
        $entrada['id'] = (int) $entrada['id'];
    }
    return $entrada;
}

function entradaNueva(array $usuario): array
{
    return [
        'id' => null, 'titulo' => '', 'slug' => '', 'fecha' => date('Y-m-d'), 'resumen' => '', 'cuerpo' => '',
        'imagen' => null, 'imagen_alt' => '', 'estado' => 'borrador', 'autor' => $usuario['nombre'],
    ];
}

/** Pasa los campos del formulario a la entrada (la imagen principal se procesa aparte). */
function entradaDelFormulario(array $entrada): array
{
    $texto = fn (string $campo): string => trim((string) ($_POST[$campo] ?? ''));
    return [
        'titulo' => $texto('titulo'),
        'slug' => mb_strtolower($texto('slug')),
        'fecha' => $texto('fecha'),
        'resumen' => (string) preg_replace('/\s+/u', ' ', $texto('resumen')),
        'cuerpo' => str_replace("\r\n", "\n", $texto('cuerpo')),
        'imagen_alt' => $texto('imagen_alt'),
        'estado' => isset(ESTADOS_ENTRADA[$texto('estado')]) ? $texto('estado') : 'borrador',
    ] + $entrada;
}

/**
 * Imagen principal según el formulario: la que ya tenía, ninguna (si se marcó "Quitar") o una nueva.
 * La nueva se guarda aunque haya otros errores, así no hay que volver a elegirla.
 */
function imagenDelFormulario(array $entrada, int $usuarioId, array &$errores): ?string
{
    $actual = (string) ($_POST['imagen_actual'] ?? '');
    $actual = $actual !== '' && datosDeImagen($actual, '') ? $actual : null;
    if (!empty($_POST['quitar_imagen'])) {
        $actual = null;
    }
    if (($_FILES['imagen']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        try {
            $actual = guardarImagenSubida($_FILES['imagen'], $entrada['imagen_alt'] ?: $entrada['titulo'], $usuarioId);
        } catch (ImagenInvalida $error) {
            $errores['imagen'] = $error->getMessage();
        }
    }
    return $actual;
}

/** Devuelve los errores de cada campo: ['titulo' => '…', …]. */
function validarEntrada(array $entrada): array
{
    $errores = [];
    $publicar = $entrada['estado'] === 'publicada';

    if ($entrada['titulo'] === '') {
        $errores['titulo'] = 'Escribí el título.';
    } elseif (mb_strlen($entrada['titulo']) > LARGO_TITULO) {
        $errores['titulo'] = 'El título puede tener hasta ' . LARGO_TITULO . ' caracteres.';
    }

    if ($entrada['slug'] !== '') {
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $entrada['slug']) || strlen($entrada['slug']) > LARGO_SLUG) {
            $errores['slug'] = 'La dirección solo puede tener letras sin tilde, números y guiones, por ejemplo: como-armar-tu-bloque.';
        } elseif (!slugLibre($entrada['slug'], $entrada['id'])) {
            $errores['slug'] = 'Ya hay otra entrada con esta dirección. Elegí otra o dejala vacía para que se arme sola.';
        }
    }

    $fecha = DateTime::createFromFormat('!Y-m-d', $entrada['fecha']);
    if (!$fecha || $fecha->format('Y-m-d') !== $entrada['fecha']) {
        $errores['fecha'] = 'Ingresá una fecha válida.';
    }

    if ($publicar && $entrada['resumen'] === '') {
        $errores['resumen'] = 'Escribí el resumen para poder publicar: se muestra en las tarjetas del blog y en Google.';
    } elseif (mb_strlen($entrada['resumen']) > LARGO_RESUMEN) {
        $errores['resumen'] = 'El resumen puede tener hasta ' . LARGO_RESUMEN . ' caracteres.';
    }

    if ($publicar && $entrada['cuerpo'] === '') {
        $errores['cuerpo'] = 'Escribí el texto de la entrada para poder publicarla.';
    } else {
        foreach (textoABloques($entrada['cuerpo']) as $bloque) {
            if ($bloque['_type'] !== 'image') {
                continue;
            }
            if ($bloque['alt'] === '') {
                $errores['cuerpo'] = 'Cada imagen del texto necesita una descripción entre los corchetes, por ejemplo: '
                    . '![Ivan haciendo sentadilla](' . $bloque['nombre'] . ').';
                break;
            }
            if (!datosDeImagen($bloque['nombre'], '')) {
                $errores['cuerpo'] = 'No encontramos la imagen "' . $bloque['nombre'] . '" del texto. Borrá esa línea y volvé a subirla con el botón "Imagen".';
                break;
            }
        }
    }

    if ($entrada['imagen'] && $entrada['imagen_alt'] === '') {
        $errores['imagen_alt'] = 'Describí la imagen principal para quienes no pueden verla.';
    } elseif (mb_strlen($entrada['imagen_alt']) > LARGO_ALT) {
        $errores['imagen_alt'] = 'La descripción puede tener hasta ' . LARGO_ALT . ' caracteres.';
    }

    return $errores;
}

/** Guarda la entrada (nueva o existente) y devuelve su id. */
function guardarEntrada(array $entrada, int $autorId): int
{
    $slug = $entrada['slug'] ?: slugUnico(crearSlug($entrada['titulo'], LARGO_SLUG) ?: 'entrada', $entrada['id']);
    $valores = [
        $entrada['titulo'], $slug, $entrada['fecha'], $entrada['resumen'], $entrada['cuerpo'],
        $entrada['imagen'], $entrada['imagen'] ? $entrada['imagen_alt'] : '', $entrada['estado'],
    ];

    if ($entrada['id']) {
        bd()->prepare('UPDATE entradas SET titulo = ?, slug = ?, fecha = ?, resumen = ?, cuerpo = ?,
            imagen = ?, imagen_alt = ?, estado = ? WHERE id = ?')->execute([...$valores, $entrada['id']]);
        $id = (int) $entrada['id'];
    } else {
        bd()->prepare('INSERT INTO entradas (titulo, slug, fecha, resumen, cuerpo, imagen, imagen_alt, estado, autor_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute([...$valores, $autorId]);
        $id = (int) bd()->lastInsertId();
    }

    limpiarImagenesSinUso();
    return $id;
}

function borrarEntrada(int $id): void
{
    bd()->prepare('DELETE FROM entradas WHERE id = ?')->execute([$id]);
    limpiarImagenesSinUso();
}

function slugLibre(string $slug, ?int $id): bool
{
    $consulta = bd()->prepare('SELECT 1 FROM entradas WHERE slug = ? AND id <> ?');
    $consulta->execute([$slug, (int) $id]);
    return !$consulta->fetchColumn();
}

/** Si la dirección ya la usa otra entrada, le agrega un número: "mi-entrada-2", "mi-entrada-3"… */
function slugUnico(string $base, ?int $id): string
{
    $slug = $base;
    for ($numero = 2; !slugLibre($slug, $id); $numero++) {
        $sufijo = "-$numero";
        $slug = rtrim(substr($base, 0, LARGO_SLUG - strlen($sufijo)), '-') . $sufijo;
    }
    return $slug;
}

// ---------- Lectura pública (blog del sitio) ----------
// Solo se leen entradas publicadas: los borradores nunca salen del panel.

/** Últimas entradas publicadas (todas si $cantidad es 0), con los datos de sus tarjetas. */
function entradasPublicadas(int $cantidad = 0): array
{
    $sql = "SELECT e.titulo, e.slug, e.fecha, e.resumen, e.imagen, e.imagen_alt, u.nombre AS autor
        FROM entradas e JOIN usuarios u ON u.id = e.autor_id
        WHERE e.estado = 'publicada' ORDER BY e.fecha DESC, e.id DESC";
    if ($cantidad > 0) {
        $sql .= ' LIMIT ' . $cantidad;
    }
    return bd()->query($sql)->fetchAll();
}

function entradaPublicada(string $slug): ?array
{
    $consulta = bd()->prepare("SELECT e.*, u.nombre AS autor FROM entradas e JOIN usuarios u ON u.id = e.autor_id
        WHERE e.slug = ? AND e.estado = 'publicada'");
    $consulta->execute([$slug]);
    return $consulta->fetch() ?: null;
}
