<?php
// CLI only. Back up and add this provider without replacing existing application providers.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$path = __DIR__ . '/bootstrap/providers.php';
$code = file_get_contents($path);
if ($code === false) throw new RuntimeException('Unable to read bootstrap/providers.php');
$provider = 'App\\Providers\\SelfDriveDamageServiceProvider::class';
if (str_contains($code, $provider)) { echo "Self drive damage provider is already installed.\n"; exit; }
if (! preg_match('/return\s*\[/', $code)) throw new RuntimeException('Unexpected providers file. Add provider manually.');
$backupDir = __DIR__ . '/storage/app/private/update-backups';
if (! is_dir($backupDir) && ! mkdir($backupDir, 0700, true) && ! is_dir($backupDir)) throw new RuntimeException('Unable to create backup directory.');
$backup = $backupDir . '/providers-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.php';
if (! copy($path, $backup)) throw new RuntimeException('Unable to back up providers.');
$updated = preg_replace_callback('/return\s*\[/', fn () => "return [\n    " . $provider . ',', $code, 1);
if (file_put_contents($path, $updated, LOCK_EX) === false) throw new RuntimeException('Unable to write providers.');
echo "Provider installed. Keep the bootstrap/providers.php change with this update.\n";
