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

// Validar el ID del paciente
$idPaciente = filter_input(INPUT_POST, 'idPaciente', FILTER_VALIDATE_INT);

if (!$idPaciente) {
    exit('Error: ID de paciente inválido.');
}

// Consultar información del paciente
$queryResumen = "
    SELECT u.nombre, p.edad, p.tipoSangre, p.peso, p.altura, 
           p.telefonoEmergencia, p.fechaNacimiento, p.sexo
    FROM paciente p
    INNER JOIN usuario u ON p.idUsuario = u.idUsuario
    WHERE p.idPaciente = ?";
$stmt = $conn->prepare($queryResumen);

if (!$stmt) {
    exit('Error en la consulta del paciente: ' . $conn->error);
}

$stmt->bind_param("i", $idPaciente);
$stmt->execute();
$resumenPaciente = $stmt->get_result()->fetch_assoc();

if (!$resumenPaciente) {
    exit('Error: No se encontró información del paciente.');
}

// Consultas relacionadas con el historial médico
$queries = [
    'vacunas' => "SELECT nombre FROM vacuna WHERE idPaciente = ?",
    'citas' => "SELECT fecha, hora, motivo, estado FROM citamedica WHERE idPaciente = ? ORDER BY fecha DESC LIMIT 10",
    'tratamientos' => "SELECT consulta, fecha, motivo, diagnostico, medicamento, dosis, frecuencia, duracion 
                       FROM tratamiento WHERE idPaciente = ? ORDER BY fecha DESC LIMIT 10",
    'antecedentes' => "SELECT tipoAntecedente, descripcion, fechaEvento, hospitalTratante, 
                              doctorTratante, tratamiento, observaciones
                       FROM antecedentesclinicos WHERE idPaciente = ?"
];

// Ejecutar consultas y almacenar resultados
$data = [];
foreach ($queries as $key => $query) {
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        exit("Error en la consulta de $key: " . $conn->error);
    }
    $stmt->bind_param("i", $idPaciente);
    $stmt->execute();
    $data[$key] = $stmt->get_result();
}

// Crear el PDF
$pdf = new TCPDF();
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();
$pdf->SetMargins(15, 15, 15);

// Fecha de generación
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetY(10);
$pdf->SetX(-50);
$pdf->Cell(0, 5, 'Generado: ' . date('Y-m-d H:i:s'), 0, 0, 'R');

// Encabezado
$pdf->Image('../diseño/logo.png', 15, 10, 20, 20, 'PNG');
$pdf->SetY(20);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(0, 102, 204); // Azul
$pdf->Cell(0, 5, 'Plataforma MedPlay', 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0); // Negro
$pdf->Cell(0, 5, 'Historial Médico', 0, 1, 'C');
$pdf->Ln(5);

// Función para escribir secciones
function writeSection($pdf, $title, $content, $defaultMsg = 'No hay información disponible.') {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor(230, 240, 255);
    $pdf->SetTextColor(0, 102, 204);
    $pdf->Cell(0, 8, $title, 0, 1, 'L', 1);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Ln(2);
    if ($content) {
        $pdf->writeHTML($content, true, false, true, false, '');
    } else {
        $pdf->MultiCell(0, 5, $defaultMsg, 0, 'L');
    }
    $pdf->Ln(5);
}

// Información General del Paciente
$htmlInfoPaciente = <<<EOD
<table style="width: 100%; border-collapse: collapse; font-size: 10pt;">
    <tr style="background-color: #f2f2f2; text-align: left;">
        <th style="padding: 5px; border: 1px solid #ddd;">Campo</th>
        <th style="padding: 5px; border: 1px solid #ddd;">Detalle</th>
    </tr>
    <tr>
        <td style="padding: 5px; border: 1px solid #ddd;">Nombre</td>
        <td style="padding: 5px; border: 1px solid #ddd;">{$resumenPaciente['nombre']}</td>
    </tr>
    <tr>
        <td style="padding: 5px; border: 1px solid #ddd;">Edad</td>
        <td style="padding: 5px; border: 1px solid #ddd;">{$resumenPaciente['edad']} años</td>
    </tr>
    <tr>
        <td style="padding: 5px; border: 1px solid #ddd;">Tipo de Sangre</td>
        <td style="padding: 5px; border: 1px solid #ddd;">{$resumenPaciente['tipoSangre']}</td>
    </tr>
    <tr>
        <td style="padding: 5px; border: 1px solid #ddd;">Peso</td>
        <td style="padding: 5px; border: 1px solid #ddd;">{$resumenPaciente['peso']} kg</td>
    </tr>
    <tr>
        <td style="padding: 5px; border: 1px solid #ddd;">Altura</td>
        <td style="padding: 5px; border: 1px solid #ddd;">{$resumenPaciente['altura']} m</td>
    </tr>
    <tr>
        <td style="padding: 5px; border: 1px solid #ddd;">Teléfono de Emergencia</td>
        <td style="padding: 5px; border: 1px solid #ddd;">{$resumenPaciente['telefonoEmergencia']}</td>
    </tr>
    <tr>
        <td style="padding: 5px; border: 1px solid #ddd;">Fecha de Nacimiento</td>
        <td style="padding: 5px; border: 1px solid #ddd;">{$resumenPaciente['fechaNacimiento']}</td>
    </tr>
    <tr>
        <td style="padding: 5px; border: 1px solid #ddd;">Sexo</td>
        <td style="padding: 5px; border: 1px solid #ddd;">{$resumenPaciente['sexo']}</td>
    </tr>
