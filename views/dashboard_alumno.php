<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$usuario   = $_SESSION['usuario'];
$nombre    = htmlspecialchars($usuario['nombre']   ?? '');
$apellido  = htmlspecialchars($usuario['apellido'] ?? '');
$email     = htmlspecialchars($usuario['email']    ?? '');
$rol       = $usuario['rol'] ?? '';

if ($rol !== 'alumno') {
    header("Location: dashboard_general.php");
    exit();
}

function obtener_iniciales($nombre, $apellido = '') {
    $nParts = array_values(array_filter(preg_split('/\s+/', trim($nombre))));
    $primerNombre = $nParts[0] ?? '';
    $primerApellido = ($apellido) ? explode(' ', $apellido)[0] : (end($nParts) ?: '');
    return strtoupper(mb_substr($primerNombre, 0, 1) . mb_substr($primerApellido, 0, 1));
}
$iniciales = obtener_iniciales($nombre, $apellido);
$nombreCompleto = trim($nombre . ' ' . $apellido);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard Alumno</title>

  <link rel="stylesheet" href="../css/alumno.css?v=6">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <!-- NAV -->
  <nav class="navbar">
    <div class="logo-container">
      <img src="../img/image2.png" alt="Logo" class="logo">
      <span class="titulo" id="home-link">Sistema de Tickets</span>
    </div>

    <div class="menu">
      <button id="btn-encuestas" class="nav-btn">Encuestas</button>
      <button id="btn-historial" class="nav-btn">Historial</button>
    </div>

    <div class="perfil">
      <div id="perfil-circulo" class="perfil-circulo"><?php echo $iniciales; ?></div>
      <div id="perfil-menu" class="perfil-menu oculto">
        <a href="#" id="mi-perfil">Mi perfil</a>
        <a href="logout.php">Cerrar Sesión</a>
      </div>
    </div>
  </nav>

  <main class="contenido">
    <!-- HOME -->
    <section id="home-section">
      <h2 id="saludo">Hola "<?php echo $nombre; ?>" ¿Listo para responder encuestas?</h2>
    </section>

    <!-- PERFIL (versión estilizada original) -->
<section id="perfil-section" class="perfil-section oculto">
  <div class="perfil-card">
    <div class="perfil-header">
      <span class="perfil-titulo">Información personal</span>
    </div>

    <div class="perfil-body">
      <div class="perfil-iniciales"><?php echo $iniciales; ?></div>

      <div class="perfil-datos">
        <label class="perfil-label">Nombre:</label>
        <input class="perfil-input" type="text" value="<?php echo $nombreCompleto; ?>" readonly>

        <label class="perfil-label">Correo electrónico:</label>
        <input class="perfil-input" type="text" value="<?php echo $email; ?>" readonly>

        <div class="perfil-row">
          <span class="perfil-label-inline">Cambiar contraseña</span>
          <button id="btn-editar-pass" class="btn-sec">Editar</button>
        </div>
      </div>
    </div>

    <div class="perfil-footer">
      <button id="btn-regresar" class="btn-primary">Regresar</button>
    </div>
  </div>
</section>


    <!-- ENCUESTAS -->
    <section id="encuestas-section" class="oculto">
      <h2 class="titulo-encuestas--barra">ENCUESTAS DISPONIBLES</h2>
      <div class="encuestas-filtros">
        <input type="text" id="filtroBusqueda" class="input-busqueda" placeholder="Buscar encuesta...">
        <select id="filtroTipo" class="select-filtro">
          <option value="todas">Todas</option>
          <option value="anonima">Anónima</option>
          <option value="identificada">Identificada</option>
        </select>
        <select id="filtroOrden" class="select-filtro">
          <option value="recientes">Más recientes</option>
          <option value="antiguas">Más antiguas</option>
        </select>
      </div>
      <div id="encuestas-grid" class="encuestas-grid"></div>
    </section>

    <!-- HISTORIAL -->
    <section id="historial-section" class="oculto">
      <h2 class="titulo-encuestas--barra">ENCUESTAS RESPONDIDAS</h2>
      <div id="historial-grid" class="historial-grid"></div>
    </section>
  </main>

