<?php
require_once '../Models/ChatModel.php';

class ChatController {
    private $chatModel;
    
    public function __construct() {
        $this->chatModel = new ChatModel();
    }
    
    // ========== MÉTODOS PARA AMISTADES ==========
    
    public function enviarSolicitudAmistad() {
        session_start();
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'No autenticado']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $receptor_id = $input['receptor_id'] ?? null;
        
        if (!$receptor_id) {
            echo json_encode(['success' => false, 'message' => 'ID de receptor requerido']);
            return;
        }
        
        $resultado = $this->chatModel->enviarSolicitudAmistad($_SESSION['user_id'], $receptor_id);
        echo json_encode($resultado);
    }
    
    public function obtenerSolicitudesPendientes() {
        session_start();
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'No autenticado']);
            return;
        }
        
        $solicitudes = $this->chatModel->obtenerSolicitudesPendientes($_SESSION['user_id']);
        echo json_encode(['success' => true, 'solicitudes' => $solicitudes]);
    }
    
    public function responderSolicitudAmistad() {
        session_start();
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'No autenticado']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $solicitud_id = $input['solicitud_id'] ?? null;
        $respuesta = $input['respuesta'] ?? null;
        
        if (!$solicitud_id || !in_array($respuesta, ['aceptada', 'rechazada'])) {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
            return;
        }
        
        $resultado = $this->chatModel->responderSolicitudAmistad($solicitud_id, $_SESSION['user_id'], $respuesta);
        echo json_encode($resultado);
    }
    
    public function obtenerAmigos() {
        session_start();
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'No autenticado']);
            return;
        }
        
        $amigos = $this->chatModel->obtenerAmigos($_SESSION['user_id']);
        echo json_encode(['success' => true, 'amigos' => $amigos]);
    }
    
    public function buscarUsuarios() {
        session_start();
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'No autenticado']);
            return;
        }
        
        $busqueda = $_GET['q'] ?? '';
        
        if (strlen($busqueda) < 2) {
            echo json_encode(['success' => false, 'message' => 'Mínimo 2 caracteres para buscar']);
            return;
        }
        
        $usuarios = $this->chatModel->buscarUsuarios($busqueda, $_SESSION['user_id']);
        echo json_encode(['success' => true, 'usuarios' => $usuarios]);
    }
    
    // ========== MÉTODOS PARA EQUIPOS ==========
    
    public function crearEquipo() {
        session_start();
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'No autenticado']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $datos = [
            'nombre' => trim($input['nombre'] ?? ''),
            'descripcion' => trim($input['descripcion'] ?? ''),
            'deporte_id' => $input['deporte_id'] ?? null,
            'creador_id' => $_SESSION['user_id'],
            'limite_miembros' => $input['limite_miembros'] ?? 10,
            'privado' => $input['privado'] ?? 0
        ];
        
        // Validaciones
        if (empty($datos['nombre'])) {
            echo json_encode(['success' => false, 'message' => 'Nombre del equipo requerido']);
            return;
        }
        
        if (!$datos['deporte_id']) {
            echo json_encode(['success' => false, 'message' => 'Deporte requerido']);
            return;
        }
        
        if ($datos['limite_miembros'] < 2 || $datos['limite_miembros'] > 50) {
            echo json_encode(['success' => false, 'message' => 'Límite de miembros debe ser entre 2 y 50']);
            return;
        }
        
        $resultado = $this->chatModel->crearEquipo($datos);
        echo json_encode($resultado);
    }
    
    public function obtenerEquipos() {
        session_start();
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'No autenticado']);
            return;
        }
        
        $deporte_id = $_GET['deporte_id'] ?? null;
        
        if ($deporte_id) {
            $equipos = $this->chatModel->obtenerEquiposPorDeporte($_SESSION['user_id'], $deporte_id);
        } else {
            $equipos = $this->chatModel->obtenerEquiposUsuario($_SESSION['user_id']);
        }
        
        echo json_encode(['success' => true, 'equipos' => $equipos]);
    }
    
    public function obtenerMiembrosEquipo() {
        session_start();
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'No autenticado']);
            return;
        }
        
        $equipo_id = $_GET['equipo_id'] ?? null;
        
        if (!$equipo_id) {
            echo json_encode(['success' => false, 'message' => 'ID de equipo requerido']);
            return;
        }
        
        // Verificar que el usuario es miembro del equipo
        $es_miembro = $this->chatModel->verificarMiembroEquipo($equipo_id, $_SESSION['user_id']);
        if (!$es_miembro) {
            echo json_encode(['success' => false, 'message' => 'No tienes acceso a este equipo']);
            return;
        }
        
        $miembros = $this->chatModel->obtenerMiembrosEquipo($equipo_id);
        echo json_encode(['success' => true, 'miembros' => $miembros]);
    }
    
    public function obtenerDeportes() {
        session_start();
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'No autenticado']);
            return;
        }
        
        $deportes = $this->chatModel->obtenerDeportes();
        echo json_encode(['success' => true, 'deportes' => $deportes]);
    }
    
    // ========== MÉTODO PARA MANEJAR RUTAS ==========
    
    public function handleRequest() {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'enviar_solicitud':
                $this->enviarSolicitudAmistad();
                break;
            case 'solicitudes_pendientes':
                $this->obtenerSolicitudesPendientes();
                break;
            case 'responder_solicitud':
                $this->responderSolicitudAmistad();
                break;
            case 'obtener_amigos':
                $this->obtenerAmigos();
                break;
            case 'buscar_usuarios':
                $this->buscarUsuarios();
                break;
            case 'crear_equipo':
                $this->crearEquipo();
                break;
            case 'obtener_equipos':
                $this->obtenerEquipos();
                break;
            case 'obtener_miembros':
                $this->obtenerMiembrosEquipo();
                break;
            case 'obtener_deportes':
                $this->obtenerDeportes();
                break;
            default:
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Acción no válida']);
                break;
        }
    }
}

// Si se llama directamente al archivo
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $controller = new ChatController();
    $controller->handleRequest();
}
?>