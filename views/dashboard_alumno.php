<?php
session_start();
// (PHP de seguridad y obtención de datos - Sin cambios)
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'alumno') { header("Location: login.php"); exit(); }
$usuario = $_SESSION['usuario']; $nombre = htmlspecialchars($usuario['nombre'] ?? ''); $apellido = htmlspecialchars($usuario['apellido'] ?? ''); $email = htmlspecialchars($usuario['email'] ?? '');
function obtener_iniciales($n, $a = '') { $np=array_values(array_filter(preg_split('/\s+/',trim($n)))); $pn=$np[0]??''; $pa=($a)?explode(' ',$a)[0]:(end($np)?:''); return strtoupper(mb_substr($pn,0,1).mb_substr($pa,0,1)); }
$iniciales = obtener_iniciales($nombre, $apellido); $nombreCompleto = trim($nombre . ' ' . $apellido);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard Alumno</title>

  <link rel="stylesheet" href="../css/alumno.css?v=7"> <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
      /* Estilos adicionales para loading y mensajes */
      #loading-encuestas, #loading-historial { text-align: center; padding: 40px; font-size: 1.2em; color: #666; }
      .grid-vacia { text-align: center; padding: 40px; color: #888; border: 1px dashed #ccc; border-radius: 8px; margin-top: 20px;}
      /* Estilos para el modal de cambio de contraseña */
      .swal-form label { display: block; text-align: left; margin-top: 10px; margin-bottom: 3px; font-weight: 500; font-size: 0.95em; }

      /* --- NUEVO: Estilos para el Modal de Ver Respuestas (Alumno) --- */
      .swal-container-alumno-respuestas {
          text-align: left;
          max-height: 60vh; /* Altura máxima del modal, permite scroll */
          overflow-y: auto;
          padding: 10px 15px;
          background-color: #f8f9fa; /* Fondo ligeramente diferente */
          border-radius: 8px;
          border: 1px solid #e0e0e0;
      }
      .swal-pregunta-block {
          background: #fff;
          border: 1px solid #ddd;
          border-left: 5px solid #007bff; /* Borde azul para destacar */
          border-radius: 8px;
          padding: 15px 20px;
          margin-bottom: 20px;
          box-shadow: 0 1px 3px rgba(0,0,0,0.08);
      }
      .swal-pregunta-block h4 {
          margin-top: 0;
          color: #333;
          font-size: 1.1em;
          margin-bottom: 15px;
          border-bottom: 1px dashed #eee;
          padding-bottom: 10px;
      }
      .swal-opcion-display {
          display: flex;
          align-items: center;
          margin-bottom: 10px;
          font-size: 0.95em;
          color: #555;
      }
      .swal-opcion-display i {
          margin-right: 10px;
          width: 20px; /* Ancho fijo para iconos */
          text-align: center;
          font-size: 1.1em;
      }
      .swal-opcion-display.selected {
          font-weight: bold;
          color: #007bff; /* Color para la opción seleccionada */
      }
      .swal-opcion-display.selected i {
          color: #007bff;
      }
      .swal-opcion-display:not(.selected) i {
          color: #ccc; /* Color para opciones no seleccionadas */
      }
      .swal-respuesta-abierta-display {
          background: #e9ecef;
          border: 1px solid #ced4da;
          border-radius: 5px;
          padding: 10px;
          margin-top: 5px;
          font-style: italic;
          color: #333;
          word-wrap: break-word; /* Romper palabras largas */
          white-space: pre-wrap; /* Mantener saltos de línea y espacios */
      }
      .swal-no-respuesta {
          color: #999;
          font-style: italic;
          margin-top: 5px;
      }
      .swal-title-response-alumno {
          color: #333;
          margin-bottom: 15px;
          font-size: 1.4em;
      }
      .swal-header-response-alumno {
          background-color: #f0f8ff; /* Fondo claro para el header del modal */
          padding: 15px 20px;
          border-bottom: 1px solid #e0e0e0;
          margin: -20px -20px 20px -20px; /* Ajuste para el padding del swal */
          border-top-left-radius: 5px;
          border-top-right-radius: 5px;
      }
  </style>
</head>
<body>
  <nav class="navbar"> <div class="logo-container"><img src="../img/image2.png" alt="Logo" class="logo"><span class="titulo" id="home-link">Sistema de Tickets</span></div> <div class="menu"><button id="btn-encuestas" class="nav-btn">Encuestas</button><button id="btn-historial" class="nav-btn">Historial</button></div> <div class="perfil"><div id="perfil-circulo" class="perfil-circulo"><?php echo $iniciales; ?></div><div id="perfil-menu" class="perfil-menu oculto"><a href="#" id="mi-perfil">Mi perfil</a><a href="logout.php">Cerrar Sesión</a></div></div> </nav>

  <main class="contenido">
    <section id="home-section"> <h2 id="saludo">Hola "<?php echo $nombre; ?>" ¿Listo para responder encuestas?</h2> </section>

    <section id="perfil-section" class="perfil-section oculto"> <div class="perfil-card"> <div class="perfil-header"><span class="perfil-titulo">Información personal</span></div> <div class="perfil-body"><div class="perfil-iniciales"><?php echo $iniciales; ?></div> <div class="perfil-datos"><label class="perfil-label">Nombre:</label><input class="perfil-input" type="text" value="<?php echo $nombreCompleto; ?>" readonly><label class="perfil-label">Correo electrónico:</label><input class="perfil-input" type="text" value="<?php echo $email; ?>" readonly><div class="perfil-row"><span class="perfil-label-inline">Cambiar contraseña</span><button id="btn-editar-pass" class="btn-sec">Editar</button></div></div></div> <div class="perfil-footer"><button id="btn-regresar" class="btn-primary">Regresar</button></div> </div> </section>

    <section id="encuestas-section" class="oculto"> <h2 class="titulo-encuestas--barra">ENCUESTAS DISPONIBLES</h2> <div class="encuestas-filtros"><input type="text" id="filtroBusqueda" class="input-busqueda" placeholder="Buscar por título, descripción o encuestador..."><select id="filtroTipo" class="select-filtro"><option value="todas">Todas</option><option value="anonima">Anónima</option><option value="identificada">Identificada</option></select><select id="filtroOrden" class="select-filtro"><option value="recientes">Más recientes</option><option value="antiguas">Más antiguas</option></select></div> <div id="encuestas-grid" class="encuestas-grid"><div id="loading-encuestas"><i class="fa-solid fa-spinner fa-spin"></i> Cargando encuestas...</div></div> </section>

    <section id="historial-section" class="oculto"> <h2 class="titulo-encuestas--barra">ENCUESTAS RESPONDIDAS</h2> <div id="historial-grid" class="historial-grid"><div id="loading-historial"><i class="fa-solid fa-spinner fa-spin"></i> Cargando historial...</div></div> </section>
  </main>

<script>
$(function(){
  // Variables globales
  let todasLasEncuestas = [];
  let historialEncuestas = [];

  // Configuración Toasts
  const Toast = Swal.mixin({ toast:true, position:"top-end", showConfirmButton:false, timer:2600, timerProgressBar:true });
  const toastOk = (m)=>Toast.fire({icon:"success",title:m});
  const toastError = (m)=>Toast.fire({icon:"error",title:m});

  /* === Navegación === */
  function navegarA(seccionId) {
    if (seccionId !== "#encuestas-section" && seccionId !== "#historial-section") { $(".nav-btn").removeClass("nav-btn--active"); }
    $("section").addClass("oculto");
    $(seccionId).removeClass("oculto");
  }
  $("#home-link").click(()=>navegarA("#home-section"));
  $("#mi-perfil").click(e=>{ e.preventDefault(); navegarA("#perfil-section"); $("#perfil-menu").addClass("oculto"); });
  $("#btn-regresar").click(()=>navegarA("#home-section"));
  $("#btn-encuestas").click(function(){ $(this).addClass("nav-btn--active"); navegarA("#encuestas-section"); cargarEncuestas(); });
  $("#btn-historial").click(function(){ $(this).addClass("nav-btn--active"); navegarA("#historial-section"); cargarHistorial(); });
  $("#perfil-circulo").click(e=>{ e.stopPropagation(); $("#perfil-menu").toggleClass("oculto"); });
  $(document).click(()=>$("#perfil-menu").addClass("oculto"));

  /* === Encuestas - Cargar y Filtrar === */
  function cargarEncuestas(forceReload = false){
      const $grid = $("#encuestas-grid");
      const $loading = $("#loading-encuestas");
      if (todasLasEncuestas.length > 0 && !forceReload) { filtrarYRenderizarEncuestas(); return; }
      $grid.empty(); $loading.show();
      $.ajax({
          url: '../api/obtenerEncuestasPublicas.php', method: 'GET', dataType: 'json',
          success: function(response) {
              $loading.hide();
              if (response.success && response.encuestas) { todasLasEncuestas = response.encuestas; filtrarYRenderizarEncuestas(); }
              else { $grid.html('<p class="grid-vacia">Error al cargar: ' + (response.mensaje || 'Error desconocido') + '</p>'); }
          },
          error: function() { $loading.hide(); $grid.html('<p class="grid-vacia">Error de conexión.</p>'); }
      });
  }
  function filtrarYRenderizarEncuestas() {
      const $grid = $("#encuestas-grid").empty();
      const filtroTexto = $("#filtroBusqueda").val().toLowerCase();
      const filtroTipo = $("#filtroTipo").val();
      const filtroOrden = $("#filtroOrden").val();
      let encuestasFiltradas = todasLasEncuestas.filter(e => {
          const textoCoincide = !filtroTexto || (e.titulo && e.titulo.toLowerCase().includes(filtroTexto)) || (e.descripcion && e.descripcion.toLowerCase().includes(filtroTexto)) || (e.encuestador_nombre && e.encuestador_nombre.toLowerCase().includes(filtroTexto));
          const tipoCoincide = filtroTipo === 'todas' || e.visibilidad === filtroTipo;
          return textoCoincide && tipoCoincide;
      });
      encuestasFiltradas.sort((a, b) => { const dateA = new Date(a.fecha_creacion); const dateB = new Date(b.fecha_creacion); return filtroOrden === 'recientes' ? dateB - dateA : dateA - dateB; });
      if (encuestasFiltradas.length === 0 && (filtroTexto || filtroTipo !== 'todas')) { $grid.html('<p class="grid-vacia">No se encontraron encuestas.</p>'); }
      else if (encuestasFiltradas.length === 0) { $grid.html('<p class="grid-vacia">No hay encuestas disponibles.</p>'); }
      else { renderEncuestas(encuestasFiltradas); }
  }
  $("#filtroBusqueda, #filtroTipo, #filtroOrden").on('input change', filtrarYRenderizarEncuestas);

  // Renderizar tarjetas de encuestas
  function renderEncuestas(list){
    const $grid=$("#encuestas-grid").empty();
    // ✅ Leer AMBOS localStorages
    const respondidasLocal = JSON.parse(localStorage.getItem('encuestasRespondidasLocalmente') || '{}');
    
    list.forEach(e=>{
      const id=e.id_encuesta;
      const vis=e.visibilidad==="identificada"?"Identificada":"Anónima";
      const icon=vis==="Identificada"?'<i class="fa-solid fa-file-signature"></i>':'<i class="fa-solid fa-user-secret"></i>';
      const titulo = $('<div>').text(e.titulo).html();
      const descripcion = $('<div>').text(e.descripcion || 'Sin descripción').html();
      const encuestador = $('<div>').text(e.encuestador_nombre || 'Encuestador').html();
      const fechaFormateada = new Date(e.fecha_creacion).toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });
      let botonHtml = '';
      const respondidaIdentificado = e.ya_respondida;
      const modoRespuestaLocal = respondidasLocal[id];

      if (respondidaIdentificado) {
          botonHtml = `<button class="btn-ver" data-id="${id}" data-modo="identificado" data-titulo="${titulo}">Ver mis respuestas</button>`;
      } else if (modoRespuestaLocal) { // 'identificado' o 'anonimo'
           botonHtml = `<button class="btn-ver" data-id="${id}" data-modo="${modoRespuestaLocal}" data-titulo="${titulo}">Ver mis respuestas</button>`;
      } else {
          botonHtml = `<button class="btn-encuesta" data-id="${id}" data-titulo="${titulo}">Responder encuesta</button>`;
      }
      const card=`<article class="enc-card" data-id="${id}"><header><h3>${titulo}</h3><p>${descripcion}</p></header><p class="meta">${icon} ${vis} ${e.visibilidad === 'identificada' ? ' - ' + encuestador : ''}</p><p class="fecha"><i class="fa-solid fa-calendar-days"></i> ${fechaFormateada}</p><footer>${botonHtml}</footer></article>`;
      $grid.append(card);
    });
  }
  
  // Ir a responder encuesta
  $(document).on("click",".btn-encuesta",function(){
    const idEncuesta=$(this).data("id");
    const titulo = $(this).data("titulo") || "Encuesta"; // Pasar el título
    // Ya no hay límite de 2 respuestas
    let swalOptions = { title: '¿Cómo deseas responder?', text: 'Puedes responder con tu nombre o de forma anónima (si la encuesta lo permite).', icon: 'question', showCancelButton: true, confirmButtonText: 'Identificado', cancelButtonText: 'Anónimo', reverseButtons: true };
    Swal.fire(swalOptions).then((result) => {
        let modo = result.isConfirmed ? 'identificado' : 'anonimo';
        window.location.href = `responder_encuesta.php?id=${idEncuesta}&modo=${modo}&titulo=${encodeURIComponent(titulo)}`;
    });
  });

  /* === Historial - Llamada a API === */
  function cargarHistorial(){
      const $grid = $("#historial-grid");
      const $loading = $("#loading-historial");
      $grid.empty(); $loading.show();
      $.ajax({
          url: '../api/obtenerEncuestasRespondidas.php', method: 'GET', dataType: 'json',
          success: function(response) {
              $loading.hide();
              if (response.success && response.encuestas_respondidas) {
                  historialEncuestas = response.encuestas_respondidas;
                  if (historialEncuestas.length === 0) { $grid.html('<p class="grid-vacia">Aún no has respondido ninguna encuesta de forma identificada.</p>'); }
                  else { renderHistorial(historialEncuestas); }
              } else { $grid.html('<p class="grid-vacia">Error al cargar historial: ' + response.mensaje + '</p>'); }
          },
          error: function() { $loading.hide(); $grid.html('<p class="grid-vacia">Error de conexión.</p>'); }
      });
  }
  function renderHistorial(list){
    const $grid=$("#historial-grid").empty();
    list.forEach(e=>{
      const fechaFormateada = new Date(e.fecha_respondida).toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });
      const titulo = $('<div>').text(e.titulo).html();
      const btn=`<button class="btn-ver" data-id="${e.id_encuesta}" data-modo="identificado" data-titulo="${titulo}">Ver mis respuestas</button>`;
      const card=`<article class="hist-card" data-id="${e.id_encuesta}"><p><strong>Nombre de la encuesta:</strong> ${titulo}</p><p><strong>Fecha respondida:</strong> ${fechaFormateada}</p>${btn}</article>`;
      $grid.append(card);
    });
  }

  /* === Modal Ver Respuestas (Unificado) === */
  $(document).on("click",".btn-ver",function(){
    const idEncuesta = $(this).data("id");
    const modoRespuesta = $(this).data("modo");
    const tituloEncuesta = $(this).data("titulo") || "Encuesta";

    if (modoRespuesta === 'identificado') {
        // --- Si fue identificado, llamar a la API ---
        mostrarMisRespuestasAPI(idEncuesta, tituloEncuesta);
    } else {
        // --- ✅ Si fue anónimo, leer de localStorage ---
        try {
            const cacheRespuestas = JSON.parse(localStorage.getItem('respuestasAnonimasCache') || '{}');
            const encuestaGuardada = cacheRespuestas[idEncuesta];
            
            if (encuestaGuardada && encuestaGuardada.preguntas) {
                // Encontramos los datos anónimos, construir el modal
                const htmlContenido = construirModalHtml(encuestaGuardada.preguntas);
                mostrarModalConHtml(tituloEncuesta, htmlContenido);
            } else {
                // No encontramos los datos en cache (raro, pero posible si borró caché)
                Swal.fire({ title: 'Respuesta Anónima', text: 'Respondiste anónimamente, pero no encontramos tus respuestas guardadas en este navegador.', icon: 'info', confirmButtonText: "Entendido", confirmButtonColor: "#3b65f1" });
            }
        } catch(e) {
            console.error("Error leyendo cache de respuestas anónimas:", e);
            Swal.fire("Error", "No se pudieron cargar tus respuestas anónimas guardadas.", "error");
        }
    }
  });

  /* === Función: Llamar a API para respuestas identificadas === */
  function mostrarMisRespuestasAPI(idEncuesta, tituloEncuesta) {
      Swal.fire({ title: `Cargando tus respuestas para "${tituloEncuesta}"...`, allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
      $.ajax({
          url: `../api/obtenerMisRespuestasDeEncuesta.php?id_encuesta=${idEncuesta}`,
          method: 'GET', dataType: 'json',
          success: function(response) {
              if (response.success && response.encuesta_con_respuestas && Array.isArray(response.encuesta_con_respuestas)) {
                  // ✅ Usar el constructor de HTML
                  const htmlContenido = construirModalHtml(response.encuesta_con_respuestas);
                  // ✅ Mostrar el modal
                  mostrarModalConHtml(tituloEncuesta, htmlContenido);
              } else {
                  if(response.success && !response.encuesta_con_respuestas) { Swal.fire("Sin respuestas", "No se encontraron respuestas identificadas para esta encuesta.", "warning"); }
                  else { Swal.fire("Error", response.mensaje || "No se pudieron cargar tus respuestas.", "warning"); }
              }
          },
          error: function(jqXHR) {
              let msg = "Error de conexión."; if (jqXHR.status === 403) msg = "Acceso denegado.";
              console.error("Error AJAX en mostrarMisRespuestas:", jqXHR.responseText);
              Swal.fire("Error", msg, "error");
          }
      });
  }

  /* === ✅ NUEVA: Función para CONSTRUIR el HTML del modal --- */
  function construirModalHtml(preguntas) {
      let htmlContenidoForm = '';
      let preguntaNumero = 1;
      if (preguntas.length === 0) {
           htmlContenidoForm = `<div class="swal-no-respuesta">No se encontraron preguntas.</div>`;
      } else {
          preguntas.forEach(pregunta => {
              htmlContenidoForm += `<div class="swal-pregunta-block"><h4>${preguntaNumero}. ${$('<div>').text(pregunta.texto_pregunta).html()}</h4>`;
              const respuesta = pregunta.respuesta_alumno;
              if (pregunta.tipo_pregunta === 'abierta') {
                  let textoRespuesta = '<div class="swal-no-respuesta"><em>No respondiste.</em></div>';
                  if(respuesta && respuesta.texto_respuesta_abierta) { textoRespuesta = `<div class="swal-respuesta-abierta-display">${$('<div>').text(respuesta.texto_respuesta_abierta).html()}</div>`; }
                  htmlContenidoForm += textoRespuesta;
              } 
              else if (pregunta.opciones && pregunta.opciones.length > 0) {
                  pregunta.opciones.forEach(opcion => {
                      let esSeleccionada = false;
                      if (respuesta && respuesta.opciones_seleccionadas) { esSeleccionada = respuesta.opciones_seleccionadas.includes(opcion.id_opcion); }
                      let iconClass = 'fa-regular fa-circle';
                      if (pregunta.tipo_pregunta === 'seleccion_multiple') { iconClass = 'fa-regular fa-square'; }
                      if (esSeleccionada) { iconClass = (pregunta.tipo_pregunta === 'seleccion_multiple') ? 'fa-solid fa-square-check' : 'fa-solid fa-check-circle'; }
                      const textoOpcion = $('<div>').text(opcion.texto_opcion).html();
                      htmlContenidoForm += `<div class="swal-opcion-display ${esSeleccionada ? 'selected' : ''}"><i class="${iconClass}"></i> ${textoOpcion}</div>`;
                  });
              } else { htmlContenidoForm += `<div class="swal-no-respuesta"><em>(Pregunta sin opciones)</em></div>`; }
              htmlContenidoForm += `</div>`;
              preguntaNumero++;
          });
      }
      return htmlContenidoForm;
  }
  
  /* === ✅ NUEVA: Función para MOSTRAR el modal con HTML --- */
  function mostrarModalConHtml(tituloEncuesta, contenidoHtml) {
       Swal.fire({ // Usar fire() en lugar de update()
          title: null,
          html: `
              <div class="swal-header-response-alumno">
                  <h3 class="swal-title-response-alumno">Tus respuestas para "${$('<div>').text(tituloEncuesta).html()}"</h3>
              </div>
              <div class="swal-container-alumno-respuestas">
                  ${contenidoHtml}
              </div>
          `,
          icon: undefined,
          width: '800px',
          showConfirmButton: true,
          confirmButtonText: "Cerrar"
      });
  }


  /* === Cambiar contraseña (SweetAlert) === */
  $("#btn-editar-pass").on("click", function(){
    Swal.fire({
      title: "Cambiar contraseña",
      html: `
        <div class="swal-form">
          <label>Contraseña actual</label>
          <input type="password" id="pwd-actual" class="swal2-input" placeholder="••••••••" required/>
          <label>Nueva contraseña</label>
          <input type="password" id="pwd-nueva" class="swal2-input" placeholder="Mín. 8 carac, 1 especial, termina en AL" required/>
          <label>Confirmar contraseña</label>
          <input type="password" id="pwd-confirmar" class="swal2-input" placeholder="Repite la nueva" required/>
        </div>
      `,
      focusConfirm:false, confirmButtonText:"Cambiar contraseña", showCancelButton:true, cancelButtonText:"Cancelar",
      preConfirm:()=>{
        const actual = $("#pwd-actual").val().trim(); const nueva = $("#pwd-nueva").val().trim(); const confirmar = $("#pwd-confirmar").val().trim();
        const specialCharRegex = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]+/;
        if(!actual || !nueva || !confirmar){ Swal.showValidationMessage("Completa los 3 campos."); return false; }
        if(nueva.length<8){ Swal.showValidationMessage("La nueva contraseña debe tener al menos 8 caracteres."); return false; }
        if(!nueva.toLowerCase().endsWith('al')){ Swal.showValidationMessage('La nueva contraseña debe terminar con "AL".'); return false; }
        if(!specialCharRegex.test(nueva)){ Swal.showValidationMessage('La nueva contraseña debe contener al menos un caracter especial.'); return false; }
        if(nueva!==confirmar){ Swal.showValidationMessage("Las contraseñas nuevas no coinciden."); return false; }
        if(nueva===actual){ Swal.showValidationMessage("La nueva contraseña no puede ser igual a la actual."); return false; }
        return { contrasena_actual: actual, nueva_contrasena: nueva, confirmar_contrasena: confirmar };
      }
    }).then((res)=>{
      if(!res.isConfirmed || !res.value) return;
      Swal.fire({ title: 'Cambiando contraseña...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
      $.ajax({
          url:"../api/cambiarMiContrasena.php", method:"POST", contentType:"application/json", data: JSON.stringify(res.value),
          success:function(response){
            if(response && response.success){ Swal.close(); toastOk("Cambio de contraseña exitoso"); } 
            else { Swal.fire("Error", response?.mensaje || "No se pudo cambiar la contraseña.", "error"); }
          },
          error:function(jqXHR){
             let errorMsg = "Error de conexión.";
             if(jqXHR.responseJSON && jqXHR.responseJSON.mensaje) { errorMsg = jqXHR.responseJSON.mensaje; }
             Swal.fire("Error", errorMsg, "error");
          }
      });
    });
  });

  /* === Lógica de Carga Inicial === */
  const urlParams = new URLSearchParams(window.location.search);
  const idVerRespuestas = urlParams.get('verRespuestas');
  const modoVerRespuestas = urlParams.get('modo'); // 'identificado' o 'anonimo'
  const tituloEncuestaResp = urlParams.get('titulo'); // Título de la encuesta

  if (idVerRespuestas && !isNaN(idVerRespuestas) && modoVerRespuestas) {
      setTimeout(() => {
          const titulo = tituloEncuestaResp ? decodeURIComponent(tituloEncuestaResp) : "Encuesta Respondida";
          if (modoVerRespuestas === 'identificado') {
              mostrarMisRespuestasAPI(parseInt(idVerRespuestas), titulo);
          } else {
              // Si es anónimo, leer de localStorage
               try {
                  const cacheRespuestas = JSON.parse(localStorage.getItem('respuestasAnonimasCache') || '{}');
                  const encuestaGuardada = cacheRespuestas[idVerRespuestas];
                  if (encuestaGuardada && encuestaGuardada.preguntas) {
                      const htmlContenido = construirModalHtml(encuestaGuardada.preguntas);
                      mostrarModalConHtml(titulo, htmlContenido);
                  } else {
                      Swal.fire('Error', 'No se encontraron tus respuestas anónimas guardadas en este navegador.', 'warning');
                  }
               } catch(e) {
                   Swal.fire('Error', 'No se pudieron cargar tus respuestas anónimas.', 'error');
               }
          }
      }, 500);
      history.replaceState(null, '', window.location.pathname);
  } else {
      // Cargar la vista de encuestas por defecto
      $("#btn-encuestas").trigger('click');
  }
  
}); // Fin $(function(){...});
</script>
</body>
</html>