<?php
// api/obtenerMisRespuestasDeEncuesta.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once '../config/db.php';
require_once '../controllers/EncuestaController.php';

// 1. Seguridad: Solo Alumnos
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'alumno') {
    http_response_code(403);
    echo json_encode(['success' => false, 'mensaje' => 'Acceso denegado.']);
    exit();
}
// 2. Seguridad: Método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
    exit();
}
// 3. Validar Parámetros
if (!isset($_GET['id_encuesta']) || !filter_var($_GET['id_encuesta'], FILTER_VALIDATE_INT)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'mensaje' => 'Se requiere "id_encuesta" válido.']);
    exit();
}

$id_encuesta = (int)$_GET['id_encuesta'];
$id_alumno_logueado = $_SESSION['usuario']['id_usuario'];

$controlador = new EncuestaController($conexion);

// --- ✅ INICIO DE LA CORRECCIÓN ---
// Llamar a la función del controlador
$respuesta_controlador = $controlador->obtenerMisRespuestas($id_encuesta, $id_alumno_logueado);

// Verificar si el controlador tuvo éxito
if ($respuesta_controlador['success'] === true) {
    // Si tuvo éxito, enviar el JSON con la estructura que el frontend espera
    http_response_code($respuesta_controlador['estado']);
    // La clave 'encuesta_con_respuestas' contendrá el array de preguntas/respuestas
    echo json_encode([
        'success' => true,
        'encuesta_con_respuestas' => $respuesta_controlador['respuestas_alumno'] 
    ]);
} else {
    // Si el controlador falló (ej. no encontrado), pasar el error
    http_response_code($respuesta_controlador['estado']);
    echo json_encode($respuesta_controlador); // Devuelve { success: false, mensaje: "..." }
}
// --- FIN DE LA CORRECCIÓN ---

$conexion->close();
?>