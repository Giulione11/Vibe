<?php 
// all’inizio del file (dopo <?php)
use MongoDB\BSON\ObjectId;
session_start();
// Verifica se l'utente è loggato
if (!isset($_SESSION['utente_loggato'])) {
    header('Location: login.php');
    exit();
}
$manager = new MongoDB\Driver\Manager("mongodb://mongo:27017");
// === Trend 1: Top 10 artisti con più brani ===
$pipeline1 = [
    // 1. Crea un nuovo campo trasformando la stringa in un array (separa per virgola e spazio)
    ['$set' => ['artisti_singoli' => ['$split' => ['$artist(s)_name', ', ']]]],
    
    // 2. Crea un documento separato per ogni artista nell'array
    ['$unwind' => '$artisti_singoli'],
    ['$group' => ['_id' => '$artist(s)_name', 'total_tracks' => ['$sum' => 1]]],
    ['$sort' => ['total_tracks' => -1]],
    ['$limit' => 10]
];

$command1 = new MongoDB\Driver\Command([
    'aggregate' => 'Spotify2023',
    'pipeline' => $pipeline1,
    'cursor' => new stdClass,
    'allowDiskUse' => true
]);
$__start_time = microtime(true);
$cursor1 = $manager->executeCommand('admin', $command1);
$__end_time = microtime(true);
$__execution_time = $__end_time - $__start_time;  // latenza di connessione misurata;

$topArtists = $cursor1->toArray(); // 👈 Trend 1 salvato qui

$__start_time = microtime(true);
// === Trend 2: Distribuzione dei brani per anno ===
$pipeline2 = [
    ['$group' => ['_id' => '$released_year', 'count' => ['$sum' => 1]]],
    ['$sort' => ['_id' => 1]]
];

$command2 = new MongoDB\Driver\Command([
    'aggregate' => 'Spotify2023',
    'pipeline' => $pipeline2,
    'cursor' => new stdClass,
]);

$cursor2 = $manager->executeCommand('admin', $command2);
$yearDistribution = $cursor2->toArray(); // 👈 Trend 2 salvato separatamente

$years = [];
$counts = [];

foreach ($yearDistribution as $entry) {
    $years[] = $entry->_id;
    $counts[] = $entry->count;
}
echo "<script>
    const years = " . json_encode($years) . ";
    const counts = " . json_encode($counts) . ";
</script>";
// Importante: la pipeline usa $dateFromString per convertire la stringa in data,
// e $year per estrarre l'anno da quella data
$__end_time = microtime(true);
$__execution_time1 = $__end_time - $__start_time;

$__start_time = microtime(true);
$pipeline3 = [
    [
        '$addFields' => [
            'release_date_parsed' => [
                '$dateFromString' => [
                    'dateString' => '$release_date',
                    'format' => '%B %d, %Y', // esempio: "April 17, 2020"
                    'onError' => null // evita errori se il formato non è corretto
                ]
            ]
        ]
    ],
    [
        '$group' => [
            '_id' => ['$year' => '$release_date_parsed'], // estrai anno
            'avg_danceability' => ['$avg' => '$danceability'],
            'avg_valence' => ['$avg' => '$valence'],
            'avg_energy' => ['$avg' => '$energy']
        ]
    ],
    ['$sort' => ['_id' => 1]]
];

$command3 = new MongoDB\Driver\Command([
    'aggregate' => 'Spotify',
    'pipeline' => $pipeline3,
    'cursor' => new stdClass,
]);

$cursor3 = $manager->executeCommand('admin', $command3);
$trend3Data = $cursor3->toArray();

$years1 = [];
$avgDanceability = [];
$avgValence = [];
$avgEnergy = [];

foreach ($trend3Data as $entry) {
    $years1[] = $entry->_id;
    $avgDanceability[] = round($entry->avg_danceability, 3);
    $avgValence[] = round($entry->avg_valence, 3);
    $avgEnergy[] = round($entry->avg_energy, 3);
}

echo "<script>
    const years1 = " . json_encode($years1) . ";
    const avgDanceability = " . json_encode($avgDanceability) . ";
    const avgValence = " . json_encode($avgValence) . ";
    const avgEnergy = " . json_encode($avgEnergy) . ";
</script>";

// Fine timing — calcolo durata
$__end_time = microtime(true);
$__execution_time2 = $__end_time - $__start_time;

