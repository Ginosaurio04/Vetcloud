<?php
require 'C:/Users/josea/OneDrive/Documentos/GitHub/Vetcloud/Vetcloud-main/Vetcloud-main/conex.php';

$id_mascota = 1;
$fecha_cita = '2026-10-10T10:00';
$tipo_servicio = 'Consulta Médica';
$observaciones = 'Prueba html5 date format';

$stmt = $conexion->prepare("INSERT INTO citas (id_mascota, fecha_cita, tipo_servicio, estado, observaciones) VALUES (?, ?, ?, 'En Espera', ?)");
if (!$stmt) {
    echo "Prepare failed: " . $conexion->error . "\n";
    exit;
}

$stmt->bind_param('isss', $id_mascota, $fecha_cita, $tipo_servicio, $observaciones);

if (!$stmt->execute()) {
    echo "Execute error: " . $stmt->error . "\n";
} else {
    echo "Success! ID: " . $stmt->insert_id . "\n";
    $conexion->query("DELETE FROM citas WHERE id_cita = " . $stmt->insert_id);
}
?>
