<?php
session_start();
include '../db.php'; // Conexión a la base de datos

// Verificar que el formulario fue enviado correctamente
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Capturar los valores del formulario
    $idPaciente = $_POST['idPaciente'];
    $tratamiento = $_POST['tratamiento'];
    $dosis = $_POST['dosis'];
    $frecuencia = $_POST['frecuencia'];
    $duracion = $_POST['duracion'];

    // Verificar que los campos no estén vacíos
    if (!empty($idPaciente) && !empty($tratamiento) && !empty($dosis) && !empty($frecuencia) && !empty($duracion)) {
        // Insertar los datos en la tabla "tratamiento"
        $query = "INSERT INTO tratamiento (idPaciente, medicamento, dosis, frecuencia, duracion) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isssi", $idPaciente, $tratamiento, $dosis, $frecuencia, $duracion);

        if ($stmt->execute()) {
            // Redirigir con mensaje de éxito
            $_SESSION['success'] = "Tratamiento asignado correctamente.";
            header("Location: asignacion.php");
        } else {
            // Error en la ejecución
            $_SESSION['error'] = "Error al asignar el tratamiento: " . $stmt->error;
            header("Location: asignacion.php");
        }

        $stmt->close();
    } else {
        // Si algún campo está vacío
        $_SESSION['error'] = "Por favor, completa todos los campos.";
        header("Location: asignacion.php");
    }

    $conn->close();
} else {
    // Si no se accedió mediante POST
    $_SESSION['error'] = "Acceso no autorizado.";
    header("Location: asignacion.php");
}
exit();
?>
