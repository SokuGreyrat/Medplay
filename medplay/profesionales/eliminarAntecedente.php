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

// Eliminar el antecedente
$query = "DELETE FROM antecedentesclinicos WHERE idAntecedente = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $idAntecedente);
$stmt->execute();

header("Location: visualizarAntecedentes.php?deleted=1");
exit();
?>
