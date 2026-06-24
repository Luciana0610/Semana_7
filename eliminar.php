<?php
session_start();
require 'conexion.php';

$id    = (int)($_GET['id'] ?? 0);
$token = $_GET['token'] ?? '';

// Protección CSRF: el token debe coincidir con el generado para este enlace
if (!$id || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    header('Location: index.php?msg=error_csrf');
    exit;
}

$conn->query("DELETE FROM citas WHERE id = $id");
header('Location: index.php?msg=borrada');
exit;
