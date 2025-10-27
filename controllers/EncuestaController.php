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
     * @param array $datos Datos de la encuesta (incluyendo id_encuestador desde la sesión y opcionalmente estado).
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

        // Permitir crear sin preguntas si es borrador? Por ahora requerimos al menos una.
        if (empty($datos['preguntas']) || !is_array($datos['preguntas'])) {
             return ['estado' => 400, 'success' => false, 'mensaje' => 'La encuesta debe tener al menos una pregunta.'];
        }

        // Validación opcional del estado (si viene)
        if (isset($datos['estado']) && !in_array($datos['estado'], ['publicada', 'borrador'])) {
             return ['estado' => 400, 'success' => false, 'mensaje' => 'Estado no válido. Debe ser "publicada" o "borrador".'];
        }

        // Validación más profunda de preguntas
        $tipos_validos = ['opcion_multiple', 'seleccion_multiple', 'escala', 'abierta', 'si_no'];
        foreach($datos['preguntas'] as $pregunta) {
            if(empty($pregunta['texto_pregunta']) || empty($pregunta['tipo_pregunta'])) {
                 return ['estado' => 400, 'success' => false, 'mensaje' => 'Todas las preguntas deben tener texto y tipo.'];
            }
            if(!in_array($pregunta['tipo_pregunta'], $tipos_validos)) {
                 return ['estado' => 400, 'success' => false, 'mensaje' => "Tipo de pregunta no válido: " . htmlspecialchars($pregunta['tipo_pregunta'])]; // Sanitize output
            }
            // Podríamos añadir validación de opciones aquí si quisiéramos ser más estrictos
        }

        // Si todo es válido, intentar crear en la DB
        $id_encuesta = $this->modeloEncuesta->create($datos);

        if ($id_encuesta) {
            return [
                'estado' => 201, // 201 Created
                'success' => true,
                'mensaje' => 'Encuesta guardada con éxito.', // Cambiado mensaje a 'guardada'
                'id_encuesta' => $id_encuesta
            ];
        } else {
            // Obtener el error específico si es posible (depende de cómo manejes errores en el modelo)
            $db_error = property_exists($this->conexion, 'error') ? $this->conexion->error : 'Error desconocido en DB.';
            return [
                'estado' => 500, // Internal Server Error
                'success' => false,
                'mensaje' => 'Error al guardar la encuesta en la base de datos.',
                'error_db' => $db_error // Incluir error de DB para depuración (¡cuidado en producción!)
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

    /**
     * Obtiene la lista de encuestas públicas para los alumnos.
     * @param int $id_alumno ID del alumno actual.
     * @param string|null $searchTerm Término de búsqueda opcional.
     */
    public function listarEncuestasPublicas($id_alumno, $searchTerm = null) { // Añadido $id_alumno
        // Validar id_alumno (básico)
        if (empty($id_alumno)) {
             return ['estado' => 400, 'success' => false, 'mensaje' => 'Se requiere ID de alumno.'];
        }
        try {
            // ✅ Pasar ambos parámetros al modelo
            $encuestas = $this->modeloEncuesta->getPublicas($id_alumno, $searchTerm);

            return [
                'estado' => 200,
                'success' => true,
                'encuestas' => $encuestas
            ];
        } catch (Exception $e) {
            return ['estado' => 500, 'success' => false, 'mensaje' => 'Error al obtener las encuestas.'];
        }
    }
    /**
     * Obtiene el detalle (preguntas/opciones) de una encuesta para responder.
     */
    public function obtenerEncuestaParaResponder($id_encuesta) {
        if (empty($id_encuesta)) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'ID de encuesta no válido.'];
        }

        $encuesta_detalle = $this->modeloEncuesta->getDetallePublico($id_encuesta);

        if ($encuesta_detalle === null) {
            return ['estado' => 404, 'success' => false, 'mensaje' => 'Encuesta no encontrada o no está disponible (puede estar cerrada o archivada).'];
        }

        return [
            'estado' => 200, 
            'success' => true, 
            'encuesta' => $encuesta_detalle
        ];
    }

    /**
     * Recibe y guarda las respuestas de un alumno.
     */
    public function recibirRespuestas($datos, $id_alumno_real) {
        // Validaciones
        if (empty($datos['id_encuesta']) || empty($datos['modo_respuesta']) || empty($datos['respuestas'])) {
             return ['estado' => 400, 'success' => false, 'mensaje' => 'Faltan datos clave (id_encuesta, modo_respuesta, respuestas).'];
        }
        if ($datos['modo_respuesta'] !== 'identificado' && $datos['modo_respuesta'] !== 'anonimo') {
             return ['estado' => 400, 'success' => false, 'mensaje' => 'Modo de respuesta no válido.'];
        }

        // Llamar al modelo para guardar
        $exito = $this->modeloEncuesta->guardarRespuestas(
            $datos['id_encuesta'],
            $id_alumno_real,
            $datos['modo_respuesta'],
            $datos['respuestas']
        );

        if ($exito) {
            return ['estado' => 201, 'success' => true, 'mensaje' => 'Respuestas guardadas con éxito.'];
        } else {
            return ['estado' => 500, 'success' => false, 'mensaje' => 'Error al guardar las respuestas.'];
        }
    }

    /**
     * Obtiene las respuestas de un alumno para una encuesta específica.
     * @param int $id_encuesta El ID de la encuesta.
     * @param int $id_alumno El ID del alumno (de la sesión).
     * @return array Respuesta con estado y datos.
     */
    public function obtenerMisRespuestas($id_encuesta, $id_alumno) {
        if (empty($id_encuesta) || empty($id_alumno)) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Faltan datos requeridos.'];
        }

        try {
            $respuestas = $this->modeloEncuesta->getRespuestasAlumno($id_encuesta, $id_alumno);

            if ($respuestas === null) {
                // No respondió identificado
                return ['estado' => 404, 'success' => false, 'mensaje' => 'No se encontraron respuestas identificadas para esta encuesta.'];
            }
            // --- ✅ Añadido: Chequeo si el modelo devolvió error 'false' ---
            if ($respuestas === false) {
                 error_log("modeloEncuesta->getRespuestasAlumno returned false for encuesta $id_encuesta, alumno $id_alumno");
                 // Indicar error interno
                 return ['estado' => 500, 'success' => false, 'mensaje' => 'Error de base de datos al obtener respuestas. Revise los logs del servidor.'];
            }
            // --- Fin añadido ---

            // Éxito
            return [
                'estado' => 200,
                'success' => true,
                'respuestas_alumno' => $respuestas
            ];

        } catch (Exception $e) {
            error_log("Exception in obtenerMisRespuestas: " . $e->getMessage()); // Registrar excepción
            return [
                'estado' => 500,
                'success' => false,
                'mensaje' => 'Error al procesar las respuestas.',
                'error_db' => $e->getMessage() // Opcional: Enviar mensaje de error (cuidado en producción)
            ];
        }
    }

    /**
     * Obtiene el historial de encuestas respondidas (identificadas) por un alumno.
     * @param int $id_alumno El ID del alumno (de la sesión).
     * @return array Respuesta con estado y datos.
     */
    public function listarEncuestasRespondidas($id_alumno) {
        if (empty($id_alumno)) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'ID de alumno no válido.'];
        }

        try {
            $encuestas = $this->modeloEncuesta->getEncuestasRespondidasPorAlumno($id_alumno);
            
            // Devolverá un array vacío [] si no ha respondido ninguna
            return [
                'estado' => 200, 
                'success' => true, 
                'encuestas_respondidas' => $encuestas
            ];

        } catch (Exception $e) {
            return [
                'estado' => 500, 
                'success' => false, 
                'mensaje' => 'Error al obtener el historial de encuestas.',
                'error_db' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene los datos completos de una encuesta para poder editarla.
     * Verifica que sea un borrador y pertenezca al encuestador.
     * @param int $id_encuesta El ID de la encuesta.
     * @param int $id_encuestador El ID del encuestador (de la sesión).
     * @return array Respuesta con estado y datos.
     */
    public function obtenerEncuestaParaEditar($id_encuesta, $id_encuestador) {
        if (empty($id_encuesta) || empty($id_encuestador)) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Faltan IDs requeridos.'];
        }

        try {
            $encuesta_editable = $this->modeloEncuesta->getEditableDetails($id_encuesta, $id_encuestador);

            if ($encuesta_editable === null) {
                // No encontrado, no es borrador, o no es propietario
                return ['estado' => 404, 'success' => false, 'mensaje' => 'Borrador de encuesta no encontrado, no es tuyo, o ya fue publicado.'];
            }
            if ($encuesta_editable === false) {
                 // Error de base de datos en el modelo
                 return ['estado' => 500, 'success' => false, 'mensaje' => 'Error de base de datos al obtener los detalles.'];
            }

            // ¡Éxito! Devolver el JSON con todos los datos
            return [
                'estado' => 200,
                'success' => true,
                'encuesta' => $encuesta_editable // Contiene todo: encuesta, preguntas, opciones
            ];

        } catch (Exception $e) {
            error_log("Exception in obtenerEncuestaParaEditar: " . $e->getMessage());
            return [
                'estado' => 500,
                'success' => false,
                'mensaje' => 'Error al procesar la solicitud de edición.',
                'error_db' => $e->getMessage() // Opcional
            ];
        }
    }
}
?>