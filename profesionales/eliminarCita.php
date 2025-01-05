<?php
session_start();
include '../db.php';

// Verificar si el usuario tiene permisos
if (!isset($_SESSION['idUsuario']) || $_SESSION['rol'] != 'profesional') {
    header("Location: ../inicio/inicioSesion.php");
    exit();
}

// Verificar si se pasó el ID de la cita
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $idCita = $_GET['id'];

    // Eliminar la cita médica
    $query = "DELETE FROM citamedica WHERE idCita = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $idCita);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Cita eliminada correctamente.";
    } else {
        $_SESSION['error'] = "Error al eliminar la cita: " . $stmt->error;
    }

    $stmt->close();
} else {
    $_SESSION['error'] = "ID de cita no válido.";
}

$conn->close();
header("Location: gestionarCitas.php");
exit();
?>