</table>
EOD;

writeSection($pdf, 'Información General', $htmlInfoPaciente);

// Vacunas
$vacunasHTML = '<ul style="list-style-type: none; padding: 0; font-size: 10pt;">';
while ($row = $data['vacunas']->fetch_assoc()) {
    $vacunasHTML .= "<li style='margin-bottom: 5px;'><span style='color: #0056b3;'>•</span> {$row['nombre']}</li>";
}
$vacunasHTML .= '</ul>';

writeSection($pdf, 'Vacunas', $vacunasHTML);


// Antecedentes Clínicos
$antecedentes = '';
while ($row = $data['antecedentes']->fetch_assoc()) {
    $antecedentes .= <<<EOD
    <div style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 5px; background-color: #f9f9f9;">
        <h4 style="margin: 0; color: #0056b3;">{$row['tipoAntecedente']} ({$row['fechaEvento']})</h4>
        <p><strong>Descripción:</strong> {$row['descripcion']}</p>
        <p><strong>Hospital Tratante:</strong> {$row['hospitalTratante']}</p>
        <p><strong>Doctor Tratante:</strong> {$row['doctorTratante']}</p>
        <p><strong>Tratamiento:</strong> {$row['tratamiento']}</p>
        <p><strong>Observaciones:</strong> {$row['observaciones']}</p>
    </div>
EOD;
}
writeSection($pdf, 'Antecedentes Clínicos', $antecedentes);


// Citas Médicas
$citas = '';
while ($row = $data['citas']->fetch_assoc()) {
    $estatus = '';
    // Determinar el estado visual de la cita
    switch ($row['estado']) {
        case 'Completada':
            $estatus = '<span style="color: green; font-weight: bold;">Completada</span>';
            break;
        case 'Cancelada':
            $estatus = '<span style="color: red; font-weight: bold;">Cancelada</span>';
            break;
        case 'Próxima':
            $estatus = '<span style="color: orange; font-weight: bold;">Próxima</span>';
            break;
        default:
            $estatus = '<span style="color: gray; font-weight: bold;">Pendiente</span>';
    }

    $citas .= <<<EOD
    <div style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 5px; background-color: #f9f9f9;">
        <h4 style="margin: 0; color: #0056b3;">Cita ({$row['fecha']} - {$row['hora']})</h4>
        <p><strong>Motivo:</strong> {$row['motivo']}</p>
        <p><strong>Estado:</strong> {$estatus}</p>
    </div>
EOD;
}
writeSection($pdf, 'Citas Médicas', $citas);


// Tratamientos
$tratamientos = '';
while ($row = $data['tratamientos']->fetch_assoc()) {
    $tratamientos .= <<<EOD
    <div style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 5px; background-color: #f9f9f9;">
        <h4 style="margin: 0; color: #0056b3;">Consulta ({$row['fecha']})</h4>
        <p><strong>Motivo:</strong> {$row['motivo']}</p>
        <p><strong>Diagnóstico:</strong> {$row['diagnostico']}</p>
        <p><strong>Medicamento:</strong></p>
        <ul style="margin-left: 20px;">
            <li><strong>Nombre:</strong> {$row['medicamento']}</li>
            <li><strong>Dosis:</strong> {$row['dosis']}</li>
            <li><strong>Frecuencia:</strong> {$row['frecuencia']}</li>
            <li><strong>Duración:</strong> {$row['duracion']} días</li>
        </ul>
    </div>
EOD;
}
writeSection($pdf, 'Consultas y Tratamientos', $tratamientos);


// Salida del PDF
$pdf->Output('historial_medico.pdf', 'D');
exit();
?>
