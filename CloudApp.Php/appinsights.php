<?php
/**
 * Minimal Application Insights sender for PHP 8.
 *
 * There is no supported first-party PHP SDK for Application Insights, so this
 * file posts telemetry straight to the Application Insights ingestion REST API.
 * The connection string is read from the APPLICATIONINSIGHTS_CONNECTION_STRING
 * app setting that Azure injects when you enable Application Insights on the
 * Web App - it is never hardcoded.
 */

declare(strict_types=1);

final class AppInsights
{
    private ?string $instrumentationKey = null;
    private string $endpoint = 'https://dc.services.visualstudio.com/v2/track';
    private string $roleName;

    public function __construct(string $roleName = 'cloudapp-php')
    {
        $this->roleName = $roleName;
        $connectionString = getenv('APPLICATIONINSIGHTS_CONNECTION_STRING') ?: '';

        // Connection string looks like:
        // InstrumentationKey=xxxx;IngestionEndpoint=https://<region>.in.applicationinsights.azure.com/;...
        foreach (explode(';', $connectionString) as $part) {
            $pair = explode('=', $part, 2);
            if (count($pair) !== 2) {
                continue;
            }
            $key = strtolower(trim($pair[0]));
            $value = trim($pair[1]);

            if ($key === 'instrumentationkey') {
                $this->instrumentationKey = $value;
            } elseif ($key === 'ingestionendpoint' && $value !== '') {
                $this->endpoint = rtrim($value, '/') . '/v2/track';
            }
        }
    }

    public function isConfigured(): bool
    {
        return $this->instrumentationKey !== null && $this->instrumentationKey !== '';
    }

    /** Records a page view / request against the Application Insights resource. */
    public function trackRequest(string $name, string $url, int $durationMs, int $responseCode): void
    {
        $this->send('Microsoft.ApplicationInsights.Request', 'RequestData', [
            'ver' => 2,
            'id' => bin2hex(random_bytes(8)),
            'name' => $name,
            'duration' => $this->formatDuration($durationMs),
            'responseCode' => (string) $responseCode,
            'success' => $responseCode < 400,
            'url' => $url,
        ]);
    }

    /** Records a custom event, e.g. "HealthCheckProbed". */
    public function trackEvent(string $name, array $properties = []): void
    {
        $this->send('Microsoft.ApplicationInsights.Event', 'EventData', [
            'ver' => 2,
            'name' => $name,
            'properties' => array_map('strval', $properties),
        ]);
    }

    /** Records a trace / log line that shows up under Logs -> traces. */
    public function trackTrace(string $message, int $severityLevel = 1): void
    {
        $this->send('Microsoft.ApplicationInsights.Message', 'MessageData', [
            'ver' => 2,
            'message' => $message,
            'severityLevel' => $severityLevel,
        ]);
    }

    private function formatDuration(int $ms): string
    {
        $seconds = intdiv($ms, 1000);
        $millis = $ms % 1000;
        return sprintf('00:00:%02d.%03d', $seconds, $millis);
    }

    private function send(string $telemetryName, string $baseType, array $baseData): void
    {
        if (!$this->isConfigured()) {
            return;
        }

        $payload = [
            'name' => $telemetryName,
            'time' => gmdate('Y-m-d\TH:i:s\Z'),
            'iKey' => $this->instrumentationKey,
            'tags' => [
                'ai.cloud.role' => $this->roleName,
                'ai.cloud.roleInstance' => gethostname() ?: 'unknown',
                'ai.operation.id' => bin2hex(random_bytes(8)),
            ],
            'data' => [
                'baseType' => $baseType,
                'baseData' => $baseData,
            ],
        ];

        $ch = curl_init($this->endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([$payload]),
            CURLOPT_TIMEOUT => 3,
        ]);
        curl_exec($ch);   // fail silently - telemetry must never break the page
        curl_close($ch);
    }

    /**
     * Browser-side telemetry snippet. Paste the returned HTML into <head> so
     * Application Insights also collects page views and browser performance.
     */
    public function javascriptSnippet(): string
    {
        $connectionString = getenv('APPLICATIONINSIGHTS_CONNECTION_STRING') ?: '';
        if ($connectionString === '') {
            return '';
        }
        $escaped = json_encode($connectionString);

        return <<<HTML
<script type="text/javascript" src="https://js.monitor.azure.com/scripts/b/ai.3.gbl.min.js"></script>
<script type="text/javascript">
  (function () {
    if (!window.Microsoft || !window.Microsoft.ApplicationInsights) { return; }
    var appInsights = new window.Microsoft.ApplicationInsights.ApplicationInsights({
      config: { connectionString: $escaped }
    });
    appInsights.loadAppInsights();
    appInsights.trackPageView();
    window.appInsights = appInsights;
  })();
</script>
HTML;
    }
}
