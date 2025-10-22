<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma de Encuestas Estudiantiles - Login</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <div class="container">
        <div class="login-card">
            <div class="icon-container">
                <div class="clipboard-icon">
                    <div class="check-icon"></div>
                </div>
            </div>
            <h1 class="title">Plataforma de Encuestas Estudiantiles</h1>
            
            <!-- Notificación de éxito -->
            <?php if (isset($_GET['success']) && $_GET['success'] == 'login'): ?>
                <div class="notification success">
                    <div class="notification-bar"></div>
                    <span>Inicio de sesión exitoso</span>
                </div>
            <?php endif; ?>
            
            <!-- Notificación de error -->
            <?php if (isset($_GET['error'])): ?>
                <div class="notification error">
                    <div class="notification-bar"></div>
                    <span><?php echo htmlspecialchars($_GET['error']); ?></span>
                </div>
            <?php endif; ?>
            
            <form action="../api/login.php" method="POST" class="login-form" id="loginForm">
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" placeholder="Ingresa tu correo electrónico" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="password-input-container">
                        <input type="password" id="password" name="password" placeholder="Contraseña" required>
                        <span class="eye-icon">👁</span>
                    </div>
                </div>
                
                <button type="submit" class="login-btn">Iniciar Sesión</button>
                
                <div class="forgot-password">
                    <a href="recuperar_password.php" class="forgot-link">¿Olvidaste tu contraseña?</a>
                </div>
                
                <div class="register-link">
                    <span>¿No tienes cuenta? </span>
                    <a href="registro.php" class="register-text">Registrate aquí</a>
                </div>
            </form>
        </div>
    </div>
    
   <script>
document.addEventListener('DOMContentLoaded', function() {
    const loginBtn = document.querySelector('.login-btn');
    const form = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.querySelector('.eye-icon');

    // --- Lógica para el Ojo de la Contraseña ---
    if (eyeIcon) {
        eyeIcon.addEventListener('click', function() {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            this.textContent = isPassword ? '🙈' : '👁'; // Cambia el icono (puedes usar clases de Font Awesome si prefieres)
        });
    }

    // --- Lógica de Envío del Formulario ---
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Evitar envío tradicional

        // Mostrar estado de carga en el botón
        loginBtn.textContent = 'Iniciando...';
        loginBtn.disabled = true;

        const email = emailInput.value;
        const contrasena = passwordInput.value;

        // Limpiar notificaciones previas
        removeNotifications();

        // --- Llamada AJAX con jQuery ---
        $.ajax({
            url: '../api/login.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ email: email, contrasena: contrasena }),
            success: function(response) {
                if (response.success) {
                    // Éxito: Cambiar botón, mostrar notificación y redirigir
                    loginBtn.classList.add('success'); // Asegúrate de tener CSS para .success si quieres el cambio de color
                    loginBtn.textContent = '¡Login Exitoso!';
                    showNotification(response.mensaje, 'success');

                    // Guardar datos en localStorage
                    localStorage.setItem('usuario', JSON.stringify(response.usuario));

                    // Redirección con delay
                    setTimeout(function() {
                        if (response.accion_requerida === 'cambiar_contrasena') {
                            window.location.href = 'cambiar-contrasena.php';
                        } else if (response.usuario.rol === 'admin') {
                            window.location.href = 'admin-panel.php';
                        } else {
                            window.location.href = 'dashboard.php';
                        }
                    }, 1500); // Espera 1.5 segundos

                } else {
                    // Error de lógica (ej. credenciales incorrectas)
                    showNotification(response.mensaje, 'error');
                    // Restaurar botón
                    loginBtn.textContent = 'Iniciar Sesión';
                    loginBtn.disabled = false;
                }
            },
            error: function() {
                // Error de conexión o del servidor
                showNotification('Error de conexión. Inténtalo de nuevo.', 'error');
                // Restaurar botón
                loginBtn.textContent = 'Iniciar Sesión';
                loginBtn.disabled = false;
            }
        });
    });

    // --- Funciones para Notificaciones (las que ya tenías) ---
    function showNotification(message, type) {
        removeNotifications();
        const notification = document.createElement('div');
        notification.className = `notification ${type}`; // Asegúrate que tu CSS defina .notification y .success/.error
        notification.innerHTML = `<div class="notification-bar"></div><span>${message}</span>`;
        // Insertar antes del formulario, por ejemplo
        form.parentNode.insertBefore(notification, form);
        setTimeout(() => { notification.remove(); }, 5000);
    }

    function removeNotifications() {
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(notif => notif.remove());
    }
});
</script>
</body>
</html>
