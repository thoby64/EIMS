<?php
/**
 * Asset Loading Verification Script
 * 
 * This script helps verify that Vite assets are properly loaded
 * Run this in tinker: php artisan tinker < verify_assets.php
 */

echo "\n🔍 Asset Loading Verification\n";
echo "==============================\n\n";

// 1. Check manifest file
$manifestPath = public_path('build/manifest.json');
echo "1. Manifest File:\n";
if (file_exists($manifestPath)) {
    echo "   ✅ Found: $manifestPath\n";
    $manifest = json_decode(file_get_contents($manifestPath), true);
    echo "   📊 Entries: " . count($manifest) . "\n";
    echo "   📝 Files:\n";
    foreach ($manifest as $key => $value) {
        echo "      - $key => " . $value['file'] . "\n";
    }
} else {
    echo "   ❌ NOT FOUND: $manifestPath\n";
    echo "   💡 Solution: Run 'npm run build' to generate assets\n";
}

echo "\n2. Environment Variables:\n";
echo "   - APP_URL: " . config('app.url') . "\n";
echo "   - ASSET_URL: " . config('app.asset_url') . "\n";
echo "   - APP_ENV: " . config('app.env') . "\n";

echo "\n3. Public Build Directory:\n";
$buildPath = public_path('build');
if (is_dir($buildPath)) {
    echo "   ✅ Directory exists: $buildPath\n";
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($buildPath));
    $count = 0;
    foreach ($files as $file) {
        if ($file->isFile()) $count++;
    }
    echo "   📂 Files: " . $count . "\n";
} else {
    echo "   ❌ Directory NOT FOUND: $buildPath\n";
}

echo "\n4. Asset Helper Functions:\n";
$testAsset = asset('build/manifest.json');
echo "   - asset('build/manifest.json'): $testAsset\n";

echo "\n5. Testing @vite directive (in Blade):\n";
echo "   View app.blade.php to see @vite usage\n";

echo "\n✅ Verification complete!\n\n";
