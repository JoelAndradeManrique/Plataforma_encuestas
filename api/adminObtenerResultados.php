<?php
// api/adminObtenerResultados.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once '../config/db.php';
require_once '../controllers/EncuestaController.php';

// --- ✅ CORRECCIÓN DE ROL ---
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'administrador') {
    http_response_code(403);
    echo json_encode(['success' => false, 'mensaje' => 'Acceso denegado.']);
    exit();
}
// --- FIN CORRECCIÓN ---

if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['id_encuesta'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'mensaje' => 'Se requiere id_encuesta.']);
    exit();
}

$id_encuesta = (int)$_GET['id_encuesta'];

$controlador = new EncuestaController($conexion);
$respuesta = $controlador->obtenerResultadosAdmin($id_encuesta); // Llama a la función Admin

http_response_code($respuesta['estado']);
echo json_encode($respuesta);
$conexion->close();
?>