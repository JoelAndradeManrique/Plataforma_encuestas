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
        #code-section label { font-weight: bold; margin-bottom: 5px; display: block; text-align: center;}
        #resetCode { letter-spacing: 1em; text-align: center; font-size: 1.5em; padding: 10px; width: 150px; margin: 0 auto 15px auto; display: block; border: 2px solid #ccc; border-radius: 6px; }
        #resetCode:focus { border-color: #007bff; outline: none;}
        #code-error { color: red; text-align: center; font-size: 0.9em; margin-top: -10px; margin-bottom: 15px; display: none;}
        .verify-code-btn { /* Estilo similar a verify-btn */ background-color: #4285f4; color: white; border: none; padding: 10px 15px; border-radius: 8px; font-size: 1em; cursor: pointer; transition: background-color 0.3s ease; display: block; margin: 0 auto; }
        .verify-code-btn:hover { background-color: #3367d6; }
        .verify-code-btn:disabled { background-color: #ccc; cursor: not-allowed; }
    </style>
</head>
<body>
    <div class="container">
        <div class="recovery-card">
            <div class="icon-container"><div class="clipboard-icon-recovery"></div></div>
            <h1 class="title">Restablecer Contraseña</h1>
            <p class="instruction-text" id="instruction-text">
            </p>

            <div id="code-section" style="display: none;">
                 <label for="resetCode">Ingresa el código de 4 dígitos:</label>
                 <input type="text" id="resetCode" name="code" maxlength="4" pattern="\d{4}" inputmode="numeric" required>
                 <div id="code-error"></div> 
                 <button type="button" id="verify-code-btn" class="verify-code-btn">Verificar Código</button>
            </div>

            
            <form id="resetForm" class="recovery-form" style="display: none;">
                <input type="hidden" id="verifiedCode" value="">

                <div class="form-group">
                    <label for="nueva_contrasena">Nueva Contraseña</label>
                    <div class="password-hint">*Mínimo 8 carac, 1 especial (ej. !@#$) y terminar con AL</div>
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

             <div id="initial-error" style="text-align: center; color: red;"></div>
        </div>
    </div>

<script>
$(document).ready(function() {
    const Toast = Swal.mixin({ /* ... Config Toast ... */ });
    const specialCharRegex = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]+/;
    const hintText = '*Mínimo 8 carac, 1 especial (ej. !@#$) y terminar con AL';

    $('.toggle-password').on('click', function() { $(this).toggleClass('fa-eye fa-eye-slash'); const i=$(this).prev('input'); i.attr('type',i.attr('type')==='password'?'text':'password'); });
    $('#nueva_contrasena').on('keyup', function() { /* ... Lógica validación viva ... */ });

    // --- Lógica Inicial: Token vs Código ---
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');
    const $resetForm = $('#resetForm'); // Formulario de contraseña
    const $codeSection = $('#code-section'); // Sección de código
    const $instructionText = $('#instruction-text');
    const $initialError = $('#initial-error');
    let verifiedCode = null; // Variable para guardar el código si se verifica

    if (token) {
        // --- CASO 1: Hay TOKEN ---
        $instructionText.text('Ingresa tu nueva contraseña.');
        $codeSection.hide();
        $resetForm.show(); // Mostrar form de contraseña directamente
        $initialError.hide();
    } else {
        // --- CASO 2: NO hay TOKEN ---
        $instructionText.text('Ingresa el código de 4 dígitos recibido en tu correo.');
        $codeSection.show(); // Mostrar solo sección de código
        $resetForm.hide();   // Ocultar form de contraseña
        $initialError.hide();
        $('#resetCode').focus();
    }

    // --- ✅ Evento Click: Verificar Código ---
    $('#verify-code-btn').on('click', function() {
        const code = $('#resetCode').val().trim();
        const $button = $(this);
        const $codeError = $('#code-error');
        $codeError.hide().text(''); // Limpiar errores previos

        if (!code || code.length !== 4 || !/^\d{4}$/.test(code)) {
            $codeError.text('Ingresa un código válido de 4 dígitos.').show();
            $('#resetCode').focus();
            return;
        }

        $button.prop('disabled', true).text('Verificando...');

        // Llamar a la NUEVA API de verificación
        $.ajax({
            url: '../api/verificarCodigoReset.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ code: code }),
            success: function(response) {
                if (response.success) {
                    // ¡Código VÁLIDO! Ocultar código, mostrar contraseña
                    verifiedCode = code; // Guardar el código verificado
                    $('#verifiedCode').val(code); // Guardarlo también en el input hidden
                    $instructionText.text('Código verificado. Ahora ingresa tu nueva contraseña.');
                    $codeSection.hide();
                    $resetForm.show();
                    $('#nueva_contrasena').focus(); // Poner foco en la nueva contraseña
                } else {
                    // Código inválido o expirado
                    $codeError.text(response.mensaje || 'Código incorrecto.').show();
                    $button.prop('disabled', false).text('Verificar Código');
                    $('#resetCode').focus().select();
                }
            },
            error: function() {
                // Error de conexión
                $codeError.text('Error de conexión al verificar. Inténtalo de nuevo.').show();
                $button.prop('disabled', false).text('Verificar Código');
            }
        });
    });

    // --- Envío del Formulario Principal (Guardar Contraseña) ---
    $('#resetForm').on('submit', function(e) {
        e.preventDefault();
        const submitBtn = $('.verify-btn');
        submitBtn.text('Guardando...').prop('disabled', true);

        const nuevaContrasena = $('#nueva_contrasena').val();
        const confirmarContrasena = $('#confirmar_contrasena').val();
        // Recuperar el código verificado si aplica
        verifiedCode = $('#verifiedCode').val() || null;

        // Validaciones Contraseña (igual que antes)
        if (nuevaContrasena !== confirmarContrasena) { /* ... Toast error ... */ submitBtn.text('Guardar').prop('disabled', false); return; }
        const isLengthOk = nuevaContrasena.length >= 8; const hasSpecialChar = specialCharRegex.test(nuevaContrasena); const isAlOk = nuevaContrasena.toLowerCase().endsWith('al');
        if (!isLengthOk || !hasSpecialChar || !isAlOk) { /* ... Toast error ... */ submitBtn.text('Guardar').prop('disabled', false); return; }

        // Construir Payload: Token o Código (verificado)
        let payload = { nueva_contrasena: nuevaContrasena, confirmar_contrasena: confirmarContrasena };
        if (token) {
            payload.token = token;
        } else if (verifiedCode) { // Asegurarse de que el código fue verificado
            payload.code = verifiedCode;
        } else {
             // Esto no debería pasar si la lógica de mostrar/ocultar funciona
             Toast.fire({ icon: 'error', title: 'Error: No se encontró token ni código verificado.' });
             submitBtn.text('Guardar Contraseña').prop('disabled', false);
             return;
        }

        // Llamada AJAX (la URL es la misma)
        $.ajax({
            url: '../api/resetearContrasena.php', method: 'POST', contentType: 'application/json', data: JSON.stringify(payload),
            success: function(response) {
                if (response.success) {
                    $('#resetForm').hide();
                    Swal.fire({ icon: 'success', title: response.mensaje, text: 'Redirigiendo...', timer: 2500, showConfirmButton: false })
                    .then(() => { window.location.href = 'login.php'; });
                } else {
                    Toast.fire({ icon: 'error', title: response.mensaje });
                    submitBtn.text('Guardar Contraseña').prop('disabled', false);
                    // Si el error fue por código, quizá mostrar sección de código de nuevo? O solo mensaje.
                }
            },
            error: function() { Toast.fire({ icon: 'error', title: 'Error de conexión.' }); submitBtn.text('Guardar').prop('disabled', false); }
        });
    });

}); // Fin document ready
</script>

</body>
</html>