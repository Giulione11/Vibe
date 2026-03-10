<?php 
use MongoDB\BSON\ObjectId;
session_start();

$manager = new MongoDB\Driver\Manager("mongodb://mongo:27017");

$username = $_SESSION['utente_loggato']; // o qualunque username attivo

// 1. Carica l'utente
$filter = ['username' => $username];
$query = new MongoDB\Driver\Query($filter);
$userCursor = $manager->executeQuery('admin.User', $query);
$user = current($userCursor->toArray());

if (!$user) {
    die("Utente non trovato");
}

// 1. Raccogli tutti gli ID dei brani
$all_ids = [];

foreach ($user->preferiti->brani ?? [] as $b) {
    $all_ids[] = $b->id_brano;
}

foreach ($user->playlist_personali ?? [] as $playlist) {
    foreach ($playlist->brani ?? [] as $b) {
        $all_ids[] = $b->id_brano;
    }
}

$all_ids = array_unique($all_ids);

// 2. Converti in ObjectId validi
$objectIds = [];
foreach ($all_ids as $id) {
    try {
        $objectIds[] = new ObjectId($id);
    } catch (Exception $e) {
        // ID non valido, salta
    }
}

// 3. Cerca in entrambe le collezioni
$tracks = [];

if (!empty($objectIds)) {
    // Prima collezione
    $query1 = new MongoDB\Driver\Query(['_id' => ['$in' => $objectIds]]);
    $cursor1 = $manager->executeQuery('admin.Spotify2023', $query1);
    foreach ($cursor1 as $track) {
        $tracks[(string)$track->_id] = $track;
    }

    // Seconda collezione (solo quelli mancanti)
    $remaining = array_diff($objectIds, array_map(fn($id) => new ObjectId($id), array_keys($tracks)));
    if (!empty($remaining)) { 
        $remaining = array_values($remaining); // <--- aggiungi questo

        $query2 = new MongoDB\Driver\Query(['_id' => ['$in' => $remaining]]);
        $cursor2 = $manager->executeQuery('admin.Spotify', $query2);
        foreach ($cursor2 as $track) {
            $tracks[(string)$track->_id] = $track;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- basic -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- mobile metas -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="viewport" content="initial-scale=1, maximum-scale=1">
    <!-- site metas -->
    <title>Profile</title>
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
        .sfondo {
    background-image: url('images/sfondobody.jpg'); 
    background-repeat: no-repeat;
    background-position: center;
    background-attachment: fixed;
    margin-top:auto;
    background-size: cover;
    width: 100%;
}    
.song-list { display: flex; flex-direction: column; gap: 12px; max-width: 800px; margin: auto;opacity: 0.8;}
.song-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fff;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        } 
        .song-info { flex: 1;}
    </style>
</head>
<!-- body -->

<body class="main-layout contact-page">
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
                                        <li> <a href="index.php">Home</a> </li>
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
                    </div> -->
                </div>
            </div>
            <!-- end header inner -->
    </header>
    <!-- end header -->
    <div class="sfondo">
       <h1 style="color: white; margin-left: 700px; font-weight:bold;">PROFILE</h1>
       <div class="song-list">
        <div class="song-card">
            <div class="song-info">
       <h2 style="font-weight: bold;">Profile </h2>
<p>Name: <?= $user->profilo->nome ?> </p>
<p>Surname: <?= $user->profilo->cognome ?></p>
<p>Email: <?= $user->profilo->email ?></p>
<p>Gender: <?= $user->profilo->genere ?></p>
<?php
// Supponiamo $user->profilo->data_nascita sia la stringa ISO 8601
$dataNascitaStr = $user->profilo->data_nascita;

if (!empty($dataNascitaStr)) {
    $date = new DateTime($dataNascitaStr);
    echo '<p>Date of Birth: ' . $date->format('d/m/Y') . '</p>';
} else {
    echo '<p>Data di nascita non disponibile</p>';
}
?>
<p>Bio: <?= $user->profilo->bio ?></p>
            </div>
            </div>
            <div class="song-card">
            <div class="song-info">
<h3 style="font-weight: bold;">🎧 Favorite Tracks</h3>
<ul>
<?php foreach ($user->preferiti->brani ?? [] as $b): ?>
    <?php 
        $id = (string)$b->id_brano;
        $track = $tracks[$id] ?? null;
        if (isset($track->{'artists'})) {
            $artistName = $track->{'artists'};
        } elseif (isset($track->{'artist(s)_name'})) {
            $artistName = $track->{'artist(s)_name'};
        } else {
            $artistName = 'Unknown artist';
        }        
    ?>
    <li> <?=  $track ? $track->track_name . " - " . $artistName : 'Brano not found'    ?>
<?php if ($track): ?>
        <form method="post" action="rimuovi_preferito.php" style="display:inline;">
            <input type="hidden" name="id_brano" value="<?= $id ?>">
            <button type="submit" style="background:none;border:none;color:red;cursor:pointer;" title="Remove from favorites" >🗑️</button>
        </form>
    <?php endif; ?></li>
<?php endforeach; ?>
</ul>
            </div>
            </div>
            <div class="song-card">
            <div class="song-info">
            <div style="display: flex; align-items: center; gap: 10px;">
<h3 style="font-weight: bold; text-align: center;margin: 0;">🎶 <?php echo $_SESSION['utente_loggato'];?>'s Playlist</h3>
<button id="addPlaylistBtn" title="Crea nuova playlist" style="
        background-color: green;
        color: white;
        border: none;
        border-radius: 50%;
        width: 25px;
        height: 25px;
        font-size: 20px;
        font-weight: bold;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-top: -7px;
    ">+</button></div>
<?php foreach ($user->playlist_personali ?? [] as $playlist): ?>
    <div style="display: flex; align-items: center; gap: 10px;">
    <h4><?= $playlist->nome_playlist ?></h4>
    <form method="post" action="rimuovi_playlist.php" onsubmit="return confirm('Sei sicuro di voler rimuovere questa playlist?');" style="margin: 0;">
        <input type="hidden" name="nome_playlist" value="<?= htmlspecialchars($playlist->nome_playlist) ?>">
        <button type="submit" title="Rimuovi playlist" style="
            background: none;
            border: none;
            color: red;
            font-size: 20px;
            cursor: pointer;
            display:flex;
            margin-top: -7px;

        ">🗑️</button>
    </form>
    </div>
    <p><?= $playlist->descrizione ?></p>
    <ul>
    <?php foreach ($playlist->brani ?? [] as $b): ?>
        <?php $track = $tracks[$b->id_brano] ?? null; ?>
    <li> <?= $track ? $track->track_name . " - " . (isset($track->artists) ? $track->artists : $track->{'artist(s)_name'}) : "Brano non trovato" ?>    
    <?php if ($track): ?>
        <form method="post" action="rimuovi_da_playlist.php" style="display:inline;">
            <input type="hidden" name="id_brano" value="<?= $b->id_brano ?>">
            <input type="hidden" name="nome_playlist" value="<?= htmlspecialchars($playlist->nome_playlist) ?>">
            <button type="submit" style="background:none;border:none;color:red;cursor:pointer;" title="Rimuovi dalla playlist">🗑️</button>
        </form>
    <?php endif; ?></li>
    <?php endforeach; ?>
    </ul>
<?php endforeach; ?>
            </div>
        </div>
       </div>
    </div>
<!-- MODALE PER CREARE UNA NUOVA PLAYLIST -->
<div id="playlistModal" style="
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    justify-content: center;
    align-items: center;
">
    <div style="
        background: white;
        padding: 20px;
        border-radius: 10px;
        width: 300px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        position: relative;
    ">
        <h3>Crea Playlist</h3>
        <form method="POST" action="crea_playlist.php">
            <label>Nome Playlist:</label><br>
            <input type="text" name="nome_playlist" required><br><br>
            
            <label>Descrizione:</label><br>
            <textarea name="descrizione" rows="3" style="width: 100%;"></textarea><br><br>
            
            <button type="submit" style="background-color: green; color: white; padding: 5px 10px; border: none;">Crea</button>
            <button type="button" onclick="closeModal()" style="margin-left: 10px;">Annulla</button>
        </form>
    </div>
</div>
<!-- MODALE CENTRALE -->
<div id="successModal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
    background-color: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center;">
  <div style="background: white; padding: 30px 40px; border-radius: 10px; text-align: center; max-width: 400px; box-shadow: 0 0 10px rgba(0,0,0,0.3);">
    <h4 id="successMessage" style="margin: 0;"></h4>
  </div>
</div>

    <!-- end footer -->
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
    <script>
    const modal = document.getElementById("playlistModal");

    document.getElementById("addPlaylistBtn").addEventListener("click", function () {
        modal.style.display = "flex";
    });

    function closeModal() {
        modal.style.display = "none";
    }

    // Chiudi se clicchi fuori
    window.onclick = function(event) {
        if (event.target === modal) {
            closeModal();
        }
    }
</script>

<script>
  function getParam(name) {
    const url = new URL(window.location.href);
    return url.searchParams.get(name);
  }

  const success = getParam("success");
  if (success) {
    const messages = {
      "brano_rimosso": "✅ Brano rimosso con successo.",
      "playlist_rimossa": "🗑️ Playlist eliminata.",
      "brano_aggiunto": "🎵 Brano aggiunto con successo.",
      "playlist_creata": "📁 Playlist creata con successo!",
      "playlist_duplicata": "⚠️ Esiste già una playlist con questo nome!",
      "errore_nome_vuoto": "⚠️ Inserisci un nome valido per la playlist."
    };

    const modal = document.getElementById("successModal");
    const message = messages[success] || "✅ Operazione completata.";
    document.getElementById("successMessage").textContent = message;

    modal.style.display = "flex";

    // Chiudi dopo 3.5 secondi
    setTimeout(() => {
      modal.style.display = "none";

      // Rimuovi parametro "success" dalla URL senza ricaricare
      const url = new URL(window.location);
      url.searchParams.delete("success");
      window.history.replaceState({}, document.title, url);
    }, 3500);
  }
</script>


</body>

</html>