<?php
session_start();
$manager = new MongoDB\Driver\Manager("mongodb://mongo:27017");

$limit = 25;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$skip = ($page - 1) * $limit;

$filter = [];
$options = [
    'sort' => ['streams' => -1],
];

$filters = [];
$sort = [];
$query = new MongoDB\Driver\Query($filters, $options);
$cursor = $manager->executeQuery('admin.Spotify2023', $query);
if (!empty($_GET)) {

if (isset($_GET['danceability_min']) && $_GET['danceability_min'] !== '') {
    $filters['danceability_%']['$gte'] = (int)$_GET['danceability_min'];
}
if (isset($_GET['valence_min']) && $_GET['valence_min'] !== '') {
    $filters['valence_%']['$gte'] = (int)$_GET['valence_min'];
}
if (isset($_GET['bpm_min']) && $_GET['bpm_min'] !== '') {
    $filters['bpm']['$gte'] = (int)$_GET['bpm_min'];
}

if (!empty($_GET['sort_field'])) {
    $field = $_GET['sort_field'];
    $direction = ($_GET['sort_order'] ?? 'desc') === 'asc' ? 1 : -1;
    $sort[$field] = $direction;
}



if (!empty($sort)) {
    $options['sort'] = $sort;
}

$query = new MongoDB\Driver\Query($filters, $options);
$cursor = $manager->executeQuery('admin.Spotify2023', $query);
}

