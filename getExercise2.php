<?php

 $host = 'localhost';
 $dbname = 'alzheimer-app';
 $user = 'root';
 $password = 'root';  // default per MAMP
 
 $conn = new mysqli($host, $user, $password, $dbname);
	
 if ($conn->connect_error) {
     die(json_encode(["error" => "connessione fallita"]));
 }
 
 // Prendi l'ultimo esercizio
 $sql = "SELECT ripetizioni, sequenza FROM esercizi ORDER BY id DESC LIMIT 1";
 $result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
     $row = $result->fetch_assoc();
     $ripetizioni = intval($row['ripetizioni']);
     $sequenza_str = $row['sequenza'];
 
     // Spezza la sequenza tipo "8R,9L,8R" in un array
     $step_strings = explode(',', $sequenza_str);
     $sequenza_array = [];
 
     foreach ($step_strings as $step) {
         $numero = intval($step); // estrae il numero
         $mano = strtoupper(substr($step, -1)); // prende l'ultima lettera
 
         // Traduzione da L/R a s/d
         $m = ($mano === 'L') ? 's' : 'd';
         $sequenza_array[] = [$numero, $m];
     }
 
     echo json_encode([
         "r" => $ripetizioni,
         "seq" => $sequenza_array
     ]);
 } else {
     echo json_encode(["error" => "nessun esercizio trovato"]);
 }
 
 $conn->close();
 ?>
