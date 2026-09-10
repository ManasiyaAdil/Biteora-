<?php
require_once __DIR__ . '/includes/connect.php';
$con->query("UPDATE wallet_details SET balance = 1500.00 WHERE wallet_id = 1");
echo "Wallet topped up to 1500.00\n";
