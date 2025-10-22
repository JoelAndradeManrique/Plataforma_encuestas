<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$usuario = $_SESSION['usuario'];
$nombre = htmlspecialchars($usuario['nombre']);
$rol = $usuario['rol'];

$mensaje = "";
if ($rol === 'alumno') {
    $mensaje = "Hola Alumno " . $nombre;
} else if ($rol === 'encuestador') {
    $mensaje = "Hola Encuestador " . $nombre;
} else if ($rol === 'admin') {
    header("Location: dashboard_admin.php");
    exit();
} else {
    header("Location: logout.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../css/style.css"> 
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body { display: grid; place-items: center; min-height: 90vh; }
        .dashboard-content { text-align: center; }
        .dashboard-content h1 { font-size: 2.5rem; }
        .dashboard-content a { font-size: 1.2rem; text-decoration: none; color: #007bff; }
        
        /* Estilos para el formulario dentro del modal */
        #swal-hint { font-size: 0.8em; color: #666; margin-top: 10px; }
        .swal2-input { margin-bottom: 5px; }
        .swal2-label { margin-bottom: 5px; font-size: 1em; }
    </style>
</head>
<body>
    <div class="dashboard-content">
        <h1><?php echo $mensaje; ?></h1>
        <a href="logout.php">Cerrar Sesión</a>
    </div>

    <script>
    $(document).ready(function() {
        
        // Este bloque PHP solo imprime el script si password_temporal es TRUE
        <?php if (isset($usuario['password_temporal']) && $usuario['password_temporal'] == TRUE): ?>
        
        const specialCharRegex = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]+/;

        Swal.fire({
            title: 'Cambio de Contraseña Requerido',
            text: 'Por seguridad, debes establecer una nueva contraseña.',
            icon: 'warning',
            allowOutsideClick: false, // No puede cerrarlo
            allowEscapeKey: false,    // No puede usar ESC
            html: `
                <div style="text-align: left; margin-top: 15px;">
                    <label for="swal-pass1" class="swal2-label">Nueva Contraseña</label>
                    <input type="password" id="swal-pass1" class="swal2-input" placeholder="Nueva contraseña">
                    
                    <label for="swal-pass2" class="swal2-label" style="margin-top: 10px;">Confirmar Contraseña</label>
                    <input type="password" id="swal-pass2" class="swal2-input" placeholder="Confirmar contraseña">
                    
                    <div id="swal-hint">
                        *Mínimo 8 carac, 1 especial (ej. !@#$) y terminar con AL
                    </div>
                </div>
            `,
            confirmButtonText: 'Guardar Contraseña',
            showLoaderOnConfirm: true, // Muestra un 'loading'
            
            // Validación antes de enviar
            preConfirm: () => {
                const pass1 = document.getElementById('swal-pass1').value;
                const pass2 = document.getElementById('swal-pass2').value;

                // Validar
                if (!pass1 || !pass2) {
                    Swal.showValidationMessage('Ambos campos son obligatorios.');
                    return false; // Detiene el preConfirm
                }
                if (pass1 !== pass2) {
                    Swal.showValidationMessage('Las contraseñas no coinciden.');
                    return false;
                }
                
                const isLengthOk = pass1.length >= 8;
                const hasSpecialChar = specialCharRegex.test(pass1);
                const isAlOk = pass1.toLowerCase().endsWith('al');

                if (!isLengthOk || !hasSpecialChar || !isAlOk) {
                     Swal.showValidationMessage('La contraseña no cumple los requisitos.');
                     return false;
                }
                
                // Si todo está OK, devolvemos los datos para la llamada AJAX
                return { nueva_contrasena: pass1, confirmar_contrasena: pass2 };
            }
        }).then((result) => {
            // 'result.value' tiene los datos de preConfirm
            if (result.isConfirmed && result.value) {
                
                // Hacemos la llamada a la NUEVA API
                $.ajax({
                    url: '../api/cambiarContrasena.php', // La nueva API
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(result.value),
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: 'Tu contraseña ha sido actualizada.',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            // Muestra el error pero deja el modal abierto
                            Swal.showValidationMessage(response.mensaje);
                        }
                    },
                    error: function() {
                         Swal.showValidationMessage('Error de conexión.');
                    }
                });
            }
        });
        
        <?php endif; ?>
    });
    </script>
</body>
</html>