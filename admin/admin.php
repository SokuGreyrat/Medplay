<?php

include '../db.php';

// Actualizar el nivel de usuario o contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idUsuario = $_POST['idUsuario'] ?? null;
    $nuevoRol = $_POST['rol'] ?? null;
    $nuevaContrasena = $_POST['password'] ?? null;

    if ($idUsuario) {
        try {
            $conn->begin_transaction();

            // Actualizar el rol
            if ($nuevoRol) {
                $queryActualizarRol = "UPDATE usuario SET rol = ? WHERE idUsuario = ?";
                $stmtActualizarRol = $conn->prepare($queryActualizarRol);
                $stmtActualizarRol->bind_param("si", $nuevoRol, $idUsuario);
                if (!$stmtActualizarRol->execute()) {
                    throw new Exception("Error al actualizar el rol: " . $stmtActualizarRol->error);
                }
            }

            // Actualizar la contraseña
            if ($nuevaContrasena) {
                $hashContrasena = password_hash($nuevaContrasena, PASSWORD_DEFAULT);
                $queryActualizarContrasena = "UPDATE usuario SET contraseña = ? WHERE idUsuario = ?";
                $stmtActualizarContrasena = $conn->prepare($queryActualizarContrasena);
                $stmtActualizarContrasena->bind_param("si", $hashContrasena, $idUsuario);
                if (!$stmtActualizarContrasena->execute()) {
                    throw new Exception("Error al actualizar la contraseña: " . $stmtActualizarContrasena->error);
                }
            }

            $conn->commit();
            $mensaje = "<div class='alert alert-success text-center'>Cambios realizados con éxito.</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $mensaje = "<div class='alert alert-danger text-center'>Error: " . $e->getMessage() . "</div>";
        }
    } else {
        $mensaje = "<div class='alert alert-danger text-center'>Todos los campos son obligatorios.</div>";
    }
}

// Obtener la lista de usuarios
$filtro = $_GET['filtro'] ?? '';
$queryUsuarios = "SELECT idUsuario, nombre, correoElectronico, rol FROM usuario WHERE nombre LIKE ? OR correoElectronico LIKE ? ORDER BY rol ASC, nombre ASC";
$stmtUsuarios = $conn->prepare($queryUsuarios);
$filtroConPorcentaje = "%" . $filtro . "%";
$stmtUsuarios->bind_param("ss", $filtroConPorcentaje, $filtroConPorcentaje);
$stmtUsuarios->execute();
$resultUsuarios = $stmtUsuarios->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Niveles de Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top bg-success">
    <div class="container">
        <a class="navbar-brand text-white" href="#">Admin - Gestionar Niveles de Usuario</a>
        <a href="adminDashboard.php" class="btn btn-danger">Regresar</a>
    </div>
</nav>

<div class="container mt-5 pt-4">
    <div class="card shadow p-4">
        <h2 class="text-center mb-4">Gestión de Niveles de Usuario</h2>

        <!-- Mostrar mensajes -->
        <?php if (isset($mensaje)) echo $mensaje; ?>

        <!-- Filtro de usuarios -->
        <form method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-10">
                    <input type="text" name="filtro" class="form-control" placeholder="Buscar por nombre o correo" value="<?php echo htmlspecialchars($filtro); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Buscar</button>
                </div>
            </div>
        </form>

        <!-- Tabla de usuarios -->
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-success">
                    <tr>
                        <th>ID Usuario</th>
                        <th>Nombre</th>
                        <th>Correo Electrónico</th>
                        <th>Rol Actual</th>
                        <th>Actualizar Rol</th>
                        <th>Modificar Contraseña</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resultUsuarios->num_rows > 0): ?>
                        <?php while ($usuario = $resultUsuarios->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $usuario['idUsuario']; ?></td>
                                <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['correoElectronico']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['rol']); ?></td>
                                <td>
                                    <form method="POST" action="">
                                        <input type="hidden" name="idUsuario" value="<?php echo $usuario['idUsuario']; ?>">
                                        <select name="rol" class="form-select" required>
                                            <option value="admin" <?php if ($usuario['rol'] == 'admin') echo 'selected'; ?>>Administrador</option>
                                            <option value="paciente" <?php if ($usuario['rol'] == 'paciente') echo 'selected'; ?>>Paciente</option>
                                            <option value="profesional" <?php if ($usuario['rol'] == 'profesional') echo 'selected'; ?>>Profesional de Salud</option>
                                            <option value="pendiente" <?php if ($usuario['rol'] == 'pendiente') echo 'selected'; ?>>Pendiente</option>
                                        </select>
                                        <button type="submit" class="btn btn-primary btn-sm mt-2">Actualizar Rol</button>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" action="">
                                        <input type="hidden" name="idUsuario" value="<?php echo $usuario['idUsuario']; ?>">
                                        <input type="password" name="password" class="form-control" placeholder="Nueva Contraseña" required>
                                        <button type="submit" class="btn btn-warning btn-sm mt-2">Actualizar Contraseña</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No se encontraron usuarios.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
