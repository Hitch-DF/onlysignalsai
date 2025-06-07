<?php

function translate(string $text, string $targetLang): string
{
    $ch = curl_init('https://libretranslate.de/translate');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'q' => $text,
            'source' => 'auto',
            'target' => $targetLang,
            'format' => 'text'
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['translatedText'] ?? $text;
}

function forceTranslateXlf(string $inputPath, string $targetLang, bool $force = false): void
{
    if (!file_exists($inputPath)) {
        echo "❌ File not found: $inputPath\n";
        exit(1);
    }

    $xml = new DOMDocument();
    $xml->load($inputPath);
    $xml->formatOutput = true;

    $xpath = new DOMXPath($xml);
    $xpath->registerNamespace("x", "urn:oasis:names:tc:xliff:document:1.2");

    $units = $xpath->query("//x:trans-unit");
    $total = $units->length;

    echo "⏳ Translating $total items to [$targetLang]... " . ($force ? "[FORCED]" : "[SKIP already translated]") . "\n";

    $index = 0;
    foreach ($units as $unit) {
        $index++;
        $source = $xpath->query("x:source", $unit)->item(0)?->nodeValue ?? '';
        if (!$source) continue;

        $targetNode = $xpath->query("x:target", $unit)->item(0);
        $existingTarget = $targetNode?->nodeValue ?? '';

        if (!$force && trim($existingTarget) && trim($existingTarget) !== trim($source)) {
            echo "[$index/$total] ⏩ Skipped\n";
            flush();
            continue;
        }

        $translated = translate($source, $targetLang);

        if (!$targetNode) {
            $targetNode = $xml->createElement("target");
            $unit->appendChild($targetNode);
        }

        $targetNode->nodeValue = htmlspecialchars($translated);

        echo "[$index/$total] ✔️ $source\n";
        flush();
        usleep(200000); // 0.2s
    }


    $outputPath = preg_replace('/\.xlf$/', '.translated.xlf', $inputPath);
    $xml->save($outputPath);
    echo "\n✅ File translated: $outputPath\n";
}

// === CLI handling ===
$options = getopt('', ['file:', 'lang:', 'force']);

if (empty($options['file']) || empty($options['lang'])) {
    echo "❌ Usage: php translate_xlf.php --file=messages.xlf --lang=en|fr [--force]\n";
    exit(1);
}

$file = __DIR__ . '/' . $options['file'];
$lang = $options['lang'];
$force = isset($options['force']);

forceTranslateXlf($file, $lang, $force);
