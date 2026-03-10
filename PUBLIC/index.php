<?php
// Public front controller shim
// If Apache's DocumentRoot is set to the `PUBLIC` folder, ensure requests are
// forwarded to the main front-controller at project root.
// This avoids a 403 when the directory has no index file.
require __DIR__ . '/../index.php';
