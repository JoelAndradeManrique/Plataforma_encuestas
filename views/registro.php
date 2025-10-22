<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma de Encuestas Estudiantiles - Registro</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.register-form');
            const registerBtn = document.querySelector('.register-btn');
            
            // --- VALIDACIONES EN TIEMPO REAL (Las que ya tenías) ---
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password'); // Asegúrate que el ID sea 'confirm_password' en tu HTML
            const passwordHint = document.querySelector('.password-hint');

            if(passwordInput && passwordHint) {
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    if (password.length > 0) {
                        if (password.length >= 8 && password.toLowerCase().endsWith('al')) {
                            passwordHint.style.color = '#28a745'; // Verde
                            passwordHint.textContent = '✓ Contraseña válida';
                        } else {
                            passwordHint.style.color = '#dc3545'; // Rojo
                            passwordHint.textContent = 'Mínimo 8 caracteres y con terminación AL';
                        }
                    } else {
                        passwordHint.style.color = '#dc3545';
                        passwordHint.textContent = 'Mínimo 8 caracteres y con terminación AL';
                    }
                });
            }

            if(confirmPasswordInput) {
                confirmPasswordInput.addEventListener('input', function() {
                    const password = passwordInput.value;
                    const confirmPassword = this.value;
                    if (confirmPassword.length > 0) {
                        this.style.borderColor = (password === confirmPassword) ? '#28a745' : '#dc3545';
                    } else {
                        this.style.borderColor = '#e0e0e0'; // Color por defecto
                    }
                });
            }

            // --- MANEJO DEL ENVÍO DEL FORMULARIO ---
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Prevenir envío tradicional
                
                // Limpiar notificaciones previas
                removeNotifications();
                
                // --- VALIDACIONES AL ENVIAR (Las que ya tenías) ---
                if (!validateEmptyFields()) {
                    return; // Detiene si faltan campos
                }
                if (!validatePasswords()) {
                    return; // Detiene si las contraseñas no coinciden o no son válidas
                }
                
                // Cambiar estado del botón a "cargando"
                registerBtn.textContent = 'Registrando...';
                registerBtn.disabled = true;

                // --- RECOGER DATOS PARA LA API ---
                const datosRegistro = {
                    nombre: document.getElementById('nombre').value,
                    apellido: document.getElementById('apellido').value,
                    email: document.getElementById('email').value,
                    genero: document.querySelector('input[name="genero"]:checked').value,
                    carrera: document.getElementById('carrera').value,
                    contrasena: passwordInput.value,
                    confirmarContrasena: confirmPasswordInput.value // Aunque ya validamos, la enviamos por si acaso
                };

                // --- LLAMADA A LA API USANDO FETCH (JavaScript moderno) ---
                fetch('../api/registrarAlumno.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(datosRegistro) // Convertir objeto a JSON
                })
                .then(response => response.json()) // Convertir la respuesta de la API a JSON
                .then(data => {
                    if (data.success) {
                        // Éxito: Mostrar notificación, cambiar botón y redirigir
                        showNotification(data.mensaje, 'success');
                        registerBtn.textContent = '¡Registrado!';
                        // registerBtn.disabled = true; // El botón ya está deshabilitado
                        setTimeout(function() {
                            window.location.href = 'login.php'; // Redirigir al login
                        }, 2000); // Espera 2 segundos
                    } else {
                        // Error de lógica (ej. correo duplicado)
                        showNotification(data.mensaje, 'error');
                        // Restaurar botón
                        registerBtn.textContent = 'Regístrate';
                        registerBtn.disabled = false;
                    }
                })
                .catch(error => {
                    // Error de red o al procesar el JSON
                    console.error('Error:', error);
                    showNotification('Error de conexión. Inténtalo de nuevo.', 'error');
                    // Restaurar botón
                    registerBtn.textContent = 'Regístrate';
                    registerBtn.disabled = false;
                });
            });
            
            // --- FUNCIONES DE VALIDACIÓN (Las que ya tenías, ligeramente ajustadas) ---
            function validateEmptyFields() {
                const requiredFields = [
                    { id: 'nombre', name: 'Nombre' }, { id: 'apellido', name: 'Apellido' },
                    { id: 'email', name: 'Correo electrónico' }, { id: 'carrera', name: 'Carrera' },
                    { id: 'password', name: 'Contraseña' }, { id: 'confirm_password', name: 'Confirmar Contraseña' }
                ];
                for (let field of requiredFields) {
                    const element = document.getElementById(field.id);
                    if (!element.value.trim()) {
                        showNotification(`El campo "${field.name}" es obligatorio.`, 'error');
                        element.focus();
                        return false; // Indica que la validación falló
                    }
                }
                const genero = document.querySelector('input[name="genero"]:checked');
                if (!genero) {
                    showNotification('Por favor, selecciona tu género.', 'error');
                    return false; // Indica que la validación falló
                }
                return true; // Indica que la validación pasó
            }
            
            function validatePasswords() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (password !== confirmPassword) {
                    showNotification('Las contraseñas no coinciden.', 'error');
                    confirmPasswordInput.focus();
                    return false; // Falla
                }
                // Añadimos la validación de formato aquí también
                if (password.length < 8 || !password.toLowerCase().endsWith('al')) {
                     showNotification('La contraseña debe tener al menos 8 caracteres y terminar con "AL".', 'error');
                     passwordInput.focus();
                     return false; // Falla
                }
                return true; // Pasa
            }
            
            // --- FUNCIONES PARA NOTIFICACIONES (Las que ya tenías) ---
            function showNotification(message, type) {
                removeNotifications();
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                notification.innerHTML = `<div class="notification-bar"></div><span>${message}</span>`;
                document.body.appendChild(notification);
                setTimeout(() => { notification.remove(); }, 5000);
            }

            function removeNotifications() {
                document.querySelectorAll('.notification').forEach(notif => notif.remove());
            }
        });
    </script>
</body>
</html>
