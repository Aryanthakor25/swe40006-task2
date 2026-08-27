using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.Extensions.Options;

namespace CloudApp.Csharp.Pages;

public class ConfigModel : PageModel
{
    private readonly IConfiguration _configuration;
    private readonly ILogger<ConfigModel> _logger;

    public AppConfig Settings { get; }

    public ConfigModel(IOptions<AppConfig> settings, IConfiguration configuration, ILogger<ConfigModel> logger)
    {
        Settings = settings.Value;
        _configuration = configuration;
        _logger = logger;
    }

    public string? ConnectionString => _configuration.GetConnectionString("DefaultConnection");
    public bool HasConnectionString => !string.IsNullOrWhiteSpace(ConnectionString);
    public int ConnectionStringLength => ConnectionString?.Length ?? 0;

    public bool HasAppInsights =>
        !string.IsNullOrWhiteSpace(_configuration["APPLICATIONINSIGHTS_CONNECTION_STRING"]) ||
        !string.IsNullOrWhiteSpace(_configuration["ApplicationInsights:ConnectionString"]);

    public void OnGet()
    {
        _logger.LogInformation(
            "Configuration page viewed. ConnectionString present: {HasConn}. App Insights present: {HasAi}",
            HasConnectionString, HasAppInsights);
    }
}
