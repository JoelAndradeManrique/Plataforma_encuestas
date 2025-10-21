<?php
// models/Usuario.php

class Usuario {
    private $conexion;

    public function __construct($db) {
        $this->conexion = $db;
    }

    /**
     * Crea un nuevo usuario (alumno) en la base de datos.
     * @param array $datos Array asociativo con los datos del alumno.
     * @return bool True si fue exitoso, false si no.
     */
    public function createAlumno($datos) {
        $query = "INSERT INTO Usuarios (nombre, apellido, email, contrasena_hash, rol, genero, carrera) VALUES (?, ?, ?, ?, 'alumno', ?, ?)";
        $stmt = $this->conexion->prepare($query);

        // Encriptar contraseña
        $contrasena_hash = password_hash($datos['contrasena'], PASSWORD_DEFAULT);

        $stmt->bind_param("ssssss",
            $datos['nombre'],
            $datos['apellido'],
            $datos['email'],
            $contrasena_hash,
            $datos['genero'],
            $datos['carrera']
        );

        return $stmt->execute();
    }

    /**
     * Crea un nuevo usuario (encuestador) en la base de datos.
     * @param array $datos Array asociativo con los datos del encuestador.
     * @return bool True si fue exitoso, false si no.
     */
   public function createEncuestador($datos) {
        // La consulta ahora incluye la nueva columna password_temporal
        $query = "INSERT INTO Usuarios (nombre, apellido, email, contrasena_hash, rol, asignatura, password_temporal) VALUES (?, ?, ?, ?, 'encuestador', ?, TRUE)"; // <-- TRUE al final
        $stmt = $this->conexion->prepare($query);

        $contrasena_hash = password_hash($datos['contrasena'], PASSWORD_DEFAULT);
        
        $nombreCompleto = explode(' ', $datos['nombre'], 2);
        $nombre = $nombreCompleto[0];
        $apellido = $nombreCompleto[1] ?? '';

        // Ahora son 6 parámetros
        $stmt->bind_param("sssss",
            $nombre,
            $apellido,
            $datos['email'],
            $contrasena_hash,
            $datos['asignatura']
        );

        return $stmt->execute();
    }
    
     /**
     * Busca un usuario por email y devuelve datos relevantes para login.
     * @return array|null
     */
    public function findByEmail($email) {
        // Añadimos las nuevas columnas a la consulta
        $query = "SELECT id_usuario, nombre, apellido, email, contrasena_hash, rol, password_temporal, 
                         failed_login_attempts, last_failed_login_attempt 
                  FROM Usuarios WHERE email = ?";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc();
    }

