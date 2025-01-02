<?php
session_start();
if (!isset($_SESSION['idUsuario']) || $_SESSION['rol'] != 'profesional') {
    header("Location: ../inicio/inicioSesion.php");
    exit();
}

include '../db.php'; // Conexión a la base de datos

// Obtener lista de pacientes
$query_pacientes = "SELECT p.idPaciente, u.nombre 
                    FROM paciente p 
                    INNER JOIN usuario u ON p.idUsuario = u.idUsuario 
                    WHERE u.rol = 'paciente'";
$result_pacientes = $conn->query($query_pacientes);

// Inicializar variables
$antecedentes = [];
$pacienteSeleccionado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idPaciente'])) {
    $pacienteSeleccionado = $_POST['idPaciente'];

    // Consultar antecedentes del paciente seleccionado
    $query_antecedentes = "SELECT * FROM antecedentesclinicos WHERE idPaciente = ?";
    $stmt = $conn->prepare($query_antecedentes);
    $stmt->bind_param("i", $pacienteSeleccionado);
    $stmt->execute();
    $result_antecedentes = $stmt->get_result();

    while ($row = $result_antecedentes->fetch_assoc()) {
        $antecedentes[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Antecedentes Clínicos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg bg-primary fixed-top">
    <div class="container">
        <a class="navbar-brand text-white" href="proDashboard.php">MedPlay - Visualizar Antecedentes</a>
        <a href="registrarAntecedentes.php" class="btn btn-light">Registrar Antecedentes</a>
        <a href="proDashboard.php" class="btn btn-danger ms-2">Regresar</a>
    </div>
</nav>

<div class="container" style="margin-top: 100px;">
    <h2 class="text-center mb-4">Visualizar Antecedentes Clínicos</h2>

    <!-- Selección de paciente -->
    <form method="POST" class="mb-4">
        <div class="row g-3">
            <div class="col-md-8">
                <select name="idPaciente" class="form-select" required>
                    <option value="" disabled selected>Seleccionar paciente...</option>
                    <?php
                    if ($result_pacientes->num_rows > 0) {
                        while ($paciente = $result_pacientes->fetch_assoc()) {
                            $selected = ($paciente['idPaciente'] == $pacienteSeleccionado) ? 'selected' : '';
                            echo "<option value='{$paciente['idPaciente']}' $selected>{$paciente['nombre']}</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">Buscar Antecedentes</button>
            </div>
        </div>
    </form>

    <!-- Tabla de antecedentes -->
    <?php if (!empty($antecedentes)) : ?>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Tipo</th>
                    <th>Descripción</th>
                    <th>Fecha Evento</th>
                    <th>Hospital</th>
                    <th>Doctor</th>
                    <th>Tratamiento</th>
                    <th>Observaciones</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($antecedentes as $antecedente) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($antecedente['idAntecedente']); ?></td>
                        <td><?php echo htmlspecialchars($antecedente['tipoAntecedente']); ?></td>
                        <td><?php echo htmlspecialchars($antecedente['descripcion']); ?></td>
                        <td><?php echo htmlspecialchars($antecedente['fechaEvento']); ?></td>
                        <td><?php echo htmlspecialchars($antecedente['hospitalTratante']); ?></td>
                        <td><?php echo htmlspecialchars($antecedente['doctorTratante']); ?></td>
                        <td><?php echo htmlspecialchars($antecedente['tratamiento']); ?></td>
                        <td><?php echo htmlspecialchars($antecedente['observaciones']); ?></td>
                        <td>
                            <a href="editarAntecedente.php?id=<?php echo $antecedente['idAntecedente']; ?>" class="btn btn-warning btn-sm">Editar</a>
                            <a href="eliminarAntecedente.php?id=<?php echo $antecedente['idAntecedente']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro de eliminar este antecedente?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($pacienteSeleccionado) : ?>
        <p class="text-center text-danger">No se encontraron antecedentes clínicos para este paciente.</p>
    <?php endif; ?>
</div>

<footer class="bg-dark text-white text-center py-3">
    <div class="container">
        <p class="mb-0">&copy; 2024 MEDPLAY. Todos los derechos reservados.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
