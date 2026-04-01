<?php
session_start();
require 'conex.php';

// Determinar la acción solicitada
$accion = isset($_POST['accion']) ? $_POST['accion'] : (isset($_GET['accion']) ? $_GET['accion'] : '');

switch ($accion) {

    // =============================================
    // CREAR NUEVA CITA
    // =============================================
    case 'crear':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $id_mascota = intval($_POST['id_mascota']);
            $fecha_cita = $_POST['fecha_cita'];
            $tipo_servicio = $_POST['tipo_servicio'];
            $nro_box = !empty($_POST['nro_box']) ? intval($_POST['nro_box']) : null;
            $observaciones = trim($_POST['observaciones']);

            $stmt = $conexion->prepare("INSERT INTO citas (id_mascota, fecha_cita, tipo_servicio, nro_box, estado, observaciones) VALUES (?, ?, ?, ?, 'En Espera', ?)");
            $stmt->bind_param("issis", $id_mascota, $fecha_cita, $tipo_servicio, $nro_box, $observaciones);

            if ($stmt->execute()) {
                header("Location: agenda.html?msg=cita_creada");
                exit();
            } else {
                echo "Error al crear la cita: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    // =============================================
    // LISTAR CITAS (por fecha o todas)
    // =============================================
    case 'listar':
        if (isset($_GET['start']) && isset($_GET['end'])) {
            $start = $_GET['start'];
            $end = $_GET['end'];
            $stmt = $conexion->prepare("
                SELECT c.id_cita, c.fecha_cita, c.tipo_servicio, c.nro_box, c.estado, c.observaciones,
                       m.nombre_animal, m.especie, m.raza,
                       cl.nombre_completo AS dueno, cl.telefono
                FROM citas c
                INNER JOIN mascotas m ON c.id_mascota = m.id_mascota
                INNER JOIN clientes cl ON m.id_cliente = cl.id_cliente
                WHERE c.fecha_cita >= ? AND c.fecha_cita < ?
                ORDER BY c.fecha_cita ASC
            ");
            $stmt->bind_param("ss", $start, $end);
        } else {
            $fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
            $stmt = $conexion->prepare("
                SELECT c.id_cita, c.fecha_cita, c.tipo_servicio, c.nro_box, c.estado, c.observaciones,
                       m.nombre_animal, m.especie, m.raza,
                       cl.nombre_completo AS dueno, cl.telefono
                FROM citas c
                INNER JOIN mascotas m ON c.id_mascota = m.id_mascota
                INNER JOIN clientes cl ON m.id_cliente = cl.id_cliente
                WHERE DATE(c.fecha_cita) = ?
                ORDER BY c.fecha_cita ASC
            ");
            $stmt->bind_param("s", $fecha);
        }
        $stmt->execute();
        $resultado = $stmt->get_result();

        $citas = [];
        while ($fila = $resultado->fetch_assoc()) {
            $citas[] = $fila;
        }

        header('Content-Type: application/json');
        echo json_encode($citas);
        $stmt->close();
        break;

    // =============================================
    // ACTUALIZAR ESTADO DE UNA CITA
    // =============================================
    case 'actualizar_estado':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $id_cita = intval($_POST['id_cita']);
            $estado = $_POST['estado'];

            $estados_validos = ['En Espera', 'En Consulta', 'En Peluquería', 'Finalizado', 'Cancelado'];
            if (!in_array($estado, $estados_validos)) {
                echo "Estado no válido.";
                break;
            }

            $stmt = $conexion->prepare("UPDATE citas SET estado = ? WHERE id_cita = ?");
            $stmt->bind_param("si", $estado, $id_cita);

            if ($stmt->execute()) {
                header("Location: agenda.html?msg=estado_actualizado");
                exit();
            } else {
                echo "Error al actualizar: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    // =============================================
    // EDITAR CITA
    // =============================================
    case 'editar':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $id_cita = intval($_POST['id_cita']);
            $fecha_cita = $_POST['fecha_cita'];
            $tipo_servicio = $_POST['tipo_servicio'];
            $nro_box = !empty($_POST['nro_box']) ? intval($_POST['nro_box']) : null;
            $observaciones = trim($_POST['observaciones']);

            $stmt = $conexion->prepare("UPDATE citas SET fecha_cita = ?, tipo_servicio = ?, nro_box = ?, observaciones = ? WHERE id_cita = ?");
            $stmt->bind_param("ssisi", $fecha_cita, $tipo_servicio, $nro_box, $observaciones, $id_cita);

            if ($stmt->execute()) {
                header("Location: agenda.html?msg=cita_editada");
                exit();
            } else {
                echo "Error al editar: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    // =============================================
    // ELIMINAR / CANCELAR CITA
    // =============================================
    case 'eliminar':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $id_cita = intval($_POST['id_cita']);

            $stmt = $conexion->prepare("DELETE FROM citas WHERE id_cita = ?");
            $stmt->bind_param("i", $id_cita);

            if ($stmt->execute()) {
                header("Location: agenda.html?msg=cita_eliminada");
                exit();
            } else {
                echo "Error al eliminar: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    // =============================================
    // OBTENER MASCOTAS (para el formulario de nueva cita)
    // =============================================
    case 'obtener_mascotas':
        $resultado = $conexion->query("
            SELECT m.id_mascota, m.nombre_animal, m.especie, m.raza,
                   cl.nombre_completo AS dueno
            FROM mascotas m
            INNER JOIN clientes cl ON m.id_cliente = cl.id_cliente
            ORDER BY m.nombre_animal ASC
        ");

        $mascotas = [];
        while ($fila = $resultado->fetch_assoc()) {
            $mascotas[] = $fila;
        }

        header('Content-Type: application/json');
        echo json_encode($mascotas);
        break;

    // =============================================
    // CONTAR CITAS DEL DÍA (estadísticas)
    // =============================================
    case 'estadisticas':
        $stats = [];
        if (isset($_GET['start']) && isset($_GET['end'])) {
            $start = $_GET['start'];
            $end = $_GET['end'];
            
            $stmtTotal = $conexion->prepare("SELECT COUNT(*) as total FROM citas WHERE fecha_cita >= ? AND fecha_cita < ?");
            $stmtTotal->bind_param("ss", $start, $end);
            $stmtTotal->execute();
            $stats['total'] = $stmtTotal->get_result()->fetch_assoc()['total'];
            $stmtTotal->close();

            $stmtEstado = $conexion->prepare("SELECT estado, COUNT(*) as cantidad FROM citas WHERE fecha_cita >= ? AND fecha_cita < ? GROUP BY estado");
            $stmtEstado->bind_param("ss", $start, $end);
            $stmtEstado->execute();
            $resultado = $stmtEstado->get_result();
        } else {
            $fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
            
            $stmtTotal = $conexion->prepare("SELECT COUNT(*) as total FROM citas WHERE DATE(fecha_cita) = ?");
            $stmtTotal->bind_param("s", $fecha);
            $stmtTotal->execute();
            $stats['total'] = $stmtTotal->get_result()->fetch_assoc()['total'];
            $stmtTotal->close();

            $stmtEstado = $conexion->prepare("SELECT estado, COUNT(*) as cantidad FROM citas WHERE DATE(fecha_cita) = ? GROUP BY estado");
            $stmtEstado->bind_param("s", $fecha);
            $stmtEstado->execute();
            $resultado = $stmtEstado->get_result();
        }

        $stats['por_estado'] = [];
        while ($fila = $resultado->fetch_assoc()) {
            $stats['por_estado'][$fila['estado']] = intval($fila['cantidad']);
        }
        if (isset($stmtEstado)) $stmtEstado->close();

        header('Content-Type: application/json');
        echo json_encode($stats);
        break;

    default:
        echo "Acción no reconocida.";
        break;
}

$conexion->close();
?>
