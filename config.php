<?php
// config.php
$host = 'localhost';
$user = 'root';
$pass = '';
$base = 'usuarios';

$conn = new mysqli($host, $user, $pass, $base);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}
?>
