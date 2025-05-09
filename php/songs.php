<?php
ob_start(); // Avvia il buffering dell'output
session_start();
// Verifica se l'utente è loggato
if (!isset($_SESSION['utente_loggato'])) {
    header('Location: login.php');
    exit();
}
$manager = new MongoDB\Driver\Manager("mongodb://mongo:27017");

$filter = [];
$options = [
    'sort' => ['streams' => -1],
    'limit' => 100
];

$query = new MongoDB\Driver\Query($filter, $options);
$cursor = $manager->executeQuery('admin.Spotify', $query);
$filters = [];
$sort = [];
if (!empty($_GET)) {


    if (isset($_GET['danceability_min']) && $_GET['danceability_min'] !== '') {
        $filters['danceability']['$gte'] = (float)$_GET['danceability_min'];
    }

    if (isset($_GET['valence_min']) && $_GET['valence_min'] !== '') {
        $filters['valence']['$gte'] = (float)$_GET['valence_min'];
    }

    if (isset($_GET['popularity_min']) && $_GET['popularity_min'] !== '') {
        $filters['popularity']['$gte'] = (int)$_GET['popularity_min'];
    }

    if (isset($_GET['explicit']) && $_GET['explicit'] !== '') {
        $filters['explicit'] = (bool)$_GET['explicit'];
    }
if (!empty($_GET['sort_field'])) {
    $field = $_GET['sort_field'];
    $direction = ($_GET['sort_order'] ?? 'desc') === 'asc' ? 1 : -1;
    $sort[$field] = $direction;
}

$options = [
    'limit' => 100
];

if (!empty($sort)) {
    $options['sort'] = $sort;
}

$query = new MongoDB\Driver\Query($filters, $options);
$cursor = $manager->executeQuery('admin.Spotify', $query);
}

$start = microtime(true);

/* Esegui la query
$query = new MongoDB\Driver\Query($filters, $options);
$cursor = $manager->executeQuery('mydb.tracks', $query);

$end = microtime(true);
$executionTime = $end - $start;

echo "Tempo di esecuzione della query: " . number_format($executionTime, 6) . " secondi\n";
*/

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
            $messaggio = "Il brano è già nei preferiti!";
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
        $messaggio = "Brano " .$_POST['track_name']. " aggiunto ai preferiti!";

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
ob_end_clean(); // Pulisce il buffer dell'output
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
    <title>Archive</title>
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
    <!-- Aggiungi nel tuo head per includere i file di Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

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

    </style>
