<?php
// api/registrarEncuestador.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once '../config/db.php';
require_once '../controllers/UsuarioController.php';

// --- IMPORTANTE: Validación de Rol ---
// En una aplicación real, aquí verificarías que quien hace la petición
// es un administrador. Por ejemplo, usando sesiones o tokens JWT.
// session_start();
// if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'administrador') {
//     http_response_code(403); // Forbidden
//     echo json_encode(['success' => false, 'mensaje' => 'Acceso no autorizado.']);
//     exit();
// }
// --- FIN Validación de Rol (SIMULADA POR AHORA) ---

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
    exit();
}

$datos = json_decode(file_get_contents("php://input"), true);

$controlador = new UsuarioController($conexion);
$respuesta = $controlador->registrarEncuestador($datos);

http_response_code($respuesta['estado']);
echo json_encode($respuesta);

$conexion->close();
?>