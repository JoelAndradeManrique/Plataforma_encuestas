<?php
// api/obtenerEncuestasRespondidas.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once '../config/db.php';
require_once '../controllers/EncuestaController.php';

// --- Seguridad: Solo Alumnos ---
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'alumno') {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'mensaje' => 'Acceso denegado.']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
    exit();
}

$id_alumno = $_SESSION['usuario']['id_usuario']; // De la sesión

$controlador = new EncuestaController($conexion);
$respuesta = $controlador->listarEncuestasRespondidas($id_alumno);

http_response_code($respuesta['estado']);
echo json_encode($respuesta);

$conexion->close();
?>