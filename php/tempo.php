<?php

try {
    
    $start = microtime(true);
$manager = new MongoDB\Driver\Manager("mongodb://mongo:27017");
$command = new MongoDB\Driver\Command(['ping' => 1]);
$manager->executeCommand('admin', $command);
$end = microtime(true);
echo "Tempo connessione+ping: " . ($end - $start) . "s\n";
    
} catch (MongoDB\Driver\Exception\Exception $e) {
    echo "Errore di connessione: " . $e->getMessage() . "\n";
}
?>