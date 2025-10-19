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
     * Busca un usuario por email y devuelve sus datos relevantes para el login.
     * @param string $email El email a buscar.
     * @return array|null Datos del usuario si se encuentra, null si no.
     */
    public function findByEmail($email) {
        // Selecciona todos los campos necesarios para login y sesión
        $query = "SELECT id_usuario, nombre, apellido, email, contrasena_hash, rol, password_temporal 
                  FROM Usuarios WHERE email = ?";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc(); // Devuelve los datos como array asociativo o null
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
}

?>