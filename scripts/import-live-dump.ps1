param(
    [Parameter(Mandatory = $true)]
    [string]$DumpPath,

    [string]$Database = "workpulse_staging_copy",
    [string]$Host = "127.0.0.1",
    [int]$Port = 3306,
    [string]$Username = "root",
    [string]$Password = ""
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path -LiteralPath $DumpPath)) {
    throw "Dump file not found: $DumpPath"
}

$mysqlCommand = Get-Command mysql -ErrorAction SilentlyContinue
if (-not $mysqlCommand) {
    throw "mysql client not found in PATH. Install MySQL client or add it to PATH first."
}

$passwordArgs = @()
if ($Password -ne "") {
    $passwordArgs = @("-p$Password")
}

Write-Host "Creating local staging database '$Database' on $Host`:$Port..."
& $mysqlCommand.Source "-h$Host" "-P$Port" "-u$Username" @passwordArgs "-e" "CREATE DATABASE IF NOT EXISTS \`$Database\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

Write-Host "Importing dump into '$Database'..."
Get-Content -LiteralPath $DumpPath | & $mysqlCommand.Source "-h$Host" "-P$Port" "-u$Username" @passwordArgs $Database

Write-Host ""
Write-Host "Done."
Write-Host "Next steps:"
Write-Host "1. Copy .env.staging-copy.example to .env and adjust DB credentials if needed."
Write-Host "2. Run: php artisan optimize:clear"
Write-Host "3. Run: php artisan migrate --force"
Write-Host "4. Run: php artisan workpulse:staging:status"
