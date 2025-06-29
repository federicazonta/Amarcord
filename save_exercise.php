<?php
// Connessione a MySQL (modifica con le tue credenziali MAMP)
$host = 'localhost';
$db = 'alzheimer-app';
$user = 'root';
$pass = 'root';  // default per MAMP

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Recupera i dati JSON dal POST
$data = json_decode(file_get_contents("php://input"), true);

$paziente = $conn->real_escape_string($data['paziente']);
$sequenza = $conn->real_escape_string(implode(",", $data['esercizio']));
$ripetizioni = (int) $data['ripetizioni'];
$giorno = $conn->real_escape_string($data['giorno']);
$orario = $conn->real_escape_string($data['orario']);

// Inserisci nel database
$sql = "INSERT INTO esercizi (paziente, sequenza, ripetizioni, giorno, orario)
        VALUES ('$paziente', '$sequenza', $ripetizioni, '$giorno', '$orario')";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["status" => "ok", "message" => "Esercizio salvato."]);
} else {
    echo json_encode(["status" => "error", "message" => $conn->error]);
}

$conn->close();
?>
