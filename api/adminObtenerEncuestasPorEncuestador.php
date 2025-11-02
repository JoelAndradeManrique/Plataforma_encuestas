<?php
// api/adminObtenerEncuestasPorEncuestador.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once '../config/db.php';
require_once '../controllers/EncuestaController.php';

// Seguridad: Solo Admin
// ✅ ASEGÚRATE QUE ESTA LÍNEA SEA IDÉNTICA A LA DE TU dashboard_admin.php
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'administrador') { // O 'administrador'
    http_response_code(403);
    echo json_encode(['success' => false, 'mensaje' => 'Acceso denegado.']);
    exit();
}
if (!isset($_GET['id_encuestador']) || !filter_var($_GET['id_encuestador'], FILTER_VALIDATE_INT)) {
     http_response_code(400);
    echo json_encode(['success' => false, 'mensaje' => 'Se requiere un ID de encuestador válido.']);
    exit();
}

$id_encuestador = (int)$_GET['id_encuestador'];

$controlador = new EncuestaController($conexion);
$respuesta = $controlador->obtenerEncuestasPorEncuestador($id_encuestador); 

http_response_code($respuesta['estado']);
echo json_encode($respuesta);
$conexion->close();
?>