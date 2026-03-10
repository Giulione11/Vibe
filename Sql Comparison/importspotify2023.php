<?php
$mysqli = new mysqli("localhost", "root", "", "musica");
if ($mysqli->connect_errno) {
    die("Errore connessione: " . $mysqli->connect_error);
}

// Crea la tabella
$mysqli->query("DROP TABLE IF EXISTS spotify2023");

$create = "
CREATE TABLE spotify2023 (
    _id VARCHAR(50),
    track_name VARCHAR(255),
    artist_name VARCHAR(255),
    artist_count INT,
    released_year INT,
    released_month INT,
    released_day INT,
    in_spotify_playlists INT,
    in_spotify_charts INT,
    streams BIGINT,
    in_apple_playlists INT,
    in_apple_charts INT,
    in_deezer_playlists INT,
    in_deezer_charts INT,
    in_shazam_charts INT,
    bpm INT,
    `key` VARCHAR(10),
    `mode` VARCHAR(10),
    danceability INT,
    valence INT,
    energy INT,
    acousticness INT,
    instrumentalness INT,
    liveness INT,
    speechiness INT
)";
$mysqli->query($create);

// Legge il CSV
$file = fopen("Spotify2023.csv", "r");
$header = fgetcsv($file); // salta intestazione

$insert = $mysqli->prepare("
INSERT INTO spotify2023 VALUES (
?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
)");
if (!$insert) {
    die("Errore prepare: " . $mysqli->error);
}
while (($data = fgetcsv($file)) !== false) {
    // Pulizia: converte valori vuoti in null
    $data = array_map(function($v) {
        return $v === '' ? null : $v;
    }, $data);

    $insert->bind_param(
        "sssiiiiiiiiiiiiissiiiiiii",
        $data[0], $data[1], $data[2], $data[3], $data[4],
        $data[5], $data[6], $data[7], $data[8], $data[9],
        $data[10], $data[11], $data[12], $data[13], $data[14],
        $data[15], $data[16], $data[17], $data[18], $data[19],
        $data[20], $data[21], $data[22], $data[23], $data[24]
    );
    $insert->execute();
}
fclose($file);

echo "✅ Importazione completata!";
?>
