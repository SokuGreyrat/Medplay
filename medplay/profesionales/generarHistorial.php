<?php
session_start();
date_default_timezone_set('America/Mexico_City');
include '../db.php';
require_once('../tcpdf/tcpdf.php');

// Verificar permisos
if (!isset($_SESSION['idUsuario']) || $_SESSION['rol'] != 'profesional') {
    header("Location: ../inicio/inicioSesion.php");
    exit();
}

$idPaciente = $_POST['idPaciente'] ?? null;

if (!$idPaciente) {
    exit('Error: No se especificó el paciente.');
}

// Consultas SQL
$queryResumen = "SELECT u.nombre, p.edad, p.tipoSangre, p.peso, p.altura, p.telefonoEmergencia, p.fechaNacimiento, p.sexo
                 FROM paciente p INNER JOIN usuario u ON p.idUsuario = u.idUsuario WHERE p.idPaciente = ?";
$stmt = $conn->prepare($queryResumen);
$stmt->bind_param("i", $idPaciente);
$stmt->execute();
$resumenPaciente = $stmt->get_result()->fetch_assoc();

if (!$resumenPaciente) {
    exit('Error: No se encontró información del paciente.');
}

// Otras consultas
$queries = [
    'vacunas' => "SELECT nombre FROM vacuna WHERE idPaciente = ?",
    'citas' => "SELECT fecha, hora, motivo, estado FROM citamedica WHERE idPaciente = ?",
    'tratamientos' => "SELECT consulta, fecha, motivo, diagnostico, medicamento, dosis, frecuencia, duracion FROM tratamiento WHERE idPaciente = ?",
    'antecedentes' => "SELECT tipoAntecedente, descripcion, fechaEvento, hospitalTratante, doctorTratante, tratamiento, observaciones FROM antecedentesclinicos WHERE idPaciente = ?"
];

$data = [];
foreach ($queries as $key => $query) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $idPaciente);
    $stmt->execute();
    $data[$key] = $stmt->get_result();
}

// Crear PDF
$pdf = new TCPDF();
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();
$pdf->SetMargins(15, 15, 15);

// Fecha de generación en la parte superior derecha
$fechaGeneracion = date('Y-m-d H:i:s');
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetY(10);
$pdf->SetX(-50);
$pdf->Cell(0, 5, 'Fecha de generación: ' . $fechaGeneracion, 0, 0, 'R');

// Encabezado general
$pdf->Image('../diseño/logo.png', 15, 10, 20, 20, 'PNG');
$pdf->SetY(20);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(0, 102, 204); // Azul
$pdf->Cell(0, 5, 'Plataforma MedPlay', 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0); // Negro
$pdf->Cell(0, 5, 'Historial Médico', 0, 1, 'C');
$pdf->Ln(5);

// Función para secciones con color de fondo y bordes
function writeSection($pdf, $title, $content, $defaultMsg = 'No hay información disponible.') {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor(230, 240, 255); // Color de fondo azul claro
    $pdf->SetTextColor(0, 102, 204); // Texto azul
    $pdf->Cell(0, 8, $title, 0, 1, 'L', 1);
    $pdf->SetTextColor(0, 0, 0); // Texto negro
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Ln(2);
    if ($content) {
        $pdf->writeHTML($content, true, false, true, false, '');
    } else {
        $pdf->MultiCell(0, 5, $defaultMsg, 0, 'L');
    }
    $pdf->Ln(5); // Agregar espacio adicional entre secciones
}

// Información del paciente
$htmlInfoPaciente = <<<EOD
<style>
    p { margin: 0; padding: 2px; line-height: 1.2; font-size: 10pt; background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 3px; }
    strong { color: #0056b3; }
</style>
<p><strong>Nombre:</strong> {$resumenPaciente['nombre']}</p>
<p><strong>Edad:</strong> {$resumenPaciente['edad']} años</p>
<p><strong>Tipo de Sangre:</strong> {$resumenPaciente['tipoSangre']}</p>
<p><strong>Peso:</strong> {$resumenPaciente['peso']} kg</p>
<p><strong>Altura:</strong> {$resumenPaciente['altura']} m</p>
<p><strong>Teléfono de Emergencia:</strong> {$resumenPaciente['telefonoEmergencia']}</p>
<p><strong>Fecha de Nacimiento:</strong> {$resumenPaciente['fechaNacimiento']}</p>
<p><strong>Sexo:</strong> {$resumenPaciente['sexo']}</p>
EOD;

writeSection($pdf, 'Información del Paciente', $htmlInfoPaciente);

// Vacunas
$vacunas = '';
while ($row = $data['vacunas']->fetch_assoc()) {
    $vacunas .= "<p>- {$row['nombre']}</p>";
}
writeSection($pdf, 'Vacunas', $vacunas);

// Antecedentes Clínicos
$antecedentes = '';
while ($row = $data['antecedentes']->fetch_assoc()) {
    $antecedentes .= "<p><strong>{$row['tipoAntecedente']}</strong>: {$row['descripcion']} ({$row['fechaEvento']})</p>";
}
writeSection($pdf, 'Antecedentes Clínicos', $antecedentes);

// Citas Médicas
$citas = '';
while ($row = $data['citas']->fetch_assoc()) {
    $citas .= "<p><strong>{$row['fecha']} {$row['hora']}</strong>: {$row['motivo']} ({$row['estado']})</p>";
}
writeSection($pdf, 'Citas Médicas', $citas);

// Tratamientos
$tratamientos = '';
while ($row = $data['tratamientos']->fetch_assoc()) {
    $tratamientos .= "<p><strong>Consulta ({$row['fecha']})</strong>: {$row['motivo']}<br>
    <strong>Diagnóstico:</strong> {$row['diagnostico']}<br>
    <strong>Medicamento:</strong> {$row['medicamento']} ({$row['dosis']}, {$row['frecuencia']}, {$row['duracion']} días)</p>";
}
writeSection($pdf, 'Consultas y Tratamientos', $tratamientos);

// Salida del PDF
$pdf->Output('historial_medico.pdf', 'D');
exit();
?>
