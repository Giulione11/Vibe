<?php
session_start();
$manager = new MongoDB\Driver\Manager("mongodb://mongo:27017");

$filter = [];
$options = [
    'sort' => ['streams' => -1],
    'limit' => 100
];

$query = new MongoDB\Driver\Query($filter, $options);
$cursor = $manager->executeQuery('admin.Spotify2023', $query);
$filters = [];
$sort = [];
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

$options = [
    'limit' => 100
];

if (!empty($sort)) {
    $options['sort'] = $sort;
}

$query = new MongoDB\Driver\Query($filters, $options);
$cursor = $manager->executeQuery('admin.Spotify2023', $query);
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
    <title>top 100</title>
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
                    <div class="col-xl-2 col-lg-2 col-md-2 col-sm-2">
                        <form class="search">
                            <input class="form-control" type="text" placeholder="Search">
                            <button><img src="images/search_icon.png"></button>
                        </form>
                    </div>
                </div>
            </div>
            <!-- end header inner -->
    </header>
    <!-- end header -->
    <div class="sfondo">
    <h1 style="color: white; margin-left: 700px; font-weight:bold;">Top 100</h1>
    <form method="GET" class="formfiltri" >
    <label style="color: white; font-weight: bold;">Danceability (%) maggiore di:
        <input type="number" name="danceability_min" min="0" max="100" value="<?= $_GET['danceability_min'] ?? '' ?>">
    </label>
    <label style="color: white; font-weight: bold;">Valence (%) maggiore di:
        <input type="number" name="valence_min" min="0" max="100" value="<?= $_GET['valence_min'] ?? '' ?>">
    </label>
    <label style="color: white; font-weight: bold;">BPM maggiore di:
        <input type="number" name="bpm_min" min="0" value="<?= $_GET['bpm_min'] ?? '' ?>">
    </label>
    <label style="color: white; font-weight: bold;">Ordina per:
    <select name="sort_field" class="select1">
        <option value="streams" <?= $_GET['sort_field'] ?? '' ?>>Streams</option>
        <option value="danceability_%" <?= $_GET['sort_field'] ?? ''  ?>>Danceability</option>
        <option value="valence_%" <?= $_GET['sort_field'] ?? '' ?>>Valence</option>
        <option value="bpm" <?= $_GET['sort_field'] ?? ''?>>BPM</option>
    </select>
    </label>

<label style="color: white; font-weight: bold;">Direzione:
    <select name="sort_order" class="select1">
        <option value="desc" <?= $_GET['sort_order'] ?? '' === 'desc' ? 'selected' : '' ?>>Decrescente</option>
        <option value="asc" <?= $_GET['sort_order'] ?? '' === 'asc' ? 'selected' : '' ?>>Crescente</option>
    </select>
</label>

    <button type="submit" class="button1">Applica filtri</button>
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
            </div>
            <form method="POST" action="" onsubmit="handleAdd(event, this)">
                <input type="hidden" name="song_id" value="<?= (string)$song->_id ?>">
                <button type="submit" class="add-button">Add to playlist</button>
            </form>
        </div>
    <?php endforeach; ?>
    <div id="popup" class="popup-overlay" style="display: none;">
  <div class="popup-content">
    <p>Brano aggiunto alla playlist!</p>
    <button onclick="closePopup()">Chiudi</button>
  </div>
</div>
</div>
    </div>

    
    <!-- Javascript files-->
    <script>
function showPopup() {
  document.getElementById('popup').style.display = 'flex';
}

function closePopup() {
  document.getElementById('popup').style.display = 'none';
}
</script>
<script>
function handleAdd(event, form) {
  event.preventDefault(); // blocca il submit tradizionale

  const formData = new FormData(form);
  fetch(form.action, {
    method: 'POST',
    body: formData
  })
  .then(res => res.ok ? showPopup() : alert('Errore'))
  .catch(err => alert('Errore nella richiesta'));
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