<?php
// api/eliminarUsuario.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE, POST"); // Aceptar DELETE o POST

require_once '../config/db.php';
require_once '../controllers/UsuarioController.php';

// --- Validación de Rol (Admin) ---
// Aquí verificarías que el usuario logueado sea 'administrador'
// --- Fin Validación ---

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
    exit();
}

$datos = json_decode(file_get_contents("php://input"), true);

$controlador = new UsuarioController($conexion);
$respuesta = $controlador->eliminarUsuario($datos);

http_response_code($respuesta['estado']);
echo json_encode($respuesta);

$conexion->close();
?>