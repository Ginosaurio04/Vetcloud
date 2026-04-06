<?php
require 'conex.php';

$sql = "CREATE TABLE IF NOT EXISTS inventario (
    id_producto INT(11) AUTO_INCREMENT PRIMARY KEY,
    codigo_barras VARCHAR(100) NULL,
    nombre_producto VARCHAR(255) NOT NULL,
    tipo VARCHAR(100) NOT NULL,
    precio_venta DECIMAL(10, 2) NOT NULL,
    stock_actual INT(11) NOT NULL DEFAULT 0,
    stock_minimo INT(11) NOT NULL DEFAULT 0,
    fecha_vencimiento DATE NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conexion->query($sql) === TRUE) {
    echo "Tabla 'inventario' creada exitosamente.";
} else {
    echo "Error creando la tabla: " . $conexion->error;
}
$conexion->close();
?>
