<?php
/**
 * Debug script for testing parameter encoding/decoding
 * Run with: php debug-params.php
 */

require_once 'vendor/autoload.php';
use App\Lib\LZCompressor\LZString;

echo "=== Parameter Debug Tool ===\n\n";

// Test data similar to what would come from the frontend
$testData = [
    'idRiwayatElab' => '12345',
    'karcis' => 'ABC123',
    'kdKlinik' => '0017',
    'kdPasienSS' => 'patient-123',
    'kdNakesSS' => 'nakes-456',
    'kdDokterSS' => 'dokter-789'
];

echo "1. Original data:\n";
print_r($testData);

echo "\n2. Encoding process (simulating frontend):\n";

// Step 1: Compress individual parts
$compressed = [];
foreach ($testData as $key => $value) {
    $compressed[$key] = LZString::compressToEncodedURIComponent($value);
    echo "   $key: '$value' -> '{$compressed[$key]}'\n";
}

// Step 2: Combine parts
$combined = implode('+', [
    $compressed['idRiwayatElab'],
    $compressed['karcis'],
    $compressed['kdKlinik'],
    $compressed['kdPasienSS'],
    $compressed['kdNakesSS'],
    $compressed['kdDokterSS']
]);
echo "\n   Combined: '$combined'\n";

// Step 3: Final compression (what gets sent from frontend)
$finalParam = LZString::compressToEncodedURIComponent($combined);
echo "   Final param: '$finalParam'\n";

echo "\n3. Decoding process (simulating job processing):\n";

// Step 1: Base64 encode (what job does)
$base64Encoded = base64_encode($finalParam);
echo "   Base64 encoded: '$base64Encoded'\n";

// Step 2: Base64 decode (what sendSatuSehat does first)
$base64Decoded = base64_decode($base64Encoded);
echo "   Base64 decoded: '$base64Decoded'\n";

// Step 3: LZ decompress
$decompressed = LZString::decompressFromEncodedURIComponent($base64Decoded);
echo "   LZ decompressed: '$decompressed'\n";

// Step 4: Split parts
$parts = explode('+', $decompressed);
echo "   Parts count: " . count($parts) . "\n";

// Step 5: Decompress individual parts
$decoded = [];
$partNames = ['idRiwayatElab', 'karcis', 'kdKlinik', 'kdPasienSS', 'kdNakesSS', 'kdDokterSS'];
foreach ($parts as $index => $part) {
    $value = LZString::decompressFromEncodedURIComponent($part);
    $name = $partNames[$index] ?? "part_$index";
    $decoded[$name] = $value;
    echo "   $name: '$part' -> '$value'\n";
}

echo "\n4. Final comparison:\n";
$success = true;
foreach ($testData as $key => $originalValue) {
    $decodedValue = $decoded[$key] ?? 'MISSING';
    $match = $originalValue === $decodedValue ? '✓' : '✗';
    echo "   $key: '$originalValue' == '$decodedValue' $match\n";
    if ($originalValue !== $decodedValue) {
        $success = false;
    }
}

echo "\n" . ($success ? "✓ All parameters decoded successfully!" : "✗ Parameter decoding failed!") . "\n";