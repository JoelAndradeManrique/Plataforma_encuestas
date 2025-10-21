<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma de Encuestas Estudiantiles - Recuperar Contraseña</title>
    <link rel="stylesheet" href="../css/style.css">
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
            
            <form action="../api/recuperar_password.php" method="POST" class="recovery-form">
                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" placeholder="Ingresa tu correo electrónico" required>
                </div>
                
                <button type="submit" class="verify-btn">Verificar correo</button>
                
                <div class="register-link">
                    <span>¿No tienes cuenta? </span>
                    <a href="registro.php" class="register-text">Registrate aquí</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
