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

<!-- Cargar CSS específico para modales -->
<link rel="stylesheet" href="../../Public/css/modales.css">

<div class="container mt-4"> 
    <h2 class="mb-4">Mis equipos</h2> 
     
    <!-- Panel de opciones de Chat --> 
    <div class="dashboard-wide-card"> 
        <h2>Opciones de Chat</h2>
        <div class="row">
            <div class="col-md-3">
                <button class="btn btn-primary w-100 mb-2" data-modal="modalCrearEquipo">
                    <i class="fas fa-plus"></i> Crear Equipo
                </button>
            </div>
            <div class="col-md-3">
                <button class="btn btn-success w-100 mb-2" data-modal="modalBuscarAmigos">
                    <i class="fas fa-user-plus"></i> Añadir Amigos
                </button>
            </div>
            <div class="col-md-3">
                <button class="btn btn-info w-100 mb-2" data-modal="modalSolicitudes">
                    <i class="fas fa-inbox"></i> Solicitudes
                    <span id="contadorSolicitudes" class="badge bg-danger ms-1" style="display: none;">0</span>
                </button>
            </div>
            <div class="col-md-3">
                <select id="filtroDeporte" class="form-select select-deportes">
                    <option value="">Filtrar por deporte</option>
                </select>
            </div>
        </div>
    </div> 
    
    <!-- Panel dividido: Amigos/Grupos y Chat --> 
    <div class="dashboard-row"> 
        <!-- Zona izquierda: AMIGOS Y GRUPOS --> 
        <div class="dashboard-card"> 
            <h3>AMIGOS Y GRUPOS</h3>
            
            <!-- Pestañas (personalizadas, no Bootstrap) -->
            <ul class="custom-tabs mb-3" id="tabsAmigosGrupos">
                <li>
                    <button class="custom-tab-link active" data-tab="tabAmigos">
                        Amigos
                    </button>
                </li>
                <li>
                    <button class="custom-tab-link" data-tab="tabEquipos">
                        Equipos
                    </button>
                </li>
            </ul>
            
            <!-- Contenido de pestañas -->
            <div class="custom-tab-content">
                <!-- Tab Amigos -->
                <div class="custom-tab-pane active" id="tabAmigos">
                    <div id="listaAmigos">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tab Equipos -->
                <div class="custom-tab-pane" id="tabEquipos">
                    <div id="listaEquipos">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> 
 
        <!-- Zona derecha: CHAT --> 
        <div class="dashboard-card"> 
            <h3>CHAT</h3>
            <div class="text-center text-muted">
                <i class="fas fa-comments fa-3x mb-3"></i>
                <p>Selecciona un amigo o equipo para iniciar una conversación</p>
                <small>La funcionalidad de chat será implementada próximamente</small>
            </div>
        </div> 
    </div> 
</div>

<!-- Modal Crear Equipo -->
<div class="custom-modal" id="modalCrearEquipo">
    <div class="custom-modal-content">
        <button class="custom-modal-close" data-close-modal="modalCrearEquipo">&times;</button>
        <h3>Crear Nuevo Equipo</h3>
        <form id="formCrearEquipo">
            <div class="mb-3">
                <label for="nombreEquipo" class="form-label">Nombre del Equipo *</label>
                <input type="text" class="form-control" id="nombreEquipo" name="nombre" required maxlength="100">
            </div>
            <div class="mb-3">
                <label for="descripcionEquipo" class="form-label">Descripción</label>
                <textarea class="form-control" id="descripcionEquipo" name="descripcion" rows="3" maxlength="500"></textarea>
            </div>
            <div class="mb-3">
                <label for="deporteEquipo" class="form-label">Deporte *</label>
                <select class="form-select select-deportes" id="deporteEquipo" name="deporte_id" required>
                    <option value="">Seleccionar deporte</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="limiteMiembros" class="form-label">Límite de Miembros</label>
                <input type="number" class="form-control" id="limiteMiembros" name="limite_miembros" 
                       min="2" max="50" value="10">
                <div class="form-text">Entre 2 y 50 miembros</div>
            </div>
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="equipoPrivado" name="privado">
                    <label class="form-check-label" for="equipoPrivado">
                        Equipo Privado
                    </label>
                    <div class="form-text">Los equipos privados requieren invitación para unirse</div>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" data-close-modal="modalCrearEquipo">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear Equipo</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Buscar Amigos -->
