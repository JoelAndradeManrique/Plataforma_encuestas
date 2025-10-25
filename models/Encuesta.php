<?php
// models/Encuesta.php

class Encuesta {
    private $conexion;

    public function __construct($db) {
        $this->conexion = $db;
    }

    /**
     * Crea una nueva encuesta, sus preguntas y sus opciones usando una transacción.
     * @param array $datos Datos de la encuesta (titulo, desc, visibilidad, id_encuestador, preguntas[])
     * @return int|false El ID de la nueva encuesta si tiene éxito, false si falla.
     */
    public function create($datos) {
        // Iniciar transacción
        $this->conexion->begin_transaction();

        try {
            // 1. Insertar la Encuesta principal
            $estado_inicial = 'publicada'; // Requerimiento: "debe ser visible"
            $query_encuesta = "INSERT INTO encuestas (id_encuestador, titulo, descripcion, visibilidad, estado) 
                               VALUES (?, ?, ?, ?, ?)";
            
            $stmt_encuesta = $this->conexion->prepare($query_encuesta);
            $stmt_encuesta->bind_param("issss",
                $datos['id_encuestador'],
                $datos['titulo'],
                $datos['descripcion'],
                $datos['visibilidad'],
                $estado_inicial
            );
            $stmt_encuesta->execute();
            $id_encuesta = $this->conexion->insert_id; // Obtener el ID de la encuesta recién creada
            $stmt_encuesta->close();

            // 2. Insertar las Preguntas
            $query_pregunta = "INSERT INTO preguntas (id_encuesta, texto_pregunta, tipo_pregunta, orden) 
                               VALUES (?, ?, ?, ?)";
            $stmt_pregunta = $this->conexion->prepare($query_pregunta);

            // 3. Insertar las Opciones
            $query_opcion = "INSERT INTO opciones (id_pregunta, texto_opcion, valor_escala) 
                             VALUES (?, ?, ?)";
            $stmt_opcion = $this->conexion->prepare($query_opcion);

            foreach ($datos['preguntas'] as $pregunta) {
                // Insertar la pregunta
                $stmt_pregunta->bind_param("issi",
                    $id_encuesta,
                    $pregunta['texto_pregunta'],
                    $pregunta['tipo_pregunta'],
                    $pregunta['orden']
                );
                $stmt_pregunta->execute();
                $id_pregunta = $this->conexion->insert_id; // Obtener el ID de la pregunta

                // Si la pregunta tiene opciones, insertarlas
                if (!empty($pregunta['opciones'])) {
                    foreach ($pregunta['opciones'] as $opcion) {
                        $valor = isset($opcion['valor_escala']) ? intval($opcion['valor_escala']) : null;
                        $stmt_opcion->bind_param("isi",
                            $id_pregunta,
                            $opcion['texto_opcion'],
                            $valor
                        );
                        $stmt_opcion->execute();
                    }
                }
            }
            $stmt_pregunta->close();
            $stmt_opcion->close();

            // Si todo salió bien, confirmar la transacción
            $this->conexion->commit();
            return $id_encuesta;

        } catch (Exception $e) {
            // Si algo falló, deshacer la transacción
            $this->conexion->rollback();
            // Opcional: registrar el error $e->getMessage()
            return false;
        }
    }

    /**
     * Actualiza el estado de una encuesta (ej. 'publicada' a 'cerrada').
     * @param int $id_encuesta El ID de la encuesta.
     * @param string $nuevo_estado El nuevo estado ('publicada', 'cerrada', 'borrador').
     * @param int $id_encuestador El ID del encuestador (para seguridad).
     * @return bool True si tuvo éxito, false si no.
     */
    public function updateEstado($id_encuesta, $nuevo_estado, $id_encuestador) {
        // La consulta se asegura que solo el encuestador que creó la encuesta pueda cambiarla
        $query = "UPDATE encuestas SET estado = ? WHERE id_encuesta = ? AND id_encuestador = ?";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("sii", $nuevo_estado, $id_encuesta, $id_encuestador);
        $stmt->execute();
        
        // affected_rows > 0 significa que la actualización fue exitosa
        return $stmt->affected_rows > 0;
    }