$__start_time = microtime(true);
$pipeline4 = [
    [
        '$bucket' => [
            'groupBy' => '$bpm',
            'boundaries' => [60, 70, 80, 90, 100, 110, 120, 130, 140, 150, 160, 170, 180, 190, 200],
            'default' => 'Other',
            'output' => [
                'count' => ['$sum' => 1]
            ]
        ]
    ]
];

$command4 = new MongoDB\Driver\Command([
    'aggregate' => 'Spotify2023',
    'pipeline' => $pipeline4,
    'cursor' => new stdClass,
]);

$cursor4 = $manager->executeCommand('admin', $command4);
$trend4Data = $cursor4->toArray();

$bucketLabels = [];
$bucketCounts = [];

foreach ($trend4Data as $entry) {
    if ($entry->_id === 'Other') {
        $bucketLabels[] = '200+';
    } else {
        $from = (int) $entry->_id;
        $to = $from + 9;
        $bucketLabels[] = "$from-$to";
    }

    $bucketCounts[] = $entry->count;
}
echo "<script>
    const bucketLabels = " . json_encode($bucketLabels) . ";
    const bucketCounts = " . json_encode($bucketCounts) . ";
</script>";

$__end_time = microtime(true);
$__execution_time3 = $__end_time - $__start_time;

$__start_time = microtime(true);
$pipeline5 = [
    [
        '$project' => [
            'preferiti.brani.id_brano' => 1
        ]
    ],
    [
        '$unwind' => '$preferiti.brani'
    ],
    [
        '$group' => [
            '_id' => '$preferiti.brani.id_brano',  // raggruppo per id_brano
            'count' => [ '$sum' => 1 ]
        ]
    ],
    [
        '$sort' => [ 'count' => -1 ]
    ],
    [
        '$limit' => 20
    ]
];

$command5 = new MongoDB\Driver\Command([
    'aggregate' => 'User',
    'pipeline' => $pipeline5,
    'cursor' => new stdClass,
]);

$cursor5 = $manager->executeCommand('admin', $command5);

// Array dove salviamo i risultati leggibili
$braniPreferiti = [];

foreach ($cursor5 as $doc) {
    $trackId = $doc->_id;
    $count = $doc->count;

    // Cerca in collezione spotify usando _id
    try {
        $objectId = new ObjectId($trackId);
    } catch (Exception $e) {
        continue; // se il trackId non è un ObjectId valido, salta
    }
    
    $querySpotify = new MongoDB\Driver\Query(['_id' => $objectId]);
    $cursorSpotify = $manager->executeQuery('admin.Spotify', $querySpotify);
    $spotifyResult = current($cursorSpotify->toArray());

    // Se non trovato, cerca in top100
    if (!$spotifyResult) {
        $queryTop100 = new MongoDB\Driver\Query(['_id' => $objectId]);
        $cursorTop100 = $manager->executeQuery('admin.Spotify2023', $queryTop100);
        $spotifyResult = current($cursorTop100->toArray());
    }

    // Aggiungi all’array solo se trovato
    if ($spotifyResult) {
        $brano = [
          'titolo' => $spotifyResult->track_name ?? 'Titolo sconosciuto',
          'artista' => $spotifyResult->artists ?? ($spotifyResult->{'artist(s)_name'} ?? 'Artista sconosciuto'),
          'preferenze' => $count
      ];
      $braniPreferiti[] = $brano;
    }
}

// Fine timing — calcolo durata
$__end_time = microtime(true);
$__execution_time4 = $__end_time - $__start_time;

$__start_time = microtime(true);
$pipelineTop100 = [
    [
        '$project' => [
            'danceability' => ['$divide' => ['$danceability_%', 100]],
            'energy' => ['$divide' => ['$energy_%', 100]],
            'valence' => ['$divide' => ['$valence_%', 100]]
        ]
    ],
    [
        '$group' => [
            '_id' => null,
            'avg_danceability' => ['$avg' => '$danceability'],
            'avg_energy' => ['$avg' => '$energy'],
            'avg_valence' => ['$avg' => '$valence']
        ]
    ]
];
$pipelineSpotify = [
    [
        '$group' => [
            '_id' => null,
            'avg_danceability' => ['$avg' => '$danceability'],
            'avg_energy' => ['$avg' => '$energy'],
            'avg_valence' => ['$avg' => '$valence']
        ]
    ]
];

