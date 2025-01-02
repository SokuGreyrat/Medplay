<?php
session_start();
if (!isset($_SESSION['idUsuario']) || $_SESSION['rol'] != 'profesional') {
    header("Location: ../inicio/inicioSesion.php");
    exit();
}

include '../db.php'; // Conexión a la base de datos

// Validar si se enviaron los datos correctamente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los datos del formulario
    $idPaciente = $_POST['idPaciente'] ?? null;
    $tipoAntecedente = $_POST['tipoAntecedente'] ?? null;
    $descripcion = $_POST['descripcion'] ?? null;
    $fechaEvento = $_POST['fechaEvento'] ?? null;
    $hospitalTratante = $_POST['hospitalTratante'] ?? null;
    $doctorTratante = $_POST['doctorTratante'] ?? null;
    $tratamiento = $_POST['tratamiento'] ?? null;
    $observaciones = $_POST['observaciones'] ?? null;

    // Validar que los campos obligatorios no estén vacíos
    if (!$idPaciente || !$tipoAntecedente || !$descripcion || !$fechaEvento) {
        echo "Error: Todos los campos obligatorios deben llenarse.";
        exit();
    }

    // Insertar datos en la base de datos
    $stmt = $conn->prepare("
        INSERT INTO antecedentesclinicos 
        (idPaciente, tipoAntecedente, descripcion, fechaEvento, hospitalTratante, doctorTratante, tratamiento, observaciones) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if ($stmt) {
        $stmt->bind_param(
            "isssssss", 
            $idPaciente, 
            $tipoAntecedente, 
            $descripcion, 
            $fechaEvento, 
            $hospitalTratante, 
            $doctorTratante, 
            $tratamiento, 
            $observaciones
        );

        if ($stmt->execute()) {
            // Redireccionar con éxito
            header("Location: registrarAntecedentes.php?success=1");
            exit();
        } else {
            echo "Error al guardar el antecedente clínico: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error en la consulta preparada: " . $conn->error;
    }

    $conn->close();
} else {
    echo "Acceso no permitido.";
    exit();
}
?>
