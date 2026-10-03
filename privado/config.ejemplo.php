<?php
// Plantilla de configuración (es el equivalente a un ".env.example": nombres sin valores reales).
// - En la PC: copiarla como privado/config.php.
// - En el hosting: copiarla como doblebarra-config.php en la carpeta de la cuenta, AL LADO de
//   public_html (no adentro), así queda fuera del alcance de la web.
// El archivo con los datos reales nunca se sube al repositorio: tiene contraseñas.

return [
    // 'desarrollo' muestra los errores en pantalla (solo en la PC). En el hosting: 'produccion'.
    'entorno' => 'produccion',

    // Base de datos MariaDB/MySQL. En cPanel: "Bases de datos MySQL" (el nombre y el usuario
    // llevan adelante el usuario de la cuenta, por ejemplo "cuenta_doblebarra").
    'bd' => [
        'host' => 'localhost',
        'puerto' => 3306,
        'nombre' => '',
        'usuario' => '',
        'clave' => '',
    ],

    // La sesión se cierra sola tras estos minutos sin actividad, y siempre después de estas horas
    'sesion' => [
        'minutos_inactividad' => 60,
        'horas_maximas' => 8,
    ],
];
