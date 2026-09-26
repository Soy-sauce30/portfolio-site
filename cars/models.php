<?php
/* =========================================
   3D model search (Sketchfab) — no key needed.
   GET ?make=Mazda&model=MX-5
   Returns up to 4 embeddable models whose names
   actually mention the model, most-liked first.
   ========================================= */

header('Content-Type: application/json');
header('Cache-Control: public, max-age=3600');

$make  = trim((string)($_GET['make'] ?? ''));
$model = trim((string)($_GET['model'] ?? ''));
if ($model === '' || mb_strlen($make . $model) > 80) {
  http_response_code(400);
  echo json_encode(['error' => 'Missing car name.']);
  exit;
}

$tmp = sys_get_temp_dir() . '/sawyer-cars';
if (!is_dir($tmp)) mkdir($tmp, 0700, true);

$cacheFile = "$tmp/models-" . sha1(strtolower("$make|$model")) . '.json';
if (is_file($cacheFile) && filemtime($cacheFile) > time() - 7 * 86400) {
  echo file_get_contents($cacheFile);
  exit;
}

function norm($s) {
  return preg_replace('/[^a-z0-9]/', '', strtolower($s));
}

$url = 'https://api.sketchfab.com/v3/search?' . http_build_query([
  'type' => 'models',
  'categories' => 'cars-vehicles',
  'sort_by' => '-likeCount',
  'count' => 24,
  'q' => "$make $model",
]);
$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT => 10,
  CURLOPT_USERAGENT => 'sawyerabrahani.com car finder',
]);
$raw = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = $status === 200 ? json_decode($raw, true) : null;
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
  ];
}

// Prefer the plain road car: race, police and cartoon versions go after it (unless that's what was searched).
$odd = '/\b(nascar|gt3|gt4|gt300|rally|drift|police|cop|taxi|lowpoly|low poly|stylized|cartoon|toy|lego|animation|custom)\b/i';
if (!preg_match($odd, $model)) {
  usort($models, fn($a, $b) => preg_match($odd, $a['name']) <=> preg_match($odd, $b['name']));
}
$models = array_slice($models, 0, 4);

$out = json_encode(['models' => $models]);
file_put_contents($cacheFile, $out, LOCK_EX);
echo $out;
