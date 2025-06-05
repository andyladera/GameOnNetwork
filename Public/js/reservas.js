// Public/js/reservas.js
class ReservasManager {
    constructor() {
        this.fechaActual = new Date();
        this.mesActual = this.fechaActual.getMonth();
        this.añoActual = this.fechaActual.getFullYear();
        this.meses = [
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];
        this.baseUrl = '../../Controllers/ReservaController.php';
        this.init();
    }

    init() {
        this.configurarEventos();
        this.generarCalendario();
        this.cargarProximasActividades();
        this.cargarEquiposUsuario();
    }

    configurarEventos() {
        document.getElementById('btnMesAnterior').addEventListener('click', () => {
            this.mesActual--;
            if (this.mesActual < 0) {
                this.mesActual = 11;
                this.añoActual--;
            }
            this.generarCalendario();
        });

        document.getElementById('btnMesSiguiente').addEventListener('click', () => {
            this.mesActual++;
            if (this.mesActual > 11) {
                this.mesActual = 0;
                this.añoActual++;
            }
            this.generarCalendario();
        });

        document.getElementById('btnCerrarModal').addEventListener('click', () => {
            document.getElementById('modalDia').style.display = 'none';
        });

        document.getElementById('modalDia').addEventListener('click', (e) => {
            if (e.target.id === 'modalDia') {
                document.getElementById('modalDia').style.display = 'none';
            }
        });

        document.getElementById('btnBuscarHorarios').addEventListener('click', () => {
            this.buscarHorarios();
        });
    }

    generarCalendario() {
        const grid = document.getElementById('calendarioGrid');
        const mesActualElement = document.getElementById('mesActual');
        
        mesActualElement.textContent = `${this.meses[this.mesActual]} ${this.añoActual}`;
        
        const cabeceras = grid.innerHTML.split('</div>').slice(0, 7).join('</div>') + '</div>';
        grid.innerHTML = cabeceras;
        
        const primerDia = new Date(this.añoActual, this.mesActual, 1);
        const ultimoDia = new Date(this.añoActual, this.mesActual + 1, 0);
        const diasEnMes = ultimoDia.getDate();
        const diaInicio = primerDia.getDay();
        
        const mesAnterior = new Date(this.añoActual, this.mesActual, 0);
        for (let i = diaInicio - 1; i >= 0; i--) {
            const dia = mesAnterior.getDate() - i;
            this.crearCeldaDia(dia, true, this.mesActual - 1, this.añoActual);
        }
        
        for (let dia = 1; dia <= diasEnMes; dia++) {
            this.crearCeldaDia(dia, false, this.mesActual, this.añoActual);
        }
        
        const totalCeldas = grid.children.length - 7;
        const celdasRestantes = (Math.ceil(totalCeldas / 7) * 7) - totalCeldas;
        for (let dia = 1; dia <= celdasRestantes; dia++) {
            this.crearCeldaDia(dia, true, this.mesActual + 1, this.añoActual);
        }
        
        this.cargarEventosMes();
    }

    crearCeldaDia(numeroDia, esOtroMes, mes, año) {
        const grid = document.getElementById('calendarioGrid');
        const celda = document.createElement('div');
        celda.className = 'dia-celda';
        
        if (esOtroMes) {
            celda.classList.add('dia-otro-mes');
        }
        
        const hoy = new Date();
        if (!esOtroMes && numeroDia === hoy.getDate() && 
            mes === hoy.getMonth() && año === hoy.getFullYear()) {
            celda.classList.add('dia-hoy');
        }
        
        const fechaCelda = new Date(año, mes, numeroDia);
        celda.dataset.fecha = fechaCelda.toISOString().split('T')[0];
        
        celda.innerHTML = `
            <div class="dia-numero">${numeroDia}</div>
            <div class="dia-eventos" id="eventos-${celda.dataset.fecha}">
                <!-- Los eventos se cargan dinámicamente -->
            </div>
        `;
        
        celda.addEventListener('click', () => {
            this.mostrarDetallesDia(celda.dataset.fecha);
        });
        
        grid.appendChild(celda);
    }

    async cargarEventosMes() {
        try {
            const fechaInicio = new Date(this.añoActual, this.mesActual, 1).toISOString().split('T')[0];
            const fechaFin = new Date(this.añoActual, this.mesActual + 1, 0).toISOString().split('T')[0];
            
            const response = await fetch(`${this.baseUrl}?action=obtener_eventos_mes&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`);
            const data = await response.json();
            
            document.querySelectorAll('.dia-eventos').forEach(div => {
                div.innerHTML = '';
            });
            
            if (data.success) {
                data.data.forEach(evento => {
                    this.agregarEventoADia(evento);
                });
            }
            
        } catch (error) {
            console.error('Error cargando eventos del mes:', error);
        }
    }

