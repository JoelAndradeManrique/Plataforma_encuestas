<?php
// Define la contraseña que quieres para tu admin
// ¡Asegúrate de que cumpla las reglas! (8+ carac, 1 especial, termina en AL)
$contrasena_admin = "encalada.AL"; 

$hash = password_hash($contrasena_admin, PASSWORD_DEFAULT);

echo "Copia este hash completo y pégalo en el SQL:<br><br>";
echo $hash;
?>