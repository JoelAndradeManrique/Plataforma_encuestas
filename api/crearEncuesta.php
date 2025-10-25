<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once '../config/db.php';
require_once '../controllers/EncuestaController.php';

// --- Seguridad: Solo Encuestadores ---
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'encuestador') {
    http_response_code(403); // 403 Forbidden
    echo json_encode(['success' => false, 'mensaje' => 'Acceso denegado. Se requiere rol de encuestador.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
    exit();
}

$datos = json_decode(file_get_contents("php://input"), true);

// Añadimos el ID del encuestador (desde la sesión) a los datos que se enviarán
$datos['id_encuestador'] = $_SESSION['usuario']['id_usuario'];

$controlador = new EncuestaController($conexion);
$respuesta = $controlador->crearNuevaEncuesta($datos);

http_response_code($respuesta['estado']);
echo json_encode($respuesta);

$conexion->close();
?>