<?php
/**
 * Small runtime page - safer than exposing full phpinfo() publicly.
 * Useful as a screenshot proving PHP 8.x is the App Service runtime.
 */
declare(strict_types=1);

$rows = [
    'PHP version'        => PHP_VERSION,
    'Zend engine'        => zend_version(),
    'Server software'    => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    'Document root'      => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
    'Instance hostname'  => gethostname() ?: 'unknown',
    'Loaded extensions'  => implode(', ', array_slice(get_loaded_extensions(), 0, 25)) . ' ...',
    'Server UTC time'    => gmdate('Y-m-d H:i:s'),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Runtime - SWE40006 PHP App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header><div class="wrap"><strong>Runtime information</strong><nav><a href="/">Home</a></nav></div></header>
<main class="wrap">
    <div class="card">
        <table>
            <?php foreach ($rows as $label => $value): ?>
                <tr><th><?= htmlspecialchars((string) $label, ENT_QUOTES) ?></th>
                    <td><?= htmlspecialchars((string) $value, ENT_QUOTES) ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>
</main>
</body>
</html>
