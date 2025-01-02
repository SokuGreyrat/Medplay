<?php
session_start();
include '../db.php';

// Capturar datos del formulario
$idPaciente = $_POST['idPaciente'];
$idCita = $_POST['idCita']; // ID de la cita seleccionada
$consulta = $_POST['consulta'];
$fecha = $_POST['fecha'];
$motivo = $_POST['motivo'];
$diagnostico = $_POST['diagnostico'];
$medicamento = $_POST['medicamento'];
$dosis = $_POST['dosis'];
$frecuencia = $_POST['frecuencia'];
$duracion = $_POST['duracion'];

// Iniciar transacción para garantizar consistencia
$conn->begin_transaction();

try {
    // Obtener el idUsuario del paciente
    $queryUsuario = "SELECT idUsuario FROM paciente WHERE idPaciente = ?";
    $stmtUsuario = $conn->prepare($queryUsuario);
    $stmtUsuario->bind_param("i", $idPaciente);
    $stmtUsuario->execute();
    $resultUsuario = $stmtUsuario->get_result();
    $rowUsuario = $resultUsuario->fetch_assoc();
    $idUsuario = $rowUsuario['idUsuario'];

    if (!$idUsuario) {
        throw new Exception("No se pudo obtener el idUsuario del paciente.");
    }

    // Insertar en la tabla tratamiento
    $queryTratamiento = "INSERT INTO tratamiento 
                        (idPaciente, consulta, fecha, motivo, diagnostico, medicamento, dosis, frecuencia, duracion) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmtTratamiento = $conn->prepare($queryTratamiento);
    $stmtTratamiento->bind_param("isssssssi", $idPaciente, $consulta, $fecha, $motivo, $diagnostico, $medicamento, $dosis, $frecuencia, $duracion);
    $stmtTratamiento->execute();

    // Actualizar el estado de la cita seleccionada
    $queryActualizarCita = "UPDATE citamedica 
                            SET estado = 'Completada' 
                            WHERE idCita = ?";
    $stmtActualizarCita = $conn->prepare($queryActualizarCita);
    $stmtActualizarCita->bind_param("i", $idCita);
    $stmtActualizarCita->execute();

    // Asignar 50 puntos al paciente
    $puntosAsignados = 50;
    $actividad = "Cita completada";

    // Obtener el total actual de puntos del usuario
    $queryTotalPuntos = "SELECT total FROM puntos WHERE idUsuario = ? ORDER BY fechaRegistro DESC LIMIT 1";
    $stmtTotalPuntos = $conn->prepare($queryTotalPuntos);
    $stmtTotalPuntos->bind_param("i", $idUsuario);
    $stmtTotalPuntos->execute();
    $resultTotalPuntos = $stmtTotalPuntos->get_result();
    $totalPuntosActual = $resultTotalPuntos->num_rows > 0 ? $resultTotalPuntos->fetch_assoc()['total'] : 0;

    $nuevoTotalPuntos = $totalPuntosActual + $puntosAsignados;

    // Insertar los puntos asignados en la tabla puntos
    $queryInsertarPuntos = "INSERT INTO puntos (idUsuario, puntosAsignados, total, actividad, fechaRegistro) 
                            VALUES (?, ?, ?, ?, NOW())";
    $stmtInsertarPuntos = $conn->prepare($queryInsertarPuntos);
    $stmtInsertarPuntos->bind_param("iiis", $idUsuario, $puntosAsignados, $nuevoTotalPuntos, $actividad);
    $stmtInsertarPuntos->execute();

    // Confirmar la transacción
    $conn->commit();

    $_SESSION['mensaje_exito'] = "Tratamiento registrado correctamente, cita marcada como completada y puntos asignados.";
    header("Location: asignacion.php");
    exit();
} catch (Exception $e) {
    // Revertir cambios si ocurre un error
    $conn->rollback();
    $_SESSION['mensaje_error'] = "Error al registrar tratamiento, actualizar cita o asignar puntos: " . $e->getMessage();
    header("Location: asignacion.php");
    exit();
}
?>
