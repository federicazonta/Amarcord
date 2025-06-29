<?php
// Connessione al database
$host = "localhost";
$user = "root";
$pass = "root";  // <-- aggiungi la password corretta
$dbname = "alzheimer-app";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
  die(json_encode(["status" => "error", "message" => "Connessione fallita"]));
}

// Leggi il contenuto JSON ricevuto
$data = json_decode(file_get_contents("php://input"), true);

// Estrai i dati
$paziente = $data['paziente'] ?? '';
$corretti = $data['corretti'] ?? 0;
$errori = $data['errori'] ?? 0;
$data_ora = date("Y-m-d H:i:s");

// Verifica dati obbligatori
if ($paziente === '') {
  echo json_encode(["status" => "error", "message" => "Paziente mancante"]);
  exit;
}

// Inserisci i dati nel database
$stmt = $conn->prepare("INSERT INTO risultati (paziente, corretti, errori, data_ora) VALUES (?, ?, ?, ?)");
$stmt->bind_param("siis", $paziente, $corretti, $errori, $data_ora);

if ($stmt->execute()) {
  echo json_encode(["status" => "ok"]);
} else {
  echo json_encode(["status" => "error", "message" => "Errore nella query"]);
}

$stmt->close();
$conn->close();
?>
