<?php

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "coworking";


$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die(json_encode(["error" => "Error de conexión a la base de datos: " . $conn->connect_error]));
}


$usuario_id = $_POST['usuario_id'] ?? null;
$fecha = $_POST['fecha'] ?? null;
$reservas_json = $_POST['reservas'] ?? null;
$reservaTodoElDia_json = $_POST['reservaTodoElDia'] ?? null;


if (!$usuario_id || !$fecha) {
    http_response_code(400);
    echo json_encode(["error" => "Faltan datos necesarios."]);
    exit;
}

if ($reservaTodoElDia_json) {
    $reservaTodo = json_decode($reservaTodoElDia_json, true);

    if (
        !$reservaTodo ||
        !isset($reservaTodo['hora_inicio'], $reservaTodo['hora_fin'], $reservaTodo['cantidad_sillas'])
    ) {
        http_response_code(400);
        echo json_encode(["error" => "Formato de reservaTodoElDia inválido."]);
        exit;
    }

    $hora_inicio = $reservaTodo['hora_inicio'];
    $hora_fin = $reservaTodo['hora_fin'];
    $cantidad_sillas = $reservaTodo['cantidad_sillas'];

    $stmt = $conn->prepare("INSERT INTO reservas (usuario_id, fecha, hora_inicio, hora_fin, cantidad_sillas) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssi", $usuario_id, $fecha, $hora_inicio, $hora_fin, $cantidad_sillas);

    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(["error" => "Error al guardar la reserva: " . $stmt->error]);
        $stmt->close();
        $conn->close();
        exit;
    }

    $stmt->close();

    $conn->close();
    echo json_encode(["success" => true, "mensaje" => "Reserva de día completo registrada correctamente."]);
    exit;
}


if ($reservas_json) {
$reservas = json_decode($reservas_json, true);

if (!$reservas || !is_array($reservas)) {
    http_response_code(400);
    echo json_encode(["error" => "Formato de reservas inválido."]);
    exit;
}


$agrupadas = [];

foreach ($reservas as $hora_inicio => $cantidad_sillas) {
    if (!$cantidad_sillas) continue;
    $agrupadas[$cantidad_sillas][] = $hora_inicio;
}

foreach ($agrupadas as $cantidad_sillas => $horas) {
    sort($horas);

    $bloque_inicio = null;
    $bloque_fin    = null;

    foreach ($horas as $hora) {
        if ($bloque_inicio === null) {
            $bloque_inicio = $hora;
            $bloque_fin    = $hora;
        } else {
            $hora_anterior = strtotime($bloque_fin);
            $hora_actual   = strtotime($hora);
            $diferencia    = ($hora_actual - $hora_anterior) / 3600;

            if ($diferencia === 1) {
                $bloque_fin = $hora;
            } else {
                $hora_fin_real = date("H:i", strtotime($bloque_fin . " +1 hour"));

                $stmt = $conn->prepare("INSERT INTO reservas (usuario_id, fecha, hora_inicio, hora_fin, cantidad_sillas) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("isssi", $usuario_id, $fecha, $bloque_inicio, $hora_fin_real, $cantidad_sillas);

                if (!$stmt->execute()) {
                    http_response_code(500);
                    echo json_encode(["error" => "Error al guardar la reserva: " . $stmt->error]);
                    $stmt->close();
                    $conn->close();
                    exit;
                }
                $stmt->close();

                
                $bloque_inicio = $hora;
                $bloque_fin    = $hora;
            }
        }
    }

 
    if ($bloque_inicio !== null) {
        $hora_fin_real = date("H:i", strtotime($bloque_fin . " +1 hour"));

        $stmt = $conn->prepare("INSERT INTO reservas (usuario_id, fecha, hora_inicio, hora_fin, cantidad_sillas) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $usuario_id, $fecha, $bloque_inicio, $hora_fin_real, $cantidad_sillas);

        if (!$stmt->execute()) {
            http_response_code(500);
            echo json_encode(["error" => "Error al guardar la reserva: " . $stmt->error]);
            $stmt->close();
            $conn->close();
            exit;
        }
        $stmt->close();
    }
}

    $conn->close();
    echo json_encode(["success" => true, "mensaje" => "Reservas registradas correctamente."]);
    exit;
}


http_response_code(400);
echo json_encode(["error" => "No se recibió ninguna reserva válida."]);
?>
