<?php


/* ------------------------------------------------------------------
   Database (SQLite file created automatically next to this script)
------------------------------------------------------------------- */
$db = new PDO('sqlite:' . __DIR__ . '/calico.sqlite', null, null, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$db->exec('PRAGMA foreign_keys = ON');

$db->exec('
CREATE TABLE IF NOT EXISTS countries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE
);
CREATE TABLE IF NOT EXISTS temperaments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    description TEXT
);
CREATE TABLE IF NOT EXISTS breed_groups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    description TEXT
);
CREATE TABLE IF NOT EXISTS breeds (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    country_id INTEGER REFERENCES countries(id) ON DELETE SET NULL,
    temperament_id INTEGER REFERENCES temperaments(id) ON DELETE SET NULL,
    group_id INTEGER REFERENCES breed_groups(id) ON DELETE SET NULL
);');

/* First run: add some sample data */
if ((int) $db->query('SELECT COUNT(*) FROM countries')->fetchColumn() === 0) {
    $ids = ['countries' => [], 'temperaments' => [], 'breed_groups' => []];

    foreach (['Egypt', 'Iran', 'Japan', 'Norway', 'Russia', 'Thailand', 'Türkiye', 'United Kingdom', 'United States'] as $n) {
        $db->prepare('INSERT INTO countries (name) VALUES (?)')->execute([$n]);
        $ids['countries'][$n] = (int) $db->lastInsertId();
    }
    foreach ([
        'Affectionate' => 'Loves laps and attention.',
        'Curious'      => 'Investigates everything.',
        'Gentle'       => 'Calm and soft-natured.',
        'Independent'  => 'Happy to do its own thing.',
        'Playful'      => 'Always up for a game.',
        'Vocal'        => 'Likes to talk back.',
    ] as $n => $d) {
        $db->prepare('INSERT INTO temperaments (name, description) VALUES (?, ?)')->execute([$n, $d]);
        $ids['temperaments'][$n] = (int) $db->lastInsertId();
    }
    foreach ([
        'Longhair'      => 'Long, flowing coat.',
        'Semi-longhair' => 'Medium coat with a fluffy tail.',
        'Shorthair'     => 'Short, dense coat.',
        'Oriental'      => 'Slender body, large ears.',
    ] as $n => $d) {
        $db->prepare('INSERT INTO breed_groups (name, description) VALUES (?, ?)')->execute([$n, $d]);
        $ids['breed_groups'][$n] = (int) $db->lastInsertId();
    }
    $seed = [
        ['Siamese',              'Thailand',       'Vocal',        'Oriental'],
        ['Maine Coon',           'United States',  'Gentle',       'Longhair'],
        ['British Shorthair',    'United Kingdom', 'Independent',  'Shorthair'],
        ['Persian',              'Iran',           'Gentle',       'Longhair'],
        ['Japanese Bobtail',     'Japan',          'Playful',      'Shorthair'],
        ['Norwegian Forest Cat', 'Norway',         'Independent',  'Semi-longhair'],
        ['Egyptian Mau',         'Egypt',          'Curious',      'Shorthair'],
        ['Russian Blue',         'Russia',         'Gentle',       'Shorthair'],
        ['Turkish Van',          'Türkiye',        'Playful',      'Semi-longhair'],
    ];
    $ins = $db->prepare('INSERT INTO breeds (name, country_id, temperament_id, group_id) VALUES (?,?,?,?)');
    foreach ($seed as [$b, $c, $t, $g]) {
        $ins->execute([$b, $ids['countries'][$c], $ids['temperaments'][$t], $ids['breed_groups'][$g]]);
    }
}