$commandTop100 = new MongoDB\Driver\Command([
    'aggregate' => 'Spotify2023',
    'pipeline' => $pipelineTop100,
    'cursor' => new stdClass,
]);

$commandSpotify = new MongoDB\Driver\Command([
    'aggregate' => 'Spotify',
    'pipeline' => $pipelineSpotify,
    'cursor' => new stdClass,
]);

$resultTop100 = $manager->executeCommand('admin', $commandTop100);
$resultSpotify = $manager->executeCommand('admin', $commandSpotify);

$mediaTop100 = current($resultTop100->toArray());
$mediaSpotify = current($resultSpotify->toArray());

$confronto = [
    'labels' => ['Danceability', 'Energy', 'Valence'],
    'Top100' => [
        round($mediaTop100->avg_danceability ?? 0, 3),
        round($mediaTop100->avg_energy ?? 0, 3),
        round($mediaTop100->avg_valence ?? 0, 3)
    ],
    'Spotify' => [
        round($mediaSpotify->avg_danceability ?? 0, 3),
        round($mediaSpotify->avg_energy ?? 0, 3),
        round($mediaSpotify->avg_valence ?? 0, 3)
    ]
];
// Invia i dati in formato JSON (per esempio con echo se dentro un endpoint o in una pagina)
echo "<script> const confronto = " . json_encode($confronto) . "; </script>";
// Fine timing — calcolo durata
$__end_time = microtime(true);
$__execution_time5 = $__end_time - $__start_time;

$__start_time = microtime(true);
$pipelineMetascore = [
    [
        '$addFields' => [
            'release_date_parsed' => [
                '$dateFromString' => [
                    'dateString' => '$release_date',
                    'format' => '%B %d, %Y',
                    'onError' => null
                ]
            ]
        ]
    ],
    [
        '$group' => [
            '_id' => ['$year' => '$release_date_parsed'],
            'avg_metascore' => ['$avg' => '$metascore'],
            'count' => ['$sum' => 1] // ⬅️ Conteggio dei brani
        ]
    ],
    ['$sort' => ['_id' => 1]]
];

$commandMetascore = new MongoDB\Driver\Command([
    'aggregate' => 'Spotify',
    'pipeline' => $pipelineMetascore,
    'cursor' => new stdClass,
]);

$cursorMetascore = $manager->executeCommand('admin', $commandMetascore);
$metascoreData = $cursorMetascore->toArray();

$yearsMetascore = [];
$avgMetascore = [];
$songCountsMetascore = [];

foreach ($metascoreData as $entry) {
    $yearsMetascore[] = $entry->_id;
    $avgMetascore[] = round($entry->avg_metascore, 2);
    $songCountsMetascore[] = $entry->count; // ⬅️ Aggiunta del conteggio
}

echo "<script>
    const yearsMetascore = " . json_encode($yearsMetascore) . ";
    const avgMetascore = " . json_encode($avgMetascore) . ";
    const songCountsMetascore = " . json_encode($songCountsMetascore) . ";
</script>";

// Fine timing — calcolo durata
$__end_time = microtime(true);
$__execution_time6 = $__end_time - $__start_time;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <style> 
    .track-rank {
    font-weight: bold;
    margin-right: 8px;
    color:  #D4A017;
}
body::-webkit-scrollbar {
  display: none; /* Chrome, Safari */
}
        html, body {
    margin: 0;
    padding: 0;
    scroll-behavior: smooth; /* Scorrimento fluido */
}

/* NAVBAR FISSA IN ALTO */
.navbar {
  position: fixed;
  top: 0;
  width: 100%;
  background-color: #222;
  z-index: 1000;
  display: flex;
  justify-content: center;
  gap: 10px;
  padding: 10px 0;
}

.navbar a {
  color: white;
  text-decoration: none;
  padding: 8px 14px;
  border-radius: 4px;
  transition: background-color 0.3s;
}

.navbar a:hover {
  background-color: #444;
  color: white;
}
/* Sezioni intere schermo */
.fullpage-section {
  height: 100vh;
  display: flex;
  align-items: center;
  font-size: 3em;
  color: white;
  scroll-snap-align: start;
  padding-top: 60px; /* per non coprire il titolo con la navbar */
  flex-direction: column;      /* Disposizione verticale */
  justify-content: space-between; /* Spazio tra titolo e bottone */
  padding: 80px 20px 40px;     /* Spazio interno con margine per navbar sopra */
  text-align: center;          /* Centra il contenuto di default */
  box-sizing: border-box;
    overflow-y: auto;
}
.fullpage-section::-webkit-scrollbar {
  width: 8px;
}

