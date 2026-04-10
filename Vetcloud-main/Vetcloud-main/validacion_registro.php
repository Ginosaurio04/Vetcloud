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
        echo "<script>
                alert('Registro Exitoso. Ahora puede iniciar sesión.');
                window.location.href = 'login.html';
              </script>";
    }
    else {
        echo "<script>
                alert('Error: El usuario o email ya están registrados');
                window.history.back();
              </script>";
    }

    $stmt->close();


}
$conexion->close();
?>