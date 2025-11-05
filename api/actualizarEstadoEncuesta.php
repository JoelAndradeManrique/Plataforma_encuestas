<?php
// api/actualizarEstadoEncuesta.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';
require_once '../models/Encuesta.php'; // Llamamos al modelo directamente

// Seguridad: Solo Encuestador o Admin
if (!isset($_SESSION['usuario']) || ($_SESSION['usuario']['rol'] !== 'encuestador' && $_SESSION['usuario']['rol'] !== 'administrador')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'mensaje' => 'Acceso denegado.']);
    exit();
}

$datos = json_decode(file_get_contents("php://input"), true);
$id_encuesta = $datos['id_encuesta'] ?? null;
$nuevo_estado = $datos['nuevo_estado'] ?? null;

// Obtenemos el ID y ROL del usuario de la sesión
$id_usuario = $_SESSION['usuario']['id_usuario'];
$rol = $_SESSION['usuario']['rol'];

if (empty($id_encuesta) || empty($nuevo_estado)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'mensaje' => 'Faltan datos (id_encuesta o nuevo_estado).']);
    exit();
}

$modeloEncuesta = new Encuesta($conexion);

// Pasamos los 4 argumentos
$success = $modeloEncuesta->updateEstado($id_encuesta, $nuevo_estado, $id_usuario, $rol);

if ($success) {
    echo json_encode(['success' => true, 'mensaje' => 'Estado actualizado.']);
} else {
    // Si affected_rows fue 0, es probable que no sea propietario (si es encuestador) o el ID no exista
    http_response_code(404);
    echo json_encode(['success' => false, 'mensaje' => 'No se pudo actualizar. La encuesta no existe o no tienes permisos.']);
}
$conexion->close();
?>