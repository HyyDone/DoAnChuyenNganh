<?php
require_once __DIR__ . '/../config/db.php';

$listingId = $_GET['id'] ?? 1; // Default to ID 1

$stmt = $pdo->prepare("SELECT * FROM listings WHERE id = ?");
$stmt->execute([$listingId]);
$listing = $stmt->fetch();

if (!$listing) {
    echo "Listing not found.";
    exit;
}

$fullAddress = ($listing['address'] ?? '') . ', ' . ($listing['district'] ?? '') . ', ' . ($listing['city'] ?? '');
$mapUrl = "https://maps.google.com/maps?q=" . urlencode($fullAddress) . "&t=&z=15&ie=UTF8&iwloc=&output=embed";

echo "Full Address: " . $fullAddress . "<br>";
echo "Map URL: <a href='$mapUrl' target='_blank'>$mapUrl</a>";
?>
