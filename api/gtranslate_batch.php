<?php
/**
 * Traduction côté serveur (relais) — pour visiteurs où Google est inaccessible (ex. Chine).
 * Ne modifie pas le widget : utilisé uniquement en secours.
 */
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'method_not_allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'invalid_json']);
    exit;
}

$texts = isset($payload['texts']) && is_array($payload['texts']) ? $payload['texts'] : [];
$target = isset($payload['tl']) ? preg_replace('/[^a-zA-Z-]/', '', (string) $payload['tl']) : 'zh-TW';
$source = isset($payload['sl']) ? preg_replace('/[^a-zA-Z-]/', '', (string) $payload['sl']) : 'fr';

if ($target === '') {
    $target = 'zh-TW';
}
if ($source === '') {
    $source = 'fr';
}

$texts = array_slice($texts, 0, 25);
$out = gtranslate_batch_translate_many($texts, $source, $target);

echo json_encode(['success' => true, 'texts' => $out], JSON_UNESCAPED_UNICODE);

/**
 * @param array<int, mixed> $texts
 * @return array<int, string>
 */
function gtranslate_batch_translate_many(array $texts, string $source, string $target): array
{
    $normalized = [];
    foreach ($texts as $text) {
        $text = (string) $text;
        if (trim($text) === '' || mb_strlen($text) > 800) {
            $normalized[] = $text;
            continue;
        }
        $normalized[] = $text;
    }

    if ($normalized === []) {
        return [];
    }

    $query = 'client=gtx&sl=' . rawurlencode($source) . '&tl=' . rawurlencode($target) . '&dt=t';
    foreach ($normalized as $line) {
        $query .= '&q=' . rawurlencode($line);
    }

    $url = 'https://translate.googleapis.com/translate_a/single?' . $query;
    $body = gtranslate_batch_http_get($url);
    if ($body === null) {
        return $normalized;
    }

    $json = json_decode($body, true);
    if (!is_array($json) || !isset($json[0]) || !is_array($json[0])) {
        return $normalized;
    }

    $translated = [];
    foreach ($json[0] as $chunk) {
        if (is_array($chunk) && isset($chunk[0])) {
            $translated[] = (string) $chunk[0];
        }
    }

    if (count($translated) !== count($normalized)) {
        return $normalized;
    }

    return $translated;
}

/**
 * @return string|null
 */
function gtranslate_batch_http_get(string $url): ?string
{
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 12,
            'header' => "User-Agent: YayeMaty-GTranslate-Proxy/1.0\r\n",
        ],
    ]);

    $body = @file_get_contents($url, false, $ctx);
    if ($body === false && function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_USERAGENT => 'YayeMaty-GTranslate-Proxy/1.0',
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code >= 400) {
            return null;
        }
    }

    if ($body === false || $body === '') {
        return null;
    }

    return (string) $body;
}