    /**
     * Busca y devuelve todas las encuestas creadas por un encuestador específico.
     * @param int $id_encuestador El ID del encuestador.
     * @return array Un array con las encuestas encontradas.
     */
    public function findByEncuestador($id_encuestador) {
        $query = "SELECT id_encuesta, titulo, descripcion, visibilidad, estado, fecha_creacion, enlace_compartir 
                  FROM encuestas 
                  WHERE id_encuestador = ? AND estado != 'archivada' 
                  ORDER BY fecha_creacion DESC";
        
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("i", $id_encuestador);
        $stmt->execute();
        
        $resultado = $stmt->get_result();
        $encuestas = $resultado->fetch_all(MYSQLI_ASSOC);
        
        $stmt->close();
        return $encuestas;
    }

    /**
     * "Elimina" lógicamente una encuesta cambiand su estado a 'archivada'.
     * Solo el encuestador que la creó puede hacer esto.
     * @param int $id_encuesta El ID de la encuesta a archivar.
     * @param int $id_encuestador El ID del propietario (desde la sesión).
     * @return bool True si tuvo éxito, false si no.
     */
    public function archiveSurvey($id_encuesta, $id_encuestador) {
        $query = "UPDATE encuestas SET estado = 'archivada' 
                  WHERE id_encuesta = ? AND id_encuestador = ?";
        
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("ii", $id_encuesta, $id_encuestador);
        $stmt->execute();
        
        // affected_rows > 0 significa que la actualización fue exitosa
        $success = $stmt->affected_rows > 0;
        $stmt->close();
        return $success;
    }

