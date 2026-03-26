# ============================================================
# Temizci Burada — Linux Sunucu Deployment Script
# Hedef: piksel@192.168.1.51 (LAN)
# ============================================================

$SERVER     = "192.168.1.51"
$USER       = "piksel"
$LOCAL_DIR  = "c:\xampp\htdocs\dashboard\temizci"
$REMOTE_DIR = "/var/www/temizci"

# SSH config'de cloudflared ProxyCommand var ama LAN icin gerekli degil
# -o ProxyCommand=none ile bypass edelim
function Run-SSH { param([string]$Cmd) ssh -o "ProxyCommand=none" "$USER@$SERVER" $Cmd }
function Run-SCP { param([string]$Src, [string]$Dst) scp -o "ProxyCommand=none" -r $Src "$USER@${SERVER}:$Dst" }

Write-Host ""
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "  TEMIZCI BURADA — DEPLOYMENT" -ForegroundColor Cyan
Write-Host "  Hedef: $USER@$SERVER" -ForegroundColor Yellow
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

# ——— ADIM 1: Sunucuda dizin hazirla ———
Write-Host "[1/5] Sunucuda dizin hazirlaniyor..." -ForegroundColor Green
Run-SSH "sudo mkdir -p $REMOTE_DIR && sudo chown ${USER}:${USER} $REMOTE_DIR && sudo mkdir -p $REMOTE_DIR/uploads/homes $REMOTE_DIR/uploads/avatars $REMOTE_DIR/logs"

if ($LASTEXITCODE -ne 0) {
    Write-Host "HATA: Sunucuya baglanilamadi!" -ForegroundColor Red
    exit 1
}

# ——— ADIM 2: Dosyalari gonder ———
Write-Host "[2/5] Proje dosyalari gonderiliyor..." -ForegroundColor Green

# Ana dosyalar
$files = Get-ChildItem -Path $LOCAL_DIR -File | Where-Object { $_.Name -ne ".git" }
foreach ($f in $files) {
    Write-Host "  -> $($f.Name)" -ForegroundColor Gray
    Run-SCP $f.FullName "$REMOTE_DIR/"
}

# Dizinler (.git haric)
$dirs = Get-ChildItem -Path $LOCAL_DIR -Directory | Where-Object { $_.Name -notin @(".git", "uploads") }
foreach ($d in $dirs) {
    Write-Host "  -> $($d.Name)/" -ForegroundColor Gray
    Run-SCP $d.FullName "$REMOTE_DIR/"
}

# uploads klasorunu bos olarak olustur (buyuk dosyalari gonderme)
Write-Host "  -> uploads/ (bos yapilar)" -ForegroundColor Gray
Run-SSH "mkdir -p $REMOTE_DIR/uploads/homes $REMOTE_DIR/uploads/avatars"

# ——— ADIM 3: .env dosyasi ———
Write-Host "[3/5] .env dosyasi gonderiliyor..." -ForegroundColor Green
Run-SCP "$LOCAL_DIR\.env" "$REMOTE_DIR/.env"

# ——— ADIM 4: Dizin izinlerini ayarla ———
Write-Host "[4/5] Dosya izinleri ayarlaniyor..." -ForegroundColor Green
$permCmds = @"
sudo chown -R www-data:www-data $REMOTE_DIR
sudo chmod -R 755 $REMOTE_DIR
sudo chmod -R 775 $REMOTE_DIR/uploads $REMOTE_DIR/logs
sudo chmod 640 $REMOTE_DIR/.env
"@
Run-SSH $permCmds

# ——— ADIM 5: Veritabani kurulumu ———
Write-Host "[5/5] Veritabani kuruluyor..." -ForegroundColor Green
$dbCmds = @"
sudo mysql -e "CREATE USER IF NOT EXISTS 'temizci'@'localhost' IDENTIFIED BY '123456';"
sudo mysql -e "GRANT ALL PRIVILEGES ON temizlik_burda.* TO 'temizci'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
echo '--- Full Database Reset (Kapsamli Kurulum) ---'
sudo mysql < $REMOTE_DIR/database/database_full.sql
echo '--- Veritabani tamamlandi ---'
"@
Run-SSH $dbCmds

# ——— Apache VirtualHost Yapilandirmasi ———
Write-Host ""
Write-Host "Apache VirtualHost ayarlaniyor..." -ForegroundColor Yellow
$apacheCmds = @"
sudo bash -c 'cat <<EOF > /etc/apache2/sites-available/000-default.conf
<VirtualHost *:80>
    ServerName temizciburada.com
    ServerAlias www.temizciburada.com
    DocumentRoot $REMOTE_DIR

    <Directory $REMOTE_DIR>
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

Write-Host ""
Write-Host "=========================================" -ForegroundColor Green
Write-Host "  DEPLOYMENT TAMAMLANDI!" -ForegroundColor Green
Write-Host "=========================================" -ForegroundColor Green
Write-Host ""
Write-Host "NOT: Apache VirtualHost yapilandirmasi gerekebilir." -ForegroundColor Yellow
Write-Host "DocumentRoot: $REMOTE_DIR" -ForegroundColor Yellow
Write-Host ""