$messaggio = ""; // Variabile per il messaggio

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['track_id']) && !empty($_POST['track_name'])) {
    $utente = $_SESSION['utente_loggato'];
    $trackId = $_POST['track_id'];
    try {
        $manager = new MongoDB\Driver\Manager("mongodb://mongo:27017");

        // Controlla se il brano è già nei preferiti
        $query = new MongoDB\Driver\Query([
            'username' => $utente,
            'preferiti.brani.id_brano' => $trackId
        ]);
        $cursor = $manager->executeQuery('admin.User', $query);
        $esiste = iterator_count($cursor) > 0;

        if ($esiste) {
            $messaggio = "The track is already in your favorites!";
            // Restituisci una risposta JSON
            echo json_encode(['success' => false, 'message' => $messaggio]);
            exit();
        }

        // Creazione dell'oggetto per l'update
        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->update(
            ['username' => $utente],
            [
                '$addToSet' => [
                    'preferiti.brani' => ['id_brano' => $trackId]
                ]
            ],
            ['multi' => false, 'upsert' => true]
        );

        $result = $manager->executeBulkWrite('admin.User', $bulk);

        // Imposta il messaggio di successo
        $messaggio = "Track " .$_POST['track_name']. " add to favorites!";

        // Restituisci una risposta JSON
        echo json_encode(['success' => true, 'message' => $messaggio]);
        exit(); // Uscita per evitare altri output

    } catch (Exception $e) {
        // Gestisci errore
        $messaggio = "Errore nell'aggiunta del brano!";
        echo json_encode(['success' => false, 'message' => $messaggio]);
        exit(); // Uscita per evitare altri output
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_playlists') {
    
    $manager = new MongoDB\Driver\Manager("mongodb://mongo:27017");

    $username = $_SESSION['utente_loggato'];
    
    $filter = ['username' => $username];
      // Recupera l'id del brano passato via POST
    $idBranoCliccato = $_POST['song_id'] ?? null;
    $query = new MongoDB\Driver\Query($filter);
    $userCursor = $manager->executeQuery('admin.User', $query);
    $user = current($userCursor->toArray());

    if (!$user) {
        die("Utente non trovato");
    }

    header('Content-Type: application/json');

    if (!empty($user->playlist_personali)) {
        // Mappiamo le playlist per includere anche solo gli id dei brani come array
        // Mappa le playlist
    $playlistArray = array_map(function($playlist) {
        return [
            'nome_playlist' => $playlist->nome_playlist,
            'descrizione' => $playlist->descrizione ?? '',
            'brani' => array_map(function($brano) {
                return $brano->id_brano;
            }, $playlist->brani ?? [])
        ];
    }, $user->playlist_personali ?? []);

    // Risposta finale con anche id del brano richiesto
    $response = [
        'id_brano_richiesto' => $idBranoCliccato,
        'playlists' => $playlistArray
    ];
        echo json_encode($response);
        exit();
    } else {
        echo json_encode([]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- basic -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- mobile metas porco due Mannaggia al 25 aprile, liberi da cosa? liberiamoci del comunismo invece-->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="viewport" content="initial-scale=1, maximum-scale=1">
    <!-- site metas -->
    <title>top Songs</title>
    <meta name="keywords" content="">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- bootstrap css -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <!-- style css -->
    <link rel="stylesheet" href="css/style.css">
    <!-- Responsive-->
    <link rel="stylesheet" href="css/responsive.css">
    <!-- fevicon -->
    <link rel="icon" href="images/fevicon.png" type="image/gif" />
    <!-- Scrollbar Custom CSS -->
    <link rel="stylesheet" href="css/jquery.mCustomScrollbar.min.css">
    <!-- Tweaks for older IEs-->
    <link rel="stylesheet" href="https://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.min.css" media="screen">
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script><![endif]-->
      <style>
       .song-list { display: flex; flex-direction: column; gap: 12px; max-width: 800px; margin: auto;}
        .song-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fff;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        } 
        .song-info { flex: 1; }
        .song-title { font-size: 18px; font-weight: bold; }
        .song-artist { color: #666; }
        .song-streams { font-size: 14px; color: #999; }
        .add-button {
            padding: 8px 12px;
            background: #1db954;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: block;
            margin-top: 20px;
            margin-left: auto;
            margin-right: auto;
        } 
        .add-button:hover {
            background: #169c44;
        }
        .select1 {
            width: 100%;
            padding: 16px 20px;
            border: none;
            border-radius: 4px;
            background-color: #f1f1f1;
        }
        input[type=number], select {
  width: 100%;
  padding: 12px 20px;
  margin: 8px 0;
  display: inline-block;
  border: 1px solid #ccc;
  border-radius: 4px;
  box-sizing: border-box;
}
.button1 {
    
  width: 20%;
  background-color: #4CAF50;
  color: white;
  padding: 14px 20px;
  margin: 0 auto;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  display: block;

}
.nice-select {
    display: none;
}
.formfiltri {
    margin-top: 20px;
    text-align: left;
    margin-left: 0;
    padding: 10px;
}
.sfondo {
    background-image: url('images/sfondobody.jpg'); 
    background-repeat: no-repeat;
    background-position: center;
    background-attachment: fixed;
    margin-top:auto;
    background-size: cover;
}    
.popup-overlay {
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.popup-content {
  background: white;
  padding: 20px 30px;
  border-radius: 12px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.3);
  text-align: center;
}
/* Titolo */
.popup-content h2 {
  font-size: 24px;
  margin-bottom: 15px;
  font-weight: bold;
}

/* Stile per la lista delle playlist */
#playlist-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 20px;
  max-height: 300px; /* Imposto un'altezza massima con scroll */
  overflow-y: auto;
}

#playlist-list li {
  padding: 10px;
  background-color: #f1f1f1;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

#playlist-list li:hover {
  background-color: #ddd; /* Cambia colore al passaggio del mouse */
}

/* Bottoni all'interno del pop-up */
.popup-buttons {
  display: flex;
  justify-content: space-between;
  gap: 10px;
}

/* Bottone per "Annulla" */
#close-popup {
  padding: 10px 20px;
  background-color: #ccc;
  color: #333;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}

#close-popup:hover {
  background-color: #bbb;
}

/* Bottone per "Aggiungi alla Playlist" */
#add-to-playlist {
  padding: 10px 20px;
  background-color: #1db954;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}

#add-to-playlist:hover {
  background-color: #169c44;
}
.no-playlist-link {
  color: #007bff;
  text-decoration: underline;
  display: inline-block;
  margin-top: 10px;
}
#addPlaylistItemBtn {
  background-color: green;
  color: white;
  border: none;
  border-radius: 50%;
  width: 25px;
  height: 25px;
  font-size: 16px;
  font-weight: bold;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}
