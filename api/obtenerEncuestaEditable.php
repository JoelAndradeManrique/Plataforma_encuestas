<?php
// api/obtenerEncuestaEditable.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once '../config/db.php';
require_once '../controllers/EncuestaController.php'; // Usamos el mismo controlador

// --- Seguridad: Solo Encuestadores ---
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'encuestador') {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'mensaje' => 'Acceso denegado.']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
    exit();
}

// El ID de la encuesta a editar viene por la URL
if (!isset($_GET['id_encuesta']) || !filter_var($_GET['id_encuesta'], FILTER_VALIDATE_INT)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'mensaje' => 'Se requiere un ID de encuesta válido en la URL (id_encuesta=X).']);
    exit();
}

$id_encuesta = (int)$_GET['id_encuesta'];
$id_encuestador = $_SESSION['usuario']['id_usuario']; // De la sesión

$controlador = new EncuestaController($conexion);
// Llamar a la nueva función del controlador
$respuesta = $controlador->obtenerEncuestaParaEditar($id_encuesta, $id_encuestador);

http_response_code($respuesta['estado']);
echo json_encode($respuesta);

$conexion->close();
?>