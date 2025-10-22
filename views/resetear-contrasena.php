<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma de Encuestas Estudiantiles - Restablecer Contraseña</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .password-wrapper { position: relative; display: flex; align-items: center; }
        .password-wrapper input { padding-right: 40px !important; }
        .toggle-password { position: absolute; right: 15px; cursor: pointer; color: #6b7c80; }
        .password-hint { font-size: 0.85em; color: #ef4444; margin-top: -15px; margin-bottom: 15px; text-align: left; }
        .password-hint.valid { color: #16a34a; }
        
        /* ❌ ELIMINADO: Ya no necesitamos los estilos para #mensaje */
    </style>
</head>
<body>
    <div class="container">
        <div class="recovery-card"> 
            <div class="icon-container">
                <div class="clipboard-icon-recovery">
                </div>
            </div>
            <h1 class="title">Restablecer Contraseña</h1>
            <p class="instruction-text">
                Ingresa tu nueva contraseña. Asegúrate de que cumpla los requisitos.
            </p>
            
            <form id="resetForm" class="recovery-form">
                <div class="form-group">
                    <label for="nueva_contrasena">Nueva Contraseña</label>
                    <div class="password-hint">*Mínimo 8 caracteres y con terminación AL</div>
                    <div class="password-wrapper">
                        <input type="password" id="nueva_contrasena" placeholder="Ingresa tu nueva contraseña" required>
                        <i class="fa-solid fa-eye toggle-password"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirmar_contrasena">Confirmar Nueva Contraseña</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirmar_contrasena" placeholder="Confirma tu contraseña" required>
                        <i class="fa-solid fa-eye toggle-password"></i>
                    </div>
                </div>
                <button type="submit" class="verify-btn">Guardar Contraseña</button>
            </form>
        </div>
    </div>

<script>
$(document).ready(function() {

    // Configuración del "Toast" (notificación pequeña)
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
        }
    });
    
    // Lógica para el ojo de contraseña (sin cambios)
    $('.toggle-password').on('click', function() {
        $(this).toggleClass('fa-eye fa-eye-slash');
        const input = $(this).prev('input');
        input.attr('type', input.attr('type') === 'password' ? 'text' : 'password');
    });

    // Validador en vivo (sin cambios)
    $('#nueva_contrasena').on('keyup', function() {
        const password = $(this).val();
        const hint = $(this).closest('.form-group').find('.password-hint');
        const isLengthOk = password.length >= 8;
        const isAlOk = password.endsWith('AL');
        if (isLengthOk && isAlOk) {
            hint.text('Contraseña válida');
            hint.addClass('valid');
        } else {
            hint.text('*Mínimo 8 caracteres y con terminación AL');
            hint.removeClass('valid');
        }
    });

    // 1. Extraer el token de la URL
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');

    if (!token) {
        // Error grave: Mostramos un MODAL (ventana grande)
        Swal.fire({
            icon: 'error',
            title: 'Error de Token',
            text: 'Token inválido o no encontrado. Solicita un nuevo enlace.',
            allowOutsideClick: false // Impide cerrar la alerta haciendo clic fuera
        });
        $('#resetForm').hide(); // Ocultar formulario
    }

    // 2. Manejar el envío del formulario
    $('#resetForm').on('submit', function(e) {
        e.preventDefault();
        const submitBtn = $('.verify-btn');
        submitBtn.text('Guardando...').prop('disabled', true);

        const nuevaContrasena = $('#nueva_contrasena').val();
        const confirmarContrasena = $('#confirmar_contrasena').val();

        // Validaciones (ahora usan Toasts)
        if (nuevaContrasena !== confirmarContrasena) {
            Toast.fire({ icon: 'error', title: 'Las contraseñas no coinciden.' });
            submitBtn.text('Guardar Contraseña').prop('disabled', false);
            return;
        }
        if (nuevaContrasena.length < 8 || !nuevaContrasena.endsWith('AL')) {
            Toast.fire({ icon: 'error', title: 'La contraseña debe tener al menos 8 caracteres y terminar con "AL".' });
            submitBtn.text('Guardar Contraseña').prop('disabled', false);
            return;
        }

        // Llamada a la API para resetear
        $.ajax({
            url: '../api/resetearContrasena.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                token: token,
                nueva_contrasena: nuevaContrasena,
                confirmar_contrasena: confirmarContrasena 
            }),
            success: function(response) {
                if (response.success) {
                    // Éxito: Mostramos MODAL y luego redirigimos
                    $('#resetForm').hide();
                    Swal.fire({
                        icon: 'success',
                        title: response.mensaje,
                        text: 'Redirigiendo al inicio de sesión...',
                        timer: 2500, // 2.5 segundos
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'login.php';
                    });
                } else {
                    // Error de la API (ej. token expirado)
                    Toast.fire({ icon: 'error', title: response.mensaje });
                    submitBtn.text('Guardar Contraseña').prop('disabled', false);
                }
            },
            error: function() {
                // Error de conexión
                Toast.fire({ icon: 'error', title: 'Error de conexión. Inténtalo de nuevo.' });
                submitBtn.text('Guardar Contraseña').prop('disabled', false);
            }
        });
    });
});
</script>
</body>
</html>