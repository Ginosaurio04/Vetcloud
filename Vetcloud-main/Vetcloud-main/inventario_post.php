<?php
session_start();
require 'conex.php';

$accion = isset($_POST['accion']) ? $_POST['accion'] : (isset($_GET['accion']) ? $_GET['accion'] : '');

switch ($accion) {

    // =============================================
    // AGREGAR NUEVO PRODUCTO
    // =============================================
    case 'crear':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $codigo_barras = trim($_POST['codigo_barras']);
            $nombre_producto = trim($_POST['nombre_producto']);
            $tipo = $_POST['tipo'];
            $precio_venta = max(0, floatval($_POST['precio_venta']));
            $stock_actual = max(0, intval($_POST['stock_actual']));
            $stock_minimo = max(0, intval($_POST['stock_minimo']));
            $fecha_vencimiento = !empty($_POST['fecha_vencimiento']) ? $_POST['fecha_vencimiento'] : null;

            if (!empty($codigo_barras)) {
                $stmt_check = $conexion->prepare("SELECT id_producto FROM inventario WHERE codigo_barras = ?");
                $stmt_check->bind_param("s", $codigo_barras);
                $stmt_check->execute();
                if ($stmt_check->get_result()->num_rows > 0) {
                    header("Location: inventario.html?msg=error_codigo_duplicado");
                    exit();
                }
                $stmt_check->close();
            }

            $stmt = $conexion->prepare("INSERT INTO inventario (codigo_barras, nombre_producto, tipo, precio_venta, stock_actual, stock_minimo, fecha_vencimiento) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssdiis", $codigo_barras, $nombre_producto, $tipo, $precio_venta, $stock_actual, $stock_minimo, $fecha_vencimiento);

            if ($stmt->execute()) {
                header("Location: inventario.html?msg=producto_creado");
                exit();
            } else {
                echo "Error al agregar producto: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    // =============================================
    // LISTAR PRODUCTOS (todos o filtrado por tipo)
    // =============================================
    case 'listar':
        $tipo_filtro = isset($_GET['tipo']) ? $_GET['tipo'] : '';

        if (!empty($tipo_filtro) && $tipo_filtro !== 'Todos') {
            $stmt = $conexion->prepare("SELECT * FROM inventario WHERE tipo = ? ORDER BY nombre_producto ASC");
            $stmt->bind_param("s", $tipo_filtro);
            $stmt->execute();
            $resultado = $stmt->get_result();
        } else {
            $resultado = $conexion->query("SELECT * FROM inventario ORDER BY nombre_producto ASC");
        }

        $productos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $productos[] = $fila;
        }

        header('Content-Type: application/json');
        echo json_encode($productos);

        if (isset($stmt)) $stmt->close();
        break;

    // =============================================
    // LISTAR PRODUCTOS CON STOCK BAJO
    // =============================================
    case 'stock_bajo':
        $resultado = $conexion->query("SELECT * FROM inventario WHERE stock_actual <= stock_minimo ORDER BY stock_actual ASC");

        $productos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $productos[] = $fila;
        }

        header('Content-Type: application/json');
        echo json_encode($productos);
        break;

    // =============================================
    // EDITAR PRODUCTO
    // =============================================
    case 'editar':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $id_producto = intval($_POST['id_producto']);
            $codigo_barras = trim($_POST['codigo_barras']);
            $nombre_producto = trim($_POST['nombre_producto']);
            $tipo = $_POST['tipo'];
            $precio_venta = max(0, floatval($_POST['precio_venta']));
            $stock_actual = max(0, intval($_POST['stock_actual']));
            $stock_minimo = max(0, intval($_POST['stock_minimo']));
            $fecha_vencimiento = !empty($_POST['fecha_vencimiento']) ? $_POST['fecha_vencimiento'] : null;

            if (!empty($codigo_barras)) {
                $stmt_check = $conexion->prepare("SELECT id_producto FROM inventario WHERE codigo_barras = ? AND id_producto != ?");
                $stmt_check->bind_param("si", $codigo_barras, $id_producto);
                $stmt_check->execute();
                if ($stmt_check->get_result()->num_rows > 0) {
                    header("Location: inventario.html?msg=error_codigo_duplicado");
                    exit();
                }
                $stmt_check->close();
            }

            $stmt = $conexion->prepare("UPDATE inventario SET codigo_barras = ?, nombre_producto = ?, tipo = ?, precio_venta = ?, stock_actual = ?, stock_minimo = ?, fecha_vencimiento = ? WHERE id_producto = ?");
            $stmt->bind_param("sssdiisi", $codigo_barras, $nombre_producto, $tipo, $precio_venta, $stock_actual, $stock_minimo, $fecha_vencimiento, $id_producto);

            if ($stmt->execute()) {
                header("Location: inventario.html?msg=producto_editado");
                exit();
            } else {
                echo "Error al editar: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    // =============================================
    // ELIMINAR PRODUCTO
    // =============================================
    case 'eliminar':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $id_producto = intval($_POST['id_producto']);

            $stmt = $conexion->prepare("DELETE FROM inventario WHERE id_producto = ?");
            $stmt->bind_param("i", $id_producto);

            if ($stmt->execute()) {
                header("Location: inventario.html?msg=producto_eliminado");
                exit();
            } else {
                echo "Error al eliminar: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    // =============================================
    // ACTUALIZAR STOCK (sumar o restar)
    // =============================================
    case 'actualizar_stock':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $id_producto = intval($_POST['id_producto']);
            $cantidad = intval($_POST['cantidad']); // positivo = entrada, negativo = salida

            $stmt = $conexion->prepare("UPDATE inventario SET stock_actual = stock_actual + ? WHERE id_producto = ?");
            $stmt->bind_param("ii", $cantidad, $id_producto);

            if ($stmt->execute()) {
                header("Location: inventario.html?msg=stock_actualizado");
                exit();
            } else {
                echo "Error al actualizar stock: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    // =============================================
    // ESTADÍSTICAS DEL INVENTARIO
    // =============================================
    case 'estadisticas':
        $stats = [];

        // Productos con stock bajo
        $result = $conexion->query("SELECT COUNT(*) as total FROM inventario WHERE stock_actual <= stock_minimo");
        $stats['stock_bajo'] = $result->fetch_assoc()['total'];

        // Valor total del inventario
        $result = $conexion->query("SELECT SUM(precio_venta * stock_actual) as valor_total FROM inventario");
        $stats['valor_total'] = $result->fetch_assoc()['valor_total'] ?? 0;

        // Total de productos activos
        $result = $conexion->query("SELECT COUNT(*) as total FROM inventario WHERE stock_actual > 0");
        $stats['productos_activos'] = $result->fetch_assoc()['total'];

        // Productos vencidos
        $result = $conexion->query("SELECT COUNT(*) as total FROM inventario WHERE fecha_vencimiento IS NOT NULL AND fecha_vencimiento < CURDATE()");
        $stats['vencidos'] = $result->fetch_assoc()['total'];

        header('Content-Type: application/json');
        echo json_encode($stats);
        break;

    default:
        echo "Acción no reconocida.";
        break;
}

$conexion->close();
?>
