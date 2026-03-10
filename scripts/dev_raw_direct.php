<?php
require __DIR__ . '/../APP/config.php';
$admin = new AdminController();
ob_start();
$admin->dashboard();
$html = ob_get_clean();
file_put_contents(__DIR__ . '/admin_raw_direct.html', $html);
echo "Saved to scripts/admin_raw_direct.html\n";
