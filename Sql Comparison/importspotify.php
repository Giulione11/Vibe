<?php
$mysqli = new mysqli("localhost", "root", "", "musica");
if ($mysqli->connect_errno) {
    die("Errore connessione: " . $mysqli->connect_error);
}

$mysqli->query("DROP TABLE IF EXISTS tracks");

$createTable = "
CREATE TABLE tracks (
    _id VARCHAR(24),
    id INT,
    title VARCHAR(255),
    artists VARCHAR(255),
    release_date VARCHAR(50),
    summary TEXT,
    metascore INT,
    user_score VARCHAR(10),
    track_url TEXT,
    artist_url TEXT,
    track_img TEXT,
    track_album TEXT,
    track_album_url TEXT,
    explicit TINYINT,
    track_number INT,
    disc_number INT,
    duration_ms INT,
    tempo FLOAT,
    key_signature INT,
    mode TINYINT,
    time_signature INT,
    valence FLOAT,
    acousticness FLOAT,
    instrumentalness FLOAT,
    liveness FLOAT,
    speechiness FLOAT,
    loudness FLOAT,
    energy FLOAT,
    danceability FLOAT
)";
$mysqli->query($createTable);

// Apri il file CSV
$file = fopen("Spotify.csv", "r");
$header = fgetcsv($file); // leggi intestazione

$insert = $mysqli->prepare("
INSERT INTO tracks VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
if (!$insert) {
    die("Errore prepare: " . $mysqli->error);
}
while (($data = fgetcsv($file)) !== false) {
    // Converti eventuali valori vuoti in NULL o 0
    $data = array_map(function($val) {
        return $val === '' ? null : $val;
    }, $data);

    $insert->bind_param(
        "sissssiisssssiiiidiiidddddddd",
        $data[0],  // _id
        $data[1],  // id
        $data[2],  // title
        $data[3],  // artists
        $data[4],  // release_date
        $data[5],  // summary
        $data[6],  // metascore
        $data[7],  // user_score
        $data[8],  // track_url
        $data[9],  // artist_url
        $data[10], // track_img
        $data[11], // track_album
        $data[12], // track_album_url
        $data[13], // explicit
        $data[14], // track_number
        $data[15], // disc_number
        $data[16], // duration_ms
        $data[17], // tempo
        $data[18], // key_signature
        $data[19], // mode
        $data[20], // time_signature
        $data[21], // valence
        $data[22], // acousticness
        $data[23], // instrumentalness
        $data[24], // liveness
        $data[25], // speechiness
        $data[26], // loudness
        $data[27], // energy
        $data[28]  // danceability
    );
    $insert->execute();
}
fclose($file);

echo "✅ Dati importati con successo!";
?>
