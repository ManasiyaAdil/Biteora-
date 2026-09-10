<?php
// CampusBite Database Schema Migration and Realistic Demo Seeder

function campusbite_init_db($con, $pdo) {
    static $initialized = false;
    if ($initialized) return;
    $initialized = true;

    if ($con && !($con instanceof BiteoraSQLiteWrapper)) {
        campusbite_init_mysql($con);
    } elseif ($pdo) {
        campusbite_init_sqlite($pdo);
    }
}

function campusbite_init_mysql($con) {
    // 1. Categories Table
    $con->query("CREATE TABLE IF NOT EXISTS `categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(50) NOT NULL UNIQUE,
        `slug` VARCHAR(50) NOT NULL,
        `icon` VARCHAR(50) DEFAULT 'utensils',
        `description` VARCHAR(255) NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Users Table
    $con->query("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `role` VARCHAR(20) NOT NULL DEFAULT 'Customer',
        `name` VARCHAR(100) NOT NULL,
        `username` VARCHAR(30) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `email` VARCHAR(100) NULL,
        `address` VARCHAR(300) NULL,
        `contact` VARCHAR(20) NOT NULL,
        `verified` TINYINT(1) NOT NULL DEFAULT 1,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    @$con->query("ALTER TABLE `users` MODIFY `password` VARCHAR(255) NOT NULL;");
    @$con->query("ALTER TABLE `users` MODIFY `name` VARCHAR(100) NOT NULL;");
    @$con->query("ALTER TABLE `users` MODIFY `contact` VARCHAR(20) NOT NULL;");

    // 3. Items Table
    $con->query("CREATE TABLE IF NOT EXISTS `items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `category_id` INT NULL,
        `name` VARCHAR(100) NOT NULL,
        `price` DECIMAL(10,2) NOT NULL,
        `description` TEXT NULL,
        `image` VARCHAR(500) NULL,
        `is_available` TINYINT(1) NOT NULL DEFAULT 1,
        `is_veg` TINYINT(1) NOT NULL DEFAULT 1,
        `rating` DECIMAL(2,1) DEFAULT 4.5,
        `prep_time` VARCHAR(20) DEFAULT '10-15 mins',
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $cols = [];
    $res = $con->query("SHOW COLUMNS FROM `items`");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $cols[] = $r['Field'];
        }
    }
    if (!in_array('category_id', $cols)) @$con->query("ALTER TABLE `items` ADD COLUMN `category_id` INT NULL AFTER `id`;");
    if (!in_array('description', $cols)) @$con->query("ALTER TABLE `items` ADD COLUMN `description` TEXT NULL AFTER `price`;");
    if (!in_array('image', $cols)) @$con->query("ALTER TABLE `items` ADD COLUMN `image` VARCHAR(500) NULL AFTER `description`;");
    if (!in_array('is_available', $cols)) @$con->query("ALTER TABLE `items` ADD COLUMN `is_available` TINYINT(1) NOT NULL DEFAULT 1 AFTER `image`;");
    if (!in_array('is_veg', $cols)) @$con->query("ALTER TABLE `items` ADD COLUMN `is_veg` TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_available`;");
    if (!in_array('rating', $cols)) @$con->query("ALTER TABLE `items` ADD COLUMN `rating` DECIMAL(2,1) DEFAULT 4.5 AFTER `is_veg`;");
    if (!in_array('prep_time', $cols)) @$con->query("ALTER TABLE `items` ADD COLUMN `prep_time` VARCHAR(20) DEFAULT '10-15 mins' AFTER `rating`;");
    @$con->query("ALTER TABLE `items` MODIFY `name` VARCHAR(255) NOT NULL;");
    @$con->query("ALTER TABLE `items` MODIFY `price` DECIMAL(10,2) NOT NULL;");

    // 4. Orders Table with Razorpay, Token & Pickup Verification
    $con->query("CREATE TABLE IF NOT EXISTS `orders` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_code` VARCHAR(30) NULL,
        `customer_id` INT NOT NULL,
        `pickup_token` VARCHAR(10) NULL,
        `address` VARCHAR(300) NOT NULL DEFAULT 'Campus Cafeteria Counter 1',
        `description` VARCHAR(300) NULL,
        `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `payment_type` VARCHAR(30) NOT NULL DEFAULT 'Online Payment',
        `payment_status` VARCHAR(20) NOT NULL DEFAULT 'Pending',
        `razorpay_order_id` VARCHAR(100) NULL,
        `razorpay_payment_id` VARCHAR(100) NULL,
        `razorpay_signature` VARCHAR(255) NULL,
        `payment_verified_at` DATETIME NULL,
        `pickup_verification_token` VARCHAR(64) NULL,
        `picked_up_at` DATETIME NULL,
        `refund_id` VARCHAR(100) NULL,
        `refund_amount` DECIMAL(10,2) NULL,
        `refund_status` VARCHAR(30) NULL,
        `refund_at` DATETIME NULL,
        `total` DECIMAL(10,2) NOT NULL,
        `status` VARCHAR(30) NOT NULL DEFAULT 'Placed',
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $order_cols = [];
    $res = $con->query("SHOW COLUMNS FROM `orders`");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $order_cols[] = $r['Field'];
        }
    }
    if (!in_array('order_code', $order_cols)) @$con->query("ALTER TABLE `orders` ADD COLUMN `order_code` VARCHAR(30) NULL AFTER `id`;");
    if (!in_array('pickup_token', $order_cols)) @$con->query("ALTER TABLE `orders` ADD COLUMN `pickup_token` VARCHAR(10) NULL AFTER `customer_id`;");
    if (!in_array('payment_status', $order_cols)) @$con->query("ALTER TABLE `orders` ADD COLUMN `payment_status` VARCHAR(20) NOT NULL DEFAULT 'Pending' AFTER `payment_type`;");
    if (!in_array('razorpay_order_id', $order_cols)) @$con->query("ALTER TABLE `orders` ADD COLUMN `razorpay_order_id` VARCHAR(100) NULL AFTER `payment_status`;");
    if (!in_array('razorpay_payment_id', $order_cols)) @$con->query("ALTER TABLE `orders` ADD COLUMN `razorpay_payment_id` VARCHAR(100) NULL AFTER `razorpay_order_id`;");
    if (!in_array('razorpay_signature', $order_cols)) @$con->query("ALTER TABLE `orders` ADD COLUMN `razorpay_signature` VARCHAR(255) NULL AFTER `razorpay_payment_id`;");
    if (!in_array('payment_verified_at', $order_cols)) @$con->query("ALTER TABLE `orders` ADD COLUMN `payment_verified_at` DATETIME NULL AFTER `razorpay_signature`;");
    if (!in_array('pickup_verification_token', $order_cols)) @$con->query("ALTER TABLE `orders` ADD COLUMN `pickup_verification_token` VARCHAR(64) NULL AFTER `payment_verified_at`;");
    if (!in_array('picked_up_at', $order_cols)) @$con->query("ALTER TABLE `orders` ADD COLUMN `picked_up_at` DATETIME NULL AFTER `pickup_verification_token`;");
    if (!in_array('refund_id', $order_cols)) @$con->query("ALTER TABLE `orders` ADD COLUMN `refund_id` VARCHAR(100) NULL AFTER `picked_up_at`;");
    if (!in_array('refund_amount', $order_cols)) @$con->query("ALTER TABLE `orders` ADD COLUMN `refund_amount` DECIMAL(10,2) NULL AFTER `refund_id`;");
    if (!in_array('refund_status', $order_cols)) @$con->query("ALTER TABLE `orders` ADD COLUMN `refund_status` VARCHAR(30) NULL AFTER `refund_amount`;");
    if (!in_array('refund_at', $order_cols)) @$con->query("ALTER TABLE `orders` ADD COLUMN `refund_at` DATETIME NULL AFTER `refund_status`;");
    if (!in_array('delivery_method', $order_cols)) @$con->query("ALTER TABLE `orders` ADD COLUMN `delivery_method` VARCHAR(20) NOT NULL DEFAULT 'pickup' AFTER `customer_id`;");
    if (!in_array('delivery_fee', $order_cols)) @$con->query("ALTER TABLE `orders` ADD COLUMN `delivery_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `delivery_method`;");
    if (!in_array('delivery_building', $order_cols)) @$con->query("ALTER TABLE `orders` ADD COLUMN `delivery_building` VARCHAR(100) NULL AFTER `delivery_fee`;");
    if (!in_array('delivery_floor', $order_cols)) @$con->query("ALTER TABLE `orders` ADD COLUMN `delivery_floor` VARCHAR(20) NULL AFTER `delivery_building`;");
    if (!in_array('delivery_room', $order_cols)) @$con->query("ALTER TABLE `orders` ADD COLUMN `delivery_room` VARCHAR(50) NULL AFTER `delivery_floor`;");
    if (!in_array('delivery_note', $order_cols)) @$con->query("ALTER TABLE `orders` ADD COLUMN `delivery_note` TEXT NULL AFTER `delivery_room`;");
    if (!in_array('placed_at', $order_cols)) @$con->query("ALTER TABLE `orders` ADD COLUMN `placed_at` DATETIME NULL AFTER `payment_verified_at`;");
    if (!in_array('preparing_at', $order_cols)) @$con->query("ALTER TABLE `orders` ADD COLUMN `preparing_at` DATETIME NULL AFTER `placed_at`;");
    if (!in_array('out_for_delivery_at', $order_cols)) @$con->query("ALTER TABLE `orders` ADD COLUMN `out_for_delivery_at` DATETIME NULL AFTER `preparing_at`;");
    if (!in_array('delivered_at', $order_cols)) @$con->query("ALTER TABLE `orders` ADD COLUMN `delivered_at` DATETIME NULL AFTER `out_for_delivery_at`;");
    if (!in_array('created_at', $order_cols)) @$con->query("ALTER TABLE `orders` ADD COLUMN `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP;");
    if (!in_array('updated_at', $order_cols)) @$con->query("ALTER TABLE `orders` ADD COLUMN `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;");

    // Add index on pickup_verification_token and razorpay_order_id if not present
    $indexes = [];
    $idx_res = $con->query("SHOW INDEX FROM `orders`");
    if ($idx_res) {
        while ($idx_r = $idx_res->fetch_assoc()) {
            $indexes[] = $idx_r['Key_name'];
        }
    }
    if (!in_array('idx_pvt', $indexes)) @$con->query("ALTER TABLE `orders` ADD INDEX `idx_pvt` (`pickup_verification_token`);");
    if (!in_array('idx_rzo', $indexes)) @$con->query("ALTER TABLE `orders` ADD INDEX `idx_rzo` (`razorpay_order_id`);");

    // 5. Order Details Table
    $con->query("CREATE TABLE IF NOT EXISTS `order_details` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_id` INT NOT NULL,
        `item_id` INT NOT NULL,
        `quantity` INT NOT NULL,
        `price` DECIMAL(10,2) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 6. Wallet Tables
    $con->query("CREATE TABLE IF NOT EXISTS `wallet` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `customer_id` INT NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $con->query("CREATE TABLE IF NOT EXISTS `wallet_details` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `wallet_id` INT NOT NULL UNIQUE,
        `number` VARCHAR(20) NOT NULL,
        `cvv` INT NOT NULL DEFAULT 123,
        `balance` DECIMAL(10,2) NOT NULL DEFAULT 1500.00
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 7. Tickets & Feedback
    $con->query("CREATE TABLE IF NOT EXISTS `tickets` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `poster_id` INT NOT NULL,
        `subject` VARCHAR(100) NOT NULL,
        `description` TEXT NOT NULL,
        `status` VARCHAR(20) NOT NULL DEFAULT 'Open',
        `type` VARCHAR(30) NOT NULL DEFAULT 'Support',
        `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $con->query("CREATE TABLE IF NOT EXISTS `ticket_details` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `ticket_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `description` TEXT NOT NULL,
        `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Seed Data
    campusbite_seed_data($con);
}

function campusbite_init_sqlite($pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT UNIQUE NOT NULL,
        slug TEXT NOT NULL,
        icon TEXT DEFAULT 'utensils',
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        role TEXT NOT NULL DEFAULT 'Customer',
        name TEXT NOT NULL,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        email TEXT,
        address TEXT,
        contact TEXT NOT NULL,
        verified INTEGER NOT NULL DEFAULT 1,
        deleted INTEGER NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );");

    $pdo->exec("CREATE TABLE IF NOT EXISTS items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category_id INTEGER,
        name TEXT NOT NULL,
        price REAL NOT NULL,
        description TEXT,
        image TEXT,
        is_available INTEGER NOT NULL DEFAULT 1,
        is_veg INTEGER NOT NULL DEFAULT 1,
        rating REAL DEFAULT 4.5,
        prep_time TEXT DEFAULT '10-15 mins',
        deleted INTEGER NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );");

    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_code TEXT,
        customer_id INTEGER NOT NULL,
        pickup_token TEXT,
        address TEXT NOT NULL DEFAULT 'Campus Cafeteria Counter 1',
        description TEXT,
        date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        payment_type TEXT NOT NULL DEFAULT 'Online Payment',
        payment_status TEXT NOT NULL DEFAULT 'Pending',
        razorpay_order_id TEXT,
        razorpay_payment_id TEXT,
        razorpay_signature TEXT,
        payment_verified_at DATETIME,
        pickup_verification_token TEXT,
        picked_up_at DATETIME,
        refund_id TEXT,
        refund_amount REAL,
        refund_status TEXT,
        refund_at DATETIME,
        total REAL NOT NULL,
        status TEXT NOT NULL DEFAULT 'Placed',
        deleted INTEGER NOT NULL DEFAULT 0
    );");

    $pdo->exec("CREATE TABLE IF NOT EXISTS order_details (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        item_id INTEGER NOT NULL,
        quantity INTEGER NOT NULL,
        price REAL NOT NULL
    );");

    $pdo->exec("CREATE TABLE IF NOT EXISTS wallet (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        customer_id INTEGER UNIQUE NOT NULL
    );");

    $pdo->exec("CREATE TABLE IF NOT EXISTS wallet_details (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        wallet_id INTEGER UNIQUE NOT NULL,
        number TEXT NOT NULL,
        cvv INTEGER NOT NULL DEFAULT 123,
        balance REAL NOT NULL DEFAULT 1500.00
    );");
}

function campusbite_seed_data($con) {
    // 1. Categories
    $categories = [
        ['Breakfast', 'breakfast', 'egg-fried', 'Hot, fresh morning campus breakfast meals.'],
        ['Snacks & Quick Bites', 'snacks', 'cookie', 'Crispy fries, rolls, wraps and evening snacks.'],
        ['Main Course', 'main-course', 'bowl-rice', 'Nutritious lunch & dinner combos, biryanis and meals.'],
        ['Beverages', 'beverages', 'cup-hot', 'Cold brewed coffee, artisanal shakes, fresh juice & tea.'],
        ['Desserts & Bakery', 'desserts', 'cake-candles', 'Warm brownies, pastries, cookies & ice creams.'],
        ['Campus Specials', 'specials', 'fire', 'Chef special limited editions and combo platters.']
    ];

    foreach ($categories as $cat) {
        $name = $con->real_escape_string($cat[0]);
        $slug = $con->real_escape_string($cat[1]);
        $icon = $con->real_escape_string($cat[2]);
        $desc = $con->real_escape_string($cat[3]);
        $con->query("INSERT IGNORE INTO `categories` (`name`, `slug`, `icon`, `description`) VALUES ('$name', '$slug', '$icon', '$desc')");
    }

    $cat_map = [];
    $res = $con->query("SELECT id, slug FROM categories");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $cat_map[$r['slug']] = $r['id'];
        }
    }

    // 2. Demo Menu Items
    $items = [
        [
            'name' => 'Campus Supreme Veg Burger',
            'slug' => 'snacks',
            'price' => 85.00,
            'desc' => 'Crispy herb potato patty topped with fresh lettuce, tomatoes, creamy cheddar sauce & toasted sesame bun.',
            'image' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=600&q=80',
            'veg' => 1,
            'rating' => 4.8,
            'prep' => '8-10 mins'
        ],
        [
            'name' => 'Paneer Tikka Kathi Roll',
            'slug' => 'snacks',
            'price' => 110.00,
            'desc' => 'Chargrilled marinated cottage cheese, crunchy onions, mint chutney rolled in a layered flaky paratha.',
            'image' => 'https://images.unsplash.com/photo-1626777552726-4a6b54c97e46?auto=format&fit=crop&w=600&q=80',
            'veg' => 1,
            'rating' => 4.9,
            'prep' => '10-12 mins'
        ],
        [
            'name' => 'Peri-Peri Loaded French Fries',
            'slug' => 'snacks',
            'price' => 75.00,
            'desc' => 'Golden crispy fries tossed in fiery peri-peri spices served with zesty garlic mayo dip.',
            'image' => 'https://images.unsplash.com/photo-1576107232684-1279f3908594?auto=format&fit=crop&w=600&q=80',
            'veg' => 1,
            'rating' => 4.7,
            'prep' => '5-8 mins'
        ],
        [
            'name' => 'Four Cheese Burst Pizza (7-inch)',
            'slug' => 'snacks',
            'price' => 160.00,
            'desc' => 'Stone-baked thin crust pizza loaded with mozzarella, cheddar, gouda, fresh basil & herb marinara.',
            'image' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=600&q=80',
            'veg' => 1,
            'rating' => 4.8,
            'prep' => '12-15 mins'
        ],
        [
            'name' => 'Bombay Masala Grilled Sandwich',
            'slug' => 'breakfast',
            'price' => 65.00,
            'desc' => 'Triple-layered toasted bread packed with spiced potatoes, beetroot, cucumber, cheese & tangy coriander dip.',
            'image' => 'https://images.unsplash.com/photo-1528735602780-2552fd46c7af?auto=format&fit=crop&w=600&q=80',
            'veg' => 1,
            'rating' => 4.6,
            'prep' => '8-10 mins'
        ],
        [
            'name' => 'South Indian Butter Masala Dosa',
            'slug' => 'breakfast',
            'price' => 70.00,
            'desc' => 'Crispy golden crepe filled with fragrant spiced potato masala, served with piping hot sambar & coconut chutney.',
            'image' => 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?auto=format&fit=crop&w=600&q=80',
            'veg' => 1,
            'rating' => 4.9,
            'prep' => '6-8 mins'
        ],
        [
            'name' => 'Royal Dum Hyderabadi Veg Biryani',
            'slug' => 'main-course',
            'price' => 140.00,
            'desc' => 'Long grain basmati rice slow-cooked with fresh garden veggies, saffron & aromatic whole spices with cooling burani raita.',
            'image' => 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?auto=format&fit=crop&w=600&q=80',
            'veg' => 1,
            'rating' => 4.9,
            'prep' => '12-15 mins'
        ],
        [
            'name' => 'Classic Butter Chicken / Paneer Rice Bowl',
            'slug' => 'main-course',
            'price' => 135.00,
            'desc' => 'Velvety rich tomato butter gravy served over steaming jeera rice with pickled onions.',
            'image' => 'https://images.unsplash.com/photo-1588166524941-3bf61a9c41db?auto=format&fit=crop&w=600&q=80',
            'veg' => 1,
            'rating' => 4.8,
            'prep' => '10-12 mins'
        ],
        [
            'name' => 'Iced Caramel Frappuccino',
            'slug' => 'beverages',
            'price' => 80.00,
            'desc' => 'Chilled espresso blended with rich cream, caramel drizzle and topped with whipped chocolate froth.',
            'image' => 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=600&q=80',
            'veg' => 1,
            'rating' => 4.9,
            'prep' => '4-6 mins'
        ],
        [
            'name' => 'Kulhad Masala Chai',
            'slug' => 'beverages',
            'price' => 20.00,
            'desc' => 'Authentic slow-brewed tea infused with fresh ginger, crushed cardamom & cloves served in an earthen clay cup.',
            'image' => 'https://images.unsplash.com/photo-1576092768241-dec231879fc3?auto=format&fit=crop&w=600&q=80',
            'veg' => 1,
            'rating' => 5.0,
            'prep' => '3-5 mins'
        ],
        [
            'name' => 'Fresh Mint Lemonade Soda',
            'slug' => 'beverages',
            'price' => 40.00,
            'desc' => 'Zesty freshly squeezed lemons, crushed mint leaves, rock salt & chilled sparkling soda.',
            'image' => 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?auto=format&fit=crop&w=600&q=80',
            'veg' => 1,
            'rating' => 4.7,
            'prep' => '3-5 mins'
        ],
        [
            'name' => 'Sizzling Hot Chocolate Walnut Brownie',
            'slug' => 'desserts',
            'price' => 95.00,
            'desc' => 'Decadent dark chocolate fudge brownie loaded with roasted walnuts, served with hot chocolate fudge.',
            'image' => 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?auto=format&fit=crop&w=600&q=80',
            'veg' => 1,
            'rating' => 4.9,
            'prep' => '5-7 mins'
        ]
    ];

    @$con->query("UPDATE `items` SET `deleted` = 1 WHERE `name` LIKE 'Item %'");
    $check_items = $con->query("SELECT COUNT(*) as cnt FROM `items` WHERE `deleted` = 0 AND `description` IS NOT NULL AND `description` != ''");
    $cnt = $check_items ? (int)$check_items->fetch_assoc()['cnt'] : 0;
    
    if ($cnt < 50 && file_exists(__DIR__ . '/../seed_100_items.php')) {
        ob_start();
        require_once __DIR__ . '/../seed_100_items.php';
        ob_end_clean();
    } elseif ($cnt < 6) {
        try {
            @$con->query("SET FOREIGN_KEY_CHECKS = 0;");
            @$con->query("DELETE FROM `items` WHERE `name` LIKE 'Item %' OR `description` IS NULL OR `description` = ''");
            @$con->query("SET FOREIGN_KEY_CHECKS = 1;");
        } catch (Throwable $e) {
            // Ignore if foreign keys or permissions restrict deletion
        }
        
        foreach ($items as $it) {
            $c_id = $cat_map[$it['slug']] ?? 1;
            $name = $con->real_escape_string($it['name']);
            $price = floatval($it['price']);
            $desc = $con->real_escape_string($it['desc']);
            $img = $con->real_escape_string($it['image']);
            $veg = intval($it['veg']);
            $rating = floatval($it['rating']);
            $prep = $con->real_escape_string($it['prep']);

            $con->query("INSERT INTO `items` (`category_id`, `name`, `price`, `description`, `image`, `is_available`, `is_veg`, `rating`, `prep_time`, `deleted`) 
                VALUES ($c_id, '$name', $price, '$desc', '$img', 1, $veg, $rating, '$prep', 0)
                ON DUPLICATE KEY UPDATE `price` = $price, `description` = '$desc', `image` = '$img', `is_available` = 1");
        }
    }

    // Ensure every active item has a valid image URL (repair missing/null images)
    @$con->query("UPDATE `items` SET `image` = 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=600&q=75' WHERE (`image` IS NULL OR `image` = '') AND `deleted` = 0");

    // 3. Demo Users (Admin + Student)
    $admin_pass_hash = password_hash('toor', PASSWORD_DEFAULT);
    $con->query("INSERT INTO `users` (`id`, `role`, `name`, `username`, `password`, `email`, `address`, `contact`, `verified`, `deleted`)
        VALUES (1, 'Administrator', 'Cafeteria Manager', 'root', '$admin_pass_hash', 'admin@campusbite.edu', 'Campus Admin Block A', '9876543210', 1, 0)
        ON DUPLICATE KEY UPDATE `role` = 'Administrator', `name` = 'Cafeteria Manager'");

    $student_pass_hash = password_hash('pass1', PASSWORD_DEFAULT);
    $con->query("INSERT INTO `users` (`id`, `role`, `name`, `username`, `password`, `email`, `address`, `contact`, `verified`, `deleted`)
        VALUES (2, 'Customer', 'Aarav Sharma', 'user1', '$student_pass_hash', 'aarav.sharma@campus.edu', 'Hostel H-4, Room 302', '9898000001', 1, 0)
        ON DUPLICATE KEY UPDATE `name` = 'Aarav Sharma'");

    $student2_pass_hash = password_hash('password123', PASSWORD_DEFAULT);
    $con->query("INSERT INTO `users` (`id`, `role`, `name`, `username`, `password`, `email`, `address`, `contact`, `verified`, `deleted`)
        VALUES (3, 'Customer', 'Priya Patel', 'priya', '$student2_pass_hash', 'priya.patel@campus.edu', 'Girls Hostel Block B, Room 114', '9898000002', 1, 0)
        ON DUPLICATE KEY UPDATE `name` = 'Priya Patel'");

    // 4. Wallet setup for users
    $con->query("INSERT IGNORE INTO `wallet` (`id`, `customer_id`) VALUES (1, 1), (2, 2), (3, 3);");
    $con->query("INSERT IGNORE INTO `wallet_details` (`id`, `wallet_id`, `number`, `cvv`, `balance`) 
        VALUES (1, 1, '8822441100223344', 999, 5000.00),
               (2, 2, '5511223344556677', 342, 1850.00),
               (3, 3, '4433221188776655', 521, 2400.00);");
}
?>
