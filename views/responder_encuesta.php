<?php
session_start();
// Seguridad y obtención de datos (Sin cambios)
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'alumno') { header("Location: login.php"); exit(); }
$usuario = $_SESSION['usuario']; $nombre = htmlspecialchars($usuario['nombre'] ?? ''); $apellido = htmlspecialchars($usuario['apellido'] ?? '');
$id_encuesta = isset($_GET['id']) ? intval($_GET['id']) : 0;
// ✅ Leer el modo desde la URL
$modo_respuesta = isset($_GET['modo']) && in_array($_GET['modo'], ['identificado', 'anonimo']) ? $_GET['modo'] : 'anonimo';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Responder Encuesta</title>
  <link rel="stylesheet" href="../css/alumno.css?v=9"> <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
      /* Estilos específicos (copiados de la versión anterior que te pasé) */
      body { background-color: #f8f9fa; }
      .encuesta-container { max-width: 800px; margin: 20px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
      .encuesta-header h1 { font-size: 1.8rem; margin-bottom: 5px; color: #333; }
      .encuesta-header p { color: #666; margin-bottom: 25px; font-size: 1rem; }
      .pregunta-item { background: #fdfdff; border: 1px solid #eee; border-left: 5px solid #e3eef6ff; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
      .pregunta-item h3 { font-size: 1.1rem; margin-bottom: 15px; }
      .opcion-label { display: block; margin-bottom: 10px; cursor: pointer; }
      .opcion-label input[type="radio"], .opcion-label input[type="checkbox"] { margin-right: 10px; }
      .respuesta-texto { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin-top: 5px; font-size: 1rem; }
      .botones-finales { margin-top: 30px; text-align: right; border-top: 1px solid #eee; padding-top: 20px; }
      .btn-enviar, .btn-cancelar { padding: 10px 20px; border: none; border-radius: 5px; font-size: 1rem; cursor: pointer; margin-left: 10px; }
      .btn-enviar { background-color: #28a745; color: white; }
      .btn-cancelar { background-color: #6c757d; color: white; }
      #loading-encuesta { text-align: center; padding: 40px; font-size: 1.2em; color: #666; }
      .modo-respuesta-aviso { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; padding: 10px 15px; border-radius: 5px; margin-bottom: 20px; font-size: 0.9em; }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="logo-container">
      <img src="../img/image2.png" alt="Logo" class="logo"> <span class="titulo">Responder Encuesta</span>
    </div>
    <button id="btn-volver" class="nav-btn" onclick="window.location.href='dashboard_alumno.php'">Volver</button>
  </nav>

  <main class="encuesta-container">
    <div id="loading-encuesta"><i class="fa-solid fa-spinner fa-spin"></i> Cargando encuesta...</div>
    <div id="encuesta-content" style="display: none;">
        <div class="encuesta-header">
            <h1 id="encuesta-titulo"></h1>
            <p id="encuesta-descripcion"></p>
            <div class="modo-respuesta-aviso">
                Estás respondiendo esta encuesta de forma
                <strong><?php echo $modo_respuesta === 'identificado' ? 'Identificada' : 'Anónima'; ?></strong>.
                <?php if ($modo_respuesta === 'anonimo'): ?>
                    Recuerda que no podrás visualizar tus respuestas al finalizar.
                <?php endif; ?>
            </div>
        </div>

        <form id="form-respuestas">
            <div id="preguntas-container"></div>
            <div class="botones-finales">
                <button type="button" id="btn-cancelar" class="btn-cancelar">Cancelar</button>
                <button type="submit" id="btn-enviar" class="btn-enviar">Enviar respuestas</button>
            </div>
        </form>
    </div>
  </main>

  <script>
    // Asegúrate de que jQuery esté listo
    $(function () {
    // Leer datos iniciales desde PHP
    const idEncuesta = <?php echo $id_encuesta; ?>;
    const modoRespuesta = "<?php echo $modo_respuesta; ?>"; // Leer desde PHP

    // Referencias a elementos del DOM
    const $loading = $('#loading-encuesta');
    const $content = $('#encuesta-content');
    const $preguntasContainer = $('#preguntas-container');
    const $form = $('#form-respuestas');

    // --- 1. Cargar la Encuesta ---
    function cargarEncuesta() {
      // Validar ID primero
      if (!idEncuesta || isNaN(idEncuesta) || idEncuesta <= 0) {
          mostrarErrorCarga('ID de encuesta no válido en la URL.');
          return;
      }

      $loading.show();
      $content.hide();

      // Llamada AJAX real
      $.ajax({
          url: `../api/obtenerDetalleEncuesta.php?id_encuesta=${idEncuesta}`,
          method: 'GET',
          dataType: 'json', // Esperamos JSON
          success: function(response) {
              $loading.hide();
              // Validar la respuesta
              if (response && response.success && response.encuesta && response.encuesta.preguntas) {
                  renderizarEncuesta(response.encuesta);
                  $content.show();
              } else {
                  // Mensaje de error de la API o genérico
                  mostrarErrorCarga(response.mensaje || 'La respuesta de la API no es válida.');
              }
          },
          error: function(jqXHR, textStatus, errorThrown) {
              // Error de conexión, 404, 500, etc.
              $loading.hide();
              console.error("Error AJAX en cargarEncuesta:", textStatus, errorThrown, jqXHR.responseText); // Log detallado
              let msg = 'Error de conexión al cargar la encuesta.';
              if(jqXHR.status === 404) msg = 'Encuesta no encontrada o no disponible (404).';
              if(jqXHR.status === 403) msg = 'No tienes permiso para ver esta encuesta (403).';
              if(jqXHR.status === 500) msg = 'Error interno del servidor al cargar (500).';
              if(textStatus === 'parsererror') msg = 'Error: La respuesta del servidor no es JSON válido.';
              mostrarErrorCarga(msg);
          }
      });
    }

    // --- 2. Renderizar el Formulario ---
    function renderizarEncuesta(encuesta) {
        $('#encuesta-titulo').text(encuesta.titulo || 'Encuesta sin título');
        $('#encuesta-descripcion').text(encuesta.descripcion || '');
        $preguntasContainer.empty(); // Limpiar antes de añadir

        if (!encuesta.preguntas || encuesta.preguntas.length === 0) {
             $preguntasContainer.html('<p>Esta encuesta no tiene preguntas.</p>');
             $('#btn-enviar').hide(); // Ocultar botón si no hay preguntas
             return;
        }

        encuesta.preguntas.forEach((p, i) => {
            // Validar datos de la pregunta
            if (!p || !p.id_pregunta || !p.texto_pregunta || !p.tipo_pregunta) {
                console.warn("Pregunta inválida encontrada:", p);
                return; // Saltar esta pregunta
            }

            let htmlPregunta = `<div class="pregunta-item"><h3>${i + 1}. ${p.texto_pregunta}</h3>`;
            const inputName = `respuesta[${p.id_pregunta}]`;

            switch(p.tipo_pregunta) {
                case 'opcion_multiple':
                case 'si_no':
                case 'escala':
                    if (p.opciones && Array.isArray(p.opciones) && p.opciones.length > 0) {
                        p.opciones.forEach((op) => {
                            // Validar opción
                            if (op && op.id_opcion && op.texto_opcion) {
                                htmlPregunta += `
                                    <label class="opcion-label">
                                        <input type="radio" name="${inputName}[opcion]" value="${op.id_opcion}" required> ${op.texto_opcion}
                                    </label>`;
                            } else { console.warn("Opción inválida en pregunta", p.id_pregunta, op); }
                        });
                    } else { htmlPregunta += `<p><em>No hay opciones definidas para esta pregunta.</em></p>`; }
                    break;
                case 'seleccion_multiple':
                     if (p.opciones && Array.isArray(p.opciones) && p.opciones.length > 0) {
                        p.opciones.forEach((op) => {
                            if (op && op.id_opcion && op.texto_opcion) {
                                 const checkName = `${inputName}[opciones][${op.id_opcion}]`;
                                 htmlPregunta += `
                                    <label class="opcion-label">
                                        <input type="checkbox" name="${checkName}" value="${op.id_opcion}"> ${op.texto_opcion}
                                    </label>`;
                             } else { console.warn("Opción inválida en pregunta", p.id_pregunta, op); }
                        });
                    } else { htmlPregunta += `<p><em>No hay opciones definidas para esta pregunta.</em></p>`; }
                    break;
                case 'abierta':
                    htmlPregunta += `<textarea name="${inputName}[texto]" class="respuesta-texto" rows="3" placeholder="Escribe tu respuesta aquí..." required></textarea>`;
                    break;
                default:
                     htmlPregunta += `<p><em>Tipo de pregunta no soportado: ${p.tipo_pregunta}</em></p>`;
            }
            htmlPregunta += `</div>`;
            $preguntasContainer.append(htmlPregunta);
        });
    }

    // --- 3. Enviar Respuestas ---
    $form.on('submit', function(e) {
        e.preventDefault();
        const $submitBtn = $('.btn-enviar');

        // Validación de campos requeridos (mejorada)
        let formValido = true;
        $form.find('.pregunta-item').each(function() {
            const $preguntaItem = $(this);
            $preguntaItem.css('border-color', '#eee'); // Resetear borde
            const $requiredInputs = $preguntaItem.find('[required]');
            if ($requiredInputs.length > 0) {
                if ($requiredInputs.is(':radio')) {
                    const name = $requiredInputs.first().attr('name');
                    if ($(`input[name="${name}"]:checked`).length === 0) {
                        formValido = false; $preguntaItem.css('border-color', 'red');
                    }
                } else if ($requiredInputs.is('textarea')) {
                    if (!$requiredInputs.val().trim()) {
                        formValido = false; $requiredInputs.css('border-color', 'red'); $preguntaItem.css('border-color', 'red');
                    } else { $requiredInputs.css('border-color', '#ccc'); }
                }
            }
        });

        if (!formValido) { Swal.fire("Atención", "Por favor responde todas las preguntas marcadas antes de enviar.", "warning"); return; }

        Swal.fire({
            title: "¿Deseas enviar tus respuestas?", icon: "question", showCancelButton: true, confirmButtonText: "Sí, enviar",
            cancelButtonText: "Cancelar", confirmButtonColor: "#28a745"
        }).then((res) => {
            if (!res.isConfirmed) return;

            $submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Enviando...');

            // Procesar datos para la API
            const formData = $form.serializeArray(); const respuestasApi = []; const respuestasProcesadas = {};
            formData.forEach(item => { const match = item.name.match(/respuesta\[(\d+)\]\[(\w+)(?:\[(\d+)\])?\]/); if (match) { const idPregunta = parseInt(match[1]); const tipoDato = match[2]; const idOpcionCheck = match[3] ? parseInt(match[3]) : null; if (!respuestasProcesadas[idPregunta]) { respuestasProcesadas[idPregunta] = { id_pregunta: idPregunta }; } if (tipoDato === 'opcion') { respuestasProcesadas[idPregunta].id_opcion_seleccionada = parseInt(item.value); } else if (tipoDato === 'texto') { respuestasProcesadas[idPregunta].texto_respuesta = item.value; } else if (tipoDato === 'opciones' && idOpcionCheck) { respuestasApi.push({ id_pregunta: idPregunta, id_opcion_seleccionada: parseInt(item.value) }); respuestasProcesadas[idPregunta].procesada = true; } } });
            for (const idPregunta in respuestasProcesadas) { if (!respuestasProcesadas[idPregunta].procesada) { respuestasApi.push(respuestasProcesadas[idPregunta]); } }
            const payload = { id_encuesta: idEncuesta, modo_respuesta: modoRespuesta, respuestas: respuestasApi };

            // Envío real
            $.ajax({
                url: "../api/enviarRespuesta.php",
                method: "POST",
                contentType: "application/json",
                data: JSON.stringify(payload),
                success: (r) => {
                    if (r.success) {
                        // --- ✅ LÓGICA DE GUARDADO LOCAL ---
                        // No importa el modo, guardamos que ya respondió.
                        try {
                            // Usamos una clave que guarda el MODO
                            let respondidasLocal = JSON.parse(localStorage.getItem('encuestasRespondidasLocalmente') || '{}');
                            // Guardamos CÓMO respondió ('identificado' o 'anonimo')
                            respondidasLocal[idEncuesta] = modoRespuesta; 
                            localStorage.setItem('encuestasRespondidasLocalmente', JSON.stringify(respondidasLocal));
                            console.log(`LocalStorage: Marcada encuesta ${idEncuesta} como respondida (${modoRespuesta})`);
                        } catch (e) {
                            console.error("Error al guardar en localStorage:", e);
                        }
                        // --- FIN LÓGICA GUARDADO ---

                        // Obtener el título de la encuesta desde el H1
                        const tituloEncuesta = $('#encuesta-titulo').text() || "Encuesta Respondida";

                        Swal.fire({
                            icon: 'success', title: '¡Respuestas enviadas!', text: 'Gracias por participar.',
                            showCancelButton: true, confirmButtonText: 'Ver mis respuestas', cancelButtonText: 'Volver al inicio',
                            confirmButtonColor: '#17a2b8', cancelButtonColor: '#6c757d',
                            preConfirm: () => { 
                                if (modoRespuesta === 'anonimo') { 
                                    Swal.showValidationMessage('No puedes ver tus respuestas si respondiste de forma anónima.'); 
                                    return false; // Evita que se cierre al confirmar
                                }
                                return true; // Permite continuar si es identificado
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // ✅ Redirigir pasando el título para el modal
                                window.location.href = `dashboard_alumno.php?verRespuestas=${idEncuesta}&titulo=${encodeURIComponent(tituloEncuesta)}`;
                            } else {
                                window.location.href = 'dashboard_alumno.php';
                            }
                        });
                    } else {
                        Swal.fire("Error", r.mensaje || "No se pudo enviar la encuesta.", "error");
                        $submitBtn.prop('disabled', false).text('Enviar respuestas');
                    }
                },
                error: () => {
                    Swal.fire("Error", "No se pudo conectar con el servidor.", "error");
                    $submitBtn.prop('disabled', false).text('Enviar respuestas');
                }
            });
        });
    });

    // Botones Cancelar y Volver
    $("#btn-cancelar").on("click", function () { window.location.href = "dashboard_alumno.php"; });

    // Función de error genérica
    function mostrarErrorCarga(mensaje) {
        $loading.hide(); $content.hide();
        Swal.fire({ icon: 'error', title: 'Error al cargar', text: mensaje || 'No se pudo cargar la encuesta.', confirmButtonText: 'Volver al Dashboard'
        }).then(() => { window.location.href = 'dashboard_alumno.php'; });
    }

    // Ejecutar carga inicial
    cargarEncuesta();
  });
  </script>
</body>
</html>