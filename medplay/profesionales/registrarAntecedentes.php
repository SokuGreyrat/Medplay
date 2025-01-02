<?php
session_start();
if (!isset($_SESSION['idUsuario']) || $_SESSION['rol'] != 'profesional') {
    header("Location: ../inicio/inicioSesion.php");
    exit();
}

include '../db.php'; // Conexión a la base de datos

// Obtener lista de pacientes
$query = "SELECT p.idPaciente, u.nombre 
          FROM paciente p 
          INNER JOIN usuario u ON p.idUsuario = u.idUsuario 
          WHERE u.rol = 'paciente'";
$result_pacientes = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Antecedentes Clínicos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg fixed-top bg-primary">
    <div class="container">
        <a class="navbar-brand text-white" href="proDashboard.php">MedPlay - Antecedentes Clínicos</a>
        <div class="d-flex">
            <a href="visualizarAntecedentes.php" class="btn btn-light me-2">Visualizar Antecedentes</a>
            <a href="proDashboard.php" class="btn btn-danger">Regresar</a>
        </div>
    </div>
</nav>

<div class="container" style="margin-top: 100px;">
    <div class="card shadow p-4">
        <h2 class="text-center mb-4">Registrar Antecedentes Clínicos</h2>
        <form action="procesarAntecedentes.php" method="POST">
            <!-- Selección del Paciente -->
            <div class="mb-3">
                <label for="idPaciente" class="form-label fw-bold">Paciente:</label>
                <select id="idPaciente" name="idPaciente" class="form-select" required>
                    <option value="" disabled selected>Seleccionar paciente...</option>
                    <?php
                    if ($result_pacientes && $result_pacientes->num_rows > 0) {
                        while ($row = $result_pacientes->fetch_assoc()) {
                            echo "<option value='{$row['idPaciente']}'>{$row['nombre']}</option>";
                        }
                    } else {
                        echo "<option value='' disabled>No hay pacientes disponibles</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Tipo de Antecedente -->
            <div class="mb-3">
                <label for="tipoAntecedente" class="form-label fw-bold">Tipo de Antecedente:</label>
                <select id="tipoAntecedente" name="tipoAntecedente" class="form-select" required>
                    <option value="" disabled selected>Seleccionar tipo...</option>
                    <option value="Alergia">Alergia</option>
                    <option value="Operacion">Operación</option>
                    <option value="Accidente">Accidente</option>
                    <option value="EnfermedadCronica">Enfermedad Crónica</option>
                    <option value="Hospitalizacion">Hospitalización</option>
                    <option value="Familiar">Familiar</option>
                    <option value="Otro">Otro</option>
                </select>
            </div>

            <!-- Campo Adicional para "Otro" -->
            <div class="mb-3" id="campoOtro" style="display: none;">
                <label for="otroAntecedente" class="form-label fw-bold">Especifique el tipo de antecedente:</label>
                <input type="text" id="otroAntecedente" name="otroAntecedente" class="form-control" placeholder="Fractura de brazo">
            </div>

            <!-- Descripción -->
            <div class="mb-3">
                <label for="descripcion" class="form-label fw-bold">Descripción:</label>
                <textarea id="descripcion" name="descripcion" class="form-control" placeholder="Fractura del brazo derecho debido a una caída." rows="3" required></textarea>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="fechaEvento" class="form-label fw-bold">Fecha del Evento:</label>
                    <input type="date" id="fechaEvento" name="fechaEvento" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label for="hospitalTratante" class="form-label fw-bold">Hospital Tratante:</label>
                    <input type="text" id="hospitalTratante" name="hospitalTratante" class="form-control" placeholder="Hospital Ortopédico Nacional">
                </div>
            </div>

            <div class="mb-3">
                <label for="doctorTratante" class="form-label fw-bold">Doctor Tratante:</label>
                <input type="text" id="doctorTratante" name="doctorTratante" class="form-control" placeholder="Dra. Laura Martínez">
            </div>

            <div class="mb-3">
                <label for="tratamiento" class="form-label fw-bold">Tratamiento:</label>
                <textarea id="tratamiento" name="tratamiento" class="form-control" placeholder="Yeso inmovilizador durante 6 semanas." rows="2"></textarea>
            </div>

            <div class="mb-3">
                <label for="observaciones" class="form-label fw-bold">Observaciones:</label>
                <textarea id="observaciones" name="observaciones" class="form-control" placeholder="Necesita rehabilitación ligera." rows="2"></textarea>
            </div>

            <button type="submit" class="btn btn-primary w-100">Guardar Antecedente</button>
        </form>
    </div>
</div>

<footer class="text-center bg-dark text-white py-3">
    <div class="container">
        <p class="mb-0">&copy; 2024 MEDPLAY. Todos los derechos reservados.</p>
    </div>
</footer>

<!-- Bootstrap y JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Mostrar campo adicional si se selecciona "Otro"
    const tipoAntecedente = document.getElementById('tipoAntecedente');
    const campoOtro = document.getElementById('campoOtro');

    tipoAntecedente.addEventListener('change', function () {
        if (this.value === 'Otro') {
            campoOtro.style.display = 'block';
        } else {
            campoOtro.style.display = 'none';
        }
    });
</script>
</body>
</html>
