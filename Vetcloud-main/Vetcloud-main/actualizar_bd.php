<?php
require 'conex.php';

// Script de migración para actualizar la estructura de la base de datos
// Esto añade la categoría 'Higiene' sin borrar los datos existentes.

$sql = "ALTER TABLE inventario MODIFY COLUMN tipo ENUM('Medicamento', 'Vacuna', 'Accesorio', 'Alimento', 'Higiene') NOT NULL";

if ($conexion->query($sql) === TRUE) {
    echo "<h2 style='color: green;'>✅ Base de datos actualizada con éxito.</h2>";
    echo "<p>La categoría 'Higiene' ya está disponible en el sistema.</p>";
    echo "<a href='inventario.html'>Volver al Inventario</a>";
} else {
    echo "<h2 style='color: red;'>❌ Error al actualizar:</h2> " . $conexion->error;
}

$conexion->close();
?>
