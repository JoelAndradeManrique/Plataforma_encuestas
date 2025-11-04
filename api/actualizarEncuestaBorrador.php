<?php
// api/actualizarEncuestaBorrador.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once '../config/db.php';
require_once '../controllers/EncuestaController.php';

// Seguridad: Solo Encuestador o Admin
if (!isset($_SESSION['usuario']) || ($_SESSION['usuario']['rol'] !== 'encuestador' && $_SESSION['usuario']['rol'] !== 'administrador')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'mensaje' => 'Acceso denegado.']);
    exit();
}

$datos = json_decode(file_get_contents("php://input"), true);
$id_usuario = $_SESSION['usuario']['id_usuario'];
$rol = $_SESSION['usuario']['rol'];
$id_encuesta = $datos['id_encuesta'] ?? null;

if (empty($id_encuesta)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'mensaje' => 'ID de encuesta no proporcionado.']);
    exit();
}

$controlador = new EncuestaController($conexion);

// Pasamos el ID de usuario y el ROL a la función del controlador
$respuesta = $controlador->actualizarEncuestaBorrador($id_encuesta, $datos, $id_usuario, $rol);

http_response_code($respuesta['estado']);
echo json_encode($respuesta);
$conexion->close();
?>