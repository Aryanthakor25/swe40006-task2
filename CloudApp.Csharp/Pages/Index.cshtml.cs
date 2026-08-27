using System.Runtime.InteropServices;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.Extensions.Options;

namespace CloudApp.Csharp.Pages;

public class IndexModel : PageModel
{
    private readonly ILogger<IndexModel> _logger;
    private readonly IWebHostEnvironment _environment;

    public AppConfig Settings { get; }
    public string MachineName => Environment.MachineName;
    public string HostEnvironmentName => _environment.EnvironmentName;
    public string FrameworkDescription => RuntimeInformation.FrameworkDescription;
    public DateTime UtcNow => DateTime.UtcNow;

    public IndexModel(ILogger<IndexModel> logger, IOptions<AppConfig> settings, IWebHostEnvironment environment)
    {
        _logger = logger;
        _environment = environment;
        Settings = settings.Value;
    }

    public void OnGet()
    {
        _logger.LogInformation("Home page rendered on instance {Machine}", Environment.MachineName);
    }
}
