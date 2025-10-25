<?php
session_start();
// Seguridad Estricta para Encuestador
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'encuestador') {
    header("Location: login.php");
    exit();
}
$usuario = $_SESSION['usuario'];
$nombre = htmlspecialchars($usuario['nombre']);
$esTemporal = isset($usuario['password_temporal']) && $usuario['password_temporal'] == TRUE;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Encuestador</title>

    <link rel="stylesheet" href="../css/style.css">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* Reseteo del body (para anular tu style.css) */
        body { display: block; justify-content: normal; align-items: normal; padding: 0; background-color: #f4f7f6; font-family: Arial, sans-serif; margin: 0; }
        .dashboard-wrapper { display: flex; flex-direction: column; min-height: 100vh; }

        /* Cabecera */
        .dashboard-header { background: #fff; border-bottom: 1px solid #ddd; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 1000; width: 100%; box-sizing: border-box; min-height: 60px; }
        .header-left-group { display: flex; align-items: center; height: 100%; }
        .header-logo { font-size: 1.5rem; font-weight: bold; color: #333; margin-right: 30px; }

        /* Pestañas */
        .dashboard-tabs { display: flex; height: 100%; }
        .tab-link { padding: 0 15px; display: flex; align-items: center; border: none; background: none; cursor: pointer; font-size: 1rem; color: #555; text-decoration: none; border-bottom: 3px solid transparent; height: 100%; gap: 8px; }
        .tab-link i { color: inherit; font-size: 1em; }
        .tab-link:hover { color: #007bff; }
        .tab-link.active { color: #007bff; border-bottom-color: #007bff; font-weight: bold; }

        /* Grupo Derecho */
        .header-right-group { display: flex; align-items: center; }
        .btn-publish { background-color: #FFB6C1; color: #333; padding: 8px 15px; border: none; border-radius: 5px; font-size: 0.9rem; font-weight: 500; cursor: pointer; margin-right: 15px; transition: background-color 0.2s ease; display: flex; align-items: center; gap: 5px; }
        .btn-publish:hover { background-color: #FFAEB9; }
        .btn-logout { background-color: #dc3545; color: white; padding: 8px 12px; border-radius: 5px; text-decoration: none; font-size: 0.9rem; font-weight: 500; margin-right: 15px; transition: background-color 0.2s ease; display: flex; align-items: center; gap: 5px; }
        .btn-logout:hover { background-color: #c82333; }
        .user-profile { display: flex; align-items: center; gap: 5px; }
        .user-profile span { font-size: 0.9rem; color: #333; }
        .user-profile i { margin-right: 5px; }

        /* Contenido Principal */
        .dashboard-content { margin: 20px; padding: 0; width: auto; flex-grow: 1; }

        /* Estilos Form Builder */
        .form-builder-container { max-width: 800px; margin: 0 auto; }
        .survey-header-editor, .pregunta-block { background: #fff; border: 1px solid #ccc; border-radius: 8px; padding: 25px; margin-bottom: 20px; border-left: 8px solid #e3eef6ff; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .pregunta-block:focus-within { border-left-color: #007bff; }
        .survey-header-editor input, .survey-header-editor textarea { width: 100%; border: none; border-bottom: 1px solid #eee; padding: 10px 0; font-size: 1.5rem; margin-bottom: 10px; }
        .survey-header-editor input:focus, .survey-header-editor textarea:focus { outline: none; border-bottom-color: #007bff; }
        .survey-header-editor textarea { font-size: 1rem; min-height: 40px; resize: vertical; }
        .pregunta-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 15px; margin-bottom: 15px; }
        .pregunta-header input[type="text"] { flex-grow: 1; padding: 10px; border: 1px dashed #ccc; border-radius: 4px; font-size: 1.1rem; }
        .pregunta-header input[type="text"]:focus { border-style: solid; border-color: #007bff; outline: none; }
        .pregunta-header select { padding: 10px; border: 1px solid #ccc; border-radius: 4px; background: #f9f9f9; }
        .opciones-container { margin-left: 20px; }
        .opcion-item { display: flex; align-items: center; margin-bottom: 10px; }
        .opcion-item input[type="text"] { border: none; border-bottom: 1px solid #eee; padding: 8px 0; margin-left: 10px; flex-grow: 1; }
        .opcion-item input[type="text"]:focus { border-bottom-color: #007bff; outline: none; }
        .btn-delete-opcion { background: none; border: none; color: #aaa; cursor: pointer; font-size: 1.2rem; }
        .btn-add-opcion { background: none; border: none; color: #007bff; cursor: pointer; margin-left: 30px; font-size: 0.9rem; display: flex; align-items: center; gap: 5px; }
        .pregunta-footer { margin-top: 15px; display: flex; justify-content: flex-end; align-items: center; gap: 15px; border-top: 1px solid #eee; padding-top: 15px; }
        .btn-delete-pregunta { color: #dc3545; background: none; border: none; cursor: pointer; font-size: 1.2rem; }
        .btn-add-pregunta { display: block; margin: 20px auto; padding: 10px 20px; background: #fff; border: 1px dashed #ccc; border-radius: 5px; cursor: pointer; color: #555; transition: all 0.2s ease; display: flex; align-items: center; gap: 8px; justify-content: center; }
        .btn-add-pregunta:hover { background: #f9f9f9; border-style: solid; color: #007bff; }

        /* Estilos Lista Encuestas */
        .encuesta-item { display: flex; flex-direction: column; justify-content: space-between; padding: 15px; background: #fff; border-radius: 8px; margin-bottom: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .encuesta-info { margin-bottom: 10px; }
        .encuesta-info h3 { margin: 0; font-size: 1.2rem; }
        .encuesta-info div { display: flex; align-items: center; gap: 10px; margin-top: 5px; flex-wrap: wrap; }
        .encuesta-info span { font-size: 0.85rem; padding: 3px 8px; border-radius: 12px; color: #fff; }
        .encuesta-info small { font-size: 0.85rem; color: #666;}
        .encuesta-info .estado-publicada { background-color: #28a745; }
        .encuesta-info .estado-cerrada { background-color: #dc3545; }
        .encuesta-info .estado-borrador { background-color: #6c757d; }
        .encuesta-info .estado-archivada { background-color: #343a40; }
        .encuesta-acciones { display: flex; flex-wrap: wrap; gap: 5px; }
        .encuesta-acciones button, .encuesta-acciones a { padding: 8px 12px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; color: white; font-size: 0.9rem; flex-grow: 1; text-align: center; display: flex; align-items: center; justify-content: center; gap: 5px; }
        .btn-resultados { background-color: #17a2b8; }
        .btn-cerrar { background-color: #ffc107; color: #333; }
        .btn-eliminar { background-color: #dc3545; }
        #loading { text-align: center; padding: 40px; font-size: 1.2em; color: #666; }

        /* Estilos Vista Resultados */
        .resultados-container { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .resultados-header h2 { margin-bottom: 5px; }
        .resultados-header p { color: #666; margin-bottom: 20px; }
        .pie-chart-container { max-width: 300px; margin: 20px auto; }
        .pregunta-resultados { border-top: 1px solid #eee; padding-top: 20px; margin-top: 20px; }
        .pregunta-resultados h4 { font-size: 1.1rem; margin-bottom: 10px; }
        .opcion-resultado { margin-bottom: 8px; }
        .opcion-resultado .texto { font-weight: 500; }
        .opcion-resultado .conteo { color: #007bff; font-weight: bold; }
        .participante-lista { font-size: 0.9em; color: #777; margin-left: 15px; }
        .respuesta-abierta { background: #f8f9fa; border-left: 3px solid #ccc; padding: 8px 12px; margin-bottom: 8px; font-style: italic; }
        .respuesta-abierta span { font-weight: bold; color: #555; }

        @media (min-width: 768px) {
            .encuesta-item { flex-direction: row; align-items: center; }
            .encuesta-info { margin-bottom: 0; }
            .encuesta-acciones { flex-grow: 0; flex-wrap: nowrap; }
        }
    </style>
</head>
<body>

    <div class="dashboard-wrapper">

        <header class="dashboard-header">
            <div class="header-left-group">
                <div class="header-logo">Mi Panel</div>
                <nav class="dashboard-tabs">
                    <a href="#" class="tab-link active" id="btn-tab-mis-encuestas"><i class="fa-solid fa-list-ul"></i>Mis Encuestas</a>
                    <a href="#" class="tab-link" id="btn-tab-crear"><i class="fa-solid fa-plus-circle"></i>Crear Nueva Encuesta</a>
                </nav>
            </div>
            <div class="header-right-group">
                <div id="publish-button-placeholder"></div>
                <a href="logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
                <div class="user-profile"><i class="fa-solid fa-user"></i> <span><?php echo $nombre; ?></span></div>
            </div>
        </header>

        <main class="dashboard-content" id="dashboard-content-container">
            </main>

    </div> <script>
        // Configuración global de Toasts
        const Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true,
            didOpen: (toast) => { toast.onmouseenter = Swal.stopTimer; toast.onmouseleave = Swal.resumeTimer; }
        });
        let preguntaIndex = 0; // Contador global

        // Marcar pestaña activa
        function activarTab(tabId) {
            $('.tab-link').removeClass('active');
            if (tabId) { // Puede ser null si cargamos resultados
                 $(tabId).addClass('active');
            }
        }

        // --- CARGAR VISTA: MIS ENCUESTAS ---
        function cargarMisEncuestas() {
            activarTab('#btn-tab-mis-encuestas');
            $('#publish-button-placeholder').empty();
            const container = $('#dashboard-content-container');
            container.html('<div id="loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando tus encuestas...</div>');

            $.ajax({
                url: '../api/misEncuestas.php', method: 'GET', dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (response.encuestas.length === 0) {
                            container.html('<h3>No has creado ninguna encuesta todavía.</h3><p>Usa la pestaña "Crear Nueva Encuesta" para empezar.</p>'); return;
                        }
                        container.empty();
                        response.encuestas.forEach(function(encuesta) {
                            const estadoClase = `estado-${encuesta.estado}`;
                            const estadoTexto = encuesta.estado.charAt(0).toUpperCase() + encuesta.estado.slice(1);
                            const btnCerrarHtml = encuesta.estado === 'publicada'
                                ? `<button class="btn-cerrar" data-id="${encuesta.id_encuesta}" data-nuevo-estado="cerrada"><i class="fa-solid fa-lock"></i> Cerrar</button>`
                                : `<button class="btn-cerrar" data-id="${encuesta.id_encuesta}" data-nuevo-estado="publicada" style="background-color: #28a745; color: white;"><i class="fa-solid fa-lock-open"></i> Re-Publicar</button>`;

                            const encuestaHtml = `
                                <div class="encuesta-item">
                                    <div class="encuesta-info">
                                        <h3>${encuesta.titulo}</h3>
                                        <div><small>Creada: ${new Date(encuesta.fecha_creacion).toLocaleDateString()}</small><span class="${estadoClase}">${estadoTexto}</span></div>
                                    </div>
                                    <div class="encuesta-acciones">
                                        <button class="btn-resultados" data-id="${encuesta.id_encuesta}"><i class="fa-solid fa-chart-pie"></i> Resultados</button>
                                        ${btnCerrarHtml}
                                        <button class="btn-eliminar" data-id="${encuesta.id_encuesta}"><i class="fa-solid fa-trash-alt"></i> Eliminar</button>
                                    </div>
                                </div>`;
                            container.append(encuestaHtml);
                        });
                    } else { container.html(`<p style="color: red;">Error al cargar encuestas: ${response.mensaje}</p>`); }
                },
                error: function() { container.html('<p style="color: red;">Error de conexión o tu sesión ha expirado. Por favor, recarga la página.</p>'); }
            });
        }

        // --- CARGAR VISTA: CREAR ENCUESTA ---
        function cargarFormCrear() {
            activarTab('#btn-tab-crear');
            $('#publish-button-placeholder').html(`<button type="submit" form="form-crear-encuesta" class="btn-publish"><i class="fa-solid fa-paper-plane"></i> Publicar</button>`);
            const container = $('#dashboard-content-container');
            const formHtml = `
                <form id="form-crear-encuesta" class="form-builder-container">
                    <div class="survey-header-editor">
                        <input type="text" id="titulo" name="titulo" placeholder="Título del formulario" required>
                        <textarea id="descripcion" name="descripcion" placeholder="Descripción del formulario"></textarea>
                        <div class="form-group" style="margin-top: 15px;">
                            <label style="font-size: 0.9rem; color: #555;">Visibilidad:</label>
                            <select id="visibilidad" name="visibilidad" style="font-size: 0.9rem; padding: 5px;">
                                <option value="identificada">Identificada (Mostrar mi nombre)</option>
                                <option value="anonima">Anónima</option>
                            </select>
                        </div>
                    </div>
                    <div id="preguntas-container"></div>
                    <button type="button" id="btn-add-pregunta" class="btn-add-pregunta"><i class="fa-solid fa-plus"></i> Añadir Pregunta</button>
                </form>`;
            container.html(formHtml);
            preguntaIndex = 0; // Resetear contador
            agregarPregunta(); // Añadir la primera
        }

        // --- CARGAR VISTA: RESULTADOS ---
        function cargarResultados(idEncuesta) {
            activarTab(null);
            $('#publish-button-placeholder').empty();
            const container = $('#dashboard-content-container');
            container.html('<div id="loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando resultados...</div>');

            $.ajax({
                url: `../api/obtenerResultados.php?id_encuesta=${idEncuesta}`, method: 'GET', dataType: 'json',
                success: function(response) {
                    if (response.success && response.resultados) {
                        const r = response.resultados;
                        let html = `<div class="resultados-container"><div class="resultados-header"><h2>Resultados: ${r.titulo}</h2><p>Visibilidad: ${r.visibilidad} | Estado: ${r.estado}</p></div>`;
                        const totalRespuestas = r.resumen_participacion.respuestas_anonimas + r.resumen_participacion.respuestas_identificadas;
                        if (totalRespuestas > 0) {
                            html += `<h3>Resumen de Participación (${totalRespuestas} respuestas totales)</h3><div class="pie-chart-container"><canvas id="pieChartParticipacion"></canvas></div>`;
                        } else { html += `<div style="text-align: center; padding: 30px; border: 1px dashed #ccc; border-radius: 8px; margin-top: 20px;"><i class="fa-solid fa-inbox fa-2x" style="color: #ccc; margin-bottom: 15px;"></i><p><strong>Aún no hay respuestas</strong> para esta encuesta.</p></div>`; }

                        if (totalRespuestas > 0 && r.preguntas) {
                            r.preguntas.forEach((preg, index) => {
                                html += `<div class="pregunta-resultados"><h4>${index + 1}. ${preg.texto_pregunta} (${preg.tipo_pregunta})</h4>`;
                                if (preg.tipo_pregunta === 'abierta') {
                                    if (preg.resultados && preg.resultados.length > 0) { preg.resultados.forEach(res => { html += `<div class="respuesta-abierta">"${res.texto_respuesta}" <span>- ${res.participante}</span></div>`; }); } else { html += `<p><em>No hay respuestas abiertas.</em></p>`; }
                                } else {
                                    if (preg.resultados && preg.resultados.length > 0) {
                                        preg.resultados.forEach(res => { html += `<div class="opcion-resultado"><span class="texto">${res.texto_opcion}:</span> <span class="conteo">${res.conteo} respuesta(s)</span>`; if (res.participantes && res.participantes.length > 0) { html += `<div class="participante-lista">(${res.participantes.join(', ')})</div>`; } html += `</div>`; });
                                    } else { html += `<p><em>No hay respuestas para estas opciones.</em></p>`; }
                                }
                                html += `</div>`;
                            });
                        }
                        html += `</div>`;
                        container.html(html);

                        if (totalRespuestas > 0) {
                            try {
                                const ctx = document.getElementById('pieChartParticipacion').getContext('2d');
                                new Chart(ctx, { type: 'pie', data: { labels: ['Identificadas', 'Anónimas'], datasets: [{ label: '# de Respuestas', data: [r.resumen_participacion.respuestas_identificadas, r.resumen_participacion.respuestas_anonimas], backgroundColor: ['rgba(75, 192, 192, 0.7)', 'rgba(201, 203, 207, 0.7)'], borderColor: ['rgba(75, 192, 192, 1)', 'rgba(201, 203, 207, 1)'], borderWidth: 1 }] }, options: { responsive: true, maintainAspectRatio: true } });
                            } catch (e) { console.error("Error al crear el gráfico:", e); }
                        }
                    } else { container.html(`<p style="color: red;">Error: ${response.mensaje || 'No se pudieron cargar los resultados.'}</p>`); }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("Error AJAX:", textStatus, errorThrown, jqXHR.responseText); let errorMsg = 'Error de conexión al cargar resultados.'; if (jqXHR.status === 404) { errorMsg = 'Error: No se encontró la API de resultados (404).'; } else if (jqXHR.status === 500) { errorMsg = 'Error interno del servidor al procesar los resultados (500). Revisa los logs de PHP.'; } else if (textStatus === 'parsererror') { errorMsg = 'Error: La respuesta del servidor no es un JSON válido.'; } container.html(`<p style="color: red;">${errorMsg}</p>`);
                }
            });
        }

        // --- FUNCIONES DEL FORM BUILDER ---
        function agregarPregunta() {
            const index = preguntaIndex++;
            const preguntaHtml = `<div class="pregunta-block" data-index="${index}"><div class="pregunta-header"><input type="text" name="preguntas[${index}][texto_pregunta]" placeholder="Pregunta sin título" required><select name="preguntas[${index}][tipo_pregunta]" class="tipo-pregunta-selector"><option value="opcion_multiple">Opción Múltiple</option><option value="abierta">Respuesta Corta</option><option value="si_no">Sí / No</option></select></div><div class="opciones-container"></div><div class="pregunta-footer"><button type="button" class="btn-delete-pregunta" title="Eliminar Pregunta"><i class="fa-solid fa-trash-alt"></i></button></div></div>`;
            $('#preguntas-container').append(preguntaHtml);
            $(`.pregunta-block[data-index="${index}"] .tipo-pregunta-selector`).trigger('change');
        }
        function agregarOpcion(container, indexPregunta, opcionIndex) {
            const opcionHtml = `<div class="opcion-item"><i class="far fa-circle" style="color: #ccc;"></i><input type="text" name="preguntas[${indexPregunta}][opciones][${opcionIndex}][texto_opcion]" placeholder="Opción ${opcionIndex + 1}"><button type="button" class="btn-delete-opcion" title="Eliminar Opción">&times;</button></div>`;
            container.append(opcionHtml);
        }

        // --- MANEJADORES DE EVENTOS ---
        $(document).ready(function() {
            cargarMisEncuestas(); // Carga inicial

            // Pop-up contraseña temporal
            <?php if ($esTemporal): ?>
            const specialCharRegex = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]+/;
            Swal.fire({ title: 'Cambio de Contraseña Requerido', text: 'Por seguridad, debes establecer una nueva contraseña.', icon: 'warning', allowOutsideClick: false, allowEscapeKey: false, html: `<div style="text-align: left; margin-top: 15px;"><label for="swal-pass1" class="swal2-label">Nueva Contraseña</label><input type="password" id="swal-pass1" class="swal2-input" placeholder="Nueva contraseña"><label for="swal-pass2" class="swal2-label" style="margin-top: 10px;">Confirmar Contraseña</label><input type="password" id="swal-pass2" class="swal2-input" placeholder="Confirmar contraseña"><div id="swal-hint" style="font-size: 0.8em; color: #666; margin-top: 10px;">*Mínimo 8 carac, 1 especial (ej. !@#$) y terminar con AL</div></div>`, confirmButtonText: 'Guardar Contraseña', showLoaderOnConfirm: true,
                preConfirm: () => { const pass1 = document.getElementById('swal-pass1').value; const pass2 = document.getElementById('swal-pass2').value; if (!pass1 || !pass2) { Swal.showValidationMessage('Ambos campos son obligatorios.'); return false; } if (pass1 !== pass2) { Swal.showValidationMessage('Las contraseñas no coinciden.'); return false; } const isLengthOk = pass1.length >= 8; const hasSpecialChar = specialCharRegex.test(pass1); const isAlOk = pass1.toLowerCase().endsWith('al'); if (!isLengthOk || !hasSpecialChar || !isAlOk) { Swal.showValidationMessage('La contraseña no cumple los requisitos.'); return false; } return { nueva_contrasena: pass1, confirmar_contrasena: pass2 }; }
            }).then((result) => { if (result.isConfirmed && result.value) { $.ajax({ url: '../api/cambiarContrasena.php', method: 'POST', contentType: 'application/json', data: JSON.stringify(result.value), success: function(response) { if (response.success) { Swal.fire('¡Éxito!', 'Tu contraseña ha sido actualizada.', 'success'); } else { Swal.showValidationMessage(response.mensaje); } }, error: function() { Swal.showValidationMessage('Error de conexión.'); } }); } });
            <?php endif; ?>

            // Navegación Pestañas
            $('#btn-tab-mis-encuestas').on('click', (e) => { e.preventDefault(); cargarMisEncuestas(); });
            $('#btn-tab-crear').on('click', (e) => { e.preventDefault(); cargarFormCrear(); });

            // Eventos Form Builder
            $('#dashboard-content-container').on('click', '#btn-add-pregunta', agregarPregunta);
            $('#dashboard-content-container').on('click', '.btn-delete-pregunta', function() { $(this).closest('.pregunta-block').remove(); });
            $('#dashboard-content-container').on('change', '.tipo-pregunta-selector', function() {
                const tipo = $(this).val(); const containerOpciones = $(this).closest('.pregunta-block').find('.opciones-container');
                const indexPregunta = $(this).closest('.pregunta-block').data('index'); containerOpciones.empty();
                if (tipo === 'opcion_multiple' || tipo === 'seleccion_multiple') {
                    agregarOpcion(containerOpciones, indexPregunta, 0); containerOpciones.append(`<button type="button" class="btn-add-opcion"><i class="fa-solid fa-plus"></i> Añadir opción</button>`);
                } else if (tipo === 'si_no') {
                     containerOpciones.html(`<div class="opcion-item"><i class="far fa-circle" style="color: #ccc;"></i> <input type="text" value="Sí" disabled></div><div class="opcion-item"><i class="far fa-circle" style="color: #ccc;"></i> <input type="text" value="No" disabled></div>`);
                }
            });
            $('#dashboard-content-container').on('click', '.btn-add-opcion', function() {
                const container = $(this).closest('.opciones-container'); const indexPregunta = $(this).closest('.pregunta-block').data('index');
                const opcionIndex = container.find('.opcion-item').length; agregarOpcion(container, indexPregunta, opcionIndex); $(this).appendTo(container);
            });
            $('#dashboard-content-container').on('click', '.btn-delete-opcion', function() { $(this).closest('.opcion-item').remove(); });

            // Delegación botones Mis Encuestas
            $('#dashboard-content-container').on('click', '.btn-resultados', function() { const idEncuesta = $(this).data('id'); cargarResultados(idEncuesta); });
            $('#dashboard-content-container').on('click', '.btn-eliminar', function() {
                const idEncuesta = $(this).data('id'); Swal.fire({title: '¿Estás seguro?', text: "Esto archivará la encuesta y ya no será visible.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', cancelButtonText: 'Cancelar', confirmButtonText: 'Sí, eliminar'
                }).then((result) => { if (result.isConfirmed) { $.ajax({url: '../api/eliminarEncuesta.php', method: 'POST', contentType: 'application/json', data: JSON.stringify({ id_encuesta: idEncuesta }), success: function(res) { if(res.success) { Toast.fire({ icon: 'success', title: 'Encuesta eliminada.' }); cargarMisEncuestas(); } else { Toast.fire({ icon: 'error', title: res.mensaje }); } } }); } });
            });
            $('#dashboard-content-container').on('click', '.btn-cerrar', function() {
                const idEncuesta = $(this).data('id'); const nuevoEstado = $(this).data('nuevo-estado'); $.ajax({url: '../api/actualizarEstadoEncuesta.php', method: 'POST', contentType: 'application/json', data: JSON.stringify({ id_encuesta: idEncuesta, nuevo_estado: nuevoEstado }), success: function(res) { if(res.success) { Toast.fire({ icon: 'success', title: `Encuesta ${nuevoEstado}.` }); cargarMisEncuestas(); } else { Toast.fire({ icon: 'error', title: res.mensaje }); } } });
            });

            // Submit Formulario Crear Encuesta
            $('#dashboard-content-container').on('submit', '#form-crear-encuesta', function(e) {
                e.preventDefault(); const datosEncuesta = { titulo: $('#titulo').val(), descripcion: $('#descripcion').val(), visibilidad: $('#visibilidad').val(), preguntas: [] };
                $('.pregunta-block').each(function(index) {
                    const block = $(this); const preguntaData = { texto_pregunta: block.find('input[name*="[texto_pregunta]"]').val(), tipo_pregunta: block.find('select[name*="[tipo_pregunta]"]').val(), orden: index + 1, opciones: [] };
                    block.find('.opcion-item input[type="text"]').each(function() { const textoOpcion = $(this).val(); if ($(this).prop('disabled') === false && textoOpcion.trim() !== "") { preguntaData.opciones.push({ texto_opcion: textoOpcion }); } }); // Evitar opciones deshabilitadas (Sí/No)
                    if (preguntaData.tipo_pregunta === 'si_no') { preguntaData.opciones.push({ texto_opcion: 'Sí' }); preguntaData.opciones.push({ texto_opcion: 'No' }); } datosEncuesta.preguntas.push(preguntaData);
                });
                if(datosEncuesta.preguntas.length === 0) { Swal.fire('Error', 'Debes añadir al menos una pregunta.', 'error'); return; }
                const publishBtn = $('.btn-publish'); publishBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Publicando...');
                $.ajax({ url: '../api/crearEncuesta.php', method: 'POST', contentType: 'application/json', data: JSON.stringify(datosEncuesta), success: function(res) { if(res.success) { Swal.fire('¡Éxito!', 'Encuesta creada correctamente.', 'success'); cargarMisEncuestas(); } else { Swal.fire('Error', res.mensaje, 'error'); publishBtn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane"></i> Publicar'); } }, error: function() { Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error'); publishBtn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane"></i> Publicar'); } });
            });
        });
    </script>
</body>
</html>