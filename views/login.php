<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma de Encuestas Estudiantiles - Login</title>
    <link rel="stylesheet" href="../css/style.css">
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
        // Cambiar color del botón cuando el login sea exitoso (solo frontend)
        document.addEventListener('DOMContentLoaded', function() {
            const loginBtn = document.querySelector('.login-btn');
            const form = document.getElementById('loginForm');
            
            // Manejar el envío del formulario (solo para cambio visual)
            form.addEventListener('submit', function(e) {
                // Mostrar estado de carga
                loginBtn.textContent = 'Iniciando...';
                loginBtn.disabled = true;
                
                // Simular éxito después de un delay
                setTimeout(function() {
                    // Cambiar el botón a rosa cuando sea "exitoso"
                    loginBtn.classList.add('success');
                    loginBtn.textContent = '¡Login Exitoso!';
                    
                    // Mostrar notificación de éxito
                    showNotification('Inicio de sesión exitoso', 'success');
                }, 1500);
            });
            
            // Función para mostrar notificaciones (solo visual)
            function showNotification(message, type) {
                // Remover notificaciones existentes
                const existingNotifications = document.querySelectorAll('.notification');
                existingNotifications.forEach(notif => notif.remove());
                
                // Crear nueva notificación
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                notification.innerHTML = `
                    <div class="notification-bar"></div>
                    <span>${message}</span>
                `;
                
                // Agregar al body
                document.body.appendChild(notification);
                
                // Remover después de 5 segundos
                setTimeout(() => {
                    notification.remove();
                }, 5000);
            }
        });
    </script>
</body>
</html>
