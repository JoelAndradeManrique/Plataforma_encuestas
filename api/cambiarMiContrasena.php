<?php
// api/cambiarMiContrasena.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once '../config/db.php';
require_once '../controllers/UsuarioController.php'; // Usamos el mismo controlador

// Seguridad: Usuario debe estar logueado
if (!isset($_SESSION['usuario']['id_usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado.']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
    exit();
}

$datos = json_decode(file_get_contents("php://input"), true);
// Añadimos el ID del usuario logueado
$datos['id_usuario'] = $_SESSION['usuario']['id_usuario'];

$controlador = new UsuarioController($conexion);
$respuesta = $controlador->cambiarMiContrasena($datos); // Llamamos a la nueva función

http_response_code($respuesta['estado']);
echo json_encode($respuesta);

$conexion->close();
?>