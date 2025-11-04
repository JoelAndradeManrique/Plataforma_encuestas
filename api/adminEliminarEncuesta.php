<?php
// api/adminEliminarEncuesta.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
require_once '../config/db.php';
require_once '../controllers/EncuestaController.php';

// Seguridad: Solo Admin
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'administrador') {
    http_response_code(403); 
    echo json_encode(['success' => false, 'mensaje' => 'Acceso denegado.']); 
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); 
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']); 
    exit();
}

$datos = json_decode(file_get_contents("php://input"), true);
$id_encuesta = $datos['id_encuesta'] ?? null;

$controlador = new EncuestaController($conexion);
$respuesta = $controlador->eliminarEncuestaAdmin($id_encuesta);
http_response_code($respuesta['estado']);
echo json_encode($respuesta);
$conexion->close();
?>