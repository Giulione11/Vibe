<?php
$mysqli = new mysqli("localhost", "root", "", "musica");
if ($mysqli->connect_errno) {
    die("Errore di connessione: " . $mysqli->connect_error);
}

$sql = "
    SELECT artist_name, COUNT(*) AS total_tracks
    FROM spotify2023
    GROUP BY artist_name
    ORDER BY total_tracks DESC
    LIMIT 10
";
$start = microtime(true);
$result = $mysqli->query($sql);
$end = microtime(true);
if ($result) {
    echo "<h3>Top 10 artisti per numero di brani:</h3><ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>{$row['artist_name']} - {$row['total_tracks']} brani</li>";
    }
    echo "</ul>";
} else {
    echo "Errore nella query: " . $mysqli->error;
}
echo "Tempo: " . ($end - $start) . "s";

$sql = "
    SELECT released_year, COUNT(*) AS count
    FROM spotify2023
    GROUP BY released_year
    ORDER BY released_year ASC
";
$start = microtime(true);
$result = $mysqli->query($sql);
$end = microtime(true);

if ($result) {
    echo "<h3>Distribuzione dei brani per anno:</h3><ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>{$row['released_year']}: {$row['count']} brani</li>";
    }
    echo "</ul>";
} else {
    echo "Errore nella query: " . $mysqli->error;
}
echo "Tempo: " . ($end - $start) . "s";
$sql = "
    SELECT 
        released_year,
        AVG(danceability) AS avg_danceability,
        AVG(valence) AS avg_valence,
        AVG(energy) AS avg_energy
    FROM spotify2023
    GROUP BY released_year
    ORDER BY released_year ASC
";

$result = $mysqli->query($sql);

if ($result) {
    echo "<h3>Media di Danceability, Valence ed Energy per anno:</h3><table border='1' cellpadding='5'>";
    echo "<tr><th>Anno</th><th>Danceability %</th><th>Valence %</th><th>Energy %</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
            <td>{$row['released_year']}</td>
            <td>" . round($row['avg_danceability'], 2) . "</td>
            <td>" . round($row['avg_valence'], 2) . "</td>
            <td>" . round($row['avg_energy'], 2) . "</td>
        </tr>";
    }
    echo "</table>";
} else {
    echo "Errore nella query: " . $mysqli->error;
}
$sql = "
SELECT
  CASE
    WHEN bpm >= 60 AND bpm < 90 THEN '60-89'
    WHEN bpm >= 90 AND bpm < 120 THEN '90-119'
    WHEN bpm >= 120 AND bpm < 150 THEN '120-149'
    WHEN bpm >= 150 AND bpm < 180 THEN '150-179'
    WHEN bpm >= 180 AND bpm < 210 THEN '180-209'
    ELSE 'Other'
  END AS bpm_range,
  COUNT(*) AS count
FROM spotify2023
GROUP BY bpm_range
ORDER BY bpm_range
";

$result = $mysqli->query($sql);

if ($result) {
    echo "<h3>Distribuzione BPM nella Top 100</h3><ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>{$row['bpm_range']}: {$row['count']} brani</li>";
    }
    echo "</ul>";
} else {
    echo "Errore nella query: " . $mysqli->error;
}
$sql = "
SELECT 
    t.title AS track_name,
    t.artists AS artist_name,
    COUNT(f.track_id) AS preferenze
FROM user_favorites f
JOIN tracks t ON f.track_id = t.id
GROUP BY f.track_id
ORDER BY preferenze DESC
LIMIT 10
";

$result = $mysqli->query($sql);

if ($result) {
    echo "<h3>🎧 Top 10 Brani più Preferiti dagli Utenti</h3>";
    echo "<table border='1' cellpadding='6' style='border-collapse: collapse;'>";
    echo "<tr><th>Brano</th><th>Artista</th><th>Preferenze</th></tr>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['track_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['artist_name']) . "</td>";
        echo "<td>" . $row['preferenze'] . "</td>";
        echo "</tr>";
    }

    echo "</table>";
} else {
    echo "Errore nella query: " . $mysqli->error;
}
// Query per Top 100 (spotify2023)
$sqlTop100 = "SELECT AVG(danceability) AS avg_danceability, AVG(energy) AS avg_energy, AVG(valence) AS avg_valence FROM spotify2023";
$resTop = $mysqli->query($sqlTop100);
$top = $resTop->fetch_assoc();

// Query per Archivio (tracks)
$sqlFull = "SELECT AVG(danceability) AS avg_danceability, AVG(energy) AS avg_energy, AVG(valence) AS avg_valence FROM tracks";
$resFull = $mysqli->query($sqlFull);
$full = $resFull->fetch_assoc();

// Stampa confronto


$sql = "SELECT AVG(metascore) AS avg_metascore FROM tracks";
$result = $mysqli->query($sql);

if ($result) {
    $row = $result->fetch_assoc();
    $media = round($row['avg_metascore'], 2);
    echo "<h3>📊 Media del Metascore: <strong>$media</strong></h3>";
} else {
    echo "Errore nella query: " . $mysqli->error;
}

?>
