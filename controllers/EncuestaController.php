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

    public function actualizarEstado($datos) {
    $estados_validos = ['publicada', 'cerrada', 'borrador'];

    if (empty($datos['id_encuesta']) || empty($datos['nuevo_estado'])) {
        return ['estado' => 400, 'success' => false, 'mensaje' => 'Faltan datos requeridos.'];
    }

    if (!in_array($datos['nuevo_estado'], $estados_validos)) {
        return ['estado' => 400, 'success' => false, 'mensaje' => 'Estado no válido.'];
    }

    // Verificar rol
    $esAdmin = ($_SESSION['rol'] ?? null) === 'admin';

    // Si es admin, no validar encuestador
    $idEncuestador = $esAdmin ? null : ($datos['id_encuestador'] ?? null);

    if (!$esAdmin && empty($idEncuestador)) {
        return ['estado' => 400, 'success' => false, 'mensaje' => 'Falta el ID del encuestador.'];
    }

    if ($this->modeloEncuesta->updateEstado($datos['id_encuesta'], $datos['nuevo_estado'], $idEncuestador, $esAdmin)) {
        return ['estado' => 200, 'success' => true, 'mensaje' => 'Estado de la encuesta actualizado.'];
    } else {
        return ['estado' => 404, 'success' => false, 'mensaje' => 'No se pudo actualizar la encuesta.'];
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
     * Obtiene los resultados de una encuesta. (Comprueba propiedad)
     * @param int $id_encuesta El ID de la encuesta.
     * @param int $id_encuestador_logueado El ID del usuario que solicita.
     * @param string $rol_logueado El ROL del usuario (opcional, para chequeos futuros)
     * @return array Respuesta con estado y datos.
     */
    public function obtenerResultados($id_encuesta, $id_encuestador_logueado, $rol_logueado = 'encuestador') { // Añadido rol
        if (empty($id_encuesta) || empty($id_encuestador_logueado)) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Faltan datos requeridos.'];
        }
        
        // Esta función SIEMPRE comprueba propiedad, es para encuestadores
        $esPropietario = $this->modeloEncuesta->checkSurveyOwnership($id_encuesta, $id_encuestador_logueado);
        if (!$esPropietario) {
            return ['estado' => 403, 'success' => false, 'mensaje' => 'Encuesta no encontrada o no eres el propietario.'];
        }

        try {
            // Pasamos el ID del propietario para la lógica interna
            $resultados = $this->modeloEncuesta->getResultados($id_encuesta, $id_encuestador_logueado);

            if ($resultados === null) { return ['estado' => 404, 'success' => false, 'mensaje' => 'No se encontraron resultados (null).']; }
            if ($resultados === false) { return ['estado' => 500, 'success' => false, 'mensaje' => 'Error de BD al obtener resultados.']; }
            return [ 'estado' => 200, 'success' => true, 'resultados' => $resultados ];
        } catch (Exception $e) {
             error_log("Exception in obtenerResultados: " . $e->getMessage());
             return [ 'estado' => 500, 'success' => false, 'mensaje' => 'Error al procesar los resultados.', 'error_db' => $e->getMessage() ];
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
     * Verifica que sea un borrador y pertenezca al usuario (A MENOS que sea admin).
     * @param int $id_encuesta El ID de la encuesta.
     * @param int $id_usuario El ID del usuario (de la sesión).
     * @param string $rol El ROL del usuario (de la sesión).
     * @return array Respuesta con estado y datos.
     */
    public function obtenerEncuestaParaEditar($id_encuesta, $id_usuario, $rol) {
        if (empty($id_encuesta) || empty($id_usuario)) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Faltan IDs requeridos.'];
        }

        try {
            // ✅ Modificamos la llamada al modelo para pasar el ROL
            $encuesta_editable = $this->modeloEncuesta->getEditableDetails($id_encuesta, $id_usuario, $rol);

            if ($encuesta_editable === null) {
                return ['estado' => 404, 'success' => false, 'mensaje' => 'Borrador de encuesta no encontrado, no te pertenece, o ya fue publicado.'];
            }
            if ($encuesta_editable === false) {
                 return ['estado' => 500, 'success' => false, 'mensaje' => 'Error de base de datos al obtener los detalles.'];
            }

            return [
                'estado' => 200,
                'success' => true,
                'encuesta' => $encuesta_editable
            ];

        } catch (Exception $e) {
            error_log("Exception in obtenerEncuestaParaEditar: " . $e->getMessage());
            return [
                'estado' => 500,
                'success' => false,
                'mensaje' => 'Error al procesar la solicitud de edición.',
                'error_db' => $e->getMessage()
            ];
        }
    }

/**
     * Obtiene las respuestas de un alumno específico PARA EL ENCUESTADOR o ADMIN.
     * @param int $id_encuesta
     * @param int $id_alumno_a_ver
     * @param int $id_usuario_logueado El ID del encuestador o admin
     * @param string $rol_logueado El ROL del usuario (encuestador o administrator)
     * @return array Respuesta con estado y datos.
     */
    public function obtenerRespuestasDeAlumno($id_encuesta, $id_alumno_a_ver, $id_usuario_logueado, $rol_logueado = 'encuestador') {
        
        // 1. Verificar propiedad O ROL DE ADMIN
        if ($rol_logueado !== 'administrador') {
            // Si no es admin, debe ser el propietario
            $esPropietario = $this->modeloEncuesta->checkSurveyOwnership($id_encuesta, $id_usuario_logueado);
            if (!$esPropietario) {
                return ['estado' => 403, 'success' => false, 'mensaje' => 'No eres el propietario de esta encuesta.'];
            }
        }
        // Si es admin, se salta el check de propiedad
        
        // 2. Obtener respuestas (reutilizando la función 'obtenerMisRespuestas')
        return $this->obtenerMisRespuestas($id_encuesta, $id_alumno_a_ver);
    }


    /**
     * Obtiene estadísticas globales (para Admin).
     * @return array
     */
    public function obtenerEstadisticasGlobales() {
        try {
            $stats_visibilidad = $this->modeloEncuesta->getStatsVisibilidad();
            $stats_tipos = $this->modeloEncuesta->getStatsTiposPregunta();
            
            // Formatear/Traducir los tipos de pregunta para el gráfico
            $tipos_formateados = [];
            foreach ($stats_tipos as $tipo) {
                $nombre_tipo = $tipo['tipo_pregunta'];
                switch ($tipo['tipo_pregunta']) {
                    case 'si_no':
                        $nombre_tipo = 'Verdadero / Falso';
                        break;
                    case 'opcion_multiple':
                        $nombre_tipo = 'Opción Múltiple';
                        break;
                    case 'abierta':
                        $nombre_tipo = 'Respuesta Corta';
                        break;
                    case 'escala':
                        $nombre_tipo = 'Escala (1-5)';
                        break;
                }
                $tipos_formateados[] = ['tipo_pregunta' => $nombre_tipo, 'total' => $tipo['total']];
            }

            return [
                'estado' => 200,
                'success' => true,
                'estadisticas' => [
                    'visibilidad' => $stats_visibilidad,
                    'tipos_pregunta' => $tipos_formateados // Enviar los nombres formateados
                ]
            ];
        } catch (Exception $e) {
            error_log("Error en obtenerEstadisticasGlobales: " . $e->getMessage());
            return ['estado' => 500, 'success' => false, 'mensaje' => 'Error al obtener estadísticas.'];
        }
    }

   /**
 * Obtiene los resultados de una encuesta (VERSIÓN ADMIN).
 * Ahora verifica propiedad para determinar nivel de acceso.
 * @param int $id_encuesta El ID de la encuesta.
 * @return array Respuesta con estado y datos.
 */
public function obtenerResultadosAdmin($id_encuesta) {
    if (empty($id_encuesta)) {
        return ['estado' => 400, 'success' => false, 'mensaje' => 'Falta ID de encuesta.'];
    }
    
    try {
        // Obtener ID del admin actual desde la sesión
        $id_admin = $_SESSION['usuario']['id_usuario'];
        
        // Verificar si la encuesta es del admin
        $esPropia = $this->modeloEncuesta->esEncuestaDelUsuario($id_encuesta, $id_admin);
        
        if ($esPropia) {
            // Encuesta propia - acceso completo
            $resultados = $this->modeloEncuesta->getResultadosCompletos($id_encuesta);
            if ($resultados) {
                $resultados['acceso_limitado'] = false;
                $resultados['es_propia'] = true;
            }
        } else {
            // Encuesta ajena - acceso limitado
            $resultados = $this->modeloEncuesta->getResultadosLimitados($id_encuesta);
            if ($resultados) {
                $resultados['acceso_limitado'] = true;
                $resultados['es_propia'] = false;
            }
        }

        if ($resultados === null) { 
            return ['estado' => 404, 'success' => false, 'mensaje' => 'Encuesta no encontrada.']; 
        }
        if ($resultados === false) { 
            return ['estado' => 500, 'success' => false, 'mensaje' => 'Error de BD.']; 
        }
        
        return [ 
            'estado' => 200, 
            'success' => true, 
            'resultados' => $resultados 
        ];
        
    } catch (Exception $e) {
        error_log("Exception in obtenerResultadosAdmin: " . $e->getMessage());
        return [ 
            'estado' => 500, 
            'success' => false, 
            'mensaje' => 'Error al procesar resultados admin.', 
            'error_db' => $e->getMessage() 
        ];
    }
}


    /**
     * Procesa la eliminación de una encuesta (Admin).
     * @param int $id_encuesta
     * @return array
     */
    public function eliminarEncuestaAdmin($id_encuesta) {
        if (empty($id_encuesta)) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Se requiere ID de encuesta.'];
        }
        try {
            if ($this->modeloEncuesta->deleteSurveyAdmin($id_encuesta)) {
                return ['estado' => 200, 'success' => true, 'mensaje' => 'Encuesta eliminada permanentemente.'];
            } else {
                return ['estado' => 404, 'success' => false, 'mensaje' => 'Encuesta no encontrada o no se pudo eliminar.'];
            }
        } catch (Exception $e) {
            return ['estado' => 500, 'success' => false, 'mensaje' => 'Error de base de datos.', 'error_db' => $e->getMessage()];
        }
    }

    /**
     * Actualiza una encuesta que está en modo "borrador".
     * Permite al admin editar cualquier encuesta.
     * @param int $id_encuesta ID de la encuesta a actualizar.
     * @param array $datos Nuevos datos (titulo, descripcion, preguntas, etc.)
     * @param int $id_usuario_logueado ID del usuario que hace la petición.
     * @param string $rol_logueado Rol del usuario.
     * @return array
     */
    public function actualizarEncuestaBorrador($id_encuesta, $datos, $id_usuario_logueado, $rol_logueado) {
        
        $this->conexion->begin_transaction();
        
        $stmt_pregunta = null;
        $stmt_opcion = null;

        try {
            // 1. Verificar propiedad O ROL DE ADMIN
            if ($rol_logueado !== 'administrador') {
                // Usamos la función que SÍ existe en tu modelo
                $esPropietario = $this->modeloEncuesta->checkSurveyOwnership($id_encuesta, $id_usuario_logueado);
                if (!$esPropietario) {
                    $this->conexion->rollback();
                    return ['estado' => 403, 'success' => false, 'mensaje' => 'No eres el propietario de esta encuesta.'];
                }
            }
            // Si es admin, se salta el check de propiedad

            // 2. Actualizar metadatos de la encuesta
            // Usamos la función que SÍ existe en tu modelo
            $this->modeloEncuesta->updateSurveyMeta($id_encuesta, $datos['titulo'], $datos['descripcion'], $datos['visibilidad'], $datos['estado']);

            // 3. Borrar preguntas y opciones antiguas
            // Usamos la función que SÍ existe en tu modelo
            $this->modeloEncuesta->deleteAllQuestionsFromSurvey($id_encuesta);

            // 4. Re-insertar preguntas y opciones (Lógica copiada de tu models/Encuesta.php -> create())
            
            // Preparar statement de Pregunta
            $query_pregunta = "INSERT INTO preguntas (id_encuesta, texto_pregunta, tipo_pregunta, orden) VALUES (?, ?, ?, ?)";
            $stmt_pregunta = $this->conexion->prepare($query_pregunta);
            if (!$stmt_pregunta) { throw new Exception("Prepare failed (pregunta): ".$this->conexion->error); }

            // Preparar statement de Opción
            $query_opcion = "INSERT INTO opciones (id_pregunta, texto_opcion, valor_escala) VALUES (?, ?, ?)";
            $stmt_opcion = $this->conexion->prepare($query_opcion);
            if (!$stmt_opcion) { throw new Exception("Prepare failed (opcion): ".$this->conexion->error); }


            if (isset($datos['preguntas']) && is_array($datos['preguntas'])) {
                foreach ($datos['preguntas'] as $index => $pregunta) {
                    
                    if (empty($pregunta['texto_pregunta']) || empty($pregunta['tipo_pregunta'])) {
                        throw new Exception("Datos de pregunta incompletos en el índice $index.");
                    }
                    $orden = isset($pregunta['orden']) ? $pregunta['orden'] : ($index + 1);

                    // Insertar la pregunta
                    $stmt_pregunta->bind_param("issi",
                        $id_encuesta,
                        $pregunta['texto_pregunta'],
                        $pregunta['tipo_pregunta'],
                        $orden
                    );
                    $stmt_pregunta->execute();
                    if ($stmt_pregunta->errno) { throw new Exception("Execute failed (pregunta $index): ".$stmt_pregunta->error); }
                    $id_pregunta = $this->conexion->insert_id;
                    if (!$id_pregunta) { throw new Exception("Failed to get insert ID for pregunta at index $index."); }

                    // Insertar opciones
                    if (!empty($pregunta['opciones']) && is_array($pregunta['opciones'])) {
                        foreach ($pregunta['opciones'] as $opcion) {
                            if (empty($opcion['texto_opcion'])) {
                                continue; // Saltar opciones vacías
                            }
                            $valor = isset($opcion['valor_escala']) ? intval($opcion['valor_escala']) : null;
                            $stmt_opcion->bind_param("isi",
                                $id_pregunta,
                                $opcion['texto_opcion'],
                                $valor
                            );
                            $stmt_opcion->execute();
                            if ($stmt_opcion->errno) { throw new Exception("Execute failed (opcion for pregunta $id_pregunta): ".$stmt_opcion->error); }
                        }
                    }
                }
            } else {
                 throw new Exception("No se proporcionaron preguntas.");
            }
            
            // Cerrar statements
            if ($stmt_pregunta) $stmt_pregunta->close();
            if ($stmt_opcion) $stmt_opcion->close();

            // 5. Commit
            $this->conexion->commit();
            return ['estado' => 200, 'success' => true, 'mensaje' => 'Borrador actualizado con éxito.'];

        } catch (Exception $e) {
            $this->conexion->rollback();
             // Cerrar statements si falló
            if ($stmt_pregunta instanceof mysqli_stmt) $stmt_pregunta->close();
            if ($stmt_opcion instanceof mysqli_stmt) $stmt_opcion->close();
            error_log("Error en actualizarEncuestaBorrador: " . $e->getMessage());
            return ['estado' => 500, 'success' => false, 'mensaje' => 'Error de base de datos al actualizar.', 'error_db' => $e->getMessage()];
        }
    }

    
    
}
?>