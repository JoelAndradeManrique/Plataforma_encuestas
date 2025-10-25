<?php
// controllers/EncuestaController.php

require_once '../models/Encuesta.php';

class EncuestaController {
    private $modeloEncuesta;
    public $conexion;

    public function __construct($db) {
        $this->conexion = $db;
        $this->modeloEncuesta = new Encuesta($db);
    }

    /**
     * Procesa la creación de una nueva encuesta.
     * @param array $datos Datos de la encuesta (incluyendo id_encuestador desde la sesión).
     * @return array Respuesta con estado y mensaje.
     */
    public function crearNuevaEncuesta($datos) {
        // Validación básica
        if (empty($datos['titulo']) || empty($datos['visibilidad']) || empty($datos['id_encuestador'])) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Título, visibilidad e ID de encuestador son requeridos.'];
        }

        if ($datos['visibilidad'] !== 'identificada' && $datos['visibilidad'] !== 'anonima') {
             return ['estado' => 400, 'success' => false, 'mensaje' => 'Visibilidad no válida.'];
        }

        if (empty($datos['preguntas']) || !is_array($datos['preguntas'])) {
             return ['estado' => 400, 'success' => false, 'mensaje' => 'La encuesta debe tener al menos una pregunta.'];
        }

        // Validación más profunda de preguntas (opcional pero recomendado)
        $tipos_validos = ['opcion_multiple', 'seleccion_multiple', 'escala', 'abierta', 'si_no'];
        foreach($datos['preguntas'] as $pregunta) {
            if(empty($pregunta['texto_pregunta']) || empty($pregunta['tipo_pregunta'])) {
                 return ['estado' => 400, 'success' => false, 'mensaje' => 'Todas las preguntas deben tener texto y tipo.'];
            }
            if(!in_array($pregunta['tipo_pregunta'], $tipos_validos)) {
                 return ['estado' => 400, 'success' => false, 'mensaje' => "Tipo de pregunta no válido: " . $pregunta['tipo_pregunta']];
            }
        }
        
        // Si todo es válido, intentar crear en la DB
        $id_encuesta = $this->modeloEncuesta->create($datos);

        if ($id_encuesta) {
            return [
                'estado' => 201, // 201 Created
                'success' => true, 
                'mensaje' => 'Encuesta creada con éxito.',
                'id_encuesta' => $id_encuesta
            ];
        } else {
            return [
                'estado' => 500, // Internal Server Error
                'success' => false, 
                'mensaje' => 'Error al crear la encuesta en la base de datos.',
                'error_db' => $this->conexion->error
            ];
        }
    }

    /**
     * Procesa la actualización del estado de una encuesta (ej. cerrarla).
     * @param array $datos Debe contener 'id_encuesta', 'nuevo_estado' y 'id_encuestador'.
     * @return array Respuesta con estado y mensaje.
     */
    public function actualizarEstado($datos) {
        $estados_validos = ['publicada', 'cerrada', 'borrador'];
        if (empty($datos['id_encuesta']) || empty($datos['nuevo_estado']) || empty($datos['id_encuestador'])) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Faltan datos requeridos.'];
        }
        if (!in_array($datos['nuevo_estado'], $estados_validos)) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Estado no válido.'];
        }

        if ($this->modeloEncuesta->updateEstado($datos['id_encuesta'], $datos['nuevo_estado'], $datos['id_encuestador'])) {
            return ['estado' => 200, 'success' => true, 'mensaje' => 'Estado de la encuesta actualizado.'];
        } else {
            return ['estado' => 404, 'success' => false, 'mensaje' => 'No se pudo actualizar la encuesta. (Verifica que seas el propietario)'];
        }
    }

    /**
     * Obtiene todas las encuestas para un ID de encuestador específico.
     * @param int $id_encuestador El ID del encuestador (de la sesión).
     * @return array Respuesta con estado y datos.
     */
    public function obtenerEncuestasPorEncuestador($id_encuestador) {
        // Validar que el ID no esté vacío
        if (empty($id_encuestador)) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'ID de encuestador no proporcionado.'];
        }

        try {
            $encuestas = $this->modeloEncuesta->findByEncuestador($id_encuestador);
            
            // Si $encuestas es un array (incluso vacío), es un éxito
            return [
                'estado' => 200, 
                'success' => true, 
                'encuestas' => $encuestas // Devolverá un array vacío [] si no tiene ninguna
            ];

        } catch (Exception $e) {
            return [
                'estado' => 500, 
                'success' => false, 
                'mensaje' => 'Error al obtener las encuestas.',
                'error_db' => $e->getMessage()
            ];
        }
    }

    /**
     * Procesa la solicitud de "eliminar" (archivar) una encuesta.
     * @param int $id_encuesta El ID de la encuesta.
     * @param int $id_encuestador El ID del encuestador (de la sesión).
     * @return array Respuesta con estado y mensaje.
     */
    public function archivarEncuesta($id_encuesta, $id_encuestador) {
        if (empty($id_encuesta) || empty($id_encuestador)) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Faltan datos requeridos.'];
        }

        if ($this->modeloEncuesta->archiveSurvey($id_encuesta, $id_encuestador)) {
            return ['estado' => 200, 'success' => true, 'mensaje' => 'Encuesta eliminada con éxito.'];
        } else {
            return [
                'estado' => 404, 
                'success' => false, 
                'mensaje' => 'No se pudo eliminar la encuesta. (Verifica que seas el propietario)'
            ];
        }
    }

    /**
     * Obtiene los resultados de una encuesta.
     * @param int $id_encuesta El ID de la encuesta.
     * @param int $id_encuestador El ID del encuestador (de la sesión).
     * @return array Respuesta con estado y datos.
     */
    public function obtenerResultados($id_encuesta, $id_encuestador) {
        if (empty($id_encuesta) || empty($id_encuestador)) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Faltan datos requeridos.'];
        }

        try {
            $resultados = $this->modeloEncuesta->getResultados($id_encuesta, $id_encuestador);

            if ($resultados === null) {
                // No es propietario o la encuesta no existe
                return ['estado' => 404, 'success' => false, 'mensaje' => 'Encuesta no encontrada o no eres el propietario.'];
            }
            if ($resultados === false) {
                 return ['estado' => 500, 'success' => false, 'mensaje' => 'Error de base de datos.'];
            }

            // ¡Éxito! Devolver el JSON de resultados
            return [
                'estado' => 200, 
                'success' => true, 
                'resultados' => $resultados
            ];

        } catch (Exception $e) {
            return [
                'estado' => 500, 
                'success' => false, 
                'mensaje' => 'Error al procesar los resultados.',
                'error_db' => $e->getMessage()
            ];
        }
    }
}
?>