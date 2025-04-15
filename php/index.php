<?php
$manager = new MongoDB\Driver\Manager("mongodb://mongo:27017");

echo "<h1>Connessione a MongoDB riuscita!</h1>";

$query = new MongoDB\Driver\Query([]);
$cursor = $manager->executeQuery('testdb.testcollection', $query);

echo "<h2>Dati dalla collezione:</h2>";
foreach ($cursor as $document) {
    echo "<pre>";
    var_dump($document);
    echo "</pre>";
}
?>
