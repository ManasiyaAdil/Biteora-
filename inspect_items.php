<?php
require_once __DIR__ . '/includes/connect.php';

$res = $con->query("SELECT id, name, image FROM items WHERE id IN (3, 103)");
while ($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | Name: {$row['name']} | Image: {$row['image']}\n";
}
