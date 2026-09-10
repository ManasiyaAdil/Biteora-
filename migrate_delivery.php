<?php
// Migration script to safely add delivery fields and timestamps to orders table
require_once __DIR__ . '/includes/connect.php';

echo "=== MIGRATING BITEORA ORDERS SCHEMA FOR CAMPUS DELIVERY ===\n";

$existing_cols = [];
$res = $con->query("SHOW COLUMNS FROM `orders`");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $existing_cols[] = $r['Field'];
    }
}

$columns_to_add = [
    'delivery_method' => "VARCHAR(20) NOT NULL DEFAULT 'pickup' AFTER `customer_id`",
    'delivery_fee' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `delivery_method`",
    'delivery_building' => "VARCHAR(100) NULL AFTER `delivery_fee`",
    'delivery_floor' => "VARCHAR(20) NULL AFTER `delivery_building`",
    'delivery_room' => "VARCHAR(50) NULL AFTER `delivery_floor`",
    'delivery_note' => "TEXT NULL AFTER `delivery_room`",
    'placed_at' => "DATETIME NULL AFTER `payment_verified_at`",
    'preparing_at' => "DATETIME NULL AFTER `placed_at`",
    'out_for_delivery_at' => "DATETIME NULL AFTER `preparing_at`",
    'delivered_at' => "DATETIME NULL AFTER `out_for_delivery_at`"
];

foreach ($columns_to_add as $col_name => $col_def) {
    if (!in_array($col_name, $existing_cols)) {
        $sql = "ALTER TABLE `orders` ADD COLUMN `$col_name` $col_def";
        if ($con->query($sql)) {
            echo " [+] Added column: $col_name\n";
        } else {
            echo " [!] Failed adding $col_name: " . $con->error . "\n";
        }
    } else {
        echo " [=] Column already exists: $col_name\n";
    }
}

// Backfill placed_at for existing orders if null
$con->query("UPDATE `orders` SET `placed_at` = `date` WHERE `placed_at` IS NULL");
$con->query("UPDATE `orders` SET `delivery_method` = 'pickup' WHERE `delivery_method` IS NULL OR `delivery_method` = ''");

echo "=== MIGRATION COMPLETE ===\n";
