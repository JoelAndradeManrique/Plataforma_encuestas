<?php
// api/adminEliminarUsuario.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
require_once '../config/db.php';
require_once '../controllers/UsuarioController.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'administrador') {
    http_response_code(403); echo json_encode(['success' => false, 'mensaje' => 'Acceso denegado.']); exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']); exit();
}

$datos = json_decode(file_get_contents("php://input"), true);
$id_usuario = $datos['id_usuario'] ?? null;

if ($id_usuario == $_SESSION['usuario']['id_usuario']) {
     http_response_code(403);
     echo json_encode(['success' => false, 'mensaje' => 'No puedes eliminarte a ti mismo.']);
     exit();
}

$controlador = new UsuarioController($conexion);
// ✅ CORRECCIÓN: Pasar el array $datos completo
$respuesta = $controlador->eliminarUsuario($datos); 
http_response_code($respuesta['estado']);
echo json_encode($respuesta);
$conexion->close();
?>