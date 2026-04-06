<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require "C:/Users/josea/OneDrive/Documentos/GitHub/Vetcloud/Vetcloud-main/Vetcloud-main/conex.php";

echo "Conexi\u00f3n exitosa a " . $db . "\n";

$res = $conexion->query("DESCRIBE citas");
if ($res) {
    while($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error DESCRIBE: " . $conexion->error . "\n";
}

// Intentar un insert de prueba vacío para ver el error exacto
$stmt = $conexion->prepare("INSERT INTO citas (id_mascota, fecha_cita, tipo_servicio, estado, observaciones) VALUES (1, '2026-10-10 10:00:00', 'Consulta Médica', 'En Espera', 'Prueba')");
if ($stmt) {
    if (!$stmt->execute()) {
        echo "Error INSERT: " . $stmt->error . "\n";
    } else {
        echo "Insertado correctamente. id_cita: " . $stmt->insert_id . "\n";
        $conexion->query("DELETE FROM citas WHERE id_cita = " . $stmt->insert_id);
    }
} else {
    echo "Error PREPARE: " . $conexion->error . "\n";
}
?>
