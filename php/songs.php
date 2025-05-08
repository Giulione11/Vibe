<?php
session_start();
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
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script><![endif]-->
      <style>
       .song-card {
    border: 1px solid #ccc;
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 8px;
    background-color: #f9f9f9;
}

.song-info {
    margin-bottom: 10px;
}

.song-title {
    font-size: 1.2em;
    font-weight: bold;
}

.song-artist {
    color: #555;
}

.song-danceability,
.song-valence,
.song-duration,
.song-popularity,
.song-explicit {
    margin: 5px 0;
}

.add-button {
    background-color: #007bff;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.add-button:hover {
    background-color: #0056b3;
}
.nice-select{
    display: none;
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

    <div class="aboutbg">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="abouttitlepage">
                        <h2 style="font-family:'Courier New', Courier, monospace; font-weight: bold;">ARCHIVE</h2>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="song-list">
    <form method="GET" action="">
        <label for="danceability_min">Danceability (min):</label>
        <input type="number" step="0.1" name="danceability_min" id="danceability_min" value="<?php echo isset($_GET['danceability_min']) ? $_GET['danceability_min'] : ''; ?>" min="0" max="1">
        
        <label for="valence_min">Valence (min):</label>
        <input type="number" step="0.1" name="valence_min" id="valence_min" value="<?php echo isset($_GET['valence_min']) ? $_GET['valence_min'] : ''; ?>" min="0" max="1">
        
        <label for="popularity_min">Popularity (min):</label>
        <input type="number" name="popularity_min" id="popularity_min" value="<?php echo isset($_GET['popularity_min']) ? $_GET['popularity_min'] : ''; ?>" min="0" max="100">
        
        <label for="explicit">Explicit:</label>
        <select name="explicit" id="explicit">
            <option value="">All</option>
            <option value="1" <?php echo isset($_GET['explicit']) && $_GET['explicit'] == '1' ? 'selected' : ''; ?>>Yes</option>
            <option value="0" <?php echo isset($_GET['explicit']) && $_GET['explicit'] == '0' ? 'selected' : ''; ?>>No</option>
        </select>
        
        <label for="sort_field">Sort By:</label>
        <select name="sort_field" id="sort_field">
            <option value="popularity" <?php echo isset($_GET['sort_field']) && $_GET['sort_field'] == 'popularity' ? 'selected' : ''; ?>>Popularity</option>
            <option value="danceability" <?php echo isset($_GET['sort_field']) && $_GET['sort_field'] == 'danceability' ? 'selected' : ''; ?>>Danceability</option>
            <option value="valence" <?php echo isset($_GET['sort_field']) && $_GET['sort_field'] == 'valence' ? 'selected' : ''; ?>>Valence</option>
        </select>

        <label for="sort_order">Sort Order:</label>
        <select name="sort_order" id="sort_order">
            <option value="asc" <?php echo isset($_GET['sort_order']) && $_GET['sort_order'] == 'asc' ? 'selected' : ''; ?>>Ascending</option>
            <option value="desc" <?php echo isset($_GET['sort_order']) && $_GET['sort_order'] == 'desc' ? 'selected' : ''; ?>>Descending</option>
        </select>

        <button type="submit">Apply Filters</button>
    </form>
    </br>
    <?php foreach ($cursor as $song): ?>
    <div class="song-card">
        <div class="song-info">
            <div class="song-title"><?= htmlspecialchars($song->track_name ?? 'Titolo sconosciuto') ?></div>
            <div class="song-artist"><?= htmlspecialchars($song->{'artist(s)_name'} ?? 'Artista sconosciuto') ?></div>
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
                echo "duration: " . gmdate("i:s", $durationInSecondi);  // Mostra minuti e secondi
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
        <form method="POST" action="">
            <input type="hidden" name="song_id" value="<?= (string)$song->_id ?>">
            <button type="submit" class="add-button">Add to playlist</button>
        </form>
    </div>
<?php endforeach; ?>

</div>


    
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