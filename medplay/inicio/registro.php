<?php 
// Mostrar errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../db.php';  // Incluir la conexión a la base de datos

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['name'] ?? '';
    $correo = $_POST['email'] ?? '';
    $contrasena = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : '';
    $rol = $_POST['role'] ?? '';

    // Campos adicionales para profesional
    $especialidad = $_POST['especialidad'] ?? '';
    $numeroLicencia = $_POST['numeroLicencia'] ?? '';
    $hospitalAsociado = $_POST['hospitalAsociado'] ?? '';

    if (!empty($nombre) && !empty($correo) && !empty($contrasena) && !empty($rol)) {
        $conn->begin_transaction(); // Iniciar transacción
        try {
            // Si el rol es 'profesional', el rol será 'pendiente'
            $rolUsuario = ($rol === 'profesional') ? 'pendiente' : $rol;

            // Insertar usuario en la tabla 'usuario'
            $sqlUsuario = "INSERT INTO usuario (nombre, correoElectronico, contraseña, rol) VALUES (?, ?, ?, ?)";
            $stmtUsuario = $conn->prepare($sqlUsuario);
            $stmtUsuario->bind_param("ssss", $nombre, $correo, $contrasena, $rolUsuario);
            if (!$stmtUsuario->execute()) {
                throw new Exception("Error al insertar en la tabla usuario: " . $stmtUsuario->error);
            }

            // Obtener el ID del usuario insertado
            $idUsuario = $conn->insert_id;

            // Si el rol es 'profesional', insertar los datos adicionales pero sin activarlo
            if ($rol === 'profesional') {
                if (!empty($especialidad) && !empty($numeroLicencia) && !empty($hospitalAsociado)) {
                    $sqlProfesional = "INSERT INTO profesionaldesalud (idUsuario, especialidad, numeroLicencia, hospitalAsociado) 
                                       VALUES (?, ?, ?, ?)";
                    $stmtProfesional = $conn->prepare($sqlProfesional);
                    $stmtProfesional->bind_param("isss", $idUsuario, $especialidad, $numeroLicencia, $hospitalAsociado);
                    if (!$stmtProfesional->execute()) {
                        throw new Exception("Error al insertar en la tabla profesionaldesalud: " . $stmtProfesional->error);
                    }
                } else {
                    throw new Exception("Todos los campos adicionales para profesionales son obligatorios.");
                }

                $mensaje = "<div class='alert alert-info text-center'>Registro exitoso. Su cuenta debe ser verificada por las autoridades correspondientes para otorgarle acceso como profesional de salud.</div>";
            }

            // Si el rol es 'paciente', insertar en la tabla 'paciente'
            if ($rol === 'paciente') {
                $sqlPaciente = "INSERT INTO paciente (idUsuario, edad, tipoSangre) VALUES (?, NULL, NULL)";
                $stmtPaciente = $conn->prepare($sqlPaciente);
                $stmtPaciente->bind_param("i", $idUsuario);
                if (!$stmtPaciente->execute()) {
                    throw new Exception("Error al insertar en la tabla paciente: " . $stmtPaciente->error);
                }

                $mensaje = "<div class='alert alert-success text-center'>Registro exitoso. Su cuenta ha sido activada como paciente. Recibira un correo de su confirmación.</div>";
            }

            $conn->commit(); // Confirmar transacción
        } catch (Exception $e) {
            $conn->rollback(); // Revertir transacción
            $mensaje = "<div class='alert alert-danger text-center'>Error: " . $e->getMessage() . "</div>";
        }

        $stmtUsuario->close();
        if (isset($stmtProfesional)) $stmtProfesional->close();
        if (isset($stmtPaciente)) $stmtPaciente->close();
    } else {
        $mensaje = "<div class='alert alert-danger text-center'>Todos los campos son obligatorios.</div>";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script>
        // Mostrar campos adicionales si se selecciona el rol 'profesional'
        function toggleProfesionalFields() {
            const role = document.getElementById('role').value;
            const profesionalFields = document.getElementById('profesionalFields');
            profesionalFields.style.display = (role === 'profesional') ? 'block' : 'none';
        }
    </script>
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top bg-success">
    <div class="container">
        <a class="navbar-brand text-white" href="#">Registro</a>
        <a href="inicio.html" class="btn btn-danger">Regresar</a>
    </div>
</nav>

<!-- Contenido Principal -->
<div class="container mt-5 pt-4">
    <div class="card shadow p-4 mx-auto" style="max-width: 500px;">
        <h2 class="text-center mb-4">Crear una Cuenta</h2>
        <?php if (isset($mensaje)) echo $mensaje; ?>
        <form method="POST" action="">
            <!-- Nombre completo -->
            <div class="mb-3">
                <label for="name" class="form-label">Nombre Completo:</label>
                <input type="text" id="name" name="name" class="form-control" required>
            </div>
            <!-- Correo Electrónico -->
            <div class="mb-3">
                <label for="email" class="form-label">Correo Electrónico:</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <!-- Contraseña -->
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <!-- Rol -->
            <div class="mb-3">
                <label for="role" class="form-label">Rol:</label>
                <select id="role" name="role" class="form-select" onchange="toggleProfesionalFields()" required>
                    <option value="" disabled selected>Selecciona tu rol</option>
                    <option value="paciente">Paciente</option>
                    <option value="profesional">Profesional de Salud</option>
                </select>
            </div>
            <!-- Campos Adicionales para Profesional -->
            <div id="profesionalFields" style="display: none;">
                <div class="mb-3">
                    <label for="especialidad" class="form-label">Especialidad:</label>
                    <input type="text" id="especialidad" name="especialidad" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="numeroLicencia" class="form-label">Número de Licencia:</label>
                    <input type="text" id="numeroLicencia" name="numeroLicencia" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="hospitalAsociado" class="form-label">Hospital Asociado:</label>
                    <input type="text" id="hospitalAsociado" name="hospitalAsociado" class="form-control">
                </div>
            </div>
            <!-- Botón -->
            <button type="submit" class="btn btn-success w-100">Registrarse</button>
        </form>
    </div>
</div>

<!-- Footer -->
<footer class="text-center text-white bg-dark py-3 mt-5">
    <p class="mb-0">&copy; 2024 MEDPLAY. Todos los derechos reservados.</p>
</footer>
</body>
</html>
