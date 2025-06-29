<?php
$host = 'localhost';
$dbname = 'alzheimer-app';
$user = 'root';
$password = 'root';

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connessione fallita");
}

// Recupera gli ultimi 10 esercizi
$sql = "SELECT paziente, sequenza, ripetizioni, giorno, orario FROM esercizi ORDER BY id DESC LIMIT 10";
$result = $conn->query($sql);

echo "<!DOCTYPE html>
<html lang='it'>
<head>
  <meta charset='UTF-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
  <title>Esercizi salvati</title>
  <link rel='stylesheet' href='style.css'>
</head>
<body>
<div class='container'>
  <h1>Esercizi recenti</h1>";

if ($result->num_rows > 0) {
    echo "<ul class='exercise-list'>";
    while ($row = $result->fetch_assoc()) {
        echo "<li class='exercise-item'>
          <span><strong>Paziente:</strong> " . htmlspecialchars($row["paziente"]) . "</span>
          <span><strong>Sequenza:</strong> " . htmlspecialchars($row["sequenza"]) . "</span>
          <span><strong>Ripetizioni:</strong> " . $row["ripetizioni"] . "</span>
          <span><strong>Giorno:</strong> " . $row["giorno"] . "</span>
          <span><strong>Orario:</strong> " . $row["orario"] . "</span>
        </li>";
    }
    echo "</ul>";
} else {
    echo "<p>Nessun esercizio trovato.</p>";
}

echo "</div></body></html>";

$conn->close();
?>
