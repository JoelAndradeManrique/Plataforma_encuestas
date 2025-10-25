<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$usuario = $_SESSION['usuario'];
$nombre = htmlspecialchars($usuario['nombre'] ?? '');
$apellido = htmlspecialchars($usuario['apellido'] ?? '');
$rol = $usuario['rol'] ?? '';

if ($rol !== 'alumno') {
    header("Location: dashboard_general.php");
    exit();
}

$id_encuesta = isset($_GET['id']) ? intval($_GET['id']) : 0;
$iniciales = strtoupper(substr($nombre, 0, 1) . substr($apellido, 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Responder Encuesta</title>
  <link rel="stylesheet" href="../css/alumno.css?v=4">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <nav class="navbar">
    <div class="logo-container">
      <img src="../img/images.png" alt="Logo" class="logo">
      <span class="titulo" id="home-link">Sistema de Tickets</span>
    </div>
    <button id="btn-volver" class="nav-btn">Volver</button>
  </nav>

  <main class="contenido encuesta-contenido">
    <h2 class="titulo-encuestas--barra">RESPONDER ENCUESTA</h2>
    <div id="encuesta-container" class="encuesta-container">
      <p>Cargando...</p>
    </div>
    <div class="encuesta-acciones">
      <button id="btn-enviar" class="btn-primary">Enviar respuestas</button>
      <button id="btn-cancelar" class="btn-sec">Cancelar</button>
    </div>
  </main>

  <script>
  $(async function () {
    const id = <?php echo $id_encuesta; ?>;
    const $contenedor = $("#encuesta-container");

    // --- MODO DEMO: 101, 102, 103 ---
    const demo = {
      101: {
        titulo: "Encuesta de satisfacción del campus",
        descripcion: "Opiniones sobre la vida en el campus",
        preguntas: [
          { id: 1, texto: "¿Cómo calificas la limpieza del campus?", tipo: "opcion", opciones: ["Excelente", "Buena", "Regular", "Mala"] },
          { id: 2, texto: "¿Qué mejorarías?", tipo: "abierta" }
        ]
      },
      102: {
        titulo: "Encuesta de servicios institucionales",
        descripcion: "Evalúa el desempeño de los servicios brindados por la universidad",
        preguntas: [
          { id: 1, texto: "¿Qué tan satisfecho estás con la atención administrativa?", tipo: "opcion", opciones: ["Muy satisfecho", "Satisfecho", "Neutral", "Insatisfecho"] },
          { id: 2, texto: "¿Qué área consideras más eficiente?", tipo: "abierta" }
        ]
      },
      103: {
        titulo: "Encuesta de actividades complementarias",
        descripcion: "Ayúdanos a conocer tu participación en actividades extracurriculares",
        preguntas: [
          { id: 1, texto: "¿Participas en actividades deportivas?", tipo: "opcion", opciones: ["Sí", "No"] },
          { id: 2, texto: "¿Qué tipo de actividades te gustaría que agregáramos?", tipo: "abierta" }
        ]
      }
    };

    // --- Carga de encuesta ---
    async function cargarEncuesta() {
      // Si es una demo, usar local
      if (demo[id]) {
        renderEncuesta(demo[id]);
        return;
      }

      // Si no es demo, intentar obtener desde API
      try {
        const res = await $.getJSON("../api/obtenerDetalleEncuesta.php", { id_encuesta: id });
        if (!res || !res.titulo) throw new Error();
        renderEncuesta(res);
      } catch (err) {
        Swal.fire({
          icon: "error",
          title: "No se pudo cargar la encuesta",
          confirmButtonColor: "#7367f0"
        });
        $contenedor.html("<p>No se pudo cargar la encuesta.</p>");
      }
    }

    function renderEncuesta(encuesta) {
      let html = `
        <div class="encuesta-header">
          <h3>${encuesta.titulo}</h3>
          <p>${encuesta.descripcion || ""}</p>
        </div>
        <form id="form-encuesta" class="encuesta-form">
      `;

      encuesta.preguntas.forEach((p, i) => {
        html += `<div class="pregunta">
          <label><b>${i + 1}. ${p.texto}</b></label>`;

        if (p.tipo === "opcion" && Array.isArray(p.opciones)) {
          html += `<div class="opciones">`;
          p.opciones.forEach((op, j) => {
            html += `
              <label>
                <input type="radio" name="preg_${p.id}" value="${op}" required> ${op}
              </label>`;
          });
          html += `</div>`;
        } else {
          html += `<textarea name="preg_${p.id}" rows="3" placeholder="Escribe tu respuesta aquí..." required></textarea>`;
        }
        html += `</div>`;
      });

      html += `</form>`;
      $contenedor.html(html);
    }

    // --- Enviar respuestas ---
    $("#btn-enviar").on("click", function () {
      const datos = $("#form-encuesta").serializeArray();
      if (!datos.length) {
        Swal.fire("Atención", "Por favor responde todas las preguntas antes de enviar.", "warning");
        return;
      }

      Swal.fire({
        title: "¿Deseas enviar tus respuestas?",
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Sí, enviar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#007bff"
      }).then((res) => {
        if (!res.isConfirmed) return;

        // Si es demo, solo simular éxito
        if (demo[id]) {
          Swal.fire("¡Gracias!", "Tus respuestas se han enviado correctamente.", "success");
          return;
        }

        // Envío real (cuando el backend esté listo)
        $.ajax({
          url: "../api/guardarRespuestas.php",
          method: "POST",
          contentType: "application/json",
          data: JSON.stringify({ id_encuesta: id, respuestas: datos }),
          success: (r) => {
            if (r.success) {
              Swal.fire("¡Gracias!", "Tus respuestas se han enviado correctamente.", "success");
            } else {
              Swal.fire("Error", r.mensaje || "No se pudo enviar la encuesta.", "error");
            }
          },
          error: () => Swal.fire("Error", "No se pudo conectar con el servidor.", "error")
        });
      });
    });

    $("#btn-cancelar, #btn-volver").on("click", function () {
      window.location.href = "dashboard_alumno.php";
    });

    // Ejecutar carga inicial
    cargarEncuesta();
  });
  </script>
</body>
</html>
