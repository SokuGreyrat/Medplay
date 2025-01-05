<?php
session_start();
include '../db.php'; // Conexión a la base de datos

// Verificar si el usuario es paciente
if (!isset($_SESSION['idUsuario']) || $_SESSION['rol'] != 'paciente') {
    header("Location: ../inicio/inicioSesion.php");
    exit();
}

// Verificar que se haya pasado el ID de la cita
if (isset($_GET['id'])) {
    $idCita = intval($_GET['id']); // Sanitizar el ID de la cita

    try {
        // Actualizar el estado de la cita a "Cancelada"
        $query = "UPDATE citamedica SET estado = 'Cancelada' WHERE idCita = ? AND estado != 'Cancelada'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $idCita);

        if ($stmt->execute()) {
            $_SESSION['success'] = "La cita ha sido cancelada exitosamente.";
        } else {
            $_SESSION['error'] = "Error al cancelar la cita. Por favor, inténtalo de nuevo.";
        }
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error'] = "Ocurrió un error: " . $e->getMessage();
    } finally {
        $conn->close();
        header("Location: gestionarCitasPaciente.php");
        exit();
    }
} else {
    $_SESSION['error'] = "ID de cita no válido.";
    header("Location: gestionarCitasPaciente.php");
    exit();
}
?>
