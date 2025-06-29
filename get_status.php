<?php
$host = "localhost";
$user = "root";
$pass = "root";
$dbname = "alzheimer-app";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
  die(json_encode(["error" => "connessione fallita"]));
}

// Data di oggi
$oggi = date("Y-m-d");

// Ottieni ultimo risultato per ogni paziente
$sql = "
  SELECT paziente, MAX(data_ora) as ultima
  FROM risultati
  GROUP BY paziente
";

$result = $conn->query($sql);
$stati = [];

while ($row = $result->fetch_assoc()) {
  $dataUltima = substr($row['ultima'], 0, 10); // solo data
  $stati[$row['paziente']] = ($dataUltima === $oggi) ? 'ok' : 'no';
}

echo json_encode($stati);
?>