<div class="custom-modal" id="modalBuscarAmigos">
    <div class="custom-modal-content">
        <button class="custom-modal-close" data-close-modal="modalBuscarAmigos">&times;</button>
        <h3>Buscar y Agregar Amigos</h3>
        <form id="formBuscarAmigos" class="mb-3">
            <div class="input-group">
                <input type="text" class="form-control" id="busquedaAmigos" 
                       placeholder="Buscar por nombre, apellido o username..." minlength="2">
                <button class="btn btn-outline-secondary" type="submit">
                    <i class="fas fa-search"></i> Buscar
                </button>
            </div>
            <div class="form-text">Ingresa al menos 2 caracteres para buscar</div>
        </form>
        <div id="resultadosBusqueda"></div>
        <div class="d-flex justify-content-end">
            <button type="button" class="btn btn-secondary" data-close-modal="modalBuscarAmigos">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal Solicitudes de Amistad -->
<div class="custom-modal" id="modalSolicitudes">
    <div class="custom-modal-content">
        <button class="custom-modal-close" data-close-modal="modalSolicitudes">&times;</button>
        <h3>Solicitudes de Amistad</h3>
        <div id="solicitudesPendientes">
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-end">
            <button type="button" class="btn btn-secondary" data-close-modal="modalSolicitudes">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal Miembros del Equipo -->  
<div class="custom-modal" id="modalMiembrosEquipo">
    <div class="custom-modal-content">
        <button class="custom-modal-close" data-close-modal="modalMiembrosEquipo">&times;</button>
        <h3>Miembros del Equipo</h3>
        <div id="listaMiembrosEquipo"></div>
        <div class="d-flex justify-content-end">
            <button type="button" class="btn btn-secondary" data-close-modal="modalMiembrosEquipo">Cerrar</button>
        </div>
    </div>
</div>

<script>
console.log('Script cargado correctamente');

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado');
    
    // Función simple para abrir modal
    function abrirModal(modalId) {
        console.log('Intentando abrir modal:', modalId);
        const modal = document.getElementById(modalId);
        if (modal) {
            console.log('Modal encontrado, aplicando clase active');
            modal.classList.add('active');
            modal.style.display = 'flex'; // Forzar display por si acaso
        } else {
            console.log('Modal NO encontrado:', modalId);
        }
    }
    
    // Función para cerrar modal
    function cerrarModal(modalId) {
        console.log('Cerrando modal:', modalId);
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            modal.style.display = 'none';
        }
    }
    
    // Event listener para botones de abrir modal
    document.addEventListener('click', function(e) {
        console.log('Click detectado en:', e.target);
        
        if (e.target.hasAttribute('data-modal')) {
            e.preventDefault();
            const modalId = e.target.getAttribute('data-modal');
            console.log('Botón de modal encontrado, ID:', modalId);
            abrirModal(modalId);
        }
        
        if (e.target.hasAttribute('data-close-modal')) {
            e.preventDefault();
            const modalId = e.target.getAttribute('data-close-modal');
            console.log('Botón de cerrar modal encontrado, ID:', modalId);
            cerrarModal(modalId);
        }
    });
    
    // Pestañas
    document.querySelectorAll('.custom-tab-link').forEach(btn => {
        btn.addEventListener('click', function() {
            console.log('Tab clickeado:', this.dataset.tab);
            document.querySelectorAll('.custom-tab-link').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.custom-tab-pane').forEach(tab => tab.classList.remove('active'));
            this.classList.add('active');
            document.getElementById(this.dataset.tab).classList.add('active');
        });
    });
    
    // Verificar que los modales existen
    const modales = ['modalCrearEquipo', 'modalBuscarAmigos', 'modalSolicitudes', 'modalMiembrosEquipo'];
    modales.forEach(id => {
        const modal = document.getElementById(id);
        console.log('Modal', id, modal ? 'EXISTE' : 'NO EXISTE');
    });
    
    // Verificar botones
    const botones = document.querySelectorAll('[data-modal]');
    console.log('Botones encontrados:', botones.length);
    botones.forEach(btn => {
        console.log('Botón:', btn.textContent.trim(), 'Modal:', btn.getAttribute('data-modal'));
    });
});
</script>

<?php 
include_once 'footer.php'; 
?>