    /**
     * Actualiza la bandera password_temporal para un usuario.
     * @param int $id_usuario El ID del usuario.
     * @return bool True en éxito, false si falla.
     */
    public function markPasswordAsChanged($id_usuario) {
        $stmt = $this->conexion->prepare("UPDATE Usuarios SET password_temporal = FALSE WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        return $stmt->execute();
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
     * Obtiene los datos de un usuario por su ID (sin la contraseña).
     * @param int $id_usuario
     * @return array|null Null si no se encuentra.
     */
    public function getById($id_usuario) {
        $query = "SELECT id_usuario, nombre, apellido, email, rol, genero, carrera, asignatura, created_at 
                  FROM Usuarios WHERE id_usuario = ?";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc();
    }

    /**
     * Actualiza los datos básicos de un usuario (Admin puede editar otros).
     * No actualiza contraseña ni rol aquí.
     * @param int $id_usuario ID del usuario a editar.
     * @param array $datos Nuevos datos (nombre, apellido, email, genero, carrera, asignatura).
     * @return int Número de filas afectadas (-1 error, 0 sin cambios, 1 éxito).
     */
    public function updateUser($id_usuario, $datos) {
        // Construimos la consulta dinámicamente según el rol
        $query_parts = [];
        $params = [];
        $types = "";

        // Campos comunes
        if (isset($datos['nombre'])) {
            $query_parts[] = "nombre = ?";
            $types .= "s";
            $params[] = $datos['nombre'];
        }
        if (isset($datos['apellido'])) {
            $query_parts[] = "apellido = ?";
            $types .= "s";
            $params[] = $datos['apellido'];
        }
        if (isset($datos['email'])) {
            $query_parts[] = "email = ?";
            $types .= "s";
            $params[] = $datos['email'];
        }
        // Campos específicos de Alumno
        if (isset($datos['genero'])) {
            $query_parts[] = "genero = ?";
            $types .= "s";
            $params[] = $datos['genero'];
        }
        if (isset($datos['carrera'])) {
            $query_parts[] = "carrera = ?";
            $types .= "s";
            $params[] = $datos['carrera'];
        }
         // Campo específico de Encuestador
        if (isset($datos['asignatura'])) {
            $query_parts[] = "asignatura = ?";
            $types .= "s";
            $params[] = $datos['asignatura'];
        }

        if (empty($query_parts)) {
            return 0; // No hay nada que actualizar
        }

        $query = "UPDATE Usuarios SET " . implode(", ", $query_parts) . " WHERE id_usuario = ?";
        $types .= "i";
        $params[] = $id_usuario;

        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->affected_rows;
    }

    /**
     * Elimina un usuario de la base de datos por su ID.
     * @param int $id_usuario El ID del usuario a eliminar.
     * @return int El número de filas afectadas (-1 si hay error, 0 si no se encontró, 1 si se borró).
     */
    public function delete($id_usuario) {
        // Primero, intentamos borrar las respuestas asociadas (si es alumno)
        // Esto es opcional, pero previene errores si hay respuestas anónimas que quedaron con ID
        // O podríamos configurar la llave foránea para que haga esto en cascada (ON DELETE CASCADE)
        try {
            $stmt_resp = $this->conexion->prepare("DELETE FROM Respuestas WHERE id_alumno = ?");
            $stmt_resp->bind_param("i", $id_usuario);
            $stmt_resp->execute();
            $stmt_resp->close();
        } catch (Exception $e) {
            // Ignorar error si no hay respuestas
        }

        // Ahora, borramos el usuario
        $stmt = $this->conexion->prepare("DELETE FROM Usuarios WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        return $stmt->affected_rows;
    }

    /**
     * Guarda un token de reseteo y su fecha de expiración para un usuario.
     * @param string $email
     * @param string $token
     * @param string $fechaExpiracion (Formato 'Y-m-d H:i:s')
     * @return bool
     */
    public function guardarResetToken($email, $token, $fechaExpiracion) {
        $stmt = $this->conexion->prepare("UPDATE Usuarios SET reset_token = ?, reset_token_expires = ? WHERE email = ?");
        $stmt->bind_param("sss", $token, $fechaExpiracion, $email);
        return $stmt->execute();
    }

    /**
     * Busca un usuario por su token de reseteo y verifica que no haya expirado.
     * @param string $token
     * @return array|null Null si no es válido.
     */
    public function buscarPorResetToken($token) {
        $query = "SELECT id_usuario, email FROM Usuarios WHERE reset_token = ? AND reset_token_expires > NOW()";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc();
    }

    /**
     * Actualiza la contraseña de un usuario y limpia el token de reseteo.
     * @param int $id_usuario
     * @param string $contrasena_hash Nueva contraseña hasheada.
     * @return int Número de filas afectadas.
     */
    public function updatePassword($id_usuario, $contrasena_hash) {
        $query = "UPDATE Usuarios SET contrasena_hash = ?, reset_token = NULL, reset_token_expires = NULL, password_temporal = FALSE WHERE id_usuario = ?";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("si", $contrasena_hash, $id_usuario);
        $stmt->execute();
        return $stmt->affected_rows;
    }

   

    /**
     * Incrementa el contador de intentos fallidos y actualiza la fecha.
     * @param int $id_usuario
     * @return bool
     */
    public function incrementFailedAttempts($id_usuario) {
        $query = "UPDATE Usuarios SET failed_login_attempts = failed_login_attempts + 1, 
                         last_failed_login_attempt = NOW() 
                  WHERE id_usuario = ?";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("i", $id_usuario);
        return $stmt->execute();
    }

    /**
     * Resetea el contador de intentos fallidos.
     * @param int $id_usuario
     * @return bool
     */
    public function resetFailedAttempts($id_usuario) {
        $query = "UPDATE Usuarios SET failed_login_attempts = 0, 
                         last_failed_login_attempt = NULL 
                  WHERE id_usuario = ?";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("i", $id_usuario);
        return $stmt->execute();
    }
}

?>