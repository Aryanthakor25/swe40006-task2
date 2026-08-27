using System.Text.Json;
using Microsoft.Extensions.Diagnostics.HealthChecks;

var builder = WebApplication.CreateBuilder(args);

// ---------------------------------------------------------------------------
// Task 2.4 - Application Insights telemetry.
// The connection string is NEVER hardcoded. It is read from the app setting
// APPLICATIONINSIGHTS_CONNECTION_STRING, which Azure App Service injects for us
// once Application Insights is enabled on the Web App.
// ---------------------------------------------------------------------------
builder.Services.AddApplicationInsightsTelemetry();

// ---------------------------------------------------------------------------
// Task 2.3 - Configuration is bound from IConfiguration. Locally the values
// come from appsettings.json / user secrets; in Azure they come from
// App Service -> Settings -> Environment variables -> App settings.
// Nested keys use a double underscore in Azure, e.g. AppConfig__WelcomeMessage
// ---------------------------------------------------------------------------
builder.Services.Configure<AppConfig>(builder.Configuration.GetSection("AppConfig"));

builder.Services.AddRazorPages();

// ---------------------------------------------------------------------------
// Task 2.3 - Health checks. /health returns JSON so it can be screenshotted,
// and Azure App Service "Health check" can be pointed at the same path.
// ---------------------------------------------------------------------------
builder.Services.AddHealthChecks()
    .AddCheck("self", () => HealthCheckResult.Healthy("Web front end is responding."))
    .AddCheck<ConfigurationHealthCheck>("configuration");

// Log to the console so that the Azure "Log stream" blade shows live output.
builder.Logging.AddConsole();

var app = builder.Build();

if (!app.Environment.IsDevelopment())
{
    app.UseExceptionHandler("/Error");
    app.UseHsts();
}

app.UseHttpsRedirection();
app.UseStaticFiles();
app.UseRouting();

// Simple request logger - every hit shows up in the Azure log stream.
app.Use(async (context, next) =>
{
    var logger = context.RequestServices.GetRequiredService<ILoggerFactory>()
        .CreateLogger("RequestLogger");
    logger.LogInformation("Incoming request {Method} {Path} from {Ip}",
        context.Request.Method,
        context.Request.Path,
        context.Connection.RemoteIpAddress?.ToString() ?? "unknown");
    await next();
    logger.LogInformation("Completed {Path} with status {StatusCode}",
        context.Request.Path, context.Response.StatusCode);
});

app.MapRazorPages();

app.MapHealthChecks("/health", new Microsoft.AspNetCore.Diagnostics.HealthChecks.HealthCheckOptions
{
    ResponseWriter = async (context, report) =>
    {
        context.Response.ContentType = "application/json";
        var payload = new
        {
            status = report.Status.ToString(),
            totalDurationMs = report.TotalDuration.TotalMilliseconds,
            machine = Environment.MachineName,
            utcTime = DateTime.UtcNow,
            checks = report.Entries.Select(e => new
            {
                name = e.Key,
                status = e.Value.Status.ToString(),
                description = e.Value.Description
            })
        };
        await context.Response.WriteAsync(
            JsonSerializer.Serialize(payload, new JsonSerializerOptions { WriteIndented = true }));
    }
});

// Deliberate error endpoint so Application Insights "Failures" has something
// real to show in the report screenshots.
app.MapGet("/boom", (ILogger<Program> logger) =>
{
    logger.LogError("Deliberate test exception requested via /boom");
    throw new InvalidOperationException("Deliberate test exception for Application Insights.");
});

app.Logger.LogInformation("CloudApp.Csharp started in {Environment} on {Machine}",
    app.Environment.EnvironmentName, Environment.MachineName);

app.Run();
