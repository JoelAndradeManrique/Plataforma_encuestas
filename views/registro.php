<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma de Encuestas Estudiantiles - Registro</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <div class="register-card">
            <h1 class="title">Registro del Alumno</h1>
            
            <!-- Notificación de éxito -->
            <?php if (isset($_GET['success']) && $_GET['success'] == 'register'): ?>
                <div class="notification success">
                    <div class="notification-bar"></div>
                    <span>Registro exitoso</span>
                </div>
            <?php endif; ?>
            
            <!-- Notificación de error -->
            <?php if (isset($_GET['error'])): ?>
                <div class="notification error">
                    <div class="notification-bar"></div>
                    <span><?php echo htmlspecialchars($_GET['error']); ?></span>
                </div>
            <?php endif; ?>
            
            <form action="../api/registrarAlumno.php" method="POST" class="register-form">
                <div class="form-row">
                    <div class="form-group half">
                        <label for="nombre">Nombre(s)</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>
                    <div class="form-group half">
                        <label for="apellido">Apellido</label>
                        <input type="text" id="apellido" name="apellido" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" placeholder="Ingresa tu correo electrónico" required>
                </div>
                
                <div class="form-group">
                    <label>Género</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="genero" value="hombre" required>
                            <span>Hombre</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="genero" value="mujer" required>
                            <span>Mujer</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="genero" value="otro" required>
                            <span>Otro</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="carrera">Carrera</label>
                    <select id="carrera" name="carrera" required>
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
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="password-hint">Mínimo 8 caracteres y con terminación AL</div>
                    <input type="password" id="password" name="password" placeholder="Ingrese contraseña" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmar Contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirmar Contraseña" required>
                </div>
                
                <div class="form-footer">
                    <div class="login-link">
                        <span>¿Ya tienes cuenta? </span>
                        <a href="login.php" class="login-text">Inicia Sesión Aquí</a>
                    </div>
                    <button type="submit" class="register-btn">Regístrate</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Validaciones del formulario de registro (solo frontend)
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.register-form');
            const registerBtn = document.querySelector('.register-btn');
            
            // Manejar el envío del formulario
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Prevenir envío para simular solo frontend
                
                // Validar campos vacíos
                if (validateEmptyFields()) {
                    return;
                }
                
                // Validar contraseñas
                if (validatePasswords()) {
                    return;
                }
                
                // Si todo está bien, simular éxito
                registerBtn.textContent = 'Registrando...';
                registerBtn.disabled = true;
                
                setTimeout(function() {
                    showNotification('Registro exitoso', 'success');
                    registerBtn.textContent = '¡Registrado!';
                }, 1500);
            });
            
            // Validar campos vacíos
            function validateEmptyFields() {
                const requiredFields = [
                    { id: 'nombre', name: 'Nombre' },
                    { id: 'apellido', name: 'Apellido' },
                    { id: 'email', name: 'Correo electrónico' },
                    { id: 'carrera', name: 'Carrera' },
                    { id: 'password', name: 'Contraseña' },
                    { id: 'confirm_password', name: 'Confirmar Contraseña' }
                ];
                
                for (let field of requiredFields) {
                    const element = document.getElementById(field.id);
                    if (!element.value.trim()) {
                        showNotification('No se ha llenado un campo', 'error');
                        element.focus();
                        return true;
                    }
                }
                
                // Validar género
                const genero = document.querySelector('input[name="genero"]:checked');
                if (!genero) {
                    showNotification('No se ha llenado un campo', 'error');
                    return true;
                }
                
                return false;
            }
            
            // Validar contraseñas
            function validatePasswords() {
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                if (password !== confirmPassword) {
                    showNotification('contraseñas no coinciden', 'error');
                    document.getElementById('confirm_password').focus();
                    return true;
                }
                
                return false;
            }
            
            // Función para mostrar notificaciones
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
            
            // Validación de contraseña en tiempo real
            document.getElementById('password').addEventListener('input', function() {
                const password = this.value;
                const hint = document.querySelector('.password-hint');
                
                if (password.length > 0) {
                    if (password.length >= 8 && password.toLowerCase().endsWith('al')) {
                        hint.style.color = '#28a745';
                        hint.textContent = '✓ Contraseña válida';
                    } else {
                        hint.style.color = '#dc3545';
                        hint.textContent = 'Mínimo 8 caracteres y con terminación AL';
                    }
                } else {
                    hint.style.color = '#dc3545';
                    hint.textContent = 'Mínimo 8 caracteres y con terminación AL';
                }
            });
            
            // Validación de confirmación de contraseña en tiempo real
            document.getElementById('confirm_password').addEventListener('input', function() {
                const password = document.getElementById('password').value;
                const confirmPassword = this.value;
                
                if (confirmPassword.length > 0) {
                    if (password === confirmPassword) {
                        this.style.borderColor = '#28a745';
                    } else {
                        this.style.borderColor = '#dc3545';
                    }
                } else {
                    this.style.borderColor = '#e0e0e0';
                }
            });
        });
    </script>
</body>
</html>
