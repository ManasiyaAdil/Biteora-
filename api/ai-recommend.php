<?php
@header('Content-Type: application/json');
require_once __DIR__ . '/../includes/connect.php';

$query = isset($_GET['q']) ? strtolower(trim($_GET['q'])) : '';

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a request, e.g. "under ₹100", "spicy snack", or "cold drink"']);
    exit;
}

// Extract price limit if mentioned
$max_price = null;
if (preg_match('/(?:under|below|less than|within|budget|<=?|₹|\$)\s*(\d+)/i', $query, $matches)) {
    $max_price = floatval($matches[1]);
} elseif (preg_match('/(\d+)\s*(?:rs|rupees|inr)/i', $query, $matches)) {
    $max_price = floatval($matches[1]);
}

// Fetch all active available items
$sql = "SELECT i.*, c.name as category_name, c.slug as category_slug 
        FROM items i 
        LEFT JOIN categories c ON i.category_id = c.id 
        WHERE i.deleted = 0 AND i.is_available = 1";
$res = $con->query($sql);
$all_items = [];
while ($row = $res->fetch_assoc()) {
    $all_items[] = $row;
}

$scored_items = [];

foreach ($all_items as $item) {
    $score = 0;
    $item_text = strtolower($item['name'] . ' ' . $item['description'] . ' ' . $item['category_name']);
    $price = floatval($item['price']);

    // Price filtering and scoring
    if ($max_price !== null) {
        if ($price <= $max_price) {
            $score += 50;
            $score += ($price / $max_price) * 10;
        } else {
            continue;
        }
    }

    // Keyword matching
    $keywords = explode(' ', preg_replace('/[^a-z0-9]/', ' ', $query));
    foreach ($keywords as $kw) {
        if (strlen($kw) <= 2) continue;
        if (strpos($item_text, $kw) !== false) {
            $score += 20;
        }
    }

    // Specific intent tags
    if (strpos($query, 'spicy') !== false && (strpos($item_text, 'spice') !== false || strpos($item_text, 'peri') !== false || strpos($item_text, 'masala') !== false || strpos($item_text, 'tikka') !== false)) {
        $score += 35;
    }
    if (strpos($query, 'cold') !== false && (strpos($item_text, 'iced') !== false || strpos($item_text, 'cold') !== false || strpos($item_text, 'frappuccino') !== false || strpos($item_text, 'soda') !== false || strpos($item_text, 'lemonade') !== false)) {
        $score += 35;
    }
    if (strpos($query, 'sweet') !== false && (strpos($item_text, 'brownie') !== false || strpos($item_text, 'dessert') !== false || strpos($item_text, 'chocolate') !== false)) {
        $score += 35;
    }
    if (strpos($query, 'healthy') !== false && (strpos($item_text, 'fresh') !== false || strpos($item_text, 'dosa') !== false || strpos($item_text, 'lemonade') !== false)) {
        $score += 25;
    }
    if (strpos($query, 'quick') !== false || strpos($query, 'fast') !== false) {
        if (strpos($item['prep_time'], '3-5') !== false || strpos($item['prep_time'], '5-8') !== false) {
            $score += 20;
        }
    }

    $score += floatval($item['rating']) * 2;

    if ($score > 0) {
        $item['match_score'] = $score;
        $scored_items[] = $item;
    }
}

usort($scored_items, function($a, $b) {
    return $b['match_score'] <=> $a['match_score'];
});

$top_recommendations = array_slice($scored_items, 0, 4);

if (count($top_recommendations) > 0) {
    echo json_encode([
        'success' => true,
        'message' => 'Found ' . count($top_recommendations) . ' top recommendations curated for you:',
        'items' => $top_recommendations
    ]);
} else {
    $fallback = array_slice($all_items, 0, 3);
    echo json_encode([
        'success' => true,
        'message' => 'Here are our most popular chef picks right now:',
        'items' => $fallback
    ]);
}
?>
