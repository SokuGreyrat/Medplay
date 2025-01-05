<?php
session_start();
if (!isset($_SESSION['idUsuario']) || $_SESSION['rol'] != 'profesional') {
    header("Location: ../inicio/inicioSesion.php");
    exit();
}

include '../db.php'; // Conexión a la base de datos

// Obtener lista de pacientes
$query_pacientes = "SELECT p.idPaciente, u.nombre 
                    FROM paciente p 
                    INNER JOIN usuario u ON p.idUsuario = u.idUsuario 
                    WHERE u.rol = 'paciente'";
$result_pacientes = $conn->query($query_pacientes);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Misiones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg bg-primary fixed-top">
    <div class="container">
        <a class="navbar-brand text-white" href="proDashboard.php">MedPlay - Asignar Misiones</a>
        <a href="proDashboard.php" class="btn btn-danger ms-2">Regresar</a>
    </div>
</nav>

<div class="container" style="margin-top: 100px;">
    <h2 class="text-center mb-4">Asignar Misiones y Consultar Información</h2>

    <!-- Campo de búsqueda dinámica -->
    <div class="mb-3">
        <label for="buscarPaciente" class="form-label">Buscar Paciente:</label>
        <input type="text" id="buscarPaciente" class="form-control" placeholder="Escriba el nombre del paciente">
    </div>

    <!-- Selección de paciente -->
    <form method="POST">
        <div class="mb-3">
            <label for="idPaciente" class="form-label fw-bold">Paciente:</label>
            <select id="idPaciente" name="idPaciente" class="form-select" required>
                <option value="" disabled selected>Seleccionar paciente...</option>
                <?php
                if ($result_pacientes->num_rows > 0) {
                    while ($paciente = $result_pacientes->fetch_assoc()) {
                        echo "<option value='{$paciente['idPaciente']}'>{$paciente['nombre']}</option>";
                    }
                }
                ?>
            </select>
        </div>
    </form>

    <!-- Tabla para mostrar tratamientos -->
    <div id="tablaTratamientos" style="display:none;">
        <h3 class="mt-4">Tratamientos Asignados al Paciente</h3>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Descripción</th>
                    <th>Estatus</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="contenidoTratamientos">
                <!-- Aquí se insertarán las filas dinámicamente -->
            </tbody>
        </table>
    </div>

    <!-- Tabla para mostrar misiones -->
    <div id="tablaMisiones" style="display:none;">
        <h3 class="mt-4">Misiones Asignadas al Paciente</h3>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Descripción</th>
                    <th>Estatus</th>
                </tr>
            </thead>
            <tbody id="contenidoMisiones">
                <!-- Aquí se insertarán las filas dinámicamente -->
            </tbody>
        </table>
    </div>

    <!-- Apartado para asignar nuevas misiones -->
    <div id="seccionNuevaMision" style="display:none;">
        <h3 class="mt-4">Asignar Nueva Misión</h3>
        <form id="formNuevaMision">
            <div class="mb-3">
                <label for="nombreMision" class="form-label">Nombre de la Misión:</label>
                <input type="text" id="nombreMision" class="form-control" placeholder="Nombre de la misión" required>
            </div>
            <div class="mb-3">
                <label for="descripcionMision" class="form-label">Descripción de la Misión:</label>
                <textarea id="descripcionMision" class="form-control" placeholder="Descripción de la misión" required></textarea>
            </div>
            <div class="mb-3">
                <label for="puntosMision" class="form-label">Puntos de Recompensa:</label>
                <input type="number" id="puntosMision" class="form-control" placeholder="Puntos de recompensa" required>
            </div>
            <button type="submit" class="btn btn-success">Asignar Misión</button>
        </form>
    </div>
</div>