</head>
<!-- body -->
 
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
                                        <li> <a href="top100.php">TOP 100</a> </li>
                                        <li> <a href="songs.php"> Archive</a> </li>
                                        <li> <a href="blog.php">Trend</a> </li>
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
                    <!-- <div class="col-xl-2 col-lg-2 col-md-2 col-sm-2">
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
    <h1 style="color: white; margin-left: 700px; font-weight:bold;">Archive</h1>
    <form method="GET" class="formfiltri">
        <label style="color: white; font-weight: bold;">Danceability (%) maggiore di:
            <input type="number" name="danceability_min" min="0" max="100" value="<?= $_GET['danceability_min'] ?? '' ?>">
        </label>
        <label style="color: white; font-weight: bold;">Valence (%) maggiore di:
            <input type="number" name="valence_min" min="0" max="100" value="<?= $_GET['valence_min'] ?? '' ?>">
        </label>
        <label style="color: white; font-weight: bold;">Popularity (%) maggiore di:
            <input type="number" name="popularity_min" min="0" max="100" value="<?= $_GET['popularity_min'] ?? '' ?>">
        </label>
        <label style="color: white; font-weight: bold;">Explicit:
            <select name="explicit" id="explicit">
                <option value="">All</option>
                <option value="1" <?= isset($_GET['explicit']) && $_GET['explicit'] == '1' ? 'selected' : ''; ?>>Yes</option>
                <option value="0" <?= isset($_GET['explicit']) && $_GET['explicit'] == '0' ? 'selected' : ''; ?>>No</option>
            </select>
        </label>
        <label style="color: white; font-weight: bold;">Ordina per:
            <select name="sort_field" class="select1">
                <option value="popularity" <?= isset($_GET['sort_field']) && $_GET['sort_field'] == 'popularity' ? 'selected' : ''; ?>>Popularity</option>
                <option value="danceability" <?= isset($_GET['sort_field']) && $_GET['sort_field'] == 'danceability' ? 'selected' : ''; ?>>Danceability</option>
                <option value="valence" <?= isset($_GET['sort_field']) && $_GET['sort_field'] == 'valence' ? 'selected' : ''; ?>>Valence</option>
            </select>
        </label>
        <label style="color: white; font-weight: bold;">Direzione:
            <select name="sort_order" class="select1">
                <option value="asc" <?= isset($_GET['sort_order']) && $_GET['sort_order'] == 'asc' ? 'selected' : ''; ?>>Crescente</option>
                <option value="desc" <?= isset($_GET['sort_order']) && $_GET['sort_order'] == 'desc' ? 'selected' : ''; ?>>Decrescente</option>
            </select>
        </label>
        <button type="submit" class="button1">Applica filtri</button>
    </form>

    <div class="song-list">
        <?php foreach ($cursor as $song): ?>
            <div class="song-card">
                <div class="song-info">
                    <div class="song-title"><?= htmlspecialchars($song->track_name ?? 'Titolo sconosciuto') ?></div>
                    <div class="song-artist"><?= htmlspecialchars($song->{'artists'} ?? 'Artista sconosciuto') ?></div>
                    <div class="song-danceability">
                        Danceability: <?= number_format($song->danceability ?? 0, 2) ?>
                    </div>
                    <div class="song-valence">
                        Valence: <?= number_format($song->valence ?? 0, 2) ?>
                    </div>
                    <div class="song-duration">
                        <?php 
                            // Verifica il valore di $song->duration per capire che tipo di dato stiamo trattando
                            if (isset($song->duration_ms)) {
                                $durationInSecondi = floor($song->duration_ms / 1000);  // Conversione in secondi
                                if ($durationInSecondi > 0) {
                                    echo "Duration: " . gmdate("i:s", $durationInSecondi);  // Mostra minuti e secondi
                                } else {
                                    echo "Duration: Non disponibile";
                                }
                            } else {
                                echo "Duration: Non disponibile";
                            }
                        ?>
                    </div>
                    <div class="song-popularity">
                        Popularity: <?= $song->popularity ?? 'Non disponibile' ?>
                    </div>
                    <div class="song-explicit">
                        Explicit: <?= $song->explicit ? 'Yes' : 'No' ?>
                    </div>
                </div>
                <div class="button-container">
                    <?php if (isset($_SESSION['utente_loggato'])): ?>
                        <!-- Bottone "Add to playlist" -->
                        <form method="POST" action="" onsubmit="handleAdd(event, this)">
                            <input type="hidden" name="song_id" value="<?= (string)$song->_id ?>">
                            <button type="submit" class="add-button">Add to playlist</button>
                        </form>
                    </br>
                        <!-- Bottone "Aggiungi ai preferiti" -->
                        <form method="POST" action=""  onsubmit="handleAdd(event, this)">
                            <input type="hidden" name="track_id" value="<?= htmlspecialchars($song->_id) ?>">
                            <input type="hidden" name="track_name" value="<?= (string)$song->track_name ?>">
                            <button class="add-button" type="submit">Aggiungi ai preferiti</button>
                        </form>
                    <?php else: ?>
                        <p><a href="login.php">Per aggiungere ai preferiti, effettua il login!</a></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="popup" class="popup-overlay" style="display: none;">
    <div class="popup-content">
        <p id="popup-message">Qui verrà mostrato il messaggio</p>
        <button onclick="closePopup()">Chiudi</button>
    </div>
</div>


</div>

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
}

function handleAdd(event, form) {
  event.preventDefault(); // blocca il submit tradizionale

  const formData = new FormData(form);

  fetch(form.action, {
  method: 'POST',
  body: formData
})
.then(res => {
  if (!res.ok) {
    return res.text(); // Mostra il testo della risposta in caso di errore
  }
  return res.json(); // Continua a leggere la risposta come JSON se è OK
})
.then(data => {
  console.log(data); // Aggiungi il log per vedere la risposta
  if (data.success) {
    showPopup(data.message, true); // Mostra il pop-up con messaggio di successo
  } else {
    showPopup(data.message, false); // Mostra il pop-up con messaggio di errore
  }
})
.catch(err => {
  console.error('Errore nella richiesta:', err); // Log per l'errore
  showPopup('Errore nella richiesta'+ err, false); // Mostra errore se c’è un problema nella richiesta
});

}
</script>


<!-- Aggiungi nella parte inferiore del body per includere i file JS di Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Javascript files-->
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