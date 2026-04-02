<?php
session_start();
require 'conex.php';

$accion = isset($_POST['accion']) ? $_POST['accion'] : (isset($_GET['accion']) ? $_GET['accion'] : '');

switch ($accion) {

    // =============================================
    // CREAR NUEVA FACTURA
    // =============================================
    case 'crear':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $id_cliente = intval($_POST['id_cliente']);
            $metodo_pago = $_POST['metodo_pago'];

            // Evitar facturar la misma cita 2 veces
            if (isset($_POST['id_cita']) && !empty($_POST['id_cita'])) {
                $id_cita = intval($_POST['id_cita']);
                $stmt_check = $conexion->prepare("SELECT estado FROM citas WHERE id_cita = ?");
                $stmt_check->bind_param("i", $id_cita);
                $stmt_check->execute();
                $resultado_check = $stmt_check->get_result();
                if ($fila = $resultado_check->fetch_assoc()) {
                    if ($fila['estado'] === 'Facturado') {
                        header("Location: factura.html?msg=error_cita_facturada");
                        exit();
                    }
                }
                $stmt_check->close();
            }

            // Crear la factura con total 0, se actualizará con los detalles
            $stmt = $conexion->prepare("INSERT INTO facturas (id_cliente, metodo_pago, total_pagar) VALUES (?, ?, 0.00)");
            $stmt->bind_param("is", $id_cliente, $metodo_pago);

            if ($stmt->execute()) {
                $id_factura = $conexion->insert_id;

                // Insertar los detalles de la factura
                if (isset($_POST['items']) && is_array($_POST['items'])) {
                    $total = 0;

                    $stmt_detalle = $conexion->prepare("INSERT INTO detalle_factura (id_factura, id_producto, descripcion_servicio, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?)");

                    foreach ($_POST['items'] as $item) {
                        $id_producto = !empty($item['id_producto']) ? intval($item['id_producto']) : null;
                        $descripcion = trim($item['descripcion']);
                        $cantidad = intval($item['cantidad']);
                        $precio_unitario = floatval($item['precio_unitario']);
                        $subtotal = $cantidad * $precio_unitario;
                        $total += $subtotal;

                        $stmt_detalle->bind_param("iisidd", $id_factura, $id_producto, $descripcion, $cantidad, $precio_unitario, $subtotal);
                        $stmt_detalle->execute();

                        // Descontar del inventario si es un producto
                        if ($id_producto) {
                            $stmt_stock = $conexion->prepare("UPDATE inventario SET stock_actual = stock_actual - ? WHERE id_producto = ?");
                            $stmt_stock->bind_param("ii", $cantidad, $id_producto);
                            $stmt_stock->execute();
                            $stmt_stock->close();
                        }
                    }

                    $stmt_detalle->close();

                    // Actualizar total de la factura
                    $stmt_total = $conexion->prepare("UPDATE facturas SET total_pagar = ? WHERE id_factura = ?");
                    $stmt_total->bind_param("di", $total, $id_factura);
                    $stmt_total->execute();
                    $stmt_total->close();
                }

                // Si viene de una cita, actualizamos su estado
                if (isset($_POST['id_cita']) && !empty($_POST['id_cita'])) {
                    $id_cita = intval($_POST['id_cita']);
                    $stmt_cita = $conexion->prepare("UPDATE citas SET estado = 'Facturado' WHERE id_cita = ?");
                    if ($stmt_cita) {
                        $stmt_cita->bind_param("i", $id_cita);
                        $stmt_cita->execute();
                        $stmt_cita->close();
                    }
                }

                header("Location: factura.html?msg=factura_creada&id=" . $id_factura);
                exit();
            } else {
                echo "Error al crear la factura: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    // =============================================
    // LISTAR FACTURAS
    // =============================================
    case 'listar':
        $resultado = $conexion->query("
            SELECT f.id_factura, f.fecha_emision, f.total_pagar, f.metodo_pago,
                   cl.nombre_completo AS cliente, cl.cedula
            FROM facturas f
            INNER JOIN clientes cl ON f.id_cliente = cl.id_cliente
            ORDER BY f.fecha_emision DESC
        ");

        $facturas = [];
        while ($fila = $resultado->fetch_assoc()) {
            $facturas[] = $fila;
        }

        header('Content-Type: application/json');
        echo json_encode($facturas);
        break;

    // =============================================
    // VER DETALLE DE UNA FACTURA
    // =============================================
    case 'detalle':
        $id_factura = intval($_GET['id_factura']);

        // Datos de la factura
        $stmt = $conexion->prepare("
            SELECT f.*, cl.nombre_completo AS cliente, cl.cedula, cl.telefono, cl.email
            FROM facturas f
            INNER JOIN clientes cl ON f.id_cliente = cl.id_cliente
            WHERE f.id_factura = ?
        ");
        $stmt->bind_param("i", $id_factura);
        $stmt->execute();
        $factura = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Detalles (líneas de la factura)
        $stmt2 = $conexion->prepare("
            SELECT df.*, inv.nombre_producto
            FROM detalle_factura df
            LEFT JOIN inventario inv ON df.id_producto = inv.id_producto
            WHERE df.id_factura = ?
        ");
        $stmt2->bind_param("i", $id_factura);
        $stmt2->execute();
        $resultado = $stmt2->get_result();

        $detalles = [];
        while ($fila = $resultado->fetch_assoc()) {
            $detalles[] = $fila;
        }
        $stmt2->close();

        $factura['detalles'] = $detalles;

        header('Content-Type: application/json');
        echo json_encode($factura);
        break;

    // =============================================
    // AGREGAR ITEM A FACTURA EXISTENTE
    // =============================================
    case 'agregar_item':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $id_factura = intval($_POST['id_factura']);
            $id_producto = !empty($_POST['id_producto']) ? intval($_POST['id_producto']) : null;
            $descripcion = trim($_POST['descripcion']);
            $cantidad = intval($_POST['cantidad']);
            $precio_unitario = floatval($_POST['precio_unitario']);
            $subtotal = $cantidad * $precio_unitario;

            $stmt = $conexion->prepare("INSERT INTO detalle_factura (id_factura, id_producto, descripcion_servicio, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisidd", $id_factura, $id_producto, $descripcion, $cantidad, $precio_unitario, $subtotal);

            if ($stmt->execute()) {
                // Actualizar total de la factura
                $conexion->query("UPDATE facturas SET total_pagar = (SELECT SUM(subtotal) FROM detalle_factura WHERE id_factura = $id_factura) WHERE id_factura = $id_factura");

                // Descontar stock si es producto
                if ($id_producto) {
                    $stmt_stock = $conexion->prepare("UPDATE inventario SET stock_actual = stock_actual - ? WHERE id_producto = ?");
                    $stmt_stock->bind_param("ii", $cantidad, $id_producto);
                    $stmt_stock->execute();
                    $stmt_stock->close();
                }

                header("Location: factura.html?msg=item_agregado&id=" . $id_factura);
                exit();
            } else {
                echo "Error al agregar item: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    // =============================================
    // ELIMINAR FACTURA
    // =============================================
    case 'eliminar':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $id_factura = intval($_POST['id_factura']);

            // Los detalles se eliminan automáticamente por ON DELETE CASCADE
            $stmt = $conexion->prepare("DELETE FROM facturas WHERE id_factura = ?");
            $stmt->bind_param("i", $id_factura);

            if ($stmt->execute()) {
                header("Location: factura.html?msg=factura_eliminada");
                exit();
            } else {
                echo "Error al eliminar: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    // =============================================
    // OBTENER CLIENTES (para formulario nueva factura)
    // =============================================
    case 'obtener_clientes':
        $resultado = $conexion->query("SELECT id_cliente, cedula, nombre_completo, telefono FROM clientes ORDER BY nombre_completo ASC");

        $clientes = [];
        while ($fila = $resultado->fetch_assoc()) {
            $clientes[] = $fila;
        }

        header('Content-Type: application/json');
        echo json_encode($clientes);
        break;

    // =============================================
    // OBTENER PRODUCTOS (para agregar a factura)
    // =============================================
    case 'obtener_productos':
        $resultado = $conexion->query("SELECT id_producto, nombre_producto, precio_venta, stock_actual FROM inventario WHERE stock_actual > 0 ORDER BY nombre_producto ASC");

        $productos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $productos[] = $fila;
        }

        header('Content-Type: application/json');
        echo json_encode($productos);
        break;

    // =============================================
    // ESTADÍSTICAS DE FACTURACIÓN
    // =============================================
    case 'estadisticas':
        $stats = [];

        // Facturación del día
        $result = $conexion->query("SELECT COALESCE(SUM(total_pagar), 0) as total_hoy FROM facturas WHERE DATE(fecha_emision) = CURDATE()");
        $stats['total_hoy'] = $result->fetch_assoc()['total_hoy'];

        // Facturas del día
        $result = $conexion->query("SELECT COUNT(*) as cantidad FROM facturas WHERE DATE(fecha_emision) = CURDATE()");
        $stats['facturas_hoy'] = $result->fetch_assoc()['cantidad'];

        // Facturación del mes
        $result = $conexion->query("SELECT COALESCE(SUM(total_pagar), 0) as total_mes FROM facturas WHERE MONTH(fecha_emision) = MONTH(CURDATE()) AND YEAR(fecha_emision) = YEAR(CURDATE())");
        $stats['total_mes'] = $result->fetch_assoc()['total_mes'];

        header('Content-Type: application/json');
        echo json_encode($stats);
        break;

    // =============================================
    // OBTENER CITAS DEL DÍA (para sidebar de facturación)
    // =============================================
    case 'obtener_citas_hoy':
        $resultado = $conexion->query("
            SELECT c.id_cita, c.fecha_cita, c.tipo_servicio, c.estado, c.observaciones,
                   m.nombre_animal, m.especie, m.raza,
                   cl.id_cliente, cl.nombre_completo AS dueno, cl.telefono, cl.cedula, cl.puntos_fidelidad
            FROM citas c
            INNER JOIN mascotas m ON c.id_mascota = m.id_mascota
            INNER JOIN clientes cl ON m.id_cliente = cl.id_cliente
            WHERE DATE(c.fecha_cita) = CURDATE()
            ORDER BY c.fecha_cita ASC
        ");

        $citas = [];
        while ($fila = $resultado->fetch_assoc()) {
            $citas[] = $fila;
        }

        header('Content-Type: application/json');
        echo json_encode($citas);
        break;

    default:
        echo "Acción no reconocida.";
        break;
}

$conexion->close();
?>