<footer class="bg-dark text-white text-center py-3">
    <div class="container">
        <p class="mb-0">&copy; 2024 MEDPLAY. Todos los derechos reservados.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Búsqueda dinámica de pacientes
    document.getElementById('buscarPaciente').addEventListener('input', function () {
        const filtro = this.value.toLowerCase();
        const opciones = document.querySelectorAll('#idPaciente option');

        opciones.forEach(opcion => {
            if (opcion.textContent.toLowerCase().includes(filtro)) {
                opcion.style.display = '';
            } else {
                opcion.style.display = 'none';
            }
        });
    });

    // Cargar información asignada al paciente
    document.getElementById('idPaciente').addEventListener('change', function () {
        const idPaciente = this.value;

        if (idPaciente) {
            fetch(`obtenerAsignaciones.php?idPaciente=${idPaciente}`)
                .then(response => response.json())
                .then(data => {
                    const tablaTratamientos = document.getElementById('tablaTratamientos');
                    const contenidoTratamientos = document.getElementById('contenidoTratamientos');
                    const tablaMisiones = document.getElementById('tablaMisiones');
                    const contenidoMisiones = document.getElementById('contenidoMisiones');
                    const seccionNuevaMision = document.getElementById('seccionNuevaMision');

                    contenidoTratamientos.innerHTML = '';
                    contenidoMisiones.innerHTML = '';

                    const tratamientos = data.filter(asignacion => asignacion.tipo === 'Tratamiento');
                    const misiones = data.filter(asignacion => asignacion.tipo === 'Misión');

                    if (tratamientos.length > 0) {
                        tratamientos.forEach(tratamiento => {
                            const estatus = tratamiento.asignada ? 'Asignada' : 'No asignada';
                            const boton = tratamiento.asignada
                                ? '<button class="btn btn-secondary btn-sm" disabled>Asignar</button>'
                                : `<button class="btn btn-primary btn-sm btn-asignar" data-id-paciente="${idPaciente}" data-tipo="${tratamiento.tipo}" data-descripcion="${tratamiento.descripcion}">Asignar</button>`;

                            const fila = document.createElement('tr');
                            fila.innerHTML = `
                                <td>${tratamiento.descripcion}</td>
                                <td>${estatus}</td>
                                <td>${boton}</td>
                            `;
                            contenidoTratamientos.appendChild(fila);
                        });
                        tablaTratamientos.style.display = 'block';
                    } else {
                        tablaTratamientos.style.display = 'none';
                    }

                    if (misiones.length > 0) {
                        misiones.forEach(mision => {
                            const fila = document.createElement('tr');
                            fila.innerHTML = `
                                <td>${mision.descripcion}</td>
                                <td>Asignada</td>
                            `;
                            contenidoMisiones.appendChild(fila);
                        });
                        tablaMisiones.style.display = 'block';
                    } else {
                        tablaMisiones.style.display = 'none';
                    }

                    seccionNuevaMision.style.display = 'block';
                })
                .catch(error => console.error('Error al obtener asignaciones:', error));
        }
    });

    // Asignar misión al paciente
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('btn-asignar')) {
            const idPaciente = e.target.getAttribute('data-id-paciente');
            const tipo = e.target.getAttribute('data-tipo');
            const descripcion = e.target.getAttribute('data-descripcion');

            fetch('asignarTratamientoMision.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ idPaciente, tipo, descripcion })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Misión asignada exitosamente.');
                    document.getElementById('idPaciente').dispatchEvent(new Event('change'));
                } else {
                    alert('Error al asignar misión: ' + data.message);
                }
            })
            .catch(error => console.error('Error al asignar misión:', error));
        }
    });

    document.getElementById('formNuevaMision').addEventListener('submit', function (e) {
        e.preventDefault();

        const idPaciente = document.getElementById('idPaciente').value;
        const nombre = document.getElementById('nombreMision').value;
        const descripcion = document.getElementById('descripcionMision').value;
        const puntos = document.getElementById('puntosMision').value;

        if (!idPaciente || !nombre || !descripcion || !puntos) {
            alert('Por favor, completa todos los campos.');
            return;
        }

        fetch('asignarNuevaMision.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ idPaciente, nombre, descripcion, puntos })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Misión asignada exitosamente.');
                document.getElementById('formNuevaMision').reset();
                document.getElementById('idPaciente').dispatchEvent(new Event('change'));
            } else {
                alert('Error al asignar misión: ' + data.message);
            }
        })
        .catch(error => console.error('Error al asignar misión:', error));
    });
</script>
</body>
</html>
