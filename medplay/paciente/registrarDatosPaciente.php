<?php
session_start();
include '../db.php';

// Verificar si el usuario tiene rol de paciente
if (!isset($_SESSION['idUsuario']) || $_SESSION['rol'] != 'paciente') {
    header("Location: ../inicio/inicioSesion.php");
    exit();
}

// Obtener el idUsuario de la sesión
$idUsuario = $_SESSION['idUsuario'];

// Verificar si el paciente existe en la tabla 'paciente'
$query = "SELECT idPaciente FROM paciente WHERE idUsuario = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Error al preparar la consulta SQL (paciente): " . $conn->error);
}
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$result = $stmt->get_result();
$paciente = $result->fetch_assoc();

$idPaciente = $paciente['idPaciente'] ?? null;

if (!$idPaciente) {
    die("No se encontró un paciente asociado al usuario actual.");
}

// Procesar el formulario si se envía
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener datos del formulario
    $edad = intval($_POST['edad']);
    $tipoSangre = trim($_POST['tipoSangre']);
    $peso = floatval($_POST['peso']);
    $altura = floatval($_POST['altura']);
    $fechaNacimiento = $_POST['fechaNacimiento'];
    $telefonoEmergencia = trim($_POST['telefonoEmergencia']);
    $sexo = trim($_POST['sexo']);
    $problemasMedicos = trim($_POST['problemas']);
    $vacunas = $_POST['vacunas'] ?? []; // Array de vacunas seleccionadas

    try {
        // Iniciar transacción
        $conn->begin_transaction();

        // Actualizar datos del paciente
        $updatePaciente = "UPDATE paciente 
            SET edad = ?, tipoSangre = ?, peso = ?, altura = ?, fechaNacimiento = ?, 
                telefonoEmergencia = ?, sexo = ?, problemasMedicos = ?, datosCompletos = 1 
            WHERE idUsuario = ?";
        $stmt = $conn->prepare($updatePaciente);
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta SQL de actualización: " . $conn->error);
        }
        $stmt->bind_param(
            "isddssssi", 
            $edad, $tipoSangre, $peso, $altura, $fechaNacimiento, $telefonoEmergencia, 
            $sexo, $problemasMedicos, $idUsuario
        );
        $stmt->execute();

        // Eliminar vacunas antiguas
        $deleteVacunas = "DELETE FROM vacuna WHERE idPaciente = ?";
        $stmt = $conn->prepare($deleteVacunas);
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta SQL (eliminar vacunas): " . $conn->error);
        }
        $stmt->bind_param("i", $idPaciente);
        $stmt->execute();

        // Insertar vacunas seleccionadas
        $insertVacuna = "INSERT INTO vacuna (idPaciente, nombre) VALUES (?, ?)";
        $stmt = $conn->prepare($insertVacuna);
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta SQL (insertar vacuna): " . $conn->error);
        }
        foreach ($vacunas as $vacuna) {
            $stmt->bind_param("is", $idPaciente, $vacuna);
            $stmt->execute();
        }

        // Confirmar transacción
        $conn->commit();
        header("Location: ../paciente/pacienteDashboard.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error al guardar la información: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Información</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center mb-4">Complete su Información</h2>
    <form method="POST">
        <!-- Edad -->
        <div class="mb-3">
            <label for="edad" class="form-label">Edad</label>
            <input type="number" name="edad" id="edad" class="form-control" required min="1" placeholder="Ingrese su edad">
        </div>

        <!-- Tipo de Sangre -->
        <div class="mb-3">
            <label for="tipoSangre" class="form-label">Tipo de Sangre</label>
            <select name="tipoSangre" id="tipoSangre" class="form-select" required>
                <option value="" disabled selected>Seleccione su tipo de sangre</option>
                <option value="A+">A+</option>
                <option value="A-">A-</option>
                <option value="B+">B+</option>
                <option value="B-">B-</option>
                <option value="AB+">AB+</option>
                <option value="AB-">AB-</option>
                <option value="O+">O+</option>
                <option value="O-">O-</option>
            </select>
        </div>

        <!-- Peso -->
        <div class="mb-3">
            <label for="peso" class="form-label">Peso (kg)</label>
            <input type="number" name="peso" id="peso" step="0.01" class="form-control" required placeholder="Ingrese su peso">
        </div>

        <!-- Altura -->
        <div class="mb-3">
            <label for="altura" class="form-label">Altura (m)</label>
            <input type="number" name="altura" id="altura" step="0.01" class="form-control" required placeholder="Ingrese su altura">
        </div>

        <!-- Fecha de Nacimiento -->
        <div class="mb-3">
            <label for="fechaNacimiento" class="form-label">Fecha de Nacimiento</label>
            <input type="date" name="fechaNacimiento" id="fechaNacimiento" class="form-control" required>
        </div>

        <!-- Teléfono de Emergencia -->
        <div class="mb-3">
            <label for="telefonoEmergencia" class="form-label">Teléfono de Emergencia</label>
            <input type="text" name="telefonoEmergencia" id="telefonoEmergencia" class="form-control" required placeholder="Ingrese su teléfono de emergencia">
        </div>

        <!-- Sexo -->
        <div class="mb-3">
            <label for="sexo" class="form-label">Sexo</label>
            <select name="sexo" id="sexo" class="form-select" required>
                <option value="" disabled selected>Seleccione su sexo</option>
                <option value="Masculino">Masculino</option>
                <option value="Femenino">Femenino</option>
                <option value="Otro">Otro</option>
            </select>
        </div>

        <!-- Problemas Médicos -->
        <div class="mb-3">
            <label for="problemas" class="form-label">Problemas Médicos</label>
            <textarea name="problemas" id="problemas" class="form-control" rows="2" required placeholder="Describa sus problemas médicos"></textarea>
        </div>

        <!-- Vacunas -->
        <div class="mb-3">
            <label class="form-label">Vacunas Aplicadas</label>

            <!-- Recién nacidos -->
            <h6 class="fw-bold mt-2">Recién nacidos</h6>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="vacunas[]" value="BCG" id="bcg">
                <label class="form-check-label" for="bcg">BCG (Tuberculosis)</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="vacunas[]" value="Hepatitis B" id="hepB">
                <label class="form-check-label" for="hepB">Hepatitis B</label>
            </div>

            <!-- Lactantes -->
            <h6 class="fw-bold mt-3">Lactantes (2, 4 y 6 meses)</h6>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="vacunas[]" value="Pentavalente Acelular" id="pentavalente">
                <label class="form-check-label" for="pentavalente">Pentavalente Acelular (Difteria, Tosferina, Tétanos, Poliomielitis, Haemophilus influenzae tipo b)</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="vacunas[]" value="Rotavirus" id="rotavirus">
                <label class="form-check-label" for="rotavirus">Rotavirus</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="vacunas[]" value="Neumocócica Conjugada" id="neumococica">
                <label class="form-check-label" for="neumococica">Neumocócica Conjugada</label>
            </div>

            <!-- 12 meses -->
            <h6 class="fw-bold mt-3">12 meses</h6>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="vacunas[]" value="Influenza" id="influenza">
                <label class="form-check-label" for="influenza">Influenza (Primera dosis)</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="vacunas[]" value="Triple Viral (SRP)" id="tripleViral">
                <label class="form-check-label" for="tripleViral">Triple Viral (Sarampión, Rubéola, Parotiditis)</label>
            </div>

            <!-- Refuerzos y niños mayores -->
            <h6 class="fw-bold mt-3">Refuerzos (18 meses y 4 años)</h6>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="vacunas[]" value="DPT (Difteria, Tosferina, Tétanos)" id="dpt">
                <label class="form-check-label" for="dpt">DPT (Difteria, Tosferina, Tétanos)</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="vacunas[]" value="Influenza Refuerzo" id="influenzaRefuerzo">
                <label class="form-check-label" for="influenzaRefuerzo">Influenza (Refuerzos anuales)</label>
            </div>

            <!-- Adolescentes -->
            <h6 class="fw-bold mt-3">Adolescentes</h6>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="vacunas[]" value="Virus del Papiloma Humano (VPH)" id="vph">
                <label class="form-check-label" for="vph">Virus del Papiloma Humano (VPH)</label>
            </div>

            <!-- Adultos -->
            <h6 class="fw-bold mt-3">Adultos y mayores</h6>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="vacunas[]" value="COVID-19" id="covid">
                <label class="form-check-label" for="covid">COVID-19</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="vacunas[]" value="Hepatitis A" id="hepA">
                <label class="form-check-label" for="hepA">Hepatitis A</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="vacunas[]" value="Varicela" id="varicela">
                <label class="form-check-label" for="varicela">Varicela</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="vacunas[]" value="Neumocócica para Adultos" id="neumococicaAdultos">
                <label class="form-check-label" for="neumococicaAdultos">Neumocócica para Adultos</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="vacunas[]" value="Influenza Mayores" id="influenzaMayores">
                <label class="form-check-label" for="influenzaMayores">Influenza (Mayores de 60 años)</label>
            </div>
        </div>

        <!-- Botón para enviar -->
        <button type="submit" class="btn btn-success w-100">Guardar Información</button>
    </form>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
