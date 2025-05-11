<?php
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "coworking";


$con = new mysqli($servername, $username, $password, $dbname);
if ($con->connect_error) {
    die("ERROR DE CONEXIÓN: " . $con->connect_error);
}

$id = $_POST['id'] ?? null;

if (!$id){
    http_response_code(400);
    echo json_enconde(["error" => "Falta la id de la reserva"]);
    exit;
}

$sql = "DELETE FROM reservas where id=?";
$stmt = $con->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'mensaje' => 'Error al preparar la consulta']);
    $con->close();
    exit;
}

$stmt->bind_param("i", $id);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'mensaje' => 'Fallo al ejecutar']);
} elseif ($stmt->affected_rows === 0) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'mensaje' => 'Reserva no encontrada']);
} else {
    http_response_code(200);
    echo json_encode(['status' => 'success', 'mensaje' => 'Reserva eliminada']);
}

$con->close();
?>