.playlist-table {
  max-height: 300px; /* altezza massima visibile */
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 10px;
  padding-right: 8px; /* spazio per scrollbar */
}

/* Riga "tipo tabella" */
.playlist-row {
  display: grid;
  grid-template-columns: 1fr auto; /* nome a sinistra, bottone a destra */
  align-items: center;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 6px;
  background-color: #f9f9f9;
  width: 250px;
}

/* Celle */
.playlist-cell.name {
  font-weight: 500;
  font-size: 16px;
  padding-left: 5px;
}

.playlist-cell.button {
  display: flex;
  justify-content: flex-end;
}

/* Bottone "+" */
.addPlaylistItemBtn {
  background-color: green;
  color: white;
  border: none;
  border-radius: 50%;
  width: 25px;
  height: 25px;
  font-size: 16px;
  font-weight: bold;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}
.playlistToggleBtn {
  border: none;
  border-radius: 50%;
  width: 25px;
  height: 25px;
  font-size: 16px;
  font-weight: bold;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* verde: aggiungi */
.playlistToggleBtn.add {
  background-color: green;
  color: white;
}

/* rosso: rimuovi */
.playlistToggleBtn.remove {
  background-color: red;
  color: white;
}
.toast {
    visibility: hidden;
    min-width: 250px;
    margin-left: -155px;
    background-color: #333;
    color: white;
    text-align: center;
    border-radius: 2px;
    padding: 16px;
    position: fixed;
    z-index: 1;
    left: 50%;
    right: 50%;
    bottom: 30px;
    font-size: 17px;
}

.toast.show {
    visibility: visible;
    animation: fadein 0.5s, fadeout 0.5s 2.5s;
}

@keyframes fadein {
    from {bottom: 0; opacity: 0;}
    to {bottom: 30px; opacity: 1;}
}

@keyframes fadeout {
    from {bottom: 30px; opacity: 1;}
    to {bottom: 0; opacity: 0;}
}
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 20px 0;
}

.pagination a,
.pagination span {
    margin: 0 5px;
    padding: 10px;
    text-decoration: none;
    color: #333;
    background-color: #f1f1f1;
    border-radius: 5px;
}

.pagination .page-num {
    font-weight: bold;
}

.pagination .prev, .pagination .next {
    font-size: 18px;
}

.pagination .first, .pagination .last {
    font-weight: bold;
}

.pagination .ellipsis {
    font-size: 16px;
}

.pagination a:hover {
    background-color: #ddd;
}

.pagination .active {
    background-color: #4CAF50;
    color: white;
}

    </style>
</head>
 
