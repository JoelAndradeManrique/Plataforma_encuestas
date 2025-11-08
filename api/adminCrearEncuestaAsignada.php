<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php';
require_once '../controllers/EncuestaController.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'administrador') {
    http_response_code(403);
    echo json_encode(['success' => false, 'mensaje' => 'Acceso denegado.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$controlador = new EncuestaController($conexion);

// Usar el nuevo método específico para asignar encuestas
$respuesta = $controlador->crearEncuestaAsignada($data);

http_response_code($respuesta['estado']);
echo json_encode($respuesta);
?>