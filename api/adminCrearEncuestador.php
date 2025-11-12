<?php
// api/adminCrearEncuestador.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once '../config/db.php';
require_once '../controllers/UsuarioController.php';

// --- Seguridad: Solo Administradores ---
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'administrador') {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'mensaje' => 'Acceso denegado. Se requiere rol de administrador.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
    exit();
}

$datos = json_decode(file_get_contents("php://input"), true);

// --- Validación de datos recibidos del formulario del admin ---
// (Tu controlador 'registrarEncuestador' ya valida esto, pero una doble
// validación aquí para los campos específicos que mencionaste es buena)

// El controlador espera "nombre" (completo) y "asignatura".
// El campo "carrera" no está en tu función 'registrarEncuestador' actual.
// Por ahora, asumiré que "materia que imparte" es 'asignatura'.
$campos_requeridos = ['nombre', 'apellido', 'email', 'carrera', 'asignatura', 'contrasena'];
foreach ($campos_requeridos as $campo) {
    if (empty($datos[$campo])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'mensaje' => "Falta el campo '$campo'."]);
        exit();
    }
}

// Combinamos nombre y apellido para el controlador (si los manda separados)
// Tu controlador actual espera "nombre" (completo) y lo divide él mismo.
// Vamos a ajustarnos a lo que el controlador YA espera:
$datos_para_controlador = [
    'nombre' => trim($datos['nombre']),
    'apellido' => trim($datos['apellido']), 
    'email' => trim($datos['email']),
    'carrera' => $datos['carrera'],
    'asignatura' => trim($datos['asignatura']),
    'contrasena' => $datos['contrasena'],

    // ✅ ¡AÑADE ESTA LÍNEA QUE FALTABA!
    // Esto toma el '1' del JS y lo pasa al controlador.
    'password_temporal' => $datos['password_temporal'] ?? 0 
];

if (isset($datos_para_controlador['password_temporal'])) {
    error_log("API: password_temporal SÍ ESTÁ PRESENTE. Valor: " . $datos_para_controlador['password_temporal']);
} else {
    error_log("API: password_temporal NO LLEGÓ a datos_para_controlador.");
}

$controlador = new UsuarioController($conexion);
$respuesta = $controlador->registrarEncuestador($datos_para_controlador);

http_response_code($respuesta['estado']);
echo json_encode($respuesta);

$conexion->close();
?>