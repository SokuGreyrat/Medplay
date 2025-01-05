<?php
session_start();
include '../db.php'; // Conexión a la base de datos

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['idMision'], $data['puntos']) || !isset($_SESSION['idUsuario'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
    exit();
}

$idMision = intval($data['idMision']);
$puntos = intval($data['puntos']);
$idUsuario = $_SESSION['idUsuario'];

$conn->begin_transaction(); // Inicia la transacción

try {
    // 1. Marcar la misión como completada
    $queryActualizarMision = "
        UPDATE usuarios_misiones 
        SET estadoMision = 'Completada', fechaCompletada = NOW() 
        WHERE idMision = ? AND idUsuario = ?";
    $stmtActualizar = $conn->prepare($queryActualizarMision);
    $stmtActualizar->bind_param("ii", $idMision, $idUsuario);
    $stmtActualizar->execute();

    if ($stmtActualizar->affected_rows === 0) {
        throw new Exception('No se pudo actualizar la misión como completada.');
    }

    // 2. Calcular el total de puntos
    $queryUltimoTotal = "SELECT COALESCE(MAX(total), 0) AS ultimoTotal FROM puntos WHERE idUsuario = ?";
    $stmtUltimoTotal = $conn->prepare($queryUltimoTotal);
    $stmtUltimoTotal->bind_param("i", $idUsuario);
    $stmtUltimoTotal->execute();
    $ultimoTotal = $stmtUltimoTotal->get_result()->fetch_assoc()['ultimoTotal'];

    $nuevoTotal = $ultimoTotal + $puntos;

    // 3. Insertar los puntos en la tabla `puntos`
    $queryInsertarPuntos = "
        INSERT INTO puntos (idUsuario, actividad, puntosAsignados, total, fechaRegistro) 
        VALUES (?, 'Misión Completada', ?, ?, NOW())";
    $stmtPuntos = $conn->prepare($queryInsertarPuntos);
    $stmtPuntos->bind_param("iii", $idUsuario, $puntos, $nuevoTotal);
    $stmtPuntos->execute();

    if ($stmtPuntos->affected_rows === 0) {
        throw new Exception('No se pudo registrar los puntos.');
    }

    // 4. Obtener la fecha de completado
    $queryFecha = "SELECT fechaCompletada FROM usuarios_misiones WHERE idMision = ? AND idUsuario = ?";
    $stmtFecha = $conn->prepare($queryFecha);
    $stmtFecha->bind_param("ii", $idMision, $idUsuario);
    $stmtFecha->execute();
    $fechaCompletada = $stmtFecha->get_result()->fetch_assoc()['fechaCompletada'];

    $conn->commit(); // Confirmar la transacción

    echo json_encode(['success' => true, 'fechaCompletada' => $fechaCompletada]);
} catch (Exception $e) {
    $conn->rollback(); // Revertir los cambios
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
