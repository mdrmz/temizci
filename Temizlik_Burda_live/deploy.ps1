param(
    [string]$Server = "192.168.1.51",
    [string]$User = "piksel",
    [string]$RemoteDir = "/var/www/html/Temizlik_Burda",
    [switch]$ResetDatabase,
    [switch]$SkipApache
)

$ErrorActionPreference = "Stop"

$LocalDir = Split-Path -Parent $MyInvocation.MyCommand.Path

function Run-SSH {
    param([string]$Cmd)
    ssh -o "ProxyCommand=none" "$User@$Server" $Cmd
    if ($LASTEXITCODE -ne 0) {
        throw "SSH komutu basarisiz: $Cmd"
    }
}

function Run-SCP {
    param([string]$Src, [string]$Dst)
    scp -o "ProxyCommand=none" -r $Src "$User@${Server}:$Dst"
    if ($LASTEXITCODE -ne 0) {
        throw "SCP komutu basarisiz: $Src"
    }
}

$excludeFiles = @(
    ".env",
    "deploy_temp_ed25519",
    "deploy_temp_ed25519.pub",
    "debug_db.php",
    "fix_users.php",
    "repair_db.php",
    "setup.php",
    "diag_output.txt"
)

$excludeDirs = @(
    ".git",
    "uploads",
    "logs",
    "qa",
    "reports"
)

Write-Host ""
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "  TEMIZCI BURADA - DEPLOYMENT" -ForegroundColor Cyan
Write-Host "  Hedef: $User@$Server" -ForegroundColor Yellow
Write-Host "  Kaynak: $LocalDir" -ForegroundColor Yellow
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "[1/5] Sunucuda dizin hazirlaniyor..." -ForegroundColor Green
Run-SSH "sudo mkdir -p $RemoteDir $RemoteDir/uploads/homes $RemoteDir/uploads/avatars $RemoteDir/logs && sudo chown -R ${User}:${User} $RemoteDir"

Write-Host "[2/5] Dosyalar gonderiliyor..." -ForegroundColor Green
$files = Get-ChildItem -LiteralPath $LocalDir -File | Where-Object { $excludeFiles -notcontains $_.Name }
foreach ($file in $files) {
    Write-Host "  -> $($file.Name)" -ForegroundColor Gray
    Run-SCP $file.FullName "$RemoteDir/"
}

$dirs = Get-ChildItem -LiteralPath $LocalDir -Directory | Where-Object { $excludeDirs -notcontains $_.Name }
foreach ($dir in $dirs) {
    Write-Host "  -> $($dir.Name)/" -ForegroundColor Gray
    Run-SCP $dir.FullName "$RemoteDir/"
}

Write-Host "[3/5] .env kontrol ediliyor..." -ForegroundColor Green
$localEnv = Join-Path $LocalDir ".env"
if (Test-Path -LiteralPath $localEnv) {
    Run-SCP $localEnv "$RemoteDir/.env"
    Write-Host "  -> .env guncellendi" -ForegroundColor Gray
} else {
    Write-Host "  -> Lokal .env yok; sunucudaki mevcut .env korunuyor" -ForegroundColor Yellow
}

Write-Host "[4/5] Dosya izinleri ayarlaniyor..." -ForegroundColor Green
$permCmds = @"
sudo chown -R www-data:www-data $RemoteDir
sudo chmod -R 755 $RemoteDir
sudo chmod -R 775 $RemoteDir/uploads $RemoteDir/logs
if [ -f $RemoteDir/.env ]; then sudo chmod 640 $RemoteDir/.env; fi
"@
Run-SSH $permCmds

Write-Host "[5/5] Veritabani ve Apache kontrolu..." -ForegroundColor Green
if ($ResetDatabase) {
    Write-Host "  -> ResetDatabase acik: database/database_full.sql uygulanacak" -ForegroundColor Yellow
    $dbCmds = @"
sudo mysql -e "CREATE USER IF NOT EXISTS 'temizci'@'localhost' IDENTIFIED BY '123456';"
sudo mysql -e "GRANT ALL PRIVILEGES ON temizlik_burda.* TO 'temizci'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
sudo mysql < $RemoteDir/database/database_full.sql
"@
    Run-SSH $dbCmds
} else {
    Write-Host "  -> Veritabani sifirlanmadi. Gerekirse -ResetDatabase ile calistirin." -ForegroundColor Gray
}

if (-not $SkipApache) {
    $apacheCmds = @"
sudo bash -c 'cat <<EOF > /etc/apache2/sites-available/000-default.conf
<VirtualHost *:80>
    ServerName temizciburada.com
    ServerAlias www.temizciburada.com
    DocumentRoot $RemoteDir

    <Directory $RemoteDir>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
EOF'
sudo a2enmod rewrite 2>/dev/null
sudo a2ensite 000-default.conf 2>/dev/null
sudo systemctl restart apache2
"@
    Run-SSH $apacheCmds
} else {
    Write-Host "  -> Apache ayari atlandi." -ForegroundColor Gray
}

Write-Host ""
Write-Host "=========================================" -ForegroundColor Green
Write-Host "  DEPLOYMENT TAMAMLANDI" -ForegroundColor Green
Write-Host "=========================================" -ForegroundColor Green
Write-Host ""