.fullpage-section::-webkit-scrollbar-thumb {
  background-color: rgba(255,255,255,0.4);
  border-radius: 4px;
}
/* Colori diversi */
#section1 { background-color: #1abc9c; }
#section2 { background-color: #2ecc71; }
#section3 { background-color: #3498db; }
#section4 { background-color: #9b59b6; }
#section5 { background-color: #34495e; }
#section6 { background-color: #f1c40f; color: black; }
#section7 { background-color: #e67e22; }
#section8 { background-color: #e74c3c; }
#section9 { background-color: #95a5a6; }

.back-to-top a {
  display: inline-block;
  padding: 6px 12px;          /* Più piccolo */
  font-size: 14px;            /* Testo ridotto */
  background-color: #333;
  color: white;
  text-decoration: none;
  border-radius: 4px;
  transition: background-color 0.3s ease, transform 0.2s ease;
}

.back-to-top a:hover {
  background-color: #444;
  color: white;
  transform: scale(1.05);     /* Piccola animazione al passaggio */
}
.fullpage-section h1 {
  align-self: flex-start;      /* Per metterlo a sinistra */
  text-align: left;            /* oppure 'center' se vuoi centrato */
  margin: 0;
  padding-bottom: 20px;
  font-size: 60px;
  color: white;
  margin-bottom: 40px;
}

.back-to-top {
  align-self: center;          /* Bottone centrato orizzontalmente */
  margin-top: auto;            /* Lo spinge in fondo alla sezione */
}
.top-artists-list {
  list-style-type: decimal;
  padding-left: 1.5em;
  margin: 0 auto 40px auto;
  max-width: 100%;
  width: 100%;
  box-sizing: border-box;
  color: white;
  font-size: 25px;
  text-align: left;
  overflow-wrap: break-word;
}

.top-artists-list li {
  margin-bottom: 10px;
  display: flex;
  justify-content: space-between;
  gap: 1em;
  flex-wrap: wrap;
  width: 100%;
}

.artist-name {
  font-weight: 600;
  max-width: 70%;
  word-break: break-word;
  flex: 1 1 auto;
}

.track-count {
  font-style: italic;
  color: #ddd;
  flex-shrink: 0;
}

