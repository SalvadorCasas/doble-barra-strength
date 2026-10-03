<?php
// Herramienta de consola para administrar las cuentas del panel (no hay registro público).
// Se ejecuta en la terminal (en la PC o en cPanel → "Terminal"):
//
//   php privado/usuarios.php crear        crea una cuenta
//   php privado/usuarios.php clave        cambia la contraseña de una cuenta
//   php privado/usuarios.php desactivar   bloquea una cuenta (y cierra su sesión)
//   php privado/usuarios.php activar      vuelve a habilitar una cuenta
//   php privado/usuarios.php listar       muestra todas las cuentas
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/arranque.php';

const LARGO_MINIMO_CLAVE = 12;

function preguntar(string $texto): string
{
    echo $texto;
    $respuesta = fgets(STDIN);
    return $respuesta === false ? '' : trim($respuesta);
}

function salirConError(string $mensaje): never
{
    fwrite(STDERR, 'Error: ' . $mensaje . PHP_EOL);
    exit(1);
}

/** Contraseña al azar de 16 caracteres, sin letras que se confundan (l, I, O, 0). */
function generarClave(): string
{
    $caracteres = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789-_.';
    $clave = '';
    for ($i = 0; $i < 16; $i++) {
        $clave .= $caracteres[random_int(0, strlen($caracteres) - 1)];
    }
    return $clave;
}

/** Pide una contraseña; si se deja vacía, genera una. Devuelve [clave, fueGenerada]. */
function pedirClave(string $email): array
{
    $clave = preguntar('Contraseña (Enter para generar una segura): ');
    if ($clave === '') {
        return [generarClave(), true];
    }
    if (mb_strlen($clave) < LARGO_MINIMO_CLAVE) {
        salirConError('la contraseña tiene que tener al menos ' . LARGO_MINIMO_CLAVE . ' caracteres.');
    }
    if (mb_strtolower($clave) === $email) {
        salirConError('la contraseña no puede ser igual al mail.');
    }
    return [$clave, false];
}

function pedirEmailExistente(): array
{
    $email = mb_strtolower(preguntar('Mail de la cuenta: '));
    $consulta = bd()->prepare('SELECT id, nombre, email, activo FROM usuarios WHERE email = ?');
    $consulta->execute([$email]);
    return $consulta->fetch() ?: salirConError("no existe una cuenta con el mail $email.");
}

function mostrarClave(string $clave, bool $fueGenerada): void
{
    if ($fueGenerada) {
        echo PHP_EOL . "Contraseña generada: $clave" . PHP_EOL
            . 'Pasala por un medio seguro y guardala en un gestor de contraseñas: no se vuelve a mostrar.' . PHP_EOL;
    }
}

$accion = $argv[1] ?? '';

switch ($accion) {
    case 'crear':
        $nombre = preguntar('Nombre (ej.: Ivan Casas): ');
        $email = mb_strtolower(preguntar('Mail: '));
        if ($nombre === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            salirConError('hace falta un nombre y un mail válido.');
        }
        $existe = bd()->prepare('SELECT 1 FROM usuarios WHERE email = ?');
        $existe->execute([$email]);
        if ($existe->fetchColumn()) {
            salirConError("ya existe una cuenta con el mail $email.");
        }
        [$clave, $fueGenerada] = pedirClave($email);
        bd()->prepare('INSERT INTO usuarios (nombre, email, clave_hash) VALUES (?, ?, ?)')
            ->execute([$nombre, $email, password_hash($clave, PASSWORD_DEFAULT)]);
        echo "Cuenta creada para $nombre ($email)." . PHP_EOL;
        mostrarClave($clave, $fueGenerada);
        break;

    case 'clave':
        $usuario = pedirEmailExistente();
        [$clave, $fueGenerada] = pedirClave($usuario['email']);
        bd()->prepare('UPDATE usuarios SET clave_hash = ? WHERE id = ?')
            ->execute([password_hash($clave, PASSWORD_DEFAULT), $usuario['id']]);
        echo "Contraseña actualizada para {$usuario['nombre']}." . PHP_EOL;
        mostrarClave($clave, $fueGenerada);
        break;

    case 'desactivar':
    case 'activar':
        $usuario = pedirEmailExistente();
        bd()->prepare('UPDATE usuarios SET activo = ? WHERE id = ?')
            ->execute([$accion === 'activar' ? 1 : 0, $usuario['id']]);
        echo 'Cuenta de ' . $usuario['nombre'] . ($accion === 'activar' ? ' activada.' : ' desactivada.') . PHP_EOL;
        break;

    case 'listar':
        $cuentas = bd()->query('SELECT id, nombre, email, activo, ultimo_ingreso FROM usuarios ORDER BY id')->fetchAll();
        if (!$cuentas) {
            echo 'Todavía no hay cuentas. Creá una con: php privado/usuarios.php crear' . PHP_EOL;
        }
        foreach ($cuentas as $cuenta) {
            printf("%d. %s <%s> | %s | último ingreso: %s%s",
                $cuenta['id'], $cuenta['nombre'], $cuenta['email'],
                $cuenta['activo'] ? 'activa' : 'DESACTIVADA', $cuenta['ultimo_ingreso'] ?? 'nunca', PHP_EOL);
        }
        break;

    default:
        echo 'Uso: php privado/usuarios.php crear | clave | desactivar | activar | listar' . PHP_EOL;
        exit($accion === '' ? 0 : 1);
}
