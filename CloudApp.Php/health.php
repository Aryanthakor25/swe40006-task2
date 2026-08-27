<?php
/**
 * Health endpoint for the PHP app - the Linux equivalent of the /health
 * endpoint in the ASP.NET Core app. Returns HTTP 200 + JSON when the required
 * app settings are present, HTTP 503 when they are not.
 */
declare(strict_types=1);

require __DIR__ . '/appinsights.php';
$ai = new AppInsights('cloudapp-php');

header('Content-Type: application/json');

$required = ['APP_WELCOME_MESSAGE', 'APP_ENVIRONMENT', 'APP_API_KEY'];
$missing = [];
foreach ($required as $key) {
    if (getenv($key) === false || getenv($key) === '') {
        $missing[] = $key;
    }
}

$healthy = count($missing) === 0;
http_response_code($healthy ? 200 : 503);

$ai->trackEvent('HealthCheckProbed', [
    'status'  => $healthy ? 'Healthy' : 'Unhealthy',
    'missing' => implode(',', $missing),
]);

echo json_encode([
    'status'        => $healthy ? 'Healthy' : 'Unhealthy',
    'application'   => 'cloudapp-php',
    'phpVersion'    => PHP_VERSION,
    'instance'      => gethostname() ?: 'unknown',
    'utcTime'       => gmdate('c'),
    'missingSettings' => $missing,
    'appInsights'   => $ai->isConfigured() ? 'configured' : 'not configured',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
