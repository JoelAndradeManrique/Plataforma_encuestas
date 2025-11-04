<?php
// api/adminObtenerUsuario.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';
require_once '../controllers/UsuarioController.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'administrador') {
    http_response_code(403); echo json_encode(['success' => false, 'mensaje' => 'Acceso denegado.']); exit();
}
if (!isset($_GET['id_usuario'])) {
    http_response_code(400); echo json_encode(['success' => false, 'mensaje' => 'Falta ID de usuario.']); exit();
}

$id_usuario = (int)$_GET['id_usuario'];
$controlador = new UsuarioController($conexion);
// ✅ CORRECCIÓN: Llamar a la función que SÍ existe en tu controlador
$respuesta = $controlador->obtenerUsuarioPorId($id_usuario);
http_response_code($respuesta['estado']);
echo json_encode($respuesta);
$conexion->close();
?>