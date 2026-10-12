<?php
require 'config.php';
$dataFile = __DIR__ . '/data.json';
$data = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [
    'myservices'=>[], 'markets'=>[], 'forums'=>[], 'services'=>[], 'wikis'=>[], 'sponsors'=>[]
];

// Rimuovi sponsor scaduti
$now = time();
$data['sponsors'] = array_values(array_filter($data['sponsors'], function($s) use ($now) {
    return empty($s['expire']) || $s['expire'] > $now;
}));
file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));

function renderSection($title, $items) {
    if (empty($items)) return;
    echo "<h2>★ $title</h2><ul>";
    foreach ($items as $item) {
        $url  = htmlspecialchars($item['url']);
        $name = htmlspecialchars($item['name']);
        $desc = htmlspecialchars($item['desc'] ?? '');
        echo "<li><a href=\"$url\" target=\"_blank\">$name</a>";
        if ($desc) echo "<span class=\"desc\">$desc</span>";
        echo "</li>";
    }
    echo "</ul>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= WIKI_TITLE ?></title>
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{background:#0d0d0d;color:#c0c0c0;font-family:"Courier New",monospace;line-height:1.6;padding:20px;max-width:900px;margin:0 auto}
  h1{color:#00ff00;text-align:center;margin-bottom:10px;font-size:2em;text-shadow:0 0 8px #00ff00}
  .subtitle{text-align:center;color:#888;margin-bottom:30px;font-size:.9em}
  h2{color:#ff6600;border-bottom:1px solid #333;padding-bottom:5px;margin:30px 0 15px}
  ul{list-style:none}
  li{margin:8px 0;padding:8px 12px;background:#1a1a1a;border-left:3px solid #00ff00}
  a{color:#00ccff;text-decoration:none}
  a:hover{color:#00ff00;text-decoration:underline}
  .desc{color:#888;font-size:.85em;display:block;margin-top:3px}
  .sponsor{background:#1a0f00;border:1px solid #ff6600;padding:15px;margin:20px 0;text-align:center}
  .sponsor h3{color:#ff6600;margin-bottom:10px}
  .sponsor img{max-width:100%;max-height:80px;margin:8px 0;border:1px solid #333}
  .footer{margin-top:50px;text-align:center;color:#555;font-size:.8em;border-top:1px solid #222;padding-top:15px}
  .onion{color:#00ff00}
</style>
</head>
<body>

<h1>♦ <?= WIKI_TITLE ?> ♦</h1>
<p class="subtitle">Updated regularly • Mirror friendly • No bullshit</p>

<?php if (!empty($data['sponsors'])): ?>
<div class="sponsor">
  <h3>★ SPONSORED ★</h3>
  <?php foreach ($data['sponsors'] as $s): ?>
    <div style="margin:12px 0">
      <?php if (!empty($s['banner'])): ?>
        <a href="<?= htmlspecialchars($s['url']) ?>" target="_blank">
          <img src="uploads/<?= htmlspecialchars($s['banner']) ?>" alt="banner">
        </a><br>
      <?php endif; ?>
      <a href="<?= htmlspecialchars($s['url']) ?>" target="_blank"><?= htmlspecialchars($s['name']) ?></a>
      <?php if (!empty($s['desc'])): ?> — <?= htmlspecialchars($s['desc']) ?><?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
renderSection('My Services', $data['myservices']);
renderSection('Markets', $data['markets']);
renderSection('Forums', $data['forums']);
renderSection('Services & Tools', $data['services']);
renderSection('Other Wikis / Mirrors', $data['wikis']);
?>

<h2>★ Support / Donations</h2>
<p style="background:#111;padding:12px;border-left:3px solid #ff6600">
  XMR: <span class="onion"><?= XMR_ADDR ?></span><br>
  BTC: <span class="onion"><?= BTC_ADDR ?></span>
</p>

<div class="footer">
  <?= WIKI_TITLE ?> • Keep this page bookmarked • New mirrors will be posted here<br>
  Last update: <?= date('Y-m-d H:i') ?>
</div>
</body>
</html>
