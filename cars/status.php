<?php
/* =========================================
   Live status for the HUD theme's panels.
   GET → { sources: [...], photoId: bool, catalog: {...} }
   Every value is real: each data source is actually
   pinged, and the counts come from the catalog files.
   Cached for 5 minutes so page views don't hammer the APIs.
   ========================================= */

header('Content-Type: application/json');
header('Cache-Control: public, max-age=60');

$tmp = sys_get_temp_dir() . '/sawyer-cars';
if (!is_dir($tmp)) mkdir($tmp, 0700, true);
$cacheFile = "$tmp/status.json";
if (is_file($cacheFile) && filemtime($cacheFile) > time() - 300) {
  echo file_get_contents($cacheFile);
  exit;
}

// The same sources the site uses for lookups, each with a tiny request.
$checks = [
  ['id' => 'epa',       'name' => 'EPA car database',   'url' => 'https://www.fueleconomy.gov/ws/rest/vehicle/menu/year'],
  ['id' => 'wikipedia', 'name' => 'Wikipedia',          'url' => 'https://en.wikipedia.org/api/rest_v1/page/summary/Car'],
  ['id' => 'sketchfab', 'name' => 'Sketchfab 3D models', 'url' => 'https://api.sketchfab.com/v3/search?type=models&count=1&q=car'],
];

$multi = curl_multi_init();
$handles = [];
foreach ($checks as $i => $c) {
  $ch = curl_init($c['url']);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
    CURLOPT_USERAGENT => 'sawyerabrahani.com car finder (status check)',
  ]);
  curl_multi_add_handle($multi, $ch);
  $handles[$i] = $ch;
}
do {
  $status = curl_multi_exec($multi, $running);
  if ($running) curl_multi_select($multi, 0.5);
} while ($running && $status === CURLM_OK);

$sources = [];
foreach ($checks as $i => $c) {
  $ch = $handles[$i];
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $sources[] = [
    'id' => $c['id'],
    'name' => $c['name'],
    'ok' => $code >= 200 && $code < 400,
    'ms' => (int)round(curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000),
  ];
  curl_multi_remove_handle($multi, $ch);
  curl_close($ch);
}
curl_multi_close($multi);

// Photo ID only works when an Anthropic API key is installed (see api.php).
$keyFile = dirname(__DIR__, 2) . '/sawyerabrahani-secrets/anthropic_key';
$photoId = (bool)getenv('ANTHROPIC_API_KEY') || is_readable($keyFile);

$catalog = null;
$data = json_decode(@file_get_contents(__DIR__ . '/data/catalog.json'), true);
if ($data) {
  $brands = [];
  $priced = 0;
  foreach ($data['cars'] as $c) {
    $brands[$c['make']] = 1;
    if (isset($c['price']) || isset($c['modelPrice'])) $priced++;
  }
  $catalog = [
    'built' => $data['built'],
    'onSale' => $data['totalCurrent'],
    'total' => $data['total'],
    'brands' => count($brands),
    'priced' => $priced,
  ];
}

$out = json_encode(['checked' => gmdate('c'), 'sources' => $sources, 'photoId' => $photoId, 'catalog' => $catalog]);
file_put_contents($cacheFile, $out, LOCK_EX);
echo $out;
