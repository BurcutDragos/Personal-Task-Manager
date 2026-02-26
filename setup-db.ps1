# Run this script as Administrator:
#   Right-click PowerShell -> "Run as Administrator", then:
#   cd "E:\Aplicatii lucru\rockna-task-manager\rockna-task-manager"
#   .\setup-db.ps1

$ErrorActionPreference = "Stop"

# 1. Install MySQL
Write-Host "Installing MySQL via Chocolatey..." -ForegroundColor Cyan
choco install mysql -y

# 2. Wait for the MySQL service to start
Write-Host "Waiting for MySQL service to start..." -ForegroundColor Cyan
$timeout = 30
$elapsed = 0
while ($elapsed -lt $timeout) {
    $svc = Get-Service -Name "MySQL*" -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($svc -and $svc.Status -eq "Running") { break }
    Start-Sleep -Seconds 2
    $elapsed += 2
}

# If service not auto-started, start it manually
$svc = Get-Service -Name "MySQL*" -ErrorAction SilentlyContinue | Select-Object -First 1
if ($svc -and $svc.Status -ne "Running") {
    Write-Host "Starting MySQL service..." -ForegroundColor Cyan
    Start-Service $svc.Name
    Start-Sleep -Seconds 3
}

# 3. Refresh PATH so mysql.exe is found
$env:PATH = [System.Environment]::GetEnvironmentVariable("PATH", "Machine") + ";" +
            [System.Environment]::GetEnvironmentVariable("PATH", "User")

# 4. Create the database
Write-Host "Creating database rockna_tasks..." -ForegroundColor Cyan
mysql -u root -e "CREATE DATABASE IF NOT EXISTS rockna_tasks CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

Write-Host ""
Write-Host "Done! MySQL is running and rockna_tasks database is ready." -ForegroundColor Green
Write-Host "Now run migrations from your normal terminal:" -ForegroundColor Yellow
Write-Host '  cd "E:\Aplicatii lucru\rockna-task-manager\rockna-task-manager"' -ForegroundColor Yellow
Write-Host "  php yii migrate --migrationPath=@app/migrations --interactive=0" -ForegroundColor Yellow
