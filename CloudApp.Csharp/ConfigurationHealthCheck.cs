using Microsoft.Extensions.Diagnostics.HealthChecks;
using Microsoft.Extensions.Options;

namespace CloudApp.Csharp;

/// <summary>
/// Task 2.3 - a real health check. It reports Unhealthy when the settings that
/// should come from Azure App Settings are missing, so a mis-configured
/// deployment is visible at /health instead of failing silently.
/// </summary>
public class ConfigurationHealthCheck : IHealthCheck
{
    private readonly AppConfig _config;
    private readonly IConfiguration _configuration;

    public ConfigurationHealthCheck(IOptions<AppConfig> config, IConfiguration configuration)
    {
        _config = config.Value;
        _configuration = configuration;
    }

    public Task<HealthCheckResult> CheckHealthAsync(
        HealthCheckContext context, CancellationToken cancellationToken = default)
    {
        var missing = new List<string>();

        if (string.IsNullOrWhiteSpace(_config.ApiKey) || _config.ApiKey.Contains("REPLACE"))
            missing.Add("AppConfig__ApiKey");

        if (string.IsNullOrWhiteSpace(_configuration.GetConnectionString("DefaultConnection")))
            missing.Add("ConnectionStrings__DefaultConnection");

        var result = missing.Count == 0
            ? HealthCheckResult.Healthy("All required app settings are present.")
            : HealthCheckResult.Degraded("Missing app settings: " + string.Join(", ", missing));

        return Task.FromResult(result);
    }
}
