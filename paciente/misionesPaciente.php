<?php
session_start();
if (!isset($_SESSION['idUsuario']) || $_SESSION['rol'] != 'paciente') {
    header("Location: ../inicio/inicioSesion.php");
    exit();
}

include '../db.php'; // Conexión a la base de datos

$idUsuario = $_SESSION['idUsuario'];

// Consultar misiones asignadas al paciente
$queryMisiones = "
    SELECT m.idMision, 
           m.nombre AS mision, 
           m.descripcion, 
           um.estadoMision, 
           um.fechaAsignacion, 
           um.fechaCompletada, 
           m.puntosRecompensa
    FROM usuarios_misiones um
    INNER JOIN mision m ON um.idMision = m.idMision
    WHERE um.idUsuario = ?
    ORDER BY um.fechaAsignacion DESC";
$stmtMisiones = $conn->prepare($queryMisiones);
$stmtMisiones->bind_param("i", $idUsuario);
$stmtMisiones->execute();
$resultMisiones = $stmtMisiones->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Misiones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="#">MedPlay - Mis Misiones</a>
        <a href="pacienteDashboard.php" class="btn btn-danger ms-2">Regresar</a>
    </div>
</nav>

<!-- Contenido principal -->
<div class="container mt-5">
    <h2 class="text-center mb-4">Mis Misiones</h2>

    <?php if ($resultMisiones->num_rows > 0): ?>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Nombre de la Misión</th>
                    <th>Descripción</th>
                    <th>Estado</th>
                    <th>Fecha Asignación</th>
                    <th>Fecha Completada</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($mision = $resultMisiones->fetch_assoc()): ?>
                    <tr id="mision-<?php echo $mision['idMision']; ?>">
                        <td><?php echo htmlspecialchars($mision['mision']); ?></td>
                        <td><?php echo htmlspecialchars($mision['descripcion']); ?></td>
                        <td class="estado"><?php echo htmlspecialchars($mision['estadoMision']); ?></td>
                        <td><?php echo htmlspecialchars($mision['fechaAsignacion']); ?></td>
                        <td class="fechaCompletada">
                            <?php echo $mision['fechaCompletada'] ? htmlspecialchars($mision['fechaCompletada']) : 'No completada'; ?>
                        </td>
                        <td>
                            <?php if ($mision['estadoMision'] !== 'Completada'): ?>
                                <button 
                                    class="btn btn-success btn-sm completarMision" 
                                    data-id-mision="<?php echo $mision['idMision']; ?>" 
                                    data-puntos="<?php echo $mision['puntosRecompensa']; ?>">
                                    Marcar como Completada
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm" disabled>Completada</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-center">No tienes misiones asignadas.</p>
    <?php endif; ?>
</div>

<!-- Footer -->
<footer class="mt-auto text-center text-white bg-dark py-3">
    <div class="container">
        <p class="mb-0">&copy; 2024 MEDPLAY. Todos los derechos reservados.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('completarMision')) {
            const idMision = e.target.getAttribute('data-id-mision');
            const puntos = e.target.getAttribute('data-puntos');
            const row = document.getElementById(`mision-${idMision}`);

            if (confirm('¿Estás seguro de marcar esta misión como completada?')) {
                fetch('marcarMisionCompletada.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ idMision, puntos })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Actualizar estado en tiempo real
                        row.querySelector('.estado').textContent = 'Completada';
                        row.querySelector('.fechaCompletada').textContent = data.fechaCompletada;

                        // Desactivar el botón
                        e.target.textContent = 'Completada';
                        e.target.classList.remove('btn-success');
                        e.target.classList.add('btn-secondary');
                        e.target.setAttribute('disabled', true);
                    } else {
                        alert('Error al completar la misión: ' + data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }
    });
</script>
</body>
</html>

<?php
$conn->close();
?>
