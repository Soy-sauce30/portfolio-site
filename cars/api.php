<?php
/* =========================================
   Car lookup endpoint
   POST JSON: { "query": "2024 Mazda MX-5" }
          or  { "image": "<base64 jpeg>" }
   Returns the car's specs/price as JSON.
   ========================================= */

header('Content-Type: application/json');
header('Cache-Control: no-store');

function fail($status, $message) {
  http_response_code($status);
  echo json_encode(['error' => $message]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail(405, 'POST only.');

// API key: env var first, then a file kept outside the web root.
$apiKey = getenv('ANTHROPIC_API_KEY');
$keyFile = dirname(__DIR__, 2) . '/sawyerabrahani-secrets/anthropic_key';
if (!$apiKey && is_readable($keyFile)) $apiKey = trim(file_get_contents($keyFile));
if (!$apiKey) fail(500, 'Car lookup is not configured yet.');

$input = json_decode(file_get_contents('php://input'), true);
$query = trim((string)($input['query'] ?? ''));
$image = (string)($input['image'] ?? '');

if ($query === '' && $image === '') fail(400, 'Type a car or add a photo.');
if (mb_strlen($query) > 120) fail(400, 'That search is a bit long — try just the make and model.');
if ($image !== '' && (strlen($image) > 1400000 || base64_decode($image, true) === false)) {
  fail(400, 'That photo could not be read. Try a smaller one.');
}

$tmp = sys_get_temp_dir() . '/sawyer-cars';
if (!is_dir($tmp)) mkdir($tmp, 0700, true);

// Text lookups are cached for a week — the same car gets searched a lot.
$cacheFile = null;
if ($image === '') {
  $cacheKey = preg_replace('/\s+/', ' ', strtolower($query));
  $cacheFile = "$tmp/cache-" . sha1($cacheKey) . '.json';
  if (is_file($cacheFile) && filemtime($cacheFile) > time() - 7 * 86400) {
    echo file_get_contents($cacheFile);
    exit;
  }
}

// Simple per-IP limit so a public page can't run up the API bill.
$limitFile = "$tmp/rate-" . sha1($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '.json';
$hits = is_file($limitFile) ? (json_decode(file_get_contents($limitFile), true) ?: []) : [];
$hits = array_values(array_filter($hits, fn($t) => $t > time() - 3600));
if (count($hits) >= 20) fail(429, 'Too many lookups — try again in a bit.');
$hits[] = time();
file_put_contents($limitFile, json_encode($hits), LOCK_EX);

$schema = [
  'type' => 'object',
  'additionalProperties' => false,
  'required' => ['identified', 'confidence', 'make', 'model', 'years', 'trim', 'body_style',
                 'summary', 'price_new', 'price_used', 'specs', 'pros', 'cons', 'rivals', 'note'],
  'properties' => [
    'identified' => ['type' => 'boolean'],
    'confidence' => ['type' => 'string', 'enum' => ['high', 'medium', 'low']],
    'make'       => ['type' => 'string'],
    'model'      => ['type' => 'string'],
    'years'      => ['type' => 'string'],
    'trim'       => ['type' => 'string'],
    'body_style' => ['type' => 'string', 'enum' => ['sedan', 'suv', 'truck', 'sports', 'hatchback', 'wagon', 'van', 'other']],
    'summary'    => ['type' => 'string'],
    'price_new'  => ['type' => 'string'],
    'price_used' => ['type' => 'string'],
    'specs' => [
      'type' => 'array',
      'items' => [
        'type' => 'object',
        'additionalProperties' => false,
        'required' => ['label', 'value'],
        'properties' => ['label' => ['type' => 'string'], 'value' => ['type' => 'string']],
      ],
    ],
    'pros'   => ['type' => 'array', 'items' => ['type' => 'string']],
    'cons'   => ['type' => 'array', 'items' => ['type' => 'string']],
    'rivals' => ['type' => 'array', 'items' => ['type' => 'string']],
    'note'   => ['type' => 'string'],
  ],
];

$system = <<<TXT
You identify cars and describe them for a car-enthusiast website. Today is {DATE}.

Set identified=false (and leave other fields empty) if the photo or search is not a car or you can't tell what it is; explain briefly in note.
Otherwise fill in:
- make, model, years (generation or model-year range, e.g. "2019–present"), trim (best guess, or "" if unknown).
- confidence: how sure you are of the identification. From a photo, say what visual cues you used in note.
- summary: 2–3 sentences on what the car is and who it's for.
- price_new: US MSRP range in USD for the current or most recent model year, e.g. "\$28,400 – \$36,900". Say "Not sold new" if discontinued.
- price_used: typical US used price range for the identified generation.
- specs: 8–10 label/value pairs — engine, horsepower, torque, 0–60 mph, top speed, transmission, drivetrain, fuel economy (or EV range), seating, curb weight. Use US units.
- pros / cons: 3 each, short.
- rivals: 3 competing models.
Prices and specs are from your knowledge, so give ranges rather than false precision.
TXT;
$system = str_replace('{DATE}', date('F Y'), $system);

$content = [];
if ($image !== '') {
  $content[] = ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => 'image/jpeg', 'data' => $image]];
  $content[] = ['type' => 'text', 'text' => 'What car is this?' . ($query !== '' ? " Extra hint: $query" : '')];
} else {
  $content[] = ['type' => 'text', 'text' => "Look up this car: $query"];
}

$body = [
  'model' => 'claude-opus-5',
  'max_tokens' => 8000,
  'fallbacks' => 'default',
  'output_config' => [
    'effort' => 'low',
    'format' => ['type' => 'json_schema', 'schema' => $schema],
  ],
  'system' => $system,
  'messages' => [['role' => 'user', 'content' => $content]],
];

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT => 55,
  CURLOPT_HTTPHEADER => [
    'content-type: application/json',
    'x-api-key: ' . $apiKey,
    'anthropic-version: 2023-06-01',
    'anthropic-beta: server-side-fallback-2026-07-01',
  ],
  CURLOPT_POSTFIELDS => json_encode($body),
]);
$raw = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($raw === false) fail(504, 'The lookup timed out. Try again.');
$response = json_decode($raw, true);
if ($status !== 200) {
  error_log("car lookup: HTTP $status " . substr($raw, 0, 500));
  fail(502, $status === 429 || $status === 529 ? 'Lookups are busy right now — try again shortly.' : 'The lookup failed. Try again.');
}
if (($response['stop_reason'] ?? '') === 'refusal') fail(422, "Couldn't look that one up.");

$text = '';
foreach ($response['content'] ?? [] as $block) {
  if (($block['type'] ?? '') === 'text') $text .= $block['text'];
}
$car = json_decode($text, true);
if (!is_array($car)) fail(502, 'The lookup came back garbled. Try again.');

$out = json_encode($car);
if ($cacheFile && $car['identified']) file_put_contents($cacheFile, $out, LOCK_EX);
echo $out;
