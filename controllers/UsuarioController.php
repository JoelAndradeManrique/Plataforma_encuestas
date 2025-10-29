<?php
// controllers/UsuarioController.php
// --- ✅ AÑADIDO: Incluir PHPMailer ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Incluir el autoload de Composer para cargar PHPMailer
require '../vendor/autoload.php'; // Ajusta la ruta si tu carpeta vendor está en otro lugar

require_once '../models/Usuario.php';

class UsuarioController {
    private $modeloUsuario;
    public $conexion; // Necesario para acceder a $conexion->error

    public function __construct($db) {
        $this->conexion = $db;
        $this->modeloUsuario = new Usuario($db);
    }

    /**
     * Procesa el registro de un nuevo alumno.
     * @param array $datos Datos recibidos del formulario.
     * @return array Respuesta con estado y mensaje.
     */
    public function registrarAlumno($datos) {
        // Validación básica de campos requeridos
        $campos_requeridos = ['nombre', 'apellido', 'email', 'genero', 'carrera', 'contrasena', 'confirmarContrasena'];
        foreach ($campos_requeridos as $campo) {
            if (empty($datos[$campo])) {
                return ['estado' => 400, 'success' => false, 'mensaje' => 'Todos los campos son obligatorios.'];
            }
        }

        // Validación de formato de email
        if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'El formato del correo no es válido.'];
        }

        // Validación de coincidencia de contraseñas
        if ($datos['contrasena'] !== $datos['confirmarContrasena']) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Las contraseñas no coinciden.'];
        }

        // --- ✅ INICIO DE VALIDACIÓN DE CONTRASEÑA ACTUALIZADA ---
        $contrasena = $datos['contrasena'];
        $mensajeError = '';

        // 1. Mínimo 8 caracteres
        if (strlen($contrasena) < 8) {
            $mensajeError = 'La contraseña debe tener al menos 8 caracteres.';
        }
        // 2. Termina en AL (case-insensitive para ser robusto)
        else if (!preg_match('/AL$/i', $contrasena)) { 
            $mensajeError = 'La contraseña debe terminar con "AL".';
        }
        // 3. Un caracter especial
        else if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]+/', $contrasena)) {
            $mensajeError = 'La contraseña debe contener al menos un caracter especial (ej. !@#$%).';
        }

        // Si hay algún error, devolverlo
        if (!empty($mensajeError)) {
            return ['estado' => 400, 'success' => false, 'mensaje' => $mensajeError];
        }
        // --- ✅ FIN DE VALIDACIÓN DE CONTRASEÑA ---


        // Verificar si el email ya existe
        if ($this->modeloUsuario->findByEmail($datos['email'])) {
            return ['estado' => 409, 'success' => false, 'mensaje' => 'Este correo electrónico ya está registrado.']; // 409 Conflict
        }

        // Si todo es válido, intentar crear el alumno
        if ($this->modeloUsuario->createAlumno($datos)) {
            return ['estado' => 201, 'success' => true, 'mensaje' => 'Registro exitoso. Ahora puedes iniciar sesión.']; // 201 Created
        } else {
            return ['estado' => 500, 'success' => false, 'mensaje' => 'Error al registrar el usuario.', 'error_db' => $this->conexion->error]; // 500 Internal Server Error
        }
    }

   /**
     * Procesa el registro de un nuevo encuestador (por el admin).
     * @param array $datos Datos recibidos (nombre, email, asignatura, contrasena).
     * @return array Respuesta con estado y mensaje.
     */
    public function registrarEncuestador($datos) {
        // Validación de campos requeridos para encuestador
        $campos_requeridos = ['nombre', 'email', 'asignatura', 'contrasena'];
        foreach ($campos_requeridos as $campo) {
            if (empty($datos[$campo])) {
                return ['estado' => 400, 'success' => false, 'mensaje' => "El campo '$campo' es obligatorio."];
            }
        }

        // Validación de formato de email
        if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'El formato del correo no es válido.'];
        }

        // --- ✅ NUEVA VALIDACIÓN DE DOMINIO ---
        // Verificamos si el email termina con "@tecmerida.com" (ignorando mayúsculas/minúsculas)
        if (!preg_match('/@tecmerida\.com$/i', $datos['email'])) {
            return [
                'estado' => 400, 
                'success' => false, 
                'mensaje' => 'El correo del encuestador debe pertenecer al dominio @tecmerida.com.'
            ];
        }
        // --- FIN DE LA NUEVA VALIDACIÓN ---


        // --- VALIDACIÓN DE CONTRASEÑA (La que ya teníamos) ---
        $contrasena = $datos['contrasena'];
        $mensajeError = '';

        if (strlen($contrasena) < 8) {
            $mensajeError = 'La contraseña debe tener al menos 8 caracteres.';
        }
        else if (!preg_match('/AL$/i', $contrasena)) { 
            $mensajeError = 'La contraseña debe terminar con "AL".';
        }
        else if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]+/', $contrasena)) {
            $mensajeError = 'La contraseña debe contener al menos un caracter especial (ej. !@#$%).';
        }

        if (!empty($mensajeError)) {
            return ['estado' => 400, 'success' => false, 'mensaje' => $mensajeError];
        }
        // --- FIN DE VALIDACIÓN DE CONTRASEÑA ---

        // Verificar si el email ya existe
        if ($this->modeloUsuario->findByEmail($datos['email'])) {
            return ['estado' => 409, 'success' => false, 'mensaje' => 'Este correo electrónico ya está registrado.'];
        }

        // Si todo es válido, intentar crear el encuestador
        if ($this->modeloUsuario->createEncuestador($datos)) {
            return ['estado' => 201, 'success' => true, 'mensaje' => 'Encuestador registrado con éxito.'];
        } else {
            return ['estado' => 500, 'success' => false, 'mensaje' => 'Error al registrar el encuestador.', 'error_db' => $this->conexion->error];
        }
    }
    /**
     * Procesa el inicio de sesión del usuario.
     * @param array $datos Contiene 'email' y 'contrasena'.
     * @return array Array de respuesta con estado, éxito, mensaje y datos del usuario/info de redirección.
     */
    public function login($datos) {
        // Validación básica
        if (empty($datos['email']) || empty($datos['contrasena'])) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Correo y contraseña son requeridos.'];
        }

        // Buscar usuario por email
        $usuario = $this->modeloUsuario->findByEmail($datos['email']);

        // Verificar si el usuario existe Y la contraseña es correcta
        if ($usuario && password_verify($datos['contrasena'], $usuario['contrasena_hash'])) {
            // ¡Contraseña correcta!

            // Eliminar datos sensibles antes de devolver
            unset($usuario['contrasena_hash']);

            // Verificar si es un encuestador con contraseña temporal
            if ($usuario['rol'] === 'encuestador' && $usuario['password_temporal'] == TRUE) {
                // Indicamos al frontend que redirija a la página de cambio de contraseña
                return [
                    'estado' => 200, 
                    'success' => true, 
                    'mensaje' => 'Login exitoso. Debes cambiar tu contraseña temporal.', 
                    'usuario' => $usuario,
                    'accion_requerida' => 'cambiar_contrasena' // Señal para redirección
                ];
            } else {
                // Login normal para admin, alumno o encuestador con contraseña ya cambiada
                return [
                    'estado' => 200, 
                    'success' => true, 
                    'mensaje' => 'Login exitoso.', 
                    'usuario' => $usuario 
                ];
            }

        } else {
            // Usuario no encontrado o contraseña incorrecta
            return ['estado' => 401, 'success' => false, 'mensaje' => 'Credenciales incorrectas.']; // 401 Unauthorized
        }
    }
    /**
     * Obtiene los datos de un usuario específico para edición.
     * @param int $id_usuario
     * @return array
     */
    public function obtenerUsuarioPorId($id_usuario) {
        if (empty($id_usuario) || !is_numeric($id_usuario)) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Se requiere un ID de usuario válido.'];
        }

        $usuario = $this->modeloUsuario->getById($id_usuario);

        if ($usuario) {
            return ['estado' => 200, 'success' => true, 'usuario' => $usuario];
        } else {
            return ['estado' => 404, 'success' => false, 'mensaje' => 'Usuario no encontrado.']; // 404 Not Found
        }
    }

    /**
     * Procesa la edición de un usuario (por Admin).
     * @param array $datos Datos del usuario a actualizar, incluyendo id_usuario.
     * @return array
     */
    public function editarUsuario($datos) {
        if (empty($datos['id_usuario']) || !is_numeric($datos['id_usuario'])) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Se requiere el ID del usuario a editar.'];
        }
        $id_usuario = intval($datos['id_usuario']);

        // Opcional: Validar formato de email si se incluye
        if (isset($datos['email']) && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
             return ['estado' => 400, 'success' => false, 'mensaje' => 'El formato del correo no es válido.'];
        }
        
        // Opcional: Verificar si el nuevo email ya existe para otro usuario
        if (isset($datos['email'])) {
            $existingUser = $this->modeloUsuario->findByEmail($datos['email']);
            if ($existingUser && $existingUser['id_usuario'] != $id_usuario) {
                return ['estado' => 409, 'success' => false, 'mensaje' => 'El nuevo correo electrónico ya está en uso por otro usuario.'];
            }
        }

        $filasAfectadas = $this->modeloUsuario->updateUser($id_usuario, $datos);

        if ($filasAfectadas >= 0) {
            return ['estado' => 200, 'success' => true, 'mensaje' => 'Usuario actualizado con éxito.'];
        } else {
            return ['estado' => 500, 'success' => false, 'mensaje' => 'Error al actualizar el usuario.', 'error_db' => $this->conexion->error];
        }
    }

    /**
     * Procesa la eliminación de un usuario (por Admin).
     * @param array $datos Debe contener 'id_usuario'.
     * @return array
     */
    public function eliminarUsuario($datos) {
        if (empty($datos['id_usuario']) || !is_numeric($datos['id_usuario'])) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Se requiere el ID del usuario a eliminar.'];
        }
        $id_usuario = intval($datos['id_usuario']);

        // Opcional: Impedir que el admin se elimine a sí mismo
        // if ($id_usuario === $_SESSION['usuario']['id_usuario']) { ... }

        $filasAfectadas = $this->modeloUsuario->delete($id_usuario);

        if ($filasAfectadas > 0) {
            return ['estado' => 200, 'success' => true, 'mensaje' => 'Usuario eliminado con éxito.'];
        } elseif ($filasAfectadas === 0) {
            return ['estado' => 404, 'success' => false, 'mensaje' => 'Usuario no encontrado.'];
        } else {
             // Verificamos si es un error por llave foránea (ej. un encuestador con encuestas)
            if ($this->conexion->errno == 1451) {
                 return ['estado' => 409, 'success' => false, 'mensaje' => 'Conflicto: No se puede eliminar el usuario porque tiene registros asociados (encuestas creadas, etc.).'];
            }
            return ['estado' => 500, 'success' => false, 'mensaje' => 'Error al eliminar el usuario.', 'error_db' => $this->conexion->error];
        }
    }
    /**
     * Procesa la solicitud de recuperación de contraseña.
     * Genera token y código, los guarda, y envía un email con PHPMailer.
     */
    public function solicitarRecuperacion($datos) {
        if (empty($datos['email'])) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Se requiere el correo electrónico.'];
        }
        $email = $datos['email'];

        $usuario = $this->modeloUsuario->findByEmail($email);
        if (!$usuario) {
            // Mensaje genérico por seguridad
            return ['estado' => 200, 'success' => true, 'mensaje' => 'Si tu correo está registrado, recibirás instrucciones.'];
        }

        // Generar token y código
        $token = bin2hex(random_bytes(32));
        $code = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $expiracion = date('Y-m-d H:i:s', time() + 3600); // 1 hora

        // Guardar en DB
        if ($this->modeloUsuario->guardarResetToken($email, $token, $code, $expiracion)) {

            // --- ✅ INICIO: Envío de Correo con PHPMailer (Gmail) ---
            $mail = new PHPMailer(true); // Habilitar excepciones

            try {
                // Configuración del servidor SMTP de Gmail
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                // --- TU CORREO Y CONTRASEÑA DE APLICACIÓN ---
                $mail->Username   = 'joelmanrique38@gmail.com'; // ¡¡CAMBIA ESTO!!
                $mail->Password   = 'izpj olwq zndp rvfi'; // ¡¡LA CONTRASEÑA DE 16 LETRAS QUE GENERASTE!!
                // --- FIN CONFIGURACIÓN PERSONAL ---
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Usar SSL/TLS
                $mail->Port       = 465;                   // Puerto para SSL

                // Remitente y Destinatario
                $mail->setFrom('tu_correo@gmail.com', 'Plataforma Encuestas'); // ¡CAMBIA Remitente!
                $mail->addAddress($email); // El correo del usuario que solicitó

                // Contenido del Correo
                $mail->isHTML(false); // Enviar como texto plano (más simple)
                $mail->Subject = 'Recuperacion de Contrasena - Plataforma de Encuestas';

                $urlBaseFrontend = "http://localhost/plataformaEncuestas/views/"; // ¡Ajusta esta URL!
                $linkRecuperacion = $urlBaseFrontend . "resetear-contrasena.php?token=" . $token; // Asegúrate que el archivo se llame así

                $mail->Body    = "Hola,\n\n";
                $mail->Body   .= "Has solicitado restablecer tu contrasena.\n\n";
                $mail->Body   .= "Opcion 1: Haz clic en el siguiente enlace (valido por 1 hora):\n";
                $mail->Body   .= $linkRecuperacion . "\n\n";
                $mail->Body   .= "Opcion 2: Ingresa el siguiente codigo de 4 digitos en la pagina de reseteo (valido por 1 hora):\n";
                $mail->Body   .= "Codigo: " . $code . "\n\n";
                $mail->Body   .= "Si no solicitaste esto, ignora este correo.\n\n";
                $mail->Body   .= "Saludos,\nEquipo Plataforma de Encuestas";

                $mail->send(); // Enviar el correo

                // Éxito al enviar
                return [
                    'estado' => 200,
                    'success' => true,
                    'mensaje' => 'Instrucciones enviadas a tu correo electrónico.'
                ];

            } catch (Exception $e) {
                // Error al enviar correo (PHPMailer lanzó excepción)
                error_log("PHPMailer Error al enviar a $email: {$mail->ErrorInfo}");
                // No revelamos el error detallado al usuario por seguridad
                return ['estado' => 500, 'success' => false, 'mensaje' => 'Se procesó tu solicitud, pero hubo un error al enviar el correo. Contacta soporte.'];
            }
            // --- ✅ FIN: Envío de Correo ---

        } else {
            return ['estado' => 500, 'success' => false, 'mensaje' => 'Error al procesar la solicitud en la base de datos.'];
        }
    }

    

    /**
     * Procesa el reseteo de la contraseña usando un token O un código.
     */
    public function resetearContrasena($datos) {
        // Validar campos comunes
        if (empty($datos['nueva_contrasena']) || empty($datos['confirmar_contrasena'])) {
             return ['estado' => 400, 'success' => false, 'mensaje' => 'Se requiere la nueva contraseña y la confirmación.'];
        }
        if ($datos['nueva_contrasena'] !== $datos['confirmar_contrasena']) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Las contraseñas no coinciden.'];
        }

        // --- VALIDACIÓN DE NUEVA CONTRASEÑA ---
        // (Añade aquí la misma validación que en registrarAlumno: 8 carac, especial, termina AL)
        $contrasena = $datos['nueva_contrasena'];
        // ... (código de validación) ...
        if (!empty($mensajeError)) { return ['estado' => 400, 'success' => false, 'mensaje' => $mensajeError]; }
        // --- FIN VALIDACIÓN ---


        // --- Lógica para buscar por TOKEN o CÓDIGO ---
        $usuario = null;
        if (!empty($datos['token'])) {
            // Buscar por token (como antes)
            $usuario = $this->modeloUsuario->buscarPorResetToken($datos['token']);
            if (!$usuario) {
                 return ['estado' => 400, 'success' => false, 'mensaje' => 'Token inválido o expirado. Solicita un nuevo enlace.'];
            }
        } else if (!empty($datos['code'])) {
            // Buscar por código (nueva lógica)
            $usuario = $this->modeloUsuario->buscarPorResetCode($datos['code']);
             if (!$usuario) {
                 return ['estado' => 400, 'success' => false, 'mensaje' => 'Código inválido o expirado. Intenta de nuevo.'];
            }
        } else {
             // Ni token ni código proporcionados
             return ['estado' => 400, 'success' => false, 'mensaje' => 'Se requiere el token o el código de reseteo.'];
        }
        // --- FIN LÓGICA TOKEN/CÓDIGO ---


        // Si encontramos al usuario (por token o código), procedemos a actualizar
        if ($usuario) {
            $hash = password_hash($datos['nueva_contrasena'], PASSWORD_DEFAULT);
            // La función updatePassword ya limpia ambos (token y code)
            if ($this->modeloUsuario->updatePassword($usuario['id_usuario'], $hash) > 0) {
                return ['estado' => 200, 'success' => true, 'mensaje' => 'Contraseña actualizada con éxito. Ya puedes iniciar sesión.'];
            } else {
                return ['estado' => 500, 'success' => false, 'mensaje' => 'Error al actualizar la contraseña en la base de datos.'];
            }
        }
        // Este else no debería alcanzarse por las validaciones anteriores, pero por si acaso:
        else {
             return ['estado' => 400, 'success' => false, 'mensaje' => 'No se pudo validar el token o código.'];
        }
    }

    /**
     * Procesa el cambio de contraseña para un usuario ya logueado (ej. temporal).
     * No usa token, usa la sesión.
     */
    public function cambiarContrasenaLogueado($datos) {
        // Validar campos
        if (empty($datos['id_usuario']) || empty($datos['nueva_contrasena']) || empty($datos['confirmar_contrasena'])) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Todos los campos son requeridos.'];
        }

        if ($datos['nueva_contrasena'] !== $datos['confirmar_contrasena']) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Las contraseñas no coinciden.'];
        }
        
        // --- VALIDACIÓN DE CONTRASEÑA (la misma que usamos en registro) ---
        $contrasena = $datos['nueva_contrasena'];
        $mensajeError = '';

        if (strlen($contrasena) < 8) {
            $mensajeError = 'La contraseña debe tener al menos 8 caracteres.';
        }
        else if (!preg_match('/AL$/i', $contrasena)) { 
            $mensajeError = 'La contraseña debe terminar con "AL".';
        }
        else if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]+/', $contrasena)) {
            $mensajeError = 'La contraseña debe contener al menos un caracter especial (ej. !@#$%).';
        }

        if (!empty($mensajeError)) {
            return ['estado' => 400, 'success' => false, 'mensaje' => $mensajeError];
        }
        // --- FIN VALIDACIÓN ---

        // Hashear y guardar
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        
        // Usamos la función del MODELO existente 'updatePassword'
        // (ya que esa función también pone password_temporal = FALSE)
        if ($this->modeloUsuario->updatePassword($datos['id_usuario'], $hash) > 0) {
            return ['estado' => 200, 'success' => true, 'mensaje' => 'Contraseña actualizada con éxito.'];
        } else {
            return ['estado' => 500, 'success' => false, 'mensaje' => 'Error al actualizar la contraseña.'];
        }
    }

    /**
     * CAMBIAR MI CONTRASEÑA (Desde Perfil)
     * Permite a un usuario logueado cambiar su contraseña proporcionando la actual.
     */
    public function cambiarMiContrasena($datos) {
        // Validaciones básicas
        if (empty($datos['id_usuario']) || empty($datos['contrasena_actual']) || empty($datos['nueva_contrasena']) || empty($datos['confirmar_contrasena'])) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Todos los campos son requeridos.'];
        }
        if ($datos['nueva_contrasena'] !== $datos['confirmar_contrasena']) {
            return ['estado' => 400, 'success' => false, 'mensaje' => 'Las contraseñas nuevas no coinciden.'];
        }
        if ($datos['nueva_contrasena'] === $datos['contrasena_actual']) {
             return ['estado' => 400, 'success' => false, 'mensaje' => 'La nueva contraseña no puede ser igual a la actual.'];
        }

        // --- VALIDACIÓN DE NUEVA CONTRASEÑA (la misma regla) ---
        $contrasena = $datos['nueva_contrasena'];
        $mensajeError = '';
        if (strlen($contrasena) < 8) { $mensajeError = 'La contraseña debe tener al menos 8 caracteres.'; }
        else if (!preg_match('/AL$/i', $contrasena)) { $mensajeError = 'La contraseña debe terminar con "AL".'; }
        else if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]+/', $contrasena)) { $mensajeError = 'La contraseña debe contener al menos un caracter especial (ej. !@#$%).'; }
        if (!empty($mensajeError)) { return ['estado' => 400, 'success' => false, 'mensaje' => $mensajeError]; }
        // --- FIN VALIDACIÓN ---

        // 1. Obtener el hash actual del usuario desde la DB
        $query_hash = "SELECT contrasena_hash FROM usuarios WHERE id_usuario = ?";
        $stmt_hash = $this->conexion->prepare($query_hash);
        if (!$stmt_hash) { return ['estado' => 500, 'success' => false, 'mensaje' => 'Error al preparar consulta.']; }
        $stmt_hash->bind_param("i", $datos['id_usuario']);
        $stmt_hash->execute();
        $resultado = $stmt_hash->get_result()->fetch_assoc();
        $stmt_hash->close();

        if (!$resultado) {
            return ['estado' => 404, 'success' => false, 'mensaje' => 'Usuario no encontrado.'];
        }

        // 2. Verificar si la contraseña actual proporcionada coincide
        if (!password_verify($datos['contrasena_actual'], $resultado['contrasena_hash'])) {
            return ['estado' => 401, 'success' => false, 'mensaje' => 'La contraseña actual es incorrecta.']; // 401 Unauthorized
        }

        // 3. Si todo es correcto, hashear y actualizar la nueva contraseña
        $nuevo_hash = password_hash($datos['nueva_contrasena'], PASSWORD_DEFAULT);
        
        // Usamos la función del MODELO existente 'updatePassword'
        // (ya que esa función también pone password_temporal = FALSE, por si acaso)
        if ($this->modeloUsuario->updatePassword($datos['id_usuario'], $nuevo_hash) > 0) {
            return ['estado' => 200, 'success' => true, 'mensaje' => 'Contraseña actualizada con éxito.'];
        } else {
            return ['estado' => 500, 'success' => false, 'mensaje' => 'Error al actualizar la contraseña en la base de datos.'];
        }
    }
}
?>