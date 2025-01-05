<?php
session_start();
include '../db.php'; // Conexión a la base de datos

// Mostrar errores durante el desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verifica si el formulario se ha enviado
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validar campos vacíos
    if (empty($email) || empty($password)) {
        $error = "Por favor, completa todos los campos.";
    } else {
        // Consulta para verificar el correo electrónico
        $query = "SELECT * FROM usuario WHERE correoElectronico = ? LIMIT 1";
        $stmt = $conn->prepare($query);

        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            // Verificar si el usuario existe
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();

                // Comparar contraseña encriptada
                if (password_verify($password, $user['contraseña'])) {
                    // Almacenar datos en la sesión
                    $_SESSION['idUsuario'] = $user['idUsuario'];
                    $_SESSION['nombre'] = $user['nombre'];
                    $_SESSION['rol'] = $user['rol'];

                    // Redirigir según el rol
                    if ($user['rol'] === 'paciente') {
                        // Verificar si el paciente ya completó su información
                        $queryPaciente = "SELECT idPaciente, datosCompletos FROM paciente WHERE idUsuario = ?";
                        $stmtPaciente = $conn->prepare($queryPaciente);
                        $stmtPaciente->bind_param("i", $user['idUsuario']);
                        $stmtPaciente->execute();
                        $resultPaciente = $stmtPaciente->get_result();

                        // Si no existe registro en la tabla paciente, crearlo
                        if ($resultPaciente->num_rows == 0) {
                            $insertPaciente = "INSERT INTO paciente (idUsuario, edad, tipoSangre, datosCompletos) VALUES (?, 0, '', 0)";
                            $stmtInsert = $conn->prepare($insertPaciente);
                            $stmtInsert->bind_param("i", $user['idUsuario']);
                            $stmtInsert->execute();
                            $stmtInsert->close();

                            header("Location: ../paciente/registrarDatosPaciente.php");
                            exit();
                        } else {
                            $paciente = $resultPaciente->fetch_assoc();
                            if ($paciente['datosCompletos'] == 0) {
                                header("Location: ../paciente/registrarDatosPaciente.php");
                                exit();
                            } else {
                                header("Location: ../paciente/pacienteDashboard.php");
                                exit();
                            }
                        }
                    } elseif ($user['rol'] === 'profesional') {
                        header("Location: ../profesionales/proDashboard.php");
                        exit();
                    } else {
                        header("Location: ../inicio/inicio.html");
                        exit();
                    }
                } else {
                    $error = "Contraseña incorrecta.";
                }
            } else {
                $error = "Usuario no encontrado.";
            }

            $stmt->close();
        } else {
            $error = "Error en la base de datos: " . $conn->error;
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <!-- Icono y Bootstrap -->
    <link rel="icon" href="../diseño/logo.png" type="image/jpg" sizes="16x16">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/navbar.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top bg-success">
    <div class="container">
        <a class="navbar-brand me-auto text-white" href="inicio.html">
            <img src="../diseño/logo.png" alt="Logo 1" class="navbar-logo"> MEDPLAY
        </a>
        <a href="inicio.html" class="btn btn-danger">Regresar</a>
    </div>
</nav>



<!-- Contenido principal -->
<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow p-4 feature-box" style="max-width: 500px; width: 100%;">

        <h2 class="text-center mb-4">Iniciar Sesión</h2>
        <!-- Mostrar error si existe -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger text-center"><?php echo $error; ?></div>
        <?php endif; ?>
        <!-- Formulario -->
        <form action="inicioSesion.php" method="POST">
            <div class="form-group mb-3">
                <label for="email" class="form-label">Correo Electrónico:</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="Ingresa tu correo" required>
            </div>
            <div class="form-group mb-3">
                <label for="password" class="form-label">Contraseña:</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Ingresa tu contraseña" required>
            </div>
            <button type="submit" class="btn btn-success w-100">Iniciar Sesión</button>
        </form>
        <!-- Links adicionales -->
        <div class="mt-3 text-center">
            <a href="registro.php" class="text-success">¿No tienes cuenta? Regístrate aquí</a><br>
            <a href="recuperarContraseña.php" class="text-success">¿Olvidaste tu contraseña?</a>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="text-center text-white bg-dark py-3 mt-auto">
    <p class="mb-0">&copy; 2024 MEDPLAY. Todos los derechos reservados.</p>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
