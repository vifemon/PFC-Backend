<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "coworking";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["error" => "Conexión fallida: " . $conn->connect_error]));
}

$usuario_id = $_POST['usuario_id'] ?? null;

if (!$usuario_id) {
    http_response_code(400);
    echo json_encode(["error" => "Falta el ID de usuario"]);
    exit;
}

if ($usuario_id == 1){

    $stmt_admin = $conn->prepare("SELECT 
    reservas.id,
    CONCAT(usuarios.nombre, ' ', usuarios.apellidos) AS nombre_completo,
    DATE_FORMAT(reservas.fecha, '%d-%m-%Y') AS fecha_format,
    DATE_FORMAT(reservas.hora_inicio, '%H:%i') AS hora_inicio_format,
    DATE_FORMAT(reservas.hora_fin, '%H:%i') AS hora_fin_format,
    reservas.cantidad_sillas,
    reservas.sala_id
FROM reservas
INNER JOIN usuarios ON reservas.usuario_id = usuarios.id;");
$stmt_admin->execute();
$result_admin = $stmt_admin->get_result();
$reservas = [];
while ($row = $result_admin->fetch_assoc()) {
    $reservas[] = $row;
}
$stmt_admin->close();

$conn->close();


echo json_encode([
    "reservas" => $reservas
]);

} else {

$stmt_usuario = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt_usuario->bind_param("i", $usuario_id);
$stmt_usuario->execute();
$result_usuario = $stmt_usuario->get_result();

if ($result_usuario->num_rows === 0) {
    echo json_encode(["error" => "Usuario no encontrado"]);
    exit;
}

$usuario = $result_usuario->fetch_assoc();
$stmt_usuario->close();

$stmt_reservas = $conn->prepare("SELECT 
    id,
    DATE_FORMAT(fecha, '%d-%m-%Y') AS fecha_format,
    DATE_FORMAT(hora_inicio, '%H:%i') AS hora_inicio_format,
    DATE_FORMAT(hora_fin, '%H:%i') AS hora_fin_format,
    cantidad_sillas,
    sala_id
FROM reservas
WHERE usuario_id = ?");
$stmt_reservas->bind_param("i", $usuario_id);
$stmt_reservas->execute();
$result_reservas = $stmt_reservas->get_result();

$reservas = [];
while ($row = $result_reservas->fetch_assoc()) {
    $reservas[] = $row;
}
$stmt_reservas->close();

$conn->close();


echo json_encode([
    "usuario" => $usuario,
    "reservas" => $reservas
]);

}

?>
