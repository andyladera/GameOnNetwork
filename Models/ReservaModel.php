<?php
require_once __DIR__ . '/../Config/database.php';

class ReservaModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Obtener cronograma de disponibilidad para una institución
    public function obtenerCronogramaDisponibilidad($idInstitucion, $fecha = null) {
        // Si no se especifica fecha, usar la fecha actual
        if (!$fecha) {
            $fecha = date('Y-m-d');
        }

        // Obtener información de la institución
        $sqlInstitucion = "SELECT nombre FROM instituciones_deportivas WHERE id = ?";
        $stmtInstitucion = $this->conn->prepare($sqlInstitucion);
        $stmtInstitucion->bind_param("i", $idInstitucion);
        $stmtInstitucion->execute();
        $resultadoInstitucion = $stmtInstitucion->get_result();
        $institucion = $resultadoInstitucion->fetch_assoc();

        if (!$institucion) {
            return null;
        }

        // Obtener cronograma para los próximos 7 días
        $cronogramaSemanal = [];
        for ($i = 0; $i < 7; $i++) {
            $fechaActual = date('Y-m-d', strtotime($fecha . " +$i days"));
            $nombreDia = $this->obtenerNombreDia($fechaActual);
            
            // Obtener reservas existentes para esta fecha
            $sqlReservas = "SELECT hora_inicio, hora_fin, estado FROM reservas 
                           WHERE id_institucion = ? AND fecha = ? AND estado != 'cancelada'";
            $stmtReservas = $this->conn->prepare($sqlReservas);
            $stmtReservas->bind_param("is", $idInstitucion, $fechaActual);
            $stmtReservas->execute();
            $resultadoReservas = $stmtReservas->get_result();
            $reservasExistentes = $resultadoReservas->fetch_all(MYSQLI_ASSOC);

            // Generar cronograma para este día
            $cronogramaDia = $this->generarCronogramaCompleto($reservasExistentes);
            
            $cronogramaSemanal[] = [
                'fecha' => $fechaActual,
                'nombre_dia' => $nombreDia,
                'cronograma' => $cronogramaDia
            ];
        }

        return [
            'institucion' => $institucion['nombre'],
            'fecha_inicio' => $fecha,
            'cronograma_semanal' => $cronogramaSemanal
        ];
    }

    // Obtener nombre del día en español
    private function obtenerNombreDia($fecha) {
        $dias = [
            'Monday' => 'Lunes',
            'Tuesday' => 'Martes', 
            'Wednesday' => 'Miércoles',
            'Thursday' => 'Jueves',
            'Friday' => 'Viernes',
            'Saturday' => 'Sábado',
            'Sunday' => 'Domingo'
        ];
        
        $nombreIngles = date('l', strtotime($fecha));
        return $dias[$nombreIngles] ?? $nombreIngles;
    }

    // Generar cronograma completo desde 5:00 AM hasta 12:00 AM (medianoche)
    private function generarCronogramaCompleto($reservasExistentes) {
        $cronograma = [];
        $horaInicio = new DateTime('05:00:00');
        $horaFin = new DateTime('24:00:00'); // Medianoche del mismo día
        $intervalo = new DateInterval('PT30M'); // 30 minutos

        while ($horaInicio->format('H:i:s') !== '00:00:00') {
            $horaActual = $horaInicio->format('H:i:s');
            $horaSiguiente = clone $horaInicio;
            $horaSiguiente->add($intervalo);
            
            // Si llegamos a medianoche, usar 00:00:00
            if ($horaSiguiente->format('H:i:s') === '00:00:00') {
                $horaFinIntervalo = '00:00:00';
            } else {
                $horaFinIntervalo = $horaSiguiente->format('H:i:s');
            }

            // Verificar si este intervalo está ocupado
            $ocupado = $this->verificarIntervaloOcupado($horaActual, $horaFinIntervalo, $reservasExistentes);

            $cronograma[] = [
                'hora_inicio' => $horaInicio->format('H:i'),
                'hora_fin' => $horaSiguiente->format('H:i') === '00:00' ? '00:00' : $horaSiguiente->format('H:i'),
                'disponible' => !$ocupado,
                'estado' => $ocupado ? 'ocupado' : 'disponible'
            ];

            $horaInicio->add($intervalo);
            
            // Romper el bucle si llegamos a medianoche
            if ($horaInicio->format('H:i:s') === '00:00:00') {
                break;
            }
        }

        return $cronograma;
    }

    // Verificar si un intervalo específico está ocupado
    private function verificarIntervaloOcupado($horaInicio, $horaFin, $reservasExistentes) {
        foreach ($reservasExistentes as $reserva) {
            $reservaInicio = $reserva['hora_inicio'];
            $reservaFin = $reserva['hora_fin'];

            // Verificar si hay solapamiento
            if (($horaInicio >= $reservaInicio && $horaInicio < $reservaFin) ||
                ($horaFin > $reservaInicio && $horaFin <= $reservaFin) ||
                ($horaInicio <= $reservaInicio && $horaFin >= $reservaFin)) {
                return true;
            }
        }
        return false;
    }

    // Obtener reservas de una institución (método original)
    public function obtenerReservasPorInstitucion($idInstitucion) {
        $sql = "SELECT * FROM reservas WHERE id_institucion = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $idInstitucion);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_all(MYSQLI_ASSOC);
    }
}