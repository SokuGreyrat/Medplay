<?php
session_start();
if (!isset($_SESSION['idUsuario']) || $_SESSION['rol'] != 'profesional') {
    header("Location: ../inicio/inicioSesion.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Profesional</title>
    <link rel="icon" href="../diseño/logo.png" type="image/jpg" sizes="16x16">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/index.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="#">MedPlay - Dashboard Profesional</a>
        <a href="../inicio/inicioSesion.php" class="btn btn-danger">Cerrar Sesión</a>
    </div>
</nav>

<!-- Contenido principal -->
<div class="container mt-5">
    <h2 class="text-center mb-4">Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?>!</h2>
    <p class="text-center mb-5">Seleccione una opción para continuar:</p>

    <!-- Botones de navegación -->
    <div class="row text-center">
        <div class="col-md-4 mb-3">
            <a href="asignacion.php" class="btn btn-primary btn-lg w-100">Consulta, Tratamiento y Misiones</a>
        </div>
        <div class="col-md-4 mb-3">
            <a href="gestionarCitas.php" class="btn btn-primary btn-lg w-100">Gestión de Citas Médicas</a>
        </div>
        <div class="col-md-4 mb-3">
            <a href="asignarcitas.php" class="btn btn-primary btn-lg w-100">Asignar Citas</a>
        </div>
        <div class="col-md-4 mb-3">
            <a href="gestionarHistorial.php" class="btn btn-primary btn-lg w-100">Gestión de Historial Médico</a>
        </div>
        <div class="col-md-4 mb-3">
            <a href="reportes.php" class="btn btn-primary btn-lg w-100">Asignar Misiones</a>
        </div>
        
        <!-- Nuevo botón: Registrar Antecedentes Clínicos -->
        <div class="col-md-4 mb-3">
            <a href="registrarAntecedentes.php" class="btn btn-primary btn-lg w-100">Registrar Antecedentes Clínicos</a>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="mt-auto text-center text-white bg-dark py-3">
    <div class="container">
        <p class="mb-0">&copy; 2024 MEDPLAY. Todos los derechos reservados.</p>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
