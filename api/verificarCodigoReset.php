<?php
// api/verificarCodigoReset.php
session_start(); // Aunque no la usemos directamente, es buena práctica iniciarla
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once '../config/db.php';
require_once '../models/Usuario.php'; // Solo necesitamos el modelo

// Seguridad básica: Solo método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
    exit();
}

$datos = json_decode(file_get_contents("php://input"), true);
$code = $datos['code'] ?? null;

// Validar que el código tenga el formato correcto (4 dígitos)
if (empty($code) || !preg_match('/^\d{4}$/', $code)) {
     http_response_code(400);
     echo json_encode(['success' => false, 'mensaje' => 'Formato de código inválido.']);
     exit();
}

$modeloUsuario = new Usuario($conexion);
// Usamos la función que ya existía en el modelo
$usuario = $modeloUsuario->buscarPorResetCode($code);

if ($usuario) {
    // Código VÁLIDO y NO EXPIRADO
    http_response_code(200);
    echo json_encode(['success' => true, 'mensaje' => 'Código verificado correctamente.']);
} else {
    // Código INVÁLIDO o EXPIRADO
    http_response_code(400); // Bad Request o 404 Not Found podrían ser alternativas
    echo json_encode(['success' => false, 'mensaje' => 'Código inválido o expirado.']);
}

$conexion->close();
?>