<?php
session_start();

// --- ✅ CORRECCIÓN DE ROL ---
// Verificar si hay sesión Y si el rol es 'administrator'
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'administrador') {
    // Si no es admin, lo sacamos al login (incluso si es alumno o encuestador)
    header("Location: login.php");
    exit();
}
// --- FIN CORRECCIÓN ---

// Si llegamos aquí, es admin.
$usuario = $_SESSION['usuario'];
$nombre = htmlspecialchars($usuario['nombre']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
   <meta charset="UTF-8">
    <title>Panel de Administrador</title>

    <link rel="stylesheet" href="../css/style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Reseteo del body */
        body { display: block; justify-content: normal; align-items: normal; padding: 0; background-color: #f4f7f6; font-family: Arial, sans-serif; margin: 0; }
        .dashboard-wrapper { display: flex; flex-direction: column; min-height: 100vh; }

        /* Cabecera */
        .dashboard-header { background: #fff; border-bottom: 1px solid #ddd; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 1000; width: 100%; box-sizing: border-box; min-height: 60px; }
        .header-left-group { display: flex; align-items: center; height: 100%; }
        .header-logo { font-size: 1.5rem; font-weight: bold; color: #333; margin-right: 30px; cursor: pointer; }
        .dashboard-tabs { display: flex; height: 100%; }
        .tab-link { padding: 0 15px; display: flex; align-items: center; border: none; background: none; cursor: pointer; font-size: 1rem; color: #555; text-decoration: none; border-bottom: 3px solid transparent; height: 100%; gap: 8px; }
        .tab-link i { color: inherit; } .tab-link:hover { color: #007bff; }
        .tab-link.active { color: #007bff; border-bottom-color: #007bff; font-weight: bold; }
        .header-right-group { display: flex; align-items: center; }
        .btn-logout { background-color: #dc3545; color: white; padding: 8px 12px; border-radius: 5px; text-decoration: none; font-size: 0.9rem; font-weight: 500; margin-right: 15px; display: flex; align-items: center; gap: 5px; }
        .btn-logout:hover { background-color: #c82333; }
        .user-profile { display: flex; align-items: center; gap: 5px; } .user-profile span { font-size: 0.9rem; color: #333; } .user-profile i { margin-right: 5px; }
        .dashboard-content { margin: 20px; padding: 0; width: auto; flex-grow: 1; }
        #loading { text-align: center; padding: 40px; font-size: 1.2em; color: #666; }
        
        /* Formulario Admin */
        .admin-form-container { max-width: 600px; margin: 20px auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .admin-form-container h2 { text-align: center; margin-bottom: 20px; }
        .admin-form-container .form-group { margin-bottom: 15px; }
        .admin-form-container .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .admin-form-container .form-group input,
        .admin-form-container .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .admin-form-container .btn-crear-encuestador { width: 100%; padding: 12px; background-color: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1.1rem; font-weight: 500; }
        .admin-form-container .btn-crear-encuestador:disabled { background-color: #aaa; }
        .password-hint { font-size: 0.85em; color: #666; margin-top: 5px;}

        /* Acordeón Encuestadores */
        .encuestador-acordeon { background-color: #f1f1f1; color: #444; cursor: pointer; padding: 18px; width: 100%; border: none; text-align: left; outline: none; font-size: 1.1rem; transition: background-color 0.3s ease; display: flex; justify-content: space-between; align-items: center; border-radius: 5px; margin-bottom: 2px;}
        .encuestador-acordeon:hover { background-color: #ddd; }
        .encuestador-acordeon.active { background-color: #ccc; border-radius: 5px 5px 0 0; }
        .encuestador-acordeon .icon-chevron { transition: transform 0.3s ease; }
        .encuestador-acordeon.active .icon-chevron { transform: rotate(180deg); }
        .panel-encuestas { padding: 0; background-color: white; display: none; overflow: hidden; border-radius: 0 0 5px 5px; border: 1px solid #ddd; border-top: none;}
        .panel-encuestas .encuesta-item { border-radius: 0; margin-bottom: 0; box-shadow: none; border-bottom: 1px solid #f0f0f0; display: flex; flex-direction: column; justify-content: space-between; padding: 15px; }
        .panel-encuestas .encuesta-item:last-child { border-bottom: none; }
        .panel-loading { padding: 20px; text-align: center; color: #888; }
        .encuesta-info h3 { margin: 0 0 5px 0; font-size: 1.1rem; } .encuesta-info span { font-size: 0.85rem; padding: 3px 8px; border-radius: 12px; color: #fff; }
        .encuesta-info .estado-publicada { background-color: #28a745; } .encuesta-info .estado-cerrada { background-color: #dc3545; } .encuesta-info .estado-borrador { background-color: #6c757d; }
        .encuesta-acciones { display: flex; gap: 5px; margin-top: 10px;}
        .encuesta-acciones button { padding: 8px 12px; border: none; border-radius: 5px; cursor: pointer; color: white; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; gap: 5px; }
        .btn-resultados { background-color: #17a2b8; }
        @media (min-width: 768px) { .panel-encuestas .encuesta-item { flex-direction: row; align-items: center; } .encuesta-info { margin-bottom: 0; } .encuesta-acciones { margin-top: 0; } }

        /* Estilos Vista Resultados */
        .resultados-container { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .resultados-header h2 { margin-bottom: 5px; } .resultados-header p { color: #666; margin-bottom: 20px; }
        .pie-chart-container { max-width: 300px; margin: 20px auto; }
        .pregunta-resultados { border-top: 1px solid #eee; padding-top: 20px; margin-top: 20px; }
        .pregunta-resultados h4 { font-size: 1.1rem; margin-bottom: 10px; }
        .opcion-resultado { margin-bottom: 8px; } .opcion-resultado .texto { font-weight: 500; } .opcion-resultado .conteo { color: #007bff; font-weight: bold; } .participante-lista { font-size: 0.9em; color: #777; margin-left: 15px; }
        .respuesta-abierta { background: #f8f9fa; border-left: 3px solid #ccc; padding: 8px 12px; margin-bottom: 8px; font-style: italic; }
        .respuesta-abierta span { font-weight: bold; color: #555; }
        .tabs-container-resultados { width: 100%; margin-top: 20px; } .tab-buttons { display: flex; border-bottom: 2px solid #eee; } .tab-button-res { padding: 10px 20px; border: none; background: none; cursor: pointer; font-size: 1.1rem; color: #888; font-weight: 500; border-bottom: 3px solid transparent; margin-bottom: -2px; display: flex; align-items: center; gap: 8px; } .tab-button-res:hover { color: #333; } .tab-button-res.active { color: #007bff; border-bottom-color: #007bff; }
        .tab-content-res { padding-top: 20px; }
        .tab-pane-res { display: none; opacity: 0; transition: opacity 0.3s ease-in-out; }
        .tab-pane-res.active { display: block; opacity: 1; }
        .bar-chart-container { position: relative; width: 100%; max-width: 600px; margin: 15px 0; height: 250px; }
        .pregunta-resultado-grafico, .pregunta-resultado-abierta { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #f0f0f0; }
        .pregunta-resultado-abierta h4 { font-size: 1.1rem; margin-bottom: 10px; }
        .lista-participantes { list-style: none; padding: 0; margin: 0; } .lista-participantes li { border-bottom: 1px solid #f0f0f0; } .participante-link { display: block; padding: 12px 10px; text-decoration: none; color: #333; transition: background-color 0.2s ease; border-radius: 4px; } .participante-link:hover { background-color: #f8f9fa; color: #007bff; } .participante-link i { margin-right: 10px; color: #6c757d; }
        .swal-form-respuestas { text-align: left; max-height: 50vh; overflow-y: auto; padding: 5px 15px; margin-top: -10px; } .swal-pregunta-item { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; } .swal-pregunta-item:last-child { border-bottom: none; margin-bottom: 0; } .swal-pregunta-item h4 { font-size: 1.1em; color: #333; margin-bottom: 10px; } .swal-opcion-item { font-size: 1em; color: #888; margin-left: 10px; padding: 5px; display: flex; align-items: center; gap: 10px; } .swal-opcion-item.selected { font-weight: bold; color: #007bff; background-color: #e3f2fd; border-radius: 4px; } .swal-opcion-item i { color: #007bff; font-size: 0.9em; } .swal-opcion-item i.fa-circle, .swal-opcion-item i.fa-square { color: #ccc; } .swal-respuesta-abierta-display { font-style: italic; color: #333; background: #f8f9fa; border: 1px solid #eee; border-radius: 4px; padding: 10px; margin-top: 5px; width: 95%; }
        
        /* Media Queries Responsivas */
        @media (max-width: 768px) { .dashboard-header { flex-direction: column; padding: 10px; min-height: auto; align-items: stretch;} .header-left-group { width: 100%; justify-content: space-between; margin-bottom: 10px;} .header-right-group { width: 100%; justify-content: space-between; } .dashboard-tabs { justify-content: center; } .header-logo { margin-right: 0; } }
        @media (max-width: 480px) { .dashboard-tabs { flex-wrap: wrap; justify-content: center;} .tab-link { font-size: 0.9rem; padding: 10px 8px; } .header-logo { font-size: 1.2rem; } .btn-logout, .user-profile span { font-size: 0.8rem;} .btn-publish { font-size: 0.8rem; padding: 6px 10px;} .encuesta-acciones button, .encuesta-acciones a { font-size: 0.8rem; padding: 6px 8px; } .tab-button-res { font-size: 0.95rem; padding: 10px 15px; } }
        /* --- NUEVO: Estilos para Pestañas Internas (Gestión de Usuarios) --- */
        .inner-tabs-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin: 20px auto;
            max-width: 800px; /* Centrar el contenedor de pestañas */
        }
        .inner-tab-buttons {
            display: flex;
            border-bottom: 2px solid #eee;
            padding: 5px 10px 0 10px;
        }
        .inner-tab-link {
            padding: 12px 20px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 1rem;
            color: #555;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px; /* Alinear con borde */
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .inner-tab-link.active {
            color: #007bff;
            border-bottom-color: #007bff;
        }
        .inner-tab-content {
            padding: 25px;
        }
        .inner-tab-pane {
            display: none;
        }
        .inner-tab-pane.active {
            display: block;
        }
        /* Botón genérico para formularios internos */
        .btn-crear-usuario {
             width: 100%; padding: 12px; color: white;
            border: none; border-radius: 5px; cursor: pointer; font-size: 1.1rem;
            font-weight: 500;
        }
        .btn-crear-usuario:disabled { background-color: #aaa; }
        .btn-crear-encuestador { background-color: #28a745; } /* Verde */
        .btn-crear-alumno { background-color: #007bff; } /* Azul */
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <header class="dashboard-header">
            <div class="header-left-group">
                <div class="header-logo">Panel Admin</div>
                <nav class="dashboard-tabs">
                    <a href="#" class="tab-link active" id="btn-tab-gestion-usuarios">
                        <i class="fa-solid fa-users-cog"></i> Gestión de Usuarios
                    </a>
                    <a href="#" class="tab-link" id="btn-tab-gestion-encuestas">
                        <i class="fa-solid fa-list-ul"></i> Gestión de Encuestas
                    </a>
                </nav>
            </div>
            <div class="header-right-group">
                <a href="logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
                <div class="user-profile"><i class="fa-solid fa-shield-halved"></i> <span><?php echo $nombre; ?></span></div>
            </div>
        </header>

        <main class="dashboard-content" id="dashboard-content-container">
            </main>
    </div>

    <script>
        // Configuración global de Toasts
        const Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true,
            didOpen: (toast) => { toast.onmouseenter = Swal.stopTimer; toast.onmouseleave = Swal.resumeTimer; }
        });

        // --- Navegación ---
        function activarTab(tabId) {
            $('.tab-link').removeClass('active');
            if (tabId) { $(tabId).addClass('active'); }
        }

        // --- Cargar Vistas del Admin ---

        // 1. Cargar "Gestión de Usuarios" (Formulario crear encuestador)
        function cargarGestionUsuarios() {
            activarTab('#btn-tab-gestion-usuarios');
            const container = $('#dashboard-content-container');
            
            const tabsHtml = `
                <div class="inner-tabs-container">
                    <div class="inner-tab-buttons">
                        <button class="inner-tab-link active" data-tab="crear-encuestador">
                            <i class="fa-solid fa-user-tie"></i> Registrar Encuestador
                        </button>
                        <button class="inner-tab-link" data-tab="crear-alumno">
                            <i class="fa-solid fa-user-graduate"></i> Registrar Alumno
                        </button>
                    </div>
                    <div class="inner-tab-content">
                        
                        <div id="tab-crear-encuestador" class="inner-tab-pane active">
                            <form id="form-crear-encuestador">
                                <h2 style="text-align: center; margin-bottom: 20px;">Registrar Nuevo Encuestador (Maestro)</h2>
                                <div class="form-group"><label for="admin-nombre-enc">Nombres</label><input type="text" id="admin-nombre-enc" required></div>
                                <div class="form-group"><label for="admin-apellido-enc">Apellidos</label><input type="text" id="admin-apellido-enc" required></div>
                                <div class="form-group"><label for="admin-email-enc">Correo Electrónico</label><input type="email" id="admin-email-enc" placeholder="ejemplo@tecmerida.com" required></div>
                                <div class="form-group"><label for="admin-asignatura-enc">Materia (Asignatura)</label><input type="text" id="admin-asignatura-enc" required></div>
                                <div class="form-group"><label for="admin-contrasena-enc">Contraseña Temporal</label><input type="password" id="admin-contrasena-enc" required><div class="password-hint">Debe cumplir: 8+ carac, 1 especial, termina en "AL"</div></div>
                                <button type="submit" class="btn-crear-usuario btn-crear-encuestador"><i class="fa-solid fa-user-plus"></i> Crear Encuestador</button>
                            </form>
                        </div>
                        
                        <div id="tab-crear-alumno" class="inner-tab-pane">
                            <form id="form-crear-alumno">
                                <h2 style="text-align: center; margin-bottom: 20px;">Registrar Nuevo Alumno</h2>
                                <div class="form-group"><label for="admin-nombre-alu">Nombres</label><input type="text" id="admin-nombre-alu" required></div>
                                <div class="form-group"><label for="admin-apellido-alu">Apellidos</label><input type="text" id="admin-apellido-alu" required></div>
                                <div class="form-group"><label for="admin-email-alu">Correo Electrónico</label><input type="email" id="admin-email-alu" required></div>
                                <div class="form-group"><label for="admin-carrera-alu">Carrera</label><input type="text" id="admin-carrera-alu" required></div>
                                <div class="form-group"><label for="admin-genero-alu">Género</label>
                                    <select id="admin-genero-alu" required>
                                        <option value="" disabled selected>Seleccione...</option>
                                        <option value="masculino">Masculino</option>
                                        <option value="femenino">Femenino</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>
                                <div class="form-group"><label for="admin-contrasena-alu">Contraseña</label><input type="password" id="admin-contrasena-alu" required><div class="password-hint">Debe cumplir: 8+ carac, 1 especial, termina en "AL"</div></div>
                                <div class="form-group"><label for="admin-confirmar-alu">Confirmar Contraseña</label><input type="password" id="admin-confirmar-alu" required></div>
                                <button type="submit" class="btn-crear-usuario btn-crear-alumno"><i class="fa-solid fa-user-plus"></i> Crear Alumno</button>
                            </form>
                        </div>

                    </div>
                </div>`;
            container.html(tabsHtml);
        }

        // 2. Cargar "Gestión de Encuestas" (Acordeón de Encuestadores)
        function cargarGestionEncuestas() {
            activarTab('#btn-tab-gestion-encuestas');
            const container = $('#dashboard-content-container');
            container.html('<div id="loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando encuestadores...</div>');

            $.ajax({
                url: '../api/adminListarEncuestadores.php',
                method: 'GET', dataType: 'json',
                success: function(response) {
                    if (response.success && response.encuestadores) {
                        if (response.encuestadores.length === 0) {
                            container.html('<h3>No hay encuestadores registrados.</h3><p>Usa la pestaña "Gestión de Usuarios" para añadir uno.</p>'); return;
                        }
                        
                        let accordionHtml = '<div class="lista-encuestadores-admin">';
                        response.encuestadores.forEach(enc => {
                            accordionHtml += `
                                <div class="encuestador-item">
                                    <button class="encuestador-acordeon" data-id="${enc.id_usuario}">
                                        <span><i class="fa-solid fa-user-tie"></i> ${enc.apellido}, ${enc.nombre} (${enc.email})</span>
                                        <i class="fa-solid fa-chevron-down icon-chevron"></i>
                                    </button>
                                    <div class="panel-encuestas" id="panel-encuestador-${enc.id_usuario}">
                                        <div class="panel-loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</div>
                                    </div>
                                </div>`;
                        });
                        accordionHtml += '</div>';
                        container.html(accordionHtml);
                    } else { container.html(`<p style="color: red;">${response.mensaje}</p>`); }
                },
                error: function() { container.html('<p style="color: red;">Error de conexión.</p>'); }
            });
        }
        
        // 3. Cargar Resultados (versión Admin)
        function cargarResultadosAdmin(idEncuesta, tituloEncuesta) {
            activarTab(null);
            const container = $('#dashboard-content-container');
            container.html('<div id="loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando resultados...</div>');

            $.ajax({
                url: `../api/adminObtenerResultados.php?id_encuesta=${idEncuesta}`,
                method: 'GET', dataType: 'json',
                success: function(response) {
                    if (response.success && response.resultados) {
                        const r = response.resultados;
                        let html = `<div class="resultados-container">`;
                        html += `<button id="btn-back-to-encuestas" class="btn-primary" style="background-color: #6c757d; border:none; padding: 8px 12px; border-radius: 5px; color: white; cursor: pointer; margin-bottom: 15px;"><i class="fa-solid fa-arrow-left"></i> Volver a Encuestas</button>`;
                        html += `<div class="resultados-header"><h2>Resultados: ${$('<div>').text(r.titulo).html()}</h2><p>Visibilidad: ${r.visibilidad} | Estado: ${r.estado}</p></div>`;
                        const totalRespuestas = r.resumen_participacion.respuestas_anonimas + r.resumen_participacion.respuestas_identificadas;
                        
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
                                        else { html += `<div style="text-align: center; padding: 30px; border: 1px dashed #ccc; border-radius: 8px; margin-top: 20px;"><i class="fa-solid fa-inbox fa-2x" style="color: #ccc; margin-bottom: 15px;"></i><p><strong>Aún no hay respuestas</strong>.</p></div>`; }
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
                        
                        html += `</div>`; 
                        container.html(html);

                        // 1. Inicializar Gráfico Pastel
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
                                            backgroundColor: ['rgba(75, 192, 192, 0.7)', 'rgba(201, 203, 207, 0.7)'], 
                                            borderColor: ['rgba(75, 192, 192, 1)', 'rgba(201, 203, 207, 1)'], 
                                            borderWidth: 1 
                                        }] 
                                    }, 
                                    options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'top' } } } 
                                }); 
                            } catch (e) { console.error("Error al crear gráfico pastel:", e); }
                        }

                        // 2. Generar Gráficos de Barras
                        const preguntasGraficosContainer = $('#preguntas-graficos-container');
                        if (totalRespuestas > 0 && r.preguntas && r.preguntas.length > 0) {
                            let preguntasContablesEncontradas = false;
                            r.preguntas.forEach((preg, index) => {
                                const textoPreguntaEscapado = $('<div>').text(preg.texto_pregunta).html();
                                if (['opcion_multiple', 'si_no', 'escala', 'seleccion_multiple'].includes(preg.tipo_pregunta)) {
                                    if (preg.resultados && preg.resultados.length > 0) {
                                        preguntasContablesEncontradas = true;
                                        const labels = []; const data = []; 
                                        const backgroundColors = ['rgba(54, 162, 235, 0.6)', 'rgba(255, 206, 86, 0.6)', 'rgba(75, 192, 192, 0.6)', 'rgba(153, 102, 255, 0.6)', 'rgba(255, 159, 64, 0.6)', 'rgba(255, 99, 132, 0.6)']; 
                                        const borderColors = ['rgba(54, 162, 235, 1)', 'rgba(255, 206, 86, 1)', 'rgba(75, 192, 192, 1)', 'rgba(153, 102, 255, 1)', 'rgba(255, 159, 64, 1)', 'rgba(255, 99, 132, 1)'];
                                        preg.resultados.forEach((res, i) => { labels.push(res.texto_opcion); data.push(res.conteo); });
                                        const preguntaGraficoHtml = `<div class="pregunta-resultado-grafico"><h4>${index + 1}. ${textoPreguntaEscapado}</h4><div class="bar-chart-container"><canvas id="barChartPregunta${preg.id_pregunta}"></canvas></div></div>`;
                                        preguntasGraficosContainer.append(preguntaGraficoHtml);
                                        try { 
                                            const ctxBar = document.getElementById(`barChartPregunta${preg.id_pregunta}`).getContext('2d'); 
                                            new Chart(ctxBar, { 
                                                type: 'bar', 
                                                data: { labels: labels, datasets: [{ label: '# de Respuestas', data: data, backgroundColor: backgroundColors.slice(0, data.length), borderColor: borderColors.slice(0, data.length), borderWidth: 1 }] }, 
                                                options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { legend: { display: false } } } 
                                            }); 
                                        } catch (e) { console.error(`Error al crear gráfico ${preg.id_pregunta}:`, e); }
                                    } else { 
                                        preguntasGraficosContainer.append(`<div class="pregunta-resultado-grafico"><h4>${index + 1}. ${textoPreguntaEscapado}</h4><p><em>No hay respuestas.</em></p></div>`); 
                                    }
                                } else if (preg.tipo_pregunta === 'abierta') {
                                    preguntasContablesEncontradas = true; 
                                    let abiertaHtml = `<div class="pregunta-resultado-abierta"><h4>${index + 1}. ${textoPreguntaEscapado} (Respuesta Corta)</h4>`;
                                    if (preg.resultados && preg.resultados.length > 0) { 
                                        preg.resultados.forEach(res => { abiertaHtml += `<div class="respuesta-abierta">"${$('<div>').text(res.texto_respuesta).html()}" <span>- ${$('<div>').text(res.participante || 'Anónimo').html()}</span></div>`; }); 
                                    } else { abiertaHtml += `<p><em>No hay respuestas.</em></p>`; }
                                    abiertaHtml += `</div>`; 
                                    preguntasGraficosContainer.append(abiertaHtml);
                                }
                            });
                            if (!preguntasContablesEncontradas) { preguntasGraficosContainer.html('<div style="text-align: center; padding: 20px;"><p>No hay preguntas contables.</p></div>'); }
                        } else { 
                            preguntasGraficosContainer.html('<div style="text-align: center; padding: 30px; border: 1px dashed #ccc; border-radius: 8px; margin-top: 20px;"><i class="fa-solid fa-inbox fa-2x" style="color: #ccc; margin-bottom: 15px;"></i><p><strong>Aún no hay respuestas</strong>.</p></div>'); 
                        }

                        // 3. Llenar Pestaña Participantes
                        if (r.visibilidad === 'identificada' && r.participantes_identificados && r.participantes_identificados.length > 0) {
                            $('.tab-button-res[data-tab="participantes"]').show();
                            const participantesContainer = $('#participantes-lista-container'); 
                            let listaHtml = '<ul class="lista-participantes">';
                            r.participantes_identificados.forEach(p => { 
                                const nombreCompleto = `${p.apellido}, ${p.nombre}`;
                                const nombreEscapado = $('<div>').text(nombreCompleto).html();
                                listaHtml += `<li><a href="#" class="participante-link admin-ver-respuestas" data-id-encuesta="${idEncuesta}" data-id-alumno="${p.id_usuario}" data-nombre-alumno="${nombreEscapado}"><i class="fa-solid fa-user"></i> ${nombreEscapado}</a></li>`; 
                            });
                            listaHtml += '</ul>'; 
                            participantesContainer.html(listaHtml);
                        } else { 
                            $('.tab-button-res[data-tab="participantes"]').hide(); 
                            $('#participantes-lista-container').html('<p>Encuesta anónima o sin respuestas identificadas.</p>'); 
                        }
                    } else { 
                        container.html(`<p style="color: red;">${response.mensaje || 'Error al cargar resultados.'}</p>`); 
                    }
                },
                error: function() { container.html('<p style="color: red;">Error de conexión.</p>'); }
            });
        }
        
        // 4. Mostrar Modal de Respuestas (versión Admin)
        function mostrarRespuestasAlumnoAdmin(idEncuesta, idAlumno, nombreAlumno) {
            Swal.fire({ title: `Cargando respuestas de ${nombreAlumno}...`, didOpen: () => { Swal.showLoading(); } });
            $.ajax({
                url: `../api/obtenerRespuestasDeAlumno.php?id_encuesta=${idEncuesta}&id_alumno=${idAlumno}`,
                method: 'GET', dataType: 'json',
                success: function(response) {
                    if (response.success && response.respuestas_alumno && Array.isArray(response.respuestas_alumno)) {
                        let html = '<div class="swal-form-respuestas">';
                        let preguntaNumero = 1;
                        response.respuestas_alumno.forEach(pregunta => {
                            html += `<div class="swal-pregunta-item"><h4>${preguntaNumero++}. ${$('<div>').text(pregunta.texto_pregunta).html()}</h4>`;
                            const respuesta = pregunta.respuesta_alumno;
                            if (pregunta.tipo_pregunta === 'abierta') {
                                let texto = '<em>(No respondió)</em>'; if(respuesta && respuesta.texto_respuesta_abierta) { texto = $('<div>').text(respuesta.texto_respuesta_abierta).html(); }
                                html += `<div class="swal-respuesta-abierta-display">${texto}</div>`;
                            } else if (pregunta.opciones && pregunta.opciones.length > 0) {
                                pregunta.opciones.forEach(opcion => {
                                    let esSeleccionada = false; if (respuesta && respuesta.opciones_seleccionadas) { esSeleccionada = respuesta.opciones_seleccionadas.includes(opcion.id_opcion); }
                                    let iconClass = 'fa-regular fa-circle'; if (pregunta.tipo_pregunta === 'seleccion_multiple') { iconClass = 'fa-regular fa-square'; } if (esSeleccionada) { iconClass = (pregunta.tipo_pregunta === 'seleccion_multiple') ? 'fa-solid fa-square-check' : 'fa-solid fa-check-circle'; }
                                    const textoOpcion = $('<div>').text(opcion.texto_opcion).html();
                                    html += `<div class="swal-opcion-display ${esSeleccionada ? 'selected' : ''}"><i class="${iconClass}"></i> ${textoOpcion}</div>`;
                                });
                            } else { html += `<p><em>(Pregunta sin opciones)</em></p>`; }
                            html += `</div>`;
                        });
                        html += "</div>";
                        Swal.update({ title: `Respuestas de ${nombreAlumno}`, html: html, icon: undefined, width: '700px', showConfirmButton: true, confirmButtonText: "Cerrar" });
                    } else { Swal.fire("Error", response.mensaje || "No se pudieron cargar.", "warning"); }
                },
                error: function(jqXHR) { let msg = "Error de conexión."; if (jqXHR.status === 403) msg = "No tienes permiso."; Swal.fire("Error", msg, "error"); }
            });
        }


        // --- Manejadores de Eventos ---
        $(document).ready(function() {
            cargarGestionUsuarios(); // Cargar vista inicial

            $('#btn-tab-gestion-usuarios').on('click', (e) => { e.preventDefault(); cargarGestionUsuarios(); });
            $('#btn-tab-gestion-encuestas').on('click', (e) => { e.preventDefault(); cargarGestionEncuestas(); });
            $('.header-logo').on('click', (e) => { e.preventDefault(); cargarGestionUsuarios(); });
            $('#dashboard-content-container').on('click', '#btn-back-to-encuestas', (e) => { e.preventDefault(); cargarGestionEncuestas(); });

            // Submit: Crear Encuestador
            $('#dashboard-content-container').on('submit', '#form-crear-encuestador', function(e) {
                e.preventDefault();
                const $button = $(this).find('.btn-crear-encuestador');
                $button.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Creando...');
                const datos = {
                    nombre: $('#admin-nombre-enc').val().trim(),
                    apellido: $('#admin-apellido-enc').val().trim(),
                    email: $('#admin-email-enc').val().trim(),
                    asignatura: $('#admin-asignatura-enc').val().trim(),
                    contrasena: $('#admin-contrasena-enc').val()
                };
                const specialCharRegex = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]+/;
                if (datos.contrasena.length < 8 || !datos.contrasena.toLowerCase().endsWith('al') || !specialCharRegex.test(datos.contrasena)) { Swal.fire('Error', 'La contraseña no cumple los requisitos (8+ carac, 1 especial, termina en "AL").', 'error'); $button.prop('disabled', false).html('<i class="fa-solid fa-user-plus"></i> Crear Encuestador'); return; }
                 if (!datos.email.toLowerCase().endsWith('@tecmerida.com')) { Swal.fire('Error', 'El correo debe ser @tecmerida.com.', 'error'); $button.prop('disabled', false).html('<i class="fa-solid fa-user-plus"></i> Crear Encuestador'); return; }
                $.ajax({
                    url: '../api/adminCrearEncuestador.php',
                    method: 'POST', contentType: 'application/json', data: JSON.stringify(datos),
                    success: function(response) {
                        if (response.success) { Swal.fire('¡Éxito!', 'Encuestador registrado.', 'success'); $('#form-crear-encuestador')[0].reset(); }
                        else { Swal.fire('Error', response.mensaje || 'No se pudo registrar.', 'error'); }
                        $button.prop('disabled', false).html('<i class="fa-solid fa-user-plus"></i> Crear Encuestador');
                    },
                    error: function(jqXHR) {
                        let errorMsg = 'Error de conexión.'; if (jqXHR.responseJSON && jqXHR.responseJSON.mensaje) { errorMsg = jqXHR.responseJSON.mensaje; }
                        Swal.fire('Error', errorMsg, 'error');
                        $button.prop('disabled', false).html('<i class="fa-solid fa-user-plus"></i> Crear Encuestador');
                    }
                });
            });

            // --- ✅ NUEVO: Evento para Pestañas Internas ---
            $('#dashboard-content-container').on('click', '.inner-tab-link', function(e) {
                e.preventDefault();
                const tabId = $(this).data('tab');
                $(this).siblings().removeClass('active');
                $(this).closest('.inner-tabs-container').find('.inner-tab-pane').removeClass('active');
                $(this).addClass('active');
                $(`#tab-${tabId}`).addClass('active');
            });

            // --- ✅ NUEVO: Submit del Formulario Crear Alumno ---
            $('#dashboard-content-container').on('submit', '#form-crear-alumno', function(e) {
                e.preventDefault();
                const $button = $(this).find('.btn-crear-alumno');
                $button.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Creando...');

                const datos = {
                    nombre: $('#admin-nombre-alu').val().trim(),
                    apellido: $('#admin-apellido-alu').val().trim(),
                    email: $('#admin-email-alu').val().trim(),
                    carrera: $('#admin-carrera-alu').val().trim(),
                    genero: $('#admin-genero-alu').val(),
                    contrasena: $('#admin-contrasena-alu').val(),
                    confirmar_contrasena: $('#admin-confirmar-alu').val()
                };

                // Validaciones
                if (datos.contrasena !== datos.confirmar_contrasena) {
                    Swal.fire('Error', 'Las contraseñas no coinciden.', 'error');
                    $button.prop('disabled', false).html('<i class="fa-solid fa-user-plus"></i> Crear Alumno');
                    return;
                }
                const specialCharRegex = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]+/;
                if (datos.contrasena.length < 8 || !datos.contrasena.toLowerCase().endsWith('al') || !specialCharRegex.test(datos.contrasena)) {
                    Swal.fire('Error', 'La contraseña no cumple los requisitos (8+ carac, 1 especial, termina en "AL").', 'error');
                    $button.prop('disabled', false).html('<i class="fa-solid fa-user-plus"></i> Crear Alumno');
                    return;
                }
                if (!datos.genero) {
                     Swal.fire('Error', 'Debes seleccionar un género.', 'error');
                    $button.prop('disabled', false).html('<i class="fa-solid fa-user-plus"></i> Crear Alumno');
                    return;
                }

                // Llamar a la API de registrar ALUMNO
                $.ajax({
                    url: '../api/registrarAlumno.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(datos),
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('¡Éxito!', 'Alumno registrado con éxito.', 'success');
                            $('#form-crear-alumno')[0].reset(); // Limpiar formulario
                        } else {
                            Swal.fire('Error', response.mensaje || 'No se pudo registrar al alumno.', 'error');
                        }
                        $button.prop('disabled', false).html('<i class="fa-solid fa-user-plus"></i> Crear Alumno');
                    },
                    error: function(jqXHR) {
                        let errorMsg = 'Error de conexión.';
                        if (jqXHR.responseJSON && jqXHR.responseJSON.mensaje) {
                            errorMsg = jqXHR.responseJSON.mensaje;
                        } else if (jqXHR.status === 409) {
                            errorMsg = "El correo electrónico ya está registrado.";
                        }
                        Swal.fire('Error', errorMsg, 'error');
                        $button.prop('disabled', false).html('<i class="fa-solid fa-user-plus"></i> Crear Alumno');
                    }
                });
            });


            // --- Lógica de Acordeón (Gestión de Encuestas) ---
            $('#dashboard-content-container').on('click', '.encuestador-acordeon', function() {
                const $button = $(this); const $panel = $button.next('.panel-encuestas'); const idEncuestador = $button.data('id');
                $button.toggleClass('active');
                if ($panel.is(':visible')) { $panel.slideUp(); }
                else { $panel.slideDown();
                    if ($panel.find('.panel-loading').length > 0) {
                        $.ajax({
                            url: `../api/adminObtenerEncuestasPorEncuestador.php?id_encuestador=${idEncuestador}`,
                            method: 'GET', dataType: 'json',
                            success: function(res) {
                                $panel.empty();
                                if (res.success && res.encuestas.length > 0) {
                                    res.encuestas.forEach(function(encuesta) {
                                        const estadoClase = `estado-${encuesta.estado}`;
                                        const estadoTexto = encuesta.estado.charAt(0).toUpperCase() + encuesta.estado.slice(1);
                                        const tituloEscapado = $('<div>').text(encuesta.titulo).html();
                                        const encuestaHtml = `
                                            <div class="encuesta-item">
                                                <div class="encuesta-info"><h3>${tituloEscapado}</h3><div><span class="${estadoClase}">${estadoTexto}</span></div></div>
                                                <div class="encuesta-acciones">
                                                    <button class="btn-resultados admin-ver-resultados" data-id="${encuesta.id_encuesta}" data-titulo="${tituloEscapado}"><i class="fa-solid fa-chart-pie"></i> Ver Resultados</button>
                                                </div>
                                            </div>`;
                                        $panel.append(encuestaHtml);
                                    });
                                } else { $panel.html('<div class="panel-loading">Este encuestador no tiene encuestas.</div>'); }
                            },
                            error: function() { $panel.html('<div class="panel-loading" style="color:red;">Error al cargar encuestas.</div>'); }
                        });
                    }
                }
            });
            
            // Clic en "Ver Resultados" (Admin)
            $('#dashboard-content-container').on('click', '.admin-ver-resultados', function() {
                const idEncuesta = $(this).data('id');
                const tituloEncuesta = $(this).data('titulo');
                cargarResultadosAdmin(idEncuesta, tituloEncuesta); // ✅ Llamada corregida
            });
            
            // Clic en Pestañas de Resultados (delegado)
            $('#dashboard-content-container').on('click', '.tab-buttons .tab-button-res', function(e) {
                e.preventDefault(); const tabId = $(this).data('tab');
                $(this).closest('.tab-buttons').find('.tab-button-res').removeClass('active');
                $(this).closest('.tabs-container-resultados').find('.tab-pane-res').removeClass('active');
                $(this).addClass('active'); $(`#tab-${tabId}`).addClass('active');
            });
            
            // Clic en Link de Participante (delegado)
            $('#dashboard-content-container').on('click', '.participante-link.admin-ver-respuestas', function(e) {
                e.preventDefault();
                const idEncuesta = $(this).data('id-encuesta'); const idAlumno = $(this).data('id-alumno'); const nombreAlumno = $(this).data('nombre-alumno');
                mostrarRespuestasAlumnoAdmin(idEncuesta, idAlumno, nombreAlumno); // ✅ Llamada corregida
            });

        }); // Fin $(document).ready
    </script>
</body>
</html>