<?php
session_start();
require 'conex.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "No hay sesión activa"]);
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conexion->prepare("SELECT id, username, email FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($datos = $resultado->fetch_assoc()) {
    echo json_encode([
        "status" => "success",
        "user" => [
            "id" => $datos['id'],
            "username" => $datos['username'],
            "email" => $datos['email'] ?? 'No registrado'
        ]
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Usuario no encontrado"]);
}

$stmt->close();
$conexion->close();
?>
