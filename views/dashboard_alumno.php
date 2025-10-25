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
  // Variables globales para almacenar datos
  let todasLasEncuestas = [];
  let historialEncuestas = [];

  /* === Menú perfil === */
  $("#perfil-circulo").click(e=>{ e.stopPropagation(); $("#perfil-menu").toggleClass("oculto"); });
  $(document).click(()=>$("#perfil-menu").addClass("oculto"));

  /* === Navegación === */
  function navegarA(seccionId) {
    $(".nav-btn").removeClass("nav-btn--active");
    $("section").addClass("oculto");
    $(seccionId).removeClass("oculto");
  }
  $("#home-link").click(()=>navegarA("#home-section"));
  $("#mi-perfil").click(e=>{ e.preventDefault(); navegarA("#perfil-section"); $("#perfil-menu").addClass("oculto"); });
  $("#btn-regresar").click(()=>navegarA("#home-section"));
  $("#btn-encuestas").click(function(){ $(this).addClass("nav-btn--active"); navegarA("#encuestas-section"); cargarEncuestas(); });
  $("#btn-historial").click(function(){ $(this).addClass("nav-btn--active"); navegarA("#historial-section"); cargarHistorial(); });

  /* === Encuestas - Llamada a API === */
  function cargarEncuestas(forceReload = false){
      const $grid = $("#encuestas-grid");
      const $loading = $("#loading-encuestas");

      // Si ya tenemos los datos y no forzamos recarga, solo filtramos
      if (todasLasEncuestas.length > 0 && !forceReload) {
          filtrarYRenderizarEncuestas();
          return;
      }

      $grid.empty(); // Limpiar grid
      $loading.show(); // Mostrar cargando

      $.ajax({
          url: '../api/obtenerEncuestasPublicas.php',
          method: 'GET',
          dataType: 'json',
          success: function(response) {
              $loading.hide();
              if (response.success) {
                  todasLasEncuestas = response.encuestas; // Guardar datos originales
                  filtrarYRenderizarEncuestas(); // Aplicar filtros iniciales y renderizar
              } else {
                  $grid.html('<p class="grid-vacia">Error al cargar encuestas: ' + response.mensaje + '</p>');
              }
          },
          error: function() {
              $loading.hide();
              $grid.html('<p class="grid-vacia">Error de conexión al cargar encuestas.</p>');
          }
      });
  }

  // Función para filtrar y renderizar encuestas
  function filtrarYRenderizarEncuestas() {
      const $grid = $("#encuestas-grid").empty();
      const filtroTexto = $("#filtroBusqueda").val().toLowerCase();
      const filtroTipo = $("#filtroTipo").val();
      const filtroOrden = $("#filtroOrden").val();

      let encuestasFiltradas = todasLasEncuestas.filter(e => {
          // Filtrar por texto
          const textoCoincide = !filtroTexto ||
                               (e.titulo && e.titulo.toLowerCase().includes(filtroTexto)) ||
                               (e.descripcion && e.descripcion.toLowerCase().includes(filtroTexto)) ||
                               (e.encuestador_nombre && e.encuestador_nombre.toLowerCase().includes(filtroTexto));
          // Filtrar por tipo
          const tipoCoincide = filtroTipo === 'todas' || e.visibilidad === filtroTipo;

          return textoCoincide && tipoCoincide;
      });

      // Ordenar
      encuestasFiltradas.sort((a, b) => {
          const dateA = new Date(a.fecha_creacion);
          const dateB = new Date(b.fecha_creacion);
          return filtroOrden === 'recientes' ? dateB - dateA : dateA - dateB;
      });

      // Renderizar
      if (encuestasFiltradas.length === 0) {
          $grid.html('<p class="grid-vacia">No se encontraron encuestas con los filtros aplicados.</p>');
      } else {
          renderEncuestas(encuestasFiltradas);
      }
  }

  // Eventos de filtros
  $("#filtroBusqueda, #filtroTipo, #filtroOrden").on('input change', filtrarYRenderizarEncuestas);


 // Renderizar tarjetas de encuestas (Modificado para botón dinámico)
  function renderEncuestas(list){
    const $grid=$("#encuestas-grid").empty();
    if (list.length === 0 && $("#filtroBusqueda").val()) { // Mostrar mensaje si no hay resultados de búsqueda
        $grid.html('<p class="grid-vacia">No se encontraron encuestas con los filtros aplicados.</p>');
        return; // Salir temprano si no hay nada que renderizar después de filtrar
    } else if (list.length === 0) { // Mostrar mensaje si no hay encuestas en general
         $grid.html('<p class="grid-vacia">No hay encuestas disponibles en este momento.</p>');
         return;
    }

    list.forEach(e=>{
      const id=e.id_encuesta;
      const vis=e.visibilidad==="identificada"?"Identificada":"Anónima";
      const icon=vis==="Identificada"?'<i class="fa-solid fa-file-signature"></i>':'<i class="fa-solid fa-user-secret"></i>';
      const fechaFormateada = new Date(e.fecha_creacion).toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });

      // --- ✅ Lógica del Botón Dinámico ---
      let botonHtml = '';
      if (e.ya_respondida) {
          // Si ya respondió, mostrar botón "Ver mis respuestas"
          botonHtml = `<button class="btn-ver" data-id="${id}">Ver mis respuestas</button>`;
      } else {
          // Si no ha respondido, mostrar botón "Responder encuesta"
          botonHtml = `<button class="btn-encuesta" data-id="${id}">Responder encuesta</button>`;
      }
      // --- Fin Lógica Botón ---

      const card=`
      <article class="enc-card" data-id="${id}">
        <header><h3>${e.titulo}</h3><p>${e.descripcion || 'Sin descripción'}</p></header>
        <p class="meta">${icon} ${vis} ${e.visibilidad === 'identificada' ? ' - ' + (e.encuestador_nombre || 'Encuestador') : ''}</p>
        <p class="fecha"><i class="fa-solid fa-calendar-days"></i> ${fechaFormateada}</p>
        <footer>${botonHtml}</footer> </article>`;
      $grid.append(card);
    });
  }

  // Ir a responder encuesta
  $(document).on("click",".btn-encuesta",function(){
    const id=$(this).data("id");
    // Aquí puedes añadir la lógica para preguntar si quiere responder anónimo o no
    Swal.fire({
        title: '¿Cómo deseas responder?',
        text: 'Puedes responder con tu nombre o de forma anónima (si la encuesta lo permite).',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Identificado',
        cancelButtonText: 'Anónimo',
        reverseButtons: true
    }).then((result) => {
        let modo = result.isConfirmed ? 'identificado' : 'anonimo';
        // Redirigir pasando el modo como parámetro (o guardarlo en sessionStorage)
        window.location.href = `responder_encuesta.php?id=${id}&modo=${modo}`;
        // Nota: la página responder_encuesta.php deberá leer este parámetro 'modo'
    });
  });

  /* === Historial - Llamada a API === */
  function cargarHistorial(){
      const $grid = $("#historial-grid");
      const $loading = $("#loading-historial");
      $grid.empty();
      $loading.show();

      $.ajax({
          url: '../api/obtenerEncuestasRespondidas.php',
          method: 'GET',
          dataType: 'json',
          success: function(response) {
              $loading.hide();
              if (response.success) {
                  historialEncuestas = response.encuestas_respondidas; // Guardar datos
                  if (historialEncuestas.length === 0) {
                      $grid.html('<p class="grid-vacia">Aún no has respondido ninguna encuesta de forma identificada.</p>');
                  } else {
                      renderHistorial(historialEncuestas);
                  }
              } else {
                  $grid.html('<p class="grid-vacia">Error al cargar historial: ' + response.mensaje + '</p>');
              }
          },
          error: function() {
              $loading.hide();
              $grid.html('<p class="grid-vacia">Error de conexión al cargar el historial.</p>');
          }
      });
  }

  // Renderizar tarjetas de historial (Modificado para datos reales)
  function renderHistorial(list){
    const $grid=$("#historial-grid").empty();
    list.forEach(e=>{
      const fechaFormateada = new Date(e.fecha_respondida).toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });
      // El botón siempre se muestra, la API se encargará de validar
      const btn=`<button class="btn-ver" data-id="${e.id_encuesta}">Ver mis respuestas</button>`;
      const card=`
      <article class="hist-card" data-id="${e.id_encuesta}">
        <p><strong>Nombre de la encuesta:</strong> ${e.titulo}</p>
        <p><strong>Fecha respondida:</strong> ${fechaFormateada}</p>
        ${btn}
      </article>`;
      $grid.append(card);
    });
  }

  /* === Modal Ver Respuestas - Llamada a API === */
  $(document).on("click",".btn-ver",function(){
    const idEncuesta = $(this).data("id");

    Swal.fire({
        title: 'Cargando tus respuestas...',
        didOpen: () => { Swal.showLoading(); }
    });

    $.ajax({
        url: `../api/obtenerMisRespuestas.php?id_encuesta=${idEncuesta}`,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.respuestas_alumno) {
                let html = "<ul style='text-align:left; list-style:none; padding:0; max-height: 400px; overflow-y: auto;'>";
                response.respuestas_alumno.forEach(r=>{
                  html += `<li style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #eee;"><strong>${r.pregunta}</strong><br>${r.respuesta_dada || '<em>No respondida</em>'}</li>`;
                });
                html += "</ul>";

                Swal.update({ // Actualizar modal existente
                   title: "📋 Mis respuestas",
                   html: html,
                   icon: undefined, // Quitar icono de carga
                   showConfirmButton: true,
                   confirmButtonText: "Cerrar",
                   confirmButtonColor: "#3b65f1"
                });
            } else {
                Swal.fire("Sin respuestas", response.mensaje || "No se encontraron respuestas identificadas para esta encuesta.", "warning");
            }
        },
        error: function() {
            Swal.fire("Error", "No se pudieron cargar tus respuestas. Inténtalo de nuevo.", "error");
        }
    });
  });

  /* === Cambiar contraseña (SweetAlert) === */
  const toast = (icon, title) => Swal.fire({ toast:true, position:"top-end", showConfirmButton:false, timer:2600, timerProgressBar:true, icon, title });
  const toastOk = (m)=>toast("success",m);
  const toastError = (m)=>toast("error",m);

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
      focusConfirm:false,
      confirmButtonText:"Cambiar contraseña",
      showCancelButton:true,
      cancelButtonText:"Cancelar",
      preConfirm:()=>{
        const actual = $("#pwd-actual").val().trim();
        const nueva = $("#pwd-nueva").val().trim();
        const confirmar = $("#pwd-confirmar").val().trim();
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
      // No necesitamos doble confirmación aquí, preConfirm ya valida
      $.ajax({
          // ✅ URL ACTUALIZADA A LA NUEVA API
          url:"../api/cambiarMiContrasena.php",
          method:"POST",
          contentType:"application/json",
          data: JSON.stringify(res.value),
          success:function(response){
            if(response && response.success){
              toastOk("Cambio de contraseña exitoso");
            } else {
              // Mostrar error específico de la API
              Swal.showValidationMessage(response?.mensaje || "No se pudo cambiar la contraseña.");
              // Para que el modal no se cierre y muestre el error:
              return false;
            }
          },
          error:function(){
            // Error de conexión
            Swal.showValidationMessage("Error de conexión. No se pudo cambiar la contraseña.");
            return false;
          }
      });
    });
  });

  // Cargar encuestas al inicio (si no estamos en perfil)
  if (!$("#perfil-section").is(":visible")) {
      $("#btn-encuestas").trigger('click');
  }

});
</script>
</body>
</html>