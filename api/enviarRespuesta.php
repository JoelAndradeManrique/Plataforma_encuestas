<?php
// api/enviarRespuesta.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once '../config/db.php';
require_once '../controllers/EncuestaController.php';

// --- Seguridad: Solo Alumnos ---
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'alumno') {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'mensaje' => 'Acceso denegado.']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
    exit();
}

$datos = json_decode(file_get_contents("php://input"), true);
$id_alumno_real = $_SESSION['usuario']['id_usuario']; // De la sesión

$controlador = new EncuestaController($conexion);
$respuesta = $controlador->recibirRespuestas($datos, $id_alumno_real);

http_response_code($respuesta['estado']);
echo json_encode($respuesta);

$conexion->close();
?>