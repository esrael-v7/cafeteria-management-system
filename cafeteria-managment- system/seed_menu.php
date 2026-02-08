<?php
// Seed menu_items table with initial menu data (run once after create_db.php)
header('Content-Type: text/html; charset=utf-8');

$host = "localhost";
$db = "cafeteria";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// If menu already has data, don't insert duplicates
$countRes = $conn->query("SELECT COUNT(*) AS c FROM menu_items");
if ($countRes) {
    $row = $countRes->fetch_assoc();
    if ((int)($row['c'] ?? 0) > 0) {
        echo "<b>menu_items already has data. No seeding needed.</b><br>";
        echo "<a href='index.html'>Go to application</a>";
        $conn->close();
        exit;
    }
}

$menuItems = [
    // regular
    ["regular", "Rice & Chicken", "Steamed rice with spicy chicken", 180, "image/Smokin and Grillin with AB Recipes _ Cajun Chicken and Rice_ A Simple & Flavorful One-Pan Dinner _ Facebook.jpg"],
    ["regular", "Pasta Bolognese", "Pasta with meat sauce", 160, "image/Easy Spaghetti Bolognese with Beef Recipe.jpg"],
    ["regular", "Fried Rice", "Vegetable fried rice", 140, "image/Nasi Goreng (Indonesian Fried Rice).jpg"],
    ["regular", "Grilled Chicken", "Grilled chicken with sauce", 220, "image/download (9).jpg"],
    ["regular", "Beef Tibs", "Ethiopian beef tibs", 260, "image/Beef Tibs Recipe – Ethiopian Stir Fry — Eleni's Kitchen - Ethiopian Favorites.jpg"],
    ["regular", "Vegetable Plate", "Mixed vegetables", 130, "image/Air Fryer Mixed Vegetables_ 7 Secrets for Perfect Crunch - Wilingga Air Fryer Recipes.jpg"],
    ["regular", "Fish & Chips", "Fried fish with fries", 210, "image/🍟🐟Crispy Fish Fillet with Fries 🍟🐟.jpg"],
    ["regular", "Lasagna", "Layered meat lasagna", 190, "image/Million Dollar Lasagna Recipe.jpg"],
    ["regular", "Chicken Curry", "Spicy curry chicken", 200, "image/Delicious Indian Chicken Curry Recipe.jpg"],
    ["regular", "Rice & Beef", "Rice with beef stew", 185, "image/Delicious Beef Stew and Rice.jpg"],

    // fast
    ["fast", "Burger", "Beef burger with cheese", 120, "image/Crack Burgers_ Your New Go-To Burger.jpg"],
    ["fast", "Chicken Burger", "Crispy chicken burger", 130, "image/KFC-Style Original Crispy Chicken Burger.jpg"],
    ["fast", "Pizza", "Cheese pizza", 220, "image/The American Feast_🍔🇺🇸 _ Classic Cheese Pizza 🍕🧀 _ Facebook.jpg"],
    ["fast", "Sandwich", "Club sandwich", 90, "image/All-American Club Sandwich Recipe _ CDKitchen_com.jpg"],
    ["fast", "Fries", "Crispy french fries", 70, "image/Crispy french fries with ketchup isolated in a wooden table _ Premium AI-generated image.jpg"],
    ["fast", "Hot Dog", "Classic hot dog", 85, "image/download (10).jpg"],
    ["fast", "Chicken Wrap", "Chicken wrap roll", 110, "image/Crispy BBQ Chicken Wraps.jpg"],
    ["fast", "Fried Chicken", "Crispy fried chicken", 150, "image/Crispy Fried Chicken Wings Recipe_ A Simple, Flavorful Treat for Any Occasion.jpg"],
    ["fast", "Shawarma", "Chicken shawarma", 140, "image/Chicken Shawarma with Creamy Garlic Sauce.jpg"],
    ["fast", "Cheese Roll", "Cheese filled roll", 80, "image/Lebanese cheese rolls.jpg"],

    // dessert
    ["dessert", "Chocolate Cake", "Rich chocolate cake", 90, "image/Chocolate Mousse Cake.jpg"],
    ["dessert", "Ice Cream", "Vanilla ice cream", 70, "image/Homemade Vanilla Ice Cream.jpg"],
    ["dessert", "Fruit Salad", "Fresh fruit mix", 65, "image/Fruit Salad with Condensed Milk.jpg"],
    ["dessert", "Cupcake", "Soft cupcake", 50, "image/Vanilla Cupcakes with Sweet Buttercream Frosting.jpg"],
    ["dessert", "Cheesecake", "Creamy cheesecake", 95, "image/Traditionelle New York Käsekuchen Rezept Download pdf, Rezept Dessert, Käsekuchen Rezept digitaler Download, druckbare Dessert Karte, PDF Rezeptkarte.jpg"],
    ["dessert", "Brownie", "Chocolate brownie", 75, "image/Soupe lovers ( Recipes & Tips )🍲🍛🍜 _ 🍫 Ultimate Fudgy Brownies ✨ _ Facebook.jpg"],
    ["dessert", "Pancake", "Honey pancake", 85, "image/Homemade american pancakes with fresh blueberry, raspberries and honey_.jpg"],
    ["dessert", "Waffle", "Belgian waffle", 90, "image/Fluffy Belgian Waffles Light, Crispy, and Perfect Every Time.jpg"],
    ["dessert", "Donut", "Sugar donut", 45, "image/Simple Homemade Sugar Donuts - Let the Baking Begin!.jpg"],
    ["dessert", "Apple Pie", "Apple pie slice", 80, "image/10 Amazing Thanksgiving Pie Recipes!.jpg"],

    // hot drinks
    ["hot", "Espresso", "Strong espresso", 40, "image/Espresso anyone_.jpg"],
    ["hot", "Cappuccino", "Milk cappuccino", 55, "image/20_20 Mocha Cappuccino - F-Factor.jpg"],
    ["hot", "Latte", "Smooth latte", 60, "image/Latte Macchiato _ Recette automne hiver.jpg"],
    ["hot", "Macchiato", "Coffee macchiato", 50, "image/Caramel Macchiato.jpg"],
    ["hot", "Tea", "Hot tea", 30, "image/GOODS FROM CHINA-TEA.jpg"],
    ["hot", "Hot Chocolate", "Chocolate drink", 55, "image/Chocolat Chaud Fouetté _ La Recette Ultime et Facile - La Cuillère de Miel.jpg"],
    ["hot", "Americano", "Americano coffee", 45, "image/Americano Coffee_ 5 Surprising Benefits That Make It Better Than Regular Coffee.jpg"],
    ["hot", "Mocha", "Chocolate coffee", 65, "image/Caffe Mocha Magic For Chocolate Coffee Lovers - Happy Baking Days.jpg"],
    ["hot", "Milk Tea", "Tea with milk", 40, "image/Vanilla Cinnamon Milk Tea_ Cozy & Inviting Hot or Iced Drink.jpg"],
    ["hot", "Ginger Tea", "Ginger tea", 35, "image/25 Lemon and Ginger Tea Recipes for Immunity and Warmth.jpg"],

    // cold drinks
    ["cold", "Iced Coffee", "Cold coffee", 55, "image/Cold Brew Coffee Bliss_ Rich, Creamy, and Refreshingly Delicious.jpg"],
    ["cold", "Milkshake", "Vanilla milkshake", 70, "image/Vanilla Milkshake _ All Yum Cooks.jpg"],
    ["cold", "Smoothie", "Fruit smoothie", 75, "image/Recette Smoothie Fruit Congelé _ Facile et Savoureux en 5 Minutes.jpg"],
    ["cold", "Orange Juice", "Fresh juice", 50, "image/download (13).jpg"],
    ["cold", "Lemonade", "Cold lemonade", 45, "image/Lemonade Recipe.jpg"],
    ["cold", "Iced Tea", "Cold tea", 40, "image/Refreshing Instant Pot Iced Tea.jpg"],
    ["cold", "Soda", "Soft drink", 35, "image/Soft Drinks.jpg"],
];

$stmt = $conn->prepare("INSERT INTO menu_items (category, name, description, price, image_path, active) VALUES (?, ?, ?, ?, ?, 1)");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$inserted = 0;
foreach ($menuItems as $it) {
    [$category, $name, $desc, $price, $img] = $it;
    $stmt->bind_param("sssds", $category, $name, $desc, $price, $img);
    if ($stmt->execute()) {
        $inserted++;
    }
}

$stmt->close();
$conn->close();

echo "<b>Seed complete!</b> Inserted: " . $inserted . " menu items.<br>";
echo "<a href='index.html'>Go to application</a>";
?>

