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
     * Guarda un token Y un código de reseteo y su fecha de expiración para un usuario.
     * Sobrescribe los anteriores, invalidándolos.
     * @param string $email
     * @param string $token Token largo.
     * @param string $code Código corto (4 dígitos).
     * @param string $fechaExpiracion (Formato 'Y-m-d H:i:s')
     * @return bool
     */
    public function guardarResetToken($email, $token, $code, $fechaExpiracion) {
        // Esta consulta actualiza (o inserta si no existe) el token y el código
        $stmt = $this->conexion->prepare("UPDATE Usuarios SET
                                            reset_token = ?,
                                            reset_code = ?,
                                            reset_token_expires = ?
                                          WHERE email = ?");
        // Son 4 parámetros ahora: token(s), code(s), fecha(s), email(s)
        $stmt->bind_param("ssss", $token, $code, $fechaExpiracion, $email);
        return $stmt->execute();
    }

    /**
     * Busca un usuario por su token de reseteo y verifica que no haya expirado.
     * @param string $token
     * @return array|null Null si no es válido.
     */
    public function buscarPorResetToken($token) {
        // La consulta sigue igual: busca por token y verifica expiración
        $query = "SELECT id_usuario, email FROM Usuarios
                  WHERE reset_token = ? AND reset_token_expires > NOW()";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $resultado = $stmt->get_result();
        // No necesitamos limpiar el código aquí, lo haremos en updatePassword
        return $resultado->fetch_assoc();
    }

    /**
     * Busca un usuario por su código de reseteo (4 dígitos) y verifica que no haya expirado.
     * @param string $code El código de 4 dígitos.
     * @return array|null Null si no es válido o no encontrado.
     */
    public function buscarPorResetCode($code) {
        // Busca por código y verifica expiración (usa la misma columna de expiración)
        $query = "SELECT id_usuario, email FROM Usuarios
                  WHERE reset_code = ? AND reset_token_expires > NOW()";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("s", $code); // El código es string
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc();
    }

    /**
     * Actualiza la contraseña de un usuario y limpia el token Y el código de reseteo.
     * @param int $id_usuario
     * @param string $contrasena_hash Nueva contraseña hasheada.
     * @return int Número de filas afectadas.
     */
    public function updatePassword($id_usuario, $contrasena_hash) {
        // Limpiamos reset_token y reset_code
        $query = "UPDATE Usuarios SET
                    contrasena_hash = ?,
                    reset_token = NULL,
                    reset_code = NULL,
                    reset_token_expires = NULL,
                    password_temporal = FALSE
                  WHERE id_usuario = ?";
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

   /**
     * Crea un nuevo usuario (ADMINISTRADOR) en la base de datos.
     * @param array $datos Array asociativo con los datos del admin.
     * @return bool True si fue exitoso, false si no.
     */
    public function createAdmin($datos) {
        
        // --- ✅ CORRECCIÓN CLAVE ---
        // Usamos el rol 'administrator' (largo) como está en tu ENUM
        $query = "INSERT INTO Usuarios (nombre, apellido, email, contrasena_hash, rol, password_temporal) 
                  VALUES (?, ?, ?, ?, 'administrator', FALSE)";
        // --- FIN CORRECCIÓN ---
        
        $stmt = $this->conexion->prepare($query);

        if (!$stmt) {
             error_log("Error al preparar la consulta createAdmin: " . $this->conexion->error);
             return false;
        }

        // Encriptar contraseña
        $contrasena_hash = password_hash($datos['contrasena'], PASSWORD_DEFAULT);

        $stmt->bind_param("ssss",
            $datos['nombre'],
            $datos['apellido'],
            $datos['email'],
            $contrasena_hash
        );

        $success = $stmt->execute();
        if (!$success) {
             error_log("Error al ejecutar createAdmin: " . $stmt->error);
        }
        $stmt->close();
        return $success;
    }
}

?>