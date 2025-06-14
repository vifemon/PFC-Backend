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

$query = "SELECT * FROM usuarios";
$result = $con->query($query);
$rows = $result->num_rows; 
for ($j = 0 ; $j < $rows ; ++$j)
{
$row = $result->fetch_assoc();
}


$id= $row['id']+1;

$usuario = $_POST['usuario'];
$nombre = $_POST['nombre'];
$apellidos = $_POST['apellidos'];
$email = $_POST['email'];
$telefono = $_POST['telefono'];
$password = $_POST['password'];
$strongpass = password_hash($password, PASSWORD_DEFAULT);


$sql = "INSERT INTO usuarios VALUES (?,?,?,?,?,?,?)";
$stmt = $con->prepare($sql);
if ($stmt === false) {
    die("Error al preparar la consulta: " . $con->error);
}

$stmt->bind_param("issssss", $id, $usuario, $nombre, $apellidos, $email, $telefono, $strongpass );
$stmt->execute();
 echo json_encode(["status" => "success", "mensaje" => "Registro completado"]);

$con->close();
?>
