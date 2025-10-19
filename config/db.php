<?php
// -- Configuración de la base de datos --
$db_host = 'localhost';
$db_usuario = 'root';
$db_contrasena = '';
$db_nombre = 'encuestas_db'; 

// -- Crear la conexión --
$conexion = new mysqli($db_host, $db_usuario, $db_contrasena, $db_nombre);

// -- Establecer UTF-8 --
$conexion->set_charset("utf8mb4");

// -- Verificar conexión --
if ($conexion->connect_error) {
    // Es mejor no mostrar errores detallados en producción, pero útil para desarrollo.
    // Considera loggear el error en lugar de mostrarlo.
    header('Content-Type: application/json');
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'mensaje' => 'Error de conexión a la base de datos.']);
    exit(); // Detiene la ejecución si no hay conexión
}

// La variable $conexion estará disponible al incluir este archivo.
?>