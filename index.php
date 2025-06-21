<?php
$filename = 'scrobbles.jsonl';
$lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$monthlyScrobbles = [];
$monthlyArtistCounts = [];

foreach ($lines as $line) {
    $data = json_decode($line, true);
    if (!$data || !isset($data['artist'], $data['track'], $data['timeHuman'])) continue;

    $rawDate = $data['timeHuman'];
    $normalized = iconv('UTF-8', 'ASCII//TRANSLIT', $rawDate);
    $normalized = str_replace('at', '', $normalized);
    $normalized = preg_replace('/\s+/', ' ', $normalized);
    $normalized = trim($normalized);

    $timestamp = strtotime($normalized);
    if (!$timestamp) continue;

    $monthKey = date('Y-m', $timestamp);
    $monthName = date('F Y', $timestamp);
    $artist = $data['artist'];
    $track = $data['track'];
    $key = "$artist - $track";

    $monthlyScrobbles[$monthKey]['name'] = $monthName;
    if (!isset($monthlyScrobbles[$monthKey]['tracks'][$key])) {
        $monthlyScrobbles[$monthKey]['tracks'][$key] = 0;
    }
    $monthlyScrobbles[$monthKey]['tracks'][$key]++;

    if (!isset($monthlyArtistCounts[$monthKey]['name'])) {
        $monthlyArtistCounts[$monthKey]['name'] = $monthName;
    }
    if (!isset($monthlyArtistCounts[$monthKey]['artists'][$artist])) {
        $monthlyArtistCounts[$monthKey]['artists'][$artist] = 0;
    }
    $monthlyArtistCounts[$monthKey]['artists'][$artist]++;
}

krsort($monthlyScrobbles);
krsort($monthlyArtistCounts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>scrobbler</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;700&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            font-family: 'Montserrat', sans-serif;
            background-color: #1e1e1e;
            color: #f5f5f5;
        }
        img {
            opacity: 50%;
        }
        .container {
            display: flex;
            flex-direction: row;
            min-height: 100vh;
        }
        .sidebar {
            background-color: #121212;
            padding: 1.5em;
            width: 300px;
            border-right: 2px solid #2a2a2a;
        }
        .content {
            flex: 1;
            padding: 2em;
        }
        h1 {
            color: #00e676;
            font-size: 4em;
			letter-spacing: -4px;
        }
        h2 {
            color: #00e676;
            margin-top: 1em;
            border-bottom: 2px solid #00e676;
            padding-bottom: 0.2em;
            font-size: 1em;
        }
        h3 {
            font-size: 0.9em;
            margin-top: 1.5em;
            color: #f5f5f5;
        }
        ol {
            padding-left: 1.5em;
        }
        li {
            margin-bottom: 0.3em;
            padding: 0.2em 0.4em;
            background-color: #2c2c2c;
            border-radius: 3px;
            font-size: 0.9em;
        }
        li a {
            color: #81d4fa;
            text-decoration: none;
            font-weight: bold;
        }
        li a:hover {
            text-decoration: underline;
        }
        .artist-highlight {
            color: #ffc107;
            font-weight: bold;
        }
		.show-more {
			font-family: 'Montserrat', sans-serif;
			font-weight: bold;
			background: #00e676;
			color: #000;
			border: none;
			padding: 0.5em 1em;
			cursor: pointer;
			border-radius: 3px;
		}
        .show-more:hover {
			background: #00c853;
		}
        /* Mobile Styles */
        @media screen and (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 2px solid #2a2a2a;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h2>Top 3 Artists»</h2>
            <?php foreach ($monthlyArtistCounts as $month => $data): ?>
                <h3><?= htmlspecialchars($data['name']) ?></h3>
                <?php arsort($data['artists']); ?>
                <ol>
                    <?php foreach (array_slice($data['artists'], 0, 3) as $artist => $count): ?>
                        <li><span class="artist-highlight"><?= htmlspecialchars($artist) ?></span> <small>x<?= $count ?></small></li>
                    <?php endforeach; ?>
                </ol>
            <?php endforeach; ?>
        </div>
        <div class="content">
            <center><h1>»Scrobbler</h1></center>
            <?php foreach ($monthlyScrobbles as $month => $data): ?>
                <?php arsort($data['tracks']); ?>
                <h2><?= htmlspecialchars($data['name']) ?>»</h2>
                <ol id="list-<?= $month ?>">
                    <?php
                        $index = 0;
                        foreach ($data['tracks'] as $entry => $count):
                            [$artist, $track] = explode(' - ', $entry, 2);
                            $query = urlencode("$artist $track");
                            $youtubeUrl = "https://www.youtube.com/results?search_query=$query";
                            $hidden = $index >= 10 ? 'style="display:none;" class="extra-'. $month . '"' : '';
                    ?>
                        <li <?= $hidden ?>>
                            <a href="<?= $youtubeUrl ?>" target="_blank" rel="noopener">
                                <?= htmlspecialchars($entry) ?>
                            </a> <small>x<?= $count ?></small>
                        </li>
                    <?php $index++; endforeach; ?>
                </ol>
				<?php if ($index > 10): ?>
					<center><button class="show-more" onclick="toggleTracks('<?= $month ?>', this)">Show More</button></center>
				<?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
<script>
    function toggleTracks(month, btn) {
        const items = document.querySelectorAll('.extra-' + month);
        const isExpanded = btn.textContent === 'Show Less';
        
        items.forEach(item => {
            item.style.display = isExpanded ? 'none' : 'list-item';
        });

        btn.textContent = isExpanded ? 'Show More' : 'Show Less';
    }
</script>
</body>
</html>