/* ------------------------------------------------------------------
   Entity configuration (everything the CRUD needs lives here;
   table and column names only ever come from this whitelist)
------------------------------------------------------------------- */
$entities = [
    'breeds' => [
        'label'    => 'Breeds',
        'singular' => 'breed',
        'table'    => 'breeds',
        'fields'   => [
            'name'           => ['label' => 'Name',        'type' => 'text', 'required' => true],
            'country_id'     => ['label' => 'Country',     'type' => 'select', 'options' => 'countries'],
            'temperament_id' => ['label' => 'Temperament', 'type' => 'select', 'options' => 'temperaments'],
            'group_id'       => ['label' => 'Group',       'type' => 'select', 'options' => 'breed_groups'],
        ],
        'sql' => 'SELECT b.id, b.name, c.name AS country, t.name AS temperament, g.name AS grp
                  FROM breeds b
                  LEFT JOIN countries c ON c.id = b.country_id
                  LEFT JOIN temperaments t ON t.id = b.temperament_id
                  LEFT JOIN breed_groups g ON g.id = b.group_id
                  ORDER BY b.name',
        'columns'  => ['name' => 'Breed', 'country' => 'Country', 'temperament' => 'Temperament', 'grp' => 'Group'],
        'on_delete' => '',
    ],
    'countries' => [
        'label'    => 'Countries',
        'singular' => 'country',
        'table'    => 'countries',
        'fields'   => [
            'name' => ['label' => 'Name', 'type' => 'text', 'required' => true],
        ],
        'sql' => 'SELECT c.id, c.name, (SELECT COUNT(*) FROM breeds WHERE country_id = c.id) AS breeds
                  FROM countries c ORDER BY c.name',
        'columns'  => ['name' => 'Country', 'breeds' => 'Breeds'],
        'on_delete' => 'Breeds from this country will be left without a country.',
    ],
    'temperaments' => [
        'label'    => 'Temperaments',
        'singular' => 'temperament',
        'table'    => 'temperaments',
        'fields'   => [
            'name'        => ['label' => 'Name',        'type' => 'text', 'required' => true],
            'description' => ['label' => 'Description', 'type' => 'textarea'],
        ],
        'sql' => 'SELECT t.id, t.name, t.description, (SELECT COUNT(*) FROM breeds WHERE temperament_id = t.id) AS breeds
                  FROM temperaments t ORDER BY t.name',
        'columns'  => ['name' => 'Temperament', 'description' => 'Description', 'breeds' => 'Breeds'],
        'on_delete' => 'Breeds with this temperament will be left without one.',
    ],
    'groups' => [
        'label'    => 'Groups',
        'singular' => 'group',
        'table'    => 'breed_groups',
        'fields'   => [
            'name'        => ['label' => 'Name',        'type' => 'text', 'required' => true],
            'description' => ['label' => 'Description', 'type' => 'textarea'],
        ],
        'sql' => 'SELECT g.id, g.name, g.description, (SELECT COUNT(*) FROM breeds WHERE group_id = g.id) AS breeds
                  FROM breed_groups g ORDER BY g.name',
        'columns'  => ['name' => 'Group', 'description' => 'Description', 'breeds' => 'Breeds'],
        'on_delete' => 'Breeds in this group will be left without a group.',
    ],
];

/* ------------------------------------------------------------------
   Helpers
------------------------------------------------------------------- */
function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function cell(string $col, $val): string
{
    if ($val === null || $val === '') {
        return '<span class="muted">&mdash;</span>';
    }
    if ($col === 'temperament' || $col === 'grp') {
        return '<span class="chip chip-' . (abs(crc32((string) $val)) % 3) . '">' . h((string) $val) . '</span>';
    }
    if ($col === 'breeds') {
        return '<span class="count">' . (int) $val . '</span>';
    }
    return h((string) $val);
}

/* Cartoon calico cat: face */
function catFace(string $mood = 'happy', string $class = ''): string
{
    static $n = 0;
    $id = 'cf' . (++$n);
    $ink = '#2B2623';

    $eyes = match ($mood) {
        'sleepy' => '<path d="M38 66 q8 7 16 0 M66 66 q8 7 16 0" fill="none" stroke="' . $ink . '" stroke-width="3.5" stroke-linecap="round"/>',
        'wink'   => '<path d="M38 66 q8 7 16 0" fill="none" stroke="' . $ink . '" stroke-width="3.5" stroke-linecap="round"/>
                     <ellipse cx="74" cy="65" rx="6" ry="7.5" fill="' . $ink . '"/><circle cx="76" cy="62" r="2.2" fill="#fff"/>',
        default  => '<ellipse cx="46" cy="65" rx="6" ry="7.5" fill="' . $ink . '"/><circle cx="48" cy="62" r="2.2" fill="#fff"/>
                     <ellipse cx="74" cy="65" rx="6" ry="7.5" fill="' . $ink . '"/><circle cx="76" cy="62" r="2.2" fill="#fff"/>',
    };

    return <<<SVG
<svg class="cat cat-face {$class}" viewBox="0 0 120 110" role="img" aria-label="Cartoon calico cat face">
  <defs><clipPath id="{$id}"><ellipse cx="60" cy="64" rx="46" ry="40"/></clipPath></defs>
  <polygon points="20,50 20,8 54,30" fill="{$ink}" stroke="{$ink}" stroke-width="4" stroke-linejoin="round"/>
  <polygon points="100,50 100,8 66,30" fill="#E8913A" stroke="{$ink}" stroke-width="4" stroke-linejoin="round"/>
  <polygon points="26,38 27,19 42,30" fill="#F4A6A0"/>
  <polygon points="94,38 93,19 78,30" fill="#F4A6A0"/>
  <ellipse cx="60" cy="64" rx="46" ry="40" fill="#FFF8EE"/>
  <g clip-path="url(#{$id})">
    <circle cx="26" cy="44" r="26" fill="#E8913A"/>
    <ellipse cx="98" cy="34" rx="22" ry="20" fill="{$ink}"/>
    <circle cx="62" cy="20" r="9" fill="#E8913A"/>
  </g>
  <ellipse cx="60" cy="64" rx="46" ry="40" fill="none" stroke="{$ink}" stroke-width="4"/>
  {$eyes}
  <ellipse cx="34" cy="78" rx="6" ry="4" fill="#F4A6A0" opacity=".8"/>
  <ellipse cx="86" cy="78" rx="6" ry="4" fill="#F4A6A0" opacity=".8"/>
  <polygon points="54,73 66,73 60,80" fill="#F4A6A0" stroke="{$ink}" stroke-width="2" stroke-linejoin="round"/>
  <path d="M60 80 q-5 7 -12 3 M60 80 q5 7 12 3" fill="none" stroke="{$ink}" stroke-width="2.5" stroke-linecap="round"/>
  <path d="M30 76 l-22 -4 M30 82 l-22 4 M90 76 l22 -4 M90 82 l22 4" stroke="{$ink}" stroke-width="2" stroke-linecap="round"/>
</svg>
SVG;
}

