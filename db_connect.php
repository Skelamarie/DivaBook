<?php
/**
 * db_connect.php
 * Central database connection and helper tools for DivaBook.
 */

// Bring in our Cloudinary tools
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Api\Admin\AdminApi;

// Load all our external tools (like Cloudinary and Dotenv)
require_once __DIR__ . '/vendor/autoload.php';

// ─── LOAD HIDDEN SECRETS ───────────────────────────────────────────────────────
// This looks for the hidden .env file in the folder right above this one
// Adjust the path '__DIR__ . "/.."' if your .env file is in a different spot!
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// ─── CLOUDINARY CONFIGURATION ──────────────────────────────────────────────────
// Connect to our image storage using the hidden keys from the .env file
Configuration::instance([
  'cloud' => [
    'cloud_name' => $_ENV['CLOUDINARY_CLOUD_NAME'], 
    'api_key'    => $_ENV['CLOUDINARY_API_KEY'], 
    'api_secret' => $_ENV['CLOUDINARY_API_SECRET']
  ],
  'url' => [
    'secure' => true
  ]
]);

// ─── MONGODB CONNECTION ────────────────────────────────────────────────────────
try {
    // Connect to our database using the hidden link from the .env file
    $uri    = $_ENV['MONGODB_URI'];
    $client = new MongoDB\Client($uri);
    $db     = $client->DivaBookDB;

    // Define our main data categories
    $partnersCollection = $db->partners;   // Business partner accounts
    $servicesCollection = $db->services;   // Services offered by partners
    $bookingsCollection = $db->bookings;   // Customer appointments

} catch (Exception $e) {
    die("Database Connection Error: " . $e->getMessage());
}

// ─── HELPER FUNCTIONS ──────────────────────────────────────────────────────────

/**
 * Get all business partners (Sorted by newest first)
 */
function getAllBoutiques(int $limit = 0): array {
    global $partnersCollection;
    $filter  = []; 
    $options = ['sort' => ['created_at' => -1]]; 
    if ($limit > 0) $options['limit'] = $limit;
    $cursor = $partnersCollection->find($filter, $options);
    return iterator_to_array($cursor, false);
}

/**
 * Get business partners that only offer a specific category (like 'nails')
 */
function getBoutiquesByCategory(string $category, int $limit = 0): array {
    global $partnersCollection;
    $filter  = ['biz_type' => strtolower($category)]; 
    $options = ['sort' => ['created_at' => -1]];
    if ($limit > 0) $options['limit'] = $limit;
    $cursor = $partnersCollection->find($filter, $options);
    return iterator_to_array($cursor, false);
}

/**
 * Get a few recommended partners to show on the Home Page
 */
function getRecommendedBoutiques($limit = 3) {
    global $partnersCollection;
    try {
        return $partnersCollection->find([], ['limit' => $limit])->toArray();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Find one specific business partner using their unique ID
 */
function getBoutiqueById(string $id): ?array {
    global $partnersCollection;
    try {
        $doc = $partnersCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
        return $doc ? iterator_to_array($doc) : null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Get all the services a specific partner offers
 */
function getServicesByBoutique(string $adminId): array {
    global $servicesCollection;
    try {
        $cursor = $servicesCollection->find(['admin_id' => $adminId]);
        return iterator_to_array($cursor, false);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Find one specific service using its unique ID
 */
function getServiceById(string $id): ?array {
    global $servicesCollection;
    try {
        $doc = $servicesCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
        return $doc ? iterator_to_array($doc) : null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Get a partner's custom profile details (like their name, logo, and colors)
 */
function getSalonProfileByAdminId(string $adminId): array {
    global $partnersCollection;
    try {
        $doc = $partnersCollection->findOne(['admin_id' => $adminId]);
        
        if ($doc) {
            $branding = isset($doc['branding']) ? (array)$doc['branding'] : [];
            
            return [
                'salon_name'      => $branding['salon_name'] ?? ($doc['shop_name'] ?? 'DivaBook'),
                'description'     => $branding['description'] ?? '',
                'credentials'     => $branding['credentials'] ?? '',
                'location'        => $branding['location'] ?? '',
                'contact_info'    => $branding['contact_info'] ?? '',
                'email'           => $branding['email'] ?? ($doc['email'] ?? ''),
                'facebook'        => $branding['facebook'] ?? '',
                'instagram'       => $branding['instagram'] ?? '',
                'logo_path'       => $branding['logo_path'] ?? '',
                'color_primary'   => $branding['color_primary'] ?? '#064E3B',
                'color_secondary' => $branding['color_secondary'] ?? '#7cbf7f',
            ];
        }
    } catch (Exception $e) {
        error_log("Profile fetch failed: " . $e->getMessage());
    }

    // Default backup information if we can't find the partner
    return [
        'salon_name' => 'DivaBook', 'description' => '', 'credentials' => '',
        'location' => '', 'contact_info' => '', 'email' => '', 'facebook' => '',
        'instagram' => '', 'logo_path' => '', 'color_primary' => '#064E3B', 
        'color_secondary' => '#7cbf7f',
    ];
}

/**
 * Completely delete a partner's account, including all their images and services
 */
function deletePartnerData($adminId) {
    global $db, $partnersCollection;
    $adminApi = new \Cloudinary\Api\Admin\AdminApi();

    try {
        // 1. Delete all their pictures from our image storage
        $folders = [
            "divabook_branding/$adminId",
            "divabook_services/$adminId",
            "divabook/$adminId"
        ];

        foreach ($folders as $folderPath) {
            try {
                $adminApi->deleteAssetsByPrefix($folderPath);
                $adminApi->deleteFolder($folderPath);
            } catch (Exception $e) {
                error_log("Cloudinary cleanup for $folderPath failed: " . $e->getMessage());
            }
        }

        // 2. Delete all their services and customer bookings from the database
        $db->services->deleteMany(['admin_id' => $adminId]);
        $db->bookings->deleteMany(['admin_id' => $adminId]);
        
        // 3. Delete their actual account
        $partnersCollection->deleteOne(['admin_id' => $adminId]);

        return true;
    } catch (Exception $e) {
        error_log("Account deletion failed: " . $e->getMessage());
        return false;
    }
}

// ─── UTILITY HELPERS (Small formatting tools) ──────────────────────────────────

// Get the text version of an ID
function mongoId($doc): string {
    return isset($doc['_id']) ? (string)$doc['_id'] : '';
}

// Draw star ratings
function renderStars(float $rating): string {
    $full = (int)round($rating);
    return str_repeat('★', $full) . str_repeat('☆', 5 - $full);
}

// Clean up text so it's safe to show on the screen
function h($str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

// Make dates look nice (like "May 28, 2026")
function formatDate($mongoDate, string $format = 'M d, Y'): string {
    return ($mongoDate instanceof MongoDB\BSON\UTCDateTime) ? $mongoDate->toDateTime()->format($format) : '';
}

// Add a dollar sign to prices
function formatPrice($price): string {
    return '$' . number_format((float)$price, 0) . '+';
}
?>