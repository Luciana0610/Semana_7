<?php
// Configuración con soporte para Docker (variables de entorno) y XAMPP local (valores por defecto)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASSWORD') ?: '');          // En XAMPP por defecto vacío
define('DB_NAME', getenv('DB_NAME') ?: 'agenda_citas');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("<div style='color:red;padding:20px;font-family:sans-serif;'>
        ❌ Error de conexión: " . $conn->connect_error . "
        <br><br>¿Tienes MySQL activo en XAMPP y la base de datos creada?
        <br>Ejecuta primero el archivo <b>database.sql</b> en phpMyAdmin.
        <br>Si usas Docker, verifica que el servicio <b>db</b> esté disponible y healthy.
    </div>");
}

$conn->set_charset("utf8");
