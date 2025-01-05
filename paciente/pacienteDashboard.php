<?php
session_start();
if (!isset($_SESSION['idUsuario']) || $_SESSION['rol'] != 'paciente') {
    header("Location: ../inicio/inicioSesion.php");
    exit();
}

include '../db.php'; // Conexión a la base de datos

$idUsuario = $_SESSION['idUsuario'];
$nombre = $_SESSION['nombre'];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Paciente</title>
    <link rel="icon" href="../diseño/logo.png" type="image/jpg" sizes="16x16">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="#">MedPlay - Dashboard Paciente</a>

        <!-- Botón de Notificaciones con Despliegue -->
        <div class="d-flex align-items-center">
                <div class="dropdown">
            <button class="btn btn-outline-light d-flex align-items-center dropdown-toggle" type="button" id="dropdownNotificaciones" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-bell me-1"></i> Notificaciones
            </button>
            <ul class="dropdown-menu dropdown-menu-end p-3" id="listaNotificaciones" aria-labelledby="dropdownNotificaciones" style="width: 300px; max-height: 400px; overflow-y: auto;">
                <!-- Este contenido será actualizado dinámicamente con JavaScript -->
                <p class="text-center">Cargando notificaciones...</p>
            </ul>
        </div>





            
            <a href="../inicio/inicioSesion.php" class="btn btn-danger ms-2">Cerrar Sesión</a>
        </div>
    </div>
</nav>

<!-- Contenido principal -->
<div class="container mt-5">
    <h2 class="text-center mb-4">Bienvenido, <?php echo htmlspecialchars($nombre); ?>!</h2>

    <!-- Secciones -->
    <div class="row">
         <!-- Resumen de Puntos -->
        <div class="col-md-4 mb-3">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">Resumen de Puntos</div>
                <div class="card-body text-center">
                    <h3 id="totalPuntos">Cargando...</h3>
                    <p>Acumulados hasta la fecha.</p>
                </div>
            </div>
        </div>
        <!-- Recompensas -->
        <div class="col-md-4 mb-3">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">Recompensas</div>
                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                    <ul class="list-unstyled" id="listaRecompensas">
                        <!-- Este contenido se actualizará dinámicamente con JavaScript -->
                        <p class="text-center">Cargando recompensas...</p>
                    </ul>
                </div>
            </div>
        </div>




        <!-- Citas Próximas -->
        <div class="col-md-4 mb-3">
            <div class="card shadow">
                <div class="card-header bg-info text-white">Citas Próximas</div>
                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                    <ul class="list-unstyled" id="listaCitasProximas">
                        <!-- Este contenido será actualizado dinámicamente con JavaScript -->
                        <p class="text-center">Cargando citas próximas...</p>
                    </ul>
                </div>
            </div>
        </div>




    <!-- Secciones de Acciones -->
    <div class="row text-center mt-4">
        <div class="col-md-3 mb-3">
            <a href="gestionarCitasPaciente.php" class="btn btn-primary w-100">Gestionar Citas</a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="resumenHistorial.php" class="btn btn-primary w-100">Vizualizar Historial</a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="misionesPaciente.php" class="btn btn-primary w-100">Misiones</a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="soporte.html" class="btn btn-primary w-100">Soporte</a>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="mt-auto text-center text-white bg-dark py-3">
    <div class="container">
        <p class="mb-0">&copy; 2024 MEDPLAY. Todos los derechos reservados.</p>
    </div>
</footer>

