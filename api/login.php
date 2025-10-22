<?php
// ✅ PASO 1: Iniciar la sesión ANTES de cualquier otra cosa
session_start();

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
// header("Access-Control-Allow-Origin: *"); // Descomenta si tienes problemas de CORS

require_once '../config/db.php';
require_once '../controllers/UsuarioController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
    exit();
}

$datos = json_decode(file_get_contents("php://input"), true);
$controlador = new UsuarioController($conexion);
$respuesta = $controlador->login($datos);

// ✅ PASO 2: Si el login es exitoso, GUARDAR AL USUARIO EN LA SESIÓN
if (isset($respuesta['success']) && $respuesta['success'] == true) {
    // Guardamos todo el array de 'usuario' en la sesión.
    $_SESSION['usuario'] = $respuesta['usuario'];
}

// PASO 3: Devolver la respuesta JSON al frontend
http_response_code($respuesta['estado']);
echo json_encode($respuesta);

$conexion->close();
?>