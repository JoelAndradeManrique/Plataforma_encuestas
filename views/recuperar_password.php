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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .resend-container { margin-top: 20px; text-align: center; }
        .resend-btn { background-color: #6c757d; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: not-allowed; opacity: 0.65; transition: background-color 0.3s ease, opacity 0.3s ease; }
        .resend-btn:enabled { background-color: #007bff; cursor: pointer; opacity: 1; }
        .resend-timer { display: block; margin-top: 5px; font-size: 0.9em; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="recovery-card">
            <div class="icon-container"><div class="clipboard-icon-recovery"></div></div>
            <h1 class="title">Recuperación de contraseña</h1>
            <p class="instruction-text">Escribe tu correo electrónico registrado para enviarte las instrucciones y poder cambiar la contraseña.</p>

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

            <div id="resend-section" style="display: none; margin-top: 20px; text-align: center;">
                <p style="color: green; font-weight: bold; margin-bottom: 15px;">
                    <i class="fa-solid fa-check-circle"></i> Se envió un correo de verificación. Sugerimos revisar la bandeja de SPAM o correos no deseados.
                </p>
                <div class="resend-container">
                    <button id="resend-btn" class="resend-btn" disabled>Reenviar correo</button>
                    <span id="resend-timer" class="resend-timer">Podrás reenviar en 20 segundos</span>
                </div>
                 <p style="margin-top: 15px; font-size: 0.9em;">
                    <a href="resetear-contrasena.php" class="register-text">Ya tengo mi código/enlace</a>
                 </p>
            </div>
        </div>
    </div>

<script>
$(document).ready(function() {
    const Toast = Swal.mixin({ /* ... Config Toast ... */ });
    let resendTimerInterval = null; let secondsLeft = 20;

    function startResendTimer() {
        secondsLeft = 20; const $resendBtn = $('#resend-btn'); const $timerSpan = $('#resend-timer');
        $resendBtn.prop('disabled', true); $timerSpan.text(`Podrás reenviar en ${secondsLeft} segundos`).show();
        if (resendTimerInterval) { clearInterval(resendTimerInterval); }
        resendTimerInterval = setInterval(() => { secondsLeft--; if (secondsLeft > 0) { $timerSpan.text(`Podrás reenviar en ${secondsLeft} segundos`); } else { clearInterval(resendTimerInterval); $timerSpan.hide(); $resendBtn.prop('disabled', false); } }, 1000);
    }

    $('#recoveryForm').on('submit', function(e) {
        e.preventDefault(); const email = $('#email').val().trim(); const submitBtn = $('.verify-btn');
        $('#resend-section').hide(); submitBtn.text('Verificando...').prop('disabled', true);
        if (!email) { Toast.fire({ icon: 'error', title: 'Ingresa tu correo.' }); submitBtn.text('Verificar correo').prop('disabled', false); return; }

        $.ajax({
            url: '../api/solicitarRecuperacion.php', method: 'POST', contentType: 'application/json', data: JSON.stringify({ email: email }),
            success: function(response) {
                // Independientemente del mensaje exacto (genérico o no), si success es true, mostramos la sección de reenvío
                if (response.success) {
                    $('#recoveryForm').hide(); $('#resend-section').show(); startResendTimer();
                     // Mostrar un toast con el mensaje específico de la API (ej. "Instrucciones enviadas...")
                     Toast.fire({ icon: 'success', title: response.mensaje || 'Solicitud procesada.' });
                } else {
                    Toast.fire({ icon: 'error', title: response.mensaje || 'Ocurrió un error.' }); submitBtn.text('Verificar correo').prop('disabled', false);
                }
            },
            error: function(jqXHR) {
                let errorMsg = 'Error de conexión.'; if(jqXHR.responseJSON && jqXHR.responseJSON.mensaje){ errorMsg = jqXHR.responseJSON.mensaje; } else if (jqXHR.status === 500) { errorMsg = 'Error interno del servidor.'; }
                Toast.fire({ icon: 'error', title: errorMsg }); submitBtn.text('Verificar correo').prop('disabled', false);
            }
        });
    });

    $('#resend-btn').on('click', function() {
        const email = $('#email').val().trim(); const $resendBtn = $(this);
        if (!email) { Toast.fire({ icon: 'error', title: 'No se encontró correo.' }); return; }
        $resendBtn.prop('disabled', true).text('Reenviando...');

         $.ajax({
            url: '../api/solicitarRecuperacion.php', method: 'POST', contentType: 'application/json', data: JSON.stringify({ email: email }),
            success: function(response) {
                if (response.success) { Toast.fire({ icon: 'success', title: response.mensaje || 'Correo reenviado.' }); startResendTimer(); }
                else { Toast.fire({ icon: 'error', title: response.mensaje || 'Error al reenviar.' }); $resendBtn.prop('disabled', false); }
                setTimeout(() => { if (!$resendBtn.prop('disabled')) { $resendBtn.text('Reenviar correo'); } }, 500);
            },
            error: function(jqXHR) { let errorMsg = 'Error de conexión.'; if(jqXHR.responseJSON && jqXHR.responseJSON.mensaje){ errorMsg = jqXHR.responseJSON.mensaje; } Toast.fire({ icon: 'error', title: errorMsg }); $resendBtn.prop('disabled', false).text('Reenviar correo'); }
         });
    });
});
</script>

</body>
</html>