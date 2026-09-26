<?php
/* =========================================
   Builds the All Cars catalog from the EPA fuel economy
   database (every US-market car, 1984–today). Every
   version is its own entry — "Model 3 Performance AWD",
   "911 GT3", "F150 RAPTOR 4WD" — not just the model.

   Writes:
     data/catalog.json      versions on sale now (loads first)
     data/catalog-all.json  every version since 1984

   Prices: data/variant-prices.json (per version) first,
   then data/prices.json (per model range).

   Run from the command line:  php cars/build-catalog.php
   (Re-run every few months to pick up new models.)
   ========================================= */

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$dataDir = __DIR__ . '/data';
$zipPath = sys_get_temp_dir() . '/epa-vehicles.csv.zip';

echo "Downloading EPA vehicle data...\n";
$ch = curl_init('https://www.fueleconomy.gov/feg/epadata/vehicles.csv.zip');
$fp = fopen($zipPath, 'w');
curl_setopt_array($ch, [CURLOPT_FILE => $fp, CURLOPT_TIMEOUT => 120, CURLOPT_FOLLOWLOCATION => true]);
$ok = curl_exec($ch) && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
curl_close($ch);
fclose($fp);
if (!$ok) exit("Download failed.\n");

$zip = new ZipArchive();
if ($zip->open($zipPath) !== true) exit("Could not open zip.\n");
$csv = fopen('php://temp', 'w+');
fwrite($csv, $zip->getFromName('vehicles.csv'));
$zip->close();
rewind($csv);

function load_prices($file) {
  $p = json_decode(file_get_contents($file), true);
  unset($p['_note']);
  return $p;
}
$modelPrices = load_prices("$dataDir/prices.json");
$variantPrices = load_prices("$dataDir/variant-prices.json");

$MAKE_FIX = ['Mini' => 'MINI'];

function body_type($vclass) {
  $c = strtolower($vclass);
  if (str_contains($c, 'pickup')) return 'truck';
  if (str_contains($c, 'sport utility') || str_contains($c, 'special purpose')) return 'suv';
  if (str_contains($c, 'van')) return 'van';
  if (str_contains($c, 'two seater')) return 'sports';
  if (str_contains($c, 'wagon')) return 'wagon';
  return 'car';
}

function fuel_kind($row) {
  switch ($row['atvType']) {
    case 'EV': return 'ev';
    case 'Plug-in Hybrid': return 'phev';
    case 'Hybrid': return 'hybrid';
    case 'Diesel': return 'diesel';
    case 'FCV': return 'hydrogen';
  }
  return 'gas';
}

function engine($row) {
  if ($row['atvType'] === 'EV') return 'Electric';
  if ($row['atvType'] === 'FCV') return 'Hydrogen fuel cell';
  $e = ($row['cylinders'] ? $row['cylinders'] . '-cyl ' : '') . ($row['displ'] ? $row['displ'] . 'L' : '');
  if ($row['tCharger'] === 'T') $e .= ' turbo';
  if ($row['sCharger'] === 'S') $e .= ' supercharged';
  if ($row['atvType'] === 'Hybrid') $e .= ' hybrid';
  if ($row['atvType'] === 'Plug-in Hybrid') $e .= ' plug-in hybrid';
  if ($row['atvType'] === 'Diesel') $e .= ' diesel';
  return trim($e);
}

function drive($d) {
  $d = strtolower($d);
  if (str_contains($d, 'front')) return 'FWD';
  if (str_contains($d, 'rear')) return 'RWD';
  if (str_contains($d, 'all-wheel') || str_contains($d, 'all wheel')) return 'AWD';
  if (str_contains($d, '4-wheel') || str_contains($d, '4wd')) return '4WD';
  if (str_contains($d, '2-wheel')) return '2WD';
  return '';
}

// "Model S Plaid (21in wheels)" and "Model S Plaid (19in wheels)" are the same car.
function clean_name($model) {
  $m = preg_replace('/\s*\((?:[^)]*\bwheels?\b[^)]*|\d+\s*in[^)]*)\)/i', '', $model);
  return trim(preg_replace('/\s+/', ' ', $m));
}

