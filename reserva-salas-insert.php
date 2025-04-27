<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "coworking";

$con = new mysqli($servername, $username, $password, $dbname);
if ($con->connect_error) {
    die(json_encode(["error" => "Conexión fallida: " . $con->connect_error]));
}

// Comprobar parámetros recibidos
if (!isset($_POST['usuario_id']) || !isset($_POST['fecha']) || !isset($_POST['hora_inicio']) || !isset($_POST['hora_fin']) || !isset($_POST['sala_id'])) {
    die(json_encode(["status" => "error", "mensaje" => "Faltan datos para la reserva."]));
}

$usuario_id = $_POST['usuario_id'];
$fecha = $_POST['fecha'];
$hora_inicio = $_POST['hora_inicio'];
$hora_fin = $_POST['hora_fin'];
$sala_id = $_POST['sala_id'];

// Validaciones básicas
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    die(json_encode(["status" => "error", "mensaje" => "Formato de fecha incorrecto."]));
}

if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora_inicio) || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora_fin)) {
    die(json_encode(["status" => "error", "mensaje" => "Formato de hora incorrecto."]));
}

// Comprobar si la sala está ya reservada en ese rango horario
$stmt = $con->prepare("
    SELECT COUNT(*) as total
    FROM reservas
    WHERE sala_id = ?
      AND fecha = ?
      AND (
            hora_inicio < ? AND hora_fin > ?
          )
");
$stmt->bind_param("isss", $sala_id, $fecha, $hora_fin, $hora_inicio);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['total'] > 0) {
    die(json_encode(["status" => "error", "mensaje" => "Sala ocupada en ese horario."]));
}

// Insertar la reserva
$stmt = $con->prepare("
    INSERT INTO reservas (usuario_id, fecha, hora_inicio, hora_fin, cantidad_sillas, sala_id)
    VALUES (?, ?, ?, ?, NULL, ?)
");
$stmt->bind_param("isssi", $usuario_id, $fecha, $hora_inicio, $hora_fin, $sala_id);
if ($stmt->execute()) {
    echo json_encode(["status" => "success", "mensaje" => "Reserva realizada correctamente."]);
} else {
    echo json_encode(["status" => "error", "mensaje" => "Error al insertar la reserva."]);
}

$con->close();
