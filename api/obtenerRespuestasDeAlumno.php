<?php
// api/obtenerRespuestasDeAlumno.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once '../config/db.php';
require_once '../controllers/EncuestaController.php';

// --- ✅ CORRECCIÓN DE ROL ---
if (!isset($_SESSION['usuario']) ||
    ($_SESSION['usuario']['rol'] !== 'encuestador' && $_SESSION['usuario']['rol'] !== 'administrador')) { // Usar 'administrador'
    http_response_code(403);
    echo json_encode(['success' => false, 'mensaje' => 'Acceso denegado.']);
    exit();
}
// --- FIN CORRECCIÓN ---

if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['id_encuesta']) || !isset($_GET['id_alumno'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'mensaje' => 'Se requieren "id_encuesta" y "id_alumno".']);
    exit();
}

$id_encuesta = (int)$_GET['id_encuesta'];
$id_alumno_a_ver = (int)$_GET['id_alumno'];
$id_usuario_logueado = $_SESSION['usuario']['id_usuario'];
$rol_logueado = $_SESSION['usuario']['rol']; // Obtener el rol

$controlador = new EncuestaController($conexion);
$respuesta = $controlador->obtenerRespuestasDeAlumno($id_encuesta, $id_alumno_a_ver, $id_usuario_logueado, $rol_logueado);

http_response_code($respuesta['estado']);
echo json_encode($respuesta);
$conexion->close();
?>