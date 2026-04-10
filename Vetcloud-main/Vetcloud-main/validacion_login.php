<?php
session_start();
require 'conex.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conexion->prepare("SELECT id, username, password FROM usuarios WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($datos = $resultado->fetch_assoc()) {
        if (password_verify($password, $datos['password'])) {
            $_SESSION['user_id'] = $datos['id'];
            $_SESSION['username'] = $datos['username'];
            header("Location: menu.html");
            exit();
        }
        else {
            echo "<script>
                    alert('Contraseña incorrecta');
                    window.history.back();
                  </script>";
        }
    }
    else {
        echo "<script>
                alert('Usuario no encontrado');
                window.history.back();
              </script>";
    }
    $stmt->close();
    $conexion->close();

}