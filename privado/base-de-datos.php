<?php
// Conexión a la base de datos (MariaDB/MySQL) con PDO.
// Siempre usar consultas preparadas (prepare + execute): nunca pegar datos dentro del SQL.
declare(strict_types=1);

function bd(): PDO
{
    static $conexion = null;
    if ($conexion === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            config('bd.host', 'localhost'),
            (int) config('bd.puerto', 3306),
            config('bd.nombre')
        );
        $conexion = new PDO($dsn, config('bd.usuario'), config('bd.clave'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $conexion;
}
