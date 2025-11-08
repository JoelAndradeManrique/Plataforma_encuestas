<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'administrador') {
    header("Location: login.php");
    exit();
}
$usuario = $_SESSION['usuario'];
$nombre = htmlspecialchars($usuario['nombre']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administrador</title>

    <link rel="stylesheet" href="../css/style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* (Estilos del dashboard - omitidos por brevedad, son los mismos que ya tenías) */
        body { display: block; padding: 0; background-color: #f4f7f6; font-family: Arial, sans-serif; margin: 0; }
        .dashboard-wrapper { display: flex; flex-direction: column; min-height: 100vh; }
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
        .password-hint { font-size: 0.85em; color: #666; margin-top: 5px;}
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
        .encuesta-acciones button, .encuesta-acciones a { padding: 8px 12px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; color: white; font-size: 0.9rem; flex-grow: 1; text-align: center; display: flex; align-items: center; justify-content: center; gap: 5px; }
        .btn-resultados { background-color: #17a2b8; }
        .resultados-container { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .resultados-header h2 { margin-bottom: 5px; } .resultados-header p { color: #666; margin-bottom: 20px; }
        .pie-chart-container { max-width: 300px; margin: 20px auto; }
        .tabs-container-resultados { width: 100%; margin-top: 20px; } .tab-buttons { display: flex; border-bottom: 2px solid #eee; } .tab-button-res { padding: 10px 20px; border: none; background: none; cursor: pointer; font-size: 1.1rem; color: #888; font-weight: 500; border-bottom: 3px solid transparent; margin-bottom: -2px; display: flex; align-items: center; gap: 8px; } .tab-button-res:hover { color: #333; } .tab-button-res.active { color: #007bff; border-bottom-color: #007bff; }
        .tab-content-res { padding-top: 20px; }
        .tab-pane-res { display: none; } .tab-pane-res.active { display: block; }
        .bar-chart-container { position: relative; width: 100%; max-width: 600px; margin: 15px 0; height: 250px; }
        .pregunta-resultado-grafico, .pregunta-resultado-abierta { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #f0f0f0; }
        .pregunta-resultado-abierta h4 { font-size: 1.1rem; margin-bottom: 10px; }
        .respuesta-abierta { background: #f8f9fa; border-left: 3px solid #ccc; padding: 8px 12px; margin-bottom: 8px; font-style: italic; }
        .respuesta-abierta span { font-weight: bold; color: #555; }
        .lista-participantes { list-style: none; padding: 0; margin: 0; } .lista-participantes li { border-bottom: 1px solid #f0f0f0; } .participante-link { display: block; padding: 12px 10px; text-decoration: none; color: #333; transition: background-color 0.2s ease; border-radius: 4px; } .participante-link:hover { background-color: #f8f9fa; color: #007bff; } .participante-link i { margin-right: 10px; color: #6c757d; }
        .swal-form-respuestas { text-align: left; max-height: 50vh; overflow-y: auto; padding: 5px 15px; margin-top: -10px; } .swal-pregunta-item { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; } .swal-pregunta-item:last-child { border-bottom: none; margin-bottom: 0; } .swal-pregunta-item h4 { font-size: 1.1em; color: #333; margin-bottom: 10px; } .swal-opcion-display { font-size: 1em; color: #888; margin-left: 10px; padding: 5px; display: flex; align-items: center; gap: 10px; } .swal-opcion-display.selected { font-weight: bold; color: #007bff; background-color: #e3f2fd; border-radius: 4px; } .swal-opcion-display i { color: #007bff; font-size: 0.9em; } .swal-opcion-display i.fa-circle, .swal-opcion-display i.fa-square { color: #ccc; } .swal-respuesta-abierta-display { font-style: italic; color: #333; background: #f8f9fa; border: 1px solid #eee; border-radius: 4px; padding: 10px; margin-top: 5px; width: 95%; }
        .stats-grid { display: grid; grid-template-columns: 1fr; gap: 20px; }
        .stat-card { background: #fff; padding: 20px 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stat-card h3 { margin-top: 0; text-align: center; border-bottom: 1px solid #eee; padding-bottom: 10px; color: #333; }
        .stats-grid .pie-chart-container { max-width: 350px; height: 350px; margin: 20px auto; }
        .stats-grid .bar-chart-container { width: 100%; height: 350px; margin: 20px auto; }
        .gestion-usuarios-container { max-width: 1000px; margin: 0 auto; }
        .add-user-toggle { background-color: #007bff; color: white; border: none; padding: 10px 15px; font-size: 1rem; border-radius: 5px; cursor: pointer; display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
        .add-user-toggle i { transition: transform 0.3s ease; }
        .add-user-toggle.open i { transform: rotate(180deg); }
        .form-accordion-content { display: none; }
        .inner-tabs-container { background: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .inner-tab-buttons { display: flex; border-bottom: 2px solid #eee; padding: 5px 10px 0 10px; }
        .inner-tab-link { padding: 12px 20px; border: none; background: none; cursor: pointer; font-size: 1rem; color: #555; font-weight: 500; border-bottom: 3px solid transparent; margin-bottom: -2px; display: flex; align-items: center; gap: 8px; }
        .inner-tab-link.active { color: #007bff; border-bottom-color: #007bff; }
        .inner-tab-content { padding: 25px; }
        .inner-tab-pane { display: none; } .inner-tab-pane.active { display: block; }
        .btn-crear-usuario { width: 100%; padding: 12px; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1.1rem; font-weight: 500; }
        .btn-crear-usuario:disabled { background-color: #aaa; }
        .btn-crear-encuestador { background-color: #28a745; }
        .btn-crear-alumno { background-color: #007bff; }
        .user-list-container { margin-top: 30px; }
        .search-bar-container { position: relative; margin-bottom: 15px; width: 100%; max-width: 400px; }
        #admin-user-search { width: 100%; padding: 10px 15px 10px 40px; border-radius: 5px; border: 1px solid #ccc; box-sizing: border-box; font-size: 1rem; }
        .search-bar-container i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999; }
        .filter-no-results td { text-align: center; padding: 20px; font-style: italic; color: #777; }
        .user-list-tabs { display: flex; border-bottom: 2px solid #ccc; }
        .user-tab-link { padding: 10px 20px; cursor: pointer; font-size: 1.1rem; font-weight: 500; color: #666; border-bottom: 3px solid transparent; margin-bottom: -2px; }
        .user-tab-link.active { color: #333; border-bottom-color: #333; }
        .user-list-content { background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-radius: 0 8px 8px 8px; }
        .user-list-pane { display: none; padding: 10px; } .user-list-pane.active { display: block; }
        .user-table { width: 100%; border-collapse: collapse; }
        .user-table th, .user-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        .user-table th { background-color: #f8f9fa; }
        .user-table .actions { display: flex; gap: 10px; }
        .user-table .btn-edit, .user-table .btn-delete { padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; color: white; }
        .user-table .btn-edit { background-color: #ffc107; }
        .user-table .btn-delete { background-color: #dc3545; }
        .swal-edit-form { text-align: left; }
        .swal-edit-form .form-group { margin-bottom: 15px; }
        .swal-edit-form .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .swal-edit-form .form-group input, .swal-edit-form .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        
        /* --- ✅ CSS DE FORM BUILDER (copiado de dashboard_general) --- */
        .btn-publish { background-color: #007bff; color: white; padding: 8px 15px; border: none; border-radius: 5px; font-size: 0.9rem; font-weight: 500; cursor: pointer; margin-right: 15px; display: flex; align-items: center; gap: 5px; }
        .btn-publish:hover { background-color: #0069d9; }
        .back-button-bar { background-color: #fff; padding: 10px 30px; border-bottom: 1px solid #ddd; box-shadow: 0 2px 4px rgba(0,0,0,0.03); }
        #btn-back-to-list { background: none; border: 1px solid #6c757d; color: #6c757d; padding: 5px 12px; border-radius: 5px; cursor: pointer; font-size: 0.9rem; font-weight: 500; display: flex; align-items: center; gap: 5px; transition: all 0.2s ease; }
        #btn-back-to-list:hover { background-color: #f8f9fa; color: #333; border-color: #5a6268; }
        .form-builder-container { max-width: 800px; margin: 0 auto; }
        .survey-header-editor, .pregunta-block { background: #fff; border: 1px solid #ccc; border-radius: 8px; padding: 25px; margin-bottom: 20px; border-left: 8px solid #e3eef6ff; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .pregunta-block:focus-within { border-left-color: #007bff; }
        .survey-header-editor input, .survey-header-editor textarea { width: 100%; border: none; border-bottom: 1px solid #eee; padding: 10px 0; font-size: 1.5rem; margin-bottom: 10px; box-sizing: border-box; }
        .survey-header-editor input:focus, .survey-header-editor textarea:focus { outline: none; border-bottom-color: #007bff; }
        .survey-header-editor textarea { font-size: 1rem; min-height: 40px; resize: vertical; }
        .survey-header-editor .form-group { margin-bottom: 0; }
        .survey-header-editor .form-group label { display: block; margin-bottom: 5px; font-weight: normal; font-size: 0.9rem; color: #555;}
        .survey-header-editor .form-group select { width: 100%; box-sizing: border-box; font-size: 0.9rem; padding: 5px; border: 1px solid #ccc; border-radius: 4px; background: #f9f9f9;}
        .pregunta-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 15px; margin-bottom: 15px; }
        .pregunta-header input[type="text"] { flex-grow: 1; padding: 10px; border: 1px dashed #ccc; border-radius: 4px; font-size: 1.1rem; box-sizing: border-box; }
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
        
        /* ✅ CSS PARA BOTONES DE ENCUESTA (copiado de dashboard_general) */
        .btn-admin-delete-survey, .btn-eliminar { background-color: #dc3545; }
        .btn-editar { background-color: #ffc107; color: #333; }
        .btn-publish-lista { background-color: #28a745; color: white;}
        .btn-cerrar { background-color: #ffc107; color: #333; }

        /* ✅ CSS PARA LA LISTA DE ENCUESTAS PROPIAS DEL ADMIN */
        .admin-own-surveys-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 10px 20px 20px 20px;
            margin-bottom: 30px;
        }
        .admin-own-surveys-container h2 {
            margin-top: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .admin-own-surveys-container .encuesta-item {
             border-bottom: 1px solid #f0f0f0;
             padding: 15px 0;
             margin-bottom: 0;
             border-radius: 0;
             box-shadow: none;
             display: flex;
             flex-direction: column;
        }
        .admin-own-surveys-container .encuesta-item:last-child {
            border-bottom: none;
            padding-bottom: 5px;
        }
        
        @media (min-width: 992px) { 
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .admin-own-surveys-container .encuesta-item { flex-direction: row; align-items: center; } 
        }
        @media (min-width: 768px) { 
            .encuesta-item { flex-direction: row; align-items: center; } 
            .encuesta-info { margin-bottom: 0; } 
            .encuesta-acciones { flex-grow: 0; flex-wrap: nowrap; } 
        }
        @media (max-width: 768px) { 
            .dashboard-header { flex-direction: column; padding: 10px; min-height: auto; align-items: stretch;} 
            .header-left-group { width: 100%; justify-content: space-between; margin-bottom: 10px;} 
            .header-right-group { width: 100%; justify-content: space-between; } 
            .dashboard-tabs { justify-content: center; } .header-logo { margin-right: 0; } 
            .user-table { font-size: 0.9rem; } 
            .user-table th, .user-table td { padding: 8px; } 
            .user-table .actions { flex-direction: column; } 
        }
        @media (max-width: 480px) { 
            .dashboard-tabs { flex-wrap: wrap; justify-content: center;} 
            .tab-link { font-size: 0.9rem; padding: 10px 8px; } 
            .header-logo { font-size: 1.2rem; } 
            .btn-logout, .user-profile span { font-size: 0.8rem;} 
            .tab-button-res { font-size: 0.95rem; padding: 10px 15px; } 
        }

        .info-basica-encuesta {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 8px;
    margin-top: 20px;
    border: 1px solid #e9ecef;
}

.pregunta-item {
    background: white;
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 5px;
    border-left: 4px solid #6c757d;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.participante-info {
    padding: 10px 15px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.participante-info:last-child {
    border-bottom: none;
}

.lista-participantes {
    list-style: none;
    padding: 0;
    margin: 0;
    background: white;
    border-radius: 5px;
    border: 1px solid #e9ecef;
    max-height: 400px;
    overflow-y: auto;
}
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <header class="dashboard-header">
            <div class="header-left-group">
                <div class="header-logo">Panel Admin</div>
                <nav class="dashboard-tabs">
                    <a href="#" class="tab-link active" id="btn-tab-estadisticas">
                        <i class="fa-solid fa-chart-line"></i> Estadísticas
                    </a>
                    <a href="#" class="tab-link" id="btn-tab-gestion-usuarios">
                        <i class="fa-solid fa-users-cog"></i> Gestión de Usuarios
                    </a>
                    <a href="#" class="tab-link" id="btn-tab-gestion-encuestas">
                        <i class="fa-solid fa-list-ul"></i> Gestión de Encuestas
                    </a>
                    <a href="#" class="tab-link" id="btn-tab-crear">
                        <i class="fa-solid fa-plus-circle"></i> Crear Encuesta
                    </a>
                </nav>
            </div>
            <div class="header-right-group">
                <div id="publish-button-placeholder"></div>
                <a href="logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
                <div class="user-profile"><i class="fa-solid fa-shield-halved"></i> <span><?php echo $nombre; ?></span></div>
            </div>
        </header>

        <div class="back-button-bar" id="back-button-container" style="display: none;">
            <button id="btn-back-to-list">
                <i class="fa-solid fa-arrow-left"></i> Volver a Gestión de Encuestas
            </button>
        </div>

        <main class="dashboard-content" id="dashboard-content-container">
            </main>
    </div>

    <script>
        // Configuración global de Toasts
        const Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true,
            didOpen: (toast) => { toast.onmouseenter = Swal.stopTimer; toast.onmouseleave = Swal.resumeTimer; }
        });
        
        let preguntaIndex = 0; // Contador global para el form builder

        // --- Navegación ---
        function activarTab(tabId) {
            $('.tab-link').removeClass('active');
            if (tabId) { $(tabId).addClass('active'); }
        }

        function setNavContext(context) {
            if (context === 'list') {
                $('#publish-button-placeholder').empty();
                $('#back-button-container').hide();
            } else if (context === 'form-create') {
                $('#back-button-container').show();
                $('#publish-button-placeholder').html(`<button type="submit" form="form-crear-encuesta" class="btn-publish" style="background-color: #007bff; color: white;"><i class="fa-solid fa-save"></i> Guardar Encuesta</button>`);
            } else if (context === 'form-edit') { // ✅ AÑADIDO
                 $('#back-button-container').show();
                $('#publish-button-placeholder').html(`<button type="submit" form="form-editar-encuesta" class="btn-publish" style="background-color: #ffc107; color: #333;"><i class="fa-solid fa-save"></i> Actualizar Borrador</button>`);
            } else if (context === 'results') {
                $('#back-button-container').show();
                $('#publish-button-placeholder').empty();
            }
        }


        // --- Cargar Vistas del Admin ---

        // 0. Cargar "Estadísticas" (Home)
        function cargarEstadisticas() {
            activarTab('#btn-tab-estadisticas');
            setNavContext('list');
            const container = $('#dashboard-content-container');
            container.html('<div id="loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando estadísticas...</div>');

            $.ajax({
                url: '../api/adminObtenerEstadisticas.php',
                method: 'GET', dataType: 'json',
                success: function(response) {
                    if (response.success && response.estadisticas) {
                        const stats = response.estadisticas;
                        let html = `
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <h3>Encuestas Publicadas por Visibilidad</h3>
                                    <div class="pie-chart-container"><canvas id="visibilidadPieChart"></canvas></div>
                                </div>
                                <div class="stat-card">
                                    <h3>Total de Preguntas Creadas por Tipo</h3>
                                    <div class="bar-chart-container"><canvas id="tiposPreguntaBarChart"></canvas></div>
                                </div>
                            </div>`;
                        container.html(html);

                        // ... (código de pintar gráficos) ...
                        try { const visData = stats.visibilidad || []; const visLabels = visData.map(item => item.visibilidad.charAt(0).toUpperCase() + item.visibilidad.slice(1)); const visCounts = visData.map(item => item.total); if(visData.length > 0){ const ctxPie = document.getElementById('visibilidadPieChart').getContext('2d'); new Chart(ctxPie, { type: 'pie', data: { labels: visLabels, datasets: [{ label: 'Encuestas', data: visCounts, backgroundColor: ['rgba(75, 192, 192, 0.7)', 'rgba(201, 203, 207, 0.7)'], borderColor: ['rgba(75, 192, 192, 1)', 'rgba(201, 203, 207, 1)'], }] }, options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'top' } } } }); } else { $('#visibilidadPieChart').parent().html('<p style="text-align:center; padding: 20px;">No hay encuestas publicadas.</p>'); } } catch (e) { console.error("Error al crear gráfico de visibilidad:", e); }
                        try { const tiposData = stats.tipos_pregunta || []; const tiposLabels = tiposData.map(item => item.tipo_pregunta); const tiposCounts = tiposData.map(item => item.total); if(tiposData.length > 0){ const ctxBar = document.getElementById('tiposPreguntaBarChart').getContext('2d'); new Chart(ctxBar, { type: 'bar', data: { labels: tiposLabels, datasets: [{ label: 'Total de Preguntas', data: tiposCounts, backgroundColor: 'rgba(54, 162, 235, 0.6)', borderColor: 'rgba(54, 162, 235, 1)', borderWidth: 1 }] }, options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { legend: { display: false } } } }); } else { $('#tiposPreguntaBarChart').parent().html('<p style="text-align:center; padding: 20px;">No se han creado preguntas.</p>'); } } catch (e) { console.error("Error al crear gráfico de tipos:", e); }

                    } else { container.html(`<p style="color: red;">${response.mensaje || 'No se pudieron cargar las estadísticas.'}</p>`); }
                },
                error: function() { container.html('<p style="color: red;">Error de conexión.</p>'); }
            });
        }

        // 1. Cargar "Gestión de Usuarios"
        function cargarGestionUsuarios() {
            activarTab('#btn-tab-gestion-usuarios');
            setNavContext('list');
            const container = $('#dashboard-content-container');
            
            const html = `
                <div class="gestion-usuarios-container">
                    <button id="btn-toggle-forms" class="add-user-toggle">
                        <i class="fa-solid fa-plus"></i> Añadir Nuevo Usuario
                    </button>
                    <div id="form-accordion-content" class="form-accordion-content">
                        <div class="inner-tabs-container">
                            <div class="inner-tab-buttons">
                                <button class="inner-tab-link active" data-tab="crear-encuestador"><i class="fa-solid fa-user-tie"></i> Registrar Encuestador</button>
                                <button class="inner-tab-link" data-tab="crear-alumno"><i class="fa-solid fa-user-graduate"></i> Registrar Alumno</button>
                            </div>
                            <div class="inner-tab-content">
                                <div id="tab-crear-encuestador" class="inner-tab-pane active">
                                    <form id="form-crear-encuestador">
                                        <h2 style="text-align: center; margin-bottom: 20px;">Registrar Nuevo Encuestador (Maestro)</h2>
                                        <div class="form-group"><label for="admin-nombre-enc">Nombres</label><input type="text" id="admin-nombre-enc" required></div>
                                        <div class="form-group"><label for="admin-apellido-enc">Apellidos</label><input type="text" id="admin-apellido-enc" required></div>
                                        <div class="form-group"><label for="admin-email-enc">Correo Electrónico</label><input type="email" id="admin-email-enc" placeholder="ejemplo@tecmerida.com" required></div>
                                        <div class="form-group"><label for="admin-carrera-enc">Carrera</label>
                                                                                    <select id="admin-carrera-enc" name="carrera" required>
                                                                                        <option value="">Seleccione Carrera</option>
                                                                                        <option value="ingenieria-sistemas">Ingeniería en Sistemas</option>
                                                                                        <option value="ingenieria-civil">Ingeniería Civil</option>
                                                                                        <option value="medicina">Medicina</option>
                                                                                        <option value="derecho">Derecho</option>
                                                                                        <option value="administracion">Administración</option>
                                                                                        <option value="contabilidad">Contabilidad</option>
                                                                                        <option value="psicologia">Psicología</option>
                                                                                        <option value="arquitectura">Arquitectura</option>
                                                                                    </select>
                                                                                </div>
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
                                        <div class="form-group"><label for="admin-carrera-alu">Carrera</label>
                                            <select id="admin-carrera-alu" name="carrera" required>
                                                <option value="">Seleccione Carrera</option>
                                                <option value="ingenieria-sistemas">Ingeniería en Sistemas</option>
                                                <option value="ingenieria-civil">Ingeniería Civil</option>
                                                <option value="medicina">Medicina</option>
                                                <option value="derecho">Derecho</option>
                                                <option value="administracion">Administración</option>
                                                <option value="contabilidad">Contabilidad</option>
                                                <option value="psicologia">Psicología</option>
                                                <option value="arquitectura">Arquitectura</option>
                                            </select>
                                        </div>
                                        <div class="form-group"><label for="admin-genero-alu">Género</label>
                                            <select id="admin-genero-alu" required>
                                                <option value="" disabled selected>Seleccione...</option>
                                                <option value="hombre">Hombre</option>
                                                <option value="mujer">Mujer</option>
                                                <option value="otro">Otro</option>
                                            </select>
                                        </div>
                                        <div class="form-group"><label for="admin-contrasena-alu">Contraseña</label><input type="password" id="admin-contrasena-alu" required><div class="password-hint">Debe cumplir: 8+ carac, 1 especial, termina en "AL"</div></div>
                                        <div class="form-group"><label for="admin-confirmar-alu">Confirmar Contraseña</label><input type="password" id="admin-confirmar-alu" required></div>
                                        <button type="submit" class="btn-crear-usuario btn-crear-alumno"><i class="fa-solid fa-user-plus"></i> Crear Alumno</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="user-list-container">
                        <div class="search-bar-container">
                            <i class="fa-solid fa-search"></i>
                            <input type="text" id="admin-user-search" placeholder="Buscar por nombre, apellido, correo...">
                        </div>
                        <div class="user-list-tabs">
                            <button class="user-tab-link active" data-tab="lista-encuestadores">Encuestadores</button>
                            <button class="user-tab-link" data-tab="lista-alumnos">Alumnos</button>
                        </div>
                        <div class="user-list-content">
                            <div id="tab-lista-encuestadores" class="user-list-pane active">
                                <div id="loading-encuestadores" style="text-align:center; padding:20px;"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</div>
                                <table class="user-table" id="tabla-encuestadores" style="width:100%;">
                                    <thead><tr><th>Nombre</th><th>Correo</th><th>Asignatura</th><th>Acciones</th></tr></thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                            <div id="tab-lista-alumnos" class="user-list-pane">
                                <div id="loading-alumnos" style="text-align:center; padding:20px;"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</div>
                                <table class="user-table" id="tabla-alumnos" style="width:100%;">
                                    <thead><tr><th>Nombre</th><th>Correo</th><th>Carrera</th><th>Acciones</th></tr></thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>`;
            container.html(html);
            
            cargarTablaEncuestadores();
            cargarTablaAlumnos();
        }

        // --- Funciones Auxiliares para Gestión de Usuarios ---
        function cargarTablaEncuestadores() {
            $('#loading-encuestadores').show();
            $.ajax({
                url: '../api/adminListarEncuestadores.php', method: 'GET', dataType: 'json',
                success: function(res) {
                    const $tbody = $('#tabla-encuestadores tbody').empty();
                    $('#loading-encuestadores').hide();
                    if (res.success && res.encuestadores.length > 0) {
                        res.encuestadores.forEach(user => {
                            $tbody.append(`
                                <tr data-id="${user.id_usuario}">
                                    <td>${user.apellido}, ${user.nombre}</td>
                                    <td>${user.email}</td>
                                    <td>${user.asignatura || 'N/A'}</td>
                                    <td class="actions">
                                        <button class="btn-edit" data-id="${user.id_usuario}" data-rol="encuestador" title="Editar"><i class="fa-solid fa-pencil"></i></button>
                                        <button class="btn-delete" data-id="${user.id_usuario}" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>`);
                        });
                    } else {
                        $tbody.append('<tr class="no-data-row"><td colspan="4" style="text-align:center; padding: 20px;">No hay encuestadores registrados.</td></tr>');
                    }
                }
            });
        }
        function cargarTablaAlumnos() {
             $('#loading-alumnos').show();
            $.ajax({
                url: '../api/adminListarAlumnos.php', method: 'GET', dataType: 'json',
                success: function(res) {
                    const $tbody = $('#tabla-alumnos tbody').empty();
                     $('#loading-alumnos').hide();
                    if (res.success && res.alumnos.length > 0) {
                        res.alumnos.forEach(user => {
                            $tbody.append(`
                                <tr data-id="${user.id_usuario}">
                                    <td>${user.apellido}, ${user.nombre}</td>
                                    <td>${user.email}</td>
                                    <td>${user.carrera || 'N/A'}</td>
                                    <td class="actions">
                                        <button class="btn-edit" data-id="${user.id_usuario}" data-rol="alumno" title="Editar"><i class="fa-solid fa-pencil"></i></button>
                                        <button class="btn-delete" data-id="${user.id_usuario}" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>`);
                        });
                    } else {
                        $tbody.append('<tr class="no-data-row"><td colspan="4" style="text-align:center; padding: 20px;">No hay alumnos registrados.</td></tr>');
                    }
                }
            });
        }
        
        // --- ✅ 2. Cargar "Gestión de Encuestas" (ACTUALIZADO) ---
        function cargarGestionEncuestas() {
            activarTab('#btn-tab-gestion-encuestas');
            setNavContext('list');
            const container = $('#dashboard-content-container');
            const adminId = <?php echo json_encode($_SESSION['usuario']['id_usuario']); ?>;

            container.html(`
                <div class="admin-own-surveys-container">
                    <h2><i class="fa-solid fa-user-shield"></i> Mis Encuestas (Creadas como Admin)</h2>
                    <div id="admin-my-surveys-list">
                        <div id="loading-my-surveys" style="text-align:center; padding: 20px;"><i class="fa-solid fa-spinner fa-spin"></i> Cargando mis encuestas...</div>
                    </div>
                </div>
                
                <h2 style="margin-top: 30px;"><i class="fa-solid fa-users"></i> Encuestas de otros Encuestadores</h2>
                <div id="admin-other-surveys-accordion">
                    <div id="loading-other-surveys" style="text-align:center; padding: 20px;"><i class="fa-solid fa-spinner fa-spin"></i> Cargando encuestadores...</div>
                </div>
            `);

            // --- AJAX Call 1: Mis Encuestas (Admin) ---
            $.ajax({
                url: '../api/misEncuestas.php', 
                method: 'GET', dataType: 'json',
                success: function(response) {
                    const $listContainer = $('#admin-my-surveys-list').empty();
                    if (response.success && response.encuestas.length > 0) {
                        response.encuestas.forEach(function(encuesta) {
                            $listContainer.append(generarHtmlEncuestaItem(encuesta));
                        });
                    } else {
                        $listContainer.html('<p style="text-align:center; padding: 10px;">No has creado ninguna encuesta como administrador.</p>');
                    }
                },
                error: function() { $('#admin-my-surveys-list').html('<p style="color: red; text-align:center;">Error al cargar mis encuestas.</p>'); }
            });

            // --- AJAX Call 2: Encuestas de Otros ---
            $.ajax({
                url: '../api/adminListarEncuestadores.php', method: 'GET', dataType: 'json',
                success: function(response) {
                    const $accordionContainer = $('#admin-other-surveys-accordion').empty();
                    if (response.success && response.encuestadores) {
                        const otrosEncuestadores = response.encuestadores.filter(enc => enc.id_usuario != adminId);
                        if (otrosEncuestadores.length === 0) {
                            $accordionContainer.html('<p style="padding: 10px; text-align:center;">No hay otros encuestadores registrados.</p>'); return;
                        }
                        let accordionHtml = '<div class="lista-encuestadores-admin">';
                        otrosEncuestadores.forEach(enc => {
                            accordionHtml += `
                                <div class="encuestador-item">
                                    <button class="encuestador-acordeon" data-id="${enc.id_usuario}">
                                        <span><i class="fa-solid fa-user-tie"></i> ${enc.apellido}, ${enc.nombre} (${enc.email})</span>
                                        <i class="fa-solid fa-chevron-down icon-chevron"></i>
                                    </button>
                                    <div class="panel-encuestas" id="panel-encuestador-${enc.id_usuario}"><div class="panel-loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</div></div>
                                </div>`;
                        });
                        accordionHtml += '</div>';
                        $accordionContainer.append(accordionHtml);
                    } else { $accordionContainer.html(`<p style="color: red;">${response.mensaje}</p>`); }
                },
                error: function() { $('#admin-other-surveys-accordion').html('<p style="color: red;">Error de conexión.</p>'); }
            });
        }
        
        // ✅ NUEVO: Helper para generar botones de encuesta (lógica de dashboard_general)
        function generarHtmlEncuestaItem(encuesta, esPropia = true) {
    const estadoClase = `estado-${encuesta.estado}`;
    const estadoTexto = encuesta.estado.charAt(0).toUpperCase() + encuesta.estado.slice(1);
    let botonesAccion = '';

    if (esPropia) {
        // Botones completos para encuestas propias
        if (encuesta.estado === 'borrador') {
            botonesAccion = `<button class="btn-editar" data-id="${encuesta.id_encuesta}"><i class="fa-solid fa-edit"></i> Editar</button>
                             <button class="btn-publish-lista" data-id="${encuesta.id_encuesta}"><i class="fa-solid fa-paper-plane"></i> Publicar</button>`;
        } else if (encuesta.estado === 'publicada') {
            botonesAccion = `<button class="btn-resultados" data-id="${encuesta.id_encuesta}" data-titulo="${encuesta.titulo}"><i class="fa-solid fa-chart-pie"></i> Resultados</button>
                             <button class="btn-cerrar" data-id="${encuesta.id_encuesta}" data-nuevo-estado="cerrada"><i class="fa-solid fa-lock"></i> Cerrar</button>`;
        } else if (encuesta.estado === 'cerrada') {
            botonesAccion = `<button class="btn-resultados" data-id="${encuesta.id_encuesta}" data-titulo="${encuesta.titulo}"><i class="fa-solid fa-chart-pie"></i> Resultados</button>
                             <button class="btn-cerrar" data-id="${encuesta.id_encuesta}" data-nuevo-estado="publicada" style="background-color: #28a745; color: white;"><i class="fa-solid fa-lock-open"></i> Re-Publicar</button>`;
        }
    } else {
        // Solo botón de vista limitada para encuestas ajenas
        botonesAccion = `<button class="btn-resultados btn-resultados-limitado" data-id="${encuesta.id_encuesta}" data-titulo="${encuesta.titulo}"><i class="fa-solid fa-eye"></i> Ver Encuesta</button>`;
    }
    
    botonesAccion += ` <button class="btn-eliminar" data-id="${encuesta.id_encuesta}"><i class="fa-solid fa-trash-alt"></i> Eliminar</button>`;

    const tituloEscapado = $('<div>').text(encuesta.titulo || 'Encuesta sin título').html();
    let fechaFormateada = '';
    try { fechaFormateada = new Date(encuesta.fecha_creacion).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' }); } catch(e) {}
    
    return `<div class="encuesta-item" data-id-encuesta="${encuesta.id_encuesta}">
                <div class="encuesta-info">
                    <h3>${tituloEscapado}</h3>
                    <div><small>Creada: ${fechaFormateada}</small><span class="${estadoClase}">${estadoTexto}</span></div>
                </div>
                <div class="encuesta-acciones">${botonesAccion}</div>
            </div>`;
}


            function mostrarResultadosCompletos(r, idEncuesta, tituloEncuesta) {
    let html = `
        <div class="resultados-container">
            <div class="resultados-header">
                <h2>${$('<div>').text(r.titulo || tituloEncuesta).html()}</h2>
                <div style="background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 5px; padding: 10px 15px; margin: 10px 0;">
                    <i class="fa-solid fa-chart-bar" style="color: #0c5460;"></i> 
                    <strong>Vista completa</strong> - Esta es tu encuesta
                </div>
                <p>Visibilidad: ${r.visibilidad} | Estado: ${r.estado}</p>
            </div>`;

    const totalRespuestas = r.resumen_participacion.respuestas_anonimas + r.resumen_participacion.respuestas_identificadas;
    
    html += `
        <div class="tabs-container-resultados">
            <div class="tab-buttons">
                <button class="tab-button-res active" data-tab="participacion"><i class="fa-solid fa-chart-pie"></i> Participación</button>
                <button class="tab-button-res" data-tab="preguntas"><i class="fa-solid fa-chart-bar"></i> Preguntas</button>
                <button class="tab-button-res" data-tab="participantes" style="${r.visibilidad === 'identificada' && r.participantes_identificados && r.participantes_identificados.length > 0 ? '' : 'display: none;'}"><i class="fa-solid fa-users"></i> Participantes</button>
            </div>
            <div class="tab-content-res">
                <div id="tab-participacion" class="tab-pane-res active">
                    <h3>Resumen de Participación (${totalRespuestas} respuestas totales)</h3>`;
    
    if (totalRespuestas > 0) {
        html += `<div class="pie-chart-container"><canvas id="pieChartParticipacion"></canvas></div>`;
    } else {
        html += `<div style="text-align: center; padding: 30px; border: 1px dashed #ccc; border-radius: 8px; margin-top: 20px;">
                    <i class="fa-solid fa-inbox fa-2x" style="color: #ccc; margin-bottom: 15px;"></i>
                    <p><strong>Aún no hay respuestas</strong>.</p>
                 </div>`;
    }
    
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
        </div>
    </div>`;
    
    $('#dashboard-content-container').html(html);

    // Pintar gráfico de participación si hay respuestas
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
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    }
                }
            });
        } catch (e) {
            console.error("Error al crear gráfico pastel:", e);
        }
    }

    // Pintar gráficos de preguntas
    const preguntasGraficosContainer = $('#preguntas-graficos-container');
    if (totalRespuestas > 0 && r.preguntas && r.preguntas.length > 0) {
        let preguntasConEstadisticas = false;
        
        r.preguntas.forEach((preg, index) => {
            const txt = $('<div>').text(preg.texto_pregunta).html();
            
            if (['opcion_multiple', 'si_no', 'escala', 'seleccion_multiple'].includes(preg.tipo_pregunta)) {
                if (preg.resultados && preg.resultados.length > 0) {
                    preguntasConEstadisticas = true;
                    const labels = [];
                    const data = [];
                    const bg = ['rgba(54, 162, 235, 0.6)', 'rgba(255, 206, 86, 0.6)', 'rgba(75, 192, 192, 0.6)', 'rgba(153, 102, 255, 0.6)', 'rgba(255, 159, 64, 0.6)', 'rgba(255, 99, 132, 0.6)'];
                    const bd = ['rgba(54, 162, 235, 1)', 'rgba(255, 206, 86, 1)', 'rgba(75, 192, 192, 1)', 'rgba(153, 102, 255, 1)', 'rgba(255, 159, 64, 1)', 'rgba(255, 99, 132, 1)'];
                    
                    preg.resultados.forEach((res, i) => {
                        labels.push(res.texto_opcion);
                        data.push(res.conteo);
                    });
                    
                    const pHtml = `<div class="pregunta-resultado-grafico">
                                      <h4>${index + 1}. ${txt}</h4>
                                      <div class="bar-chart-container">
                                          <canvas id="barChartPregunta${preg.id_pregunta}"></canvas>
                                      </div>
                                   </div>`;
                    preguntasGraficosContainer.append(pHtml);
                    
                    try {
                        setTimeout(() => {
                            const ctxBar = document.getElementById(`barChartPregunta${preg.id_pregunta}`)?.getContext('2d');
                            if (ctxBar) {
                                new Chart(ctxBar, {
                                    type: 'bar',
                                    data: {
                                        labels: labels,
                                        datasets: [{
                                            label: '# de Respuestas',
                                            data: data,
                                            backgroundColor: bg.slice(0, data.length),
                                            borderColor: bd.slice(0, data.length),
                                            borderWidth: 1
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        scales: {
                                            y: {
                                                beginAtZero: true,
                                                ticks: {
                                                    precision: 0
                                                }
                                            }
                                        },
                                        plugins: {
                                            legend: {
                                                display: false
                                            }
                                        }
                                    }
                                });
                            }
                        }, 100);
                    } catch (e) {
                        console.error(`Error al crear gráfico ${preg.id_pregunta}:`, e);
                    }
                } else {
                    preguntasGraficosContainer.append(`<div class="pregunta-resultado-grafico"><h4>${index + 1}. ${txt}</h4><p><em>No hay respuestas para esta pregunta.</em></p></div>`);
                }
            } else if (preg.tipo_pregunta === 'abierta') {
                preguntasConEstadisticas = true;
                let aHtml = `<div class="pregunta-resultado-abierta"><h4>${index + 1}. ${txt} (Respuesta Corta)</h4>`;
                if (preg.resultados && preg.resultados.length > 0) {
                    preg.resultados.forEach(res => {
                        aHtml += `<div class="respuesta-abierta">"${$('<div>').text(res.texto_respuesta).html()}" <span>- ${$('<div>').text(res.participante || 'Anónimo').html()}</span></div>`;
                    });
                } else {
                    aHtml += `<p><em>No hay respuestas.</em></p>`;
                }
                aHtml += `</div>`;
                preguntasGraficosContainer.append(aHtml);
            }
        });
        
        if (!preguntasConEstadisticas) {
            preguntasGraficosContainer.html('<div style="text-align: center; padding: 20px;"><p>No hay preguntas con respuestas contables.</p></div>');
        }
    } else {
        preguntasGraficosContainer.html('<div style="text-align: center; padding: 30px; border: 1px dashed #ccc; border-radius: 8px; margin-top: 20px;"><i class="fa-solid fa-inbox fa-2x" style="color: #ccc; margin-bottom: 15px;"></i><p><strong>Aún no hay respuestas</strong>.</p></div>');
    }

    // Mostrar lista de participantes si es encuesta identificada
    const pCont = $('#participantes-lista-container');
    if (r.visibilidad === 'identificada' && r.participantes_identificados && r.participantes_identificados.length > 0) {
        let lHtml = '<ul class="lista-participantes">';
        r.participantes_identificados.forEach(p => {
            const nom = `${p.apellido}, ${p.nombre}`;
            const nomEsc = $('<div>').text(nom).html();
            lHtml += `<li><a href="#" class="participante-link admin-ver-respuestas" data-id-encuesta="${idEncuesta}" data-id-alumno="${p.id_usuario}" data-nombre-alumno="${nomEsc}"><i class="fa-solid fa-user"></i> ${nomEsc}</a></li>`;
        });
        lHtml += '</ul>';
        pCont.html(lHtml);
    } else {
        pCont.html('<p>Encuesta anónima o sin respuestas identificadas.</p>');
    }

    // Agregar event listeners para las pestañas
    $('.tab-button-res').off('click').on('click', function(e) {
        e.preventDefault();
        const tabId = $(this).data('tab');
        $(this).closest('.tab-buttons').find('.tab-button-res').removeClass('active');
        $(this).closest('.tabs-container-resultados').find('.tab-pane-res').removeClass('active');
        $(this).addClass('active');
        $(`#tab-${tabId}`).addClass('active');
    });
}

        // 3. Cargar Resultados (versión Admin)
        function cargarResultadosAdmin(idEncuesta, tituloEncuesta) {
    activarTab(null);
    setNavContext('results');
    const container = $('#dashboard-content-container');
    container.html('<div id="loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando resultados...</div>');
    
    $.ajax({
        url: `../api/adminObtenerResultados.php?id_encuesta=${idEncuesta}`,
        method: 'GET', 
        dataType: 'json',
        success: function(response) {
            if (response.success && response.resultados) {
                const r = response.resultados;
                
                // Verificar si es acceso limitado
                if (r.acceso_limitado) {
                    mostrarResultadosLimitados(r, idEncuesta, tituloEncuesta);
                } else {
                    mostrarResultadosCompletos(r, idEncuesta, tituloEncuesta);
                }
            } else {
                container.html(`<p style="color: red;">${response.mensaje || 'Error al cargar resultados.'}</p>`);
            }
        },
        error: function() {
            container.html('<p style="color: red;">Error de conexión.</p>');
        }
    });
}

function mostrarResultadosLimitados(r, idEncuesta, tituloEncuesta) {
    let html = `
        <div class="resultados-container">
            <div class="resultados-header">
                <h2>${$('<div>').text(r.titulo || tituloEncuesta).html()}</h2>
                <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 10px 15px; margin: 10px 0;">
                    <i class="fa-solid fa-eye-slash" style="color: #856404;"></i> 
                    <strong>Vista limitada</strong> - Encuesta de: ${r.encuestador || 'Otro encuestador'}
                </div>
                <p>Visibilidad: ${r.visibilidad} | Estado: ${r.estado}</p>
                ${r.descripcion ? `<p><strong>Descripción:</strong> ${$('<div>').text(r.descripcion).html()}</p>` : ''}
            </div>
            
            <div class="info-basica-encuesta">
                <h3><i class="fa-solid fa-list"></i> Preguntas de la encuesta (${r.preguntas ? r.preguntas.length : 0})</h3>
                <div class="lista-preguntas">
    `;
    
    if (r.preguntas && r.preguntas.length > 0) {
        r.preguntas.forEach((pregunta, index) => {
            const tipoTexto = {
                'opcion_multiple': 'Opción Múltiple',
                'seleccion_multiple': 'Selección Múltiple', 
                'abierta': 'Respuesta Corta',
                'si_no': 'Verdadero/Falso',
                'escala': 'Escala (1-5)'
            }[pregunta.tipo_pregunta] || pregunta.tipo_pregunta;
            
            html += `
                <div class="pregunta-item">
                    <h4>${index + 1}. ${$('<div>').text(pregunta.texto_pregunta).html()}</h4>
                    <small>Tipo: ${tipoTexto}</small>
                </div>
            `;
        });
    } else {
        html += `<p style="text-align: center; padding: 20px; color: #666;">No hay preguntas en esta encuesta.</p>`;
    }
    
    html += `</div>`;
    
    // Mostrar participantes si es encuesta identificada
    if (r.visibilidad === 'identificada' && r.participantes_identificados && r.participantes_identificados.length > 0) {
        html += `
            <h3 style="margin-top: 30px;"><i class="fa-solid fa-users"></i> Participantes (${r.participantes_identificados.length})</h3>
            <ul class="lista-participantes">
        `;
        r.participantes_identificados.forEach(participante => {
            const nombreCompleto = `${participante.nombre} ${participante.apellido}`.trim();
            const displayName = nombreCompleto || participante.email;
            html += `
                <li>
                    <div class="participante-info">
                        <i class="fa-solid fa-user"></i> 
                        ${$('<div>').text(displayName).html()}
                        ${participante.email ? `<small style="color: #666; margin-left: 10px;">${participante.email}</small>` : ''}
                    </div>
                </li>
            `;
        });
        html += `</ul>`;
    } else if (r.visibilidad === 'identificada') {
        html += `
            <h3 style="margin-top: 30px;"><i class="fa-solid fa-users"></i> Participantes</h3>
            <p style="text-align: center; padding: 20px; color: #666;">No hay participantes aún.</p>
        `;
    }
    
    html += `</div></div>`;
    
    $('#dashboard-content-container').html(html);
}
        
        // 4. Mostrar Modal de Respuestas (versión Admin)
        function mostrarRespuestasAlumnoAdmin(idEncuesta, idAlumno, nombreAlumno) {
            // ... (código igual que antes, omitido por brevedad) ...
            Swal.fire({ title: `Cargando respuestas de ${nombreAlumno}...`, didOpen: () => { Swal.showLoading(); } }); $.ajax({ url: `../api/obtenerRespuestasDeAlumno.php?id_encuesta=${idEncuesta}&id_alumno=${idAlumno}`, method: 'GET', dataType: 'json', success: function(response) { if (response.success && response.respuestas_alumno && Array.isArray(response.respuestas_alumno)) { let html = '<div class="swal-form-respuestas">'; let preguntaNumero = 1; response.respuestas_alumno.forEach(pregunta => { html += `<div class="swal-pregunta-item"><h4>${preguntaNumero++}. ${$('<div>').text(pregunta.texto_pregunta).html()}</h4>`; const respuesta = pregunta.respuesta_alumno; if (pregunta.tipo_pregunta === 'abierta') { let texto = '<em>(No respondió)</em>'; if(respuesta && respuesta.texto_respuesta_abierta) { texto = $('<div>').text(respuesta.texto_respuesta_abierta).html(); } html += `<div class="swal-respuesta-abierta-display">${texto}</div>`; } else if (pregunta.opciones && pregunta.opciones.length > 0) { pregunta.opciones.forEach(opcion => { let esSeleccionada = false; if (respuesta && respuesta.opciones_seleccionadas) { esSeleccionada = respuesta.opciones_seleccionadas.includes(opcion.id_opcion); } let iconClass = 'fa-regular fa-circle'; if (pregunta.tipo_pregunta === 'seleccion_multiple') { iconClass = 'fa-regular fa-square'; } if (esSeleccionada) { iconClass = (pregunta.tipo_pregunta === 'seleccion_multiple') ? 'fa-solid fa-square-check' : 'fa-solid fa-check-circle'; } const textoOpcion = $('<div>').text(opcion.texto_opcion).html(); html += `<div class="swal-opcion-display ${esSeleccionada ? 'selected' : ''}"><i class="${iconClass}"></i> ${textoOpcion}</div>`; }); } else { html += `<p><em>(Pregunta sin opciones)</em></p>`; } html += `</div>`; }); html += "</div>"; Swal.update({ title: `Respuestas de ${nombreAlumno}`, html: html, icon: undefined, width: '700px', showConfirmButton: true, confirmButtonText: "Cerrar" }); } else { Swal.fire("Error", response.mensaje || "No se pudieron cargar.", "warning"); } }, error: function(jqXHR) { let msg = "Error de conexión."; if (jqXHR.status === 403) msg = "No tienes permiso."; Swal.fire("Error", msg, "error"); } });
        }
        
        // --- 5. Cargar "Crear Nueva Encuesta" (Admin) ---
        function cargarFormCrearAdmin() {
            activarTab('#btn-tab-crear');
            setNavContext('form-create');
            const container = $('#dashboard-content-container');
            const formHtml = `
                <form id="form-crear-encuesta" class="form-builder-container">
                    <div class="survey-header-editor">
                        <input type="text" id="titulo" name="titulo" placeholder="Título del formulario" required>
                        <textarea id="descripcion" name="descripcion" placeholder="Descripción del formulario"></textarea>
                        <div style="display: flex; gap: 20px; margin-top: 15px; flex-wrap: wrap;">
                            <div class="form-group" style="flex: 1; min-width: 150px;"><label>Visibilidad:</label><select id="visibilidad" name="visibilidad"><option value="identificada">Identificada</option><option value="anonima">Anónima</option></select></div>
                            <div class="form-group" style="flex: 1; min-width: 150px;"><label>Estado inicial:</label><select id="estado" name="estado"><option value="borrador">Borrador</option><option value="publicada">Publicada</option></select></div>
                        </div>
                    </div>
                    <div id="preguntas-container"></div>
                    <button type="button" id="btn-add-pregunta" class="btn-add-pregunta"><i class="fa-solid fa-plus"></i> Añadir Pregunta</button>
                </form>`;
            container.html(formHtml);
            preguntaIndex = 0;
            agregarPregunta();
        }

        // --- ✅ 6. Cargar Formulario para EDITAR Borrador (Admin) ---
        function cargarFormEditarAdmin(idEncuesta) {
            activarTab(null);
            setNavContext('form-edit');
            const container = $('#dashboard-content-container');
            container.html('<div id="loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando datos del borrador...</div>');

            $.ajax({
                url: `../api/obtenerEncuestaEditable.php?id_encuesta=${idEncuesta}`,
                method: 'GET', dataType: 'json',
                success: function(response) {
                    if (response.success && response.encuesta) {
                        const encuesta = response.encuesta;
                        // El admin puede editar encuestas publicadas, pero al guardar se guardan como "borrador"
                        const estadoOriginal = encuesta.estado;
                        let formHtml = `<form id="form-editar-encuesta" class="form-builder-container"><input type="hidden" name="id_encuesta" value="${encuesta.id_encuesta}"><div class="survey-header-editor"><input type="text" id="titulo" name="titulo" placeholder="Título del formulario" value="${encuesta.titulo || ''}" required><textarea id="descripcion" name="descripcion" placeholder="Descripción del formulario">${encuesta.descripcion || ''}</textarea><div style="display: flex; gap: 20px; margin-top: 15px; flex-wrap: wrap;"><div class="form-group" style="flex: 1; min-width: 150px;"><label>Visibilidad:</label><select id="visibilidad" name="visibilidad"><option value="identificada" ${encuesta.visibilidad === 'identificada' ? 'selected' : ''}>Identificada</option><option value="anonima" ${encuesta.visibilidad === 'anonima' ? 'selected' : ''}>Anónima</option></select></div><div class="form-group" style="flex: 1; min-width: 150px;"><label>Estado (Al guardar, volverá a borrador):</label><input type="text" value="${estadoOriginal}" style="font-size: 0.9rem; padding: 5px; background: #eee;" disabled><input type="hidden" name="estado" value="borrador"></div></div></div><div id="preguntas-container"></div><button type="button" id="btn-add-pregunta" class="btn-add-pregunta"><i class="fa-solid fa-plus"></i> Añadir Pregunta</button></form>`;
                        container.html(formHtml);
                        preguntaIndex = 0;
                        if (encuesta.preguntas && encuesta.preguntas.length > 0) {
                            encuesta.preguntas.forEach(pregunta => { agregarPreguntaConDatos(pregunta); });
                        } else {
                            agregarPregunta();
                        }
                    } else { Swal.fire('Error', response.mensaje || 'No se pudo cargar el borrador.', 'error'); cargarGestionEncuestas(); }
                },
                error: function(jqXHR) { let msg = 'Error de conexión.'; if(jqXHR.status === 404) msg = 'Borrador no encontrado.'; Swal.fire('Error', msg, 'error'); cargarGestionEncuestas(); }
            });
        }

        // --- ✅ 7. Funciones del Form Builder (Copiadas) ---
        function agregarPregunta() {
            const index = preguntaIndex++;
            const preguntaHtml = `
                <div class="pregunta-block" data-index="${index}">
                    <div class="pregunta-header">
                        <input type="text" name="preguntas[${index}][texto_pregunta]" placeholder="Pregunta sin título" required>
                        <select name="preguntas[${index}][tipo_pregunta]" class="tipo-pregunta-selector">
                            <option value="opcion_multiple">Opción Múltiple</option>
                            <option value="seleccion_multiple">Selección Múltiple</option>
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

        function agregarOpcion(container, indexPregunta, opcionIndex) {
             const tipoPregunta = container.closest('.pregunta-block').find('.tipo-pregunta-selector').val();
             const iconClass = (tipoPregunta === 'seleccion_multiple') ? 'far fa-square' : 'far fa-circle';
            const opcionHtml = `<div class="opcion-item"><i class="${iconClass}" style="color: #ccc;"></i><input type="text" name="preguntas[${indexPregunta}][opciones][${opcionIndex}][texto_opcion]" placeholder="Opción ${opcionIndex + 1}" required><button type="button" class="btn-delete-opcion" title="Eliminar Opción">&times;</button></div>`;
            container.append(opcionHtml);
            container.find('.opcion-item:last-child input').focus();
        }

        function agregarPreguntaConDatos(pregunta) {
            const index = preguntaIndex++;
             const tipoOptions = [
                {value: 'opcion_multiple', text: 'Opción Múltiple'},
                {value: 'seleccion_multiple', text: 'Selección Múltiple'},
                {value: 'abierta', text: 'Respuesta Corta'},
                {value: 'si_no', text: 'Verdadero / Falso'},
                {value: 'escala', text: 'Escala (1-5)'}
            ].map(tipo => {
                return `<option value="${tipo.value}" ${pregunta.tipo_pregunta === tipo.value ? 'selected' : ''}>${tipo.text}</option>`;
            }).join('');
            
            const textoPreguntaEscapado = (pregunta.texto_pregunta || '');
            
            const preguntaHtml = `<div class="pregunta-block" data-index="${index}"><div class="pregunta-header"><input type="text" name="preguntas[${index}][texto_pregunta]" placeholder="Pregunta sin título" value="" required><select name="preguntas[${index}][tipo_pregunta]" class="tipo-pregunta-selector">${tipoOptions}</select></div><div class="opciones-container"></div><div class="pregunta-footer"><button type="button" class="btn-delete-pregunta" title="Eliminar Pregunta"><i class="fa-solid fa-trash-alt"></i></button></div></div>`;
            
            $('#preguntas-container').append(preguntaHtml);
            
            // Asignar el valor usando .val()
            $(`.pregunta-block[data-index="${index}"] input[name*="[texto_pregunta]"]`).val(textoPreguntaEscapado);

            const $containerOpciones = $(`.pregunta-block[data-index="${index}"] .opciones-container`);
            
            // ✅ CORRECCIÓN: El trigger('change') estaba borrando las opciones.
            // Lo quitamos de aquí y movemos la lógica del 'change' a una función separada.
            
            if (pregunta.opciones && pregunta.opciones.length > 0) {
                if (pregunta.tipo_pregunta === 'opcion_multiple' || pregunta.tipo_pregunta === 'seleccion_multiple') {
                    pregunta.opciones.forEach((opcion, opIndex) => { agregarOpcionConDatos($containerOpciones, index, opIndex, opcion.texto_opcion, pregunta.tipo_pregunta); });
                    $containerOpciones.append(`<button type="button" class="btn-add-opcion"><i class="fa-solid fa-plus"></i> Añadir opción</button>`);
                }
            } else if (pregunta.tipo_pregunta === 'opcion_multiple' || pregunta.tipo_pregunta === 'seleccion_multiple') {
                agregarOpcion($containerOpciones, index, 0); 
                $containerOpciones.append(`<button type="button" class="btn-add-opcion"><i class="fa-solid fa-plus"></i> Añadir opción</button>`);
            }
             
            // ✅ Llamamos a la lógica que actualiza la UI (sin borrar)
            // Esta función NO está definida en tu script, la añado abajo
        }
         function agregarOpcion(container, indexPregunta, opcionIndex) {
            const tipoPregunta = container.closest('.pregunta-block').find('.tipo-pregunta-selector').val();
            const iconClass = (tipoPregunta === 'seleccion_multiple') ? 'far fa-square' : 'far fa-circle';
            // ✅ Corregido: ${index} -> ${indexPregunta}
            const opcionHtml = `<div class="opcion-item"><i class="${iconClass}" style="color: #ccc;"></i><input type="text" name="preguntas[${indexPregunta}][opciones][${opcionIndex}][texto_opcion]" placeholder="Opción ${opcionIndex + 1}" required><button type="button" class="btn-delete-opcion" title="Eliminar Opción">&times;</button></div>`;
            container.append(opcionHtml);
            container.find('.opcion-item:last-child input').focus();
        }

        function agregarOpcionConDatos(container, indexPregunta, opcionIndex, textoOpcion, tipoPregunta) {
            const iconClass = (tipoPregunta === 'seleccion_multiple') ? 'far fa-square' : 'far fa-circle';
            const textoOpcionEscapado = (textoOpcion || '');
            const opcionHtml = `<div class="opcion-item"><i class="${iconClass}" style="color: #ccc;"></i><input type="text" name="preguntas[${indexPregunta}][opciones][${opcionIndex}][texto_opcion]" placeholder="Opción ${opcionIndex + 1}" value="" required><button type="button" class="btn-delete-opcion" title="Eliminar Opción">&times;</button></div>`;
            
            container.append(opcionHtml);
            
            // Asignar el valor usando .val()
            container.find('.opcion-item:last-child input').val(textoOpcionEscapado);
        }

        // --- Manejadores de Eventos ---
        $(document).ready(function() {
            cargarEstadisticas(); // Cargar vista inicial

            // --- Navegación Principal ---
            $('#btn-tab-estadisticas').on('click', (e) => { e.preventDefault(); cargarEstadisticas(); });
            $('#btn-tab-gestion-usuarios').on('click', (e) => { e.preventDefault(); cargarGestionUsuarios(); });
            $('#btn-tab-gestion-encuestas').on('click', (e) => { e.preventDefault(); cargarGestionEncuestas(); });
            $('#btn-tab-crear').on('click', (e) => { e.preventDefault(); cargarFormCrearAdmin(); }); 
            $('.header-logo').on('click', (e) => { e.preventDefault(); cargarEstadisticas(); });
            
            $('#back-button-container').on('click', '#btn-back-to-list', function(e) {
                e.preventDefault();
                cargarGestionEncuestas(); 
            });

            // --- GESTIÓN DE USUARIOS ---
            $('#dashboard-content-container').on('click', '#btn-toggle-forms', function() { $(this).toggleClass('open'); $('#form-accordion-content').slideToggle(); });
            $('#dashboard-content-container').on('click', '.inner-tab-link', function(e) { e.preventDefault(); const tabId = $(this).data('tab'); $(this).siblings().removeClass('active'); $(this).closest('.inner-tabs-container').find('.inner-tab-pane').removeClass('active'); $(this).addClass('active'); $(`#tab-${tabId}`).addClass('active'); });
            $('#dashboard-content-container').on('click', '.user-tab-link', function(e) { e.preventDefault(); const tabId = $(this).data('tab'); $(this).siblings().removeClass('active'); $(this).closest('.user-list-container').find('.user-list-pane').removeClass('active'); $(this).addClass('active'); $(`#tab-${tabId}`).addClass('active'); });
            $('#dashboard-content-container').on('keyup', '#admin-user-search', function() { const query = $(this).val().toLowerCase(); const $activePane = $('.user-list-pane.active'); const $tbody = $activePane.find('tbody'); const $rowsToFilter = $tbody.find('tr:not(.no-data-row)'); $tbody.find('.filter-no-results').remove(); let visibleCount = 0; $rowsToFilter.each(function() { const rowText = $(this).text().toLowerCase(); if (rowText.includes(query)) { $(this).show(); visibleCount++; } else { $(this).hide(); } }); const $noDataRow = $tbody.find('.no-data-row'); if (visibleCount === 0 && $rowsToFilter.length > 0) { $noDataRow.hide(); $tbody.append('<tr class="filter-no-results"><td colspan="4">No se encontraron usuarios que coincidan.</td></tr>'); } else if ($rowsToFilter.length === 0) { $noDataRow.show(); } else { $noDataRow.hide(); } });
            $('#dashboard-content-container').on('submit', '#form-crear-encuestador', function(e) {
                e.preventDefault();
                const $button = $(this).find('.btn-crear-encuestador'); $button.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Creando...');
                const datos = { nombre: $('#admin-nombre-enc').val().trim(), apellido: $('#admin-apellido-enc').val().trim(), email: $('#admin-email-enc').val().trim(), carrera: $('#admin-carrera-enc').val(), asignatura: $('#admin-asignatura-enc').val().trim(), contrasena: $('#admin-contrasena-enc').val() };
                const specialCharRegex = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]+/;
                if (datos.contrasena.length < 8 || !datos.contrasena.toLowerCase().endsWith('al') || !specialCharRegex.test(datos.contrasena)) { Swal.fire('Error', 'La contraseña no cumple los requisitos (8+ carac, 1 especial, termina en "AL").', 'error'); $button.prop('disabled', false).html('<i class="fa-solid fa-user-plus"></i> Crear Encuestador'); return; }
                 if (!datos.email.toLowerCase().endsWith('@tecmerida.com')) { Swal.fire('Error', 'El correo debe ser @tecmerida.com.', 'error'); $button.prop('disabled', false).html('<i class="fa-solid fa-user-plus"></i> Crear Encuestador'); return; }
                $.ajax({
                    url: '../api/adminCrearEncuestador.php', method: 'POST', contentType: 'application/json', data: JSON.stringify(datos),
                    success: function(response) { if (response.success) { Swal.fire('¡Éxito!', 'Encuestador registrado.', 'success'); $('#form-crear-encuestador')[0].reset(); cargarTablaEncuestadores(); } else { Swal.fire('Error', response.mensaje || 'No se pudo registrar.', 'error'); } $button.prop('disabled', false).html('<i class="fa-solid fa-user-plus"></i> Crear Encuestador'); },
                    error: function(jqXHR) { let errorMsg = 'Error de conexión.'; if (jqXHR.responseJSON && jqXHR.responseJSON.mensaje) { errorMsg = jqXHR.responseJSON.mensaje; } Swal.fire('Error', errorMsg, 'error'); $button.prop('disabled', false).html('<i class="fa-solid fa-user-plus"></i> Crear Encuestador'); }
                });
            });
            $('#dashboard-content-container').on('submit', '#form-crear-alumno', function(e) {
                e.preventDefault();
                const $button = $(this).find('.btn-crear-alumno'); $button.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Creando...');
                const datos = { nombre: $('#admin-nombre-alu').val().trim(), apellido: $('#admin-apellido-alu').val().trim(), email: $('#admin-email-alu').val().trim(), carrera: $('#admin-carrera-alu').val(), genero: $('#admin-genero-alu').val(), contrasena: $('#admin-contrasena-alu').val(), confirmarContrasena: $('#admin-confirmar-alu').val() };
                if (datos.contrasena !== datos.confirmarContrasena) { Swal.fire('Error', 'Las contraseñas no coinciden.', 'error'); $button.prop('disabled', false).html('<i class="fa-solid fa-user-plus"></i> Crear Alumno'); return; }
                const specialCharRegex = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]+/;
                if (datos.contrasena.length < 8 || !datos.contrasena.toLowerCase().endsWith('al') || !specialCharRegex.test(datos.contrasena)) { Swal.fire('Error', 'La contraseña no cumple los requisitos (8+ carac, 1 especial, termina en "AL").', 'error'); $button.prop('disabled', false).html('<i class="fa-solid fa-user-plus"></i> Crear Alumno'); return; }
                if (!datos.genero || datos.genero === "") { Swal.fire('Error', 'Debes seleccionar un género.', 'error'); $button.prop('disabled', false).html('<i class="fa-solid fa-user-plus"></i> Crear Alumno'); return; }
                if (!datos.carrera || datos.carrera === "") { Swal.fire('Error', 'Debes seleccionar una carrera.', 'error'); $button.prop('disabled', false).html('<i class="fa-solid fa-user-plus"></i> Crear Alumno'); return; }
                $.ajax({
                    url: '../api/registrarAlumno.php', method: 'POST', contentType: 'application/json', data: JSON.stringify(datos),
                    success: function(response) { if (response.success) { Swal.fire('¡Éxito!', 'Alumno registrado con éxito.', 'success'); $('#form-crear-alumno')[0].reset(); cargarTablaAlumnos(); } else { Swal.fire('Error', response.mensaje || 'No se pudo registrar al alumno.', 'error'); } $button.prop('disabled', false).html('<i class="fa-solid fa-user-plus"></i> Crear Alumno'); },
                    error: function(jqXHR) { let errorMsg = 'Error de conexión.'; if (jqXHR.responseJSON && jqXHR.responseJSON.mensaje) { errorMsg = jqXHR.responseJSON.mensaje; } else if (jqXHR.status === 409) { errorMsg = "El correo electrónico ya está registrado."; } Swal.fire('Error', errorMsg, 'error'); $button.prop('disabled', false).html('<i class="fa-solid fa-user-plus"></i> Crear Alumno'); }
                });
            });
            $('#dashboard-content-container').on('click', '.btn-delete', function() {
                const idUsuario = $(this).data('id'); const $fila = $(this).closest('tr');
                Swal.fire({ title: '¿Estás seguro?', text: "Esta acción no se puede revertir. Se eliminará al usuario.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d', confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
                }).then((result) => { if (result.isConfirmed) { $.ajax({ url: '../api/adminEliminarUsuario.php', method: 'POST', contentType: 'application/json', data: JSON.stringify({ id_usuario: idUsuario }), success: function(res) { if (res.success) { Toast.fire({icon:'success', title: 'Usuario eliminado.'}); $fila.fadeOut(500, function() { $(this).remove(); }); } else { Swal.fire('Error', res.mensaje, 'error'); } }, error: function(jqXHR) { const msg = jqXHR.responseJSON?.mensaje || 'No se pudo conectar con el servidor.'; Swal.fire('Error', msg, 'error'); } }); } });
            });
            $('#dashboard-content-container').on('click', '.btn-edit', function() {
                const idUsuario = $(this).data('id'); const rol = $(this).data('rol');
                $.ajax({
                    url: `../api/adminObtenerUsuario.php?id_usuario=${idUsuario}`, method: 'GET', dataType: 'json',
                    success: function(res) {
                        if (!res.success) { Swal.fire('Error', res.mensaje, 'error'); return; }
                        const user = res.usuario; let formHtml = '<form id="form-edit-usuario" class="swal-edit-form">';
                        formHtml += `<div class="form-group"><label>Nombre(s)</label><input type="text" id="swal-nombre" value="${user.nombre}" required></div><div class="form-group"><label>Apellido(s)</label><input type="text" id="swal-apellido" value="${user.apellido}" required></div><div class="form-group"><label>Email</label><input type="email" id="swal-email" value="${user.email}" required></div>`;
                        if (rol === 'alumno') {
                            formHtml += `<div class="form-group"><label>Carrera</label><select id="swal-carrera" required><option value="ingenieria-sistemas" ${user.carrera === 'ingenieria-sistemas' ? 'selected' : ''}>Ingeniería en Sistemas</option><option value="ingenieria-civil" ${user.carrera === 'ingenieria-civil' ? 'selected' : ''}>Ingeniería Civil</option><option value="medicina" ${user.carrera === 'medicina' ? 'selected' : ''}>Medicina</option><option value="derecho" ${user.carrera === 'derecho' ? 'selected' : ''}>Derecho</option><option value="administracion" ${user.carrera === 'administracion' ? 'selected' : ''}>Administración</option><option value="contabilidad" ${user.carrera === 'contabilidad' ? 'selected' : ''}>Contabilidad</option><option value="psicologia" ${user.carrera === 'psicologia' ? 'selected' : ''}>Psicología</option><option value="arquitectura" ${user.carrera === 'arquitectura' ? 'selected' : ''}>Arquitectura</option></select></div>`;
                            formHtml += `<div class="form-group"><label>Género</label><select id="swal-genero" required><option value="hombre" ${user.genero === 'hombre' ? 'selected' : ''}>Hombre</option><option value="mujer" ${user.genero === 'mujer' ? 'selected' : ''}>Mujer</option><option value="otro" ${user.genero === 'otro' ? 'selected' : ''}>Otro</option></select></div>`;
                        } else if (rol === 'encuestador') { formHtml += `<div class="form-group"><label>Asignatura</label><input type="text" id="swal-asignatura" value="${user.asignatura || ''}" required></div>`; }
                        formHtml += '</form>';
                        Swal.fire({
                            title: `Editar Usuario (Rol: ${rol})`, html: formHtml, showCancelButton: true, confirmButtonText: 'Guardar Cambios', cancelButtonText: 'Cancelar', showLoaderOnConfirm: true,
                            preConfirm: () => {
                                const datosUpdate = { id_usuario: idUsuario, nombre: $('#swal-nombre').val(), apellido: $('#swal-apellido').val(), email: $('#swal-email').val() };
                                if (rol === 'alumno') { datosUpdate.carrera = $('#swal-carrera').val(); datosUpdate.genero = $('#swal-genero').val(); }
                                else if (rol === 'encuestador') { datosUpdate.asignatura = $('#swal-asignatura').val(); }
                                return $.ajax({ url: '../api/adminEditarUsuario.php', method: 'POST', contentType: 'application/json', data: JSON.stringify(datosUpdate) }).fail(function(jqXHR) { const msg = jqXHR.responseJSON?.mensaje || 'Error al guardar.'; Swal.showValidationMessage(msg); });
                            }
                        }).then((result) => { if (result.isConfirmed && result.value.success) { Toast.fire({icon: 'success', title: 'Usuario actualizado.'}); cargarTablaEncuestadores(); cargarTablaAlumnos(); } else if (result.isConfirmed) { Swal.fire('Error', result.value.mensaje || 'No se pudo actualizar.', 'error'); } });
                    },
                    error: () => { Swal.fire('Error', 'No se pudieron cargar los datos del usuario.', 'error'); }
                });
            });
            

            // --- GESTIÓN DE ENCUESTAS (ADMIN) ---
            
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
                                        $panel.append(generarHtmlEncuestaItem(encuesta));
                                    });
                                } else { $panel.html('<div class="panel-loading">Este encuestador no tiene encuestas.</div>'); }
                            },
                            error: function() { $panel.html('<div class="panel-loading" style="color:red;">Error al cargar encuestas.</div>'); }
                        });
                    }
                }
            });
            
            // Clic en "Ver Resultados"
            $('#dashboard-content-container').on('click', '.btn-resultados, .admin-ver-resultados', function(e) {
                e.stopPropagation();
                const idEncuesta = $(this).data('id');
                const tituloEncuesta = $(this).data('titulo');
                cargarResultadosAdmin(idEncuesta, tituloEncuesta); 
            });
            
            // Clic en Pestañas de Resultados
            $('#dashboard-content-container').on('click', '.tab-buttons .tab-button-res', function(e) {
                e.preventDefault(); const tabId = $(this).data('tab');
                $(this).closest('.tab-buttons').find('.tab-button-res').removeClass('active');
                $(this).closest('.tabs-container-resultados').find('.tab-pane-res').removeClass('active');
                $(this).addClass('active'); $(`#tab-${tabId}`).addClass('active');
            });
            
            // Clic en Link de Participante
            $('#dashboard-content-container').on('click', '.participante-link.admin-ver-respuestas', function(e) {
                e.preventDefault();
                const idEncuesta = $(this).data('id-encuesta'); const idAlumno = $(this).data('id-alumno'); const nombreAlumno = $(this).data('nombre-alumno');
                mostrarRespuestasAlumnoAdmin(idEncuesta, idAlumno, nombreAlumno);
            });

            // --- FORM BUILDER (ADMIN) ---
            $('#dashboard-content-container').on('click', '#btn-add-pregunta', agregarPregunta);
            $('#dashboard-content-container').on('click', '.btn-delete-pregunta', function() { $(this).closest('.pregunta-block').remove(); });
            $('#dashboard-content-container').on('change', '.tipo-pregunta-selector', function() {
                const tipo = $(this).val(); const $pb = $(this).closest('.pregunta-block'); const $oc = $pb.find('.opciones-container'); const ip = $pb.data('index'); $oc.empty();
                if (tipo === 'opcion_multiple' || tipo === 'seleccion_multiple') { agregarOpcion($oc, ip, 0); $oc.append(`<button type="button" class="btn-add-opcion"><i class="fa-solid fa-plus"></i> Añadir opción</button>`); }
                else if (tipo === 'si_no') { $oc.html(`<div class="opcion-item"><i class="far fa-circle" style="color: #ccc;"></i> <input type="text" value="Verdadero" disabled></div><div class="opcion-item"><i class="far fa-circle" style="color: #ccc;"></i> <input type="text" value="Falso" disabled></div>`); }
                else if (tipo === 'escala') { $oc.html(`<div style="display: flex; justify-content: space-between; padding: 0 10px;"><span>1</span><span>2</span><span>3</span><span>4</span><span>5</span></div><div style="display: flex; justify-content: space-between; padding: 0 10px; font-size: 0.8em; color: #666;"><span>(Mínimo)</span><span></span><span></span><span></span><span>(Máximo)</span></div>`); }
            });
            $('#dashboard-content-container').on('click', '.btn-add-opcion', function() { const $c = $(this).closest('.opciones-container'); const ip = $(this).closest('.pregunta-block').data('index'); const oi = $c.find('.opcion-item').length; agregarOpcion($c, ip, oi); $(this).appendTo($c); });
            $('#dashboard-content-container').on('click', '.btn-delete-opcion', function() { $(this).closest('.opcion-item').remove(); });

            // Submit del Formulario Crear Encuesta
            $('#dashboard-content-container').on('submit', '#form-crear-encuesta', function(e) {
                e.preventDefault(); const datosEncuesta = { titulo: $('#titulo').val().trim(), descripcion: $('#descripcion').val().trim(), visibilidad: $('#visibilidad').val(), estado: $('#estado').val(), preguntas: [] };
                if (!datosEncuesta.titulo) { Swal.fire('Error', 'Título obligatorio.', 'error'); $('#titulo').focus(); return; }
                $('.pregunta-block').each(function(index) { const block = $(this); const textoPregunta = block.find('input[name*="[texto_pregunta]"]').val().trim(); const tipoPregunta = block.find('select[name*="[tipo_pregunta]"]').val(); if (!textoPregunta) { console.warn(`Pregunta ${index+1} ignorada.`); return; } const preguntaData = { texto_pregunta: textoPregunta, tipo_pregunta: tipoPregunta, orden: index + 1, opciones: [] }; block.find('.opcion-item input[type="text"]').each(function() { const textoOpcion = $(this).val().trim(); if (!$(this).prop('disabled') && textoOpcion !== "") { preguntaData.opciones.push({ texto_opcion: textoOpcion }); } }); if (tipoPregunta === 'si_no') { preguntaData.opciones.push({texto_opcion:'Verdadero'}); preguntaData.opciones.push({texto_opcion:'Falso'}); } else if (tipoPregunta === 'escala') { preguntaData.opciones.push({texto_opcion:'1', valor_escala: 1}); preguntaData.opciones.push({texto_opcion:'2', valor_escala: 2}); preguntaData.opciones.push({texto_opcion:'3', valor_escala: 3}); preguntaData.opciones.push({texto_opcion:'4', valor_escala: 4}); preguntaData.opciones.push({texto_opcion:'5', valor_escala: 5}); } datosEncuesta.preguntas.push(preguntaData); });
                if (datosEncuesta.preguntas.length === 0) { Swal.fire('Error', 'Añade al menos una pregunta válida.', 'error'); return; }
                const saveBtn = $('#publish-button-placeholder .btn-publish'); saveBtn.prop('disabled',true).html('<i class="fa-solid fa-spinner fa-spin"></i> Guardando...');
                
                $.ajax({ url: '../api/crearEncuesta.php', method: 'POST', contentType: 'application/json', data: JSON.stringify(datosEncuesta),
                    success: function(res) { if(res.success) { Swal.fire('¡Guardado!', res.mensaje||'Ok.', 'success'); cargarGestionEncuestas(); } else { Swal.fire('Error al guardar', res.mensaje||'Ocurrió un error.', 'error'); saveBtn.prop('disabled', false).html('<i class="fa-solid fa-save"></i> Guardar Encuesta'); } },
                    error: function(jqXHR) { console.error("Error AJAX crear:", jqXHR.responseText); let msg='Error conexión.'; if(jqXHR.responseJSON&&jqXHR.responseJSON.mensaje){msg=jqXHR.responseJSON.mensaje;}else if(jqXHR.status===500){msg='Error servidor.';} Swal.fire('Error', msg, 'error'); saveBtn.prop('disabled', false).html('<i class="fa-solid fa-save"></i> Guardar Encuesta'); }
                });
            });

            // --- ✅ NUEVOS MANEJADORES DE ENCUESTA (copiados de dashboard_general) ---
            
            // Clic en "Editar" (en cualquier lista de encuestas)
            $('#dashboard-content-container').on('click', '.btn-editar', function(e) { 
                e.stopPropagation();
                cargarFormEditarAdmin($(this).data('id')); // Llama a la función de edición
            });
            
            $('#dashboard-content-container').on('click', '.btn-publish-lista', function(e) { 
                e.stopPropagation();
                const id = $(this).data('id'); 
                Swal.fire({ title: '¿Publicar?', text: "Los alumnos podrán ver esta encuesta. No podrás editarla después.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#28a745', confirmButtonText: 'Sí, Publicar', showLoaderOnConfirm: true, 
                    preConfirm: () => $.ajax({ url: '../api/actualizarEstadoEncuesta.php', method: 'POST', contentType:'application/json', data: JSON.stringify({id_encuesta: id, nuevo_estado:'publicada'})}).catch(e=>Swal.showValidationMessage(`Error: ${e.statusText||'?'}`)), 
                    allowOutsideClick:()=>!Swal.isLoading() 
                }).then((r) => { if(r.isConfirmed&&r.value&&r.value.success) { Toast.fire({icon:'success',title:'Encuesta publicada.'}); cargarGestionEncuestas(); } else if(r.isConfirmed) Swal.fire('Error', r.value.mensaje||'?', 'error'); }); 
            });
            
            // Clic en "Cerrar" / "Re-Publicar"
            $('#dashboard-content-container').on('click', '.btn-cerrar', function(e) { 
                e.stopPropagation();
                const id = $(this).data('id'); 
                const ne = $(this).data('nuevo-estado');
                const $b = $(this).prop('disabled',true); 
                $.ajax({ url: '../api/actualizarEstadoEncuesta.php', method: 'POST', contentType:'application/json', data: JSON.stringify({id_encuesta: id, nuevo_estado: ne}), 
                    success: (r)=>{ if(r.success) { Toast.fire({icon:'success',title:`Encuesta ${ne}.`}); cargarGestionEncuestas(); } else { Toast.fire({icon:'error',title:r.mensaje}); $b.prop('disabled',false); }}, 
                    error: ()=>{ Toast.fire({icon:'error',title:'Error conexión.'}); $b.prop('disabled',false); } 
                }); 
            });

            // Clic en "Eliminar" (ahora usa la API de encuestador)
            $('#dashboard-content-container').on('click', '.btn-eliminar', function(e) {
                e.stopPropagation(); 
                const idEncuesta = $(this).data('id');
                const $item = $(this).closest('.encuesta-item');
                Swal.fire({
                    title: '¿Eliminar esta encuesta?', text: "Esta acción es permanente y eliminará todas sus preguntas y respuestas.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d', confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '../api/adminEliminarEncuesta.php', // Usa la API del encuestador (permisos arreglados)
                            method: 'POST', contentType: 'application/json',
                            data: JSON.stringify({ id_encuesta: idEncuesta }),
                            success: function(res) {
                                if (res.success) { Toast.fire({icon:'success', title: 'Encuesta eliminada.'}); $item.fadeOut(500, function() { $(this).remove(); }); } 
                                else { Swal.fire('Error', res.mensaje, 'error'); }
                            },
                            error: function(jqXHR) { const msg = jqXHR.responseJSON?.mensaje || 'No se pudo conectar con el servidor.'; Swal.fire('Error', msg, 'error'); }
                        });
                    }
                });
            });

            // Submit del Formulario EDITAR Encuesta
            $('#dashboard-content-container').on('submit', '#form-editar-encuesta', function(e) {
                e.preventDefault();
                const datosActualizados = { id_encuesta: $(this).find('input[name="id_encuesta"]').val(), titulo: $('#titulo').val().trim(), descripcion: $('#descripcion').val().trim(), visibilidad: $('#visibilidad').val(), estado: 'borrador', preguntas: [] };
                if (!datosActualizados.titulo) { Swal.fire('Error', 'Título obligatorio.', 'error'); $('#titulo').focus(); return; }
                $('.pregunta-block').each(function(index) { const block = $(this); const textoPregunta = block.find('input[name*="[texto_pregunta]"]').val().trim(); const tipoPregunta = block.find('select[name*="[tipo_pregunta]"]').val(); if (!textoPregunta) return; const preguntaData = { texto_pregunta: textoPregunta, tipo_pregunta: tipoPregunta, orden: index + 1, opciones: [] }; block.find('.opcion-item input[type="text"]').each(function() { const textoOpcion = $(this).val().trim(); if (!$(this).prop('disabled') && textoOpcion !== "") { preguntaData.opciones.push({ texto_opcion: textoOpcion }); } }); if (tipoPregunta === 'si_no') { preguntaData.opciones.push({texto_opcion:'Verdadero'}); preguntaData.opciones.push({texto_opcion:'Falso'}); } else if (tipoPregunta === 'escala') { preguntaData.opciones.push({texto_opcion:'1', valor_escala: 1}); preguntaData.opciones.push({texto_opcion:'2', valor_escala: 2}); preguntaData.opciones.push({texto_opcion:'3', valor_escala: 3}); preguntaData.opciones.push({texto_opcion:'4', valor_escala: 4}); preguntaData.opciones.push({texto_opcion:'5', valor_escala: 5}); } datosActualizados.preguntas.push(preguntaData); });
                if (datosActualizados.preguntas.length === 0) { Swal.fire('Error', 'Añade al menos una pregunta válida.', 'error'); return; }
                
                const updateBtn = $('#publish-button-placeholder .btn-publish'); 
                updateBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Actualizando...'); 
                console.log("Enviando actualización:", JSON.stringify(datosActualizados));
                
                $.ajax({ 
                    url: '../api/actualizarEncuestaBorrador.php', // ¡API AHORA EXISTE!
                    method: 'POST', 
                    contentType: 'application/json', 
                    data: JSON.stringify(datosActualizados), 
                    success: function(res) { 
                        if (res.success) { 
                            Swal.fire('¡Actualizado!', 'Borrador guardado.', 'success'); 
                            cargarGestionEncuestas(); 
                        } else { 
                            Swal.fire('Error', res.mensaje, 'error'); 
                            updateBtn.prop('disabled', false).html('<i class="fa-solid fa-save"></i> Actualizar Borrador'); 
                        } 
                    }, 
                    error: function() { 
                        Swal.fire('Error', 'Conexión fallida.', 'error'); 
                        updateBtn.prop('disabled', false).html('<i class="fa-solid fa-save"></i> Actualizar Borrador'); 
                    } 
                });
            });

        }); // Fin $(document).ready
    </script>
</body>
</html>