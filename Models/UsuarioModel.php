<?php
require_once __DIR__ . '/../Config/database.php';

class UsuarioModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function obtenerUsuarioPorUsername($username, $user_type) {
        $table = ($user_type === 'deportista') ? 'usuarios_deportistas' : 'usuarios_instalaciones';

        $stmt = $this->conn->prepare("SELECT id, username, password, estado FROM $table WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();

        $stmt->close();
        return $usuario;
    }

    public function registrarDeportista($data) {
        $stmt = $this->conn->prepare("INSERT INTO usuarios_deportistas (
            nombre, apellidos, email, username, password, telefono, fecha_nacimiento, genero, nivel_habilidad, estado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo')");

        $password_hashed = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt->bind_param(
            "sssssssss",
            $data['nombre'],
            $data['apellidos'],
            $data['email'],
            $data['username'],
            $password_hashed,
            $data['telefono'],
            $data['fecha_nacimiento'],
            $data['genero'],
            $data['nivel_habilidad']
        );

        if (!$stmt->execute()) {
            return ['error' => $stmt->error];
        }

        $usuario_id = $stmt->insert_id;
        $stmt->close();

        // Deportes favoritos
        foreach ($data['deportes_favoritos'] as $deporte_id) {
            $insertDeporte = $this->conn->prepare("INSERT INTO usuarios_deportistas_deportes (usuario_id, deporte_id) VALUES (?, ?)");
            $insertDeporte->bind_param("ii", $usuario_id, $deporte_id);
            $insertDeporte->execute();
            $insertDeporte->close();
        }

        // Disponibilidad
        foreach ($data['disponibilidad'] as $dia => $franjas) {
            foreach ($franjas as $franja) {
                $insertDisponibilidad = $this->conn->prepare("INSERT INTO usuarios_deportistas_disponibilidad (usuario_id, dia_semana, franja_horaria) VALUES (?, ?, ?)");
                $insertDisponibilidad->bind_param("iss", $usuario_id, $dia, $franja);
                $insertDisponibilidad->execute();
                $insertDisponibilidad->close();
            }
        }

        return ['success' => true];
    }
}