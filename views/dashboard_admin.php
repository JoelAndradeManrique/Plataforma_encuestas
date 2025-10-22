<?php
session_start();

// 2. Seguridad ESTRICTA: 
// Verificar si hay sesión Y si el rol es 'admin'
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    // Si no es admin, lo sacamos al login
    header("Location: login.php");
    exit();
}

// 3. Si llegamos aquí, es admin.
$usuario = $_SESSION['usuario'];
$nombre = htmlspecialchars($usuario['nombre']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Administrador</title>
    <link rel="stylesheet" href="../css/style.css"> <style>
        body { display: grid; place-items: center; min-height: 90vh; }
        .dashboard-content { text-align: center; }
        .dashboard-content h1 { font-size: 2.5rem; color: #dc3545; }
        .dashboard-content a { font-size: 1.2rem; text-decoration: none; color: #007bff; }
    </style>
</head>
<body>
    <div class="dashboard-content">
        <h1>Hola Administrador <?php echo $nombre; ?> 🛡️</h1>
        <a href="logout.php">Cerrar Sesión</a>
    </div>
</body>
</html>