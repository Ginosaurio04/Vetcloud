<?php
require 'conex.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $passwordhash = password_hash($password, PASSWORD_BCRYPT);


    $stmt = $conexion->prepare("INSERT INTO usuarios (username, email, password) VALUES (?,?,?)");
    $stmt->bind_param("sss", $username, $email, $passwordhash);

    if ($stmt->execute()) {
        echo "<h3>Registro Exitoso</h3> <a href='login.html'>Ir al Login</a>";
    }
    else {
        echo "Error: El usuario o email ya están registrados";
    }

    $stmt->close();


}
$conexion->close();
?>