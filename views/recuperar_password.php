<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma de Encuestas Estudiantiles - Recuperar Contraseña</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    </head>
<body>
    <div class="container">
        <div class="recovery-card">
            <div class="icon-container">
                <div class="clipboard-icon-recovery">
                </div>
            </div>
            <h1 class="title">Recuperación de contraseña</h1>
            
            <p class="instruction-text">
                Si olvidaste tu contraseña de acceso a tu cuenta, escribe aquí tu correo electrónico en la cual te registraste para enviar un enlace para que pueda cambiar la contraseña
            </p>
            
            <form id="recoveryForm" class="recovery-form">
                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" placeholder="Ingresa tu correo electrónico" required>
                </div>
                
                <button type="submit" class="verify-btn">Verificar correo</button>
                
                <div class="register-link" style="margin-top: 1rem;">
                    <span>¿Recordaste tu contraseña? </span>
                    <a href="login.php" class="register-text">Inicia Sesión</a>
                </div>
            </form>
        </div>
    </div>

<script>
$(document).ready(function() {
    
    // Configuración del "Toast" (notificación pequeña)
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end', // Esquina superior derecha
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
        }
    });

    $('#recoveryForm').on('submit', function(e) {
        e.preventDefault();
        const email = $('#email').val();
        const submitBtn = $('.verify-btn');

        submitBtn.text('Verificando...').prop('disabled', true);

        if (!email) {
            // Error de validación local
            Toast.fire({
                icon: 'error',
                title: 'Por favor, ingresa tu correo electrónico.'
            });
            submitBtn.text('Verificar correo').prop('disabled', false);
            return;
        }

        $.ajax({
            url: '../api/solicitarRecuperacion.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ email: email }),
            success: function(response) {
                if (response.success) {
                    // Éxito: Mostramos un MODAL (ventana grande) 
                    // para que el enlace sea fácil de clickear
                    Swal.fire({
                        icon: 'success',
                        title: 'Solicitud Procesada',
                        html: `Si tu correo está registrado, recibirás las instrucciones.<br><br>
                               <strong>Enlace simulado (para pruebas):</strong><br>
                               <a href="${response.simulacion_enlace}" target="_blank">Haz clic aquí para restablecer</a>`,
                        showConfirmButton: true
                    });
                    
                    submitBtn.text('Enlace Enviado').prop('disabled', true);
                } 
                // Nota: El 'else' no es necesario porque 
                // el backend (con el cambio que hicimos) 
                // devolverá un error 404, que es manejado por la función 'error' de abajo.
            },
            error: function(jqXHR) {
                // Error de conexión O error del backend (ej. 404 Correo no encontrado)
                let errorMsg = 'Error de conexión. Inténtalo de nuevo.';
                if(jqXHR.responseJSON && jqXHR.responseJSON.mensaje){
                    errorMsg = jqXHR.responseJSON.mensaje; // "No existe un registro con ese correo"
                }
                
                Toast.fire({
                    icon: 'error',
                    title: errorMsg
                });
                
                submitBtn.text('Verificar correo').prop('disabled', false);
            }
        });
    });
});
</script>
</body>
</html>
