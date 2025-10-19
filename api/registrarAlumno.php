<?php
// api/registrarAlumno.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// Incluir archivos necesarios
require_once '../config/db.php';
require_once '../controllers/UsuarioController.php';

// Solo aceptar peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
    exit();
}

// Obtener los datos enviados (JSON)
$datos = json_decode(file_get_contents("php://input"), true);

// Crear controlador y procesar registro
$controlador = new UsuarioController($conexion);
$respuesta = $controlador->registrarAlumno($datos);

// Enviar respuesta
http_response_code($respuesta['estado']);
echo json_encode($respuesta);

$conexion->close();
?>