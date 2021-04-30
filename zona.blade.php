<!DOCTYPE html>
<html lang="en">
<head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <meta http-equiv="X-UA-Compatible" content="ie=edge">
     <link rel="apple-touch-icon" href="apple-touch-icon.jpg">

     <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
     <link rel="stylesheet" href="{{ asset('css/bootstrap-theme.min.css') }}">
     <link rel="stylesheet" href="{{ asset('css/fontAwesome.css') }}">
     <link rel="stylesheet" href="{{ asset('css/light-box.css') }}">

     <link href="https://fonts.googleapis.com/css?family=Kanit:100,200,300,400,500,600,700,800,900" rel="stylesheet">


     <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
     <script src="{{ asset('js/jquery-3.5.1.min.js') }}"></script>
     <script src="{{ asset('js/jquery-ui.min.js') }}"></script>

     <script>
               
          var parcelas = [];
          var multimedias = [];
          var contador = 0;
          var anyo_minimo = Number.MAX_VALUE;
          var anyo_maximo = Number.MIN_VALUE;
          var botonAudio;
          var botonVideo;
          var botonImagen;
          var oculto = true; //Es la variable que controla si el plano que se muestra en el fondo está oculto o no
          var parcelasOcultas = false; //Es la variable que controla si las parcelas que se muestran están ocultas o no
          var contadorZoom = 1;
          var moviendoMapa;
          var peli;

          $(document).ready(function() {

               setTimeout(function() {
                    $(".fondoLogo").slideUp(200);
               },2500)

               setTimeout(function() {
                    $("body").css("overflow-y","scroll");
               },2700)
     

     


               //Con este bucle, guardamos toda la información que nos devuelve el servidor en objetos de javascript. De esta manera, podremos montar los planos con puro javascript.
     
               @foreach ($parcelas as $parcela)

                    // ==========================================================================
                    // ======            INICIO DEL BLOQUE DE LA PARCELA {{$parcela->id}}               =======
                    // ==========================================================================
     
                    var parcela{{$parcela->id}} = new Object();
                    parcela{{$parcela->id}}.id = {{$parcela->id}};
                    parcela{{$parcela->id}}.nombre = "{{$parcela->nombre}}";
                    parcela{{$parcela->id}}.descripcion = "{{$parcela->descripcion}}";
                    parcela{{$parcela->id}}.anyo_inicio = {{$parcela->anyo_inicio}};
                    parcela{{$parcela->id}}.anyo_fin = {{$parcela->anyo_fin}};
                    parcela{{$parcela->id}}.imagen = new Image();
                    parcela{{$parcela->id}}.imagen.src = "{{asset('img/parcelas/'.$parcela->imagen)}}";
                    parcela{{$parcela->id}}.canvas = document.createElement("canvas");
                    parcela{{$parcela->id}}.ctx = parcela{{$parcela->id}}.canvas.getContext("2d");
                    parcela{{ $parcela->id }}.multimedia = [];
     
                    parcelas.push(parcela{{$parcela->id}});

                    @foreach ($multimedias as $multimedia)
                    @if ($multimedia->parcela_id == $parcela->id)
                    // Inicio del recurso multimedia
                    var multimedia{{ $multimedia->id }} = new Object();
                    multimedia{{ $multimedia->id }}.id = {{ $multimedia->id }};
                    @switch($multimedia->tipo)
                    @case('audio')
                    multimedia{{ $multimedia->id }}.url = document.createElement('audio')
                    @break;
                    @case('video')
                    multimedia{{ $multimedia->id }}.url = document.createElement('video')
                    @break;
                    @case('imagen')
                    multimedia{{ $multimedia->id }}.url = document.createElement('img')
                    @break;
                    @endswitch
                    multimedia{{ $multimedia->id }}.url.setAttribute('src',"{{asset('img/multimedia/'.$multimedia->url)}}")
                    multimedia{{ $multimedia->id }}.parcela_id = {{ $multimedia->parcela_id }};
                    multimedia{{ $multimedia->id }}.tipo = '{{ $multimedia->tipo }}';
                    multimedias.push(multimedia{{ $multimedia->id }})
                    // Fin del recurso multimedia
                    @endif
               @endforeach

                    // ==========================================================================
                    // ======              FIN DEL BLOQUE DE LA PARCELA {{$parcela->id}}                =======
                    // ==========================================================================
     
               @endforeach
              
               for (i=0; i<parcelas.length; i++) {
                    if (parcelas[i].anyo_inicio < anyo_minimo)
                         anyo_minimo = parcelas[i].anyo_inicio;
                    if (parcelas[i].anyo_fin > anyo_maximo)
                         anyo_maximo = parcelas[i].anyo_fin;
               }

               for (let i = 0; i < parcelas.length; i++) {
                   for (let j = 0; j < multimedias.length; j++) {
                       if (parcelas[i].id == multimedias[j].parcela_id) {
                           parcelas[i].multimedia.push(multimedias[j])
                       }
                   }
               }
     
               $('#sliderAnyos').attr('min',anyo_minimo);
               $('#sliderAnyos').attr('max',anyo_maximo);
               $('#sliderAnyos').attr('value',anyo_maximo);
               
               //Dibujamos la imagen de fondo del canvas (el plano actual)
               var fondo = document.createElement("img");
               fondo.setAttribute("class","zona");
               fondo.setAttribute("style","opacity: 0; transition: 0.5s all");
               fondo.setAttribute("src","{{asset('img/zonas/'.$zona->imagen_fondo)}}");
               fondo.setAttribute("id","fondoCanvas");
               document.getElementById("aquiVanLosCanvas").appendChild(fondo);

               setTimeout(function() {
                    mapear($("#sliderAnyos").attr("min"));
               },1000)

               setTimeout(function() {
                    mapear($("#sliderAnyos").attr("max"));
               },1500)
     
               $("#anyoMaximoLabel").html(anyo_maximo);
               $("#anyoMinimoLabel").html(anyo_minimo);
               //Función que dibuja los canvas en función del año seleccionado, el cual se le pasa como parámetro.
               
     
     
               // Función encargada de saber si el click se ha hecho en un canvas o en otro.
                botonAudio = document.getElementById('audio');
                botonVideo = document.getElementById('video');
                botonImagen = document.getElementById('imagen');




               // MOVIMIENTO POR EL MAPA CUANDO HAY ZOOM APLICADO
                    var canvasHeight;
                    var canvasWidth;

                    setTimeout(function() {
                         canvasHeight = $('#padreAquiVanLosCanvas').height();
                         canvasWidth = $('#padreAquiVanLosCanvas').width();
                         $('#aquiVanLosCanvas').css("width",canvasWidth);
                         $('#aquiVanLosCanvas').css("height",canvasHeight);
                    },1000)
                    

                    $('#aquiVanLosCanvas').draggable({
                    start: function() {
                         $("#ultCanvas").attr("onclick","")
                    },
                    drag: function(evt,ui)
                    {
                         $("#popup").slideUp(150);
                         var cosoDentroWidth = $(this).width()
                         var cosoDentroHeight = $(this).height()
                         
                         console.log(cosoDentroWidth + " x " + cosoDentroHeight)
                         if (ui.position.left < 0) {
                              if (cosoDentroWidth <= canvasWidth) {
                                   console.log("cumpliendo a")
                                   ui.position.left = 0;	
                              } else if (ui.position.left < -(cosoDentroWidth - canvasWidth)) {
                                   console.log("cumpliendo b")
                                   ui.position.left = -(cosoDentroWidth - canvasWidth);
                              }
                         } else if (ui.position.left+(cosoDentroWidth/2)>0) {
                              console.log("cumpliendo d")
                              ui.position.left = 0;	
                         }
                         
                         
                         if (ui.position.top < 0) {
                              if (cosoDentroHeight <= canvasHeight) {
                                   ui.position.top = 0;	
                              } else if (ui.position.top < -(cosoDentroHeight - canvasHeight)) {
                                   ui.position.top = -(cosoDentroHeight - canvasHeight);
                              }
                         } else {
                              ui.position.top = 0;	
                         }

                    }, stop: function() {
                         setTimeout(function() {
                              $("#ultCanvas").attr("onclick","verificarClick(event)")
                         },100)
                    }      
                    });

            $("#velocidadesPelicula").click(function() {
                velocidad-=50;
                if (velocidad == 50)
                    velocidad = 25;
                if (velocidad <= 0)
                    velocidad = 150;
                
                switch(velocidad) {
                    case 150:
                        $("#velocidadesPelicula").text("x1");
                    break;
                    case 100:
                        $("#velocidadesPelicula").text("x2");
                    break;
                    case 25:
                        $("#velocidadesPelicula").text("x4");
                    break;
                }
            })


          })

          function mapear(anyo_seleccionado) 
          {
               var anyosPosibles = anyo_maximo - anyo_minimo;
               var porcentaje = 100 - ((anyo_maximo - anyo_seleccionado) * 100 / anyosPosibles);

               $("#anyoSlider").css("left",porcentaje+"%")

               $("#popup").slideUp(150);
               if (contador>0) {
                    $('#ultCanvas').remove();
               }
                    
               $('#anyoSlider').html(anyo_seleccionado);
               contador++;

               

               for (let i=0; i<parcelas.length; i++) 
               {
                    parcelas[i].canvas.style.opacity = "0";
                    //parcelas[i].ctx.clearRect(0, 0, parcelas[i].canvas.width, parcelas[i].canvas.height);

                    if ((anyo_seleccionado > parcelas[i].anyo_inicio && anyo_seleccionado <= parcelas[i].anyo_fin) || (parcelas[i].anyo_inicio == anyo_minimo && anyo_seleccionado == anyo_minimo)) 
                    {
                         parcelas[i].canvas.setAttribute("class","zona");
                         parcelas[i].canvas.setAttribute("id","canvas"+anyo_seleccionado + "_" +i);
                         parcelas[i].canvas.setAttribute("data-numeroMapa",""+contador);
                         parcelas[i].canvas.setAttribute("data-nombre",""+parcelas[i].nombre);

                         if (contadorZoom > 1) {
                              parcelas[i].canvas.width = parcelas[i].imagen.width*contadorZoom;
                              parcelas[i].canvas.height = parcelas[i].imagen.height*contadorZoom;
                              parcelas[i].ctx.drawImage(parcelas[i].imagen,0,0,contadorZoom*parcelas[i].imagen.width,contadorZoom*parcelas[i].imagen.height);
                         } else {
                              parcelas[i].canvas.width = parcelas[i].imagen.width;
                              parcelas[i].canvas.height = parcelas[i].imagen.height;
                              parcelas[i].ctx.drawImage(parcelas[i].imagen,0,0);
                              
                         }

                         document.getElementById("aquiVanLosCanvas").appendChild(parcelas[i].canvas);
                         parcelas[i].canvas.style.opacity = "1";
                    }
               }

               var ultCanvas = document.createElement("canvas");
               ultCanvas.setAttribute("class","zona");
               ultCanvas.setAttribute("id","ultCanvas");
               ultCanvas.setAttribute("onclick","verificarClick(event)");
               ultCanvas.width = parcelas[0].imagen.width*contadorZoom;
               ultCanvas.height = parcelas[0].imagen.height*contadorZoom;
               document.getElementById("aquiVanLosCanvas").appendChild(ultCanvas);
               
               $(".pergamino").css("width",parcelas[0].imagen.width+400)
               $(".pergamino").css("height",parcelas[0].imagen.height+150)
               if (!(contadorZoom > 1)) {
                    $("#aquiVanLosCanvas").css("width",parcelas[0].imagen.width)
                    $("#aquiVanLosCanvas").css("height",parcelas[0].imagen.height)
               }
               
               $("#padreAquiVanLosCanvas").css("width",parcelas[0].imagen.width)
               $("#padreAquiVanLosCanvas").css("height",parcelas[0].imagen.height)


               $('#audio').removeClass('imagenRellena');
               $('#audio').attr('onclick','');
               $('#video').removeClass('imagenRellena');
               $('#video').attr('onclick','');
               $('#imagen').removeClass('imagenRellena');
               $('#imagen').attr('onclick','');
          }

          function verificarClick(event) 
          {
               $('#audio').removeClass('imagenRellena');
               $('#audio').attr('onclick','');
               $('#video').removeClass('imagenRellena');
               $('#video').attr('onclick','');
               $('#imagen').removeClass('imagenRellena');
               $('#imagen').attr('onclick','');

               var rect = document.getElementById("padreAquiVanLosCanvas").getBoundingClientRect(); 
               var x = (event.x - rect.left - parseFloat($("#aquiVanLosCanvas").css("left")));
               var y = (event.y - rect.top - parseFloat($("#aquiVanLosCanvas").css("top")));
               console.log("click en " + x + " x " + y)
               console.log(parseFloat($("#aquiVanLosCanvas").css("width")))
               var audioRelleno = false;
               var videoRelleno = false;
               var imagenRelleno = false;

               for (let i=0; i<parcelas.length; i++) 
               {
                    if (parcelas[i].ctx.getImageData(x, y, parcelas[i].canvas.width, parcelas[i].canvas.height).data[3] != 0 && parcelas[i].canvas.style.opacity != "0") {

                         //Mostramos el popup
                         $("#popup").slideUp(100);
                         setTimeout(function() {
                              $("#popup .titulo").html(parcelas[i].nombre)
                              $("#popup .descripcion").html(parcelas[i].descripcion)
                              $("#popup").css("left",event.x)
                               $("#popup").css("top",event.y+10+window.scrollY);
                               $("#popup").slideDown(150);
                         },100)
                         

                        for (let j = 0; j < parcelas[i].multimedia.length; j++) {
                            if (parcelas[i].multimedia[j].tipo == 'audio' && !audioRelleno) {
                                audioRelleno = true;
                                $('#audio').addClass('imagenRellena');
                                $('#audio').attr('data-parcela',parcelas[i].id)
                                $('#audio').attr('onclick','sacarAudioParcela(this.dataset.parcela)');
                            } else if (parcelas[i].multimedia[j].tipo == 'video' && !videoRelleno) {
                                videoRelleno = true;
                                $('#video').addClass('imagenRellena');
                                $('#video').attr('data-parcela',parcelas[i].id)
                                $('#video').attr('onclick','sacarVideoParcela(this.dataset.parcela)');
                            } else if (parcelas[i].multimedia[j].tipo == 'imagen' && !imagenRelleno) {
                                imagenRelleno = true;
                                $('#imagen').addClass('imagenRellena');
                                $('#imagen').attr('data-parcela',parcelas[i].id);
                                $('#imagen').attr('onclick','sacarImgParcela(this.dataset.parcela)');
                            }
                        }

                    }
               }

                /*for (let i = 0; i < parcelas[data-parcela].multimedia.length; i++) {
                   HAY QUE RECORRER TODAS LAS PARCELAS PARA SABER CUAL TIENE EL ID DATA-PARCELA
               }*/
          }


          function sacarAudioParcela(id) 
          {
               $("#popup").slideUp(200);
               $("#modal").html('<h3 class="tituloModal"></h3> \
                              <div id="cerrarPopup" onclick="cerrarModal()"> \
                                   <div id="equis" class="equis1"></div> \
                                   <div id="equis" class="equis2"></div> \
                              </div>')

               for (i=0; i<parcelas.length; i++) 
               {
                    if (parcelas[i].id == id) 
                    {
                         $(".tituloModal").text("Audios de '" + parcelas[i].nombre + "'");
                         for (j=0; j<parcelas[i].multimedia.length; j++) 
                         {
                              if (parcelas[i].multimedia[j].tipo == "audio") 
                              {
                                        $("#modal").html($("#modal").html() + "<audio style='margin-top: 10px; width: 100%; outline: none;' src='" + parcelas[i].multimedia[j].url.src + "' controls>")
                              }
                         }
                    }
               }

               $("#fondo").fadeIn(200);
               $("#modal").fadeIn(200);
            }
            
          function sacarVideoParcela(id) 
          {
               var contador = 0;
               $("#popup").slideUp(200);
               $("#modal").html('<h3 class="tituloModal"></h3> \
                              <div id="cerrarPopup" onclick="cerrarModal()"> \
                                   <div id="equis" class="equis1"></div> \
                                   <div id="equis" class="equis2"></div> \
                              </div>')

               for (i=0; i<parcelas.length; i++) 
               {
                    if (parcelas[i].id == id) 
                    {
                         $(".tituloModal").text("Vídeos de '" + parcelas[i].nombre + "'");
                         for (j=0; j<parcelas[i].multimedia.length; j++) 
                         {
                              if (parcelas[i].multimedia[j].tipo == "video") 
                              {
                                   if (contador % 2 == 0) {
                                        $("#modal").html($("#modal").html() + "<video controls style='margin-top: 10px; width: 48%; margin-right: 4%; outline: none;' src='" + parcelas[i].multimedia[j].url.src + "'>")
                                   } else {
                                        $("#modal").html($("#modal").html() + "<video controls style='margin-top: 10px; width: 48%;outline: none;' src='" + parcelas[i].multimedia[j].url.src + "'>")
                                   }
                                   
                                   contador = contador+1;
                              }
                         }
                    }
               }

               $("#fondo").fadeIn(200);
               $("#modal").fadeIn(200);
            }

          function sacarImgParcela(id) 
          {
               $("#popup").slideUp(200);
               $("#modal").html('<h3 class="tituloModal"></h3> \
                              <div id="cerrarPopup" onclick="cerrarModal()"> \
                                   <div id="equis" class="equis1"></div> \
                                   <div id="equis" class="equis2"></div> \
                              </div>')

               for (i=0; i<parcelas.length; i++) 
               {
                    if (parcelas[i].id == id) 
                    {
                         $(".tituloModal").text("Imágenes de '" + parcelas[i].nombre + "'");
                         for (j=0; j<parcelas[i].multimedia.length; j++) 
                         {
                              if (parcelas[i].multimedia[j].tipo == "imagen") 
                              {
                                   $("#modal").html($("#modal").html() + "<div class='imgMulti'><img src='" + parcelas[i].multimedia[j].url.src + "' onclick='ponerImagen("+ parcelas[i].multimedia[j].id +")'></div>")
                              }
                         }
                    }
               }

               $("#fondo").fadeIn(200);
               $("#modal").fadeIn(200);
          }


          function ponerImagen(id) 
          {
              for (i=0; i<parcelas.length; i++) {
                   for (j=0; j<parcelas[i].multimedia.length; j++) {
                        if (parcelas[i].multimedia[j].id == id) {
                             $("#modalImg").css("background-image","url("+parcelas[i].multimedia[j].url.src+")")
                             $("#modalImgExternal").attr("onclick","window.open('"+parcelas[i].multimedia[j].url.src+"','_blank');")
                        }
                   }
              }
              $("#modalImg").slideDown(200);
          }

          function cerrarModal() 
          {
               $("#modal").fadeOut(200);
               $("#fondo").fadeOut(200);

               $('audio,video').each(function(){
                    this.pause();
               });

          }

          function mostrarFondo() 
          {
               if (oculto) {
                    $("#fondoCanvas").css("opacity","0.6");
                    $("#mapitaEsquina").css("text-shadow","0px 0px 10px gold");
                    oculto = false;
               } else {
                    $("#fondoCanvas").css("opacity","0");
                    $("#mapitaEsquina").css("text-shadow","none");
                    oculto = true;
               }
               
          }

          function ocultarMostrarParcelas()
          {
               if (parcelasOcultas) {
                    for (i=0; i<parcelas.length; i++) {
                         parcelas[i].canvas.style.display = "block";
                         $("#ojito").css("opacity","1");
                    }
                    parcelasOcultas = false;
               } else {
                    for (i=0; i<parcelas.length; i++) {
                         parcelas[i].canvas.style.display = "none";
                         $("#ojito").css("opacity","0.7");
                    }
                    parcelasOcultas = true;
               }
          }

          function cerrarPopup() {
               $('#audio').removeClass('imagenRellena');
               $('#audio').attr('onclick','');
               $('#video').removeClass('imagenRellena');
               $('#video').attr('onclick','');
               $('#imagen').removeClass('imagenRellena');
               $('#imagen').attr('onclick','');
               $('#popup').slideUp(100)
          }


          //FUNCIONES DEL ZOOM
          function zoomIn() {
               if (contadorZoom < 2) {

                    contadorZoom = contadorZoom + 0.1;
                    $("#aquiVanLosCanvas").css("width", parseFloat($("#aquiVanLosCanvas").css("width")) * 1.1)
                    $("#aquiVanLosCanvas").css("height", parseFloat($("#aquiVanLosCanvas").css("height")) * 1.1)

                    for (i=0; i<parcelas.length; i++) {
                         parcelas[i].ctx.scale(1.1,1.1);
                    }
                    mapear($("#sliderAnyos").val());
               }
          }
          function zoomOut() {
               if (contadorZoom > 1) {
                    
                    contadorZoom = contadorZoom - 0.1;
                    $("#aquiVanLosCanvas").css("width", parseInt(parseFloat($("#aquiVanLosCanvas").css("width")) / 1.1))
                    $("#aquiVanLosCanvas").css("height", parseInt(parseFloat($("#aquiVanLosCanvas").css("height")) / 1.1))

                    for (i=0; i<parcelas.length; i++) {
                         parcelas[i].ctx.scale(1.1,1.1);
                    }
                    mapear($("#sliderAnyos").val());
               }
          }

          var velocidad = 150;

          function verPelicula() {
              $("#textoPelicula").text("Parar película");
              $("#spanQueReproduce, #spanQueReproduce span").css("text-shadow","none");
              $("#spanQueReproduce i").removeClass("fa fa-play");
              $("#spanQueReproduce i").addClass("fa fa-stop");

              var funcionPeli = function() {
                    clearInterval(peli);
                    console.log("setinetrval")
                    if (anyo<=anyo_maximo) {
                        peli = setInterval(funcionPeli,velocidad)
                         $("#sliderAnyos").val(anyo);
                         mapear(anyo)
                    } else {
                         clearInterval(peli)
                         $("#spanQueReproduce").attr("onclick","verPelicula()")
                         $("#spanQueReproduce i").removeClass("fa fa-stop");
                         $("#spanQueReproduce i").addClass("fa fa-play");
                         $("#textoPelicula").text("Ver película");
                    }
                    anyo++;
               }

               var anyo = anyo_minimo;
               $("#spanQueReproduce").attr("onclick","detenerPelicula()")
               peli = setInterval(funcionPeli,velocidad)
          }

          function detenerPelicula() {
            $("#spanQueReproduce, #spanQueReproduce span").css("text-shadow","gold 0px 0px 10px");
            $("#spanQueReproduce").attr("onclick","verPelicula()")
            $("#spanQueReproduce i").removeClass("fa fa-stop");
            $("#spanQueReproduce i").addClass("fa fa-play");
            $("#textoPelicula").text("Ver película");
            clearInterval(peli);
          }
          

     </script>
     <style>
     

     </style>

     <title>{{$zona->nombre}}</title>