.track-artist {
    font-family: 'Courier New', monospace;
    font-style: normal;
    font-weight: 400;
    margin-left: 0; /* nessuno spazio */
}
</style>
    <!-- basic -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- mobile metas -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="viewport" content="initial-scale=1, maximum-scale=1">
    <!-- site metas -->
    <title>Rock</title>
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
</head>
<!-- body -->

    <!-- loader  -->
    <div class="loader_bg">
        <div class="loader"><img src="images/loading.gif" alt="#" /></div>
    </div>
    <!-- end loader -->
    <!-- header -->
    <header>
        <!-- header inner -->
        <div class="header"id="top">
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
                    </div> -->
                </div>
            </div>
            <!-- end header inner -->
    </header>
    <!-- end header -->

  <!-- Navbar fissa -->
  <div class="navbar">
    <a href="#section1">Top 10 Artists</a>
    <a href="#section2">Top Songs Hits: Yearly Song Trends</a>
    <a href="#section3">Music Mood Shift</a>
    <a href="#section4">Beat Flow</a>
    <a href="#section5">Top 20 Favorites Tracks</a>
    <a href="#section6">Top Songs vs Archive Tracks</a>
    <a href="#section7">Metascore Trends</a>
  </div>

  <!-- Sezioni a schermo intero -->
    <div class="fullpage-section" id="section1">
        <h1>🎤 Top 10 Artists of Top Songs</h1>
        <div class="trend-content">
            <h2 class="trend-description">Ranking based on the total number of songs in the Top Songs database.</h2>
            <ol class="top-artists-list">
                <?php $pos = 1; foreach ($topArtists as $artist): ?>
                    <li>
                        <span class="track-rank"><?php echo $pos++; ?>.</span>
                        <span class="artist-name"><?php echo htmlspecialchars($artist->_id); ?></span>
                        <span class="track-count"><?php echo $artist->total_tracks; ?> brani</span>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
          <h3>
          This chart displays the top 10 artists with the highest number of tracks in the dataset. It highlights the most prolific artists based on the quantity of their musical output, offering insight into which artists dominated the dataset landscape in terms of volume.
          </h3>
        <div class="back-to-top">
            <a href="#top">⬆ Torna su</a>
        </div>
    </div>
    <div class="fullpage-section" id="section2" style="overflow-x:auto;"><h1>Top Songs Hits: the trend of songs by year</h1>
          <h2 class="trend-description">View the distribution of Top Songs songs year by year.</h2>  
          <canvas id="yearChart" ></canvas>
          <!-- Trend 2: Distribution of Tracks by Release Year -->
          <h3>
          This visualization shows the distribution of tracks over the years, providing a historical view of how music releases have evolved. Peaks and dips can indicate trends in production, popularity surges, or even cultural shifts impacting the music industry.
          The data shows a slow and sporadic emergence of the trend from 1930 through the late 1990s, with only one to a few occurrences per year. There are minor fluctuations, with occasional peaks (e.g., in 1958, 1963, 1984, and 2002), but overall the trend remains relatively dormant for decades.

          Starting around the year 2000, a notable increase begins, with a visible upward curve becoming evident after 2010. This growth intensifies between 2011 and 2016, with the number of occurrences climbing into double digits.

          A true explosion happens from 2017 onwards, reaching a sharp peak in 2022, where the count skyrockets to 402, representing a massive surge in activity or popularity. The value remains high in 2023 (175), though showing a slight decrease, possibly indicating a plateau or post-peak stabilization.
          </h3>
        <div class="back-to-top">
            <a href="#top">⬆ Torna su</a>
        </div>
    </div>
    <div class="fullpage-section" id="section3"style="overflow-x:auto;"><h1>"Shifting Rhythms: How Music's Mood Evolved (1999-2023)"</h1>
          <h2 class="trend-description">A year-by-year comparison of Danceability, Energy, and Valence in popular music trends.</h2>    
          <canvas id="yearChartAVG"></canvas>
          <!-- Trend 3: Evolution of Danceability, Valence, and Energy Over Time -->
          <h3>
          This graph explores how average values for danceability, valence (musical positivity), and energy have changed across years. It reveals the emotional and rhythmic trends in music over time, showing whether music has become more upbeat, energetic, or emotionally charged.
            This trend focuses on how key musical characteristics have evolved over the last 25 years. By looking at danceability, valence, and energy, we can uncover how the overall "feel" of popular music has shifted across generations.</br>

            Danceability 
            Danceability remains relatively stable over the entire period, with values ranging between 0.50 and 0.59.

            Starts at 0.571 in 1999, sees a mild dip around 2011–2012 (down to 0.501).

            Gradually increases in the following years, peaking at 0.592 in 2022.

            Slight drop in 2023 (0.558), but still above the historical average.

             Danceability has remained a central and steady element in music production. Despite minor fluctuations, there's a clear return to more danceable tracks in the 2020s, possibly reflecting a cultural desire for rhythm-driven music and social interaction post-2010s.</br>

            Valence
            Valence, which measures the positivity or emotional brightness of a track, shows more pronounced variation.

            Very high in early 2000s, peaking at 0.732 in 2000.

            From 2001 onward, it gradually declines, maintaining moderate levels around 0.66–0.71.

            In the 2020s, we see a sharper drop to 0.510 by 2023, the lowest point in the entire series.

             This could signal a shift toward moodier, darker, or more emotionally complex music. The decline in valence may reflect changing societal moods or a greater appetite for introspection and emotional depth in recent years.</br>

            Energy 
            Energy, reflecting the intensity and activity level of a track, shows a clear downward trajectory:

            Starts high in 1999 (0.622) but quickly declines in the early 2000s.

            Hits its lowest point in 2016 (0.431).

            Slight recovery in the late 2010s and early 2020s, ending at 0.486 in 2023.

             This decline suggests a shift away from high-intensity, aggressive sounds toward more laid-back, chill, or minimalist production styles. However, the mild resurgence post-2018 hints at a possible blending of energy with subtlety, or perhaps a nostalgic revival of older high-energy styles with modern restraint.

                    
                    </h3>
        <div class="back-to-top">
            <a href="#top">⬆ Torna su</a>
        </div>
    </div>
    <div class="fullpage-section" id="section4"><h1>Feel the Beat: How Fast Are the Hits?</h1>
        <h2 class="trend-description">Discover the tempo trends of chart-topping songs — from chill vibes to dancefloor bangers.</h2>    
        <canvas id="bpmChart" width="600" height="400"></canvas>
          <h3>
          This bar chart categorizes tracks based on their BPM (Beats Per Minute), showing how frequently different tempo ranges occur in the dataset. It helps identify whether slow, mid-tempo, or fast-paced songs are more prevalent in the Top Songs catalog.
          The graph illustrates the temporal evolution of a musical index from the top Songs, highlighting fluctuations in its mean, minimum, and maximum values. The data forms a wave-like pattern, reflecting changes in variability and intensity over time. Notably, the range between minimum and maximum values narrows in recent years, indicating increased homogeneity in the dataset.

          Additionally, the BPM distribution reveals that most tracks cluster between 90 and 139 BPM, with fewer compositions at extreme tempos. This emphasizes a focus on mid-tempo music typical of mainstream genres.
                  </h3>
        <div class="back-to-top">
            <a href="#top">⬆ Torna su</a>
        </div>
    </div>
   <div class="fullpage-section" id="section5">
    <h1>🎧 Top 20 Most Liked Tracks by Users</h1>
    <div class="trend-content">
        <h2 class="trend-description">Ranking based on the number of users who have added each track to their favorites.</h2>
        <ol class="top-artists-list">
            <?php $pos = 1; foreach ($braniPreferiti as $brano): ?>
                <li>
                    <span class="track-rank"><?php echo $pos++; ?>.</span>
                    <span class="artist-name"><?php echo htmlspecialchars($brano['titolo']); ?> - <span class="track-artist"><?php echo htmlspecialchars($brano['artista']); ?></span></span>
                    <span class="count"> Favorited by <?php echo (int)$brano['preferenze']; ?> users</span>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
    <h3>
        This list highlights the 20 most favored tracks among users, showing which songs have gained the greatest popularity within the community based on how many users added them to their favorites.
    </h3>
    <div class="back-to-top">
        <a href="#top">⬆ Back to top</a>
    </div>