/* Cartoon calico cat: sitting, with swaying tail */
function catSitting(): string
{
    static $n = 0;
    $id = 'cs' . (++$n);
    $ink = '#2B2623';

    return <<<SVG
<svg class="cat cat-sitting" viewBox="0 0 220 230" role="img" aria-label="Cartoon calico cat sitting">
  <defs>
    <clipPath id="{$id}b"><ellipse cx="100" cy="160" rx="54" ry="60"/></clipPath>
    <clipPath id="{$id}h"><ellipse cx="100" cy="84" rx="50" ry="44"/></clipPath>
  </defs>
  <g class="tail">
    <path d="M148 205 C205 210 214 150 184 132" fill="none" stroke="{$ink}" stroke-width="22" stroke-linecap="round"/>
    <path d="M148 205 C205 210 214 150 184 132" fill="none" stroke="#E8913A" stroke-width="14" stroke-linecap="round"/>
    <path d="M196 156 C198 144 192 138 184 132" fill="none" stroke="{$ink}" stroke-width="14" stroke-linecap="round"/>
  </g>
  <ellipse cx="100" cy="160" rx="54" ry="60" fill="#FFF8EE"/>
  <g clip-path="url(#{$id}b)">
    <ellipse cx="58" cy="140" rx="30" ry="34" fill="#E8913A"/>
    <ellipse cx="146" cy="190" rx="30" ry="28" fill="{$ink}"/>
    <ellipse cx="104" cy="214" rx="22" ry="14" fill="#E8913A"/>
  </g>
  <ellipse cx="100" cy="160" rx="54" ry="60" fill="none" stroke="{$ink}" stroke-width="4.5"/>
  <ellipse cx="78" cy="216" rx="17" ry="10" fill="#FFF8EE" stroke="{$ink}" stroke-width="4"/>
  <ellipse cx="122" cy="216" rx="17" ry="10" fill="#FFF8EE" stroke="{$ink}" stroke-width="4"/>
  <polygon points="56,58 56,14 92,38" fill="{$ink}" stroke="{$ink}" stroke-width="4.5" stroke-linejoin="round"/>
  <polygon points="144,58 144,14 108,38" fill="#E8913A" stroke="{$ink}" stroke-width="4.5" stroke-linejoin="round"/>
  <polygon points="63,45 63,26 79,37" fill="#F4A6A0"/>
  <polygon points="137,45 137,26 121,37" fill="#F4A6A0"/>
  <ellipse cx="100" cy="84" rx="50" ry="44" fill="#FFF8EE"/>
  <g clip-path="url(#{$id}h)">
    <circle cx="64" cy="62" r="28" fill="#E8913A"/>
    <ellipse cx="142" cy="52" rx="24" ry="22" fill="{$ink}"/>
    <circle cx="104" cy="38" r="9" fill="#E8913A"/>
  </g>
  <ellipse cx="100" cy="84" rx="50" ry="44" fill="none" stroke="{$ink}" stroke-width="4.5"/>
  <ellipse cx="80" cy="88" rx="7" ry="9" fill="{$ink}"/><circle cx="82.5" cy="84.5" r="2.6" fill="#fff"/>
  <ellipse cx="120" cy="88" rx="7" ry="9" fill="{$ink}"/><circle cx="122.5" cy="84.5" r="2.6" fill="#fff"/>
  <ellipse cx="68" cy="102" rx="7" ry="4.5" fill="#F4A6A0" opacity=".85"/>
  <ellipse cx="132" cy="102" rx="7" ry="4.5" fill="#F4A6A0" opacity=".85"/>
  <polygon points="93,97 107,97 100,105" fill="#F4A6A0" stroke="{$ink}" stroke-width="2.5" stroke-linejoin="round"/>
  <path d="M100 105 q-6 8 -14 3 M100 105 q6 8 14 3" fill="none" stroke="{$ink}" stroke-width="3" stroke-linecap="round"/>
  <path d="M64 98 l-26 -5 M64 104 l-26 5 M136 98 l26 -5 M136 104 l26 5" stroke="{$ink}" stroke-width="2.4" stroke-linecap="round"/>
</svg>
SVG;
}