    agregarEventoADia(evento) {
        const contenedorEventos = document.getElementById(`eventos-${evento.fecha}`);
        if (contenedorEventos) {
            const elementoEvento = document.createElement('div');
            elementoEvento.className = `evento-${evento.tipo}`;
            elementoEvento.textContent = evento.titulo;
            elementoEvento.title = evento.detalle;
            contenedorEventos.appendChild(elementoEvento);
        }
    }

    mostrarDetallesDia(fecha) {
        const modal = document.getElementById('modalDia');
        const titulo = document.getElementById('modalDiaTitulo');
        const contenido = document.getElementById('modalDiaContenido');
        
        const fechaObj = new Date(fecha + 'T00:00:00');
        const fechaFormateada = fechaObj.toLocaleDateString('es-PE', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        
        titulo.textContent = fechaFormateada;
        
        contenido.innerHTML = `
            <div style="color: #b0b0b0; text-align: center; padding: 40px;">
                <i class="fas fa-calendar-day" style="font-size: 3rem; margin-bottom: 20px; color: #007bff;"></i>
                <h4 style="color: #ffffff;">Detalles del día</h4>
                <p>Funcionalidad en desarrollo...</p>
                <p>Fecha seleccionada: ${fecha}</p>
            </div>
        `;
        
        modal.style.display = 'block';
    }

    async cargarProximasActividades() {
        try {
            // Cargar próximas reservas
            const responseReservas = await fetch(`${this.baseUrl}?action=obtener_proximas_reservas`);
            const dataReservas = await responseReservas.json();
            
            if (dataReservas.success) {
                let htmlReservas = '';
                if (dataReservas.data.length > 0) {
                    dataReservas.data.forEach(reserva => {
                        const fechaFormateada = new Date(reserva.fecha).toLocaleDateString('es-PE');
                        htmlReservas += `
                            <div class="reserva-item">
                                <div class="item-fecha">${fechaFormateada} - ${reserva.hora_inicio.substring(0,5)}</div>
                                <div class="item-titulo">${reserva.deporte || 'Deporte'} - ${reserva.estado}</div>
                                <div class="item-detalle">${reserva.instalacion}</div>
                            </div>
                        `;
                    });
                } else {
                    htmlReservas = '<p style="color: #b0b0b0; text-align: center;">No tienes reservas próximas</p>';
                }
                document.getElementById('proximasReservas').innerHTML = htmlReservas;
            }
            
            // Cargar próximos torneos
            const responseTorneos = await fetch(`${this.baseUrl}?action=obtener_proximos_torneos`);
            const dataTorneos = await responseTorneos.json();
            
            if (dataTorneos.success) {
                let htmlTorneos = '';
                if (dataTorneos.data.length > 0) {
                    dataTorneos.data.forEach(torneo => {
                        const fechaFormateada = new Date(torneo.fecha_partido).toLocaleDateString('es-PE');
                        const horaFormateada = new Date(torneo.fecha_partido).toLocaleTimeString('es-PE', {hour: '2-digit', minute: '2-digit'});
                        htmlTorneos += `
                            <div class="torneo-item">
                                <div class="item-fecha">${fechaFormateada} - ${horaFormateada}</div>
                                <div class="item-titulo">${torneo.torneo_nombre} - ${torneo.fase}</div>
                                <div class="item-detalle">${torneo.partido_detalle}</div>
                            </div>
                        `;
                    });
                } else {
                    htmlTorneos = '<p style="color: #b0b0b0; text-align: center;">No tienes partidos de torneo próximos</p>';
                }
                document.getElementById('proximosTorneos').innerHTML = htmlTorneos;
            }
            
        } catch (error) {
            console.error('Error cargando próximas actividades:', error);
            document.getElementById('proximasReservas').innerHTML = '<p style="color: #dc3545;">Error cargando reservas</p>';
            document.getElementById('proximosTorneos').innerHTML = '<p style="color: #dc3545;">Error cargando torneos</p>';
        }
    }

    async cargarEquiposUsuario() {
        try {
            const response = await fetch(`${this.baseUrl}?action=obtener_equipos_usuario`);
            const data = await response.json();
            
            if (data.success) {
                const selectEquipo = document.getElementById('equipoReserva');
                selectEquipo.innerHTML = '<option value="">Reserva individual</option>';
                
                data.data.forEach(equipo => {
                    selectEquipo.innerHTML += `<option value="${equipo.id}">${equipo.nombre} (${equipo.deporte})</option>`;
                });
            }
        } catch (error) {
            console.error('Error cargando equipos del usuario:', error);
        }
    }

    buscarHorarios() {
        const fecha = document.getElementById('fechaReserva').value;
        const deporte = document.getElementById('deporteReserva').value;
        const equipo = document.getElementById('equipoReserva').value;
        
        if (!fecha || !deporte) {
            alert('Por favor selecciona fecha y deporte');
            return;
        }
        
        console.log('Buscando horarios:', { fecha, deporte, equipo });
        alert('Función en desarrollo. Redirigiendo a instalaciones...');
        window.location.href = 'insdepor.php';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    window.reservasManager = new ReservasManager();
});