<script>
    // Cargar los puntos acumulados y las recompensas dinámicamente
    function cargarResumenPuntosYRecompensas() {
    fetch('obtenerResumenPuntos.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar los puntos acumulados
                document.getElementById('totalPuntos').textContent = `${data.totalPuntos} Puntos`;

                // Actualizar la lista de recompensas
                const recompensasContainer = document.getElementById('listaRecompensas');
                recompensasContainer.innerHTML = ''; // Limpiar recompensas actuales

                if (data.recompensas.length > 0) {
                    data.recompensas.forEach(recompensa => {
                        const li = document.createElement('li');
                        li.innerHTML = `
                            <strong>Recompensa:</strong> ${recompensa.descripcion}<br>
                            <strong>Puntos Requeridos:</strong> ${recompensa.puntosRequeridos}
                        `;
                        recompensasContainer.appendChild(li);
                        const hr = document.createElement('hr');
                        recompensasContainer.appendChild(hr);
                    });
                } else {
                    const noRecompensas = document.createElement('p');
                    noRecompensas.classList.add('text-center');
                    noRecompensas.textContent = 'No hay recompensas disponibles.';
                    recompensasContainer.appendChild(noRecompensas);
                }
            } else {
                console.error(data.message);
                document.getElementById('totalPuntos').textContent = 'Error al cargar puntos';
            }
        })
        .catch(error => {
            console.error('Error al cargar el resumen de puntos y recompensas:', error);
            document.getElementById('totalPuntos').textContent = 'Error al cargar';
        });
}

// Llamar a la función al cargar la página
document.addEventListener('DOMContentLoaded', cargarResumenPuntosYRecompensas);

function cargarCitasProximas() {
    fetch('obtenerCitasProximas.php')
        .then(response => response.json())
        .then(data => {
            const citasContainer = document.querySelector('#listaCitasProximas');
            citasContainer.innerHTML = ''; // Limpiar el contenido actual

            if (data.success && data.citas.length > 0) {
                data.citas.forEach(cita => {
                    const li = document.createElement('li');
                    li.innerHTML = `
                        <strong>Fecha:</strong> ${cita.fecha}<br>
                        <strong>Hora:</strong> ${cita.hora}<br>
                        <strong>Motivo:</strong> ${cita.motivo}<br>
                        <strong>Especialidad:</strong> ${cita.especialidad}<br>
                        <strong>Hospital:</strong> ${cita.hospitalAsociado}
                    `;
                    citasContainer.appendChild(li);
                    const hr = document.createElement('hr');
                    citasContainer.appendChild(hr);
                });
            } else {
                const noCitas = document.createElement('p');
                noCitas.classList.add('text-center');
                noCitas.textContent = 'No hay citas próximas.';
                citasContainer.appendChild(noCitas);
            }
        })
        .catch(error => {
            console.error('Error al cargar citas próximas:', error);
        });
}

// Llamar a la función al cargar la página
document.addEventListener('DOMContentLoaded', cargarCitasProximas);

function cargarNotificaciones() {
    fetch('obtenerNotificaciones.php')
        .then(response => response.json())
        .then(data => {
            const notificacionesContainer = document.querySelector('#listaNotificaciones');
            notificacionesContainer.innerHTML = ''; // Limpiar el contenido actual

            if (data.success && data.notificaciones.length > 0) {
                data.notificaciones.forEach(notificacion => {
                    const li = document.createElement('li');
                    li.innerHTML = `
                        <strong>Actividad:</strong> ${notificacion.actividad}<br>
                        <strong>Fecha:</strong> ${notificacion.fechaRegistro}
                    `;
                    notificacionesContainer.appendChild(li);
                    const hr = document.createElement('hr');
                    notificacionesContainer.appendChild(hr);
                });
            } else {
                const noNotificaciones = document.createElement('p');
                noNotificaciones.classList.add('text-center');
                noNotificaciones.textContent = 'No hay notificaciones disponibles.';
                notificacionesContainer.appendChild(noNotificaciones);
            }
        })
        .catch(error => {
            console.error('Error al cargar notificaciones:', error);
        });
}

// Llamar a la función al cargar la página
document.addEventListener('DOMContentLoaded', cargarNotificaciones);

    // Inicialización de Bootstrap Dropdown
    document.addEventListener('DOMContentLoaded', function () {
        const dropdownElement = document.getElementById('dropdownNotificaciones');
        if (dropdownElement) {
            new bootstrap.Dropdown(dropdownElement);
        }
    });
</script>

</body>
</html>

<?php
$conn->close();
?>