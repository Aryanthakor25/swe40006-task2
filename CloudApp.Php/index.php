<?php
declare(strict_types=1);

$start = microtime(true);

require __DIR__ . '/appinsights.php';
$ai = new AppInsights('cloudapp-php');

/**
 * Task 2.3 / 2.4 - configuration comes from Azure App Service app settings
 * (Settings -> Environment variables), read here with getenv().
 * Nothing below is hardcoded.
 */
$welcome   = getenv('APP_WELCOME_MESSAGE') ?: '(APP_WELCOME_MESSAGE not configured)';
$owner     = getenv('APP_OWNER_NAME')      ?: '(APP_OWNER_NAME not configured)';
$envLabel  = getenv('APP_ENVIRONMENT')     ?: '(APP_ENVIRONMENT not configured)';
$apiKey    = getenv('APP_API_KEY')         ?: '';
$maskedKey = $apiKey === ''
    ? '(APP_API_KEY not configured)'
    : str_repeat('*', max(0, strlen($apiKey) - 4)) . substr($apiKey, -4);

$aiStatus = $ai->isConfigured() ? 'Connected' : 'Not configured';
$aiClass  = $ai->isConfigured() ? 'pill-ok' : 'pill-warn';

$ai->trackTrace('PHP home page rendered on ' . (gethostname() ?: 'unknown'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SWE40006 - PHP 8 Cloud App</title>
    <link rel="stylesheet" href="style.css">
    <?= $ai->javascriptSnippet() ?>
</head>
<body>
<header>
    <div class="wrap">
        <strong>SWE40006 &mdash; Task 2.4 Polyglot App (PHP <?= PHP_VERSION ?>)</strong>
        <nav>
            <a href="/">Home</a>
            <a href="/health.php">Health</a>
            <a href="/info.php">Runtime</a>
        </nav>
    </div>
</header>

<main class="wrap">
    <h1><?= htmlspecialchars($welcome, ENT_QUOTES) ?></h1>
    <p>
        This is the second application in the polyglot deployment: a PHP 8 site running on
        an Azure App Service for Linux plan, alongside the ASP.NET Core 8 app on a Windows plan.
        Both report telemetry into the same Application Insights resource.
    </p>

    <div class="card">
        <h2>Configuration read from Azure App Settings</h2>
        <table>
            <tr><th>APP_WELCOME_MESSAGE</th><td><?= htmlspecialchars($welcome, ENT_QUOTES) ?></td></tr>
            <tr><th>APP_OWNER_NAME</th><td><?= htmlspecialchars($owner, ENT_QUOTES) ?></td></tr>
            <tr><th>APP_ENVIRONMENT</th><td><?= htmlspecialchars($envLabel, ENT_QUOTES) ?></td></tr>
            <tr><th>APP_API_KEY (masked)</th><td><?= htmlspecialchars($maskedKey, ENT_QUOTES) ?></td></tr>
            <tr><th>Application Insights</th><td><span class="pill <?= $aiClass ?>"><?= $aiStatus ?></span></td></tr>
        </table>
    </div>

    <div class="card">
        <h2>Runtime information</h2>
        <table>
            <tr><th>PHP version</th><td><?= PHP_VERSION ?></td></tr>
            <tr><th>Server software</th><td><?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'unknown', ENT_QUOTES) ?></td></tr>
            <tr><th>Instance</th><td><code><?= htmlspecialchars(gethostname() ?: 'unknown', ENT_QUOTES) ?></code></td></tr>
            <tr><th>Server UTC time</th><td><?= gmdate('Y-m-d H:i:s') ?></td></tr>
        </table>
    </div>
</main>

<footer>
    <div class="wrap">Azure App Service for Linux &middot; rendered in <?= number_format((microtime(true) - $start) * 1000, 1) ?> ms</div>
</footer>
</body>
</html>
<?php
$durationMs = (int) round((microtime(true) - $start) * 1000);
$url = ($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
$ai->trackRequest('GET /', $url, $durationMs, 200);