/* ------------------------------------------------------------------
   Request handling
------------------------------------------------------------------- */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

$page = $_GET['page'] ?? $_POST['page'] ?? 'breeds';
if (!isset($entities[$page])) {
    $page = 'breeds';
}
$ent    = $entities[$page];
$errors = [];
$old    = null;
$editId = (int) ($_GET['edit'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Invalid form token. Please go back and try again.');
    }

    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);

    try {
        if ($action === 'delete' && $id > 0) {
            $db->prepare("DELETE FROM {$ent['table']} WHERE id = ?")->execute([$id]);
            $_SESSION['flash'] = ['ok', 'Deleted that ' . $ent['singular'] . '.'];
            header('Location: ?page=' . $page);
            exit;
        }

        if ($action === 'save') {
            $values = [];
            foreach ($ent['fields'] as $col => $f) {
                $v = trim((string) ($_POST[$col] ?? ''));
                if (!empty($f['required']) && $v === '') {
                    $errors[] = $f['label'] . ' is required.';
                }
                if ($f['type'] === 'select') {
                    $v = $v === '' ? null : (int) $v;
                } elseif ($v === '' && $f['type'] === 'textarea') {
                    $v = null;
                }
                $values[$col] = $v;
            }

            if (!$errors) {
                $cols = array_keys($values);
                if ($id > 0) {
                    $set = implode(', ', array_map(fn($c) => "$c = ?", $cols));
                    $db->prepare("UPDATE {$ent['table']} SET $set WHERE id = ?")
                       ->execute([...array_values($values), $id]);
                    $_SESSION['flash'] = ['ok', 'Saved changes to ' . $values['name'] . '.'];
                } else {
                    $ph = implode(', ', array_fill(0, count($cols), '?'));
                    $db->prepare("INSERT INTO {$ent['table']} (" . implode(', ', $cols) . ") VALUES ($ph)")
                       ->execute(array_values($values));
                    $_SESSION['flash'] = ['ok', 'Added ' . $values['name'] . '.'];
                }
                header('Location: ?page=' . $page);
                exit;
            }
            $old    = $_POST;
            $editId = $id;
        }
    } catch (PDOException $e) {
        $errors[] = str_contains($e->getMessage(), 'UNIQUE')
            ? 'That name already exists. Choose a different one.'
            : 'Something went wrong while saving. Please try again.';
        $old    = $_POST;
        $editId = (int) ($_POST['id'] ?? 0);
    }
}

/* Data for the page */
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$rows = $db->query($ent['sql'])->fetchAll();

$editRow = null;
if ($editId > 0) {
    $st = $db->prepare("SELECT * FROM {$ent['table']} WHERE id = ?");
    $st->execute([$editId]);
    $editRow = $st->fetch() ?: null;
    if (!$editRow) {
        $editId = 0;
    }
}
$values = $old ?? $editRow ?? [];

$options = [];
foreach ($ent['fields'] as $f) {
    if ($f['type'] === 'select') {
        $options[$f['options']] = $db->query("SELECT id, name FROM {$f['options']} ORDER BY name")->fetchAll();
    }
}