</div>

    <div class="fullpage-section" id="section6"><h1>Top Songs vs Archive Tracks</h1>
      <h2 class="trend-description">This chart compares the average values of three key audio features — danceability, energy, and valence — between the Top Songs dataset and the broader Archive catalog, providing a quick insight into stylistic and emotional differences.</h2>  
      <canvas id="featureChart"></canvas>
      <h3>
        This analysis compares two distinct music datasets: the Archive catalog, which includes songs released from 1999 to 2023, and the Top Songs dataset, consisting of the most listened tracks primarily from 2023.

        The Top Songs songs show a higher average danceability (67%) compared to the broader Archive dataset (53.1%), reflecting that current hits favor catchy and rhythmically engaging beats that encourage movement.

        Interestingly, the Archive catalog features a slightly higher average energy (68.4%) than the Top Songs (64.3%). This suggests that while recent popular songs focus more on groove and rhythm, the larger pool of music from the past two decades includes many tracks with more intense and dynamic sound profiles.

        Regarding valence—the measure of musical positivity or happiness—the Top Songs tracks score moderately higher (51.4%) than the overall Archive collection (46.9%). This indicates that recent hits tend to have a slightly brighter and more upbeat emotional tone compared to the wider music landscape spanning over 20 years.

        Overall, this comparison highlights how trends in music consumption evolve over time, with recent chart-toppers emphasizing danceability and positive moods, while the broader Archive library offers a more varied spectrum of energy and emotional complexity.
        </h3>
        <div class="back-to-top">
            <a href="#top">⬆ Torna su</a>
        </div>
    </div>
    <div class="fullpage-section" id="section7"><h1>Critical Acclaim Over Time – Average Metascore by Year (1999-2023)</h1> 
      <h2 class="trend-description">This chart shows how the average Metascore of songs released between 2002 and 2022 has evolved over time. By analyzing yearly trends in critical reception, we can observe whether certain musical periods were more critically praised than others.</h2>  
        <canvas id="metascoreChart"></canvas>
        <h3>
          The data spans from 1999 to 2023, showing yearly average metascores and the number of songs released. At the start, the average metascore is relatively high (~80.25 in 1999) but quickly declines to the high 60s and low 70s through the early 2000s. Between 2003 and 2017, the metascore remains fairly stable in the high 60s to mid-70s range, with a slight upward trend from 2015 to around 2022, peaking near 76, before dipping again in 2023 to about 68. This suggests consistent quality with some modest improvement in recent years.

          The number of songs released shows a dramatic increase from just 20 in 1999 to a peak of over 2800 in 2013. This surge corresponds to the rise of digital platforms and easier music distribution. After 2014, song output gradually declines but remains relatively high until 2022, followed by a steep drop to only 83 songs in 2023, likely due to incomplete data or external market factors.

          The early years’ low volume with high metascores likely reflect a smaller, more curated dataset, while the growth phase features a stable average metascore despite massive increases in song counts, indicating that quality remained consistent amid rising quantity. The recent slight metascore increase alongside fewer songs may point to higher-quality or more critically acclaimed releases dominating the data. The sharp decline in 2023’s data suggests caution in interpretation, possibly reflecting data gaps or changing industry conditions.

          Overall, this data reveals an initial phase of limited releases with strong critical reception, a decade-long boom in production volume maintaining stable quality, followed by a recent downturn in output and a small drop in metascore that may be temporary or data-related.
        </h3>
        <div class="back-to-top">
            <a href="#top">⬆ Torna su</a>
        </div>
    </div>
    <!--  footer -->
    <footr>
       
    </footr>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

    <script>
