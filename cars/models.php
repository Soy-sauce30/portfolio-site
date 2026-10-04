<?php
/* =========================================
   3D model search (Sketchfab) — no key needed.
   GET ?make=Porsche&model=911[&version=911 GT3&year=2026]
   Returns up to 4 embeddable models whose names actually
   mention the model. Models matching the exact version
   (e.g. "GT3") and a nearby year come first, so a 2026
   GT3 doesn't show a 1975 Turbo.
   ========================================= */

header('Content-Type: application/json');
header('Cache-Control: public, max-age=3600');

$make  = trim((string)($_GET['make'] ?? ''));
$model = trim((string)($_GET['model'] ?? ''));
$version = trim((string)($_GET['version'] ?? ''));
$year = (int)($_GET['year'] ?? 0);
if ($model === '' || mb_strlen($make . $model . $version) > 120) {
  http_response_code(400);
  echo json_encode(['error' => 'Missing car name.']);
  exit;
}

$tmp = sys_get_temp_dir() . '/sawyer-cars';
if (!is_dir($tmp)) mkdir($tmp, 0700, true);

$cacheFile = "$tmp/models-" . sha1(strtolower("$make|$model|$version|$year")) . '.json';
if (is_file($cacheFile) && filemtime($cacheFile) > time() - 7 * 86400) {
  echo file_get_contents($cacheFile);
  exit;
}

function norm($s) {
  return preg_replace('/[^a-z0-9]/', '', strtolower($s));
}

// Words in the version that actually distinguish it ("GT3", "Raptor", "Type R"), not drivetrains or trim codes.
$STOP = ['awd','fwd','rwd','4wd','2wd','4x4','4x2','hev','phev','ev','hybrid','plugin','le','se','xle','xse','sr','sr5','base','sport',
         'limited','premium','plus','pickup','cab','chassis','2dr','3dr','4dr','5dr','sedan','coupe','convertible','cabriolet','hatchback',
         'wagon','dr','ff','long','range','standard','edition','trim','auto','manual','4motion','quattro','xdrive','sportback'];
$modelWords = array_map('norm', preg_split('/[\s\-\/]+/', $model));
$distinct = [];
foreach (preg_split('/[\s\-\/()]+/', $version) as $w) {
  $n = norm($w);
  if (strlen($n) >= 2 && !in_array($n, $STOP, true) && !in_array($n, $modelWords, true)) $distinct[] = $n;
}

function sketchfab_search($q) {
  $url = 'https://api.sketchfab.com/v3/search?' . http_build_query([
    'type' => 'models', 'categories' => 'cars-vehicles', 'sort_by' => '-likeCount', 'count' => 24, 'q' => $q,
  ]);
  $ch = curl_init($url);
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_USERAGENT => 'sawyerabrahani.com car finder']);
  $raw = curl_exec($ch);
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  return $status === 200 ? json_decode($raw, true) : null;
}

// Search the exact version first (if it has distinguishing words), then the model in general.
$data = null;
$results = [];
foreach (array_unique(array_filter([$distinct ? "$make $model " . implode(' ', $distinct) : '', "$make $model"])) as $q) {
  $r = sketchfab_search($q);
  if (!$r) continue;
  $data = $r;
  foreach ($r['results'] ?? [] as $m) $results[$m['uid']] = $results[$m['uid']] ?? $m;
}
if ($data) $data['results'] = array_values($results);

if (!$data) {
  http_response_code(502);
  echo json_encode(['error' => '3D models are unavailable right now.']);
  exit;
}

// The full model name must be in the title ("911", "grsupra", "modely"). Skip packs, wrecks and single parts.
$needle = norm($model);
$models = [];
foreach ($data['results'] ?? [] as $m) {
  if (!empty($m['isAgeRestricted'])) continue;
  if (!str_contains(norm($m['name']), $needle) || preg_match('/\b(pack|models|kit|wreck\w*|tented|covered|damaged|destroyed|crash\w*|burnt|burned|rust\w*|abandoned|broken|junk\w*|scrap\w*|chassis|interior|engine|wheel|seat)\b/i', $m['name'])) continue;

  $thumbs = $m['thumbnails']['images'] ?? [];
  usort($thumbs, fn($a, $b) => $a['width'] <=> $b['width']);
  $thumb = '';
  foreach ($thumbs as $t) if ($t['width'] >= 400) { $thumb = $t['url']; break; }

  $models[] = [
    'uid' => $m['uid'],
    'name' => $m['name'],
    'author' => $m['user']['displayName'] ?? $m['user']['username'] ?? 'Unknown',
    'license' => is_array($m['license'] ?? null) ? ($m['license']['label'] ?? '') : '',
    'thumb' => $thumb,
    '_score' => model_score($m['name'], $distinct, $year),
  ];
}

// Rank: exact version words and a nearby model year first; race, police and cartoon
// versions last (unless that's what was searched). Ties keep the most-liked order.
function model_score($name, $distinct, $year) {
  $title = norm($name);
  $score = 0;
  foreach ($distinct as $w) if (str_contains($title, $w)) $score += 3;
  if ($year && preg_match_all('/\b(19[5-9]\d|20[0-3]\d)\b/', $name, $m)) {
    $gap = min(array_map(fn($y) => abs($y - $year), $m[1]));
    if ($gap <= 5) $score += 2; elseif ($gap > 12) $score -= 2;
  }
  return $score;
}
$odd = '/\b(nascar|gt3|gt4|gt300|rally|drift|police|cop|taxi|lowpoly|low poly|stylized|cartoon|toy|lego|animation|custom)\b/i';
$searchedOdd = preg_match($odd, "$model $version");
foreach ($models as $i => &$m) {
  if (!$searchedOdd && preg_match($odd, $m['name'])) $m['_score'] -= 1;
  $m['_i'] = $i;
}
unset($m);
usort($models, fn($a, $b) => [$b['_score'], $a['_i']] <=> [$a['_score'], $b['_i']]);
$models = array_map(function ($m) { unset($m['_score'], $m['_i']); return $m; }, array_slice($models, 0, 4));

$out = json_encode(['models' => $models]);
file_put_contents($cacheFile, $out, LOCK_EX);
echo $out;
