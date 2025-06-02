<?php
session_start();
require_once __DIR__ . '/../Models/UsuarioModel.php';

class AuthController {
    private $usuarioModel;

    public function __construct() {
        $this->usuarioModel = new UsuarioModel();
    }

    public function login($username, $password, $user_type) {
        $usuario = $this->usuarioModel->obtenerUsuarioPorUsername($username, $user_type);

        if (!$usuario) {
            return "Credenciales incorrectas.";
        }

        if (!password_verify($password, $usuario['password'])) {
            return "Contraseña incorrecta.";
        }

        if ($usuario['estado'] != 1) {
            return "Tu cuenta no está activa.";
        }

        // Autenticación exitosa: iniciar sesión
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['username'] = $usuario['username'];
        $_SESSION['user_type'] = $user_type;

        // Redirigir
        if ($user_type === 'deportista') {
            header("Location: ../UserDep/dashboard.php");
        } else {
            header("Location: ../UserInsD/dashboard.php");
        }
        exit();
    }
}