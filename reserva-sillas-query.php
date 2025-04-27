<?php
session_start();
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


if (!isset($_POST['fecha'])) {
    die(json_encode(["error" => "Falta el parámetro 'fecha'"]));
}

$fecha = $_POST['fecha'];


if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    die(json_encode(["error" => "Formato de fecha no válido. Usa AAAA-MM-DD."]));
}

$bloques = [];
$hora_inicio = new DateTime("08:00");
$hora_fin_total = new DateTime("20:00");
$intervalo = new DateInterval("PT60M"); 

while ($hora_inicio < $hora_fin_total) {
    $hora_fin = clone $hora_inicio;
    $hora_fin->add($intervalo);

   
    $stmt = $con->prepare("
        SELECT COALESCE(SUM(cantidad_sillas), 0) as total_reservadas
        FROM reservas
        WHERE sala_id IS NULL
          AND fecha = ?
          AND (
                hora_inicio < ? AND hora_fin > ?
              )
    ");

    $h_inicio_str = $hora_inicio->format("H:i");
    $h_fin_str = $hora_fin->format("H:i");

    $stmt->bind_param("sss", $fecha, $h_fin_str, $h_inicio_str);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $sillas_ocupadas = $row['total_reservadas'];

    
    $sillas_disponibles = max(0, 18 - $sillas_ocupadas);

    $bloques[] = [
        "hora_inicio" => $h_inicio_str,
        "hora_fin" => $h_fin_str,
        "sillas_disponibles" => $sillas_disponibles
    ];

    $hora_inicio->add($intervalo);
}

echo json_encode($bloques);
$con->close();
