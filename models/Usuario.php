<?php
// models/Usuario.php

class Usuario {
    private $conexion;

    public function __construct($db) {
        $this->conexion = $db;
    }

    /**
     * Busca un usuario por su email.
     * @param string $email
     * @return array|null Null si no lo encuentra.
     */
    public function findByEmail($email) {
        $stmt = $this->conexion->prepare("SELECT id_usuario FROM Usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc();
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
}
?>