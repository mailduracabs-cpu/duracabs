<?php

// Run from the Laravel project root: php install_customer_app_update.php
// Adds one route include without replacing the existing route file.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$path = __DIR__ . '/routes/api.php';
$include = "require __DIR__ . '/customer_app_updates.php';";
if (!is_file($path) || !is_file(__DIR__ . '/routes/customer_app_updates.php')) {
    fwrite(STDERR, "Copy the Laravel files into the project root first.\n");
    exit(1);
}
$existing = file_get_contents($path);
if ($existing === false) {
    fwrite(STDERR, "Unable to read routes/api.php.\n");
    exit(1);
}
if (strpos($existing, $include) !== false) {
    echo "App update route is already installed.\n";
    exit(0);
}
$backup = $path . '.before-app-update-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(3));
if (!copy($path, $backup)) {
    fwrite(STDERR, "Unable to back up routes/api.php; no changes made.\n");
    exit(1);
}
// Preserve PHP mode even if the existing routes file has a closing tag.
$prefix = str_ends_with(rtrim($existing), '?>') ? "\n<?php\n" : "\n";
$updated = $existing . $prefix . "\n// Customer app version checks (public metadata).\n" . $include . "\n";
if (file_put_contents($path, $updated, LOCK_EX) !== strlen($updated)) {
    copy($backup, $path);
    fwrite(STDERR, "Write failed; restored the backup.\n");
    exit(1);
}
echo "App update route installed. Existing routes preserved.\n";
