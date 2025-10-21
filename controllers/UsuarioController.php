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

    /**
     * Obtiene los datos de un usuario específico para edición.
     * @param int $id_usuario
     * @return array
     */
    public function obtenerUsuarioPorId($id_usuario) {
        if (empty($id_usuario) || !is_numeric($id_usuario)) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Se requiere un ID de usuario válido.'];
        }

        $usuario = $this->modeloUsuario->getById($id_usuario);

        if ($usuario) {
            return ['estado' => 200, 'success' => true, 'usuario' => $usuario];
        } else {
            return ['estado' => 404, 'success' => false, 'mensaje' => 'Usuario no encontrado.']; // 404 Not Found
        }
    }

    /**
     * Procesa la edición de un usuario (por Admin).
     * @param array $datos Datos del usuario a actualizar, incluyendo id_usuario.
     * @return array
     */
    public function editarUsuario($datos) {
        if (empty($datos['id_usuario']) || !is_numeric($datos['id_usuario'])) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Se requiere el ID del usuario a editar.'];
        }
        $id_usuario = intval($datos['id_usuario']);

        // Opcional: Validar formato de email si se incluye
        if (isset($datos['email']) && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
             return ['estado' => 400, 'success' => false, 'mensaje' => 'El formato del correo no es válido.'];
        }
        
        // Opcional: Verificar si el nuevo email ya existe para otro usuario
        if (isset($datos['email'])) {
            $existingUser = $this->modeloUsuario->findByEmail($datos['email']);
            if ($existingUser && $existingUser['id_usuario'] != $id_usuario) {
                return ['estado' => 409, 'success' => false, 'mensaje' => 'El nuevo correo electrónico ya está en uso por otro usuario.'];
            }
        }

        $filasAfectadas = $this->modeloUsuario->updateUser($id_usuario, $datos);

        if ($filasAfectadas >= 0) {
            return ['estado' => 200, 'success' => true, 'mensaje' => 'Usuario actualizado con éxito.'];
        } else {
            return ['estado' => 500, 'success' => false, 'mensaje' => 'Error al actualizar el usuario.', 'error_db' => $this->conexion->error];
        }
    }

    /**
     * Procesa la eliminación de un usuario (por Admin).
     * @param array $datos Debe contener 'id_usuario'.
     * @return array
     */
    public function eliminarUsuario($datos) {
        if (empty($datos['id_usuario']) || !is_numeric($datos['id_usuario'])) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Se requiere el ID del usuario a eliminar.'];
        }
        $id_usuario = intval($datos['id_usuario']);

        // Opcional: Impedir que el admin se elimine a sí mismo
        // if ($id_usuario === $_SESSION['usuario']['id_usuario']) { ... }

        $filasAfectadas = $this->modeloUsuario->delete($id_usuario);

        if ($filasAfectadas > 0) {
            return ['estado' => 200, 'success' => true, 'mensaje' => 'Usuario eliminado con éxito.'];
        } elseif ($filasAfectadas === 0) {
            return ['estado' => 404, 'success' => false, 'mensaje' => 'Usuario no encontrado.'];
        } else {
             // Verificamos si es un error por llave foránea (ej. un encuestador con encuestas)
            if ($this->conexion->errno == 1451) {
                 return ['estado' => 409, 'success' => false, 'mensaje' => 'Conflicto: No se puede eliminar el usuario porque tiene registros asociados (encuestas creadas, etc.).'];
            }
            return ['estado' => 500, 'success' => false, 'mensaje' => 'Error al eliminar el usuario.', 'error_db' => $this->conexion->error];
        }
    }

    /**
     * Procesa la solicitud de recuperación de contraseña.
     */
    public function solicitarRecuperacion($datos) {
        if (empty($datos['email'])) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Se requiere el correo electrónico.'];
        }

        // Verificar si el usuario existe
        $usuario = $this->modeloUsuario->findByEmail($datos['email']);
        if (!$usuario) {
            // Mensaje genérico por seguridad (aunque podrías cambiarlo si prefieres)
            return ['estado' => 200, 'success' => true, 'mensaje' => 'Si tu correo está registrado, recibirás un enlace para recuperar tu contraseña.'];
        }

        // Generar token y expiración (1 hora)
        $token = bin2hex(random_bytes(32));
        $expiracion = date('Y-m-d H:i:s', time() + 3600);

        if ($this->modeloUsuario->guardarResetToken($datos['email'], $token, $expiracion)) {
            // Simulamos el envío del enlace devolviéndolo en la respuesta
            // Asegúrate de que la ruta a tu futuro archivo frontend sea correcta
            $linkRecuperacion = "http://localhost/plataformaEncuestas/api/resetearContrasena.php?token=" . $token;
            return [
                'estado' => 200,
                'success' => true,
                'mensaje' => 'Solicitud procesada. Revisa las instrucciones.',
                'simulacion_enlace' => $linkRecuperacion // Para pruebas
            ];
        } else {
            return ['estado' => 500, 'success' => false, 'mensaje' => 'Error al procesar la solicitud.'];
        }
    }

    /**
     * Procesa el reseteo de la contraseña usando un token.
     */
    public function resetearContrasena($datos) {
        if (empty($datos['token']) || empty($datos['nueva_contrasena']) || empty($datos['confirmar_contrasena'])) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Se requiere el token, la nueva contraseña y la confirmación.'];
        }

        if ($datos['nueva_contrasena'] !== $datos['confirmar_contrasena']) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Las contraseñas no coinciden.'];
        }

        // Validar nueva contraseña (igual que en registro)
        if (strlen($datos['nueva_contrasena']) < 8 || !preg_match('/AL$/', $datos['nueva_contrasena'])) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'La nueva contraseña debe tener al menos 8 caracteres y terminar con "AL".'];
        }

        // Buscar el usuario por el token (que ya valida la expiración)
        $usuario = $this->modeloUsuario->buscarPorResetToken($datos['token']);

        if ($usuario) {
            $hash = password_hash($datos['nueva_contrasena'], PASSWORD_DEFAULT);
            if ($this->modeloUsuario->updatePassword($usuario['id_usuario'], $hash) > 0) {
                return ['estado' => 200, 'success' => true, 'mensaje' => 'Contraseña actualizada con éxito. Ya puedes iniciar sesión.'];
            } else {
                return ['estado' => 500, 'success' => false, 'mensaje' => 'Error al actualizar la contraseña.'];
            }
        } else {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Token inválido o expirado. Solicita un nuevo enlace.'];
        }
    }
}
?>