<?php
session_start();

// Verificar si el usuario está autenticado como deportista
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'deportista') {
    header("Location: ../Auth/login.php");
    exit();
}

// Incluir cabecera
include_once 'header.php';
?>

<div class="dashboard-container">
    <div class="dashboard-row">
        <!-- Información Personal -->
        <div class="dashboard-card">
            <h2>Información Personal</h2>
            <div class="user-profile">
                <div class="profile-image">
                    <img src="../../Resources/logo_user.jpg" alt="Foto de perfil">
                </div>
                <div class="profile-info">
                    <h3><?php echo $_SESSION['username']; ?></h3>
                    <p>usuario@ejemplo.com</p>
                </div>
            </div>
            <button class="btn-outline">Editar Perfil</button>
        </div>

        <!-- Deportes Favoritos -->
        <div class="dashboard-card">
            <h2>Deportes Favoritos</h2>
            <div class="sports-tags">
                <span class="sport-tag">Baloncesto</span>
                <span class="sport-tag">Fútbol</span>
                <span class="sport-tag">Tenis</span>
            </div>
            <button class="btn-outline">Agregar Deportes</button>
        </div>
    </div>

    <!-- Opciones de Reserva en Tiempo Real -->
    <div class="dashboard-wide-card">
        <h2>Opciones de Reserva en Tiempo Real</h2>
        <div class="reservation-options">
            <div class="reservation-card">
                <h3>Campo de Fútbol - Green Park</h3>
                <button class="btn-primary">Reservar Ahora</button>
            </div>
            <div class="reservation-card">
                <h3>Cancha de Baloncesto - Downtown Arena</h3>
                <button class="btn-primary">Reservar Ahora</button>
            </div>
            <div class="reservation-card">
                <h3>Cancha de Tenis - Sunshine Club</h3>
                <button class="btn-primary">Reservar Ahora</button>
            </div>
        </div>
    </div>

    <!-- Instalaciones Deportivas Cercanas -->
    <div class="dashboard-wide-card">
        <h2>Instalaciones Deportivas Cercanas</h2>
        <div class="map-container">
            <div id="map"></div>
        </div>
        <!-- Lista de instalaciones cercanas -->
        <div class="nearby-facilities">
            <div class="facility-item">
                <h3>Green Park</h3>
                <p><i class="fas fa-futbol"></i> Campo de Fútbol</p>
                <p><i class="fas fa-map-marker-alt"></i> A 1.2km de distancia</p>
            </div>
            <div class="facility-item">
                <h3>Downtown Arena</h3>
                <p><i class="fas fa-basketball-ball"></i> Cancha de Baloncesto</p>
                <p><i class="fas fa-map-marker-alt"></i> A 0.8km de distancia</p>
            </div>
            <div class="facility-item">
                <h3>Sunshine Club</h3>
                <p><i class="fas fa-table-tennis"></i> Cancha de Tenis</p>
                <p><i class="fas fa-map-marker-alt"></i> A 1.5km de distancia</p>
            </div>
        </div>
    </div>

    <!-- Historia de la Reserva -->
    <div class="dashboard-wide-card">
        <h2>Historia de la Reserva</h2>
        <div class="reservation-history">
            <div class="history-card">
                <h3>Cancha de Baloncesto - Tacna Arena</h3>
                <p>Fecha: 12 de mayo, 2023</p>
            </div>
            <div class="history-card">
                <h3>Cancha de Tenis - City Sports Club</h3>
                <p>Fecha: 5 de mayo, 2023</p>
            </div>
        </div>
    </div>
</div>

<script>
    // Variables para el mapa
    let map;
    let infoWindow;
    let userMarker;
    let facilities = [
        {
            name: "Green Park",
            type: "Campo de Fútbol",
            position: { lat: -12.046374, lng: -77.042793 }, // Estas son coordenadas de ejemplo
            icon: "../../Resources/markers/football.png"
        },
        {
            name: "Downtown Arena",
            type: "Cancha de Baloncesto",
            position: { lat: -12.043822, lng: -77.047600 }, // Estas son coordenadas de ejemplo
            icon: "../../Resources/markers/basketball.png"
        },
        {
            name: "Sunshine Club",
            type: "Cancha de Tenis",
            position: { lat: -12.048952, lng: -77.035841 }, // Estas son coordenadas de ejemplo
            icon: "../../Resources/markers/tennis.png"
        }
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
            
            marker.addListener("click", () => {
                infoWindow.setContent(
                    `<div class="info-window">
                        <h3>${facility.name}</h3>
                        <p>${facility.type}</p>
                        <button onclick="reserveFacility('${facility.name}')" class="map-btn">Reservar</button>
                    </div>`
                );
                infoWindow.open(map, marker);
            });
        });
    }
    
    // Función para reservar una instalación
    function reserveFacility(facilityName) {
        console.log(`Reservando ${facilityName}`);
        // Aquí puedes agregar tu lógica para redireccionar a la página de reserva
        alert(`Reservando ${facilityName}. Esta función se implementará próximamente.`);
    }
</script>

<!-- Reemplaza TU_API_KEY con tu clave de API de Google Maps -->
<script async defer
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBjRa0PWfLEyt1Ba02c-3M6zWnyEM7lU2A&callback=initMap">
</script>

<?php
// Incluir pie de página (corregida la ruta)
include_once 'footer.php';
?>