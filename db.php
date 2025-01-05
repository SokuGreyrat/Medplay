<?php
// Configuración de conexión a la base de datos
$host = "localhost";      // Servidor
$user = "root";           // Usuario (por defecto en XAMPP)
$password = "";           // Contraseña (por defecto vacía en XAMPP)
$dbname = "medplay";      // Nombre de la base de datos

// Crear la conexión
$conn = new mysqli($host, $user, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>