<body class="main-layout about-page">
    <!-- loader  -->
    <div class="loader_bg">
        <div class="loader"><img src="images/loading.gif" alt="#" /></div>
    </div>
    <!-- end loader -->
    <!-- header -->
    <header>
        <!-- header inner -->
        <div class="header">
            <div class="container">
                <div class="row">
                    <div class="col-xl-2 col-lg-2 col-md-2 col-sm-2 col logo_section">
                        <div class="full">
                            <div class="center-desk">
                                <div class="logo">
                                    <a href="index.php" style="font-family:'Courier New', Courier, monospace; color: #C99700;">vibe<img src="images/logo2.jpg" alt="logo" style="width: 60px; " /></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-8 col-lg-8 col-md-10 col-sm-10">
                        <div class="menu-area">
                            <div class="limit-box">
                                <nav class="main-menu">
                                <ul class="menu-area-main">
                                        <li class="active"> <a href="index.php">Home</a> </li>
                                        <li> <a href="top100.php">TOP Songs</a> </li>
                                        <li> <a href="songs.php"> Archive</a> </li>
                                        <li> <a href="trend.php">Trend</a> </li>
                                        <?php if (!empty($_SESSION['utente_loggato'])): ?>
                                        <li> <a href="profile.php"><?php echo $_SESSION['utente_loggato'] ?></a> </li>
                                        <li><a href="logout.php">Logout</a></li>
                                        <?php else: ?>
                                        <li><a href="login.php">Login</a></li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                    <!--<div class="col-xl-2 col-lg-2 col-md-2 col-sm-2">
                        <form class="search">
                            <input class="form-control" type="text" placeholder="Search">
                            <button><img src="images/search_icon.png"></button>
                        </form>
                    </div> -_>
                </div>
            </div>
            <!-- end header inner -->
    </header>
    <!-- end header -->
    <div class="sfondo">
    <h1 style="color: white; margin-left: 700px; font-weight:bold;">Top Songs</h1>
    <form method="GET" class="formfiltri" >
    <label style="color: white; font-weight: bold;">Danceability (%) greater than:
        <input type="number" name="danceability_min" min="0" max="100" value="<?= $_GET['danceability_min'] ?? '' ?>">
    </label>
    <label style="color: white; font-weight: bold;">Valence (%) greater than:
        <input type="number" name="valence_min" min="0" max="100" value="<?= $_GET['valence_min'] ?? '' ?>">
    </label>
    <label style="color: white; font-weight: bold;">BPM greater than:
        <input type="number" name="bpm_min" min="0" value="<?= $_GET['bpm_min'] ?? '' ?>">
    </label>
    <label style="color: white; font-weight: bold;">Order by:
    <select name="sort_field" class="select1">
        <option value="streams" <?= $_GET['sort_field'] ?? '' ?>>Streams</option>
        <option value="danceability_%" <?= $_GET['sort_field'] ?? ''  ?>>Danceability</option>
        <option value="valence_%" <?= $_GET['sort_field'] ?? '' ?>>Valence</option>
        <option value="bpm" <?= $_GET['sort_field'] ?? ''?>>BPM</option>
    </select>
    </label>

<label style="color: white; font-weight: bold;">Direction:
    <select name="sort_order" class="select1">
        <option value="desc" <?= $_GET['sort_order'] ?? '' === 'desc' ? 'selected' : '' ?>>Descending</option>
        <option value="asc" <?= $_GET['sort_order'] ?? '' === 'asc' ? 'selected' : '' ?>>Ascending</option>
    </select>
</label>

    <button type="submit" class="button1">Apply filters</button>
    </form>

    <div class="song-list">
    <?php foreach ($cursor as $song): ?>
        <div class="song-card">
            <div class="song-info">
                <div class="song-title"><?= htmlspecialchars($song->track_name ?? 'Titolo sconosciuto') ?></div>
                <div class="song-artist"><?= htmlspecialchars($song->{'artist(s)_name'} ?? 'Artista sconosciuto') ?></div>
                <div class="song-streams">
                    Streams: <?= $song->streams ?>
                </div>
                    <div class="song-bpm">
                        BPM: <?= number_format($song->bpm ?? 0, 2) ?>
                    </div>
                    <div class="song-valence">
                        Valence: <?= number_format($song->{'valence_%'} ?? 0, 2) ?>
                    </div>
                    <div class="song-valence">
                        Danceability: <?= number_format($song->{'danceability_%'} ?? 0, 2) ?>
                    </div>

            </div>
            <div class="button-container">
                    <?php if (isset($_SESSION['utente_loggato'])): ?>
                        <!-- Bottone "Add to playlist" -->
                        <form method="POST" action="" onsubmit="handleAdd(event, this, 'playlist')">
                            <input type="hidden" name="song_id" value="<?= (string)$song->_id ?>">
                            <input type="hidden" name="song_name" value="<?= (string)$song->track_name ?>">
                            <input type="hidden" name="addToFavorites" value="false">
                            <button id="aggiungiPlaylistBtn" class="add-button">Add to Playlist</button>
                            </form>
                    </br>
                        <!-- Bottone "Aggiungi ai preferiti" -->
                        <form method="POST" action=""  onsubmit="handleAdd(event, this, 'preferiti')">
                            <input type="hidden" name="track_id" value="<?= htmlspecialchars($song->_id) ?>">
                            <input type="hidden" name="track_name" value="<?= (string)$song->track_name ?>">
                            <input type="hidden" name="addToFavorites" value="true">
                            <button class="add-button" type="submit">Add to Favorites</button>
                        </form>
                    <?php else: ?>
                        <p><a href="login.php">"Log in to add to favorites and playlists!"</a></p>
                    <?php endif; ?>
                </div>
        </div>
    <?php endforeach; ?>