$head = fgetcsv($csv);
$rows = [];
while (($r = fgetcsv($csv)) !== false) {
  $row = array_combine($head, $r);
  $row['make'] = $MAKE_FIX[$row['make']] ?? $row['make'];
  $row['base'] = trim($row['baseModel']) ?: trim($row['model']);
  $row['name'] = clean_name($row['model']);
  $row['year'] = (int)$row['year'];
  $rows[] = $row;
}

// Pass 1: year span per version
$v = [];
foreach ($rows as $row) {
  $key = $row['make'] . '|' . $row['name'];
  $v[$key]['from'] = min($v[$key]['from'] ?? 9999, $row['year']);
  $v[$key]['to'] = max($v[$key]['to'] ?? 0, $row['year']);
}

// Pass 2: details from the version's newest model year
foreach ($rows as $row) {
  $key = $row['make'] . '|' . $row['name'];
  $c = &$v[$key];
  if ($row['year'] !== $c['to']) { unset($c); continue; }
  $fuel = fuel_kind($row);
  $c['make'] = $row['make'];
  $c['base'] = $row['base'];
  $c['name'] = $row['name'];
  $c['types'][body_type($row['VClass'])] = 1;
  $c['fuels'][$fuel] = 1;
  $c['engines'][engine($row)] = 1;
  if ($d = drive($row['drive'])) $c['drives'][$d] = 1;
  $mpg = (int)$row['comb08'];
  if ($mpg > 0) {
    $k = $fuel === 'ev' ? 'mpge' : 'mpg';
    $c[$k] = isset($c[$k]) ? [min($c[$k][0], $mpg), max($c[$k][1], $mpg)] : [$mpg, $mpg];
  }
  if ($fuel === 'ev' && (int)$row['range'] > ($c['range'] ?? 0)) $c['range'] = (int)$row['range'];
  unset($c);
}

$currentYear = (int)date('Y') - 1; // sold as a current or next model year
$all = [];
$usedVariantPrices = [];
foreach ($v as $key => $c) {
  $engines = array_keys($c['engines']);
  $e = [
    'make' => $c['make'],
    'base' => $c['base'],
    'name' => $c['name'],
    'years' => [$c['from'], $c['to']],
    'types' => array_keys($c['types']),
    'fuels' => array_keys($c['fuels']),
    'engine' => count($engines) > 2 ? $engines[0] . ' + ' . (count($engines) - 1) . ' more' : implode(' / ', $engines),
    'drive' => implode('/', array_keys($c['drives'] ?? [])),
    'current' => $c['to'] >= $currentYear,
  ];
  foreach (['mpg', 'mpge', 'range'] as $k) if (!empty($c[$k])) $e[$k] = $c[$k];

  // Prices only make sense for versions on sale now
  if ($e['current']) {
    if (isset($variantPrices[$key])) { $e['price'] = $variantPrices[$key]; $usedVariantPrices[$key] = 1; }
    elseif (isset($modelPrices[$c['make'] . '|' . $c['base']])) $e['modelPrice'] = $modelPrices[$c['make'] . '|' . $c['base']];
  }
  $all[] = $e;
}
usort($all, fn($a, $b) => strcasecmp($a['make'] . ' ' . $a['name'], $b['make'] . ' ' . $b['name']));
$current = array_values(array_filter($all, fn($e) => $e['current']));

$meta = ['built' => date('Y-m-d'), 'total' => count($all), 'totalCurrent' => count($current)];
file_put_contents("$dataDir/catalog.json", json_encode($meta + ['cars' => $current], JSON_UNESCAPED_SLASHES));
file_put_contents("$dataDir/catalog-all.json", json_encode($meta + ['cars' => $all], JSON_UNESCAPED_SLASHES));

$own = count(array_filter($current, fn($e) => isset($e['price'])));
$range = count(array_filter($current, fn($e) => isset($e['modelPrice'])));
echo count($all) . " versions in total, " . count($current) . " on sale now ($own with their own price, $range with a model price range).\n";

$unused = array_diff(array_keys($variantPrices), array_keys($usedVariantPrices));
if ($unused) echo "Version prices with no matching current version: " . implode(', ', $unused) . "\n";
