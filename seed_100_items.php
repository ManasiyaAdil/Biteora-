<?php
/**
 * BITEORA - Final Menu Seeder (104 Items: 52 Veg + 52 Non-Veg)
 */

require_once __DIR__ . '/includes/connect.php';

echo "=== SEEDING BITEORA 104 MENU ITEMS ===\n";

// Ensure categories exist
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
while ($r = $res->fetch_assoc()) {
    $cat_map[$r['slug']] = $r['id'];
}

$all_items = [
    // --- 52 VEG ITEMS ---
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
        'name' => 'Idli Vada Sambar Combo',
        'slug' => 'breakfast',
        'price' => 60.00,
        'desc' => 'Steaming soft rice idlis and crispy medu vada served with freshly ground coconut chutney & spiced lentil sambar.',
        'image' => 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.8,
        'prep' => '5-7 mins'
    ],
    [
        'name' => 'Indori Poha with Peanuts & Sev',
        'slug' => 'breakfast',
        'price' => 45.00,
        'desc' => 'Fluffy flattened rice tempered with mustard seeds, curry leaves, green chilies, onions & crunchy roasted peanuts.',
        'image' => 'https://images.unsplash.com/photo-1626777552726-4a6b54c97e46?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.7,
        'prep' => '5-6 mins'
    ],
    [
        'name' => 'Aloo Paratha with Curd & Pickle',
        'slug' => 'breakfast',
        'price' => 75.00,
        'desc' => 'Whole wheat flatbread stuffed with spiced mashed potatoes, griddle roasted with butter, served with curd.',
        'image' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.8,
        'prep' => '8-10 mins'
    ],
    [
        'name' => 'Paneer Stuffed Paratha Combo',
        'slug' => 'breakfast',
        'price' => 90.00,
        'desc' => 'Crispy paratha packed with spiced grated cottage cheese, served with fresh mint raita & mango pickle.',
        'image' => 'https://images.unsplash.com/photo-1626777552726-4a6b54c97e46?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.9,
        'prep' => '8-10 mins'
    ],
    [
        'name' => 'Puri Bhaji Cafeteria Plate',
        'slug' => 'breakfast',
        'price' => 70.00,
        'desc' => 'Four puffy golden puris served with mild spiced potato curry, pickled chilies & crunchy onion rings.',
        'image' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.7,
        'prep' => '6-8 mins'
    ],
    [
        'name' => 'Upma with Podi Ghee & Chutney',
        'slug' => 'breakfast',
        'price' => 50.00,
        'desc' => 'Roasted semolina cooked with ginger, mustard seeds, cashews & vegetables, topped with spiced gun powder.',
        'image' => 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.6,
        'prep' => '5-7 mins'
    ],
    [
        'name' => 'Chole Bhature Classic Duo',
        'slug' => 'breakfast',
        'price' => 95.00,
        'desc' => 'Two oversized balloon bhaturas paired with slow-cooked Amritsari dark chickpea curry, pickled onions & lemon.',
        'image' => 'https://images.unsplash.com/photo-1626777552726-4a6b54c97e46?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.9,
        'prep' => '10-12 mins'
    ],
    [
        'name' => 'Methi Thepla with Chunda',
        'slug' => 'breakfast',
        'price' => 55.00,
        'desc' => 'Thin spiced Gujarati flatbreads flavored with fresh fenugreek leaves, served with sweet mango chunda & curd.',
        'image' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.5,
        'prep' => '5-6 mins'
    ],
    [
        'name' => 'Cheese Corn Toasties',
        'slug' => 'breakfast',
        'price' => 60.00,
        'desc' => 'Golden toasted bread layered with sweet corn kernels, melted mozzarella, bell peppers and oregano seasoning.',
        'image' => 'https://images.unsplash.com/photo-1528735602780-2552fd46c7af?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.6,
        'prep' => '6-8 mins'
    ],
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
        'image' => 'https://images.unsplash.com/photo-1541592106381-b31e9677c0e5?auto=format&fit=crop&w=600&q=80',
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
        'name' => 'Crispy Vegetable Spring Rolls',
        'slug' => 'snacks',
        'price' => 70.00,
        'desc' => 'Golden fried pastry rolls packed with shredded cabbage, carrots, bell peppers, served with sweet chili dip.',
        'image' => 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.6,
        'prep' => '8-10 mins'
    ],
    [
        'name' => 'Cheesy Garlic Breadsticks (4 pcs)',
        'slug' => 'snacks',
        'price' => 80.00,
        'desc' => 'Warm oven-baked bread glazed with garlic herb butter, bubbling mozzarella cheese and chili flakes.',
        'image' => 'https://images.unsplash.com/photo-1573140247632-f8fd74997d5c?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.7,
        'prep' => '6-8 mins'
    ],
    [
        'name' => 'Punjabi Samosa Chaat',
        'slug' => 'snacks',
        'price' => 55.00,
        'desc' => 'Crushed spiced potato samosas topped with hot ragda chickpeas, sweet yogurt, tamarind & green chutneys.',
        'image' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.9,
        'prep' => '4-6 mins'
    ],
    [
        'name' => 'Dahi Puri Crispy Bombs (6 pcs)',
        'slug' => 'snacks',
        'price' => 50.00,
        'desc' => 'Puffed puris stuffed with potatoes and sprouts, drenched in chilled sweet curd, sev and sweet tamarind sauce.',
        'image' => 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.8,
        'prep' => '3-5 mins'
    ],
    [
        'name' => 'Pav Bhaji with Extra Butter Pav',
        'slug' => 'snacks',
        'price' => 85.00,
        'desc' => 'Spicy mashed mixed vegetable gravy simmered on tawa with generous butter, served with 2 warm buttered pavs.',
        'image' => 'https://images.unsplash.com/photo-1626777552726-4a6b54c97e46?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.9,
        'prep' => '8-10 mins'
    ],
    [
        'name' => 'Crispy Onion Pakoda Plate',
        'slug' => 'snacks',
        'price' => 45.00,
        'desc' => 'Crunchy sliced onion fritters seasoned with carom seeds and green chilies, served with spicy mint chutney.',
        'image' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.6,
        'prep' => '5-7 mins'
    ],
    [
        'name' => 'Maggi Double Masala Cheese Bowl',
        'slug' => 'snacks',
        'price' => 50.00,
        'desc' => 'Classic 2-minute noodles tossed with butter sautéed veggies, extra secret masala and shredded cheese.',
        'image' => 'https://images.unsplash.com/photo-1612927601601-6638404737ce?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.8,
        'prep' => '5-7 mins'
    ],
    [
        'name' => 'Chili Cheese Toast Supreme',
        'slug' => 'snacks',
        'price' => 65.00,
        'desc' => 'Crusty sandwich bread topped with melted cheddar, chopped green chilies, capsicum and herbs.',
        'image' => 'https://images.unsplash.com/photo-1528735602780-2552fd46c7af?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.6,
        'prep' => '6-8 mins'
    ],
    [
        'name' => 'Corn & Cheese Quesadilla',
        'slug' => 'snacks',
        'price' => 95.00,
        'desc' => 'Folded toasted tortilla stuffed with spiced sweet corn, jalapeños, melted cheese blend with salsa dip.',
        'image' => 'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.7,
        'prep' => '8-10 mins'
    ],
    [
        'name' => 'Crispy Veg Manchurian Dry',
        'slug' => 'snacks',
        'price' => 85.00,
        'desc' => 'Deep-fried vegetable dumplings tossed in garlic, ginger, soy sauce, spring onions and chili peppers.',
        'image' => 'https://images.unsplash.com/photo-1563245372-f21724e3856d?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.7,
        'prep' => '8-10 mins'
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
        'name' => 'Classic Paneer Butter Masala Rice Bowl',
        'slug' => 'main-course',
        'price' => 135.00,
        'desc' => 'Velvety rich tomato butter gravy with soft paneer cubes served over steaming jeera rice with pickled onions.',
        'image' => 'https://images.unsplash.com/photo-1588166524941-3bf61a9c41db?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.8,
        'prep' => '10-12 mins'
    ],
    [
        'name' => 'Dal Makhani with 2 Butter Naan',
        'slug' => 'main-course',
        'price' => 130.00,
        'desc' => 'Overnight slow-simmered black lentils enriched with cream and butter, paired with tandoor-baked naan.',
        'image' => 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.9,
        'prep' => '10-12 mins'
    ],
    [
        'name' => 'Kadai Paneer Combo with Jeera Rice',
        'slug' => 'main-course',
        'price' => 145.00,
        'desc' => 'Cottage cheese batons cooked with crunchy bell peppers, onions and fresh ground coriander seeds.',
        'image' => 'https://images.unsplash.com/photo-1588166524941-3bf61a9c41db?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.7,
        'prep' => '10-12 mins'
    ],
    [
        'name' => 'Rajma Masala Chawal Bowl',
        'slug' => 'main-course',
        'price' => 95.00,
        'desc' => 'North Indian style red kidney beans slow-cooked in thick onion-tomato gravy, served on fragrant basmati rice.',
        'image' => 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.8,
        'prep' => '8-10 mins'
    ],
    [
        'name' => 'Chole Kulche Street Combo',
        'slug' => 'main-course',
        'price' => 90.00,
        'desc' => 'Spiced tangy chickpea gravy with chopped onions, green chilies, served with 2 fluffy butter-toasted kulchas.',
        'image' => 'https://images.unsplash.com/photo-1626777552726-4a6b54c97e46?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.8,
        'prep' => '6-8 mins'
    ],
    [
        'name' => 'Palak Paneer with Tandoori Roti',
        'slug' => 'main-course',
        'price' => 125.00,
        'desc' => 'Fresh pureed spinach simmered with aromatic garlic, cottage cheese cubes, accompanied by 2 whole wheat rotis.',
        'image' => 'https://images.unsplash.com/photo-1588166524941-3bf61a9c41db?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.6,
        'prep' => '8-10 mins'
    ],
    [
        'name' => 'Veg Hakka Noodles with Manchurian',
        'slug' => 'main-course',
        'price' => 120.00,
        'desc' => 'Wok-tossed noodles with julienne veggies, garlic and soy, paired with saucy vegetable Manchurian bowl.',
        'image' => 'https://images.unsplash.com/photo-1612927601601-6638404737ce?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.8,
        'prep' => '10-12 mins'
    ],
    [
        'name' => 'Schezwan Fried Rice with Crispy Paneer',
        'slug' => 'main-course',
        'price' => 125.00,
        'desc' => 'Spicy wok-fried rice seasoned with fiery Schezwan chili paste, crunchy scallions and golden fried paneer cubes.',
        'image' => 'https://images.unsplash.com/photo-1563245372-f21724e3856d?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.7,
        'prep' => '8-10 mins'
    ],
    [
        'name' => 'Penne Arrabbiata Red Sauce Pasta',
        'slug' => 'main-course',
        'price' => 130.00,
        'desc' => 'Italian durum wheat penne tossed in spicy garlic marinara, fresh basil, black olives and parmesan flakes.',
        'image' => 'https://images.unsplash.com/photo-1551183053-bf91a1d81141?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.7,
        'prep' => '10-12 mins'
    ],
    [
        'name' => 'Creamy Alfredo White Sauce Pasta',
        'slug' => 'main-course',
        'price' => 140.00,
        'desc' => 'Penne simmered in garlic parmesan cheese sauce with broccoli florets, sweet corn and mushrooms.',
        'image' => 'https://images.unsplash.com/photo-1546549032-9571cd6b27df?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.8,
        'prep' => '10-12 mins'
    ],
    [
        'name' => 'Shahi Malai Kofta with Laccha Paratha',
        'slug' => 'main-course',
        'price' => 155.00,
        'desc' => 'Melt-in-mouth potato cottage cheese dumplings in rich cashew nut saffron sauce with flaky paratha.',
        'image' => 'https://images.unsplash.com/photo-1588166524941-3bf61a9c41db?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.9,
        'prep' => '12-14 mins'
    ],
    [
        'name' => 'Mushroom Matar Masala Meal',
        'slug' => 'main-course',
        'price' => 125.00,
        'desc' => 'Fresh button mushrooms and sweet green peas braised in spiced onion-tomato gravy with basmati rice.',
        'image' => 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.6,
        'prep' => '10-12 mins'
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
        'name' => 'Cold Brew Iced Coffee',
        'slug' => 'beverages',
        'price' => 70.00,
        'desc' => '16-hour slow steep cold brew Arabica coffee served over ice with a touch of condensed milk.',
        'image' => 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.8,
        'prep' => '2-4 mins'
    ],
    [
        'name' => 'Alphonso Mango Thick Shake',
        'slug' => 'beverages',
        'price' => 85.00,
        'desc' => 'Thick luscious shake made with real Ratnagiri Alphonso mango pulp, whole milk and vanilla ice cream.',
        'image' => 'https://images.unsplash.com/photo-1553530666-ba11a7da3888?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.9,
        'prep' => '4-6 mins'
    ],
    [
        'name' => 'Belgian Chocolate Milkshake',
        'slug' => 'beverages',
        'price' => 90.00,
        'desc' => 'Rich decadent chocolate shake crowned with chocolate chips, chocolate syrup drizzle and whipped cream.',
        'image' => 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.9,
        'prep' => '4-6 mins'
    ],
    [
        'name' => 'Sweet Lassi in Clay Kulhad',
        'slug' => 'beverages',
        'price' => 50.00,
        'desc' => 'Churned creamy Punjabi yogurt beverage topped with saffron strands, crushed pistachios and cardamom.',
        'image' => 'https://images.unsplash.com/photo-1553530666-ba11a7da3888?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.8,
        'prep' => '3-5 mins'
    ],
    [
        'name' => 'Peach Iced Tea Cooler',
        'slug' => 'beverages',
        'price' => 60.00,
        'desc' => 'Refreshing brewed black tea blended with sweet white peach syrup, lemon slices and fresh mint.',
        'image' => 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?auto=format&fit=crop&w=600&q=80',
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
    ],
    [
        'name' => 'Gulab Jamun with Rabri (2 pcs)',
        'slug' => 'desserts',
        'price' => 60.00,
        'desc' => 'Soft fried milk solid dumplings soaked in rose cardamom syrup, served with thickened sweetened rabri.',
        'image' => 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.9,
        'prep' => '3-5 mins'
    ],
    [
        'name' => 'New York Baked Cheesecake Slice',
        'slug' => 'desserts',
        'price' => 110.00,
        'desc' => 'Creamy velvety cheesecake on a buttery graham cracker crust with sweet berry compote drizzle.',
        'image' => 'https://images.unsplash.com/photo-1533134242443-d4fd215305ad?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.8,
        'prep' => '2-3 mins'
    ],
    [
        'name' => 'Warm Apple Pie Tart',
        'slug' => 'desserts',
        'price' => 80.00,
        'desc' => 'Spiced cinnamon glazed apple slices inside a golden flaky pastry crust, dusted with powdered sugar.',
        'image' => 'https://images.unsplash.com/photo-1535920527002-b35e96722eb9?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.7,
        'prep' => '4-6 mins'
    ],
    [
        'name' => 'Choco Lava Warm Melt Cake',
        'slug' => 'desserts',
        'price' => 85.00,
        'desc' => 'Moist cocoa sponge cake with an oozing liquid chocolate center served piping hot.',
        'image' => 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 4.9,
        'prep' => '5-7 mins'
    ],
    [
        'name' => 'Campus Grand Veg Maharaja Platter',
        'slug' => 'specials',
        'price' => 199.00,
        'desc' => 'Mini paneer biryani, dal makhani, paneer tikka, butter naan, salad, gulab jamun and cold drink.',
        'image' => 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?auto=format&fit=crop&w=600&q=80',
        'veg' => 1,
        'rating' => 5.0,
        'prep' => '15-18 mins'
    ],

    // --- 52 NON-VEG ITEMS ---
    [
        'name' => 'Double Egg Cheese Omelette Toast',
        'slug' => 'breakfast',
        'price' => 65.00,
        'desc' => 'Fluffy two-egg omelette with onions, tomatoes, green chilies and melted cheese served with buttered toast.',
        'image' => 'https://images.unsplash.com/photo-1525351484163-7529414344d8?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.8,
        'prep' => '6-8 mins'
    ],
    [
        'name' => 'Chicken Sausage & Egg Scramble Plate',
        'slug' => 'breakfast',
        'price' => 95.00,
        'desc' => 'Sliced smoked chicken sausages tossed with butter scrambled eggs, grilled tomatoes and sourdough.',
        'image' => 'https://images.unsplash.com/photo-1533089860892-a7c6f0a88666?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.7,
        'prep' => '8-10 mins'
    ],
    [
        'name' => 'Egg Bhurji Pav Double Special',
        'slug' => 'breakfast',
        'price' => 70.00,
        'desc' => 'Spicy scrambled eggs cooked with chopped onions, tomatoes, ginger and green coriander, served with 2 warm pavs.',
        'image' => 'https://images.unsplash.com/photo-1525351484163-7529414344d8?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.9,
        'prep' => '6-8 mins'
    ],
    [
        'name' => 'Chicken Keema Paratha with Raita',
        'slug' => 'breakfast',
        'price' => 110.00,
        'desc' => 'Crispy whole wheat paratha stuffed with aromatic minced chicken masala, served with chilled raita.',
        'image' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.8,
        'prep' => '8-10 mins'
    ],
    [
        'name' => 'Boiled Egg Protein Chaat (3 Eggs)',
        'slug' => 'breakfast',
        'price' => 50.00,
        'desc' => 'Hard-boiled farm eggs seasoned with black pepper, chaat masala, lemon juice, diced onions and tomatoes.',
        'image' => 'https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.6,
        'prep' => '4-5 mins'
    ],
    [
        'name' => 'Egg Mayo Club Sandwich',
        'slug' => 'breakfast',
        'price' => 75.00,
        'desc' => 'Toasted multi-grain bread layered with creamy egg salad, crisp iceberg lettuce and sliced cucumbers.',
        'image' => 'https://images.unsplash.com/photo-1528735602780-2552fd46c7af?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.7,
        'prep' => '6-8 mins'
    ],
    [
        'name' => 'Grilled Chicken Breakfast Wrap',
        'slug' => 'breakfast',
        'price' => 100.00,
        'desc' => 'Tender shredded chicken breast, scrambled egg whites and mustard mayo wrapped in a soft tortilla.',
        'image' => 'https://images.unsplash.com/photo-1626777552726-4a6b54c97e46?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.8,
        'prep' => '8-10 mins'
    ],
    [
        'name' => 'French Toast with Honey & Fried Egg',
        'slug' => 'breakfast',
        'price' => 80.00,
        'desc' => 'Golden egg-washed pan-toasted brioche slices drizzled with organic honey and a side of sunny-side-up egg.',
        'image' => 'https://images.unsplash.com/photo-1484723091739-30a097e8f929?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.6,
        'prep' => '7-9 mins'
    ],
    [
        'name' => 'Egg Roll with Tangy Onion Chutney',
        'slug' => 'breakfast',
        'price' => 55.00,
        'desc' => 'Flaky pan-fried paratha lined with a beaten egg, filled with sliced onions, chaat masala and lime.',
        'image' => 'https://images.unsplash.com/photo-1626777552726-4a6b54c97e46?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.8,
        'prep' => '5-7 mins'
    ],
    [
        'name' => 'Chicken Salami & Cheese Croissant',
        'slug' => 'breakfast',
        'price' => 90.00,
        'desc' => 'Flaky butter croissant filled with sliced smoked chicken salami, cheddar cheese and Dijon mustard.',
        'image' => 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.7,
        'prep' => '5-7 mins'
    ],
    [
        'name' => 'Crispy Chicken Zinger Burger',
        'slug' => 'snacks',
        'price' => 120.00,
        'desc' => 'Buttermilk fried chicken breast fillet with crunchy coating, shredded lettuce and spicy garlic mayo.',
        'image' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.9,
        'prep' => '8-10 mins'
    ],
    [
        'name' => 'Chicken Tikka Kathi Roll',
        'slug' => 'snacks',
        'price' => 125.00,
        'desc' => 'Smoky tandoor-roasted chicken tikka pieces, thinly sliced pickled onions & mint yogurt wrapped in paratha.',
        'image' => 'https://images.unsplash.com/photo-1626777552726-4a6b54c97e46?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.9,
        'prep' => '10-12 mins'
    ],
    [
        'name' => 'Crispy Fried Chicken Popcorn',
        'slug' => 'snacks',
        'price' => 99.00,
        'desc' => 'Bite-sized boneless chicken chunks coated in peppery southern breading, fried golden with tartar dip.',
        'image' => 'https://images.unsplash.com/photo-1562967914-608f82629710?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.8,
        'prep' => '6-8 mins'
    ],
    [
        'name' => 'BBQ Smoked Chicken Wings (5 pcs)',
        'slug' => 'snacks',
        'price' => 135.00,
        'desc' => 'Oven-roasted juicy chicken wings glazed in sweet and smoky hickory BBQ sauce with sesame seeds.',
        'image' => 'https://images.unsplash.com/photo-1527477396000-e27163b481c2?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.8,
        'prep' => '10-12 mins'
    ],
    [
        'name' => 'Spicy Peri-Peri Chicken Pizza (7-inch)',
        'slug' => 'snacks',
        'price' => 180.00,
        'desc' => 'Hand-tossed pizza crust topped with peri-peri chicken cubes, red paprika, sliced onions and mozzarella.',
        'image' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.8,
        'prep' => '12-15 mins'
    ],
    [
        'name' => 'Chicken Keema Samosa (2 pcs)',
        'slug' => 'snacks',
        'price' => 60.00,
        'desc' => 'Triangular flaky pastry shells stuffed with spicy minced chicken, mint and coriander, served with chili sauce.',
        'image' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.7,
        'prep' => '5-7 mins'
    ],
    [
        'name' => 'Chicken Steamed Momos (6 pcs)',
        'slug' => 'snacks',
        'price' => 75.00,
        'desc' => 'Soft Tibetan style steamed dumplings filled with seasoned minced chicken, served with spicy chili garlic dip.',
        'image' => 'https://images.unsplash.com/photo-1534422298391-e4f8c172dddb?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.9,
        'prep' => '8-10 mins'
    ],
    [
        'name' => 'Crispy Fried Chicken Momos (6 pcs)',
        'slug' => 'snacks',
        'price' => 85.00,
        'desc' => 'Golden fried chicken dumplings with crunchy exterior, paired with fiery red dipping sauce and mayonnaise.',
        'image' => 'https://images.unsplash.com/photo-1534422298391-e4f8c172dddb?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.8,
        'prep' => '8-10 mins'
    ],
    [
        'name' => 'Chicken Cheese Loaded Nachos',
        'slug' => 'snacks',
        'price' => 110.00,
        'desc' => 'Crispy corn tortilla chips smothered in melted warm queso, spicy grilled chicken bits and jalapeño rings.',
        'image' => 'https://images.unsplash.com/photo-1513456852971-30c0b8199d4d?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.7,
        'prep' => '6-8 mins'
    ],
    [
        'name' => 'Tandoori Chicken Seekh Kebab',
        'slug' => 'snacks',
        'price' => 130.00,
        'desc' => 'Minced chicken blended with aromatic herbs and Indian spices, grilled on skewers with mint chutney.',
        'image' => 'https://images.unsplash.com/photo-1599488615731-7e5c2823ff28?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.9,
        'prep' => '10-12 mins'
    ],
    [
        'name' => 'Chicken Shawarma Pita Roll',
        'slug' => 'snacks',
        'price' => 110.00,
        'desc' => 'Thinly shaved spiced chicken rotisserie meat, creamy garlic tahini, pickled gherkins wrapped in Lebanese bread.',
        'image' => 'https://images.unsplash.com/photo-1626777552726-4a6b54c97e46?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.9,
        'prep' => '6-8 mins'
    ],
    [
        'name' => 'Chicken Nuggets with Dip (6 pcs)',
        'slug' => 'snacks',
        'price' => 85.00,
        'desc' => 'Tender seasoned chicken bites coated in golden batter, served with sweet chili sauce.',
        'image' => 'https://images.unsplash.com/photo-1562967914-608f82629710?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.6,
        'prep' => '5-7 mins'
    ],
    [
        'name' => 'Chili Chicken Dry Indo-Chinese',
        'slug' => 'snacks',
        'price' => 125.00,
        'desc' => 'Crispy fried boneless chicken pieces tossed with green chilies, capsicum, onions and dark soya sauce.',
        'image' => 'https://images.unsplash.com/photo-1563245372-f21724e3856d?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.9,
        'prep' => '8-10 mins'
    ],
    [
        'name' => 'Chicken Cheese Molten Balls (5 pcs)',
        'slug' => 'snacks',
        'price' => 90.00,
        'desc' => 'Crisp golden croquettes with a molten center of shredded chicken and gooey cheddar cheese.',
        'image' => 'https://images.unsplash.com/photo-1562967914-608f82629710?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.7,
        'prep' => '6-8 mins'
    ],
    [
        'name' => 'Royal Hyderabadi Chicken Dum Biryani',
        'slug' => 'main-course',
        'price' => 160.00,
        'desc' => 'Fragrant basmati rice layered with succulent bone-in chicken marinated in yogurt and spices, with mirchi ka salan.',
        'image' => 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 5.0,
        'prep' => '12-15 mins'
    ],
    [
        'name' => 'Boneless Chicken Tikka Biryani',
        'slug' => 'main-course',
        'price' => 175.00,
        'desc' => 'Charcoal roasted boneless chicken tikka cooked on dum with saffron rice, fried onions and cooling cucumber raita.',
        'image' => 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.9,
        'prep' => '12-15 mins'
    ],
    [
        'name' => 'Delhi Style Butter Chicken Rice Bowl',
        'slug' => 'main-course',
        'price' => 155.00,
        'desc' => 'Tender tandoori chicken cooked in rich makhani gravy enriched with butter and cream, over steaming basmati rice.',
        'image' => 'https://images.unsplash.com/photo-1588166524941-3bf61a9c41db?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.9,
        'prep' => '10-12 mins'
    ],
    [
        'name' => 'Kadai Chicken with 2 Butter Naan',
        'slug' => 'main-course',
        'price' => 160.00,
        'desc' => 'Bone-in chicken cooked in a robust wok gravy with freshly pounded coriander seeds, bell peppers and onions.',
        'image' => 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.8,
        'prep' => '10-12 mins'
    ],
    [
        'name' => 'Egg Curry Combo with Steamed Rice',
        'slug' => 'main-course',
        'price' => 100.00,
        'desc' => 'Two hard-boiled eggs simmered in homestyle spiced onion-tomato curry, served with fluffy basmati rice.',
        'image' => 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.7,
        'prep' => '8-10 mins'
    ],
    [
        'name' => 'Chicken Korma with Tandoori Roti',
        'slug' => 'main-course',
        'price' => 165.00,
        'desc' => 'Royal Mughlai chicken curry slow-simmered in a rich velvety cashew paste and yogurt gravy with 2 rotis.',
        'image' => 'https://images.unsplash.com/photo-1588166524941-3bf61a9c41db?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.8,
        'prep' => '10-12 mins'
    ],
    [
        'name' => 'Chicken Hakka Noodles with Chili Chicken',
        'slug' => 'main-course',
        'price' => 145.00,
        'desc' => 'Wok-tossed egg noodles with vegetables paired with spicy saucy Indo-Chinese chili chicken bowl.',
        'image' => 'https://images.unsplash.com/photo-1612927601601-6638404737ce?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.9,
        'prep' => '10-12 mins'
    ],
    [
        'name' => 'Chicken Fried Rice with Schezwan Chicken',
        'slug' => 'main-course',
        'price' => 150.00,
        'desc' => 'Classic egg and chicken fried rice tossed on high flame, paired with spicy Schezwan chicken gravy.',
        'image' => 'https://images.unsplash.com/photo-1563245372-f21724e3856d?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.8,
        'prep' => '10-12 mins'
    ],
    [
        'name' => 'Penne Chicken Arrabbiata Pasta',
        'slug' => 'main-course',
        'price' => 155.00,
        'desc' => 'Italian penne tossed in garlic herb tomato sauce with diced grilled chicken breasts, olives and chili flakes.',
        'image' => 'https://images.unsplash.com/photo-1551183053-bf91a1d81141?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.7,
        'prep' => '10-12 mins'
    ],
    [
        'name' => 'Creamy Chicken Carbonara Pasta',
        'slug' => 'main-course',
        'price' => 165.00,
        'desc' => 'Fettuccine pasta in rich egg parmesan sauce with sautéed chicken chunks and cracked black pepper.',
        'image' => 'https://images.unsplash.com/photo-1546549032-9571cd6b27df?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.8,
        'prep' => '10-12 mins'
    ],
    [
        'name' => 'Crispy Amritsari Fish Fry',
        'slug' => 'main-course',
        'price' => 160.00,
        'desc' => 'Fresh river fish fillets marinated in carom seeds, gram flour and spices, deep fried golden with radish laccha.',
        'image' => 'https://images.unsplash.com/photo-1534939561126-855b8675edd7?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.8,
        'prep' => '10-12 mins'
    ],
    [
        'name' => 'Coastal Fish Curry with Steamed Rice',
        'slug' => 'main-course',
        'price' => 170.00,
        'desc' => 'Coastal style fish curry cooked in coconut milk, tamarind and mustard seeds, served with hot rice.',
        'image' => 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.7,
        'prep' => '12-14 mins'
    ],
    [
        'name' => 'Mutton Rogan Josh Rice Bowl',
        'slug' => 'main-course',
        'price' => 210.00,
        'desc' => 'Slow-braised tender goat meat in Kashmiri aromatic gravy with fennel and dry ginger, over saffron jeera rice.',
        'image' => 'https://images.unsplash.com/photo-1588166524941-3bf61a9c41db?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.9,
        'prep' => '14-16 mins'
    ],
    [
        'name' => 'Chicken Keema Matar with Pav (2 Pavs)',
        'slug' => 'main-course',
        'price' => 120.00,
        'desc' => 'Minced chicken cooked with green peas, cumin and whole garam masala, served with buttery toasted pavs.',
        'image' => 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.8,
        'prep' => '8-10 mins'
    ],
    [
        'name' => 'Chicken Chettinad with Malabar Parotta',
        'slug' => 'main-course',
        'price' => 165.00,
        'desc' => 'Fiery South Indian chicken curry made with roasted spices, star anise and coconut, served with 2 flaky parottas.',
        'image' => 'https://images.unsplash.com/photo-1588166524941-3bf61a9c41db?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.9,
        'prep' => '10-12 mins'
    ],
    [
        'name' => 'Chicken Steak in Pepper Sauce',
        'slug' => 'main-course',
        'price' => 180.00,
        'desc' => 'Pan-seared tender chicken breast served with creamy black pepper mushroom glaze and French fries.',
        'image' => 'https://images.unsplash.com/photo-1532550907401-a500c9a57435?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.8,
        'prep' => '12-15 mins'
    ],
    [
        'name' => 'Student Chicken Thali Combo',
        'slug' => 'main-course',
        'price' => 190.00,
        'desc' => 'Chicken curry, dry chicken fry, dal, jeera rice, 2 rotis, raita, papad and sweet gulab jamun.',
        'image' => 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.9,
        'prep' => '12-15 mins'
    ],
    [
        'name' => 'Egg Biryani with Salan & Raita',
        'slug' => 'main-course',
        'price' => 120.00,
        'desc' => 'Basmati rice dum-cooked with boiled spiced eggs, caramelized onions, mint leaves and saffron.',
        'image' => 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.7,
        'prep' => '10-12 mins'
    ],
    [
        'name' => 'Chicken Sukka with Neer Dosa',
        'slug' => 'main-course',
        'price' => 160.00,
        'desc' => 'Mangalorean style roasted chicken coated with grated coconut and toasted chili spices with 3 soft neer dosas.',
        'image' => 'https://images.unsplash.com/photo-1588166524941-3bf61a9c41db?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.8,
        'prep' => '10-12 mins'
    ],
    [
        'name' => 'Grilled Chicken Caesar Salad Bowl',
        'slug' => 'specials',
        'price' => 130.00,
        'desc' => 'Herb-marinated sliced chicken breast over crisp romaine lettuce, crunchy croutons, parmesan and Caesar dressing.',
        'image' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.7,
        'prep' => '8-10 mins'
    ],
    [
        'name' => 'Tandoori Whole Chicken Leg (2 pcs)',
        'slug' => 'specials',
        'price' => 150.00,
        'desc' => 'Charcoal roasted juicy chicken drumstick and thigh marinated in yogurt, Kashmiri red chili and tandoori spices.',
        'image' => 'https://images.unsplash.com/photo-1599488615731-7e5c2823ff28?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.9,
        'prep' => '12-15 mins'
    ],
    [
        'name' => 'Chicken Lasagna Al Forno',
        'slug' => 'specials',
        'price' => 175.00,
        'desc' => 'Layers of pasta sheets baked with rich chicken bolognese meat sauce, béchamel cream and molten mozzarella.',
        'image' => 'https://images.unsplash.com/photo-1551183053-bf91a1d81141?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.8,
        'prep' => '14-16 mins'
    ],
    [
        'name' => 'Butter Garlic Prawns Plate',
        'slug' => 'specials',
        'price' => 210.00,
        'desc' => 'Succulent peeled prawns tossed in melted garlic butter, parsley, lime juice and chili flakes.',
        'image' => 'https://images.unsplash.com/photo-1534939561126-855b8675edd7?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.8,
        'prep' => '10-12 mins'
    ],
    [
        'name' => 'Crispy Fried Fish & Chips',
        'slug' => 'specials',
        'price' => 165.00,
        'desc' => 'Panko-crusted white fish fillet served with thick-cut salted fries, lemon wedges and homemade tartar sauce.',
        'image' => 'https://images.unsplash.com/photo-1534939561126-855b8675edd7?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.7,
        'prep' => '10-12 mins'
    ],
    [
        'name' => 'Campus Grand Non-Veg Maharaja Feast',
        'slug' => 'specials',
        'price' => 249.00,
        'desc' => 'Chicken dum biryani, butter chicken bowl, chicken tikka (2 pcs), butter naan, raita, brownie and cold beverage.',
        'image' => 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 5.0,
        'prep' => '15-18 mins'
    ],
    [
        'name' => 'Chicken Keema Loaded Fries Box',
        'slug' => 'specials',
        'price' => 140.00,
        'desc' => 'Crispy golden fries smothered in spicy minced chicken keema, molten cheese and jalapenos.',
        'image' => 'https://images.unsplash.com/photo-1630384060421-cb20d0e0649d?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.8,
        'prep' => '8-10 mins'
    ],
    [
        'name' => 'BBQ Pulled Chicken Sliders (2 pcs)',
        'slug' => 'specials',
        'price' => 115.00,
        'desc' => 'Mini brioche buns packed with slow-cooked shredded BBQ chicken, purple cabbage slaw and melted cheddar.',
        'image' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.7,
        'prep' => '8-10 mins'
    ],
    [
        'name' => 'Tandoori Non-Veg Platter Box',
        'slug' => 'specials',
        'price' => 220.00,
        'desc' => 'Assortment of chicken tikka (3 pcs), chicken seekh kebab (2 pcs) and fish tikka (2 pcs) with mint dip.',
        'image' => 'https://images.unsplash.com/photo-1599488615731-7e5c2823ff28?auto=format&fit=crop&w=600&q=80',
        'veg' => 0,
        'rating' => 4.9,
        'prep' => '14-16 mins'
    ]
];

