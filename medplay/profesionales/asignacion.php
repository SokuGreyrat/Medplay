<?php
// Inicia la sesión para verificar si el usuario es un profesional de salud
session_start();

// Comprueba si el usuario inició sesión y si es un profesional
if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'profesional') {
    header("Location: ../inicio/login.php");
    exit();
}

// Conexión a la base de datos
include '../db.php'; // Incluye la conexión a la base de datos

// Consulta para obtener pacientes registrados
$query = "SELECT p.idPaciente, u.nombre 
          FROM paciente p 
          INNER JOIN usuario u ON p.idUsuario = u.idUsuario 
          WHERE u.rol = 'paciente'";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta, Tratamientos y Misiones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top bg-primary">
    <div class="container">
        <a class="navbar-brand text-white" href="proDashboard.php">MedPlay - Consulta, Tratamientos y Misiones</a>
        <a href="proDashboard.php" class="btn btn-danger">Regresar</a>
    </div>
</nav>

<!-- Contenido principal -->
<div class="container mt-5 pt-4">
    <div class="card shadow p-4">
        <h2 class="text-center mb-4">Registro de Consulta</h2>
        <!-- Mensaje de confirmación -->
        <?php if (isset($_SESSION['mensaje_exito'])): ?>
            <div class="alert alert-success text-center">
                <?php 
                    echo $_SESSION['mensaje_exito']; 
                    unset($_SESSION['mensaje_exito']); // Limpia el mensaje
                ?>
            </div>
        <?php endif; ?>

        <form action="procesarTratamiento.php" method="POST">
            <!-- Paciente -->
            <div class="mb-3">
                <label for="paciente" class="form-label fw-bold">Paciente:</label>
                <select id="paciente" name="idPaciente" class="form-select" required>
                    <option value="" disabled selected>Seleccionar...</option>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . $row['idPaciente'] . "'>" . htmlspecialchars($row['nombre']) . "</option>";
                        }
                    } else {
                        echo "<option value='' disabled>No hay pacientes registrados</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Selector de citas -->
            <div class="mb-3">
                <label for="cita" class="form-label fw-bold">Cita:</label>
                <select id="cita" name="idCita" class="form-select" required>
                    <option value="" disabled selected>Seleccione una cita...</option>
                </select>
            </div>

            <!-- Campos completos de Tratamiento -->
            <div class="mb-3">
                <label for="consulta" class="form-label fw-bold">Consulta:</label>
                <input type="text" id="consulta" name="consulta" class="form-control" placeholder="Consulta de referencia" required>
            </div>

            <div class="mb-3">
                <label for="fecha" class="form-label fw-bold">Fecha:</label>
                <input type="date" id="fecha" name="fecha" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="mb-3">
                <label for="motivo" class="form-label fw-bold">Motivo:</label>
                <textarea id="motivo" name="motivo" class="form-control" rows="2" placeholder="Motivo de la consulta"></textarea>
            </div>

            <div class="mb-3">
                <label for="diagnostico" class="form-label fw-bold">Diagnóstico:</label>
                <textarea id="diagnostico" name="diagnostico" class="form-control" rows="2" placeholder="Diagnóstico médico"></textarea>
            </div>

            <div class="mb-3">
                <label for="medicamento" class="form-label fw-bold">Medicamento:</label>
                <input type="text" id="medicamento" name="medicamento" class="form-control" placeholder="Medicamento prescrito" required>
            </div>

            <div class="mb-3">
                <label for="dosis" class="form-label fw-bold">Dosis:</label>
                <input type="text" id="dosis" name="dosis" class="form-control" placeholder="Dosis específica (Ej: 500mg)" required>
            </div>

            <div class="mb-3">
                <label for="frecuencia" class="form-label fw-bold">Frecuencia:</label>
                <input type="text" id="frecuencia" name="frecuencia" class="form-control" placeholder="Ej: Cada 8 horas" required>
            </div>

            <div class="mb-3">
                <label for="duracion" class="form-label fw-bold">Duración (en días):</label>
                <input type="number" id="duracion" name="duracion" class="form-control" placeholder="Duración del tratamiento" min="1" required>
            </div>

            <!-- Botón para enviar -->
            <button type="submit" class="btn btn-success w-100">Registrar</button>
        </form>
    </div>
</div>

<!-- Footer -->
<footer class="text-center bg-dark text-white py-3 mt-4">
    <div class="container">
        <p class="mb-0">&copy; 2024 MEDPLAY. Todos los derechos reservados.</p>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('paciente').addEventListener('change', function () {
        const idPaciente = this.value;
        const citaSelect = document.getElementById('cita');

        if (idPaciente) {
            fetch(`obtenerCitas.php?idPaciente=${idPaciente}`)
                .then(response => response.json())
                .then(data => {
                    citaSelect.innerHTML = '<option value="" disabled selected>Seleccione una cita...</option>';
                    if (data.length > 0) {
                        data.forEach(cita => {
                            const option = document.createElement('option');
                            option.value = cita.idCita;
                            option.textContent = `Fecha: ${cita.fecha}, Hora: ${cita.hora}, Motivo: ${cita.motivo}, Estado: ${cita.estado}`;
                            citaSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error al obtener las citas:', error));
        } else {
            citaSelect.innerHTML = '<option value="" disabled selected>Seleccione una cita...</option>';
        }
    });
</script>
</body>
</html>
