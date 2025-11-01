<?php
// api/obtenerRespuestasDeAlumno.php
// API para que un EN cuestador vea las respuestas de un ALUMNO específico.
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once '../config/db.php';
require_once '../controllers/EncuestaController.php';

// 1. Seguridad: Solo Encuestadores
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'encuestador') {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'mensaje' => 'Acceso denegado.']);
    exit();
}
// 2. Seguridad: Método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
    exit();
}

// 3. Validar Parámetros de URL
if (!isset($_GET['id_encuesta']) || !filter_var($_GET['id_encuesta'], FILTER_VALIDATE_INT)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'mensaje' => 'Se requiere "id_encuesta" válido.']);
    exit();
}
if (!isset($_GET['id_alumno']) || !filter_var($_GET['id_alumno'], FILTER_VALIDATE_INT)) {
     http_response_code(400);
    echo json_encode(['success' => false, 'mensaje' => 'Se requiere "id_alumno" válido.']);
    exit();
}


$id_encuesta = (int)$_GET['id_encuesta'];
$id_alumno_a_ver = (int)$_GET['id_alumno'];
$id_encuestador_logueado = $_SESSION['usuario']['id_usuario']; // De la sesión

$controlador = new EncuestaController($conexion);
// 4. Llamar a la nueva función del controlador
$respuesta = $controlador->obtenerRespuestasDeAlumno($id_encuesta, $id_alumno_a_ver, $id_encuestador_logueado);

http_response_code($respuesta['estado']);
echo json_encode($respuesta);

$conexion->close();
?>