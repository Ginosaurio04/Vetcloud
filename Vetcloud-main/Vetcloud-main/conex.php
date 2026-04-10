<?php
date_default_timezone_set('America/Caracas');
$host = "localhost";
$user = "root";
$pass = "";
$db = "veterinaria_unimar";

$conexion = new mysqli($host, $user, $pass, $db);

if ($conexion->connect_error) {
    die("Error crítico de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");
$conexion->query("SET time_zone = '-04:00'");
?>