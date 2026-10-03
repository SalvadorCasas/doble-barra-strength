<?php
// Sesión, ingreso y cierre de sesión del panel, más protección contra ataques comunes:
// - Contraseñas guardadas con password_hash (nunca en texto plano).
// - Cookie de sesión inaccesible para JavaScript, solo por HTTPS en el hosting y SameSite=Strict.
// - Nuevo identificador de sesión al ingresar (evita que alguien "fije" una sesión ajena).
// - Token CSRF en cada formulario (evita que otro sitio envíe formularios en nombre del usuario).
// - Bloqueo temporal tras varios intentos fallidos (frena a quien prueba contraseñas al azar).
declare(strict_types=1);

const SESION_NOMBRE = 'dbs_panel';
const MAX_FALLOS_POR_MAIL = 5;
const MAX_FALLOS_POR_IP = 20;
const MINUTOS_BLOQUEO = 15;

// Hash de una contraseña al azar: si el mail no existe, se verifica igual contra este valor
// para que la respuesta tarde lo mismo y no delate qué mails tienen cuenta.
const HASH_DE_RELLENO = '$2y$10$2XVmc4slcQrX1cDWrPRso.nbWIfqf4Q0uFG763O9wVwXNgabvWB5S';

function iniciarSesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name(SESION_NOMBRE);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => esHttps(),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// ---------- Protección CSRF ----------

function tokenCsrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Campo oculto con el token, para pegar dentro de cada <form method="post">. */
function campoCsrf(): string
{
    return '<input type="hidden" name="csrf" value="' . e(tokenCsrf()) . '">';
}

function csrfValido(): bool
{
    $enviado = (string) ($_POST['csrf'] ?? '');
    return $enviado !== '' && hash_equals($_SESSION['csrf'] ?? '', $enviado);
}

// ---------- Ingreso ----------

/**
 * Intenta iniciar sesión. Devuelve null si entró, o el mensaje de error para mostrar.
 * El mensaje es el mismo si falla el mail o la contraseña, para no dar pistas.
 */
function intentarIngreso(string $email, string $clave): ?string
{
    $email = mb_strtolower(trim($email));
    $ip = ipCliente();

    if (estaBloqueado($email, $ip)) {
        return 'Hubo demasiados intentos fallidos. Esperá ' . MINUTOS_BLOQUEO . ' minutos y volvé a intentarlo.';
    }

    $consulta = bd()->prepare('SELECT id, clave_hash, activo FROM usuarios WHERE email = ?');
    $consulta->execute([$email]);
    $usuario = $consulta->fetch() ?: null;

    $claveCorrecta = password_verify($clave, $usuario['clave_hash'] ?? HASH_DE_RELLENO);
    if (!$usuario || !$claveCorrecta || !(int) $usuario['activo']) {
        registrarIntento($email, $ip, false);
        return 'El mail o la contraseña no son correctos.';
    }

    registrarIntento($email, $ip, true);
    if (password_needs_rehash($usuario['clave_hash'], PASSWORD_DEFAULT)) {
        bd()->prepare('UPDATE usuarios SET clave_hash = ? WHERE id = ?')
            ->execute([password_hash($clave, PASSWORD_DEFAULT), $usuario['id']]);
    }
    bd()->prepare('UPDATE usuarios SET ultimo_ingreso = NOW() WHERE id = ?')->execute([$usuario['id']]);

    session_regenerate_id(true);
    $_SESSION = [
        'usuario_id' => (int) $usuario['id'],
        'inicio' => time(),
        'ultima_actividad' => time(),
        'csrf' => bin2hex(random_bytes(32)),
    ];
    return null;
}

function estaBloqueado(string $email, string $ip): bool
{
    $desde = 'NOW() - INTERVAL ' . MINUTOS_BLOQUEO . ' MINUTE';
    $porMail = bd()->prepare("SELECT COUNT(*) FROM intentos_ingreso WHERE exitoso = 0 AND email = ? AND momento > $desde");
    $porMail->execute([$email]);
    $porIp = bd()->prepare("SELECT COUNT(*) FROM intentos_ingreso WHERE exitoso = 0 AND ip = ? AND momento > $desde");
    $porIp->execute([$ip]);
    return (int) $porMail->fetchColumn() >= MAX_FALLOS_POR_MAIL || (int) $porIp->fetchColumn() >= MAX_FALLOS_POR_IP;
}

function registrarIntento(string $email, string $ip, bool $exitoso): void
{
    bd()->prepare('INSERT INTO intentos_ingreso (email, ip, exitoso) VALUES (?, ?, ?)')
        ->execute([mb_substr($email, 0, 190), $ip, (int) $exitoso]);
    if ($exitoso) {
        // Al entrar bien se olvidan los fallos previos de ese mail
        bd()->prepare('DELETE FROM intentos_ingreso WHERE email = ? AND exitoso = 0')->execute([$email]);
    }
    // Limpieza: no hace falta guardar intentos de más de un día
    bd()->exec('DELETE FROM intentos_ingreso WHERE momento < NOW() - INTERVAL 1 DAY');
}

// ---------- Sesión activa ----------

/**
 * Devuelve el usuario con sesión activa (id, nombre, email) o null.
 * Si la sesión venció por inactividad o por tiempo máximo, la cierra y marca $vencida = true.
 */
function usuarioActual(bool &$vencida = false): ?array
{
    static $usuario = null;
    if ($usuario !== null) {
        return $usuario;
    }
    if (empty($_SESSION['usuario_id'])) {
        return null;
    }

    $ahora = time();
    $inactivo = $ahora - (int) ($_SESSION['ultima_actividad'] ?? 0) > (int) config('sesion.minutos_inactividad', 60) * 60;
    $excedido = $ahora - (int) ($_SESSION['inicio'] ?? 0) > (int) config('sesion.horas_maximas', 8) * 3600;
    if ($inactivo || $excedido) {
        cerrarSesion();
        $vencida = true;
        return null;
    }

    // Se vuelve a consultar la base en cada página: si se desactiva una cuenta, sale enseguida
    $consulta = bd()->prepare('SELECT id, nombre, email FROM usuarios WHERE id = ? AND activo = 1');
    $consulta->execute([$_SESSION['usuario_id']]);
    $usuario = $consulta->fetch() ?: null;
    if ($usuario === null) {
        cerrarSesion();
        return null;
    }

    $_SESSION['ultima_actividad'] = $ahora;
    return $usuario;
}

/** Para páginas protegidas: si no hay sesión activa, manda a la pantalla de ingreso. */
function requerirUsuario(): array
{
    $vencida = false;
    $usuario = usuarioActual($vencida);
    if ($usuario === null) {
        redirigir($vencida ? 'ingresar.php?motivo=vencida' : 'ingresar.php');
    }
    return $usuario;
}

function cerrarSesion(): void
{
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        $parametros = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $parametros['path'],
            'secure' => $parametros['secure'],
            'httponly' => $parametros['httponly'],
            'samesite' => $parametros['samesite'],
        ]);
        session_destroy();
    }
}
