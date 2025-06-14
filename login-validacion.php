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
    die("ERROR DE CONEXIÓN: " . $con->connect_error);
}


if (!isset($_POST['usuario']) || !isset($_POST['password'])){
    die (json_encode(["mensaje"=>"Error en la consulta. Campos vacios"]));

}
$usuario = $_POST['usuario'];
$password = $_POST['password'];


$sql = "SELECT * FROM usuarios WHERE usuario=?";
$stmt = $con->prepare($sql);
if ($stmt === false) {
    die("Error al preparar la consulta: " . $con->error);
}

$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    if (password_verify($password, $row['password']))  {
        echo json_encode(["status" => "success", "mensaje" => "Login exitoso", "user_id" => $row['id']]);
    } else {
        echo json_encode(["status" => "error", "mensaje" => "Contraseña incorrecta"]);
    }
} else {
    echo json_encode(["status" => "error", "mensaje" => "Usuario no encontrado"]);
}

$con->close();
?>
