<?php
// Incluir archivos de configuración
require_once '../../Config/database.php';

// Definir la ruta base
require_once '../../Controllers/AuthController.php';

define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/gameon/');

// Variables para almacenar mensajes y datos del formulario
$error_message = '';
$username = '';

// Procesar el formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $user_type = $_POST['user_type'];

    if (empty($username) || empty($password)) {
        $error_message = "Por favor, completa todos los campos.";
    } else {
        $authController = new AuthController();
        $error_message = $authController->login($username, $password, $user_type);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - GameOn Network</title>
    <link rel="stylesheet" href="../../Public/css/styles_login.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="auth-page">
    <div class="auth-image"></div>
        <div class="auth-container">
            <div class="auth-header">
                <img src="Resources/logo_ipd.png" alt="Logo IPD" style="width: 400px; height: auto;">
                <h2>Iniciar Sesión</h2>
                <p>Bienvenido de vuelta a GameOn Network</p>
            </div>
            
            <div class="auth-body">
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <div class="form-group">
                        <label for="username">Nombre de usuario</label>
                        <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <div class="password-field">
                            <input type="password" id="password" name="password" class="form-control" required>
                            <span class="toggle-password" onclick="togglePasswordVisibility()">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Tipo de usuario</label>
                        <div class="user-type-selector">
                            <div class="form-check">
                                <input type="radio" id="user_type_dep" name="user_type" value="deportista" checked>
                                <label for="user_type_dep">Deportista</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" id="user_type_ins" name="user_type" value="instalacion">
                                <label for="user_type_ins">Instalación Deportiva</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Recordarme</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">Iniciar Sesión</button>
                    </div>
                </form>
                
                <div class="forgot-password text-center">
                    <a href="reset-password.php">¿Olvidaste tu contraseña?</a>
                </div>
            </div>
            
            <div class="auth-footer">
                ¿No tienes cuenta? 
                <a href="../UserDep/registrousuario.php">Regístrate como deportista</a> o 
                <a href="../UserInsD/registroinsdepor.php">registra tu instalación deportiva</a>
            </div>
        </div>
    </div>
    
    <script>
        function togglePasswordVisibility() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.querySelector('.toggle-password i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
    
    <script src="../../Public/js/main.js"></script>
</body>
</html>