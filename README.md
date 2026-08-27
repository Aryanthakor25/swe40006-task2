# SWE40006 — Deployment Task 2: Web App Deployment to Azure

Source code for Task 2 (Pass → High Distinction). Two applications are deployed to
Azure App Service from Visual Studio 2026:

| Folder | Stack | Azure host |
|---|---|---|
| `CloudApp.Csharp/` | ASP.NET Core 8 Razor Pages | App Service (Windows, F1) |
| `CloudApp.Php/` | PHP 8.x | App Service (Linux, F1) |

Both applications send telemetry to a single Azure Application Insights resource.

## What each part demonstrates

**Task 2.2 — custom C# app.** `CloudApp.Csharp` is a .NET 8 Razor Pages app with a home
page, a configuration page and an error page.

**Task 2.3 — configuration and diagnostics.**
- No secrets or environment values in source. Everything is read through `IConfiguration`,
  populated in Azure from *Settings → Environment variables → App settings*:
  `AppConfig__WelcomeMessage`, `AppConfig__Environment`, `AppConfig__OwnerName`,
  `AppConfig__ApiKey`, `ConnectionStrings__DefaultConnection`.
- `/health` returns JSON from a real `IHealthCheck` (`ConfigurationHealthCheck.cs`) that
  reports *Degraded* when required app settings are missing.
- Console logging plus a request-logging middleware, so every hit appears live in the
  App Service **Log stream**.

**Task 2.4 — polyglot + Application Insights.**
- `CloudApp.Php` reads its settings with `getenv()` from the same kind of App Settings
  (`APP_WELCOME_MESSAGE`, `APP_ENVIRONMENT`, `APP_OWNER_NAME`, `APP_API_KEY`).
- `CloudApp.Php/health.php` returns 200/503 JSON.
- `CloudApp.Php/appinsights.php` posts server-side traces, events and request telemetry
  straight to the Application Insights ingestion REST API, and injects the browser
  JavaScript SDK for client-side page views. The connection string comes from the
  `APPLICATIONINSIGHTS_CONNECTION_STRING` app setting.
- `/boom` on the C# app throws on purpose so Application Insights → *Failures* has data.

## Running locally

```bash
# C#
cd CloudApp.Csharp
dotnet run

# PHP
cd CloudApp.Php
php -S localhost:8080
```

## App settings to create in Azure

**C# app (Windows plan)**

| Name | Example value |
|---|---|
| `AppConfig__WelcomeMessage` | Hello from Azure App Service |
| `AppConfig__Environment` | Azure-Production |
| `AppConfig__OwnerName` | Aryan Thakor |
| `AppConfig__ApiKey` | demo-key-1234 |
| `ConnectionStrings__DefaultConnection` | Server=demo;Database=demo;User Id=demo;Password=demo; |

**PHP app (Linux plan)**

| Name | Example value |
|---|---|
| `APP_WELCOME_MESSAGE` | Hello from the PHP app on Azure |
| `APP_ENVIRONMENT` | Azure-Production |
| `APP_OWNER_NAME` | Aryan Thakor |
| `APP_API_KEY` | demo-key-5678 |

Application Insights adds `APPLICATIONINSIGHTS_CONNECTION_STRING` automatically to both.