echo "Total items in blueprint: " . count($all_items) . "\n";

// Clear existing items to prevent duplicates while preserving orders (order_details stores snapshots)
// We will upsert or insert these clean items
$stmt_check = $con->prepare("SELECT id FROM items WHERE name = ?");
$stmt_insert = $con->prepare("INSERT INTO items (category_id, name, price, description, image, is_available, is_veg, rating, prep_time, deleted) VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?, 0)");
$stmt_update = $con->prepare("UPDATE items SET category_id = ?, price = ?, description = ?, image = ?, is_available = 1, is_veg = ?, rating = ?, prep_time = ?, deleted = 0 WHERE id = ?");

$inserted = 0;
$updated = 0;

foreach ($all_items as $it) {
    $c_id = $cat_map[$it['slug']] ?? 1;
    $name = $it['name'];
    $price = $it['price'];
    $desc = $it['desc'];
    $img = $it['image'];
    $veg = $it['veg'];
    $rating = $it['rating'];
    $prep = $it['prep'];

    $stmt_check->bind_param("s", $name);
    $stmt_check->execute();
    $existing = $stmt_check->get_result()->fetch_assoc();

    if ($existing) {
        $id = $existing['id'];
        $stmt_update->bind_param("idssidsi", $c_id, $price, $desc, $img, $veg, $rating, $prep, $id);
        $stmt_update->execute();
        $updated++;
    } else {
        $stmt_insert->bind_param("isdssids", $c_id, $name, $price, $desc, $img, $veg, $rating, $prep);
        $stmt_insert->execute();
        $inserted++;
    }
}

echo "Inserted: $inserted items\n";
echo "Updated: $updated items\n";

// Count in database
$veg_cnt = $con->query("SELECT COUNT(*) as c FROM items WHERE is_veg = 1 AND deleted = 0")->fetch_assoc()['c'];
$nonveg_cnt = $con->query("SELECT COUNT(*) as c FROM items WHERE is_veg = 0 AND deleted = 0")->fetch_assoc()['c'];
$total_cnt = $con->query("SELECT COUNT(*) as c FROM items WHERE deleted = 0")->fetch_assoc()['c'];

echo "\n--- DATABASE VERIFICATION ---\n";
echo "Veg Items in DB:     $veg_cnt\n";
echo "Non-Veg Items in DB: $nonveg_cnt\n";
echo "Total Active Items:  $total_cnt\n";

if ($veg_cnt >= 50 && $nonveg_cnt >= 50 && $total_cnt >= 100) {
    echo "SUCCESS: Criteria met! (Veg >= 50, Non-Veg >= 50, Total >= 100)\n";
} else {
    echo "ERROR: Criteria not met!\n";
}
