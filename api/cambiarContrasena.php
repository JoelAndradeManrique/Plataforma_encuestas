<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once '../config/db.php';
require_once '../controllers/UsuarioController.php';

// Seguridad: ¡El usuario DEBE estar logueado!
if (!isset($_SESSION['usuario']['id_usuario'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado.']);
    exit();
}

$datos = json_decode(file_get_contents("php://input"), true);

// Añadimos el id_usuario de la sesión a los datos
$datos['id_usuario'] = $_SESSION['usuario']['id_usuario'];

$controlador = new UsuarioController($conexion);
// Necesitamos una nueva función en el controlador
$respuesta = $controlador->cambiarContrasenaLogueado($datos); 

// Si el cambio fue exitoso, actualizamos la sesión
if ($respuesta['success'] == true) {
     $_SESSION['usuario']['password_temporal'] = 0; // O false
}

http_response_code($respuesta['estado']);
echo json_encode($respuesta);

$conexion->close();
?>