</head>
<body class="plano" style="overflow:hidden">

     <div onclick="mostrarFondo()" style="position: fixed;
    width: 80px;
    right: 10px;
    top: 10px;
    cursor: pointer;
    opacity: 1;
    font-size: 40px;
    text-align: center">
          <i style="position: absolute;
    font-size: 30px;
    left: 50%;
    transform: translateX(-50%);
    top: -5px;" class="fa fa-map-marker" aria-hidden="true"></i>
          <i style="transition: 0.5s all" id="mapitaEsquina" class="fa fa-map-o" aria-hidden="true"></i>
    </div>

    <div onmouseover="ocultarMostrarParcelas()" onmouseout="ocultarMostrarParcelas()" style="position: fixed;
    width: 80px;
    right: 70px;
    top: 10px;
    cursor: pointer;
    opacity: 1;
    font-size: 40px;
    text-align: center">
          <i id="ojito" class="fa fa-eye" aria-hidden="true"></i>
    </div>

     <div id="fondo"></div>
     <div id="modal">
          
     </div>

     <div id="modalImg">
          <div id="cerrarPopupImg" onclick="$('#modalImg').slideUp(200)">
               <div id="equisImg" class="equis1"></div>
               <div id="equisImg" class="equis2"></div>
          </div>
          <i id="modalImgExternal" class="fa fa-external-link" aria-hidden="true" style="position: absolute; font-size: 25px; font-weight: 700; left: 15px; top: 15px; cursor: pointer;"></i>
     </div>

     <div id="popup">
          <div id="pestanyitaPopup"></div>
          <div id="cerrarPopup" onclick="cerrarPopup();">
               <div id="equis" class="equis1"></div>
               <div id="equis" class="equis2"></div>
          </div>
          <div class="titulo"></div>
          <div class="descripcion"></div>
     </div>



     <div class="fondoLogo">
          <div class="logo">
               <div class="logo__inner"></div>
               <div class="logo__text">LOADING</div>
          </div>
     </div>

     <div onclick="zoomIn()" style="position: fixed;
    width: 80px;
    right: 170px;
    top: 10px;
    cursor: pointer;
    opacity: 1;
    font-size: 40px;
    text-align: center">
         <i class="fa fa-search-plus" aria-hidden="true"></i>
    </div>
    <div onclick="zoomOut()" style="position: fixed;
    width: 80px;
    right: 250px;
    top: 10px;
    cursor: pointer;
    opacity: 1;
    font-size: 40px;
    text-align: center">
         <i class="fa fa-search-minus" aria-hidden="true"></i>
    </div>

     <div id="content" style="height: 100vh">
          <a href="{{route('principal.index')}}">
               <img src="{{asset('img/principal/prev.png')}}" class="prev">
          </a>
          <span class="tituloZona">{{$zona->nombre}}</span>
          <div class="fondo_imagen"></div>
          <div class="fondo_negro"></div>
          <div class="pergamino">
               <table class="tablaGeneral">
                    <tr>
                         <td rowspan="3">
                              <div id="padreAquiVanLosCanvas" style="overflow: hidden">
                                   <div id="aquiVanLosCanvas" style="zoom:1">

                                   </div>
                              </div>
                         </td>
                         <td class="lateral">
                              <i id="video" class="fa fa-video-camera imagenMultimedia imagenVacia " aria-hidden="true"></i>
                         </td>
                    </tr>
                    <tr>
                         <td class="lateral">
                              <i id="imagen" class="fa fa-camera imagenMultimedia imagenVacia" aria-hidden="true"></i>
                         </td>
                    </tr>
                    <tr>
                         <td class="lateral">
                              <i id="audio" class="fa fa-microphone imagenMultimedia imagenVacia" aria-hidden="true"></i>
                         </td>
                    </tr>
                    <tr>
                         <td colspan="2">
                              <div id="slider">
                                   
                                   <input type="range" id="sliderAnyos" step="1" oninput="mapear(this.value)"><br>
                                   <span id="anyoMinimoLabel" style="position: absolute;margin-top: -45px;left: -50px; font-weight: 700;"></span>
                                   <span id="anyoMaximoLabel" style="position: absolute;margin-top: -45px;left: calc(100% + 20px); font-weight: 700;"></span>
                                   <div style="position: relative" id="anyoSlider"></div>
                                   <div class="divPelicula">
                                        <div id="velocidadesPelicula" style="position: relative;display: inline;user-select: none;font-size: 18px; top: -6px;right: 5px;text-shadow: red 0px 0px 12px">x1</div>
                                        <span id="spanQueReproduce" onclick="verPelicula()"><i class="fa fa-play" aria-hidden="true"></i>
                                    <span id="textoPelicula" style="font-size: 20px; color:white; font-weight: 700;top: -7px;position:relative;left: 5px;">Ver película</span></span>
                                    </div>
                              </div>
                              
                         </td>
                    </tr>
               </table>
          </div>
     </div>
</html>
