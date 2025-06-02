class ChatManager {
    constructor() {
        this.init();
    }
    init() {
        this.cargarEventos();
        this.cargarDatosIniciales();
    }

    cargarEventos() {
        // Eventos para modales
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-modal]')) {
                this.abrirModal(e.target.dataset.modal);
            }
            if (e.target.matches('[data-close-modal]')) {
                this.cerrarModal(e.target.dataset.closeModal);
            }
        });

        // Eventos para formularios
        document.addEventListener('submit', (e) => {
            if (e.target.matches('#formCrearEquipo')) {
                e.preventDefault();
                this.crearEquipo();
            }
            if (e.target.matches('#formBuscarAmigos')) {
                e.preventDefault();
                this.buscarUsuarios();
            }
        });

        // Eventos para botones de solicitudes
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-enviar-solicitud]')) {
                this.enviarSolicitudAmistad(e.target.dataset.enviarSolicitud);
            }
            if (e.target.matches('[data-aceptar-solicitud]')) {
                this.responderSolicitud(e.target.dataset.aceptarSolicitud, 'aceptada');
            }
            if (e.target.matches('[data-rechazar-solicitud]')) {
                this.responderSolicitud(e.target.dataset.rechazarSolicitud, 'rechazada');
            }
        });

        // Evento para filtrar equipos por deporte
        document.addEventListener('change', (e) => {
            if (e.target.matches('#filtroDeporte')) {
                this.filtrarEquiposPorDeporte(e.target.value);
            }
        });

        // Búsqueda en tiempo real
        document.addEventListener('input', (e) => {
            if (e.target.matches('#busquedaAmigos')) {
                this.buscarUsuariosEnTiempoReal(e.target.value);
            }
        });

        // Cerrar modal al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('custom-modal')) {
                e.target.classList.remove('active');
                document.body.style.overflow = 'auto';
            }
        });
    }

    async cargarDatosIniciales() {
        await this.cargarAmigos();
        await this.cargarEquipos();
        await this.cargarSolicitudesPendientes();
        await this.cargarDeportes();
    }

    // ========== GESTIÓN DE MODALES ==========
    
    abrirModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');  // ✅ USAR CLASE ACTIVE
            document.body.style.overflow = 'hidden';
        }
    }

    cerrarModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');  // ✅ USAR CLASE ACTIVE
            document.body.style.overflow = 'auto';
        }
    }

    // ========== GESTIÓN DE AMIGOS ==========
    
    async cargarAmigos() {
        try {
            const response = await fetch('../Controllers/ChatController.php?action=obtener_amigos');
            const data = await response.json();
            
            if (data.success) {
                this.mostrarAmigos(data.amigos);
            } else {
                this.mostrarError('Error al cargar amigos: ' + data.message);
            }
        } catch (error) {
            this.mostrarError('Error de conexión al cargar amigos');
        }
    }

    mostrarAmigos(amigos) {
        const container = document.getElementById('listaAmigos');
        if (!container) return;

        if (amigos.length === 0) {
            container.innerHTML = '<p class="text-muted">No tienes amigos agregados aún.</p>';
            return;
        }

        let html = '<div class="list-group">';
        amigos.forEach(amigo => {
            html += `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${amigo.nombre} ${amigo.apellidos}</strong>
                        <small class="text-muted d-block">@${amigo.username}</small>
                    </div>
                    <button class="btn btn-sm btn-primary" onclick="chatManager.iniciarChatPrivado(${amigo.amigo_id})">
                        Chat
                    </button>
                </div>
            `;
        });
        html += '</div>';

        container.innerHTML = html;
    }

    async enviarSolicitudAmistad(receptorId) {
        try {
            const response = await fetch('../Controllers/ChatController.php?action=enviar_solicitud', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    receptor_id: parseInt(receptorId)
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.mostrarExito(data.message);
                // Limpiar búsqueda
                document.getElementById('busquedaAmigos').value = '';
                document.getElementById('resultadosBusqueda').innerHTML = '';
            } else {
                this.mostrarError(data.message);
            }
        } catch (error) {
            this.mostrarError('Error de conexión al enviar solicitud');
        }
    }

    async cargarSolicitudesPendientes() {
        try {
            const response = await fetch('../Controllers/ChatController.php?action=solicitudes_pendientes');
            const data = await response.json();
            
            if (data.success) {
                this.mostrarSolicitudesPendientes(data.solicitudes);
                this.actualizarContadorSolicitudes(data.solicitudes.length);
            } else {
                this.mostrarError('Error al cargar solicitudes: ' + data.message);
            }
        } catch (error) {
            this.mostrarError('Error de conexión al cargar solicitudes');
        }
    }

    mostrarSolicitudesPendientes(solicitudes) {
        const container = document.getElementById('solicitudesPendientes');
        if (!container) return;

        if (solicitudes.length === 0) {
            container.innerHTML = '<p class="text-muted">No tienes solicitudes pendientes.</p>';
            return;
        }

        let html = '<div class="list-group">';
        solicitudes.forEach(solicitud => {
            const fechaSolicitud = new Date(solicitud.fecha_solicitud).toLocaleDateString();
            html += `
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${solicitud.nombre} ${solicitud.apellidos}</strong>
                            <small class="text-muted d-block">@${solicitud.username}</small>
                            <small class="text-muted">Enviada: ${fechaSolicitud}</small>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-success me-2" data-aceptar-solicitud="${solicitud.id}">
                                Aceptar
                            </button>
                            <button class="btn btn-sm btn-danger" data-rechazar-solicitud="${solicitud.id}">
                                Rechazar
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        container.innerHTML = html;
    }

    async responderSolicitud(solicitudId, respuesta) {
        try {
            const response = await fetch('../Controllers/ChatController.php?action=responder_solicitud', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    solicitud_id: parseInt(solicitudId),
                    respuesta: respuesta
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.mostrarExito(data.message);
                await this.cargarSolicitudesPendientes();
                if (respuesta === 'aceptada') {
                    await this.cargarAmigos();
                }
            } else {
                this.mostrarError(data.message);
            }
        } catch (error) {
            this.mostrarError('Error de conexión al responder solicitud');
        }
    }

    actualizarContadorSolicitudes(cantidad) {
        const contador = document.getElementById('contadorSolicitudes');
        if (contador) {
            if (cantidad > 0) {
                contador.textContent = cantidad;
                contador.style.display = 'inline';
            } else {
                contador.style.display = 'none';
            }
        }
    }

    // ========== GESTIÓN DE EQUIPOS ==========
    
    async cargarDeportes() {
        try {
            const response = await fetch('../Controllers/ChatController.php?action=obtener_deportes');
            const data = await response.json();
            
            if (data.success) {
                this.llenarSelectDeportes(data.deportes);
            }
        } catch (error) {
            console.error('Error al cargar deportes:', error);
        }
    }

    llenarSelectDeportes(deportes) {
        const selects = document.querySelectorAll('.select-deportes');
        selects.forEach(select => {
            let html = '<option value="">Todos los deportes</option>';
            deportes.forEach(deporte => {
                html += `<option value="${deporte.id}">${deporte.nombre}</option>`;
            });
            select.innerHTML = html;
        });
    }

    async cargarEquipos(deporteId = null) {
        try {
            let url = '../Controllers/ChatController.php?action=obtener_equipos';
            if (deporteId) {
                url += `&deporte_id=${deporteId}`;
            }
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success) {
                this.mostrarEquipos(data.equipos);
            } else {
                this.mostrarError('Error al cargar equipos: ' + data.message);
            }
        } catch (error) {
            this.mostrarError('Error de conexión al cargar equipos');
        }
    }

    mostrarEquipos(equipos) {
        const container = document.getElementById('listaEquipos');
        if (!container) return;

        if (equipos.length === 0) {
            container.innerHTML = '<p class="text-muted">No tienes equipos creados aún.</p>';
            return;
        }

        let html = '<div class="row">';
        equipos.forEach(equipo => {
            const fechaCreacion = new Date(equipo.creado_en).toLocaleDateString();
            html += `
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">${equipo.nombre}</h5>
                            <p class="card-text">${equipo.descripcion || 'Sin descripción'}</p>
                            <div class="mb-2">
                                <small class="text-muted">
                                    <strong>Deporte:</strong> ${equipo.deporte}<br>
                                    <strong>Miembros:</strong> ${equipo.total_miembros}<br>
                                    <strong>Rol:</strong> ${equipo.rol}<br>
                                    <strong>Creado:</strong> ${fechaCreacion}
                                </small>
                            </div>
                            <div class="btn-group w-100">
                                <button class="btn btn-primary btn-sm" onclick="chatManager.verMiembrosEquipo(${equipo.id})">
                                    Ver Miembros
                                </button>
                                <button class="btn btn-success btn-sm" onclick="chatManager.iniciarChatEquipo(${equipo.id})">
                                    Chat
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        container.innerHTML = html;
    }

    async crearEquipo() {
        const form = document.getElementById('formCrearEquipo');
        const formData = new FormData(form);
        
        const datos = {
            nombre: formData.get('nombre'),
            descripcion: formData.get('descripcion'),
            deporte_id: parseInt(formData.get('deporte_id')),
            limite_miembros: parseInt(formData.get('limite_miembros')) || 10,
            privado: formData.get('privado') ? 1 : 0
        };

        try {
            const response = await fetch('../Controllers/ChatController.php?action=crear_equipo', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(datos)
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.mostrarExito(data.message);
                this.cerrarModal('modalCrearEquipo');
                form.reset();
                await this.cargarEquipos();
            } else {
                this.mostrarError(data.message);
            }
        } catch (error) {
            this.mostrarError('Error de conexión al crear equipo');
        }
    }

    async filtrarEquiposPorDeporte(deporteId) {
        await this.cargarEquipos(deporteId || null);
    }

    async verMiembrosEquipo(equipoId) {
        try {
            const response = await fetch(`../Controllers/ChatController.php?action=obtener_miembros&equipo_id=${equipoId}`);
            const data = await response.json();
            
            if (data.success) {
                this.mostrarMiembrosEquipo(data.miembros);
                this.abrirModal('modalMiembrosEquipo');
            } else {
                this.mostrarError(data.message);
            }
        } catch (error) {
            this.mostrarError('Error de conexión al cargar miembros');
        }
    }

    mostrarMiembrosEquipo(miembros) {
        const container = document.getElementById('listaMiembrosEquipo');
        if (!container) return;

        let html = '<div class="list-group">';
        miembros.forEach(miembro => {
            const fechaUnion = new Date(miembro.fecha_union).toLocaleDateString();
            let badgeClass = 'bg-primary';
            if (miembro.rol === 'creador') badgeClass = 'bg-success';
            else if (miembro.rol === 'administrador') badgeClass = 'bg-warning';
            
            html += `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${miembro.nombre} ${miembro.apellidos}</strong>
                        <small class="text-muted d-block">@${miembro.username}</small>
                        <small class="text-muted d-block">Se unió: ${fechaUnion}</small>
                    </div>
                    <span class="badge ${badgeClass}">${miembro.rol}</span>
                </div>
            `;
        });
        html += '</div>';
        
        container.innerHTML = html;
    }

    // ========== FUNCIONES DE CHAT (PLACEHOLDER) ==========
    
    iniciarChatPrivado(amigoId) {
        // TODO: Implementar cuando se haga la funcionalidad de chats
        this.mostrarInfo('Funcionalidad de chat privado será implementada próximamente');
    }

    iniciarChatEquipo(equipoId) {
        // TODO: Implementar cuando se haga la funcionalidad de chats
        this.mostrarInfo('Funcionalidad de chat de equipo será implementada próximamente');
    }

    // ========== UTILIDADES ==========
    
    mostrarError(mensaje) {
        this.mostrarNotificacion(mensaje, 'error');
    }

    mostrarExito(mensaje) {
        this.mostrarNotificacion(mensaje, 'success');
    }

    mostrarInfo(mensaje) {
        this.mostrarNotificacion(mensaje, 'info');
    }

    mostrarNotificacion(mensaje, tipo) {
        // Crear notificación toast
        const toast = document.createElement('div');
        toast.className = `alert alert-${tipo === 'success' ? 'success' : tipo === 'error' ? 'danger' : 'info'} position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
            ${mensaje}
            <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
        `;
        
        document.body.appendChild(toast);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 5000);
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.chatManager = new ChatManager();
});