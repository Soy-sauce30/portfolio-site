<?php
/* =========================================
   Free car lookup — no API key needed.
   POST JSON: { "query": "2024 Mazda MX-5" }

   Specs come from the EPA (fueleconomy.gov), the
   description and photo from Wikipedia. Returns the
   same shape as api.php so the page renders either.
   ========================================= */

header('Content-Type: application/json');
header('Cache-Control: no-store');

function fail($status, $message) {
  http_response_code($status);
  echo json_encode(['error' => $message]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail(405, 'POST only.');

$input = json_decode(file_get_contents('php://input'), true);
$query = trim(preg_replace('/\s+/', ' ', (string)($input['query'] ?? '')));
if ($query === '') fail(400, 'Type a car to look up.');
if (mb_strlen($query) > 120) fail(400, 'That search is a bit long — try just the make and model.');

$tmp = sys_get_temp_dir() . '/sawyer-cars';
if (!is_dir($tmp)) mkdir($tmp, 0700, true);

$cacheFile = "$tmp/free-" . sha1(strtolower($query)) . '.json';
if (is_file($cacheFile) && filemtime($cacheFile) > time() - 7 * 86400) {
  echo file_get_contents($cacheFile);
  exit;
}

// Be polite to the free APIs: 60 uncached lookups per IP per hour.
$limitFile = "$tmp/rate-free-" . sha1($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '.json';
$hits = is_file($limitFile) ? (json_decode(file_get_contents($limitFile), true) ?: []) : [];
$hits = array_values(array_filter($hits, fn($t) => $t > time() - 3600));
if (count($hits) >= 60) fail(429, 'Too many lookups — try again in a bit.');
$hits[] = time();
file_put_contents($limitFile, json_encode($hits), LOCK_EX);

/* ---------- HTTP helpers ---------- */

function get_json($url, $ttl = 86400) {
  global $tmp;
  $file = "$tmp/http-" . sha1($url) . '.json';
  if (is_file($file) && filemtime($file) > time() - $ttl) return json_decode(file_get_contents($file), true);

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 12,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
    CURLOPT_USERAGENT => 'sawyerabrahani.com car finder (sawyerabrahani@gmail.com)',
  ]);
  $raw = curl_exec($ch);
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($raw === false || $status !== 200) return null;
  $data = json_decode($raw, true);
  if ($data !== null) file_put_contents($file, $raw, LOCK_EX);
  return $data;
}

// fueleconomy.gov menus return an object instead of a list when there's only one item.
function menu($path) {
  $data = get_json('https://www.fueleconomy.gov/ws/rest/vehicle/menu/' . $path);
  $items = $data['menuItem'] ?? [];
  if (isset($items['value'])) $items = [$items];
  return $items;
}

function norm($s) {
  return preg_replace('/[^a-z0-9]/', '', strtolower($s));
}

/* ---------- 1. Work out year, make and model ---------- */

$MAKE_ALIASES = [
  'chevy' => 'Chevrolet', 'vw' => 'Volkswagen', 'merc' => 'Mercedes-Benz', 'mercedes' => 'Mercedes-Benz',
  'benz' => 'Mercedes-Benz', 'landrover' => 'Land Rover', 'rangerover' => 'Land Rover', 'alfa' => 'Alfa Romeo',
  'caddy' => 'Cadillac', 'bimmer' => 'BMW', 'mini' => 'MINI',
];

$currentYear = (int)date('Y');
$year = null;
if (preg_match('/\b(19[89]\d|20\d\d)\b/', $query, $m)) $year = (int)$m[1];
$text = trim(preg_replace('/\b(19[89]\d|20\d\d)\b/', '', $query));

// Find a make at the start of the text, e.g. "Mazda Miata" or "chevy corvette".
function find_make($text, $makes, $aliases) {
  $n = norm($text);
  $best = null;
  foreach ($makes as $make) {
    $k = norm($make);
    if ($k !== '' && str_starts_with($n, $k) && (!$best || strlen($k) > strlen(norm($best)))) $best = $make;
  }
  if (!$best) {
    foreach ($aliases as $alias => $make) {
      if (str_starts_with($n, $alias) && in_array($make, $makes, true)) return [$make, substr($n, strlen($alias))];
    }
    return [null, $n];
  }
  return [$best, substr($n, strlen(norm($best)))];
}

// Score how well an EPA model name ("MX-5", "GR Supra", "Camry HEV FF LE") matches what was typed.
function score_model($model, $rest) {
  if ($rest === '') return 0;
  $words = array_values(array_filter(array_map('norm', preg_split('/[\s\/]+/', $model))));
  $matched = 0; $missed = 0;
  foreach ($words as $w) {
    // Single letters ("Model Y", "Type R") only count once a real word has matched.
    if (str_contains($rest, $w) && (strlen($w) > 1 || $matched > 0)) $matched += strlen($w);
    else $missed++;
  }
  // Needs to explain most of what was typed, e.g. "supra" or "civictyper".
  if ($matched < 2 || $matched < strlen($rest) * 0.5) return 0;
  return $matched * 10 - $missed * 3 + (norm($model) === $rest ? 50 : 0);
}

function find_vehicle($text, $year, $aliases, $currentYear) {
  $years = $year ? [$year] : range($currentYear + 1, $currentYear - 3);
  foreach ($years as $y) {
    $makes = array_column(menu("make?year=$y"), 'value');
    if (!$makes) continue;
    [$make, $rest] = find_make($text, $makes, $aliases);
    if (!$make) continue;
    $best = null; $bestScore = 0;
    foreach (array_column(menu('model?year=' . $y . '&make=' . rawurlencode($make)), 'value') as $model) {
      $s = score_model($model, $rest);
      if ($s > $bestScore) { $best = $model; $bestScore = $s; }
    }
    if ($best) return ['year' => $y, 'make' => $make, 'model' => $best, 'rest' => $rest];
  }
  return null;
}

function wiki_search($q) {
  $data = get_json('https://en.wikipedia.org/w/api.php?action=query&list=search&srlimit=1&format=json&srsearch=' . rawurlencode($q), 7 * 86400);
  return $data['query']['search'][0]['title'] ?? null;
}

$found = find_vehicle($text, $year, $MAKE_ALIASES, $currentYear);
$wikiTitle = null;

// No match (e.g. just "Miata" or "Model Y")? Let Wikipedia tell us the full name and try again.
if (!$found) {
  $wikiTitle = wiki_search("$text car");
  if ($wikiTitle) $found = find_vehicle(preg_replace('/\s*\(.*\)$/', '', $wikiTitle), $year, $MAKE_ALIASES, $currentYear);
}

if (!$found) {
  $result = ['identified' => false, 'note' => "Couldn't find \"$query\" in the EPA database. Try the make and model, like \"Honda Civic\" — it covers cars sold in the US since 1984."];
  echo json_encode($result);
  exit;
}

/* ---------- 2. Specs from the EPA ---------- */

$y = $found['year']; $make = $found['make']; $model = $found['model'];
$options = menu('options?year=' . $y . '&make=' . rawurlencode($make) . '&model=' . rawurlencode($model));

$versions = [];
foreach (array_slice($options, 0, 6) as $opt) {
  $v = get_json('https://www.fueleconomy.gov/ws/rest/vehicle/' . rawurlencode($opt['value']), 30 * 86400);
  if ($v) $versions[] = $v;
}
if (!$versions) fail(502, 'The EPA database is not responding right now. Try again in a minute.');

$v = $versions[0];
$isEV = (float)$v['combE'] > 0 && (int)$v['cylinders'] === 0;

function engine($v) {
  if (!empty($v['evMotor']) && (int)$v['cylinders'] === 0) return 'Electric — ' . $v['evMotor'];
  $e = ($v['cylinders'] ? $v['cylinders'] . '-cyl ' : '') . ($v['displ'] ? $v['displ'] . 'L' : '');
  if ($v['tCharger'] === 'T') $e .= ' turbo';
  if ($v['sCharger'] === 'S') $e .= ' supercharged';
  if (!empty($v['atvType']) && stripos($v['atvType'], 'hybrid') !== false) $e .= ' hybrid';
  return trim($e);
}

function mpg($v) {
  if ((float)$v['combE'] > 0 && (int)$v['cylinders'] === 0) return $v['comb08'] . ' MPGe';
  return $v['city08'] . ' city / ' . $v['highway08'] . ' hwy / ' . $v['comb08'] . ' combined';
}

$specs = [
  ['label' => 'Engine', 'value' => engine($v)],
  ['label' => 'Transmission', 'value' => $v['trany']],
  ['label' => 'Drivetrain', 'value' => $v['drive']],
  ['label' => $isEV ? 'Efficiency' : 'Fuel economy (mpg)', 'value' => mpg($v)],
];
if ((float)$v['range'] > 0) $specs[] = ['label' => 'EPA range', 'value' => $v['range'] . ' miles'];
$specs[] = ['label' => 'Fuel', 'value' => $v['fuelType1']];
$specs[] = ['label' => 'Class', 'value' => $v['VClass']];
$specs[] = ['label' => 'Yearly fuel cost', 'value' => '$' . number_format((int)$v['fuelCost08']) . ' (EPA est.)'];
if ((int)$v['feScore'] > 0) $specs[] = ['label' => 'EPA fuel score', 'value' => $v['feScore'] . ' / 10'];
if ((int)$v['co2TailpipeGpm'] >= 0 && !$isEV) $specs[] = ['label' => 'CO₂', 'value' => round((float)$v['co2TailpipeGpm']) . ' g/mile'];

$versionRows = array_map(fn($x) => [
  'engine' => engine($x),
  'transmission' => $x['trany'],
  'mpg' => mpg($x),
], $versions);

$class = strtolower($v['VClass']);
$body = str_contains($class, 'pickup') ? 'truck'
  : (str_contains($class, 'sport utility') ? 'suv'
  : (str_contains($class, 'two seater') || str_contains($class, 'minicompact') ? 'sports'
  : (str_contains($class, 'wagon') ? 'wagon'
  : (str_contains($class, 'van') ? 'van' : 'sedan'))));

/* ---------- 3. Description and photo from Wikipedia ---------- */

// EPA's base model is sometimes too broad ("M" for an M3, "GR" for a Supra).
$baseModel = $v['baseModel'] ?: '';
if ($baseModel === '' || strlen(norm($baseModel)) < 2 || !str_contains($found['rest'], norm($baseModel))) {
  // Keep the name up to the last word that was typed: "GR Supra", "M3 Competition M xDrive" -> "M3".
  $words = preg_split('/\s+/', $model);
  $keep = 1;
  foreach ($words as $i => $w) if (strlen(norm($w)) > 1 && str_contains($found['rest'], norm($w))) $keep = $i + 1;
  $baseModel = implode(' ', array_slice($words, 0, $keep));
}
$wikiTitle = $wikiTitle ?: wiki_search("$make $baseModel");
$summary = ''; $image = ''; $wikiUrl = '';
if ($wikiTitle) {
  $page = get_json('https://en.wikipedia.org/api/rest_v1/page/summary/' . rawurlencode(str_replace(' ', '_', $wikiTitle)), 7 * 86400);
  if ($page && ($page['type'] ?? '') === 'standard') {
    $summary = $page['extract'] ?? '';
    $image = $page['thumbnail']['source'] ?? '';
    $wikiUrl = $page['content_urls']['desktop']['page'] ?? '';
  }
}

/* ---------- 4. Prices: our approximate MSRP list, plus links for exact prices ---------- */

$prices = json_decode(file_get_contents(__DIR__ . '/data/prices.json'), true);
// Most specific first: this exact version ("Tesla|Model 3 Performance AWD"), then the
// model ("BMW|M3"), then EPA's broader group ("BMW|M").
$priceMake = $make === 'Mini' ? 'MINI' : $make;
$variantPrices = json_decode(file_get_contents(__DIR__ . '/data/variant-prices.json'), true);
$version = trim(preg_replace('/\s*\((?:[^)]*\bwheels?\b[^)]*|\d+\s*in[^)]*)\)/i', '', $model));
$disc = json_decode(file_get_contents(__DIR__ . '/data/discontinued.json'), true);
$noLongerSold = in_array("$priceMake|$version", $disc['versions'] ?? [], true)
  || in_array("$priceMake|" . ($v['baseModel'] ?: $baseModel), $disc['models'] ?? [], true);
$priceKey = null;
if (array_key_exists("$priceMake|$version", $variantPrices)) {
  // This exact version: its own price, or none at all if the maker doesn't publish one (null).
  if ($variantPrices["$priceMake|$version"]) { $prices["$priceMake|$version"] = $variantPrices["$priceMake|$version"]; $priceKey = "$priceMake|$version"; }
} else {
  foreach (["$priceMake|$baseModel", "$priceMake|" . ($v['baseModel'] ?: $baseModel)] as $k) {
    if (isset($prices[$k])) { $priceKey = $k; break; }
  }
}
$priceNew = '';
if ($priceKey && !$noLongerSold && $y >= (int)date('Y') - 1) { // only for versions still sold new
  [$lo, $hi] = $prices[$priceKey];
  $priceNew = $lo === $hi ? '$' . number_format($lo) : '$' . number_format($lo) . ' – $' . number_format($hi);
}

$name = "$y $make $baseModel";
$links = [
  ['label' => 'New price & reviews', 'url' => 'https://www.google.com/search?q=' . rawurlencode("$name MSRP price")],
  ['label' => 'Used for sale near you', 'url' => 'https://www.google.com/search?q=' . rawurlencode("used $make $baseModel for sale")],
];
if ($wikiUrl) $links[] = ['label' => 'Wikipedia', 'url' => $wikiUrl];

$result = [
  'identified' => true,
  'confidence' => 'high',
  'make' => $make,
  'model' => $baseModel,
  'years' => (string)$y,
  'trim' => $version !== $baseModel ? $version : '',
  'body_style' => $body,
  'summary' => $summary,
  'price_new' => $priceNew,
  'price_used' => '',
  'specs' => $specs,
  'pros' => [],
  'cons' => [],
  'rivals' => [],
  'image' => $image,
  'versions' => count($versionRows) > 1 ? $versionRows : [],
  'links' => $links,
  'note' => 'Specs: U.S. EPA fueleconomy.gov. Description and photo: Wikipedia.',
];

$out = json_encode($result);
file_put_contents($cacheFile, $out, LOCK_EX);
echo $out;
