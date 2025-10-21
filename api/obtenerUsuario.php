<?php
// api/obtenerUsuario.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once '../config/db.php';
require_once '../controllers/UsuarioController.php';

// --- Validación de Rol (IMPORTANTE para seguridad real) ---
// Aquí verificarías si el usuario logueado es Admin
// --- Fin Validación ---

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
    exit();
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'mensaje' => 'Se requiere el ID del usuario.']);
    exit();
}

$id_usuario = intval($_GET['id']);
$controlador = new UsuarioController($conexion);
$respuesta = $controlador->obtenerUsuarioPorId($id_usuario);

http_response_code($respuesta['estado']);
echo json_encode($respuesta);

$conexion->close();
?>