<script>
$(function(){
  /* === Menú perfil === */
  $("#perfil-circulo").click(e=>{
    e.stopPropagation();
    $("#perfil-menu").toggleClass("oculto");
  });
  $(document).click(()=>$("#perfil-menu").addClass("oculto"));

  /* === Navegación === */
  $("#home-link").click(()=>{
    $(".nav-btn").removeClass("nav-btn--active");
    $("section").addClass("oculto");
    $("#home-section").removeClass("oculto");
  });

  $("#mi-perfil").click(e=>{
    e.preventDefault();
    $(".nav-btn").removeClass("nav-btn--active");
    $("section").addClass("oculto");
    $("#perfil-section").removeClass("oculto");
    $("#perfil-menu").addClass("oculto");
  });

  $("#btn-regresar").click(()=>{
    $("#perfil-section").addClass("oculto");
    $("#home-section").removeClass("oculto");
  });

  $("#btn-encuestas").click(function(){
    $(".nav-btn").removeClass("nav-btn--active");
    $(this).addClass("nav-btn--active");
    $("section").addClass("oculto");
    $("#encuestas-section").removeClass("oculto");
    cargarEncuestas();
  });

  $("#btn-historial").click(function(){
    $(".nav-btn").removeClass("nav-btn--active");
    $(this).addClass("nav-btn--active");
    $("section").addClass("oculto");
    $("#historial-section").removeClass("oculto");
    cargarHistorial();
  });

  /* === Encuestas DEMO === */
  function cargarEncuestas(){
    const demo = [
      {id_encuesta:101,titulo:"Encuesta de satisfacción del campus",descripcion:"Opiniones sobre la vida en el campus",visibilidad:"identificada",fecha_creacion_formateada:"25 abril 2025"},
      {id_encuesta:102,titulo:"Encuesta de opinión sobre servicios",descripcion:"Encuesta anónima sobre servicios",visibilidad:"anonima",fecha_creacion_formateada:"30 agosto 2025"},
      {id_encuesta:103,titulo:"Encuesta de actividades complementarias",descripcion:"Intereses y participación",visibilidad:"anonima",fecha_creacion_formateada:"7 julio 2025"}
    ];
    renderEncuestas(demo);
  }

  function renderEncuestas(list){
    const $grid=$("#encuestas-grid").empty();
    list.forEach(e=>{
      const id=e.id_encuesta;
      const vis=e.visibilidad==="identificada"?"Identificada":"Anónima";
      const icon=vis==="Identificada"?"📄":"👤";
      const card=`
      <article class="enc-card" data-id="${id}">
        <header><h3>${e.titulo}</h3><p>${e.descripcion}</p></header>
        <p class="meta">${icon} ${vis}</p>
        <p class="fecha">📅 ${e.fecha_creacion_formateada}</p>
        <footer><button class="btn-encuesta" data-id="${id}">Responder encuesta</button></footer>
      </article>`;
      $grid.append(card);
    });
  }

  $(document).on("click",".btn-encuesta",function(){
    const id=$(this).data("id");
    if(id)window.location.href=`responder_encuesta.php?id=${id}`;
  });

    /* === Historial DEMO === */
  function cargarHistorial(){
    const demo = [
      {
        id:201,
        titulo:"Encuesta de satisfacción del campus",
        estado:"Completada",
        fecha:"25 abril 2025",
        tipo:"Identificada",
        respuestas:[
          {pregunta:"¿Cómo calificas las instalaciones?",respuesta:"Excelente"},
          {pregunta:"¿Te sientes seguro en el campus?",respuesta:"Sí, bastante"},
          {pregunta:"¿Sugerencias?",respuesta:"Mejorar horarios de biblioteca"}
        ]
      },
      {
        id:202,
        titulo:"Encuesta de opinión de servicios",
        estado:"Completada",
        fecha:"30 agosto 2025",
        tipo:"Anónima",
        respuestas:[
          {pregunta:"¿Cómo calificas los servicios del campus?",respuesta:"Buenos"},
          {pregunta:"¿Recomendarías el servicio de comedor?",respuesta:"Sí"},
          {pregunta:"¿Comentarios adicionales?",respuesta:"Podrían ofrecer más opciones de menú"}
        ]
      }
    ];
    renderHistorial(demo);
  }

  // 🔧 AHORA el botón "Ver mis respuestas" aparece en TODAS las encuestas
  function renderHistorial(list){
    const $grid=$("#historial-grid").empty();
    list.forEach(e=>{
      const btn=`<button class="btn-ver" data-id="${e.id}">Ver mis respuestas</button>`;
      const card=`
      <article class="hist-card" data-id="${e.id}">
        <p><strong>Nombre de la encuesta:</strong> ${e.titulo}</p>
        <p><strong>Estado:</strong> ${e.estado}</p>
        <p><strong>Fecha de registro:</strong> ${e.fecha}</p>
        <p><strong>Tipo:</strong> ${e.tipo}</p>
        ${btn}
      </article>`;
      $grid.append(card);
    });
  }

  /* === Modal Ver Respuestas === */
  $(document).on("click",".btn-ver",function(){
    const id=$(this).data("id");

    // Simulación de respuestas DEMO
    const respuestasDemo = {
      201: [
        {pregunta:"¿Cómo calificas las instalaciones?",respuesta:"Excelente"},
        {pregunta:"¿Te sientes seguro en el campus?",respuesta:"Sí, bastante"},
        {pregunta:"¿Sugerencias?",respuesta:"Mejorar horarios de biblioteca"}
      ],
      202: [
        {pregunta:"¿Cómo calificas los servicios del campus?",respuesta:"Buenos"},
        {pregunta:"¿Recomendarías el servicio de comedor?",respuesta:"Sí"},
        {pregunta:"¿Comentarios adicionales?",respuesta:"Podrían ofrecer más opciones de menú"}
      ]
    }[id];

    if(!respuestasDemo){
      Swal.fire("Sin respuestas","No se encontraron respuestas para esta encuesta.","warning");
      return;
    }

    let html = "<ul style='text-align:left; list-style:none; padding:0'>";
    respuestasDemo.forEach(r=>{
      html += `<li><strong>${r.pregunta}</strong><br>${r.respuesta}</li><hr>`;
    });
    html += "</ul>";

    Swal.fire({
      title: "📋 Mis respuestas",
      html: html,
      width: 600,
      confirmButtonText: "Cerrar",
      confirmButtonColor: "#3b65f1"
    });
  });
});
/* === Cambiar contraseña (SweetAlert) === */
const toast = (icon, title, cls) => {
  Swal.fire({
    toast:true,
    position:"top-end",
    showConfirmButton:false,
    timer:2600,
    timerProgressBar:true,
    icon,
    title,
    customClass:{ popup: cls }
  });
};
const toastOk    = (m)=>toast("success",m,"swal-toast-success");
const toastError = (m)=>toast("error",m,"swal-toast-error");

