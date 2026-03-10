<?php
$mysqli = new mysqli("localhost", "root", "", "musica");
if ($mysqli->connect_errno) {
    die("Errore connessione: " . $mysqli->connect_error);
}

// Crea tabella utenti
$mysqli->query("DROP TABLE IF EXISTS users");

$mysqli->query("
CREATE TABLE users (
    id VARCHAR(50) PRIMARY KEY,
    username VARCHAR(50),
    password TEXT,
    data_registrazione DATETIME,
    nome VARCHAR(50),
    cognome VARCHAR(50),
    email VARCHAR(100),
    data_nascita DATE,
    genere VARCHAR(50),
    bio TEXT,
    ruolo VARCHAR(50)
)");
$file = fopen("User.csv", "r");
$header = fgetcsv($file); // Salta intestazione

$insert = $mysqli->prepare("
INSERT INTO users VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

while (($row = fgetcsv($file)) !== false) {
    $id = $row[0];
    $username = $row[1];
    $password = $row[2];
    $data_reg = date('Y-m-d H:i:s', strtotime($row[3]));
    $nome = $row[4];
    $cognome = $row[5];
    $email = $row[6];
    $nascita = $row[7];
    $genere = $row[8];
    $bio = $row[9];
    $ruolo = $row[10];

    $insert->bind_param(
        "sssssssssss",
        $id, $username, $password, $data_reg,
        $nome, $cognome, $email, $nascita,
        $genere, $bio, $ruolo
    );
    $insert->execute();
}
fclose($file);

echo "✅ Utenti importati!";
$mysqli->query("DROP TABLE IF EXISTS user_favorites");
$mysqli->query("DROP TABLE IF EXISTS playlist_tracks");
$mysqli->query("DROP TABLE IF EXISTS playlists");

$mysqli->query("
CREATE TABLE user_favorites (
    user_id VARCHAR(50),
    track_id VARCHAR(50),
    PRIMARY KEY (user_id, track_id)
)");

$mysqli->query("
CREATE TABLE playlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50),
    nome VARCHAR(100),
    descrizione TEXT
)");

$mysqli->query("
CREATE TABLE playlist_tracks (
    playlist_id INT,
    track_id VARCHAR(50),
    PRIMARY KEY (playlist_id, track_id)
)");
$file = fopen("User.csv", "r");
$header = fgetcsv($file); // salta intestazione

while (($row = fgetcsv($file)) !== false) {
    $user_id = $row[0]; // _id Mongo
    $preferiti_json = $row[11];
    $playlist_json = $row[12];

    // Importa preferiti
    $preferiti = json_decode($preferiti_json, true);
    if (is_array($preferiti)) {
        foreach ($preferiti as $b) {
            $track_id = $b['id_brano'] ?? null;
            if ($track_id) {
                $stmt = $mysqli->prepare("INSERT IGNORE INTO user_favorites (user_id, track_id) VALUES (?, ?)");
                $stmt->bind_param("ss", $user_id, $track_id);
                $stmt->execute();
            }
        }
    }

    // Importa playlist
    $playlists = json_decode($playlist_json, true);
    if (is_array($playlists)) {
        foreach ($playlists as $playlist) {
            $nome = $playlist['nome_playlist'] ?? '';
            $descrizione = $playlist['descrizione'] ?? '';
            $stmt = $mysqli->prepare("INSERT INTO playlists (user_id, nome, descrizione) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $user_id, $nome, $descrizione);
            $stmt->execute();
            $playlist_id = $mysqli->insert_id;

            foreach ($playlist['brani'] ?? [] as $brano) {
                $track_id = $brano['id_brano'] ?? null;
                if ($track_id) {
                    $stmt2 = $mysqli->prepare("INSERT IGNORE INTO playlist_tracks (playlist_id, track_id) VALUES (?, ?)");
                    $stmt2->bind_param("is", $playlist_id, $track_id);
                    $stmt2->execute();
                }
            }
        }
    }
}
fclose($file);

echo "✅ Preferiti e playlist importati con successo!";
