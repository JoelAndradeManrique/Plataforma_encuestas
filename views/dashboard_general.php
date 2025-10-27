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
        // Configuración global de Toasts (notificaciones)
        const Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true,
            didOpen: (toast) => { toast.onmouseenter = Swal.stopTimer; toast.onmouseleave = Swal.resumeTimer; }
        });
        let preguntaIndex = 0; // Contador global para nombres únicos en form builder

        // --- NAVEGACIÓN Y UTILIDADES ---
        // Marcar pestaña activa en la cabecera
        function activarTab(tabId) {
            $('.tab-link').removeClass('active');
            if (tabId) { // Puede ser null si cargamos resultados o edición
                 $(tabId).addClass('active');
            }
        }

        // --- CARGAR VISTAS DINÁMICAS ---

        // 1. Cargar la lista de "Mis Encuestas"
        function cargarMisEncuestas() {
            activarTab('#btn-tab-mis-encuestas');
            $('#publish-button-placeholder').empty(); // Limpiar botón Publicar/Guardar/Actualizar de la cabecera
            const container = $('#dashboard-content-container');
            container.html('<div id="loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando tus encuestas...</div>');

            $.ajax({
                url: '../api/misEncuestas.php', // API para obtener encuestas del encuestador logueado
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Si no hay encuestas, mostrar mensaje
                        if (response.encuestas.length === 0) {
                            container.html('<h3>No has creado ninguna encuesta todavía.</h3><p>Usa la pestaña "Crear Nueva Encuesta" para empezar.</p>');
                            return;
                        }
                        container.empty(); // Limpiar el mensaje de "cargando"

                        // Iterar y construir HTML para cada encuesta
                        response.encuestas.forEach(function(encuesta) {
                            const estadoClase = `estado-${encuesta.estado}`;
                            const estadoTexto = encuesta.estado.charAt(0).toUpperCase() + encuesta.estado.slice(1); // Capitalizar

                            // Lógica para mostrar botones según el estado de la encuesta
                            let botonesAccion = '';
                            if (encuesta.estado === 'borrador') {
                                botonesAccion = `
                                    <button class="btn-editar" data-id="${encuesta.id_encuesta}"><i class="fa-solid fa-edit"></i> Editar</button>
                                    <button class="btn-publish-lista" data-id="${encuesta.id_encuesta}"><i class="fa-solid fa-paper-plane"></i> Publicar</button>`;
                            } else if (encuesta.estado === 'publicada') {
                                botonesAccion = `
                                    <button class="btn-resultados" data-id="${encuesta.id_encuesta}"><i class="fa-solid fa-chart-pie"></i> Resultados</button>
                                    <button class="btn-cerrar" data-id="${encuesta.id_encuesta}" data-nuevo-estado="cerrada"><i class="fa-solid fa-lock"></i> Cerrar</button>`;
                            } else if (encuesta.estado === 'cerrada') {
                                botonesAccion = `
                                    <button class="btn-resultados" data-id="${encuesta.id_encuesta}"><i class="fa-solid fa-chart-pie"></i> Resultados</button>
                                    <button class="btn-cerrar" data-id="${encuesta.id_encuesta}" data-nuevo-estado="publicada" style="background-color: #28a745; color: white;"><i class="fa-solid fa-lock-open"></i> Re-Publicar</button>`;
                            }
                            // Botón Eliminar siempre presente (mientras no esté archivada)
                            botonesAccion += ` <button class="btn-eliminar" data-id="${encuesta.id_encuesta}"><i class="fa-solid fa-trash-alt"></i> Eliminar</button>`;

                            // Formatear fecha
                            let fechaFormateada = 'Fecha desconocida';
                            try {
                                fechaFormateada = new Date(encuesta.fecha_creacion).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
                            } catch(e) { console.error("Error formateando fecha", encuesta.fecha_creacion); }

                            // HTML de la tarjeta de encuesta
                            const encuestaHtml = `
                                <div class="encuesta-item">
                                    <div class="encuesta-info">
                                        <h3>${encuesta.titulo || 'Encuesta sin título'}</h3>
                                        <div>
                                            <small>Creada: ${fechaFormateada}</small>
                                            <span class="${estadoClase}">${estadoTexto}</span>
                                        </div>
                                    </div>
                                    <div class="encuesta-acciones">
                                        ${botonesAccion}
                                    </div>
                                </div>`;
                            container.append(encuestaHtml);
                        });
                    } else {
                        // Error devuelto por la API
                        container.html(`<p style="color: red;">Error al cargar encuestas: ${response.mensaje}</p>`);
                    }
                },
                error: function(jqXHR) { // Error de conexión o sesión expirada
                    console.error("Error AJAX al cargar encuestas:", jqXHR.responseText);
                    let errorMsg = 'Error de conexión o tu sesión ha expirado.';
                    if(jqXHR.status === 403) { errorMsg = 'Acceso denegado. Tu sesión puede haber expirado.'; }
                    container.html(`<p style="color: red;">${errorMsg} Por favor, recarga la página.</p>`);
                }
            });
        }

        // 2. Cargar el formulario para "Crear Nueva Encuesta"
        function cargarFormCrear() {
            activarTab('#btn-tab-crear');
            const container = $('#dashboard-content-container');

            // Añadir botón "Guardar Encuesta" a la cabecera
            $('#publish-button-placeholder').html(`
                <button type="submit" form="form-crear-encuesta" class="btn-publish" style="background-color: #007bff; color: white;">
                    <i class="fa-solid fa-save"></i> Guardar Encuesta
                </button>
            `);

            // HTML del Form Builder (incluye select de estado)
            const formHtml = `
                <form id="form-crear-encuesta" class="form-builder-container">
                    <div class="survey-header-editor">
                        <input type="text" id="titulo" name="titulo" placeholder="Título del formulario" required>
                        <textarea id="descripcion" name="descripcion" placeholder="Descripción del formulario"></textarea>
                        <div style="display: flex; gap: 20px; margin-top: 15px; flex-wrap: wrap;">
                            <div class="form-group" style="flex: 1; min-width: 150px;">
                                <label style="font-size: 0.9rem; color: #555;">Visibilidad:</label>
                                <select id="visibilidad" name="visibilidad" style="font-size: 0.9rem; padding: 5px;">
                                    <option value="identificada">Identificada</option>
                                    <option value="anonima">Anónima</option>
                                </select>
                            </div>
                            <div class="form-group" style="flex: 1; min-width: 150px;">
                                <label style="font-size: 0.9rem; color: #555;">Estado inicial:</label>
                                <select id="estado" name="estado" style="font-size: 0.9rem; padding: 5px;">
                                    <option value="borrador">Borrador</option>
                                    <option value="publicada">Publicada</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div id="preguntas-container">
                        </div>
                    <button type="button" id="btn-add-pregunta" class="btn-add-pregunta"><i class="fa-solid fa-plus"></i> Añadir Pregunta</button>
                </form>`;
            container.html(formHtml);
            preguntaIndex = 0; // Resetear contador de preguntas
            agregarPregunta(); // Añadir la primera pregunta por defecto
        }

        // 3. Cargar la vista de "Resultados"
        function cargarResultados(idEncuesta) {
            activarTab(null); // Desmarcar pestañas principales
            $('#publish-button-placeholder').empty(); // Quitar botón Publicar/Guardar
            const container = $('#dashboard-content-container');
            container.html('<div id="loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando resultados...</div>');

            $.ajax({
                url: `../api/obtenerResultados.php?id_encuesta=${idEncuesta}`,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.resultados) {
                        const r = response.resultados;
                        let html = `<div class="resultados-container">`; // Contenedor principal de resultados
                        html += `<div class="resultados-header"><h2>Resultados: ${r.titulo}</h2><p>Visibilidad: ${r.visibilidad} | Estado: ${r.estado}</p></div>`; // Cabecera

                        const totalRespuestas = r.resumen_participacion.respuestas_anonimas + r.resumen_participacion.respuestas_identificadas;

                        // Sección del Gráfico de Pastel
                        if (totalRespuestas > 0) {
                            html += `<h3>Resumen de Participación (${totalRespuestas} respuestas totales)</h3>`;
                            html += `<div class="pie-chart-container"><canvas id="pieChartParticipacion"></canvas></div>`; // Canvas para el gráfico
                        } else {
                            // Mensaje si no hay respuestas
                            html += `<div style="text-align: center; padding: 30px; border: 1px dashed #ccc; border-radius: 8px; margin-top: 20px;"><i class="fa-solid fa-inbox fa-2x" style="color: #ccc; margin-bottom: 15px;"></i><p><strong>Aún no hay respuestas</strong> para esta encuesta.</p></div>`;
                        }

                        // Sección de Desglose por Pregunta (solo si hay respuestas)
                        if (totalRespuestas > 0 && r.preguntas) {
                            r.preguntas.forEach((preg, index) => {
                                html += `<div class="pregunta-resultados"><h4>${index + 1}. ${preg.texto_pregunta} (${preg.tipo_pregunta})</h4>`;
                                // Lógica para mostrar resultados según tipo de pregunta
                                if (preg.tipo_pregunta === 'abierta') {
                                    if (preg.resultados && preg.resultados.length > 0) {
                                        preg.resultados.forEach(res => { html += `<div class="respuesta-abierta">"${res.texto_respuesta}" <span>- ${res.participante}</span></div>`; });
                                    } else { html += `<p><em>No hay respuestas abiertas.</em></p>`; }
                                } else { // Preguntas de opciones
                                    if (preg.resultados && preg.resultados.length > 0) {
                                        preg.resultados.forEach(res => {
                                            html += `<div class="opcion-resultado"><span class="texto">${res.texto_opcion}:</span> <span class="conteo">${res.conteo} respuesta(s)</span>`;
                                            // Mostrar lista de participantes si la encuesta es identificada
                                            if (r.visibilidad === 'identificada' && res.participantes && res.participantes.length > 0) {
                                                html += `<div class="participante-lista">(${res.participantes.join(', ')})</div>`;
                                            }
                                            html += `</div>`; // Cierre opcion-resultado
                                        });
                                    } else { html += `<p><em>No hay respuestas para estas opciones.</em></p>`; }
                                }
                                html += `</div>`; // Cierre pregunta-resultados
                            });
                        }
                        html += `</div>`; // Cierre resultados-container
                        container.html(html); // Pinta todo el HTML generado

                        // Inicializar el Gráfico de Pastel si hay datos
                        if (totalRespuestas > 0) {
                            try {
                                const ctx = document.getElementById('pieChartParticipacion').getContext('2d');
                                new Chart(ctx, {
                                    type: 'pie',
                                    data: {
                                        labels: ['Identificadas', 'Anónimas'],
                                        datasets: [{
                                            label: '# de Respuestas',
                                            data: [r.resumen_participacion.respuestas_identificadas, r.resumen_participacion.respuestas_anonimas],
                                            backgroundColor: ['rgba(75, 192, 192, 0.7)', 'rgba(201, 203, 207, 0.7)'], // Verde agua y Gris
                                            borderColor: ['rgba(75, 192, 192, 1)', 'rgba(201, 203, 207, 1)'],
                                            borderWidth: 1
                                        }]
                                    },
                                    options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'top' } } } // Opciones del gráfico
                                });
                            } catch (e) { console.error("Error al crear el gráfico:", e); }
                        }
                    } else {
                        // Error devuelto por la API al obtener resultados
                        container.html(`<p style="color: red;">Error: ${response.mensaje || 'No se pudieron cargar los resultados.'}</p>`);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) { // Error de conexión
                    console.error("Error AJAX resultados:", textStatus, errorThrown, jqXHR.responseText);
                    let msg = 'Error de conexión.';
                    if (jqXHR.status === 404) msg = 'API no encontrada (404).';
                    else if (jqXHR.status === 500) msg = 'Error interno (500).';
                    else if (textStatus === 'parsererror') msg = 'Respuesta no es JSON.';
                    container.html(`<p style="color: red;">${msg}</p>`);
                }
            });
        }

         // 4. Cargar Formulario para EDITAR Borrador
        function cargarFormEditar(idEncuesta) {
            activarTab(null); // Desmarcar pestañas principales
            const container = $('#dashboard-content-container');
            container.html('<div id="loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando datos del borrador...</div>');

            // Llamar a la nueva API para obtener los detalles editables
            $.ajax({
                url: `../api/obtenerEncuestaEditable.php?id_encuesta=${idEncuesta}`,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.encuesta) {
                        const encuesta = response.encuesta;

                        // Añadir botón "Actualizar Borrador" a la cabecera
                        $('#publish-button-placeholder').html(`
                            <button type="submit" form="form-editar-encuesta" class="btn-publish" style="background-color: #ffc107; color: #333;">
                                <i class="fa-solid fa-save"></i> Actualizar Borrador
                            </button>
                        `);

                        // Construir el HTML del formulario, RELLENANDO los valores
                        // Usamos un ID diferente para el form de edición
                        let formHtml = `
                            <form id="form-editar-encuesta" class="form-builder-container">
                                <input type="hidden" name="id_encuesta" value="${encuesta.id_encuesta}">

                                <div class="survey-header-editor">
                                    <input type="text" id="titulo" name="titulo" placeholder="Título del formulario" value="${encuesta.titulo || ''}" required>
                                    <textarea id="descripcion" name="descripcion" placeholder="Descripción del formulario">${encuesta.descripcion || ''}</textarea>
                                    <div style="display: flex; gap: 20px; margin-top: 15px; flex-wrap: wrap;">
                                        <div class="form-group" style="flex: 1; min-width: 150px;">
                                            <label style="font-size: 0.9rem; color: #555;">Visibilidad:</label>
                                            <select id="visibilidad" name="visibilidad" style="font-size: 0.9rem; padding: 5px;">
                                                <option value="identificada" ${encuesta.visibilidad === 'identificada' ? 'selected' : ''}>Identificada</option>
                                                <option value="anonima" ${encuesta.visibilidad === 'anonima' ? 'selected' : ''}>Anónima</option>
                                            </select>
                                        </div>
                                        <div class="form-group" style="flex: 1; min-width: 150px;">
                                           <label style="font-size: 0.9rem; color: #555;">Estado:</label>
                                            <input type="text" value="Borrador" style="font-size: 0.9rem; padding: 5px; background: #eee;" disabled>
                                            <input type="hidden" name="estado" value="borrador">
                                        </div>
                                    </div>
                                </div>
                                <div id="preguntas-container">
                                    {/* Las preguntas existentes se añadirán aquí */}
                                </div>
                                <button type="button" id="btn-add-pregunta" class="btn-add-pregunta"><i class="fa-solid fa-plus"></i> Añadir Pregunta</button>
                            </form>`;
                        container.html(formHtml);
                        preguntaIndex = 0; // Resetear contador

                        // Generar los bloques de preguntas y opciones existentes
                        if (encuesta.preguntas && encuesta.preguntas.length > 0) {
                            encuesta.preguntas.forEach(pregunta => {
                                // Llamamos a una función modificada para añadir pregunta con datos
                                agregarPreguntaConDatos(pregunta);
                            });
                        } else {
                            // Si no tiene preguntas, añadir una vacía
                            agregarPregunta();
                        }

                    } else {
                        // Error devuelto por la API (ej. no es borrador, no es tuyo)
                        Swal.fire('Error', response.mensaje || 'No se pudo cargar el borrador.', 'error');
                        cargarMisEncuestas(); // Volver a la lista si falla la carga
                    }
                },
                error: function(jqXHR) { // Error de conexión
                    console.error("Error AJAX cargar borrador:", jqXHR.responseText);
                    let msg = 'Error de conexión al cargar el borrador.';
                    if(jqXHR.status === 404) msg = 'Borrador no encontrado.';
                    Swal.fire('Error', msg, 'error');
                    cargarMisEncuestas(); // Volver a la lista
                }
            });
        }


        // --- FUNCIONES DEL FORM BUILDER ---
        // Función para añadir bloque de pregunta (vacío)
        function agregarPregunta() {
            const index = preguntaIndex++;
            // HTML para un nuevo bloque de pregunta (CORREGIDO)
            const preguntaHtml = `
                <div class="pregunta-block" data-index="${index}">
                    <div class="pregunta-header">
                        <input type="text" name="preguntas[${index}][texto_pregunta]" placeholder="Pregunta sin título" required>
                        <select name="preguntas[${index}][tipo_pregunta]" class="tipo-pregunta-selector">
                            <option value="opcion_multiple">Opción Múltiple</option>
                            <option value="abierta">Respuesta Corta</option>
                            <option value="si_no">Verdadero / Falso</option>
                            {/* Eliminada seleccion_multiple */}
                            <option value="escala">Escala (1-5)</option>
                        </select>
                    </div>
                    <div class="opciones-container"></div>
                    <div class="pregunta-footer">
                        <button type="button" class="btn-delete-pregunta" title="Eliminar Pregunta"><i class="fa-solid fa-trash-alt"></i></button>
                    </div>
                </div>`;
            $('#preguntas-container').append(preguntaHtml);
            // Activar la lógica inicial para el tipo de pregunta seleccionado
            $(`.pregunta-block[data-index="${index}"] .tipo-pregunta-selector`).trigger('change');
        }
        // Función para añadir bloque de pregunta (con datos - para editar)
        function agregarPreguntaConDatos(pregunta) {
            const index = preguntaIndex++;
            // Preseleccionar el tipo correcto (CORREGIDO)
             const tipoOptions = ['opcion_multiple', 'abierta', 'si_no', 'escala'] // Eliminado seleccion_multiple
                .map(tipo => {
                    let texto = tipo.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                    if (tipo === 'si_no') texto = 'Verdadero / Falso';
                    // No necesitamos caso especial para seleccion_multiple ahora
                    return `<option value="${tipo}" ${pregunta.tipo_pregunta === tipo ? 'selected' : ''}>${texto}</option>`;
                })
                .join('');


            const preguntaHtml = `
                <div class="pregunta-block" data-index="${index}">
                    <div class="pregunta-header">
                        <input type="text" name="preguntas[${index}][texto_pregunta]" placeholder="Pregunta sin título" value="${pregunta.texto_pregunta || ''}" required>
                        <select name="preguntas[${index}][tipo_pregunta]" class="tipo-pregunta-selector">${tipoOptions}</select>
                    </div>
                    <div class="opciones-container"></div>
                    <div class="pregunta-footer">
                        <button type="button" class="btn-delete-pregunta" title="Eliminar Pregunta"><i class="fa-solid fa-trash-alt"></i></button>
                    </div>
                </div>`;
            $('#preguntas-container').append(preguntaHtml);

            // Rellenar opciones si existen (CORREGIDO para no incluir seleccion_multiple explícitamente)
            const $containerOpciones = $(`.pregunta-block[data-index="${index}"] .opciones-container`);
            if (pregunta.opciones && pregunta.opciones.length > 0) {
                 if (pregunta.tipo_pregunta === 'opcion_multiple') { // Solo aplica a opcion_multiple ahora
                    pregunta.opciones.forEach((opcion, opIndex) => {
                        agregarOpcionConDatos($containerOpciones, index, opIndex, opcion.texto_opcion, pregunta.tipo_pregunta);
                    });
                     $containerOpciones.append(`<button type="button" class="btn-add-opcion"><i class="fa-solid fa-plus"></i> Añadir opción</button>`);
                }
            } else if (pregunta.tipo_pregunta === 'opcion_multiple') {
                 agregarOpcion($containerOpciones, index, 0); // Añadir una vacía
                 $containerOpciones.append(`<button type="button" class="btn-add-opcion"><i class="fa-solid fa-plus"></i> Añadir opción</button>`);
            }

            // Activar la lógica visual para el tipo (ej. mostrar opciones fijas para Sí/No o Escala)
            $(`.pregunta-block[data-index="${index}"] .tipo-pregunta-selector`).trigger('change');
        }
        // Función para añadir opción (vacía)
        function agregarOpcion(container, indexPregunta, opcionIndex) {
             const tipoPregunta = container.closest('.pregunta-block').find('.tipo-pregunta-selector').val();
             // El icono siempre será círculo ahora
             const iconClass = 'far fa-circle';
            const opcionHtml = `<div class="opcion-item"><i class="${iconClass}" style="color: #ccc;"></i><input type="text" name="preguntas[${indexPregunta}][opciones][${opcionIndex}][texto_opcion]" placeholder="Opción ${opcionIndex + 1}" required><button type="button" class="btn-delete-opcion" title="Eliminar Opción">&times;</button></div>`;
            container.append(opcionHtml);
            container.find('.opcion-item:last-child input').focus();
        }
         // Función para añadir opción (con datos - para editar)
        function agregarOpcionConDatos(container, indexPregunta, opcionIndex, textoOpcion, tipoPregunta) {
             // El icono siempre será círculo ahora
            const iconClass = 'far fa-circle';
            const opcionHtml = `
                <div class="opcion-item">
                    <i class="${iconClass}" style="color: #ccc;"></i>
                    <input type="text" name="preguntas[${indexPregunta}][opciones][${opcionIndex}][texto_opcion]" placeholder="Opción ${opcionIndex + 1}" value="${textoOpcion || ''}" required>
                    <button type="button" class="btn-delete-opcion" title="Eliminar Opción">&times;</button>
                </div>`;
            container.append(opcionHtml);
        }


        // --- MANEJADORES DE EVENTOS PRINCIPALES ---
        $(document).ready(function() {
            cargarMisEncuestas(); // Carga inicial

            // Pop-up contraseña temporal
            <?php if ($esTemporal): ?>
            // (Código pop-up sin cambios)
            const specialCharRegex = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]+/;
            Swal.fire({ title: 'Cambio de Contraseña Requerido', text: 'Por seguridad, debes establecer una nueva contraseña.', icon: 'warning', allowOutsideClick: false, allowEscapeKey: false, html: `<div style="text-align: left; margin-top: 15px;"><label for="swal-pass1" class="swal2-label">Nueva Contraseña</label><input type="password" id="swal-pass1" class="swal2-input" placeholder="Nueva contraseña"><label for="swal-pass2" class="swal2-label" style="margin-top: 10px;">Confirmar Contraseña</label><input type="password" id="swal-pass2" class="swal2-input" placeholder="Confirmar contraseña"><div id="swal-hint" style="font-size: 0.8em; color: #666; margin-top: 10px;">*Mínimo 8 carac, 1 especial (ej. !@#$) y terminar con AL</div></div>`, confirmButtonText: 'Guardar Contraseña', showLoaderOnConfirm: true,
                preConfirm: () => { const p1 = $('#swal-pass1').val(); const p2 = $('#swal-pass2').val(); if (!p1 || !p2) { Swal.showValidationMessage('Campos obligatorios.'); return false; } if (p1 !== p2) { Swal.showValidationMessage('Contraseñas no coinciden.'); return false; } if (p1.length < 8 || !p1.toLowerCase().endsWith('al') || !specialCharRegex.test(p1)) { Swal.showValidationMessage('Contraseña no cumple requisitos.'); return false; } return { nueva_contrasena: p1, confirmar_contrasena: p2 }; }
            }).then((result) => { if (result.isConfirmed && result.value) { $.ajax({ url: '../api/cambiarContrasena.php', method: 'POST', contentType: 'application/json', data: JSON.stringify(result.value), success: (r) => { if (r.success) Swal.fire('¡Éxito!', 'Contraseña actualizada.', 'success'); else Swal.showValidationMessage(r.mensaje); }, error: () => Swal.showValidationMessage('Error conexión.') }); } });
            <?php endif; ?>

            // --- Navegación por Pestañas ---
            $('#btn-tab-mis-encuestas').on('click', (e) => { e.preventDefault(); cargarMisEncuestas(); });
            $('#btn-tab-crear').on('click', (e) => { e.preventDefault(); cargarFormCrear(); });

            // --- Eventos del Form Builder ---
            $('#dashboard-content-container').on('click', '#btn-add-pregunta', agregarPregunta);
            $('#dashboard-content-container').on('click', '.btn-delete-pregunta', function() { $(this).closest('.pregunta-block').remove(); });
            // Evento change corregido para no incluir seleccion_multiple
            $('#dashboard-content-container').on('change', '.tipo-pregunta-selector', function() {
                const tipo = $(this).val(); const $pb = $(this).closest('.pregunta-block'); const $oc = $pb.find('.opciones-container'); const ip = $pb.data('index'); $oc.empty();
                // Ahora solo opcion_multiple necesita opciones editables
                if (tipo === 'opcion_multiple') {
                    agregarOpcion($oc, ip, 0); $oc.append(`<button type="button" class="btn-add-opcion"><i class="fa-solid fa-plus"></i> Añadir opción</button>`);
                } else if (tipo === 'si_no') {
                     $oc.html(`<div class="opcion-item"><i class="far fa-circle" style="color: #ccc;"></i> <input type="text" value="Verdadero" disabled></div><div class="opcion-item"><i class="far fa-circle" style="color: #ccc;"></i> <input type="text" value="Falso" disabled></div>`); // Texto V/F
                } else if (tipo === 'escala') {
                     $oc.html(`<div style="display: flex; justify-content: space-between; padding: 0 10px;"><span>1</span><span>2</span><span>3</span><span>4</span><span>5</span></div><div style="display: flex; justify-content: space-between; padding: 0 10px; font-size: 0.8em; color: #666;"><span>(Muy insatisfecho)</span><span></span><span></span><span></span><span>(Muy satisfecho)</span></div>`);
                }
                 // Si es 'abierta', no hace nada
            });
            $('#dashboard-content-container').on('click', '.btn-add-opcion', function() { const $c = $(this).closest('.opciones-container'); const ip = $(this).closest('.pregunta-block').data('index'); const oi = $c.find('.opcion-item').length; agregarOpcion($c, ip, oi); $(this).appendTo($c); });
            $('#dashboard-content-container').on('click', '.btn-delete-opcion', function() { $(this).closest('.opcion-item').remove(); });

            // --- Delegación botones Mis Encuestas ---
            $('#dashboard-content-container').on('click', '.btn-resultados', function() { cargarResultados($(this).data('id')); });
            $('#dashboard-content-container').on('click', '.btn-eliminar', function() { const id = $(this).data('id'); Swal.fire({title: '¿Eliminar?', text: "Archivar encuesta.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Sí', showLoaderOnConfirm: true, preConfirm: () => $.ajax({ url: '../api/eliminarEncuesta.php', method: 'POST', contentType:'application/json', data: JSON.stringify({id_encuesta: id})}).catch(e=>Swal.showValidationMessage(`Error: ${e.statusText||'?'}`)), allowOutsideClick:()=>!Swal.isLoading() }).then((r) => { if(r.isConfirmed&&r.value&&r.value.success) { Toast.fire({icon:'success',title:'Encuesta eliminada.'}); cargarMisEncuestas(); } else if(r.isConfirmed) Swal.fire('Error', r.value.mensaje||'?', 'error'); }); });
            $('#dashboard-content-container').on('click', '.btn-publish-lista', function() { const id = $(this).data('id'); Swal.fire({ title: '¿Publicar?', text: "No podrás editarla después.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#28a745', confirmButtonText: 'Sí', showLoaderOnConfirm: true, preConfirm: () => $.ajax({ url: '../api/actualizarEstadoEncuesta.php', method: 'POST', contentType:'application/json', data: JSON.stringify({id_encuesta: id, nuevo_estado:'publicada'})}).catch(e=>Swal.showValidationMessage(`Error: ${e.statusText||'?'}`)), allowOutsideClick:()=>!Swal.isLoading() }).then((r) => { if(r.isConfirmed&&r.value&&r.value.success) { Toast.fire({icon:'success',title:'Encuesta publicada.'}); cargarMisEncuestas(); } else if(r.isConfirmed) Swal.fire('Error', r.value.mensaje||'?', 'error'); }); });
            $('#dashboard-content-container').on('click', '.btn-editar', function() { cargarFormEditar($(this).data('id')); });
            $('#dashboard-content-container').on('click', '.btn-cerrar', function() { const id = $(this).data('id'); const ne = $(this).data('nuevo-estado'); const $b = $(this).prop('disabled',true); $.ajax({ url: '../api/actualizarEstadoEncuesta.php', method: 'POST', contentType:'application/json', data: JSON.stringify({id_encuesta: id, nuevo_estado: ne}), success: (r)=>{ if(r.success) { Toast.fire({icon:'success',title:`Encuesta ${ne}.`}); cargarMisEncuestas(); } else { Toast.fire({icon:'error',title:r.mensaje}); $b.prop('disabled',false); }}, error: ()=>{ Toast.fire({icon:'error',title:'Error conexión.'}); $b.prop('disabled',false); } }); });

            // --- Submit del Formulario Crear Encuesta ---
            $('#dashboard-content-container').on('submit', '#form-crear-encuesta', function(e) {
                e.preventDefault(); const datosEncuesta = { titulo: $('#titulo').val().trim(), descripcion: $('#descripcion').val().trim(), visibilidad: $('#visibilidad').val(), estado: $('#estado').val(), preguntas: [] };
                if (!datosEncuesta.titulo) { Swal.fire('Error', 'Título obligatorio.', 'error'); $('#titulo').focus(); return; }
                $('.pregunta-block').each(function(index) { const block = $(this); const textoPregunta = block.find('input[name*="[texto_pregunta]"]').val().trim(); const tipoPregunta = block.find('select[name*="[tipo_pregunta]"]').val(); if (!textoPregunta) { console.warn(`Pregunta ${index+1} ignorada.`); return; } const preguntaData = { texto_pregunta: textoPregunta, tipo_pregunta: tipoPregunta, orden: index + 1, opciones: [] }; block.find('.opcion-item input[type="text"]').each(function() { const textoOpcion = $(this).val().trim(); if (!$(this).prop('disabled') && textoOpcion !== "") { preguntaData.opciones.push({ texto_opcion: textoOpcion }); } }); if (tipoPregunta === 'si_no') { preguntaData.opciones.push({texto_opcion:'Verdadero'}); preguntaData.opciones.push({texto_opcion:'Falso'}); } /* Lógica escala? */ datosEncuesta.preguntas.push(preguntaData); }); // Texto V/F
                if (datosEncuesta.preguntas.length === 0) { Swal.fire('Error', 'Añade al menos una pregunta válida.', 'error'); return; }
                const saveBtn = $('.btn-publish'); saveBtn.prop('disabled',true).html('<i class="fa-solid fa-spinner fa-spin"></i> Guardando...'); console.log("Enviando:", JSON.stringify(datosEncuesta));
                $.ajax({ url: '../api/crearEncuesta.php', method: 'POST', contentType: 'application/json', data: JSON.stringify(datosEncuesta),
                    success: function(res) { if(res.success) { Swal.fire('¡Guardado!', res.mensaje||'Ok.', 'success'); cargarMisEncuestas(); } else { Swal.fire('Error al guardar', res.mensaje||'Ocurrió un error.', 'error'); saveBtn.prop('disabled', false).html('<i class="fa-solid fa-save"></i> Guardar Encuesta'); } },
                    error: function(jqXHR) { console.error("Error AJAX crear:", jqXHR.responseText); let msg='Error conexión.'; if(jqXHR.responseJSON&&jqXHR.responseJSON.mensaje){msg=jqXHR.responseJSON.mensaje;}else if(jqXHR.status===500){msg='Error servidor.';} Swal.fire('Error', msg, 'error'); saveBtn.prop('disabled', false).html('<i class="fa-solid fa-save"></i> Guardar Encuesta'); }
                });
            });

             // --- Submit del Formulario EDITAR Encuesta ---
             $('#dashboard-content-container').on('submit', '#form-editar-encuesta', function(e) {
                e.preventDefault();
                // Recolectar datos (similar a crear)
                const datosActualizados = { id_encuesta: $(this).find('input[name="id_encuesta"]').val(), titulo: $('#titulo').val().trim(), descripcion: $('#descripcion').val().trim(), visibilidad: $('#visibilidad').val(), estado: 'borrador', preguntas: [] };
                 if (!datosActualizados.titulo) { Swal.fire('Error', 'Título obligatorio.', 'error'); $('#titulo').focus(); return; }
                 $('.pregunta-block').each(function(index) { const block = $(this); const textoPregunta = block.find('input[name*="[texto_pregunta]"]').val().trim(); const tipoPregunta = block.find('select[name*="[tipo_pregunta]"]').val(); if (!textoPregunta) return; const preguntaData = { texto_pregunta: textoPregunta, tipo_pregunta: tipoPregunta, orden: index + 1, opciones: [] }; block.find('.opcion-item input[type="text"]').each(function() { const textoOpcion = $(this).val().trim(); if (!$(this).prop('disabled') && textoOpcion !== "") { preguntaData.opciones.push({ texto_opcion: textoOpcion }); } }); if (tipoPregunta === 'si_no') { preguntaData.opciones.push({texto_opcion:'Verdadero'}); preguntaData.opciones.push({texto_opcion:'Falso'}); } datosActualizados.preguntas.push(preguntaData); }); // Texto V/F
                 if (datosActualizados.preguntas.length === 0) { Swal.fire('Error', 'Añade al menos una pregunta válida.', 'error'); return; }

                 const updateBtn = $('.btn-publish');
                 updateBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Actualizando...');
                 console.log("Enviando actualización:", JSON.stringify(datosActualizados)); // Depuración

                 // !! LLAMADA A LA API DE ACTUALIZACIÓN (PENDIENTE EN BACKEND) !!
                 Swal.fire('Pendiente', 'La API para actualizar el borrador aún no está implementada.', 'info').then(() => { updateBtn.prop('disabled', false).html('<i class="fa-solid fa-save"></i> Actualizar Borrador'); });
                 /* $.ajax({ url: '../api/actualizarEncuestaBorrador.php', // ¡¡API PENDIENTE!! method: 'POST', contentType: 'application/json', data: JSON.stringify(datosActualizados), success: function(res) { if (res.success) { Swal.fire('¡Actualizado!', 'Borrador guardado.', 'success'); cargarMisEncuestas(); } else { Swal.fire('Error', res.mensaje, 'error'); updateBtn.prop('disabled', false).html('<i class="fa-solid fa-save"></i> Actualizar Borrador'); } }, error: function() { Swal.fire('Error', 'Conexión fallida.', 'error'); updateBtn.prop('disabled', false).html('<i class="fa-solid fa-save"></i> Actualizar Borrador'); } }); */
            });

        }); // Fin $(document).ready
    </script>
</body>
</html>