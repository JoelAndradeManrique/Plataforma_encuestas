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
     * @param int $id_encuesta El ID de la encuesta.
     * @param int $id_encuestador El ID del propietario (desde la sesión).
     * @return array|null|false Un array con los resultados, null si no es propietario, false si hay error.
     */
    public function getResultados($id_encuesta, $id_encuestador) {

        // --- 1. Verificar Propiedad y Obtener Datos de la Encuesta ---
        $query_meta = "SELECT titulo, descripcion, visibilidad, estado
                       FROM encuestas
                       WHERE id_encuesta = ? AND id_encuestador = ?";
        $stmt_meta = $this->conexion->prepare($query_meta);
        if (!$stmt_meta) { /* Manejar error */ return false; }
        $stmt_meta->bind_param("ii", $id_encuesta, $id_encuestador);
        $stmt_meta->execute();
        $meta_encuesta = $stmt_meta->get_result()->fetch_assoc();
        $stmt_meta->close();

        if (!$meta_encuesta) { return null; }

        $resultados = [
            'titulo' => $meta_encuesta['titulo'], 'visibilidad' => $meta_encuesta['visibilidad'],
            'estado' => $meta_encuesta['estado'], 'resumen_participacion' => [], 'preguntas' => []
        ];

        // --- 2. Lógica del Pie Chart ---
        $query_pie = "SELECT SUM(CASE WHEN id_alumno IS NULL THEN 1 ELSE 0 END) AS anonimas,
                        SUM(CASE WHEN id_alumno IS NOT NULL THEN 1 ELSE 0 END) AS identificadas
                      FROM respuestas WHERE id_encuesta = ?";
        $stmt_pie = $this->conexion->prepare($query_pie);
        if (!$stmt_pie) { return false; }
        $stmt_pie->bind_param("i", $id_encuesta);
        $stmt_pie->execute();
        $resumen = $stmt_pie->get_result()->fetch_assoc();
        $stmt_pie->close();
        $resultados['resumen_participacion'] = ['respuestas_anonimas' => (int)$resumen['anonimas'], 'respuestas_identificadas' => (int)$resumen['identificadas']];

        // --- 3. Lógica de Resultados por Pregunta ---
        $query_preguntas = "SELECT id_pregunta, texto_pregunta, tipo_pregunta, orden FROM preguntas WHERE id_encuesta = ? ORDER BY orden ASC";
        $stmt_preguntas = $this->conexion->prepare($query_preguntas);
        if (!$stmt_preguntas) { return false; }
        $stmt_preguntas->bind_param("i", $id_encuesta);
        $stmt_preguntas->execute();
        $lista_preguntas = $stmt_preguntas->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_preguntas->close();

        // Preparar statements
        $query_opciones = "SELECT id_opcion, texto_opcion, COUNT(r.id_respuesta) AS conteo FROM opciones o LEFT JOIN respuestas r ON o.id_opcion = r.id_opcion_seleccionada WHERE o.id_pregunta = ? GROUP BY o.id_opcion, o.texto_opcion";
        $stmt_opciones = $this->conexion->prepare($query_opciones);
        if (!$stmt_opciones) { return false; }

        $query_participantes = "SELECT r.id_opcion_seleccionada, u.nombre, u.apellido FROM respuestas r JOIN usuarios u ON r.id_alumno = u.id_usuario WHERE r.id_pregunta = ?";
        $stmt_participantes = $this->conexion->prepare($query_participantes);
        if (!$stmt_participantes) { return false; }

        // --- ✅ CORRECCIÓN DEL NOMBRE DE COLUMNA ---
        $query_abiertas = "SELECT r.texto_respuesta_abierta, u.nombre, u.apellido
                           FROM respuestas r
                           LEFT JOIN usuarios u ON r.id_alumno = u.id_usuario
                           WHERE r.id_pregunta = ?";
        $stmt_abiertas = $this->conexion->prepare($query_abiertas);
        if (!$stmt_abiertas) { return false; }
        // --- FIN CORRECCIÓN ---


        foreach ($lista_preguntas as $pregunta) {
            $datos_pregunta = [ 'id_pregunta' => $pregunta['id_pregunta'], 'texto_pregunta' => $pregunta['texto_pregunta'], 'tipo_pregunta' => $pregunta['tipo_pregunta'], 'resultados' => [] ];

            if ($pregunta['tipo_pregunta'] === 'abierta') {
                $stmt_abiertas->bind_param("i", $pregunta['id_pregunta']); $stmt_abiertas->execute();
                $resp_abiertas = $stmt_abiertas->get_result()->fetch_all(MYSQLI_ASSOC);
                foreach($resp_abiertas as $resp) {
                    // --- ✅ CORRECCIÓN DEL NOMBRE DE COLUMNA ---
                    $datos_pregunta['resultados'][] = [ 'texto_respuesta' => $resp['texto_respuesta_abierta'], 'participante' => $resp['nombre'] ? ($resp['nombre'] . ' ' . $resp['apellido']) : 'Anónimo' ];
                     // --- FIN CORRECCIÓN ---
                }
            } else { // Opciones
                $stmt_opciones->bind_param("i", $pregunta['id_pregunta']); $stmt_opciones->execute();
                $opciones = $stmt_opciones->get_result()->fetch_all(MYSQLI_ASSOC);
                $resultados_opciones = [];
                foreach($opciones as $opc) { $resultados_opciones[$opc['id_opcion']] = [ 'texto_opcion' => $opc['texto_opcion'], 'conteo' => (int)$opc['conteo'], 'participantes' => [] ]; }

                if ($meta_encuesta['visibilidad'] === 'identificada') {
                    $stmt_participantes->bind_param("i", $pregunta['id_pregunta']); $stmt_participantes->execute();
                    $participantes = $stmt_participantes->get_result()->fetch_all(MYSQLI_ASSOC);
                    foreach($participantes as $p) { if(isset($resultados_opciones[$p['id_opcion_seleccionada']])) { $resultados_opciones[$p['id_opcion_seleccionada']]['participantes'][] = $p['nombre'] . ' ' . $p['apellido']; } }
                }
                $datos_pregunta['resultados'] = array_values($resultados_opciones);
            }
            $resultados['preguntas'][] = $datos_pregunta;
        }

        // Cerrar statements
        if ($stmt_opciones) $stmt_opciones->close();
        if ($stmt_participantes) $stmt_participantes->close();
        if ($stmt_abiertas) $stmt_abiertas->close();

        return $resultados;
    }

    /**
     * OBTIENE ENCUESTAS PÚBLICAS (PARA ALUMNOS)
     * Busca encuestas 'publicada'. Si se provee $id_alumno,
     * indica si ese alumno ya la respondió.
     * @param int|null $id_alumno ID del alumno actual (de la sesión).
     * @param string|null $searchTerm Término de búsqueda opcional.
     * @return array Un array con las encuestas públicas.
     */
    public function getPublicas($id_alumno = null, $searchTerm = null) { // Añadido $id_alumno

        // Unimos con Usuarios y (condicionalmente) con Respuestas
        // Usamos LEFT JOIN con respuestas y COUNT para ver si el alumno ya respondió
        $query = "SELECT
                    e.id_encuesta, e.titulo, e.descripcion, e.visibilidad, e.fecha_creacion,
                    CASE
                        WHEN e.visibilidad = 'identificada' THEN CONCAT(u.nombre, ' ', u.apellido)
                        ELSE 'Anónimo'
                    END AS encuestador_nombre,
                    -- ✅ Nueva Columna: Contar respuestas del alumno actual para esta encuesta
                    COUNT(r_alumno.id_respuesta) > 0 AS ya_respondida
                  FROM encuestas e
                  JOIN usuarios u ON e.id_encuestador = u.id_usuario
                  -- ✅ LEFT JOIN a respuestas FILTRANDO por el alumno actual
                  LEFT JOIN respuestas r_alumno ON e.id_encuesta = r_alumno.id_encuesta AND r_alumno.id_alumno = ?
                  WHERE e.estado = 'publicada'";

        $params = [$id_alumno]; // El primer parámetro SIEMPRE es el id_alumno para el JOIN
        $types = "i"; // Tipo para id_alumno

        // --- Lógica de Búsqueda ---
        if ($searchTerm !== null) {
            $query .= " AND ( e.titulo LIKE ? OR e.descripcion LIKE ? OR CONCAT(u.nombre, ' ', u.apellido) LIKE ? )";
            $likeTerm = "%{$searchTerm}%";
            $params = array_merge($params, [$likeTerm, $likeTerm, $likeTerm]); // Añadir parámetros de búsqueda
            $types .= "sss"; // Añadir tipos para búsqueda
        }
        // --- Fin Lógica de Búsqueda ---

        // ✅ Agrupar para que COUNT funcione correctamente
        $query .= " GROUP BY e.id_encuesta, e.titulo, e.descripcion, e.visibilidad, e.fecha_creacion, encuestador_nombre";
        $query .= " ORDER BY e.fecha_creacion DESC";

        $stmt = $this->conexion->prepare($query);
        if (!$stmt) { error_log("Prepare failed (getPublicas): " . $this->conexion->error); return []; } // Devolver array vacío en error

        // "Atar" (bind) los parámetros
        $stmt->bind_param($types, ...$params);

        $stmt->execute();
        $resultado = $stmt->get_result();
        $encuestas = $resultado->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Convertir 'ya_respondida' a booleano para JSON
        foreach ($encuestas as &$encuesta) {
            $encuesta['ya_respondida'] = (bool)$encuesta['ya_respondida'];
        }

        return $encuestas;
    }
    /**
     * OBTIENE DETALLE DE ENCUESTA (PARA ALUMNOS)
     * Obtiene una sola encuesta, sus preguntas y sus opciones (sin resultados).
     * @param int $id_encuesta El ID de la encuesta.
     * @return array|null Los datos de la encuesta, o null si no es pública.
     */
    public function getDetallePublico($id_encuesta) {
        // 1. Obtener la encuesta (solo si está 'publicada')
        $query_encuesta = "SELECT id_encuesta, titulo, descripcion, visibilidad FROM encuestas
                           WHERE id_encuesta = ? AND estado = 'publicada'";
        $stmt_encuesta = $this->conexion->prepare($query_encuesta);
        // Añadir chequeo de error en prepare
        if (!$stmt_encuesta) {
             error_log("Error preparing encuesta query: " . $this->conexion->error);
             return false; // Indicar error
        }
        $stmt_encuesta->bind_param("i", $id_encuesta);
        $stmt_encuesta->execute();
        $encuesta = $stmt_encuesta->get_result()->fetch_assoc();
        $stmt_encuesta->close();

        if (!$encuesta) {
            return null; // No se encontró o no está publicada
        }

        // 2. Obtener las preguntas
        $query_preguntas = "SELECT id_pregunta, texto_pregunta, tipo_pregunta, orden
                            FROM preguntas
                            WHERE id_encuesta = ? ORDER BY orden ASC";
        $stmt_preguntas = $this->conexion->prepare($query_preguntas);
        if (!$stmt_preguntas) {
             error_log("Error preparing preguntas query: " . $this->conexion->error);
             return false;
        }
        $stmt_preguntas->bind_param("i", $id_encuesta);
        $stmt_preguntas->execute();
        $preguntas = $stmt_preguntas->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_preguntas->close();

        // 3. Obtener las opciones para cada pregunta
        $query_opciones = "SELECT id_opcion, texto_opcion, valor_escala
                           FROM opciones
                           WHERE id_pregunta = ?";
        // --- ✅ CORRECCIÓN AQUÍ ---
        $stmt_opciones = $this->conexion->prepare($query_opciones); // Usar -> en lugar de .
        // --- FIN CORRECCIÓN ---
        if (!$stmt_opciones) {
             error_log("Error preparing opciones query: " . $this->conexion->error);
             // Si falla aquí, al menos devolvemos la encuesta sin opciones
             $encuesta['preguntas'] = $preguntas; // Devolver preguntas sin opciones
             return $encuesta;
             // O podrías devolver false para indicar un error más grave
             // return false;
        }


        $preguntas_con_opciones = [];
        foreach ($preguntas as $pregunta) {
            $stmt_opciones->bind_param("i", $pregunta['id_pregunta']);
            $stmt_opciones->execute();
            $opciones_result = $stmt_opciones->get_result(); // Guardar resultado antes de fetch
            $opciones = $opciones_result->fetch_all(MYSQLI_ASSOC);
            $pregunta['opciones'] = $opciones;
            $preguntas_con_opciones[] = $pregunta;
        }
        $stmt_opciones->close(); // Cerrar statement fuera del bucle

        $encuesta['preguntas'] = $preguntas_con_opciones;
        return $encuesta;
    }

    /**
     * GUARDA LAS RESPUESTAS (PARA ALUMNOS)
     * Guarda un set de respuestas usando una transacción.
     * Maneja la lógica de anonimato.
     * @param int $id_encuesta
     * @param int|null $id_alumno_real El ID del alumno de la SESIÓN.
     * @param string $modo_respuesta 'identificado' o 'anonimo' (la elección del alumno).
     * @param array $respuestas Array de respuestas [{id_pregunta, id_opcion_seleccionada?, texto_respuesta?}].
     * @return bool True si éxito, false si falla.
     */
    public function guardarRespuestas($id_encuesta, $id_alumno_real, $modo_respuesta, $respuestas) {

        // 1. Obtener la visibilidad de la encuesta
        $query_vis = "SELECT visibilidad FROM encuestas WHERE id_encuesta = ? AND estado = 'publicada'"; // Asegurarse que sigue publicada
        $stmt_vis = $this->conexion->prepare($query_vis);
        if (!$stmt_vis) { error_log("Prepare failed (visibilidad): ".$this->conexion->error); return false; }
        $stmt_vis->bind_param("i", $id_encuesta);
        $stmt_vis->execute();
        $encuesta = $stmt_vis->get_result()->fetch_assoc();
        $stmt_vis->close();

        // Si la encuesta no existe o ya no está publicada, no guardar
        if (!$encuesta) {
            error_log("Encuesta ID $id_encuesta no encontrada o no publicada.");
            return false;
        }

        // --- Lógica de Anonimato ---
        $id_alumno_a_guardar = null;
        if ($encuesta['visibilidad'] === 'identificada' && $modo_respuesta === 'identificado') {
            $id_alumno_a_guardar = $id_alumno_real;
        }

        // Iniciar transacción
        $this->conexion->begin_transaction();

        try {
            // --- ✅ CORRECCIÓN: Nombre de columna correcto ---
            $query_respuesta = "INSERT INTO respuestas (id_encuesta, id_pregunta, id_alumno, id_opcion_seleccionada, texto_respuesta_abierta)
                                VALUES (?, ?, ?, ?, ?)";
            $stmt_respuesta = $this->conexion->prepare($query_respuesta);
            if (!$stmt_respuesta) { // Chequeo robusto
                 error_log("Prepare failed (respuesta): ".$this->conexion->error);
                 $this->conexion->rollback();
                 return false;
            }

            foreach ($respuestas as $resp) {
                // Asegurarse que las llaves existen, default a null
                $id_preg = isset($resp['id_pregunta']) ? (int)$resp['id_pregunta'] : null;
                $opcion_id = isset($resp['id_opcion_seleccionada']) ? (int)$resp['id_opcion_seleccionada'] : null;
                $texto_resp = isset($resp['texto_respuesta']) ? $resp['texto_respuesta'] : null;

                // Si falta el ID de pregunta, es un error grave en los datos, saltar
                if ($id_preg === null) {
                    error_log("Skipping response due to missing id_pregunta in payload for encuesta $id_encuesta");
                    continue;
                }

                // Tipos para bind_param: i=integer, s=string, d=double, b=blob
                // id_encuesta(i), id_pregunta(i), id_alumno(i/null), id_opcion(i/null), texto(s/null)
                // Usamos 'i' para los IDs que pueden ser NULL, MySQLi lo maneja bien.
                $bind_success = $stmt_respuesta->bind_param("iiiis",
                    $id_encuesta,
                    $id_preg,
                    $id_alumno_a_guardar,
                    $opcion_id,
                    $texto_resp
                );
                if (!$bind_success) { // Chequeo robusto
                    error_log("Bind failed: " . $stmt_respuesta->error);
                    $this->conexion->rollback();
                    return false;
                }

                $execute_success = $stmt_respuesta->execute();
                if (!$execute_success) { // Chequeo robusto
                    error_log("Execute failed: " . $stmt_respuesta->error . " | SQL: " . $query_respuesta);
                    $this->conexion->rollback();
                    return false;
                }
            }
            $stmt_respuesta->close();

            // 3. Confirmar transacción
            $this->conexion->commit();
            return true;

        } catch (Exception $e) {
            $this->conexion->rollback();
            error_log("Exception during guardarRespuestas: " . $e->getMessage()); // Registrar el error
            return false;
        }
    }
    /**
     * OBTIENE LAS RESPUESTAS DE UN ALUMNO ESPECÍFICO (PARA VISTA "GRACIAS")
     * Obtiene una lista de preguntas y las respuestas que un alumno dio.
     * @param int $id_encuesta El ID de la encuesta.
     * @param int $id_alumno El ID del alumno (de la sesión).
     * @return array|null|false Un array de {pregunta, respuesta_dada}, null si no hay, false si error DB.
     */
    public function getRespuestasAlumno($id_encuesta, $id_alumno) {
        $query = "SELECT
                    p.texto_pregunta,
                    p.tipo_pregunta,
                    r.texto_respuesta_abierta, -- Usar el nombre correcto
                    o.texto_opcion
                  FROM respuestas r
                  JOIN preguntas p ON r.id_pregunta = p.id_pregunta
                  LEFT JOIN opciones o ON r.id_opcion_seleccionada = o.id_opcion
                  WHERE r.id_encuesta = ?
                    AND r.id_alumno = ?
                  ORDER BY p.orden ASC";

        $stmt = $this->conexion->prepare($query);
        // --- Añadido: Chequeo de error en prepare ---
        if (!$stmt) {
            error_log("Prepare failed (getRespuestasAlumno): ".$this->conexion->error);
            return false; // Indicar error de DB
        }

        $stmt->bind_param("ii", $id_encuesta, $id_alumno);

        // --- Añadido: Chequeo de error en execute ---
        if (!$stmt->execute()) {
             error_log("Execute failed (getRespuestasAlumno): ".$stmt->error);
             $stmt->close();
             return false; // Indicar error de DB
        }

        $resultado = $stmt->get_result();
        // --- Añadido: Chequeo de error en get_result ---
        if (!$resultado) {
             error_log("Get result failed (getRespuestasAlumno): ".$stmt->error);
             $stmt->close();
             return false; // Indicar error de DB
        }

        $respuestas_completas = [];
        while ($fila = $resultado->fetch_assoc()) {
            $respuesta_dada = '';
            // Usar el nombre correcto de columna
            if (!empty($fila['texto_respuesta_abierta'])) {
                $respuesta_dada = $fila['texto_respuesta_abierta'];
            }
            else if (!empty($fila['texto_opcion'])) {
                $respuesta_dada = $fila['texto_opcion'];
            }

            $respuestas_completas[] = [
                'pregunta' => $fila['texto_pregunta'],
                'respuesta_dada' => $respuesta_dada
            ];
        }
        $stmt->close();

        // Si el array está vacío, significa que no respondió identificado
        if (empty($respuestas_completas)) {
            return null;
        }

        return $respuestas_completas;
    }

    /**
     * OBTIENE EL HISTORIAL (ENCUESTAS RESPONDIDAS POR UN ALUMNO)
     * Obtiene una lista de todas las encuestas que un alumno ha respondido
     * de forma IDENTIFICADA.
     * @param int $id_alumno El ID del alumno (de la sesión).
     * @return array Un array de las encuestas que ha respondido.
     */
    public function getEncuestasRespondidasPorAlumno($id_alumno) {
        
        // Esta consulta busca en las respuestas, agrupa por encuesta,
        // y devuelve los detalles de la encuesta si encuentra al menos
        // una respuesta de este alumno.
        $query = "SELECT 
                    e.id_encuesta, 
                    e.titulo, 
                    e.descripcion,
                    MAX(r.fecha_respuesta) AS fecha_respondida 
                  FROM respuestas r
                  JOIN encuestas e ON r.id_encuesta = e.id_encuesta
                  WHERE 
                    r.id_alumno = ? 
                  GROUP BY 
                    e.id_encuesta, e.titulo, e.descripcion
                  ORDER BY 
                    fecha_respondida DESC";
        
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("i", $id_alumno);
        $stmt->execute();
        
        $resultado = $stmt->get_result();
        $encuestas = $resultado->fetch_all(MYSQLI_ASSOC);
        
        $stmt->close();
        return $encuestas;
    }

    
}
?>