<?php
require 'conex.php';

$sqlFacturas = "CREATE TABLE IF NOT EXISTS facturas (
    id_factura INT(11) AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT(11) NOT NULL,
    metodo_pago VARCHAR(50) NOT NULL,
    total_pagar DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    fecha_emision TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$sqlDetalles = "CREATE TABLE IF NOT EXISTS detalle_factura (
    id_detalle INT(11) AUTO_INCREMENT PRIMARY KEY,
    id_factura INT(11) NOT NULL,
    id_producto INT(11) NULL,
    descripcion_servicio VARCHAR(255) NULL,
    cantidad INT(11) NOT NULL,
    precio_unitario DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (id_factura) REFERENCES facturas(id_factura) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conexion->query($sqlFacturas) === TRUE) {
    echo "Tabla 'facturas' creada exitosamente.\n";
} else {
    echo "Error creando la tabla facturas: " . $conexion->error . "\n";
}

if ($conexion->query($sqlDetalles) === TRUE) {
    echo "Tabla 'detalle_factura' creada exitosamente.\n";
} else {
    echo "Error creando la tabla detalle_factura: " . $conexion->error . "\n";
}

$conexion->close();
?>
