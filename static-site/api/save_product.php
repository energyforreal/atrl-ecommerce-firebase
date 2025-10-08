<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    $requiredFields = ['title', 'price', 'description'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Load existing products
    $productsFile = '../static-site/data/products.json';
    $products = [];
    
    if (file_exists($productsFile)) {
        $products = json_decode(file_get_contents($productsFile), true);
        if (!$products) {
            $products = [];
        }
    }
    
    // Generate new product ID
    $newId = 1;
    if (!empty($products)) {
        $maxId = max(array_column($products, 'id'));
        $newId = $maxId + 1;
    }
    
    // Create new product
    $newProduct = [
        'id' => $newId,
        'title' => $input['title'],
        'price' => (float)$input['price'],
        'featured' => $input['featured'] ?? false,
        'image' => $input['image'] ?? '../assets/product_images/default.jpg',
        'description' => $input['description'],
        'category' => $input['category'] ?? 'Electronics',
        'specifications' => [
            'powerOutput' => 'N/A',
            'ports' => 'N/A',
            'technology' => 'N/A',
            'input' => 'N/A',
            'certifications' => 'N/A',
            'safety' => 'N/A'
        ],
        'features' => [
            'High-quality product',
            'Reliable performance',
            'Great value for money'
        ],
        'safetyFeatures' => [
            'Quality tested',
            'Safe to use'
        ],
        'useCases' => [
            'Daily use',
            'Professional applications'
        ],
        'images' => [$input['image'] ?? '../assets/product_images/default.jpg'],
        'marketingImages' => [],
        'videos' => [],
        'testimonials' => [],
        'shipping' => [
            'free' => true,
            'deliveryTime' => '2-5 working days across India',
            'description' => '🚚 Enjoy FREE shipping!'
        ],
        'guarantees' => [
            '✅ Secure Checkout - Guaranteed safe transactions',
            '🔒 256-bit SSL Encryption - Your data is protected',
            '🚚 Fast & Reliable Shipping - Orders delivered quickly',
            '💳 Multiple Payment Methods - Pay securely'
        ]
    ];
    
    // Add to products array
    $products[] = $newProduct;
    
    // Save back to file
    $result = file_put_contents($productsFile, json_encode($products, JSON_PRETTY_PRINT));
    
    if ($result === false) {
        throw new Exception('Failed to save products file');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Product added successfully',
        'product' => $newProduct
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
