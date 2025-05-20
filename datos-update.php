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

$id= $_POST['id'];
$usuario = $_POST['usuario'];
$nombre = $_POST['nombre'];
$apellidos = $_POST['apellidos'];
$email = $_POST['email'];
$telefono = $_POST['telefono'];


$sql = "UPDATE usuarios SET usuario = ?, nombre= ?, apellidos =?, email=?, telefono=? where id =? ";
$stmt = $con->prepare($sql);
if ($stmt === false) {
    die("Error al preparar la consulta: " . $con->error);
}

$stmt->bind_param("sssssi", $usuario, $nombre, $apellidos, $email, $telefono, $id );
$stmt->execute();
 echo json_encode(["status" => "success", "mensaje" => "Datos actualizados"]);

$con->close();
?>
