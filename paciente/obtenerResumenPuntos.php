<?php
session_start();
include '../db.php'; // Conexión a la base de datos

if (!isset($_SESSION['idUsuario']) || $_SESSION['rol'] != 'paciente') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$idUsuario = $_SESSION['idUsuario'];

// Obtener el total acumulado de puntos desde la tabla `puntos`
$queryTotalPuntos = "SELECT COALESCE(MAX(total), 0) AS totalPuntos FROM puntos WHERE idUsuario = ?";
$stmt = $conn->prepare($queryTotalPuntos);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$result = $stmt->get_result();
$totalPuntos = $result->fetch_assoc()['totalPuntos'] ?? 0;

// Calcular todos los múltiplos de 150 alcanzados
$multiples = [];
for ($i = 150; $i <= $totalPuntos; $i += 150) {
    $multiples[] = $i;
}

// Obtener recompensas existentes del usuario
$queryRecompensasExistentes = "SELECT descripcion, puntosRequeridos FROM recompensa WHERE idUsuario = ?";
$stmtRecompensas = $conn->prepare($queryRecompensasExistentes);
$stmtRecompensas->bind_param("i", $idUsuario);
$stmtRecompensas->execute();
$resultRecompensas = $stmtRecompensas->get_result();

$recompensasExistentes = [];
while ($row = $resultRecompensas->fetch_assoc()) {
    $recompensasExistentes[] = $row;
}

// Generar recompensas para los múltiplos que no tienen recompensas asignadas
$recompensasGeneradas = [];
foreach ($multiples as $multiplo) {
    // Verificar si ya existe una recompensa para este múltiplo
    $queryVerificarRecompensa = "SELECT COUNT(*) AS total FROM recompensa WHERE idUsuario = ? AND puntosRequeridos = ?";
    $stmtVerificar = $conn->prepare($queryVerificarRecompensa);
    $stmtVerificar->bind_param("ii", $idUsuario, $multiplo);
    $stmtVerificar->execute();
    $resultVerificar = $stmtVerificar->get_result();
    $dataVerificar = $resultVerificar->fetch_assoc();

    if ($dataVerificar['total'] == 0) {
        // Generar una recompensa aleatoria
        $recompensasPosibles = [
            "Consulta médica gratuita",
            "Descuento del 20% en medicamentos recetados",
            "Acceso premium a contenido educativo sobre salud durante un mes",
            "Análisis de laboratorio gratuito (glucosa o colesterol)",
            "Sesión de terapia física gratuita",
            "Consulta de seguimiento gratuita con un especialista",
            "Descuento del 30% en vacunas opcionales",
            "Check-up básico gratuito (presión arterial, peso, IMC)",
            "Entrega gratuita de medicamentos a domicilio por una semana",
            "Suscripción gratuita a boletines de salud personalizados"
        ];

        $descripcionRecompensa = $recompensasPosibles[random_int(0, count($recompensasPosibles) - 1)];

        // Insertar la recompensa en la tabla `recompensa`
        $queryInsertarRecompensa = "INSERT INTO recompensa (descripcion, puntosRequeridos, idUsuario) VALUES (?, ?, ?)";
        $stmtInsertarRecompensa = $conn->prepare($queryInsertarRecompensa);
        $stmtInsertarRecompensa->bind_param("sii", $descripcionRecompensa, $multiplo, $idUsuario);

        if ($stmtInsertarRecompensa->execute()) {
            $recompensasGeneradas[] = [
                'puntosRequeridos' => $multiplo,
                'descripcion' => $descripcionRecompensa
            ];
        }
    }
}

// Combinar recompensas existentes y generadas
$recompensasTotales = array_merge($recompensasExistentes, $recompensasGeneradas);

// Devolver el total de puntos y las recompensas generadas en formato JSON
echo json_encode([
    'success' => true,
    'totalPuntos' => $totalPuntos,
    'recompensas' => $recompensasTotales
]);

$conn->close();
?>
