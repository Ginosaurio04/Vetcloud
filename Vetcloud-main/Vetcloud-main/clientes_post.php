<?php
session_start();
require 'conex.php';

// Detectar si el body viene como JSON
$jsonInput = null;
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
if (stripos($contentType, 'application/json') !== false) {
    $jsonInput = json_decode(file_get_contents('php://input'), true);
}

$accion = '';
if ($jsonInput && isset($jsonInput['accion'])) {
    $accion = $jsonInput['accion'];
} elseif (isset($_POST['accion'])) {
    $accion = $_POST['accion'];
} elseif (isset($_GET['accion'])) {
    $accion = $_GET['accion'];
}

switch ($accion) {

    // =============================================
    // LISTAR CLIENTES
    // =============================================
    case 'listar':
        $buscar = isset($_GET['buscar']) ? '%' . $_GET['buscar'] . '%' : '%';
        $stmt = $conexion->prepare("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM mascotas m WHERE m.id_cliente = c.id_cliente) as total_mascotas
            FROM clientes c
            WHERE c.nombre_completo LIKE ? OR c.cedula LIKE ? OR c.email LIKE ?
            ORDER BY c.nombre_completo ASC
        ");
        $stmt->bind_param("sss", $buscar, $buscar, $buscar);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $clientes = [];
        while ($fila = $resultado->fetch_assoc()) {
            $clientes[] = $fila;
        }

        header('Content-Type: application/json');
        echo json_encode($clientes);
        $stmt->close();
        break;

    // =============================================
    // CREAR CLIENTE
    // =============================================
    case 'crear_cliente':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $cedula = trim($_POST['cedula']);
            $nombre = trim($_POST['nombre_completo']);
            $telefono = trim($_POST['telefono']);
            $email = trim($_POST['email']);
            $direccion = trim($_POST['direccion']);

            $stmt = $conexion->prepare("INSERT INTO clientes (cedula, nombre_completo, telefono, email, direccion) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $cedula, $nombre, $telefono, $email, $direccion);

            if ($stmt->execute()) {
                header("Location: clientes.html?msg=cliente_creado");
                exit();
            } else {
                if ($conexion->errno == 1062) {
                    header("Location: clientes.html?msg=error_duplicado");
                } else {
                    header("Location: clientes.html?msg=error_general");
                }
                exit();
            }
            $stmt->close();
        }
        break;

    // =============================================
    // EDITAR CLIENTE
    // =============================================
    case 'editar_cliente':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $id = intval($_POST['id_cliente']);
            $cedula = trim($_POST['cedula']);
            $nombre = trim($_POST['nombre_completo']);
            $telefono = trim($_POST['telefono']);
            $email = trim($_POST['email']);
            $direccion = trim($_POST['direccion']);

            $stmt = $conexion->prepare("UPDATE clientes SET cedula = ?, nombre_completo = ?, telefono = ?, email = ?, direccion = ? WHERE id_cliente = ?");
            $stmt->bind_param("sssssi", $cedula, $nombre, $telefono, $email, $direccion, $id);

            if ($stmt->execute()) {
                header("Location: clientes.html?msg=cliente_editado");
                exit();
            } else {
                if ($conexion->errno == 1062) {
                    header("Location: clientes.html?msg=error_duplicado");
                } else {
                    header("Location: clientes.html?msg=error_general");
                }
                exit();
            }
            $stmt->close();
        }
        break;

    // =============================================
    // ELIMINAR CLIENTE
    // =============================================
    case 'eliminar_cliente':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $id = intval($_POST['id_cliente']);

            // Verificar si tiene mascotas
            $check = $conexion->prepare("SELECT COUNT(*) as total FROM mascotas WHERE id_cliente = ?");
            $check->bind_param("i", $id);
            $check->execute();
            $total = $check->get_result()->fetch_assoc()['total'];
            $check->close();

            if ($total > 0) {
                header("Location: clientes.html?msg=error_tiene_mascotas");
                exit();
            }

            $stmt = $conexion->prepare("DELETE FROM clientes WHERE id_cliente = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                header("Location: clientes.html?msg=cliente_eliminado");
                exit();
            } else {
                echo "Error al eliminar: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    // =============================================
    // OBTENER UN CLIENTE (para editar)
    // =============================================
    case 'obtener_cliente':
        $id = intval($_GET['id']);
        $stmt = $conexion->prepare("SELECT * FROM clientes WHERE id_cliente = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $cliente = $resultado->fetch_assoc();

        header('Content-Type: application/json');
        echo json_encode($cliente);
        $stmt->close();
        break;

    // =============================================
    // LISTAR MASCOTAS DE UN CLIENTE
    // =============================================
    case 'listar_mascotas':
        $id_cliente = intval($_GET['id_cliente']);
        $stmt = $conexion->prepare("
            SELECT m.*, cl.nombre_completo as dueno
            FROM mascotas m
            INNER JOIN clientes cl ON m.id_cliente = cl.id_cliente
            WHERE m.id_cliente = ?
            ORDER BY m.nombre_animal ASC
        ");
        $stmt->bind_param("i", $id_cliente);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $mascotas = [];
        while ($fila = $resultado->fetch_assoc()) {
            $mascotas[] = $fila;
        }

        header('Content-Type: application/json');
        echo json_encode($mascotas);
        $stmt->close();
        break;

    // =============================================
    // CREAR MASCOTA
    // =============================================
    case 'crear_mascota':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $id_cliente = intval($_POST['id_cliente']);
            $nombre = trim($_POST['nombre_animal']);
            $especie = trim($_POST['especie']);
            $raza = trim($_POST['raza']);
            $edad = trim($_POST['edad']);
            $peso = floatval($_POST['peso']);
            $sexo = trim($_POST['sexo']);
            $color = trim($_POST['color']);
            $notas = trim($_POST['notas_medicas']);

            $stmt = $conexion->prepare("INSERT INTO mascotas (id_cliente, nombre_animal, especie, raza, edad, peso, sexo, color, notas_medicas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssdsss", $id_cliente, $nombre, $especie, $raza, $edad, $peso, $sexo, $color, $notas);

            if ($stmt->execute()) {
                header("Location: clientes.html?msg=mascota_creada&ver=" . $id_cliente);
                exit();
            } else {
                echo "Error al crear mascota: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    // =============================================
    // EDITAR MASCOTA
    // =============================================
    case 'editar_mascota':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $id_mascota = intval($_POST['id_mascota']);
            $id_cliente = intval($_POST['id_cliente']);
            $nombre = trim($_POST['nombre_animal']);
            $especie = trim($_POST['especie']);
            $raza = trim($_POST['raza']);
            $edad = trim($_POST['edad']);
            $peso = floatval($_POST['peso']);
            $sexo = trim($_POST['sexo']);
            $color = trim($_POST['color']);
            $notas = trim($_POST['notas_medicas']);

            $stmt = $conexion->prepare("UPDATE mascotas SET nombre_animal = ?, especie = ?, raza = ?, edad = ?, peso = ?, sexo = ?, color = ?, notas_medicas = ? WHERE id_mascota = ?");
            $stmt->bind_param("ssssdsssi", $nombre, $especie, $raza, $edad, $peso, $sexo, $color, $notas, $id_mascota);

            if ($stmt->execute()) {
                header("Location: clientes.html?msg=mascota_editada&ver=" . $id_cliente);
                exit();
            } else {
                echo "Error al editar mascota: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    // =============================================
    // ELIMINAR MASCOTA
    // =============================================
    case 'eliminar_mascota':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $id_mascota = intval($_POST['id_mascota']);
            $id_cliente = intval($_POST['id_cliente']);

            // Verificar si tiene citas
            $check = $conexion->prepare("SELECT COUNT(*) as total FROM citas WHERE id_mascota = ?");
            $check->bind_param("i", $id_mascota);
            $check->execute();
            $total = $check->get_result()->fetch_assoc()['total'];
            $check->close();

            if ($total > 0) {
                header("Location: clientes.html?msg=error_mascota_citas&ver=" . $id_cliente);
                exit();
            }

            $stmt = $conexion->prepare("DELETE FROM mascotas WHERE id_mascota = ?");
            $stmt->bind_param("i", $id_mascota);

            if ($stmt->execute()) {
                header("Location: clientes.html?msg=mascota_eliminada&ver=" . $id_cliente);
                exit();
            } else {
                echo "Error al eliminar: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    // =============================================
    // ESTADÍSTICAS
    // =============================================
    case 'estadisticas':
        $stats = [];

        $r = $conexion->query("SELECT COUNT(*) as total FROM clientes");
        $stats['total_clientes'] = $r->fetch_assoc()['total'];

        $r = $conexion->query("SELECT COUNT(*) as total FROM mascotas");
        $stats['total_mascotas'] = $r->fetch_assoc()['total'];

        $r = $conexion->query("SELECT COUNT(DISTINCT especie) as total FROM mascotas");
        $stats['total_especies'] = $r->fetch_assoc()['total'];

        $r = $conexion->query("SELECT COUNT(*) as total FROM clientes WHERE fecha_registro >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stats['nuevos_mes'] = $r->fetch_assoc()['total'];

        header('Content-Type: application/json');
        echo json_encode($stats);
        break;

    // =============================================
    // CREAR CLIENTE CON MASCOTAS (JSON)
    // =============================================
    case 'crear_cliente_con_mascotas':
        header('Content-Type: application/json');

        if (!$jsonInput || !isset($jsonInput['cliente'])) {
            echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
            exit();
        }

        $cliente = $jsonInput['cliente'];
        $mascotas = isset($jsonInput['mascotas']) ? $jsonInput['mascotas'] : [];

        $cedula = trim($cliente['cedula']);
        $nombre = trim($cliente['nombre_completo']);
        $telefono = trim($cliente['telefono']);
        $email = trim($cliente['email']);
        $direccion = trim($cliente['direccion']);

        // Iniciar transacción
        $conexion->begin_transaction();

        try {
            // Crear cliente
            $stmt = $conexion->prepare("INSERT INTO clientes (cedula, nombre_completo, telefono, email, direccion) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $cedula, $nombre, $telefono, $email, $direccion);

            if (!$stmt->execute()) {
                if ($conexion->errno == 1062) {
                    throw new Exception('Ya existe un cliente con esa cédula o correo');
                }
                throw new Exception('Error al crear cliente: ' . $stmt->error);
            }

            $id_cliente_nuevo = $conexion->insert_id;
            $stmt->close();

            // Crear mascotas
            if (count($mascotas) > 0) {
                $stmtM = $conexion->prepare("INSERT INTO mascotas (id_cliente, nombre_animal, especie, raza, edad, peso, sexo, color, notas_medicas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

                foreach ($mascotas as $m) {
                    $m_nombre = trim($m['nombre_animal']);
                    $m_especie = trim($m['especie']);
                    $m_raza = trim($m['raza'] ?? '');
                    $m_edad = trim($m['edad'] ?? '');
                    $m_peso = floatval($m['peso'] ?? 0);
                    $m_sexo = trim($m['sexo'] ?? '');
                    $m_color = trim($m['color'] ?? '');
                    $m_notas = trim($m['notas_medicas'] ?? '');

                    $stmtM->bind_param("issssdsss", $id_cliente_nuevo, $m_nombre, $m_especie, $m_raza, $m_edad, $m_peso, $m_sexo, $m_color, $m_notas);

                    if (!$stmtM->execute()) {
                        throw new Exception('Error al crear mascota: ' . $stmtM->error);
                    }
                }
                $stmtM->close();
            }

            $conexion->commit();
            echo json_encode(['success' => true, 'id_cliente' => $id_cliente_nuevo]);

        } catch (Exception $e) {
            $conexion->rollback();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
        break;

    default:
        echo "Acción no reconocida.";
        break;
}

$conexion->close();
?>
