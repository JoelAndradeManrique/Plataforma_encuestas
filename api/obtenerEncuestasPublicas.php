<?php
// api/obtenerEncuestasPublicas.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once '../config/db.php';
require_once '../controllers/EncuestaController.php';

// --- Seguridad: Solo Alumnos (o cualquier logueado) ---
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'alumno') {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'mensaje' => 'Acceso denegado. Se requiere rol de alumno.']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
    exit();
}

// --- ✅ INICIO DE LÓGICA DE BÚSQUEDA ---
// Verificamos si el frontend envió un parámetro "search" en la URL
$searchTerm = null;
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $searchTerm = trim($_GET['search']);
}
// --- ✅ FIN DE LÓGICA DE BÚSQUEDA ---


$controlador = new EncuestaController($conexion);
// ✅ Le pasamos el término de búsqueda (o null) al controlador
$respuesta = $controlador->listarEncuestasPublicas($searchTerm);

http_response_code($respuesta['estado']);
echo json_encode($respuesta);

$conexion->close();
?>