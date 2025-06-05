<?php
// Models/TorneosModel.php
require_once '../Config/database.php';

class TorneosModel {
    private $conn;
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    public function obtenerTorneosConFiltros($filtros) {
        $sql = "SELECT 
                    t.*,
                    d.nombre as deporte_nombre,
                    id.nombre as sede_nombre,
                    id.direccion as sede_direccion,
                    id.calificacion as sede_calificacion,
                    ui.tipo_usuario,
                    CASE 
                        WHEN t.imagen_torneo IS NOT NULL THEN CONCAT('../../images_torneos/', t.imagen_torneo)
                        ELSE '../../Resources/torneo-default.png'
                    END as imagen_url,
                    CASE t.estado
                        WHEN 'proximo' THEN 'Próximo'
                        WHEN 'inscripciones_abiertas' THEN 'Inscripciones Abiertas'
                        WHEN 'inscripciones_cerradas' THEN 'Inscripciones Cerradas'
                        WHEN 'activo' THEN 'En Curso'
                        WHEN 'finalizado' THEN 'Finalizado'
                        WHEN 'cancelado' THEN 'Cancelado'
                    END as estado_texto,
                    DATEDIFF(t.fecha_inscripcion_fin, CURDATE()) as dias_restantes_inscripcion
                FROM torneos t
                INNER JOIN deportes d ON t.deporte_id = d.id
                INNER JOIN instituciones_deportivas id ON t.institucion_sede_id = id.id
                INNER JOIN usuarios_instalaciones ui ON id.usuario_instalacion_id = ui.id
                WHERE 1=1";
        
        $params = [];
        $types = "";
        
        // Filtro por deporte
        if (!empty($filtros['deporte_id'])) {
            $sql .= " AND t.deporte_id = ?";
            $params[] = $filtros['deporte_id'];
            $types .= "i";
        }
        
        // Filtro por estado
        if (!empty($filtros['estado'])) {
            $sql .= " AND t.estado = ?";
            $params[] = $filtros['estado'];
            $types .= "s";
        }
        
        // Filtro por tipo de organizador (IPD/Privado)
        if (!empty($filtros['organizador_tipo'])) {
            if ($filtros['organizador_tipo'] === 'ipd') {
                $sql .= " AND ui.tipo_usuario = 'ipd'";
            } elseif ($filtros['organizador_tipo'] === 'privado') {
                $sql .= " AND ui.tipo_usuario = 'privado'";
            }
        }
        
        // Filtro por calificación mínima
        if (!empty($filtros['calificacion_min']) && $filtros['calificacion_min'] > 0) {
            $sql .= " AND id.calificacion >= ?";
            $params[] = $filtros['calificacion_min'];
            $types .= "d";
        }
        
        // Filtro por nombre
        if (!empty($filtros['nombre'])) {
            $sql .= " AND t.nombre LIKE ?";
            $params[] = '%' . $filtros['nombre'] . '%';
            $types .= "s";
        }
        
        $sql .= " ORDER BY 
                    CASE t.estado
                        WHEN 'inscripciones_abiertas' THEN 1
                        WHEN 'proximo' THEN 2
                        WHEN 'activo' THEN 3
                        WHEN 'inscripciones_cerradas' THEN 4
                        WHEN 'finalizado' THEN 5
                        WHEN 'cancelado' THEN 6
                    END,
                    t.fecha_inicio ASC";
        
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function obtenerDetallesTorneo($torneo_id) {
        $sql = "SELECT 
                    t.*,
                    d.nombre as deporte_nombre,
                    id.nombre as sede_nombre,
                    id.direccion as sede_direccion,
                    id.telefono as sede_telefono,
                    id.calificacion as sede_calificacion,
                    ui.tipo_usuario,
                    CASE 
                        WHEN t.imagen_torneo IS NOT NULL THEN CONCAT('../../images_torneos/', t.imagen_torneo)
                        ELSE '../../Resources/torneo-default.png'
                    END as imagen_url
                FROM torneos t
                INNER JOIN deportes d ON t.deporte_id = d.id
                INNER JOIN instituciones_deportivas id ON t.institucion_sede_id = id.id
                INNER JOIN usuarios_instalaciones ui ON id.usuario_instalacion_id = ui.id
                WHERE t.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $torneo_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function obtenerEquiposInscritos($torneo_id) {
        $sql = "SELECT 
                    e.id,
                    e.nombre as equipo_nombre,
                    te.estado_inscripcion,
                    te.fecha_inscripcion,
                    COUNT(em.id) as total_miembros,
                    u.nombre as lider_nombre,
                    u.apellidos as lider_apellidos
                FROM torneos_equipos te
                INNER JOIN equipos e ON te.equipo_id = e.id
                INNER JOIN usuarios_deportistas u ON te.inscrito_por_usuario_id = u.id
                LEFT JOIN equipo_miembros em ON e.id = em.equipo_id
                WHERE te.torneo_id = ?
                GROUP BY e.id, e.nombre, te.estado_inscripcion, te.fecha_inscripcion, u.nombre, u.apellidos
                ORDER BY te.fecha_inscripcion ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $torneo_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function inscribirEquipoEnTorneo($torneo_id, $equipo_id, $usuario_id) {
        // Verificar que el usuario sea líder del equipo
        $sql = "SELECT rol FROM equipo_miembros WHERE equipo_id = ? AND usuario_id = ? AND rol = 'creador'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $equipo_id, $usuario_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows == 0) {
            return ['success' => false, 'message' => 'Solo el líder del equipo puede inscribirlo'];
        }
        
        // Verificar que el torneo permita inscripciones
        $sql = "SELECT estado, max_equipos, equipos_inscritos FROM torneos WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $torneo_id);
        $stmt->execute();
        $torneo = $stmt->get_result()->fetch_assoc();
        
        if ($torneo['estado'] !== 'inscripciones_abiertas') {
            return ['success' => false, 'message' => 'Las inscripciones no están abiertas'];
        }
        
        if ($torneo['equipos_inscritos'] >= $torneo['max_equipos']) {
            return ['success' => false, 'message' => 'El torneo ha alcanzado el máximo de equipos'];
        }
        
        // Verificar que el equipo no esté ya inscrito
        $sql = "SELECT COUNT(*) as total FROM torneos_equipos WHERE torneo_id = ? AND equipo_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $torneo_id, $equipo_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['total'] > 0) {
            return ['success' => false, 'message' => 'El equipo ya está inscrito en este torneo'];
        }
        
        // Inscribir equipo
        $sql = "INSERT INTO torneos_equipos (torneo_id, equipo_id, inscrito_por_usuario_id, estado_inscripcion) 
                VALUES (?, ?, ?, 'confirmada')";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iii", $torneo_id, $equipo_id, $usuario_id);
        
        if ($stmt->execute()) {
            // Actualizar contador de equipos inscritos
            $sql = "UPDATE torneos SET equipos_inscritos = equipos_inscritos + 1 WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $torneo_id);
            $stmt->execute();
            
            return ['success' => true, 'message' => 'Equipo inscrito exitosamente'];
        } else {
            return ['success' => false, 'message' => 'Error al inscribir equipo'];
        }
    }
    
    public function __destruct() {
        if ($this->db) {
            $this->db->closeConnection();
        }
    }
}
?>