const ctx = document.getElementById('yearChart').getContext('2d');

new Chart(ctx, {
  type: 'bar',
  data: {
    labels: years,
    datasets: [{
      label: 'Brani pubblicati per anno',
      data: counts,
      backgroundColor: 'rgba(255, 255, 255, 0.7)',
      borderColor: 'rgba(255, 255, 255, 1)',
      borderWidth: 1,
      barThickness: 20,        // spessore fisso barre
      maxBarThickness: 30,     // massimo spessore
      minBarLength: 2          // per vedere bene anche valori bassi
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          color: 'white',
          stepSize: 1
        },
        grid: {
          color: 'rgba(255,255,255,0.1)'
        },
        suggestedMax: 420 // -> aumenta un po’ il max per far vedere bene il valore più alto
      },
      x: {
        ticks: {
          color: 'white',
          autoSkip: false,
          maxRotation: 90,
          minRotation: 45,
        },
        grid: {
          color: 'rgba(255,255,255,0.1)'
        }
      }
    },
    plugins: {
      legend: {
        labels: {
          color: 'white'
        }
      },
      datalabels: {
        color: 'white',
        anchor: 'end',
        align: 'top',
        font: {
          weight: 'bold',
          size: 12
        }
      }
    }
  },
  plugins: [ChartDataLabels]
});

const ctx1 = document.getElementById('yearChartAVG').getContext('2d');