<div id="popup" class="popup-overlay" style="display: none;">
    <div class="popup-content">
        <p id="popup-message">Qui verrà mostrato il messaggio</p>
        <button onclick="closePopup()">Close</button>
    </div>
</div>
<div id="playlist-popup" class="popup" style="display: none;">
  <div class="popup-content">
    <h2>Seleziona una Playlist</h2>
    <ul id="playlist-list">
      <!-- La lista delle playlist verrà aggiunta dinamicamente tramite JS -->
    </ul>
    <div class="popup-buttons">
      <button id="close-popup" onclick="closePlaylistPopup()">Cancel</button>
      <button id="add-to-playlist" onclick="addToPlaylist()">Add to Playlist</button>
    </div>
  </div>
</div>
</div>

<div id="toast"></div>
</div>

    </div>

    
    <!-- Javascript files-->
<script>
function showPopup(message, isSuccess) {
  // Imposta il messaggio del pop-up
  document.getElementById('popup-message').textContent = message;

  // Cambia lo stile del pop-up in base al risultato (successo o errore)
  const popup = document.getElementById('popup');
  if (isSuccess) {
    popup.classList.remove('error');
    popup.classList.add('success');
  } else {
    popup.classList.remove('success');
    popup.classList.add('error');
  }

  // Mostra il pop-up
  popup.style.display = 'flex';
}

function closePopup() {
  document.getElementById('popup').style.display = 'none';
  //document.getElementById('popup-playlist').style.display = 'none';
}

function handleAdd(event, form, tipo) {
  event.preventDefault();

  const formData = new FormData(form);

  if (tipo === 'preferiti') {
    fetch(form.action, {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        showPopup(data.message, true);
      } else {
        showPopup(data.message, false);
      }
    })
    .catch(err => {
      showPopup('Errore: ' + err, false);
    });

  } else if (tipo === 'playlist') {
    // Estrai il songId dal form
    formData.append("action", "get_playlists");

    fetch(form.action, {
      method: "POST",
      body: formData,
    })
      .then(res => res.json())
      .then(data => {
        console.log("Playlist ricevute:", data);
        mostraPopupPlaylist(data.playlists, data.id_brano_richiesto);
      })
      .catch(err => {
        console.error("Errore fetch playlist:", err);
      });

    return;
  }
}
function mostraPopupPlaylist(playlists, branoId) {
    const popup = document.createElement("div");
    popup.className = "popup-overlay";
    popup.id = "playlistPopupAdd"; // 👈 aggiungi questo

    const content = document.createElement("div");
    content.className = "popup-content";

    const title = document.createElement("h3");
    title.textContent = "Choose a playlist";
    content.appendChild(title);

    if (playlists.length === 0) {
        const br = document.createElement("br");
        const noPlaylists = document.createElement("a");
        noPlaylists.href = "profilo.php"; // cambia con il path corretto se diverso
        noPlaylists.textContent = "You have no playlists. Click here to create one!";
        noPlaylists.className = "no-playlist-link"; // opzionale: per styling
        content.appendChild(br);
        content.appendChild(noPlaylists);
    } else {
        const table = document.createElement("div");
        table.className = "playlist-table";

        playlists.forEach(pl => {
            const row = document.createElement("div");
            row.className = "playlist-row";

            const cellName = document.createElement("div");
            cellName.className = "playlist-cell name";
            cellName.textContent = pl.nome_playlist || "No nome";

            const cellBtn = document.createElement("div");
            cellBtn.className = "playlist-cell button";

            // ID del brano da aggiungere (questo lo dovrai avere già definito)

            const addBtn = document.createElement("button");
            addBtn.className = "addPlaylistItemBtn";
            addBtn.title = "Add to the playlist";
            addBtn.innerText = "+";

             // Controlla se il brano è già nella playlist
             if (pl.brani && pl.brani.includes(branoId)) {
                addBtn.innerText = "-"; // Se il brano è già nella playlist, cambia a "-"
                addBtn.style.color = "red"; // Cambia il colore del bottone a rosso
                addBtn.style.backgroundColor = "red";
                addBtn.style.color = "white"; // opzionale: per rendere il testo leggibile sul rosso
         
                addBtn.onclick = () => {
                    console.log(`Il brano è già presente nella playlist: ${pl.nome_playlist}`);
                    // Aggiungi logica per rimuovere il brano, se necessario
                    rimuoviCanzoneDaPlaylist(pl.nome_playlist, branoId); // Implementa la funzione per rimuovere il brano
                };
            } else {
                addBtn.onclick = () => {
                    console.log(`Aggiunta alla playlist: ${pl.nome_playlist}`);
                    // Aggiungi il brano alla playlist qui
                    aggiungiCanzoneAPlaylist(pl.nome_playlist, branoId); // Dovrai implementare questa funzione
                };
            }

            cellBtn.appendChild(addBtn);
            row.appendChild(cellName);
            row.appendChild(cellBtn);
            table.appendChild(row);
        });

        content.appendChild(table);
    }

    const closeBtn = document.createElement("button");
    closeBtn.textContent = "Close";
    closeBtn.className = "add-button";
    closeBtn.onclick = () => popup.remove();

    content.appendChild(closeBtn);
    popup.appendChild(content);
    document.body.appendChild(popup);
}

