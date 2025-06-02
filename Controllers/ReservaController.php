<?php
require_once __DIR__ . '/../Models/ReservaModel.php';

class ReservaController {
    private $reservaModel;

    public function __construct() {
        $this->reservaModel = new ReservaModel();
    }

    public function obtenerReservas() {
        // Manejar solicitud de cronograma
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'getCronograma' && isset($_GET['id'])) {
            $this->obtenerCronograma();
            return;
        }

        // Mantener compatibilidad con el método anterior
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'getHorarios' && isset($_GET['id'])) {
            $this->obtenerCronograma(); // Redirigir al nuevo método
            return;
        }
    }

    private function obtenerCronograma() {
        try {
            $idInstitucion = intval($_GET['id']);
            $fecha = $_GET['fecha'] ?? null; // Permitir especificar fecha opcional

            if ($idInstitucion <= 0) {
                throw new Exception('ID de institución inválido');
            }

            $cronograma = $this->reservaModel->obtenerCronogramaDisponibilidad($idInstitucion, $fecha);
            
            if (!$cronograma) {
                throw new Exception('Institución no encontrada');
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $cronograma
            ]);

        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
}

$controller = new ReservaController();
$controller->obtenerReservas();