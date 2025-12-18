<?php
$f = 'public/listing_detail.php';
$c = file_get_contents($f);
$p = strpos($c, '</html>');
if ($p !== false) {
    file_put_contents($f, substr($c, 0, $p + 7));
    echo "Fixed " . $f . "\n";
} else {
    echo "</html> not found in " . $f . "\n";
}
