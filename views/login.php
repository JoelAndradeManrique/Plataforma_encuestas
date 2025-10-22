<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma de Encuestas Estudiantiles - Login</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            
            <form class="login-form" id="loginForm">
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
        const form = document.getElementById('loginForm');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.querySelector('.eye-icon');

        // --- Configuración del "Toast" ---
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

        // --- Lógica para el Ojo de la Contraseña ---
        if (eyeIcon) {
            eyeIcon.addEventListener('click', function() {
                const isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                this.textContent = isPassword ? '🙈' : '👁';
            });
        }

        // --- Lógica de Envío del Formulario ---
        form.addEventListener('submit', function(e) {
            e.preventDefault(); 
            const loginBtn = document.querySelector('.login-btn');
            
            loginBtn.textContent = 'Iniciando...';
            loginBtn.disabled = true;

            const email = document.getElementById('email').value;
            const contrasena = document.getElementById('password').value;

            $.ajax({
                url: '../api/login.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ email: email, contrasena: contrasena }),
                success: function(response) {
                    if (response.success) {
                        
                        // --- ❌ LÓGICA DE 'accion_requerida' ELIMINADA ---
                        // El dashboard se encargará de esto ahora.

                        // Redirección por ROL
                        let destino = 'dashboard_general.php'; // Destino por defecto
                        if (response.usuario.rol === 'admin') {
                            destino = 'dashboard_admin.php';
                        }
                        
                        Swal.fire({
                            icon: 'success',
                            title: '¡Login Exitoso!',
                            text: 'Bienvenido, ' + response.usuario.nombre,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = destino;
                        });

                    } else {
                        // Error de lógica
                        Toast.fire({
                            icon: 'error',
                            title: response.mensaje
                        });
                        loginBtn.textContent = 'Iniciar Sesión';
                        loginBtn.disabled = false;
                    }
                },
                error: function(jqXHR) {
                    // Error de conexión
                    let errorMsg = 'Error de conexión. Inténtalo de nuevo.';
                    if(jqXHR.responseJSON && jqXHR.responseJSON.mensaje){
                        errorMsg = jqXHR.responseJSON.mensaje;
                    }
                    Toast.fire({ 
                        icon: 'error', 
                        title: errorMsg 
                    });
                    loginBtn.textContent = 'Iniciar Sesión';
                    loginBtn.disabled = false;
                }
            });
        });
    });
</script>
</body>
</html>