$counts = [];
foreach ($entities as $key => $e) {
    $counts[$key] = (int) $db->query("SELECT COUNT(*) FROM {$e['table']}")->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($ent['label']) ?> · Calico Breed Book</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="patch p1" aria-hidden="true"></div>
<div class="patch p2" aria-hidden="true"></div>
<div class="patch p3" aria-hidden="true"></div>
<div class="patch p4" aria-hidden="true"></div>

<div class="wrap">

  <header class="masthead">
    <div class="masthead-text">
      <h1>The Calico Breed Book</h1>
      <p>Keep track of cat breeds, where they come from, how they behave and which group they belong to.</p>
    </div>
    <?= catSitting() ?>
  </header>

  <nav class="tabs" aria-label="Sections">
    <?php foreach ($entities as $key => $e): ?>
      <a href="?page=<?= h($key) ?>" class="tab<?= $key === $page ? ' is-active' : '' ?>"
         <?= $key === $page ? 'aria-current="page"' : '' ?>>
        <?= h($e['label']) ?> <span class="tab-count"><?= $counts[$key] ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <?php if ($flash): ?>
    <div class="notice notice-ok" role="status"><?= catFace('wink', 'notice-cat') ?><span><?= h($flash[1]) ?></span></div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="notice notice-err" role="alert">
      <?= catFace('sleepy', 'notice-cat') ?>
      <span><?= implode('<br>', array_map('h', $errors)) ?></span>
    </div>
  <?php endif; ?>

  <main class="layout">

    <!-- Create / edit form -->
    <section class="panel form-panel" aria-labelledby="form-title">
      <h2 id="form-title"><?= $editId ? 'Edit ' . h($ent['singular']) : 'Add a ' . h($ent['singular']) ?></h2>
      <form method="post" action="?page=<?= h($page) ?>">
        <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
        <input type="hidden" name="page" value="<?= h($page) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= $editId ?>">

        <?php foreach ($ent['fields'] as $col => $f): ?>
          <div class="field">
            <label for="f-<?= h($col) ?>"><?= h($f['label']) ?><?= !empty($f['required']) ? ' *' : '' ?></label>
            <?php if ($f['type'] === 'select'): ?>
              <select id="f-<?= h($col) ?>" name="<?= h($col) ?>">
                <option value="">Not set</option>
                <?php foreach ($options[$f['options']] as $o): ?>
                  <option value="<?= (int) $o['id'] ?>" <?= (string) ($values[$col] ?? '') === (string) $o['id'] ? 'selected' : '' ?>>
                    <?= h($o['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            <?php elseif ($f['type'] === 'textarea'): ?>
              <textarea id="f-<?= h($col) ?>" name="<?= h($col) ?>" rows="3"><?= h((string) ($values[$col] ?? '')) ?></textarea>
            <?php else: ?>
              <input id="f-<?= h($col) ?>" type="text" name="<?= h($col) ?>" maxlength="80"
                     value="<?= h((string) ($values[$col] ?? '')) ?>" <?= !empty($f['required']) ? 'required' : '' ?>>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary"><?= $editId ? 'Save changes' : 'Add ' . h($ent['singular']) ?></button>
          <?php if ($editId): ?>
            <a class="btn btn-ghost" href="?page=<?= h($page) ?>">Cancel</a>
          <?php endif; ?>
        </div>
      </form>
      <?= catFace('happy', 'form-cat') ?>
    </section>

    <!-- List -->
    <section class="panel list-panel" aria-labelledby="list-title">
      <h2 id="list-title"><?= h($ent['label']) ?></h2>

      <?php if (!$rows): ?>
        <div class="empty">
          <?= catFace('sleepy', 'empty-cat') ?>
          <p>No <?= h(strtolower($ent['label'])) ?> yet. Add the first one using the form.</p>
        </div>
      <?php else: ?>
        <div class="table-scroll">
          <table>
            <thead>
              <tr>
                <?php foreach ($ent['columns'] as $label): ?><th scope="col"><?= h($label) ?></th><?php endforeach; ?>
                <th scope="col" class="col-actions"><span class="sr-only">Actions</span></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr<?= (int) $r['id'] === $editId ? ' class="is-editing"' : '' ?>>
                  <?php foreach ($ent['columns'] as $col => $label): ?>
                    <td data-label="<?= h($label) ?>"><?= cell($col, $r[$col] ?? null) ?></td>
                  <?php endforeach; ?>
                  <td class="col-actions">
                    <a class="btn btn-small" href="?page=<?= h($page) ?>&amp;edit=<?= (int) $r['id'] ?>">Edit</a>
                    <form method="post" action="?page=<?= h($page) ?>" class="inline"
                          data-msg="<?= h('Delete ' . $r['name'] . '? ' . $ent['on_delete']) ?>"
                          onsubmit="return confirm(this.dataset.msg)">
                      <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                      <input type="hidden" name="page" value="<?= h($page) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                      <button type="submit" class="btn btn-small btn-danger">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

  </main>
</div>
</body>
</html>
