<?php
require 'C:/Users/josea/OneDrive/Documentos/GitHub/Vetcloud/Vetcloud-main/Vetcloud-main/conex.php';

$res = $conexion->query("SHOW TABLES");
echo "Tablas:\n";
while($row = $res->fetch_array()) {
    echo $row[0] . "\n";
}
?>
