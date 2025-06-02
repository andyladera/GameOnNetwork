<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'deportista') {
    header("Location: ../Auth/login.php");
    exit();
}
require_once '../../Controllers/InsDeporController.php';
$insDeporController = new InsDeporController();
$instalaciones = $insDeporController->getInstalacionesCompletas();

include_once 'header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4">Instalaciones Deportivas</h2>
    
    <!-- mapa de instalaciones -->
    <div class="dashboard-wide-card">
        <h2>MAPA DE INSTALACIONES DEPORTIVAS</h2>
        <div id="map" style="height: 400px; width:100%;"></div>
    </div>

    <div class="dashboard-row">
        <!-- Instalaciones Deportivas -->
        <div class="dashboard-card">
            <h2>LISTADOS DE INSTALACIONES DEPORTIVAS</h2>
            <div id="listaInstalaciones">
                <?php foreach ($instalaciones as $instalacion): ?>
                    <div class="card mb-3 instalacion-card" data-id="<?= $instalacion['id'] ?>" data-deportes="<?= implode(',', array_column($instalacion['deportes'], 'id')) ?>" data-calificacion="<?= $instalacion['calificacion'] ?>">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h2 class="card-title"><?= $instalacion['nombre'] ?></h2>
                                    <p class="card-text">
                                        <strong>Dirección:</strong> <?= $instalacion['direccion'] ?><br>
                                        <strong>Tarifa:</strong> S/. <?= number_format($instalacion['tarifa'], 2) ?><br>
                                        <strong>Contacto:</strong> <?= $instalacion['telefono'] ?> | <?= $instalacion['email'] ?><br>
                                        <strong>Deportes:</strong> 
                                        <?php 
                                        $nombresDeportes = array_column($instalacion['deportes'], 'nombre');
                                        echo ucwords(implode(', ', $nombresDeportes)); 
                                        ?>
                                    </p>
                                </div>
                                <div class="col-md-4 text-right">
                                    <div class="calificacion-container">
                                        <span class="badge badge-warning p-2">
                                            <i class="fas fa-star"></i> <?= number_format($instalacion['calificacion'], 1) ?>
                                        </span>
                                    </div>
                                    <button class="btn btn-primary btn-sm mt-2 btn-ver-horarios" data-id="<?= $instalacion['id'] ?>">Ver horarios</button>
                                    <button class="btn btn-primary btn-sm mt-2 btn-ver-cronograma" data-id="<?= $instalacion['id'] ?>">Ver cronograma</button>
                                    <button class="btn btn-primary btn-sm mt-2 btn-ver-mapa" data-lat="<?= $instalacion['latitud'] ?>" data-lng="<?= $instalacion['longitud'] ?>" data-nombre="<?= $instalacion['nombre'] ?>">Ver en mapa</button>
                                    <button class="btn btn-primary btn-sm mt-2 btn-ver-comentarios" data-id="<?= $instalacion['id'] ?>">Ver Comentarios</button>
                                    <button class="btn btn-primary btn-sm mt-2 btn-ver-imagenes" data-id="<?= $instalacion['id'] ?>">Ver Imagenes</button>
                                </div>
                            </div>
                            <div class="horarios-container" id="horarios-<?= $instalacion['id'] ?>" style="display: none;">
                                <hr>
                                <h6>Horarios de atención:</h6>
                                <div class="row">
                                    <?php foreach ($instalacion['horarios'] as $dia => $horario): ?>
                                    <div class="col-md-3 mb-2">
                                        <strong><?= $dia ?>:</strong> <?= $horario ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <h2>🏆🥋🥊🏓🎾🏸🏈🏉🎱🎳⛳⛸️⚽</h2>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="dashboard-card">
            <h2>FILTROS</h2>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="busquedaNombre">Buscar por nombre:</label>
                        <input type="text" class="form-control" id="busquedaNombre" placeholder="Nombre de la instalación">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="filtroDeporte">Filtrar por deporte:</label>
                        <select class="form-control" id="filtroDeporte">
                            <option value="">Todos los deportes</option>
                            <option value="1">Fútbol</option>
                            <option value="2">Voley</option>
                            <option value="3">Básquet</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="filtroCalificacion">Calificación mínima:</label>
                        <select class="form-control" id="filtroCalificacion">
                            <option value="0">Todas</option>
                            <option value="3">3 estrellas o más</option>
                            <option value="4">4 estrellas o más</option>
                            <option value="4.5">4.5 estrellas o más</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <button id="btnFiltrar" class="btn btn-primary">Aplicar filtros</button>
                <button id="btnCercanas" class="btn btn-primary">Instalaciones cercanas</button>
            </div>
        </div>
    </div>
