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

    /**
     * Procesa el registro de un nuevo encuestador (por el admin).
     * @param array $datos Datos recibidos (nombre, email, asignatura, contrasena).
     * @return array Respuesta con estado y mensaje.
     */
    public function registrarEncuestador($datos) {
        // Validación de campos requeridos para encuestador
        $campos_requeridos = ['nombre', 'email', 'asignatura', 'contrasena'];
        foreach ($campos_requeridos as $campo) {
            if (empty($datos[$campo])) {
                return ['estado' => 400, 'success' => false, 'mensaje' => "El campo '$campo' es obligatorio."];
            }
        }

        // Validación de formato de email
        if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'El formato del correo no es válido.'];
        }

        // Validación de contraseña (igual que alumnos)
        if (strlen($datos['contrasena']) < 8 || !preg_match('/AL$/', $datos['contrasena'])) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'La contraseña debe tener al menos 8 caracteres y terminar con "AL".'];
        }

        // Verificar si el email ya existe
        if ($this->modeloUsuario->findByEmail($datos['email'])) {
            return ['estado' => 409, 'success' => false, 'mensaje' => 'Este correo electrónico ya está registrado.'];
        }

        // Si todo es válido, intentar crear el encuestador
        if ($this->modeloUsuario->createEncuestador($datos)) {
            return ['estado' => 201, 'success' => true, 'mensaje' => 'Encuestador registrado con éxito.'];
        } else {
            return ['estado' => 500, 'success' => false, 'mensaje' => 'Error al registrar el encuestador.', 'error_db' => $this->conexion->error];
        }
    }

    /**
     * Procesa el inicio de sesión del usuario.
     * @param array $datos Contiene 'email' y 'contrasena'.
     * @return array Array de respuesta con estado, éxito, mensaje y datos del usuario/info de redirección.
     */
    public function login($datos) {
        // Validación básica
        if (empty($datos['email']) || empty($datos['contrasena'])) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Correo y contraseña son requeridos.'];
        }

        // Buscar usuario por email
        $usuario = $this->modeloUsuario->findByEmail($datos['email']);

        // Verificar si el usuario existe Y la contraseña es correcta
        if ($usuario && password_verify($datos['contrasena'], $usuario['contrasena_hash'])) {
            // ¡Contraseña correcta!

            // Eliminar datos sensibles antes de devolver
            unset($usuario['contrasena_hash']);

            // Verificar si es un encuestador con contraseña temporal
            if ($usuario['rol'] === 'encuestador' && $usuario['password_temporal'] == TRUE) {
                // Indicamos al frontend que redirija a la página de cambio de contraseña
                return [
                    'estado' => 200, 
                    'success' => true, 
                    'mensaje' => 'Login exitoso. Debes cambiar tu contraseña temporal.', 
                    'usuario' => $usuario,
                    'accion_requerida' => 'cambiar_contrasena' // Señal para redirección
                ];
            } else {
                // Login normal para admin, alumno o encuestador con contraseña ya cambiada
                return [
                    'estado' => 200, 
                    'success' => true, 
                    'mensaje' => 'Login exitoso.', 
                    'usuario' => $usuario 
                ];
            }

        } else {
            // Usuario no encontrado o contraseña incorrecta
            return ['estado' => 401, 'success' => false, 'mensaje' => 'Credenciales incorrectas.']; // 401 Unauthorized
        }
    }
}
?>