function closePlaylistPopup() {
  document.getElementById('playlistPopup').style.display = 'none';
}
function aggiungiCanzoneAPlaylist(playlistName, branoId) {
    fetch('modifica_playlist.php', {
    method: 'POST',
    body: JSON.stringify({
        nome_playlist: playlistName,
        song_id: branoId,
        action: 'add'
    })
})
.then(response => response.json())
.then(data => {
    const toast = document.getElementById('toast');

    if (data.success) {
        toast.style.backgroundColor = '#4CAF50'; // Verde per successo
        toast.innerHTML = data.message;
        document.getElementById('playlistPopupAdd').remove();

    } else {

        toast.style.backgroundColor = '#f44336'; // Rosso per errore
        toast.innerHTML = `Errore: ${data.message}`;
    }

    toast.className = "toast show"; // Mostra il toast
    setTimeout(() => {
        toast.className = toast.className.replace("show", ""); // Nascondi il toast dopo 3 secondi
    }, 3000);
})
.catch(error => console.error('Errore nella richiesta:', error));

}

function rimuoviCanzoneDaPlaylist(playlistName, branoId) {
    fetch('modifica_playlist.php', {
    method: 'POST',
    body: JSON.stringify({
        nome_playlist: playlistName,
        song_id: branoId,
        action: 'remove'
    })
})
.then(response => response.json())
.then(data => {
    const toast = document.getElementById('toast');

    if (data.success) {
        toast.style.backgroundColor = '#4CAF50'; // Verde per successo
        toast.innerHTML = data.message;
        document.getElementById('playlistPopupAdd').remove();
    } else {
        toast.style.backgroundColor = '#f44336'; // Rosso per errore
        toast.innerHTML = `Errore: ${data.message}`;
    }

    toast.className = "toast show"; // Mostra il toast
    setTimeout(() => {
        toast.className = toast.className.replace("show", ""); // Nascondi il toast dopo 3 secondi
    }, 3000);
})
.catch(error => console.error('Errore nella richiesta:', error));

}


</script>
    <script src="js/jquery.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/jquery-3.0.0.min.js"></script>
    <script src="js/plugin.js"></script>
    <!-- sidebar -->
    <script src="js/jquery.mCustomScrollbar.concat.min.js"></script>
    <script src="js/custom.js"></script>
    <script src="https:cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.min.js"></script>
    <script>
        $(document).ready(function() {
            $(".fancybox").fancybox({
                openEffect: "none",
                closeEffect: "none"
            });

            $(".zoom").hover(function() {

                $(this).addClass('transition');
            }, function() {

                $(this).removeClass('transition');
            });
        });
    </script>
</body>

</html>