</div>

<!-- Agregar este modal al final de insdepor.php, antes del footer -->
<div id="modal-horarios" class="modal-horarios">
    <div class="modal-horarios-backdrop"></div>
    <div class="modal-horarios-container">
        <div class="modal-horarios-header">
            <h3 class="modal-horarios-title">Cronograma de Disponibilidad</h3>
            <button id="modal-horarios-close" class="modal-horarios-close">&times;</button>
        </div>
        <div class="modal-horarios-body">
            <div class="modal-horarios-content">
                <!-- El contenido se llenará dinámicamente -->
            </div>
        </div>
    </div>
</div>

<script>
    let map;
    let markers = [];
    let infoWindow;
    let facilities = [
        <?php foreach ($instalaciones as $instalacion): ?>
        {
            position: { lat: <?= $instalacion['latitud'] ?>, lng: <?= $instalacion['longitud'] ?> },
            name: "<?= $instalacion['nombre'] ?>",
            type: "<?= ucwords(implode(', ', array_column($instalacion['deportes'], 'nombre'))) ?>",
            id: <?= $instalacion['id'] ?>,
            tarifa: "S/. <?= number_format($instalacion['tarifa'], 2) ?>",
            calificacion: <?= $instalacion['calificacion'] ?>
        },
        <?php endforeach; ?>
    ];
    
    // Inicializar el mapa de Google
    function initMap() {
        // Coordenadas predeterminadas (Tacna, Perú)
        const defaultLocation = { lat: -18.0066, lng: -70.2463 };
        
        // Opciones del mapa
        const mapOptions = {
            zoom: 14,
            center: defaultLocation,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            styles: [
                { elementType: "geometry", stylers: [{ color: "#242f3e" }] },
                { elementType: "labels.text.stroke", stylers: [{ color: "#242f3e" }] },
                { elementType: "labels.text.fill", stylers: [{ color: "#746855" }] },
                {
                    featureType: "administrative.locality",
                    elementType: "labels.text.fill",
                    stylers: [{ color: "#d59563" }],
                },
                {
                    featureType: "poi",
                    elementType: "labels.text.fill",
                    stylers: [{ color: "#d59563" }],
                },
                {
                    featureType: "poi.park",
                    elementType: "geometry",
                    stylers: [{ color: "#263c3f" }],
                },
                {
                    featureType: "poi.park",
                    elementType: "labels.text.fill",
                    stylers: [{ color: "#6b9a76" }],
                },
                {
                    featureType: "road",
                    elementType: "geometry",
                    stylers: [{ color: "#38414e" }],
                },
                {
                    featureType: "road",
                    elementType: "geometry.stroke",
                    stylers: [{ color: "#212a37" }],
                },
                {
                    featureType: "road",
                    elementType: "labels.text.fill",
                    stylers: [{ color: "#9ca5b3" }],
                },
                {
                    featureType: "road.highway",
                    elementType: "geometry",
                    stylers: [{ color: "#746855" }],
                },
                {
                    featureType: "road.highway",
                    elementType: "geometry.stroke",
                    stylers: [{ color: "#1f2835" }],
                },
                {
                    featureType: "road.highway",
                    elementType: "labels.text.fill",
                    stylers: [{ color: "#f3d19c" }],
                },
                {
                    featureType: "transit",
                    elementType: "geometry",
                    stylers: [{ color: "#2f3948" }],
                },
                {
                    featureType: "transit.station",
                    elementType: "labels.text.fill",
                    stylers: [{ color: "#d59563" }],
                },
                {
                    featureType: "water",
                    elementType: "geometry",
                    stylers: [{ color: "#17263c" }],
                },
                {
                    featureType: "water",
                    elementType: "labels.text.fill",
                    stylers: [{ color: "#515c6d" }],
                },
                {
                    featureType: "water",
                    elementType: "labels.text.stroke",
                    stylers: [{ color: "#17263c" }],
                },
            ]
        };
        
        // Crear el mapa
        map = new google.maps.Map(document.getElementById("map"), mapOptions);
        infoWindow = new google.maps.InfoWindow();
        
        // Intentar obtener la ubicación del usuario
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                    };
                    
                    // Centrar el mapa en la ubicación del usuario
                    map.setCenter(userLocation);
                    
                    // Agregar marcador del usuario
                    userMarker = new google.maps.Marker({
                        position: userLocation,
                        map: map,
                        title: "Tu ubicación",
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 10,
                            fillColor: "#00bcd4",
                            fillOpacity: 1,
                            strokeColor: "#ffffff",
                            strokeWeight: 2,
                        },
                    });
                    
                    // Agregar los marcadores de instalaciones
                    addFacilityMarkers();
                },
                () => {
                    // En caso de error, usar ubicación por defecto
                    handleLocationError(true, infoWindow, map.getCenter());
                    addFacilityMarkers();
                }
            );
        } else {
            // El navegador no soporta geolocalización
            handleLocationError(false, infoWindow, map.getCenter());
            addFacilityMarkers();
        }
    }
    
    // Manejar errores de geolocalización
    function handleLocationError(browserHasGeolocation, infoWindow, pos) {
        infoWindow.setPosition(pos);
        infoWindow.setContent(
            browserHasGeolocation
                ? "Error: El servicio de geolocalización falló."
                : "Error: Tu navegador no soporta geolocalización."
        );
        infoWindow.open(map);
    }
    
    // Agregar marcadores de instalaciones deportivas
    function addFacilityMarkers() {
        facilities.forEach((facility) => {
            const marker = new google.maps.Marker({
                position: facility.position,
                map: map,
                title: facility.name,
                // Si tienes íconos personalizados, descomenta esta línea
                // icon: facility.icon
            });
            
            markers.push(marker);
            
            marker.addListener("click", () => {
                infoWindow.setContent(
                    `<div class="info-window">
                        <h3>${facility.name}</h3>
                        <p>${facility.type}</p>
                        <p>Tarifa: ${facility.tarifa}</p>
                        <p>Calificación: ${facility.calificacion.toFixed(1)} <i class="fas fa-star text-warning"></i></p>
                        <button onclick="verInstalacion(${facility.id})" class="map-btn">Ver detalles</button>
                    </div>`
                );
                infoWindow.open(map, marker);
            });
        });
    }
    
    // Función para ver una instalación específica
    function verInstalacion(id) {
        // Scroll hasta la instalación
        const instalacion = document.querySelector(`.instalacion-card[data-id="${id}"]`);
        if (instalacion) {
            instalacion.scrollIntoView({ behavior: 'smooth', block: 'center' });
            instalacion.classList.add('highlight');
            setTimeout(() => {
                instalacion.classList.remove('highlight');
            }, 2000);
        }
    }
    
    // Evento para mostrar/ocultar horarios
    document.querySelectorAll('.btn-ver-horarios').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const horariosContainer = document.getElementById(`horarios-${id}`);
            if (horariosContainer.style.display === 'none') {
                horariosContainer.style.display = 'block';
                this.textContent = 'Ocultar horarios';
            } else {
                horariosContainer.style.display = 'none';
                this.textContent = 'Ver horarios';
            }
        });
    });
    
    // Evento para centrar el mapa en una instalación
    document.querySelectorAll('.btn-ver-mapa').forEach(btn => {
        btn.addEventListener('click', function() {
            const lat = parseFloat(this.getAttribute('data-lat'));
            const lng = parseFloat(this.getAttribute('data-lng'));
            const nombre = this.getAttribute('data-nombre');
            
            const position = { lat, lng };
            map.setCenter(position);
            map.setZoom(16);
            
            // Abrir info window en el marcador correspondiente
            for (let i = 0; i < markers.length; i++) {
                if (markers[i].getTitle() === nombre) {
                    google.maps.event.trigger(markers[i], 'click');
                    break;
                }
            }
        });
    });
    
    // Filtrar instalaciones por nombre, deporte y calificación
    document.getElementById('btnFiltrar').addEventListener('click', function() {
        const nombreBusqueda = document.getElementById('busquedaNombre').value.toLowerCase();
        const deporteSeleccionado = document.getElementById('filtroDeporte').value;
        const calificacionMinima = parseFloat(document.getElementById('filtroCalificacion').value);
        
        document.querySelectorAll('.instalacion-card').forEach(card => {
            const nombre = card.querySelector('.card-title').textContent.toLowerCase();
            const deportes = card.getAttribute('data-deportes').split(',');
            const calificacion = parseFloat(card.getAttribute('data-calificacion'));
            
            let mostrarPorNombre = true;
            let mostrarPorDeporte = true;
            let mostrarPorCalificacion = true;
            
            // Filtrar por nombre
            if (nombreBusqueda) {
                mostrarPorNombre = nombre.includes(nombreBusqueda);
            }
            
            // Filtrar por deporte
            if (deporteSeleccionado) {
                mostrarPorDeporte = deportes.includes(deporteSeleccionado);
            }
            
            // Filtrar por calificación
            mostrarPorCalificacion = calificacion >= calificacionMinima;
            
            // Mostrar u ocultar según los filtros
            if (mostrarPorNombre && mostrarPorDeporte && mostrarPorCalificacion) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });
    
    // Mostrar instalaciones cercanas
    document.getElementById('btnCercanas').addEventListener('click', function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const userLat = position.coords.latitude;
                    const userLng = position.coords.longitude;
                    
                    // Ordenar instalaciones por distancia
                    const instalacionesConDistancia = [];
                    document.querySelectorAll('.instalacion-card').forEach(card => {
                        const btn = card.querySelector('.btn-ver-mapa');
                        const lat = parseFloat(btn.getAttribute('data-lat'));
                        const lng = parseFloat(btn.getAttribute('data-lng'));
                        
                        // Calcular distancia aproximada usando la fórmula de Haversine
                        const R = 6371; // Radio de la Tierra en km
                        const dLat = (lat - userLat) * Math.PI / 180;
                        const dLng = (lng - userLng) * Math.PI / 180;
                        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                                Math.cos(userLat * Math.PI / 180) * Math.cos(lat * Math.PI / 180) * 
                                Math.sin(dLng/2) * Math.sin(dLng/2);
                        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                        const distance = R * c; // Distancia en km
                        
                        instalacionesConDistancia.push({
                            element: card,
                            distance: distance
                        });
                    });
                    
                    // Ordenar por distancia
                    instalacionesConDistancia.sort((a, b) => a.distance - b.distance);
                    
                    // Reorganizar elementos en el DOM
                    const container = document.getElementById('listaInstalaciones');
                    instalacionesConDistancia.forEach(item => {
                        container.appendChild(item.element);
                    });
                    
                    // Mostrar mensaje
                    alert('Instalaciones ordenadas por cercanía a tu ubicación actual.');
                },
                () => {
                    alert('No se pudo acceder a tu ubicación. Permite el acceso a la ubicación para usar esta función.');
                }
            );
        } else {
            alert('Tu navegador no soporta geolocalización.');
        }
    });
</script>

<script async defer
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBjRa0PWfLEyt1Ba02c-3M6zWnyEM7lU2A&callback=initMap">
</script>

<?php
// Incluir pie de página (corregida la ruta)
include_once 'footer.php';
?>