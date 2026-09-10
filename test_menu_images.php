<?php
require_once __DIR__ . '/includes/connect.php';

$res = $con->query("SELECT id, name, image, is_veg, is_available FROM items WHERE deleted=0");
$items = $res->fetch_all(MYSQLI_ASSOC);

echo "Total active items in DB: " . count($items) . "\n";

$veg_count = 0;
$nonveg_count = 0;
foreach ($items as $item) {
    if ($item['is_veg']) {
        $veg_count++;
    } else {
        $nonveg_count++;
    }
}

echo "Veg items: " . $veg_count . "\n";
echo "Non-Veg items: " . $nonveg_count . "\n";

// Test HTTP status of each image URL using curl_multi for fast parallel checking
$mh = curl_multi_init();
$curl_array = [];

foreach ($items as $idx => $item) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $item['image']);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
    curl_multi_add_handle($mh, $ch);
    $curl_array[$idx] = ['ch' => $ch, 'item' => $item];
}

$running = null;
do {
    curl_multi_exec($mh, $running);
    curl_multi_select($mh, 0.1);
} while ($running > 0);

$working = 0;
$broken = 0;
$broken_items = [];

foreach ($curl_array as $elem) {
    $code = curl_getinfo($elem['ch'], CURLINFO_HTTP_CODE);
    if ($code >= 200 && $code < 400) {
        $working++;
    } else {
        $broken++;
        $broken_items[] = [
            'id' => $elem['item']['id'],
            'name' => $elem['item']['name'],
            'code' => $code,
            'image' => $elem['item']['image']
        ];
    }
    curl_multi_remove_handle($mh, $elem['ch']);
    curl_close($elem['ch']);
}
curl_multi_close($mh);

echo "Images working: $working / " . count($items) . "\n";
if ($broken > 0) {
    echo "Broken count: $broken\n";
    foreach ($broken_items as $b) {
        echo " - [{$b['id']}] {$b['name']} (HTTP {$b['code']}): {$b['image']}\n";
    }
} else {
    echo "All image URLs are 100% verified and responding with HTTP 200 OK!\n";
}
