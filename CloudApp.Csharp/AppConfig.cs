namespace CloudApp.Csharp;

/// <summary>
/// Strongly typed settings. Values come from appsettings.json locally and from
/// Azure App Service "App settings" in the cloud - nothing is hardcoded.
/// Azure key names use a double underscore for the nesting, e.g.
///   AppConfig__WelcomeMessage
///   AppConfig__Environment
///   AppConfig__ApiKey
/// </summary>
public class AppConfig
{
    public string WelcomeMessage { get; set; } = "(not configured)";
    public string Environment { get; set; } = "(not configured)";
    public string ApiKey { get; set; } = string.Empty;
    public string OwnerName { get; set; } = "(not configured)";

    /// <summary>Never print a secret in full - show only the last 4 characters.</summary>
    public string MaskedApiKey =>
        string.IsNullOrWhiteSpace(ApiKey)
            ? "(not configured)"
            : new string('*', Math.Max(0, ApiKey.Length - 4)) + ApiKey[^Math.Min(4, ApiKey.Length)..];
}
