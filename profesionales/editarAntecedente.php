<?php
session_start();
if (!isset($_SESSION['idUsuario']) || $_SESSION['rol'] != 'profesional') {
    header("Location: ../inicio/inicioSesion.php");
    exit();
}

include '../db.php'; // Conexión a la base de datos

// Validar el ID del antecedente
if (!isset($_GET['id'])) {
    exit("ID de antecedente no especificado.");
}

$idAntecedente = intval($_GET['id']);

// Obtener los datos actuales del antecedente
$query = "SELECT * FROM antecedentesclinicos WHERE idAntecedente = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $idAntecedente);
$stmt->execute();
$result = $stmt->get_result();
$antecedente = $result->fetch_assoc();

if (!$antecedente) {
    exit("Antecedente no encontrado.");
}

// Actualizar los datos si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipoAntecedente'];
    $descripcion = $_POST['descripcion'];
    $fecha = $_POST['fechaEvento'];
    $hospital = $_POST['hospitalTratante'];
    $doctor = $_POST['doctorTratante'];
    $tratamiento = $_POST['tratamiento'];
    $observaciones = $_POST['observaciones'];

    $query_update = "UPDATE antecedentesclinicos SET 
                        tipoAntecedente = ?, descripcion = ?, fechaEvento = ?, hospitalTratante = ?, 
                        doctorTratante = ?, tratamiento = ?, observaciones = ?
                    WHERE idAntecedente = ?";
    $stmt_update = $conn->prepare($query_update);
    $stmt_update->bind_param("sssssssi", $tipo, $descripcion, $fecha, $hospital, $doctor, $tratamiento, $observaciones, $idAntecedente);
    $stmt_update->execute();

    header("Location: visualizarAntecedentes.php?success=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Antecedente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center mb-4">Editar Antecedente Clínico</h2>
    <form method="POST">
        <div class="mb-3">
            <label for="tipoAntecedente" class="form-label fw-bold">Tipo de Antecedente:</label>
            <input type="text" class="form-control" name="tipoAntecedente" value="<?php echo htmlspecialchars($antecedente['tipoAntecedente']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="descripcion" class="form-label fw-bold">Descripción:</label>
            <textarea name="descripcion" class="form-control" rows="3"><?php echo htmlspecialchars($antecedente['descripcion']); ?></textarea>
        </div>
        <div class="mb-3">
            <label for="fechaEvento" class="form-label fw-bold">Fecha del Evento:</label>
            <input type="date" class="form-control" name="fechaEvento" value="<?php echo htmlspecialchars($antecedente['fechaEvento']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="hospitalTratante" class="form-label fw-bold">Hospital Tratante:</label>
            <input type="text" class="form-control" name="hospitalTratante" value="<?php echo htmlspecialchars($antecedente['hospitalTratante']); ?>">
        </div>
        <div class="mb-3">
            <label for="doctorTratante" class="form-label fw-bold">Doctor Tratante:</label>
            <input type="text" class="form-control" name="doctorTratante" value="<?php echo htmlspecialchars($antecedente['doctorTratante']); ?>">
        </div>
        <div class="mb-3">
            <label for="tratamiento" class="form-label fw-bold">Tratamiento:</label>
            <textarea name="tratamiento" class="form-control" rows="2"><?php echo htmlspecialchars($antecedente['tratamiento']); ?></textarea>
        </div>
        <div class="mb-3">
            <label for="observaciones" class="form-label fw-bold">Observaciones:</label>
            <textarea name="observaciones" class="form-control" rows="2"><?php echo htmlspecialchars($antecedente['observaciones']); ?></textarea>
        </div>
        <button type="submit" class="btn btn-success w-100">Actualizar</button>
    </form>
</div>
</body>
</html>
