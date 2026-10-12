<?php
require 'config.php';
session_start();

$dataFile = __DIR__ . '/data.json';

// Login
if (empty($_SESSION['logged'])) {
    if (isset($_POST['pass']) && $_POST['pass'] === ADMIN_PASS) {
        $_SESSION['logged'] = true;
        $_SESSION['token'] = bin2hex(random_bytes(16));
    } else {
        echo '<form method="post" style="background:#111;color:#ccc;padding:40px;font-family:monospace;max-width:400px;margin:100px auto">
                <h2 style="color:#00ff00">Admin Login</h2>
                <input type="password" name="pass" placeholder="Password" style="width:100%;padding:10px;margin:10px 0;background:#222;border:1px solid #444;color:#fff">
                <button style="padding:10px 20px;background:#00ff00;border:none;cursor:pointer">Login</button>
              </form>';
        exit;
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

$data = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [
    'myservices'=>[], 'markets'=>[], 'forums'=>[], 'services'=>[], 'wikis'=>[], 'sponsors'=>[]
];

// CSRF check semplice
function checkToken() {
    return isset($_POST['token']) && hash_equals($_SESSION['token'] ?? '', $_POST['token']);
}

// Azioni
if ($_SERVER['REQUEST_METHOD'] === 'POST' && checkToken()) {
    $action  = $_POST['action'] ?? '';
    $section = $_POST['section'] ?? '';

    if ($action === 'add' && $section) {
        $item = [
            'name' => trim($_POST['name'] ?? ''),
            'url'  => trim($_POST['url'] ?? ''),
            'desc' => trim($_POST['desc'] ?? '')
        ];

        // Solo per sponsor: scadenza + banner
        if ($section === 'sponsors') {
            $days = (int)($_POST['days'] ?? 7);
            $item['expire'] = time() + ($days * 86400);
            $item['banner'] = '';

            if (!empty($_FILES['banner']['name']) && $_FILES['banner']['error'] === 0) {
                $ext = strtolower(pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','gif','webp']) && $_FILES['banner']['size'] <= MAX_BANNER_SIZE) {
                    $fname = bin2hex(random_bytes(8)) . '.' . $ext;
                    if (move_uploaded_file($_FILES['banner']['tmp_name'], UPLOAD_DIR . $fname)) {
                        $item['banner'] = $fname;
                    }
                }
            }
        }

        $data[$section][] = $item;
    }

    if ($action === 'delete' && $section !== '') {
        $idx = (int)$_POST['idx'];
        if (isset($data[$section][$idx])) {
            // cancella banner se esiste
            if ($section === 'sponsors' && !empty($data[$section][$idx]['banner'])) {
                @unlink(UPLOAD_DIR . $data[$section][$idx]['banner']);
            }
            array_splice($data[$section], $idx, 1);
        }
    }

    if ($action === 'backup') {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="hiddenwiki-backup-'.date('Y-m-d').'.json"');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    header('Location: admin.php');
    exit;
}

$sections = [
    'myservices' => 'My Services',
    'sponsors'   => 'Sponsors (in alto + banner + scadenza)',
    'markets'    => 'Markets',
    'forums'     => 'Forums',
    'services'   => 'Services & Tools',
    'wikis'      => 'Other Wikis / Mirrors'
];
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin – <?= WIKI_TITLE ?></title>
<style>
  body{background:#0d0d0d;color:#ccc;font-family:monospace;padding:20px;max-width:1100px;margin:0 auto}
  h1,h2{color:#00ff00}
  table{width:100%;border-collapse:collapse;margin:15px 0;font-size:14px}
  th,td{border:1px solid #333;padding:8px;text-align:left;vertical-align:top}
  th{background:#1a1a1a}
  input,select,button{background:#222;border:1px solid #444;color:#fff;padding:7px;margin:3px}
  button{background:#00ff00;color:#000;border:none;cursor:pointer}
  .del{background:#ff3333;color:#fff}
  .expired{color:#ff6666}
  a{color:#00ccff}
  img.thumb{max-height:40px;border:1px solid #444}
</style>
</head>
<body>
<h1>Admin Panel</h1>
<p>
  <a href="index.php" target="_blank">Vedi la wiki</a> |
  <a href="?logout=1">Logout</a> |
  <form method="post" style="display:inline">
    <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
    <input type="hidden" name="action" value="backup">
    <button type="submit">Download Backup JSON</button>
  </form>
</p>

<h2>Aggiungi nuovo elemento</h2>
<form method="post" enctype="multipart/form-data">
  <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
  <input type="hidden" name="action" value="add">
  <select name="section" id="sec" onchange="toggleSponsor()">
    <?php foreach ($sections as $k=>$v) echo "<option value=\"$k\">$v</option>"; ?>
  </select><br>
  <input type="text" name="name" placeholder="Nome" required style="width:320px"><br>
  <input type="text" name="url" placeholder="http://xxxxx.onion" required style="width:420px"><br>
  <input type="text" name="desc" placeholder="Descrizione (opzionale)" style="width:420px"><br>

  <div id="sponsorFields" style="display:none; margin-top:10px; padding:10px; background:#1a1a1a">
    <label>Durata (giorni):</label>
    <input type="number" name="days" value="7" min="1" max="365" style="width:80px"><br>
    <label>Banner (jpg/png/gif/webp – max 512KB):</label>
    <input type="file" name="banner" accept="image/*">
  </div>

  <button type="submit">Aggiungi</button>
</form>

<script>
function toggleSponsor(){
  document.getElementById('sponsorFields').style.display =
    document.getElementById('sec').value === 'sponsors' ? 'block' : 'none';
}
toggleSponsor();
</script>

<?php foreach ($sections as $key => $label): ?>
  <h2><?= $label ?></h2>
  <table>
    <tr>
      <th>#</th><th>Nome</th><th>URL</th><th>Desc</th>
      <?php if ($key === 'sponsors'): ?><th>Scade</th><th>Banner</th><?php endif; ?>
      <th>Azione</th>
    </tr>
    <?php foreach ($data[$key] as $i => $item): ?>
      <tr>
        <td><?= $i ?></td>
        <td><?= htmlspecialchars($item['name']) ?></td>
        <td style="word-break:break-all"><?= htmlspecialchars($item['url']) ?></td>
        <td><?= htmlspecialchars($item['desc'] ?? '') ?></td>
        <?php if ($key === 'sponsors'): ?>
          <td class="<?= (!empty($item['expire']) && $item['expire'] < time()) ? 'expired' : '' ?>">
            <?= !empty($item['expire']) ? date('Y-m-d H:i', $item['expire']) : '∞' ?>
          </td>
          <td>
            <?php if (!empty($item['banner'])): ?>
              <img class="thumb" src="uploads/<?= htmlspecialchars($item['banner']) ?>">
            <?php else: ?>—<?php endif; ?>
          </td>
        <?php endif; ?>
        <td>
          <form method="post" style="display:inline" onsubmit="return confirm('Eliminare?')">
            <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="section" value="<?= $key ?>">
            <input type="hidden" name="idx" value="<?= $i ?>">
            <button type="submit" class="del">Elimina</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endforeach; ?>

</body>
</html>