    /**
     * Obtiene los resultados detallados de una encuesta, incluyendo resumen y desglose.
     * Solo el encuestador que la creó puede ver esto.
     * * @param int $id_encuesta El ID de la encuesta.
     * @param int $id_encuestador El ID del propietario (desde la sesión).
     * @return array|null|false Un array con los resultados, null si no es propietario, false si hay error.
     */
    public function getResultados($id_encuesta, $id_encuestador) {
        
        // --- 1. Verificar Propiedad y Obtener Datos de la Encuesta ---
        $query_meta = "SELECT titulo, descripcion, visibilidad, estado 
                       FROM encuestas 
                       WHERE id_encuesta = ? AND id_encuestador = ?";
        $stmt_meta = $this->conexion->prepare($query_meta);
        $stmt_meta->bind_param("ii", $id_encuesta, $id_encuestador);
        $stmt_meta->execute();
        $meta_encuesta = $stmt_meta->get_result()->fetch_assoc();
        $stmt_meta->close();

        // Si no devuelve nada, no es el propietario o la encuesta no existe
        if (!$meta_encuesta) {
            return null; 
        }

        $resultados = [
            'titulo' => $meta_encuesta['titulo'],
            'visibilidad' => $meta_encuesta['visibilidad'],
            'estado' => $meta_encuesta['estado'],
            'resumen_participacion' => [],
            'preguntas' => []
        ];

        // --- 2. Lógica del Pie Chart (% Respuestas Anónimas vs. Identificadas) ---
        $query_pie = "SELECT 
                        SUM(CASE WHEN id_alumno IS NULL THEN 1 ELSE 0 END) AS anonimas,
                        SUM(CASE WHEN id_alumno IS NOT NULL THEN 1 ELSE 0 END) AS identificadas
                      FROM respuestas 
                      WHERE id_encuesta = ?";
        $stmt_pie = $this->conexion->prepare($query_pie);
        $stmt_pie->bind_param("i", $id_encuesta);
        $stmt_pie->execute();
        $resumen = $stmt_pie->get_result()->fetch_assoc();
        $stmt_pie->close();
        
        $resultados['resumen_participacion'] = [
            'respuestas_anonimas' => (int)$resumen['anonimas'],
            'respuestas_identificadas' => (int)$resumen['identificadas']
        ];

        // --- 3. Lógica de Resultados por Pregunta ---
        
        // Obtener todas las preguntas de la encuesta
        $query_preguntas = "SELECT id_pregunta, texto_pregunta, tipo_pregunta, orden 
                            FROM preguntas 
                            WHERE id_encuesta = ? ORDER BY orden ASC";
        $stmt_preguntas = $this->conexion->prepare($query_preguntas);
        $stmt_preguntas->bind_param("i", $id_encuesta);
        $stmt_preguntas->execute();
        $lista_preguntas = $stmt_preguntas->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_preguntas->close();

        // Preparar las consultas que usaremos en el bucle
        $query_opciones = "SELECT id_opcion, texto_opcion, COUNT(r.id_respuesta) AS conteo
                           FROM opciones o
                           LEFT JOIN respuestas r ON o.id_opcion = r.id_opcion_seleccionada
                           WHERE o.id_pregunta = ?
                           GROUP BY o.id_opcion, o.texto_opcion";
        $stmt_opciones = $this.conexion->prepare($query_opciones);

        $query_participantes = "SELECT r.id_opcion_seleccionada, u.nombre, u.apellido
                                FROM respuestas r
                                JOIN usuarios u ON r.id_alumno = u.id_usuario
                                WHERE r.id_pregunta = ?";
        $stmt_participantes = $this.conexion->prepare($query_participantes);

        $query_abiertas = "SELECT r.texto_respuesta, u.nombre, u.apellido
                           FROM respuestas r
                           LEFT JOIN usuarios u ON r.id_alumno = u.id_usuario
                           WHERE r.id_pregunta = ?";
        $stmt_abiertas = $this.conexion->prepare($query_abiertas);


        // Bucle por cada pregunta
        foreach ($lista_preguntas as $pregunta) {
            $datos_pregunta = [
                'id_pregunta' => $pregunta['id_pregunta'],
                'texto_pregunta' => $pregunta['texto_pregunta'],
                'tipo_pregunta' => $pregunta['tipo_pregunta'],
                'resultados' => []
            ];

            if ($pregunta['tipo_pregunta'] === 'abierta') {
                // --- Caso 1: Pregunta Abierta ---
                $stmt_abiertas->bind_param("i", $pregunta['id_pregunta']);
                $stmt_abiertas->execute();
                $resp_abiertas = $stmt_abiertas->get_result()->fetch_all(MYSQLI_ASSOC);
                
                foreach($resp_abiertas as $resp) {
                    $datos_pregunta['resultados'][] = [
                        'texto_respuesta' => $resp['texto_respuesta'],
                        'participante' => $resp['nombre'] ? ($resp['nombre'] . ' ' . $resp['apellido']) : 'Anónimo'
                    ];
                }

            } else {
                // --- Caso 2: Pregunta de Opciones (Múltiple, Escala, Si/No, etc.) ---
                
                // a) Obtener las opciones y el conteo de votos
                $stmt_opciones->bind_param("i", $pregunta['id_pregunta']);
                $stmt_opciones->execute();
                $opciones = $stmt_opciones->get_result()->fetch_all(MYSQLI_ASSOC);
                
                $resultados_opciones = [];
                foreach($opciones as $opc) {
                    $resultados_opciones[$opc['id_opcion']] = [
                        'texto_opcion' => $opc['texto_opcion'],
                        'conteo' => (int)$opc['conteo'],
                        'participantes' => [] // Lista para los nombres
                    ];
                }

                // b) Si la encuesta es 'identificada', buscar los nombres de quién votó qué
                if ($meta_encuesta['visibilidad'] === 'identificada') {
                    $stmt_participantes->bind_param("i", $pregunta['id_pregunta']);
                    $stmt_participantes->execute();
                    $participantes = $stmt_participantes->get_result()->fetch_all(MYSQLI_ASSOC);

                    foreach($participantes as $p) {
                        // Añadir el nombre a la opción correspondiente
                        if(isset($resultados_opciones[$p['id_opcion_seleccionada']])) {
                            $resultados_opciones[$p['id_opcion_seleccionada']]['participantes'][] = $p['nombre'] . ' ' . $p['apellido'];
                        }
                    }
                }
                
                // Limpiar el array para que el JSON sea limpio (quitamos los IDs de opción como keys)
                $datos_pregunta['resultados'] = array_values($resultados_opciones);
            }
            
            $resultados['preguntas'][] = $datos_pregunta;
        }
        
        $stmt_opciones->close();
        $stmt_participantes->close();
        $stmt_abiertas->close();

        return $resultados;
    }
}
?>