new Chart(ctx1, {
  type: 'bar',
  data: {
    labels: years1,
    datasets: [
      {
        label: 'Danceability',
        data: avgDanceability,
        backgroundColor: 'rgba(255, 99, 132, 0.7)',    // rosso chiaro
        borderColor: 'rgba(255, 99, 132, 1)',
        borderWidth: 1,
        maxBarThickness: 25,
        minBarLength: 2
      },
      {
        label: 'Valence',
        data: avgValence,
        backgroundColor: 'rgba(153, 102, 255, 0.7)',    // viola chiaro acceso
        borderColor: 'rgba(153, 102, 255, 1)',
        borderWidth: 1,
        maxBarThickness: 25,
        minBarLength: 2
      },
      {
        label: 'Energy',
        data: avgEnergy,
        backgroundColor: 'rgba(255, 206, 86, 0.7)',    // giallo chiaro
        borderColor: 'rgba(255, 206, 86, 1)',
        borderWidth: 1,
        maxBarThickness: 25,
        minBarLength: 2
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      y: {
        beginAtZero: true,
        suggestedMax: 1,
        ticks: {
          color: 'white',
          callback: function(value) {
            return (value * 100) + '%';
          }
          // stepSize: 0.1 // opzionale
        },
        grid: {
          color: 'rgba(255,255,255,0.1)'
        }
      },
      x: {
        ticks: {
          color: 'white',
          autoSkip: false,
          maxRotation: 90,
          minRotation: 45,
        },
        grid: {
          color: 'rgba(255,255,255,0.1)'
        },
        barPercentage: 1.0,
        categoryPercentage: 0.3
      }
    },
    plugins: {
      legend: {
        labels: {
          color: 'white',
          font: {
            size: 14,
            weight: 'bold'
          }
        }
      },
      datalabels: {
        color: 'white',
        anchor: 'end',
        align: 'top',
        font: {
          weight: 'bold',
          size: 12
        },
        formatter: function(value) {
          return (value * 100).toFixed(1) + '%';
        }
      }
    }
  },
  plugins: [ChartDataLabels]
});


</script>
<script>
const ctx2 = document.getElementById('bpmChart').getContext('2d');

const bpmChart = new Chart(ctx2, {
    type: 'line',
    data: {
        labels: bucketLabels,
        datasets: [{
            label: 'Number of Songs',
            data: bucketCounts,
            fill: true,
            tension: 0.4,
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            borderColor: 'rgba(255, 99, 132, 1)',
            borderWidth: 2,
            pointRadius: 4,
            pointBackgroundColor: 'rgba(255, 99, 132, 1)',
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'BPM Distribution in Top Songs Tracks',
                color: '#fff',
                font: {
                    size: 20,
                    weight: 'bold'
                }
            },
            legend: {
                labels: {
                    color: '#fff'
                }
            },
            tooltip: {
                mode: 'index',
                intersect: false
            }
        },
        scales: {
            x: {
                ticks: {
                    color: '#fff'
                },
                grid: {
                    color: 'rgba(255,255,255,0.1)'
                }
            },
            y: {
                beginAtZero: true,
                ticks: {
                    color: '#fff'
                },
                grid: {
                    color: 'rgba(255,255,255,0.1)'
                }
            }
        }
    }
});
</script>
<script>
  console.log(confronto);
  const ctx3 = document.getElementById('featureChart').getContext('2d');

  new Chart(ctx3, {
    type: 'bar',
    data: {
      labels: confronto.labels,
      datasets: [
        {
          label: 'Top Songs',
          data: confronto.Top100,
          backgroundColor: 'rgba(54, 162, 235, 0.7)',
          borderColor: 'rgba(54, 162, 235, 1)',
          borderWidth: 1
        },
        {
          label: 'Archive',
          data: confronto.Spotify,
          backgroundColor: 'rgba(255, 99, 132, 0.7)',
          borderColor: 'rgba(255, 99, 132, 1)',
          borderWidth: 1
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          max: 1,
          ticks: {
            stepSize: 0.1,
            color: 'white',
            // opzionale: mostrare etichette y in percentuale
            callback: function(value) {
              return (value * 100) + '%';
            }
          },
          grid: {
            color: 'rgba(255,255,255,0.1)'
          }
        },
        x: {
          ticks: {
            color: 'white'
          },
          grid: {
            color: 'rgba(255,255,255,0.1)'
          }
        }
      },
      plugins: {
        legend: {
          labels: {
            color: 'white'
          }
        },
        datalabels: {
          color: 'white',
          anchor: 'end',
          align: 'top',
          font: {
            weight: 'bold',
            size: 12
          },
          formatter: function(value) {
            return (value * 100).toFixed(1) + '%';
          }
        }
      }
    },
    plugins: [ChartDataLabels]
  });
</script>
<script>
const ctxMetascore = document.getElementById('metascoreChart').getContext('2d');

const metascoreChart = new Chart(ctxMetascore, {
    type: 'line',
    data: {
        labels: yearsMetascore, // array di anni (es: [2002, 2003, ...])
        datasets: [{
            label: 'Average Metascore',
            data: avgMetascore, // array dei metascore medi per anno
            fill: true,
            tension: 0.4,
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 2,
            pointRadius: 4,
            pointBackgroundColor: 'rgba(54, 162, 235, 1)'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Average Metascore by Year (2002–2022)',
                color: '#fff',
                font: {
                    size: 20,
                    weight: 'bold'
                }
            },
            legend: {
                labels: {
                    color: '#fff'
                }
            },
            tooltip: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    label: function(context) {
                        const score = context.parsed.y;
                        const yearIndex = context.dataIndex;
                        const count = songCountsMetascore[yearIndex];
                        return `Avg Metascore: ${score} (${count} brani)`;
                    }
                }
            }
        },
        scales: {
            x: {
                ticks: { color: '#fff' },
                grid: { color: 'rgba(255,255,255,0.1)' }
            },
            y: {
                beginAtZero: true,
                ticks: { color: '#fff' },
                grid: { color: 'rgba(255,255,255,0.1)' }
            }
        }
    }
});

</script>
</body>

</html>