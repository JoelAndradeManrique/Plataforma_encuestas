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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administrador</title>

    <link rel="stylesheet" href="../css/style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body { display: block; justify-content: normal; align-items: normal; padding: 0; background-color: #f4f7f6; font-family: Arial, sans-serif; margin: 0; }
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
        
        /* Estilos para el formulario de registro de encuestador */
        .admin-form-container {
            max-width: 600px; margin: 20px auto; background: #fff;
            padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .admin-form-container h2 { text-align: center; margin-bottom: 20px; }
        .admin-form-container .form-group { margin-bottom: 15px; }
        .admin-form-container .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .admin-form-container .form-group input,
        .admin-form-container .form-group select {
            width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;
            box-sizing: border-box; /* Importante */
        }
        .admin-form-container .btn-crear-encuestador {
            width: 100%; padding: 12px; background-color: #28a745; color: white;
            border: none; border-radius: 5px; cursor: pointer; font-size: 1.1rem;
            font-weight: 500;
        }
        .admin-form-container .btn-crear-encuestador:disabled { background-color: #aaa; }
        .password-hint { font-size: 0.85em; color: #666; margin-top: 5px;}

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
            
            const formHtml = `
                <div class="admin-form-container">
                    <h2>Registrar Nuevo Encuestador (Maestro)</h2>
                    <form id="form-crear-encuestador">
                        <div class="form-group">
                            <label for="admin-nombre">Nombres</label>
                            <input type="text" id="admin-nombre" required>
                        </div>
                        <div class="form-group">
                            <label for="admin-apellido">Apellidos</label>
                            <input type="text" id="admin-apellido" required>
                        </div>
                        <div class="form-group">
                            <label for="admin-email">Correo Electrónico</label>
                            <input type="email" id="admin-email" placeholder="ejemplo@tecmerida.com" required>
                        </div>
                        <div class="form-group">
                            <label for="admin-asignatura">Materia que Imparte (Asignatura)</label>
                            <input type="text" id="admin-asignatura" required>
                        </div>
                        <div class="form-group">
                            <label for="admin-contrasena">Contraseña Temporal</label>
                            <input type="password" id="admin-contrasena" required>
                            <div class="password-hint">Debe cumplir: 8+ carac, 1 especial, terminar en "AL"</div>
                        </div>
                        <button type="submit" class="btn-crear-encuestador">
                            <i class="fa-solid fa-user-plus"></i> Crear Encuestador
                        </button>
                    </form>
                </div>`;
            container.html(formHtml);
        }

        // 2. Cargar "Gestión de Encuestas" (Placeholder)
        function cargarGestionEncuestas() {
            activarTab('#btn-tab-gestion-encuestas');
            const container = $('#dashboard-content-container');
            container.html('<div id="loading"><p>Módulo de gestión de encuestas (para ver/editar/borrar encuestas de TODOS los encuestadores) en desarrollo.</p></div>');
            // (Aquí podríamos reutilizar la lógica de 'dashboard_general.php' pero llamando
            // a una API de admin que traiga TODAS las encuestas, no solo las propias)
        }

        // --- Manejadores de Eventos ---
        $(document).ready(function() {
            // Cargar vista inicial
            cargarGestionUsuarios();

            // Navegación principal del Admin
            $('#btn-tab-gestion-usuarios').on('click', (e) => { e.preventDefault(); cargarGestionUsuarios(); });
            $('#btn-tab-gestion-encuestas').on('click', (e) => { e.preventDefault(); cargarGestionEncuestas(); });
            $('.header-logo').on('click', (e) => { e.preventDefault(); cargarGestionUsuarios(); }); // Logo lleva a home

            // --- Submit: Crear Encuestador ---
            $('#dashboard-content-container').on('submit', '#form-crear-encuestador', function(e) {
                e.preventDefault();
                const $button = $(this).find('.btn-crear-encuestador');
                $button.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Creando...');

                const datos = {
                    nombre: $('#admin-nombre').val().trim(),
                    apellido: $('#admin-apellido').val().trim(),
                    email: $('#admin-email').val().trim(),
                    asignatura: $('#admin-asignatura').val().trim(),
                    contrasena: $('#admin-contrasena').val() // No trimear contraseña
                };

                // Validar contraseña (JS) antes de enviar
                const specialCharRegex = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]+/;
                if (datos.contrasena.length < 8 || !datos.contrasena.toLowerCase().endsWith('al') || !specialCharRegex.test(datos.contrasena)) {
                    Swal.fire('Error', 'La contraseña no cumple los requisitos (8+ carac, 1 especial, termina en "AL").', 'error');
                    $button.prop('disabled', false).html('<i class="fa-solid fa-user-plus"></i> Crear Encuestador');
                    return;
                }
                
                // Validar email (JS) antes de enviar
                 if (!datos.email.toLowerCase().endsWith('@tecmerida.com')) {
                    Swal.fire('Error', 'El correo debe ser del dominio @tecmerida.com.', 'error');
                    $button.prop('disabled', false).html('<i class="fa-solid fa-user-plus"></i> Crear Encuestador');
                    return;
                }

                // Llamar a la nueva API
                $.ajax({
                    url: '../api/adminCrearEncuestador.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(datos),
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('¡Éxito!', 'Encuestador registrado con éxito. Su contraseña es temporal.', 'success');
                            $('#form-crear-encuestador')[0].reset(); // Limpiar formulario
                        } else {
                            Swal.fire('Error', response.mensaje || 'No se pudo registrar al encuestador.', 'error');
                        }
                        $button.prop('disabled', false).html('<i class="fa-solid fa-user-plus"></i> Crear Encuestador');
                    },
                    error: function(jqXHR) {
                        let errorMsg = 'Error de conexión.';
                        if (jqXHR.responseJSON && jqXHR.responseJSON.mensaje) {
                            errorMsg = jqXHR.responseJSON.mensaje;
                        } else if (jqXHR.status === 403) {
                            errorMsg = 'Acceso denegado. Tu sesión de admin pudo expirar.';
                        } else if (jqXHR.status === 500) {
                             errorMsg = 'Error interno del servidor.';
                        }
                        Swal.fire('Error', errorMsg, 'error');
                        $button.prop('disabled', false).html('<i class="fa-solid fa-user-plus"></i> Crear Encuestador');
                    }
                });
            });

        }); // Fin $(document).ready
    </script>
</body>
</html>