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

        // --- ✅ INICIO DE VALIDACIÓN DE CONTRASEÑA ACTUALIZADA ---
        $contrasena = $datos['contrasena'];
        $mensajeError = '';

        // 1. Mínimo 8 caracteres
        if (strlen($contrasena) < 8) {
            $mensajeError = 'La contraseña debe tener al menos 8 caracteres.';
        }
        // 2. Termina en AL (case-insensitive para ser robusto)
        else if (!preg_match('/AL$/i', $contrasena)) { 
            $mensajeError = 'La contraseña debe terminar con "AL".';
        }
        // 3. Un caracter especial
        else if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]+/', $contrasena)) {
            $mensajeError = 'La contraseña debe contener al menos un caracter especial (ej. !@#$%).';
        }

        // Si hay algún error, devolverlo
        if (!empty($mensajeError)) {
            return ['estado' => 400, 'success' => false, 'mensaje' => $mensajeError];
        }
        // --- ✅ FIN DE VALIDACIÓN DE CONTRASEÑA ---


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

        // --- ✅ NUEVA VALIDACIÓN DE DOMINIO ---
        // Verificamos si el email termina con "@tecmerida.com" (ignorando mayúsculas/minúsculas)
        if (!preg_match('/@tecmerida\.com$/i', $datos['email'])) {
            return [
                'estado' => 400, 
                'success' => false, 
                'mensaje' => 'El correo del encuestador debe pertenecer al dominio @tecmerida.com.'
            ];
        }
        // --- FIN DE LA NUEVA VALIDACIÓN ---


        // --- VALIDACIÓN DE CONTRASEÑA (La que ya teníamos) ---
        $contrasena = $datos['contrasena'];
        $mensajeError = '';

        if (strlen($contrasena) < 8) {
            $mensajeError = 'La contraseña debe tener al menos 8 caracteres.';
        }
        else if (!preg_match('/AL$/i', $contrasena)) { 
            $mensajeError = 'La contraseña debe terminar con "AL".';
        }
        else if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]+/', $contrasena)) {
            $mensajeError = 'La contraseña debe contener al menos un caracter especial (ej. !@#$%).';
        }

        if (!empty($mensajeError)) {
            return ['estado' => 400, 'success' => false, 'mensaje' => $mensajeError];
        }
        // --- FIN DE VALIDACIÓN DE CONTRASEÑA ---

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
        if (empty($datos['email']) || empty($datos['contrasena'])) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Correo y contraseña son requeridos.'];
        }

        // 1. Buscar usuario (ahora trae los datos de intentos fallidos)
        $usuario = $this->modeloUsuario->findByEmail($datos['email']);

        // Si el usuario NO existe, devolvemos error genérico SIN contar intento
        if (!$usuario) {
             return ['estado' => 401, 'success' => false, 'mensaje' => 'Credenciales incorrectas.'];
        }

        // 2. --- LÓGICA DE BLOQUEO ---
        $max_intentos = 3;
        $tiempo_bloqueo_segundos = 60; // 1 minuto

        // Verificamos si está bloqueado
        if ($usuario['failed_login_attempts'] >= $max_intentos) {
            // Calculamos cuánto tiempo ha pasado desde el último intento fallido
            $ultimo_intento_ts = strtotime($usuario['last_failed_login_attempt']);
            $tiempo_actual_ts = time();
            $diferencia_tiempo = $tiempo_actual_ts - $ultimo_intento_ts;

            if ($diferencia_tiempo < $tiempo_bloqueo_segundos) {
                // Si aún no ha pasado el minuto, devolvemos error de bloqueo
                $tiempo_restante = $tiempo_bloqueo_segundos - $diferencia_tiempo;
                return [
                    'estado' => 429, // Too Many Requests
                    'success' => false, 
                    'mensaje' => "Demasiados intentos fallidos. Por favor, espera " . ceil($tiempo_restante / 60) . " minuto(s)."
                ];
            }
            // Si ya pasó el tiempo de bloqueo, permitimos que intente de nuevo
            // (No reseteamos el contador aún, solo si acierta la contraseña)
        }
        // --- FIN LÓGICA DE BLOQUEO ---


        // 3. Verificar contraseña
        if (password_verify($datos['contrasena'], $usuario['contrasena_hash'])) {
            // Contraseña CORRECTA: Reseteamos intentos y procedemos al login
            $this->modeloUsuario->resetFailedAttempts($usuario['id_usuario']);
            
            unset($usuario['contrasena_hash']);
            unset($usuario['failed_login_attempts']); // No enviar estos datos al frontend
            unset($usuario['last_failed_login_attempt']);

            if ($usuario['rol'] === 'encuestador' && $usuario['password_temporal'] == TRUE) {
                return ['estado' => 200, 'success' => true, 'mensaje' => 'Login exitoso. Debes cambiar tu contraseña temporal.', 'usuario' => $usuario, 'accion_requerida' => 'cambiar_contrasena'];
            } else {
                return ['estado' => 200, 'success' => true, 'mensaje' => 'Login exitoso.', 'usuario' => $usuario ];
            }

        } else {
            // Contraseña INCORRECTA: Incrementamos intentos y devolvemos error
            $this->modeloUsuario->incrementFailedAttempts($usuario['id_usuario']);
            return ['estado' => 401, 'success' => false, 'mensaje' => 'Credenciales incorrectas.'];
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
        
        // --- ✅ BLOQUE MODIFICADO ---
        // Ahora devuelve un error 404 (No Encontrado) y 'success: false'
        // si el usuario no es encontrado.
        if (!$usuario) {
            return [
                'estado' => 404, 
                'success' => false, 
                'mensaje' => 'No existe un registro con ese correo.'
            ];
        }
        // --- FIN DEL BLOQUE MODIFICADO ---

        // Generar token y expiración (1 hora)
        $token = bin2hex(random_bytes(32));
        $expiracion = date('Y-m-d H:i:s', time() + 3600);

        if ($this->modeloUsuario->guardarResetToken($datos['email'], $token, $expiracion)) {
            
            // --- ✅ AJUSTE IMPORTANTE ---
            // El enlace debe apuntar a tu PÁGINA DE FRONTEND (la vista), 
            // no a la API de reseteo.
            // Asegúrate de que esta URL coincida con la ruta a tu archivo HTML/PHP de "resetear".
            $linkRecuperacion = "http://localhost/plataformaEncuestas/views/resetear-contrasena.php?token=" . $token;
            
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

        // --- ✅ INICIO DE VALIDACIÓN DE CONTRASEÑA ACTUALIZADA ---
        $contrasena = $datos['nueva_contrasena'];
        $mensajeError = '';

        // 1. Mínimo 8 caracteres
        if (strlen($contrasena) < 8) {
            $mensajeError = 'La contraseña debe tener al menos 8 caracteres.';
        }
        // 2. Termina en AL (case-insensitive)
        else if (!preg_match('/AL$/i', $contrasena)) { 
            $mensajeError = 'La contraseña debe terminar con "AL".';
        }
        // 3. Un caracter especial
        else if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]+/', $contrasena)) {
            $mensajeError = 'La contraseña debe contener al menos un caracter especial (ej. !@#$%).';
        }

        // Si hay algún error, devolverlo
        if (!empty($mensajeError)) {
            return ['estado' => 400, 'success' => false, 'mensaje' => $mensajeError];
        }
        // --- ✅ FIN DE VALIDACIÓN DE CONTRASEÑA ---

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

    /**
     * Procesa el cambio de contraseña para un usuario ya logueado (ej. temporal).
     * No usa token, usa la sesión.
     */
    public function cambiarContrasenaLogueado($datos) {
        // Validar campos
        if (empty($datos['id_usuario']) || empty($datos['nueva_contrasena']) || empty($datos['confirmar_contrasena'])) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Todos los campos son requeridos.'];
        }

        if ($datos['nueva_contrasena'] !== $datos['confirmar_contrasena']) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Las contraseñas no coinciden.'];
        }
        
        // --- VALIDACIÓN DE CONTRASEÑA (la misma que usamos en registro) ---
        $contrasena = $datos['nueva_contrasena'];
        $mensajeError = '';

        if (strlen($contrasena) < 8) {
            $mensajeError = 'La contraseña debe tener al menos 8 caracteres.';
        }
        else if (!preg_match('/AL$/i', $contrasena)) { 
            $mensajeError = 'La contraseña debe terminar con "AL".';
        }
        else if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]+/', $contrasena)) {
            $mensajeError = 'La contraseña debe contener al menos un caracter especial (ej. !@#$%).';
        }

        if (!empty($mensajeError)) {
            return ['estado' => 400, 'success' => false, 'mensaje' => $mensajeError];
        }
        // --- FIN VALIDACIÓN ---

        // Hashear y guardar
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        
        // Usamos la función del MODELO existente 'updatePassword'
        // (ya que esa función también pone password_temporal = FALSE)
        if ($this->modeloUsuario->updatePassword($datos['id_usuario'], $hash) > 0) {
            return ['estado' => 200, 'success' => true, 'mensaje' => 'Contraseña actualizada con éxito.'];
        } else {
            return ['estado' => 500, 'success' => false, 'mensaje' => 'Error al actualizar la contraseña.'];
        }
    }

    /**
     * CAMBIAR MI CONTRASEÑA (Desde Perfil)
     * Permite a un usuario logueado cambiar su contraseña proporcionando la actual.
     */
    public function cambiarMiContrasena($datos) {
        // Validaciones básicas
        if (empty($datos['id_usuario']) || empty($datos['contrasena_actual']) || empty($datos['nueva_contrasena']) || empty($datos['confirmar_contrasena'])) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Todos los campos son requeridos.'];
        }
        if ($datos['nueva_contrasena'] !== $datos['confirmar_contrasena']) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Las contraseñas nuevas no coinciden.'];
        }
        if ($datos['nueva_contrasena'] === $datos['contrasena_actual']) {
             return ['estado' => 400, 'success' => false, 'mensaje' => 'La nueva contraseña no puede ser igual a la actual.'];
        }

        // --- VALIDACIÓN DE NUEVA CONTRASEÑA (la misma regla) ---
        $contrasena = $datos['nueva_contrasena'];
        $mensajeError = '';
        if (strlen($contrasena) < 8) { $mensajeError = 'La contraseña debe tener al menos 8 caracteres.'; }
        else if (!preg_match('/AL$/i', $contrasena)) { $mensajeError = 'La contraseña debe terminar con "AL".'; }
        else if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]+/', $contrasena)) { $mensajeError = 'La contraseña debe contener al menos un caracter especial (ej. !@#$%).'; }
        if (!empty($mensajeError)) { return ['estado' => 400, 'success' => false, 'mensaje' => $mensajeError]; }
        // --- FIN VALIDACIÓN ---

        // 1. Obtener el hash actual del usuario desde la DB
        $query_hash = "SELECT contrasena_hash FROM usuarios WHERE id_usuario = ?";
        $stmt_hash = $this->conexion->prepare($query_hash);
        if (!$stmt_hash) { return ['estado' => 500, 'success' => false, 'mensaje' => 'Error al preparar consulta.']; }
        $stmt_hash->bind_param("i", $datos['id_usuario']);
        $stmt_hash->execute();
        $resultado = $stmt_hash->get_result()->fetch_assoc();
        $stmt_hash->close();

        if (!$resultado) {
            return ['estado' => 404, 'success' => false, 'mensaje' => 'Usuario no encontrado.'];
        }

        // 2. Verificar si la contraseña actual proporcionada coincide
        if (!password_verify($datos['contrasena_actual'], $resultado['contrasena_hash'])) {
            return ['estado' => 401, 'success' => false, 'mensaje' => 'La contraseña actual es incorrecta.']; // 401 Unauthorized
        }

        // 3. Si todo es correcto, hashear y actualizar la nueva contraseña
        $nuevo_hash = password_hash($datos['nueva_contrasena'], PASSWORD_DEFAULT);
        
        // Usamos la función del MODELO existente 'updatePassword'
        // (ya que esa función también pone password_temporal = FALSE, por si acaso)
        if ($this->modeloUsuario->updatePassword($datos['id_usuario'], $nuevo_hash) > 0) {
            return ['estado' => 200, 'success' => true, 'mensaje' => 'Contraseña actualizada con éxito.'];
        } else {
            return ['estado' => 500, 'success' => false, 'mensaje' => 'Error al actualizar la contraseña en la base de datos.'];
        }
    }
}
?>