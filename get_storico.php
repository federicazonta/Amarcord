<?php
$host = "localhost";
$user = "root";
$pass = "root";
$dbname = "alzheimer-app";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
  die(json_encode(["error" => "connessione fallita"]));
}

$paziente = $_GET['paziente'] ?? '';
if ($paziente === '') {
  echo json_encode([]);
  exit;
}

$sql = "SELECT corretti, errori, data_ora FROM risultati WHERE paziente = ? ORDER BY data_ora DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $paziente);
$stmt->execute();

$result = $stmt->get_result();
$storico = [];

$oggi = date("Y-m-d");

while ($row = $result->fetch_assoc()) {
  $data = date("Y-m-d", strtotime($row['data_ora']));
  $giorno = ($data === $oggi) ? "oggi" : $data;

  $storico[] = [
    "corretti" => $row["corretti"],
    "errori" => $row["errori"],
    "data" => $giorno,
    "verde" => true // mostra verde per ogni esercizio registrato
  ];
}

echo json_encode($storico);
?>
