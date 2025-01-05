<?php
session_start();
include '../db.php'; // Conexión a la base de datos

if (!isset($_SESSION['idUsuario']) || $_SESSION['rol'] != 'profesional') {
    header("Location: ../inicio/inicioSesion.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $idUsuario = $_SESSION['idUsuario'];
    $idPaciente = $_POST['idPaciente'] ?? null;
    $fecha = $_POST['fecha'] ?? null;
    $hora = $_POST['hora'] ?? null;
    $motivo = $_POST['motivo'] ?? null;

    // Validación de datos
    if (empty($idPaciente) || empty($fecha) || empty($hora) || empty($motivo)) {
        $_SESSION['error'] = "Todos los campos son obligatorios.";
        header("Location: asignarcitas.php");
        exit();
    }

    try {
        // Verificar conexión
        if (!$conn) {
            throw new Exception("Error en la conexión a la base de datos: " . mysqli_connect_error());
        }

        // Obtener idProfesional
        $query_profesional = "SELECT idProfesional FROM profesionaldesalud WHERE idUsuario = ?";
        $stmt_prof = $conn->prepare($query_profesional);
        $stmt_prof->bind_param("i", $idUsuario);
        $stmt_prof->execute();
        $result_prof = $stmt_prof->get_result();

        if ($result_prof->num_rows == 0) {
            $_SESSION['error'] = "No se encontró un profesional asociado al usuario.";
            header("Location: asignarcitas.php");
            exit();
        }

        $idProfesional = $result_prof->fetch_assoc()['idProfesional'];

        // Insertar cita médica
        $query = "INSERT INTO citamedica (idPaciente, idProfesional, fecha, hora, motivo, estado) 
                  VALUES (?, ?, ?, ?, ?, 'Próxima')";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iisss", $idPaciente, $idProfesional, $fecha, $hora, $motivo);

        if (!$stmt->execute()) {
            throw new Exception("Error al insertar en citamedica: " . $stmt->error);
        }

        $_SESSION['success'] = "Cita registrada exitosamente.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Ocurrió un error: " . $e->getMessage();
    } finally {
        $stmt_prof->close();
        $stmt->close();
        $conn->close();
        header("Location: asignarcitas.php");
        exit();
    }
} else {
    $_SESSION['error'] = "Método de acceso no válido.";
    header("Location: asignarcitas.php");
    exit();
}
?>
