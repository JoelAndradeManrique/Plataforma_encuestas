<?php
// api/solicitarRecuperacion.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once '../config/db.php';
require_once '../controllers/UsuarioController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
    exit();
}

$datos = json_decode(file_get_contents("php://input"), true);
$controlador = new UsuarioController($conexion);
$respuesta = $controlador->solicitarRecuperacion($datos);

http_response_code($respuesta['estado']);
echo json_encode($respuesta);

$conexion->close();
?>