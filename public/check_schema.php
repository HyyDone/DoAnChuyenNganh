<?php
require_once __DIR__ . '/config/db.php';

try {
    echo "--- POSTS TABLE ---\n";
    $stmt = $pdo->query("DESCRIBE posts");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }

    echo "\n--- LISTINGS TABLE ---\n";
    $stmt = $pdo->query("DESCRIBE listings");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }

    echo "\n--- LISTING_IMAGES TABLE (Check if exists) ---\n";
    try {
        $stmt = $pdo->query("DESCRIBE listing_images");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
    } catch (Exception $e) {
        echo "listing_images table does not exist.\n";
    }

    echo "\n--- POST_IMAGES TABLE (Check if exists) ---\n";
    try {
        $stmt = $pdo->query("DESCRIBE post_images");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
    } catch (Exception $e) {
        echo "post_images table does not exist.\n";
    }

} catch (PDOException $e) {
    echo "DB Error: " . $e->getMessage();
}
?>
