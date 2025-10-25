<?php
// api/obtenerEncuestasPublicas.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once '../config/db.php';
require_once '../controllers/EncuestaController.php';

// --- Seguridad: Solo Alumnos ---
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'alumno') {
    http_response_code(403); echo json_encode(['success' => false, 'mensaje' => 'Acceso denegado.']); exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']); exit();
}

// --- Lógica de Búsqueda ---
$searchTerm = null;
if (isset($_GET['search']) && !empty(trim($_GET['search']))) { $searchTerm = trim($_GET['search']); }

// ✅ Obtener ID del alumno desde la sesión
$id_alumno = $_SESSION['usuario']['id_usuario'];

$controlador = new EncuestaController($conexion);
// ✅ Pasar ID de alumno y término de búsqueda al controlador
$respuesta = $controlador->listarEncuestasPublicas($id_alumno, $searchTerm);

http_response_code($respuesta['estado']);
echo json_encode($respuesta);

$conexion->close();
?>