<?php
// api/adminListarAlumnos.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';
require_once '../controllers/UsuarioController.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'administrador') {
    http_response_code(403); echo json_encode(['success' => false, 'mensaje' => 'Acceso denegado.']); exit();
}
$controlador = new UsuarioController($conexion);
$respuesta = $controlador->listarAlumnos();
http_response_code($respuesta['estado']);
echo json_encode($respuesta);
$conexion->close();
?>