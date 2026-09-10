<?php
$urls = [
    'https://images.unsplash.com/photo-1541592106381-b31e9677c0e5?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1630384060421-cb20d0e0649d?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1606755962773-d324e0a13086?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1518013034458-30d0f261ddab?auto=format&fit=crop&w=600&q=80'
];

foreach ($urls as $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "$code -> $url\n";
}
