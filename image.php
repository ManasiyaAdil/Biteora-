<?php
/**
 * Biteora High-Performance Cloud Image Proxy & Resilient Fallback Generator
 * Serves same-origin images to completely bypass campus/corporate firewall CDN blocks.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$url = isset($_GET['url']) ? trim($_GET['url']) : '';

require_once __DIR__ . '/includes/connect.php';

$item_name = 'Biteora Dish';
$item_veg = 1;
$item_cat = 'Cafeteria';

if ($id > 0) {
    $stmt = $con->prepare("SELECT i.name, i.image, i.is_veg, c.name as category_name FROM items i LEFT JOIN categories c ON i.category_id = c.id WHERE i.id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            if (!empty($row['image'])) {
                $url = $row['image'];
            }
            $item_name = $row['name'];
            $item_veg = (int)$row['is_veg'];
            $item_cat = $row['category_name'] ?: 'Cafeteria';
        }
    }
}

// Function to output a beautiful, high-res inline SVG dish card if external fetch fails
function render_svg_dish($name, $veg, $cat) {
    header('Content-Type: image/svg+xml');
    header('Cache-Control: public, max-age=604800');
    
    $bg_color = $veg ? '#064E3B' : '#7F1D1D'; // Dark emerald for veg, deep ruby for non-veg
    $pill_color = $veg ? '#059669' : '#DC2626';
    $diet_text = $veg ? '100% PURE VEG' : 'NON-VEGETARIAN';
    $icon = $veg ? '🌱' : '🍗';
    
    $safe_name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safe_cat = htmlspecialchars($cat, ENT_QUOTES, 'UTF-8');
    
    echo <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400" viewBox="0 0 600 400">
  <defs>
    <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#1E293B" />
      <stop offset="50%" stop-color="#0F172A" />
      <stop offset="100%" stop-color="{$bg_color}" />
    </linearGradient>
    <radialGradient id="glow" cx="50%" cy="40%" r="50%">
      <stop offset="0%" stop-color="#FF5A36" stop-opacity="0.35" />
      <stop offset="100%" stop-color="#FF5A36" stop-opacity="0" />
    </radialGradient>
  </defs>
  
  <!-- Background Card -->
  <rect width="600" height="400" fill="url(#grad)" />
  <rect width="600" height="400" fill="url(#glow)" />
  
  <!-- Subtle Grid/Pattern -->
  <g opacity="0.08" stroke="#FFFFFF" stroke-width="1">
    <line x1="0" y1="100" x2="600" y2="100" />
    <line x1="0" y1="200" x2="600" y2="200" />
    <line x1="0" y1="300" x2="600" y2="300" />
    <line x1="150" y1="0" x2="150" y2="400" />
    <line x1="300" y1="0" x2="300" y2="400" />
    <line x1="450" y1="0" x2="450" y2="400" />
  </g>
  
  <!-- Category Badge -->
  <rect x="40" y="35" width="160" height="30" rx="15" fill="#FFFFFF" fill-opacity="0.12" />
  <text x="120" y="55" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="12" font-weight="700" fill="#CBD5E1" text-anchor="middle" letter-spacing="1">{$safe_cat}</text>
  
  <!-- Diet Pill -->
  <rect x="420" y="35" width="140" height="30" rx="15" fill="{$pill_color}" />
  <text x="490" y="55" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="11" font-weight="800" fill="#FFFFFF" text-anchor="middle" letter-spacing="0.5">{$diet_text}</text>
  
  <!-- Center Plate Illustration -->
  <circle cx="300" cy="185" r="75" fill="#FFFFFF" fill-opacity="0.05" stroke="#FFFFFF" stroke-width="2" stroke-opacity="0.15" />
  <circle cx="300" cy="185" r="60" fill="#FFFFFF" fill-opacity="0.08" stroke="#FF5A36" stroke-width="2" stroke-dasharray="6,4" />
  <text x="300" y="202" font-size="48" text-anchor="middle">{$icon}</text>
  
  <!-- Dish Name (Wrapped / Centered) -->
  <text x="300" y="305" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="24" font-weight="800" fill="#FFFFFF" text-anchor="middle">{$safe_name}</text>
  
  <!-- Fresh Kitchen Tag -->
  <text x="300" y="340" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="13" font-weight="600" fill="#FF5A36" text-anchor="middle" letter-spacing="0.5">✨ Biteora Fresh Campus Kitchen</text>
</svg>
SVG;
    exit;
}

// If no valid URL, render SVG immediately
if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    render_svg_dish($item_name, $item_veg, $item_cat);
}

// Local cache directory
$cache_dir = sys_get_temp_dir() . '/biteora_img_cache';
if (!is_dir($cache_dir)) {
    @mkdir($cache_dir, 0777, true);
}
$cache_file = $cache_dir . '/' . md5($url);

// Check if cached and less than 7 days old
if (file_exists($cache_file) && (time() - filemtime($cache_file) < 604800)) {
    $mime = mime_content_type($cache_file) ?: 'image/jpeg';
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=604800');
    header('X-Biteora-Cache: HIT');
    readfile($cache_file);
    exit;
}

// Fetch external image server-side
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 6);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

$data = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($http_code >= 200 && $http_code < 400 && !empty($data)) {
    @file_put_contents($cache_file, $data);
    $mime = $content_type ?: 'image/jpeg';
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=604800');
    header('X-Biteora-Cache: MISS');
    echo $data;
    exit;
}

// Fallback to SVG dish representation if external download failed
render_svg_dish($item_name, $item_veg, $item_cat);
?>
