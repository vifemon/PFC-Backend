<?php
// Configuración conexión
$host = 'localhost';
$user = 'tu_usuario';
$pass = 'tu_password';
$db   = 'tu_base_datos';

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die(json_encode(["error" => "Error de conexión a la base de datos."]));
}

// Recogida de datos POST
$usuario_id    = $_POST['usuario_id'] ?? null;
$fecha         = $_POST['fecha'] ?? null;
$reservas_json = $_POST['reservas'] ?? null;

if (!$usuario_id || !$fecha || !$reservas_json) {
    http_response_code(400);
    echo json_encode(["error" => "Faltan datos necesarios."]);
    exit;
}

// Decodificar reservas
$reservas = json_decode($reservas_json, true);

if (!$reservas || !is_array($reservas)) {
    http_response_code(400);
    echo json_encode(["error" => "Formato de reservas inválido."]);
    exit;
}

// Agrupar por cantidad de sillas
$agrupadas = [];

foreach ($reservas as $hora_inicio => $cantidad_sillas) {
    if (!$cantidad_sillas) continue; // ignorar vacíos o 0
    $agrupadas[$cantidad_sillas][] = $hora_inicio;
}

// Insertar reservas agrupadas por bloques consecutivos
foreach ($agrupadas as $cantidad_sillas => $horas) {
    // Ordenar las horas ascendentemente
    sort($horas);

    $bloque_inicio = null;
    $bloque_fin    = null;

    foreach ($horas as $hora) {
        if ($bloque_inicio === null) {
            // Primer valor del bloque
            $bloque_inicio = $hora;
            $bloque_fin    = $hora;
        } else {
            // Comprobar si es consecutiva (+1 hora)
            $hora_anterior = strtotime($bloque_fin);
            $hora_actual   = strtotime($hora);
            $diferencia    = ($hora_actual - $hora_anterior) / 3600;

            if ($diferencia === 1) {
                // Es consecutiva → ampliar bloque
                $bloque_fin = $hora;
            } else {
                // No es consecutiva → guardar bloque anterior y empezar nuevo
                $hora_fin_real = date("H:i", strtotime($bloque_fin . " +1 hour"));

                $stmt = mysqli_prepare($conn, "INSERT INTO Reservas (usuario_id, fecha, hora_inicio, hora_fin, cantidad_sillas)
                                               VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "isssi", $usuario_id, $fecha, $bloque_inicio, $hora_fin_real, $cantidad_sillas);

                if (!mysqli_stmt_execute($stmt)) {
                    http_response_code(500);
                    echo json_encode(["error" => "Error al guardar la reserva: " . mysqli_stmt_error($stmt)]);
                    mysqli_stmt_close($stmt);
                    mysqli_close($conn);
                    exit;
                }
                mysqli_stmt_close($stmt);

                // Nuevo bloque
                $bloque_inicio = $hora;
                $bloque_fin    = $hora;
            }
        }
    }

    // Insertar último bloque si queda
    if ($bloque_inicio !== null) {
        $hora_fin_real = date("H:i", strtotime($bloque_fin . " +1 hour"));

        $stmt = mysqli_prepare($conn, "INSERT INTO Reservas (usuario_id, fecha, hora_inicio, hora_fin, cantidad_sillas)
                                       VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isssi", $usuario_id, $fecha, $bloque_inicio, $hora_fin_real, $cantidad_sillas);

        if (!mysqli_stmt_execute($stmt)) {
            http_response_code(500);
            echo json_encode(["error" => "Error al guardar la reserva: " . mysqli_stmt_error($stmt)]);
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            exit;
        }
        mysqli_stmt_close($stmt);
    }
}

mysqli_close($conn);

// Respuesta final
echo json_encode(["success" => true, "mensaje" => "Reservas registradas correctamente."]);

?>