$("#btn-editar-pass").on("click", function(){
  Swal.fire({
    title: "Cambiar contraseña",
    html: `
      <div class="swal-form">
        <label>Contraseña actual</label>
        <input type="password" id="pwd-actual" class="swal2-input" placeholder="••••••••" />
        <label>Nueva contraseña</label>
        <input type="password" id="pwd-nueva" class="swal2-input" placeholder="Mín. 8 caracteres" />
        <label>Confirmar contraseña</label>
        <input type="password" id="pwd-confirmar" class="swal2-input" placeholder="Repite la nueva" />
      </div>
    `,
    focusConfirm:false,
    confirmButtonText:"Cambiar contraseña",
    showCancelButton:true,
    cancelButtonText:"Cancelar",
    preConfirm:()=>{
      const actual    = $("#pwd-actual").val().trim();
      const nueva     = $("#pwd-nueva").val().trim();
      const confirmar = $("#pwd-confirmar").val().trim();
      if(!actual || !nueva || !confirmar){
        Swal.showValidationMessage("Completa los 3 campos.");
        return false;
      }
      if(nueva.length<8){
        Swal.showValidationMessage("La nueva contraseña debe tener al menos 8 caracteres.");
        return false;
      }
      if(nueva!==confirmar){
        Swal.showValidationMessage("Las contraseñas no coinciden.");
        return false;
      }
      if(nueva===actual){
        Swal.showValidationMessage("La nueva contraseña no puede ser igual a la actual.");
        return false;
      }
      return { contrasena_actual: actual, nueva_contrasena: nueva, confirmar_contrasena: confirmar };
    }
  }).then((res)=>{
    if(!res.isConfirmed || !res.value) return;
    Swal.fire({
      title:"¿Seguro que quieres cambiar la contraseña?",
      icon:"question",
      showCancelButton:true,
      confirmButtonText:"Sí",
      cancelButtonText:"No"
    }).then((conf)=>{
      if(!conf.isConfirmed){ toastError("Ocurrió un error"); return; }
      $.ajax({
        url:"../api/cambiarContrasena.php",
        method:"POST",
        contentType:"application/json",
        data: JSON.stringify(res.value),
        success:function(response){
          if(response && response.success){
            toastOk("Cambio de contraseña exitoso");
          } else {
            toastError(response?.mensaje || "No se pudo cambiar la contraseña.");
          }
        },
        error:function(){
          toastError("No se pudo cambiar la contraseña.");
        }
      });
    });
  });
});

</script>
</body>
</html>
