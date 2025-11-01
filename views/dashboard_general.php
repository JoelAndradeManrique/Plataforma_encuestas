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

        /* Pestañas (Cabecera) */
        .dashboard-tabs { display: flex; height: 100%; }
        .tab-link { padding: 0 15px; display: flex; align-items: center; border: none; background: none; cursor: pointer; font-size: 1rem; color: #555; text-decoration: none; border-bottom: 3px solid transparent; height: 100%; gap: 8px; }
        .tab-link i { color: inherit; font-size: 1em; }
        .tab-link:hover { color: #007bff; }
        .tab-link.active { color: #007bff; border-bottom-color: #007bff; font-weight: bold; }

        /* Grupo Derecho (Cabecera) */
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
        .survey-header-editor .form-group label { display: block; margin-bottom: 5px; font-weight: normal; font-size: 0.9rem; color: #555;}
        .survey-header-editor .form-group select { width: 100%; box-sizing: border-box; font-size: 0.9rem; padding: 5px; border: 1px solid #ccc; border-radius: 4px; background: #f9f9f9;}
        .pregunta-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 15px; margin-bottom: 15px; }
        .pregunta-header input[type="text"] { flex-grow: 1; padding: 10px; border: 1px dashed #ccc; border-radius: 4px; font-size: 1.1rem; }
        .pregunta-header input[type="text"]:focus { border-style: solid; border-color: #007bff; outline: none; }
        .pregunta-header select { padding: 10px; border: 1px solid #ccc; border-radius: 4px; background: #f9f9f9; }
        .opciones-container { margin-left: 20px; }
        .opcion-item { display: flex; align-items: center; margin-bottom: 10px; }
        .opcion-item input[type="text"] { border: none; border-bottom: 1px solid #eee; padding: 8px 0; margin-left: 10px; flex-grow: 1; }
        .opcion-item input[type="text"]:focus { border-bottom-color: #007bff; outline: none; }
        .btn-delete-opcion { background: none; border: none; color: #aaa; cursor: pointer; font-size: 1.2rem; margin-left: 5px;}
        .btn-add-opcion { background: none; border: none; color: #007bff; cursor: pointer; margin-left: 30px; font-size: 0.9rem; display: flex; align-items: center; gap: 5px; }
        .pregunta-footer { margin-top: 15px; display: flex; justify-content: flex-end; align-items: center; gap: 15px; border-top: 1px solid #eee; padding-top: 15px; }
        .btn-delete-pregunta { color: #dc3545; background: none; border: none; cursor: pointer; font-size: 1.2rem; }
        .btn-add-pregunta { display: block; margin: 20px auto; padding: 10px 20px; background: #fff; border: 1px dashed #ccc; border-radius: 5px; cursor: pointer; color: #555; transition: all 0.2s ease; display: flex; align-items: center; gap: 8px; justify-content: center; }
        .btn-add-pregunta:hover { background: #f9f9f9; border-style: solid; color: #007bff; }
        
        /* Estilos Lista Encuestas */
        .encuesta-item { display: flex; flex-direction: column; justify-content: space-between; padding: 15px; background: #fff; border-radius: 8px; margin-bottom: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .encuesta-info { margin-bottom: 10px; } .encuesta-info h3 { margin: 0; font-size: 1.2rem; } .encuesta-info div { display: flex; align-items: center; gap: 10px; margin-top: 5px; flex-wrap: wrap; } .encuesta-info span { font-size: 0.85rem; padding: 3px 8px; border-radius: 12px; color: #fff; } .encuesta-info small { font-size: 0.85rem; color: #666;} .encuesta-info .estado-publicada { background-color: #28a745; } .encuesta-info .estado-cerrada { background-color: #dc3545; } .encuesta-info .estado-borrador { background-color: #6c757d; } .encuesta-info .estado-archivada { background-color: #343a40; } .encuesta-acciones { display: flex; flex-wrap: wrap; gap: 5px; } .encuesta-acciones button, .encuesta-acciones a { padding: 8px 12px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; color: white; font-size: 0.9rem; flex-grow: 1; text-align: center; display: flex; align-items: center; justify-content: center; gap: 5px; } .btn-resultados { background-color: #17a2b8; } .btn-cerrar { background-color: #ffc107; color: #333; } .btn-eliminar { background-color: #dc3545; } .btn-editar { background-color: #ffc107; color: #333; } .btn-publish-lista { background-color: #28a745; color: white;}
        #loading { text-align: center; padding: 40px; font-size: 1.2em; color: #666; }
        
        /* Estilos Vista Resultados */
        .resultados-container { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .resultados-header h2 { margin-bottom: 5px; }
        .resultados-header p { color: #666; margin-bottom: 20px; }
        .pie-chart-container { max-width: 300px; margin: 20px auto; }
        .pregunta-resultados { border-top: 1px solid #eee; padding-top: 20px; margin-top: 20px; }
        .pregunta-resultados h4 { font-size: 1.1rem; margin-bottom: 10px; }
        .opcion-resultado { margin-bottom: 8px; } .opcion-resultado .texto { font-weight: 500; } .opcion-resultado .conteo { color: #007bff; font-weight: bold; } .participante-lista { font-size: 0.9em; color: #777; margin-left: 15px; }
        .respuesta-abierta { background: #f8f9fa; border-left: 3px solid #ccc; padding: 8px 12px; margin-bottom: 8px; font-style: italic; }
        .respuesta-abierta span { font-weight: bold; color: #555; }

        /* Estilos para Pestañas de Resultados */
        .tabs-container-resultados { width: 100%; margin-top: 20px; }
        .tab-buttons { display: flex; border-bottom: 2px solid #eee; }
        .tab-button-res { padding: 10px 20px; border: none; background: none; cursor: pointer; font-size: 1.1rem; color: #888; font-weight: 500; border-bottom: 3px solid transparent; margin-bottom: -2px; display: flex; align-items: center; gap: 8px; }
        .tab-button-res:hover { color: #333; }
        .tab-button-res.active { color: #007bff; border-bottom-color: #007bff; }
        .tab-content-res { padding-top: 20px; }
        .tab-pane-res { display: none; opacity: 0; transition: opacity 0.3s ease-in-out; }
        .tab-pane-res.active { display: block; opacity: 1; }
        .bar-chart-container { position: relative; width: 100%; max-width: 600px; margin: 15px 0; height: 250px; }
        .pregunta-resultado-grafico, .pregunta-resultado-abierta { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #f0f0f0; }
        .pregunta-resultado-abierta h4 { font-size: 1.1rem; margin-bottom: 10px; }

        /* Estilos Lista Participantes */
        .lista-participantes { list-style: none; padding: 0; margin: 0; }
        .lista-participantes li { border-bottom: 1px solid #f0f0f0; }
        .participante-link { display: block; padding: 12px 10px; text-decoration: none; color: #333; transition: background-color 0.2s ease; border-radius: 4px; }
        .participante-link:hover { background-color: #f8f9fa; color: #007bff; }
        .participante-link i { margin-right: 10px; color: #6c757d; }
        
        /* Estilos Modal Respuestas Alumno */
        .swal-form-respuestas { text-align: left; max-height: 50vh; overflow-y: auto; padding: 5px 15px; margin-top: -10px; }
        .swal-pregunta-item { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .swal-pregunta-item:last-child { border-bottom: none; margin-bottom: 0; }
        .swal-pregunta-item h4 { font-size: 1.1em; color: #333; margin-bottom: 10px; }
        .swal-opcion-item { font-size: 1em; color: #888; margin-left: 10px; padding: 5px; display: flex; align-items: center; gap: 10px; }
        .swal-opcion-item.selected { font-weight: bold; color: #007bff; background-color: #e3f2fd; border-radius: 4px; }
        .swal-opcion-item i { color: #007bff; font-size: 0.9em; }
        .swal-opcion-item i.fa-circle, .swal-opcion-item i.fa-square { color: #ccc; }
        .swal-respuesta-abierta { font-style: italic; color: #333; background: #f8f9fa; border: 1px solid #eee; border-radius: 4px; padding: 10px; margin-top: 5px; width: 95%; }

        /* Media Queries */
        @media (min-width: 768px) { .encuesta-item { flex-direction: row; align-items: center; } .encuesta-info { margin-bottom: 0; } .encuesta-acciones { flex-grow: 0; flex-wrap: nowrap; } }
        @media (max-width: 768px) { .dashboard-header { flex-direction: column; padding: 10px; min-height: auto; align-items: stretch;} .header-left-group { width: 100%; justify-content: space-between; margin-bottom: 10px;} .header-right-group { width: 100%; justify-content: space-between; } .dashboard-tabs { justify-content: center; } .header-logo { margin-right: 0; } }
        @media (max-width: 480px) { .dashboard-tabs { flex-wrap: wrap; justify-content: center;} .tab-link { font-size: 0.9rem; padding: 10px 8px; } .header-logo { font-size: 1.2rem; } .btn-logout, .user-profile span { font-size: 0.8rem;} .btn-publish { font-size: 0.8rem; padding: 6px 10px;} .encuesta-acciones button, .encuesta-acciones a { font-size: 0.8rem; padding: 6px 8px; } .tab-button-res { font-size: 0.95rem; padding: 10px 15px; } }
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

        // --- NAVEGACIÓN Y UTILIDADES ---
        function activarTab(tabId) {
            $('.tab-link').removeClass('active');
            if (tabId) { $(tabId).addClass('active'); }
        }

        // --- CARGAR VISTAS DINÁMICAS ---

        // 1. Cargar la lista de "Mis Encuestas"
        function cargarMisEncuestas() {
            activarTab('#btn-tab-mis-encuestas');
            $('#publish-button-placeholder').empty();
            const container = $('#dashboard-content-container');
            container.html('<div id="loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando tus encuestas...</div>');

            $.ajax({
                url: '../api/misEncuestas.php', method: 'GET', dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (response.encuestas.length === 0) { container.html('<h3>No has creado ninguna encuesta todavía.</h3><p>Usa la pestaña "Crear Nueva Encuesta" para empezar.</p>'); return; }
                        container.empty();
                        response.encuestas.forEach(function(encuesta) {
                            const estadoClase = `estado-${encuesta.estado}`;
                            const estadoTexto = encuesta.estado.charAt(0).toUpperCase() + encuesta.estado.slice(1);
                            let botonesAccion = '';
                            if (encuesta.estado === 'borrador') { botonesAccion = `<button class="btn-editar" data-id="${encuesta.id_encuesta}"><i class="fa-solid fa-edit"></i> Editar</button> <button class="btn-publish-lista" data-id="${encuesta.id_encuesta}"><i class="fa-solid fa-paper-plane"></i> Publicar</button>`; }
                            else if (encuesta.estado === 'publicada') { botonesAccion = `<button class="btn-resultados" data-id="${encuesta.id_encuesta}"><i class="fa-solid fa-chart-pie"></i> Resultados</button> <button class="btn-cerrar" data-id="${encuesta.id_encuesta}" data-nuevo-estado="cerrada"><i class="fa-solid fa-lock"></i> Cerrar</button>`; }
                            else if (encuesta.estado === 'cerrada') { botonesAccion = `<button class="btn-resultados" data-id="${encuesta.id_encuesta}"><i class="fa-solid fa-chart-pie"></i> Resultados</button> <button class="btn-cerrar" data-id="${encuesta.id_encuesta}" data-nuevo-estado="publicada" style="background-color: #28a745; color: white;"><i class="fa-solid fa-lock-open"></i> Re-Publicar</button>`; }
                            botonesAccion += ` <button class="btn-eliminar" data-id="${encuesta.id_encuesta}"><i class="fa-solid fa-trash-alt"></i> Eliminar</button>`;
                            let fechaFormateada = 'Fecha desconocida'; try { fechaFormateada = new Date(encuesta.fecha_creacion).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' }); } catch(e) { console.error("Error formateando fecha", encuesta.fecha_creacion); }
                            const encuestaHtml = `<div class="encuesta-item"><div class="encuesta-info"><h3>${encuesta.titulo || 'Encuesta sin título'}</h3><div><small>Creada: ${fechaFormateada}</small><span class="${estadoClase}">${estadoTexto}</span></div></div><div class="encuesta-acciones">${botonesAccion}</div></div>`;
                            container.append(encuestaHtml);
                        });
                    } else { container.html(`<p style="color: red;">Error al cargar encuestas: ${response.mensaje}</p>`); }
                },
                error: function(jqXHR) { console.error("Error AJAX cargar encuestas:", jqXHR.responseText); let msg = 'Error de conexión o sesión expirada.'; if(jqXHR.status === 403) { msg = 'Acceso denegado.'; } container.html(`<p style="color: red;">${msg} Recarga la página.</p>`); }
            });
        }

        // 2. Cargar el formulario para "Crear Nueva Encuesta"
        function cargarFormCrear() {
            activarTab('#btn-tab-crear');
            $('#publish-button-placeholder').html(`<button type="submit" form="form-crear-encuesta" class="btn-publish" style="background-color: #007bff; color: white;"><i class="fa-solid fa-save"></i> Guardar Encuesta</button>`);
            const container = $('#dashboard-content-container');
            const formHtml = `
                <form id="form-crear-encuesta" class="form-builder-container">
                    <div class="survey-header-editor">
                        <input type="text" id="titulo" name="titulo" placeholder="Título del formulario" required>
                        <textarea id="descripcion" name="descripcion" placeholder="Descripción del formulario"></textarea>
                        <div style="display: flex; gap: 20px; margin-top: 15px; flex-wrap: wrap;">
                            <div class="form-group" style="flex: 1; min-width: 150px;"><label style="font-size: 0.9rem; color: #555;">Visibilidad:</label><select id="visibilidad" name="visibilidad" style="font-size: 0.9rem; padding: 5px;"><option value="identificada">Identificada</option><option value="anonima">Anónima</option></select></div>
                            <div class="form-group" style="flex: 1; min-width: 150px;"><label style="font-size: 0.9rem; color: #555;">Estado inicial:</label><select id="estado" name="estado" style="font-size: 0.9rem; padding: 5px;"><option value="borrador">Borrador</option><option value="publicada">Publicada</option></select></div>
                        </div>
                    </div>
                    <div id="preguntas-container"></div>
                    <button type="button" id="btn-add-pregunta" class="btn-add-pregunta"><i class="fa-solid fa-plus"></i> Añadir Pregunta</button>
                </form>`;
            container.html(formHtml);
            preguntaIndex = 0;
            agregarPregunta();
        }

        // 3. Cargar la vista de "Resultados" (ACTUALIZADA CON PESTAÑAS)
        function cargarResultados(idEncuesta) {
            activarTab(null);
            $('#publish-button-placeholder').empty();
            const container = $('#dashboard-content-container');
            container.html('<div id="loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando resultados...</div>');

            $.ajax({
                url: `../api/obtenerResultados.php?id_encuesta=${idEncuesta}`, method: 'GET', dataType: 'json',
                success: function(response) {
                    if (response.success && response.resultados) {
                        const r = response.resultados; let html = `<div class="resultados-container"><div class="resultados-header"><h2>Resultados: ${r.titulo}</h2><p>Visibilidad: ${r.visibilidad} | Estado: ${r.estado}</p></div>`;
                        const totalRespuestas = r.resumen_participacion.respuestas_anonimas + r.resumen_participacion.respuestas_identificadas;
                        
                        // HTML PARA PESTAÑAS
                        html += `
                            <div class="tabs-container-resultados">
                                <div class="tab-buttons">
                                    <button class="tab-button-res active" data-tab="participacion"><i class="fa-solid fa-chart-pie"></i> Participación</button>
                                    <button class="tab-button-res" data-tab="preguntas"><i class="fa-solid fa-chart-bar"></i> Preguntas</button>
                                    <button class="tab-button-res" data-tab="participantes" style="display: none;"><i class="fa-solid fa-users"></i> Participantes</button>
                                </div>
                                <div class="tab-content-res">
                                    <div id="tab-participacion" class="tab-pane-res active">
                                        <h3>Resumen de Participación (${totalRespuestas} respuestas totales)</h3>`;
                                        if (totalRespuestas > 0) { html += `<div class="pie-chart-container"><canvas id="pieChartParticipacion"></canvas></div>`; }
                                        else { html += `<div style="text-align: center; padding: 30px; border: 1px dashed #ccc; border-radius: 8px; margin-top: 20px;"><i class="fa-solid fa-inbox fa-2x" style="color: #ccc; margin-bottom: 15px;"></i><p><strong>Aún no hay respuestas</strong> para esta encuesta.</p></div>`; }
                                        html += `
                                    </div>
                                    <div id="tab-preguntas" class="tab-pane-res">
                                        <h3>Resultados por Pregunta</h3>
                                        <div id="preguntas-graficos-container"></div>
                                    </div>
                                    <div id="tab-participantes" class="tab-pane-res">
                                        <h3>Participantes Identificados</h3>
                                        <div id="participantes-lista-container"></div>
                                    </div>
                                </div>
                            </div>`;
                        
                        html += `</div>`; container.html(html);

                        // 1. Inicializar Gráfico de Pastel (Pestaña 1)
                        if (totalRespuestas > 0) {
                            try { const ctx = document.getElementById('pieChartParticipacion').getContext('2d'); new Chart(ctx, { type: 'pie', data: { labels: ['Identificadas', 'Anónimas'], datasets: [{ label: '# de Respuestas', data: [r.resumen_participacion.respuestas_identificadas, r.resumen_participacion.respuestas_anonimas], backgroundColor: ['rgba(75, 192, 192, 0.7)', 'rgba(201, 203, 207, 0.7)'], borderColor: ['rgba(75, 192, 192, 1)', 'rgba(201, 203, 207, 1)'], borderWidth: 1 }] }, options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'top' } } } }); } catch (e) { console.error("Error al crear el gráfico de pastel:", e); }
                        }

                        // 2. Generar Gráficos de Barras y Respuestas Abiertas (Pestaña 2)
                        const preguntasGraficosContainer = $('#preguntas-graficos-container');
                        if (totalRespuestas > 0 && r.preguntas && r.preguntas.length > 0) {
                            let preguntasContablesEncontradas = false;
                            r.preguntas.forEach((preg, index) => {
                                if (['opcion_multiple', 'si_no', 'escala', 'seleccion_multiple'].includes(preg.tipo_pregunta)) {
                                    if (preg.resultados && preg.resultados.length > 0) {
                                        preguntasContablesEncontradas = true;
                                        const labels = []; const data = []; const backgroundColors = ['rgba(54, 162, 235, 0.6)', 'rgba(255, 206, 86, 0.6)', 'rgba(75, 192, 192, 0.6)', 'rgba(153, 102, 255, 0.6)', 'rgba(255, 159, 64, 0.6)', 'rgba(255, 99, 132, 0.6)']; const borderColors = ['rgba(54, 162, 235, 1)', 'rgba(255, 206, 86, 1)', 'rgba(75, 192, 192, 1)', 'rgba(153, 102, 255, 1)', 'rgba(255, 159, 64, 1)', 'rgba(255, 99, 132, 1)'];
                                        preg.resultados.forEach((res, i) => { labels.push(res.texto_opcion); data.push(res.conteo); });
                                        const preguntaGraficoHtml = `<div class="pregunta-resultado-grafico"><h4>${index + 1}. ${preg.texto_pregunta}</h4><div class="bar-chart-container"><canvas id="barChartPregunta${preg.id_pregunta}"></canvas></div></div>`;
                                        preguntasGraficosContainer.append(preguntaGraficoHtml);
                                        try { const ctxBar = document.getElementById(`barChartPregunta${preg.id_pregunta}`).getContext('2d'); new Chart(ctxBar, { type: 'bar', data: { labels: labels, datasets: [{ label: '# de Respuestas', data: data, backgroundColor: backgroundColors.slice(0, data.length), borderColor: borderColors.slice(0, data.length), borderWidth: 1 }] }, options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { legend: { display: false } } } }); } catch (e) { console.error(`Error al crear gráfico ${preg.id_pregunta}:`, e); }
                                    } else { preguntasGraficosContainer.append(`<div class="pregunta-resultado-grafico"><h4>${index + 1}. ${preg.texto_pregunta}</h4><p><em>No hay respuestas para esta pregunta.</em></p></div>`); }
                                } else if (preg.tipo_pregunta === 'abierta') {
                                    preguntasContablesEncontradas = true; let abiertaHtml = `<div class="pregunta-resultado-abierta"><h4>${index + 1}. ${preg.texto_pregunta} (Respuesta Corta)</h4>`;
                                    if (preg.resultados && preg.resultados.length > 0) { preg.resultados.forEach(res => { abiertaHtml += `<div class="respuesta-abierta">"${res.texto_respuesta}" <span>- ${res.participante || 'Anónimo'}</span></div>`; }); } else { abiertaHtml += `<p><em>No hay respuestas abiertas.</em></p>`; }
                                    abiertaHtml += `</div>`; preguntasGraficosContainer.append(abiertaHtml);
                                }
                            });
                            if (!preguntasContablesEncontradas) { preguntasGraficosContainer.html('<div style="text-align: center; padding: 20px;"><p>No se encontraron preguntas contables o respuestas abiertas.</p></div>'); }
                        } else if (totalRespuestas > 0) { preguntasGraficosContainer.html('<div style="text-align: center; padding: 20px;"><p>No se encontraron preguntas en esta encuesta.</p></div>'); }
                        else { preguntasGraficosContainer.html('<div style="text-align: center; padding: 30px; border: 1px dashed #ccc; border-radius: 8px; margin-top: 20px;"><i class="fa-solid fa-inbox fa-2x" style="color: #ccc; margin-bottom: 15px;"></i><p><strong>Aún no hay respuestas</strong> para esta encuesta.</p></div>'); }

                        // 3. Llenar Pestaña de Participantes
                        if (r.visibilidad === 'identificada' && r.participantes_identificados && r.participantes_identificados.length > 0) {
                            $('.tab-button-res[data-tab="participantes"]').show();
                            const participantesContainer = $('#participantes-lista-container'); let listaHtml = '<ul class="lista-participantes">';
                            r.participantes_identificados.forEach(p => { listaHtml += `<li><a href="#" class="participante-link" data-id-encuesta="${idEncuesta}" data-id-alumno="${p.id_usuario}" data-nombre-alumno="${p.apellido}, ${p.nombre}"><i class="fa-solid fa-user"></i> ${p.apellido}, ${p.nombre}</a></li>`; });
                            listaHtml += '</ul>'; participantesContainer.html(listaHtml);
                        } else {
                            $('.tab-button-res[data-tab="participantes"]').hide();
                            $('#participantes-lista-container').html('<p>Esta encuesta es anónima o ningún participante identificado ha respondido aún.</p>');
                        }
                    } else { container.html(`<p style="color: red;">Error: ${response.mensaje || 'No se pudieron cargar los resultados.'}</p>`); }
                },
                error: function(jqXHR, textStatus, errorThrown) { console.error("Error AJAX resultados:", textStatus, errorThrown, jqXHR.responseText); let msg = 'Error de conexión.'; if (jqXHR.status === 404) msg = 'API no encontrada (404).'; else if (jqXHR.status === 500) msg = 'Error interno (500).'; else if (textStatus === 'parsererror') msg = 'Respuesta no es JSON.'; container.html(`<p style="color: red;">${msg}</p>`); }
            });
        }

         // 4. Cargar Formulario para EDITAR Borrador
        function cargarFormEditar(idEncuesta) {
            activarTab(null);
            const container = $('#dashboard-content-container');
            container.html('<div id="loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando datos del borrador...</div>');

            $.ajax({
                url: `../api/obtenerEncuestaEditable.php?id_encuesta=${idEncuesta}`,
                method: 'GET', dataType: 'json',
                success: function(response) {
                    if (response.success && response.encuesta) {
                        const encuesta = response.encuesta;
                        $('#publish-button-placeholder').html(`<button type="submit" form="form-editar-encuesta" class="btn-publish" style="background-color: #ffc107; color: #333;"><i class="fa-solid fa-save"></i> Actualizar Borrador</button>`);
                        let formHtml = `<form id="form-editar-encuesta" class="form-builder-container"><input type="hidden" name="id_encuesta" value="${encuesta.id_encuesta}"><div class="survey-header-editor"><input type="text" id="titulo" name="titulo" placeholder="Título del formulario" value="${encuesta.titulo || ''}" required><textarea id="descripcion" name="descripcion" placeholder="Descripción del formulario">${encuesta.descripcion || ''}</textarea><div style="display: flex; gap: 20px; margin-top: 15px; flex-wrap: wrap;"><div class="form-group" style="flex: 1; min-width: 150px;"><label style="font-size: 0.9rem; color: #555;">Visibilidad:</label><select id="visibilidad" name="visibilidad" style="font-size: 0.9rem; padding: 5px;"><option value="identificada" ${encuesta.visibilidad === 'identificada' ? 'selected' : ''}>Identificada</option><option value="anonima" ${encuesta.visibilidad === 'anonima' ? 'selected' : ''}>Anónima</option></select></div><div class="form-group" style="flex: 1; min-width: 150px;"><label style="font-size: 0.9rem; color: #555;">Estado:</label><input type="text" value="Borrador" style="font-size: 0.9rem; padding: 5px; background: #eee;" disabled><input type="hidden" name="estado" value="borrador"></div></div></div><div id="preguntas-container"></div><button type="button" id="btn-add-pregunta" class="btn-add-pregunta"><i class="fa-solid fa-plus"></i> Añadir Pregunta</button></form>`;
                        container.html(formHtml);
                        preguntaIndex = 0;
                        if (encuesta.preguntas && encuesta.preguntas.length > 0) {
                            encuesta.preguntas.forEach(pregunta => { agregarPreguntaConDatos(pregunta); });
                        } else { agregarPregunta(); }
                    } else { Swal.fire('Error', response.mensaje || 'No se pudo cargar el borrador.', 'error'); cargarMisEncuestas(); }
                },
                error: function(jqXHR) { console.error("Error AJAX cargar borrador:", jqXHR.responseText); let msg = 'Error de conexión.'; if(jqXHR.status === 404) msg = 'Borrador no encontrado.'; Swal.fire('Error', msg, 'error'); cargarMisEncuestas(); }
            });
        }


        // --- FUNCIONES DEL FORM BUILDER ---
        function agregarPregunta() {
            const index = preguntaIndex++;
            const preguntaHtml = `
                <div class="pregunta-block" data-index="${index}">
                    <div class="pregunta-header">
                        <input type="text" name="preguntas[${index}][texto_pregunta]" placeholder="Pregunta sin título" required>
                        <select name="preguntas[${index}][tipo_pregunta]" class="tipo-pregunta-selector">
                            <option value="opcion_multiple">Opción Múltiple</option>
                            <option value="abierta">Respuesta Corta</option>
                            <option value="si_no">Verdadero / Falso</option>
                            <option value="escala">Escala (1-5)</option>
                        </select>
                    </div>
                    <div class="opciones-container"></div>
                    <div class="pregunta-footer">
                        <button type="button" class="btn-delete-pregunta" title="Eliminar Pregunta"><i class="fa-solid fa-trash-alt"></i></button>
                    </div>
                </div>`;
            $('#preguntas-container').append(preguntaHtml);
            $(`.pregunta-block[data-index="${index}"] .tipo-pregunta-selector`).trigger('change');
        }
        function agregarPreguntaConDatos(pregunta) {
            const index = preguntaIndex++;
             const tipoOptions = ['opcion_multiple', 'abierta', 'si_no', 'escala', ...(pregunta.tipo_pregunta === 'seleccion_multiple' ? [{value: 'seleccion_multiple', text: 'Selección Múltiple'}] : [])]
                .map(tipo => {
                    let texto = typeof tipo === 'object' ? tipo.text : tipo.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                    let value = typeof tipo === 'object' ? tipo.value : tipo;
                    if (value === 'si_no') texto = 'Verdadero / Falso';
                    return `<option value="${value}" ${pregunta.tipo_pregunta === value ? 'selected' : ''}>${texto}</option>`;
                }).join('');
            const preguntaHtml = `<div class="pregunta-block" data-index="${index}"><div class="pregunta-header"><input type="text" name="preguntas[${index}][texto_pregunta]" placeholder="Pregunta sin título" value="${pregunta.texto_pregunta || ''}" required><select name="preguntas[${index}][tipo_pregunta]" class="tipo-pregunta-selector">${tipoOptions}</select></div><div class="opciones-container"></div><div class="pregunta-footer"><button type="button" class="btn-delete-pregunta" title="Eliminar Pregunta"><i class="fa-solid fa-trash-alt"></i></button></div></div>`;
            $('#preguntas-container').append(preguntaHtml);
            const $containerOpciones = $(`.pregunta-block[data-index="${index}"] .opciones-container`);
            if (pregunta.opciones && pregunta.opciones.length > 0) {
                 if (pregunta.tipo_pregunta === 'opcion_multiple' || pregunta.tipo_pregunta === 'seleccion_multiple') {
                    pregunta.opciones.forEach((opcion, opIndex) => { agregarOpcionConDatos($containerOpciones, index, opIndex, opcion.texto_opcion, pregunta.tipo_pregunta); });
                     $containerOpciones.append(`<button type="button" class="btn-add-opcion"><i class="fa-solid fa-plus"></i> Añadir opción</button>`);
                }
            } else if (pregunta.tipo_pregunta === 'opcion_multiple' || pregunta.tipo_pregunta === 'seleccion_multiple') {
                 agregarOpcion($containerOpciones, index, 0); $containerOpciones.append(`<button type="button" class="btn-add-opcion"><i class="fa-solid fa-plus"></i> Añadir opción</button>`);
            }
            $(`.pregunta-block[data-index="${index}"] .tipo-pregunta-selector`).trigger('change');
        }
        function agregarOpcion(container, indexPregunta, opcionIndex) {
             const tipoPregunta = container.closest('.pregunta-block').find('.tipo-pregunta-selector').val();
             const iconClass = (tipoPregunta === 'seleccion_multiple') ? 'far fa-square' : 'far fa-circle';
            const opcionHtml = `<div class="opcion-item"><i class="${iconClass}" style="color: #ccc;"></i><input type="text" name="preguntas[${indexPregunta}][opciones][${opcionIndex}][texto_opcion]" placeholder="Opción ${opcionIndex + 1}" required><button type="button" class="btn-delete-opcion" title="Eliminar Opción">&times;</button></div>`;
            container.append(opcionHtml);
            container.find('.opcion-item:last-child input').focus();
        }
        function agregarOpcionConDatos(container, indexPregunta, opcionIndex, textoOpcion, tipoPregunta) {
            const iconClass = (tipoPregunta === 'seleccion_multiple') ? 'far fa-square' : 'far fa-circle';
            const opcionHtml = `<div class="opcion-item"><i class="${iconClass}" style="color: #ccc;"></i><input type="text" name="preguntas[${indexPregunta}][opciones][${opcionIndex}][texto_opcion]" placeholder="Opción ${opcionIndex + 1}" value="${textoOpcion || ''}" required><button type="button" class="btn-delete-opcion" title="Eliminar Opción">&times;</button></div>`;
            container.append(opcionHtml);
        }

        // --- MANEJADORES DE EVENTOS PRINCIPALES ---
        $(document).ready(function() {
            cargarMisEncuestas(); // Carga inicial

            // Pop-up contraseña temporal
            <?php if ($esTemporal): ?>
            const specialCharRegex = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]+/;
            Swal.fire({ title: 'Cambio de Contraseña Requerido', text: 'Por seguridad, debes establecer una nueva contraseña.', icon: 'warning', allowOutsideClick: false, allowEscapeKey: false, html: `<div style="text-align: left; margin-top: 15px;"><label for="swal-pass1" class="swal2-label">Nueva Contraseña</label><input type="password" id="swal-pass1" class="swal2-input" placeholder="Nueva contraseña"><label for="swal-pass2" class="swal2-label" style="margin-top: 10px;">Confirmar Contraseña</label><input type="password" id="swal-pass2" class="swal2-input" placeholder="Confirmar contraseña"><div id="swal-hint" style="font-size: 0.8em; color: #666; margin-top: 10px;">*Mínimo 8 carac, 1 especial (ej. !@#$) y terminar con AL</div></div>`, confirmButtonText: 'Guardar Contraseña', showLoaderOnConfirm: true,
                preConfirm: () => { const p1 = $('#swal-pass1').val(); const p2 = $('#swal-pass2').val(); if (!p1 || !p2) { Swal.showValidationMessage('Campos obligatorios.'); return false; } if (p1 !== p2) { Swal.showValidationMessage('Contraseñas no coinciden.'); return false; } if (p1.length < 8 || !p1.toLowerCase().endsWith('al') || !specialCharRegex.test(p1)) { Swal.showValidationMessage('Contraseña no cumple requisitos.'); return false; } return { nueva_contrasena: p1, confirmar_contrasena: p2 }; }
            }).then((result) => { if (result.isConfirmed && result.value) { $.ajax({ url: '../api/cambiarContrasena.php', method: 'POST', contentType: 'application/json', data: JSON.stringify(result.value), success: (r) => { if (r.success) Swal.fire('¡Éxito!', 'Contraseña actualizada.', 'success'); else Swal.showValidationMessage(r.mensaje); }, error: () => Swal.showValidationMessage('Error conexión.') }); } });
            <?php endif; ?>

            // --- Navegación por Pestañas (Cabecera) ---
            $('#btn-tab-mis-encuestas').on('click', (e) => { e.preventDefault(); cargarMisEncuestas(); });
            $('#btn-tab-crear').on('click', (e) => { e.preventDefault(); cargarFormCrear(); });

            // --- Eventos del Form Builder ---
            $('#dashboard-content-container').on('click', '#btn-add-pregunta', agregarPregunta);
            $('#dashboard-content-container').on('click', '.btn-delete-pregunta', function() { $(this).closest('.pregunta-block').remove(); });
            $('#dashboard-content-container').on('change', '.tipo-pregunta-selector', function() {
                const tipo = $(this).val(); const $pb = $(this).closest('.pregunta-block'); const $oc = $pb.find('.opciones-container'); const ip = $pb.data('index'); $oc.empty();
                if (tipo === 'opcion_multiple' || tipo === 'seleccion_multiple') { agregarOpcion($oc, ip, 0); $oc.append(`<button type="button" class="btn-add-opcion"><i class="fa-solid fa-plus"></i> Añadir opción</button>`); }
                else if (tipo === 'si_no') { $oc.html(`<div class="opcion-item"><i class="far fa-circle" style="color: #ccc;"></i> <input type="text" value="Verdadero" disabled></div><div class="opcion-item"><i class="far fa-circle" style="color: #ccc;"></i> <input type="text" value="Falso" disabled></div>`); }
                else if (tipo === 'escala') { $oc.html(`<div style="display: flex; justify-content: space-between; padding: 0 10px;"><span>1</span><span>2</span><span>3</span><span>4</span><span>5</span></div><div style="display: flex; justify-content: space-between; padding: 0 10px; font-size: 0.8em; color: #666;"><span>(Muy insatisfecho)</span><span></span><span></span><span></span><span>(Muy satisfecho)</span></div>`); }
            });
            $('#dashboard-content-container').on('click', '.btn-add-opcion', function() { const $c = $(this).closest('.opciones-container'); const ip = $(this).closest('.pregunta-block').data('index'); const oi = $c.find('.opcion-item').length; agregarOpcion($c, ip, oi); $(this).appendTo($c); });
            $('#dashboard-content-container').on('click', '.btn-delete-opcion', function() { $(this).closest('.opcion-item').remove(); });

            // --- Evento para Pestañas de Resultados ---
            $('#dashboard-content-container').on('click', '.tab-buttons .tab-button-res', function(e) {
                e.preventDefault();
                const tabId = $(this).data('tab');
                $(this).closest('.tab-buttons').find('.tab-button-res').removeClass('active');
                $(this).closest('.tabs-container-resultados').find('.tab-pane-res').removeClass('active');
                $(this).addClass('active');
                $(`#tab-${tabId}`).addClass('active');
            });
            
            // --- Evento para Link de Participante (en Resultados) ---
            $('#dashboard-content-container').on('click', '.participante-link', function(e) {
                e.preventDefault();
                const idEncuesta = $(this).data('id-encuesta');
                const idAlumno = $(this).data('id-alumno');
                const nombreAlumno = $(this).data('nombre-alumno');
                // Llamar a la función auxiliar para mostrar el modal
                mostrarRespuestasAlumno(idEncuesta, idAlumno, nombreAlumno);
            });

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
                $('.pregunta-block').each(function(index) { const block = $(this); const textoPregunta = block.find('input[name*="[texto_pregunta]"]').val().trim(); const tipoPregunta = block.find('select[name*="[tipo_pregunta]"]').val(); if (!textoPregunta) { console.warn(`Pregunta ${index+1} ignorada.`); return; } const preguntaData = { texto_pregunta: textoPregunta, tipo_pregunta: tipoPregunta, orden: index + 1, opciones: [] }; block.find('.opcion-item input[type="text"]').each(function() { const textoOpcion = $(this).val().trim(); if (!$(this).prop('disabled') && textoOpcion !== "") { preguntaData.opciones.push({ texto_opcion: textoOpcion }); } }); if (tipoPregunta === 'si_no') { preguntaData.opciones.push({texto_opcion:'Verdadero'}); preguntaData.opciones.push({texto_opcion:'Falso'}); } else if (tipoPregunta === 'escala') { preguntaData.opciones.push({texto_opcion:'1', valor_escala: 1}); preguntaData.opciones.push({texto_opcion:'2', valor_escala: 2}); preguntaData.opciones.push({texto_opcion:'3', valor_escala: 3}); preguntaData.opciones.push({texto_opcion:'4', valor_escala: 4}); preguntaData.opciones.push({texto_opcion:'5', valor_escala: 5}); } datosEncuesta.preguntas.push(preguntaData); });
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
                const datosActualizados = { id_encuesta: $(this).find('input[name="id_encuesta"]').val(), titulo: $('#titulo').val().trim(), descripcion: $('#descripcion').val().trim(), visibilidad: $('#visibilidad').val(), estado: 'borrador', preguntas: [] };
                 if (!datosActualizados.titulo) { Swal.fire('Error', 'Título obligatorio.', 'error'); $('#titulo').focus(); return; }
                 $('.pregunta-block').each(function(index) { const block = $(this); const textoPregunta = block.find('input[name*="[texto_pregunta]"]').val().trim(); const tipoPregunta = block.find('select[name*="[tipo_pregunta]"]').val(); if (!textoPregunta) return; const preguntaData = { texto_pregunta: textoPregunta, tipo_pregunta: tipoPregunta, orden: index + 1, opciones: [] }; block.find('.opcion-item input[type="text"]').each(function() { const textoOpcion = $(this).val().trim(); if (!$(this).prop('disabled') && textoOpcion !== "") { preguntaData.opciones.push({ texto_opcion: textoOpcion }); } }); if (tipoPregunta === 'si_no') { preguntaData.opciones.push({texto_opcion:'Verdadero'}); preguntaData.opciones.push({texto_opcion:'Falso'}); } else if (tipoPregunta === 'escala') { preguntaData.opciones.push({texto_opcion:'1', valor_escala: 1}); preguntaData.opciones.push({texto_opcion:'2', valor_escala: 2}); preguntaData.opciones.push({texto_opcion:'3', valor_escala: 3}); preguntaData.opciones.push({texto_opcion:'4', valor_escala: 4}); preguntaData.opciones.push({texto_opcion:'5', valor_escala: 5}); } datosActualizados.preguntas.push(preguntaData); });
                 if (datosActualizados.preguntas.length === 0) { Swal.fire('Error', 'Añade al menos una pregunta válida.', 'error'); return; }
                 const updateBtn = $('.btn-publish'); updateBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Actualizando...'); console.log("Enviando actualización:", JSON.stringify(datosActualizados));
                 Swal.fire('Pendiente', 'La API para actualizar el borrador aún no está implementada.', 'info').then(() => { updateBtn.prop('disabled', false).html('<i class="fa-solid fa-save"></i> Actualizar Borrador'); });
                 /* $.ajax({ url: '../api/actualizarEncuestaBorrador.php', // ¡¡API PENDIENTE!! method: 'POST', contentType: 'application/json', data: JSON.stringify(datosActualizados), success: function(res) { if (res.success) { Swal.fire('¡Actualizado!', 'Borrador guardado.', 'success'); cargarMisEncuestas(); } else { Swal.fire('Error', res.mensaje, 'error'); updateBtn.prop('disabled', false).html('<i class="fa-solid fa-save"></i> Actualizar Borrador'); } }, error: function() { Swal.fire('Error', 'Conexión fallida.', 'error'); updateBtn.prop('disabled', false).html('<i class="fa-solid fa-save"></i> Actualizar Borrador'); } }); */
            });
            
            // --- ✅ Función Auxiliar para Modal de Respuestas de Alumno ---
            function mostrarRespuestasAlumno(idEncuesta, idAlumno, nombreAlumno) {
                Swal.fire({
                    title: `Cargando respuestas de ${nombreAlumno}...`,
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                $.ajax({
                    url: `../api/obtenerRespuestasDeAlumno.php?id_encuesta=${idEncuesta}&id_alumno=${idAlumno}`,
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        // El backend ahora devuelve la ESTRUCTURA de la encuesta + respuestas
                        if (response.success && response.respuestas_alumno && Array.isArray(response.respuestas_alumno)) {
                            let html = '<div class="swal-form-respuestas">'; // Contenedor scrolleable
                            
                            response.respuestas_alumno.forEach(pregunta => {
                                html += `<div class="swal-pregunta-item"><h4>${pregunta.texto_pregunta}</h4>`;

                                const respuesta = pregunta.respuesta_alumno; // { opciones_seleccionadas: [id], texto_respuesta_abierta: "..." }
                                
                                // Renderizar según el tipo
                                if (pregunta.tipo_pregunta === 'abierta') {
                                    let texto = '<em>(No respondió)</em>';
                                    if(respuesta && respuesta.texto_respuesta_abierta) {
                                        // Escapar HTML en la respuesta del usuario para seguridad
                                        texto = $('<div>').text(respuesta.texto_respuesta_abierta).html();
                                    }
                                    html += `<div class="swal-respuesta-abierta">${texto}</div>`;
                                } 
                                // Tipos con opciones
                                else if (pregunta.opciones && pregunta.opciones.length > 0) {
                                    
                                    pregunta.opciones.forEach(opcion => {
                                        let esSeleccionada = false;
                                        if (respuesta && respuesta.opciones_seleccionadas) {
                                            // .includes() funciona porque convertimos los IDs a (int) en el modelo
                                            esSeleccionada = respuesta.opciones_seleccionadas.includes(opcion.id_opcion);
                                        }
                                        
                                        // Determinar icono
                                        let iconClass = 'fa-regular fa-circle'; // Radio no seleccionado
                                        if (pregunta.tipo_pregunta === 'seleccion_multiple') {
                                            iconClass = 'fa-regular fa-square'; // Checkbox no seleccionado
                                        }
                                        if (esSeleccionada) {
                                            iconClass = (pregunta.tipo_pregunta === 'seleccion_multiple') ? 'fa-solid fa-square-check' : 'fa-solid fa-check-circle';
                                        }

                                        // Escapar HTML en el texto de la opción
                                        const textoOpcion = $('<div>').text(opcion.texto_opcion).html();

                                        if (esSeleccionada) {
                                            html += `<div class="swal-opcion-item selected"><i class="${iconClass}"></i> ${textoOpcion}</div>`;
                                        } else {
                                            // Mostrar las otras opciones pero deshabilitadas
                                            html += `<div class="swal-opcion-item"><i class="${iconClass}"></i> ${textoOpcion}</div>`;
                                        }
                                    });
                                } else {
                                     html += `<p><em>Esta pregunta no tenía opciones.</em></p>`;
                                }
                                html += `</div>`; // Cierre swal-pregunta-item
                            });
                            html += "</div>"; // Cierre swal-form-respuestas

                            Swal.update({
                               title: `📋 Respuestas de ${nombreAlumno}`,
                               html: html,
                               icon: undefined,
                               width: '700px', // Modal más ancho
                               showConfirmButton: true,
                               confirmButtonText: "Cerrar"
                            });
                        } else {
                            Swal.fire("Error", response.mensaje || "No se pudieron cargar las respuestas.", "warning");
                        }
                    },
                    error: function(jqXHR) {
                        let msg = "Error de conexión al buscar las respuestas.";
                        if (jqXHR.status === 403) msg = "No tienes permiso para ver estas respuestas.";
                        Swal.fire("Error", msg, "error");
                    }
                });
            }

        }); // Fin $(document).ready
    </script>
</body>
</html>