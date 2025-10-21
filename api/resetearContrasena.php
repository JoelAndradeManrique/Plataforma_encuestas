<?php
// api/resetearContrasena.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST"); // O PUT

require_once '../config/db.php';
require_once '../controllers/UsuarioController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { // Ajusta si prefieres PUT
    http_response_code(405);
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
    exit();
}

$datos = json_decode(file_get_contents("php://input"), true);
$controlador = new UsuarioController($conexion);
$respuesta = $controlador->resetearContrasena($datos);

http_response_code($respuesta['estado']);
echo json_encode($respuesta);

$conexion->close();
?>