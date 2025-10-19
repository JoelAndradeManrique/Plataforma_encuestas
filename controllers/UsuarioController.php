<?php
// controllers/UsuarioController.php

require_once '../models/Usuario.php';

class UsuarioController {
    private $modeloUsuario;
    public $conexion; // Necesario para acceder a $conexion->error

    public function __construct($db) {
        $this->conexion = $db;
        $this->modeloUsuario = new Usuario($db);
    }

    /**
     * Procesa el registro de un nuevo alumno.
     * @param array $datos Datos recibidos del formulario.
     * @return array Respuesta con estado y mensaje.
     */
    public function registrarAlumno($datos) {
        // Validación básica de campos requeridos
        $campos_requeridos = ['nombre', 'apellido', 'email', 'genero', 'carrera', 'contrasena', 'confirmarContrasena'];
        foreach ($campos_requeridos as $campo) {
            if (empty($datos[$campo])) {
                return ['estado' => 400, 'success' => false, 'mensaje' => 'Todos los campos son obligatorios.'];
            }
        }

        // Validación de formato de email
        if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'El formato del correo no es válido.'];
        }

        // Validación de coincidencia de contraseñas
        if ($datos['contrasena'] !== $datos['confirmarContrasena']) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Las contraseñas no coinciden.'];
        }

        // Validación de complejidad de contraseña (mínimo 8 chars, termina en AL)
        if (strlen($datos['contrasena']) < 8 || !preg_match('/AL$/', $datos['contrasena'])) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'La contraseña debe tener al menos 8 caracteres y terminar con "AL".'];
        }

        // Verificar si el email ya existe
        if ($this->modeloUsuario->findByEmail($datos['email'])) {
            return ['estado' => 409, 'success' => false, 'mensaje' => 'Este correo electrónico ya está registrado.']; // 409 Conflict
        }

        // Si todo es válido, intentar crear el alumno
        if ($this->modeloUsuario->createAlumno($datos)) {
            return ['estado' => 201, 'success' => true, 'mensaje' => 'Registro exitoso. Ahora puedes iniciar sesión.']; // 201 Created
        } else {
            return ['estado' => 500, 'success' => false, 'mensaje' => 'Error al registrar el usuario.', 'error_db' => $this->conexion->error]; // 500 Internal Server Error
        }
    }
}
?>