# Wdrożenie na VPS — przypierdolka.pl

Pliki projektu: **`/www/przypierdolka.pl`**  
Document root (ruch HTTP): **`/www/przypierdolka.pl/public`**

---

## Szybka checklista

1. Wgraj repozytorium do `/www/przypierdolka.pl`
2. Ustaw document root na `.../public`
3. Importuj **`database/install.sql`** (jedyny plik SQL)
4. Utwórz **`config/local.php`** z danymi bazy i sekretami
5. Uprawnienia na `storage/` i `public/assets/uploads/`
6. Zaloguj się, zmień hasła admin/moderator

---

## 1. Struktura katalogów na VPS

```
/www/przypierdolka.pl/              ← korzeń repozytorium (NIE document root)
/www/przypierdolka.pl/public/       ← tu wskazuje Nginx/Apache
/www/przypierdolka.pl/config/
/www/przypierdolka.pl/config/local.php   ← TWORZYSZ RĘCZNIE (nie ma w repo)
/www/przypierdolka.pl/storage/      ← cache + logi (zapisywalny)
/www/przypierdolka.pl/database/install.sql
```

---

## 2. Wymagania PHP

| Element | Wartość |
|---------|---------|
| PHP | **8.2+** |
| Rozszerzenia | `pdo_mysql`, `json`, `mbstring`, **`gd`** z **WebP** |
| Baza | MySQL 8 lub MariaDB 10.4+ |
| Composer | **opcjonalny** — działa bez `vendor/` |

Sprawdzenie:

```bash
php -v
php -m | grep -E 'pdo_mysql|mbstring|gd|json'
php -r "var_dump(function_exists('imagewebp'));"
```

Ostatnia linia musi zwrócić `bool(true)`.

---

## 3. Baza danych — jeden plik SQL

**Plik:** `/www/przypierdolka.pl/database/install.sql`  
Zawiera: schemat wszystkich tabel + kategorie + 3 historie + ustawienia + konta admin/moderator.

### Krok A — utwórz bazę (SSH, jako root MySQL)

```bash
sudo mysql
```

```sql
CREATE DATABASE `przypierdolka`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER 'przyp_user'@'localhost' IDENTIFIED BY 'TWOJE_SILNE_HASLO';
GRANT ALL PRIVILEGES ON `przypierdolka`.* TO 'przyp_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Zmień `przyp_user` i hasło na swoje.

### Krok B — edytuj nazwę bazy (jeśli inna niż `przypierdolka`)

W pliku `install.sql` linia:

```sql
USE `przypierdolka`;
```

### Krok C — import

```bash
mysql -u przyp_user -p przypierdolka < /www/przypierdolka.pl/database/install.sql
```

> Import zawiera `DROP TABLE` — używaj tylko na **pustej** bazie.

### Konta po imporcie (zmień hasła!)

| Login | Hasło domyślne | Panel |
|-------|----------------|-------|
| `admin` | `admin1234` | `/admineu` |
| `moderator` | `moderator1234` | `/admineu` |

---

## 4. Konfiguracja — `config/local.php`

To **jedyny plik**, który musisz utworzyć i wypełnić na VPS.

```bash
cp /www/przypierdolka.pl/web/config/local.php.example \
   /www/przypierdolka.pl/web/config/local.php

nano /www/przypierdolka.pl/config/local.php
```

### Co wpisać gdzie

| Pole w `local.php` | Co wpisać |
|--------------------|-----------|
| `database.host` | `127.0.0.1` (lub socket, jeśli inny) |
| `database.database` | nazwa bazy, np. `przypierdolka` |
| `database.username` | login MySQL, np. `przyp_user` |
| `database.password` | hasło MySQL |
| `app.url` | `https://przypierdolka.pl` (bez `/` na końcu) |
| `app.env` | `production` |
| `app.debug` | `false` |
| `security.ip_salt` | losowy ciąg (patrz niżej) |
| `security.app_key` | drugi losowy ciąg |

### Generowanie sekretów

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Pierwszy wynik → `ip_salt`, drugi → `app_key`.

### Przykład gotowego `config/local.php`

```php
<?php

declare(strict_types=1);

return [
    'database' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'database' => 'przypierdolka',
        'username' => 'przyp_user',
        'password' => 'TWOJE_HASLO_MYSQL',
    ],
    'app' => [
        'env'   => 'production',
        'url'   => 'https://przypierdolka.pl',
        'debug' => false,
    ],
    'security' => [
        'ip_salt' => 'a1b2c3...64_znaki_hex',
        'app_key' => 'd4e5f6...inny_64_znaki_hex',
    ],
];
```

**Nie edytuj** `config/app.php` na serwerze — produkcję ustaw w `config/local.php` (URL, debug, baza).  
`config/database.php` jest w `.gitignore` — trzymaj lokalnie, nie commituj.

---

## 4b. Aktualizacja kodu (`git pull`)

Na VPS **nie zmieniaj ręcznie** plików z repozytorium (`config/app.php`, `.htaccess`). Dane serwera trzymaj w `config/local.php`.

### Błąd: „Your local changes would be overwritten by merge”

Jednorazowo (np. katalog `/www/przypierdolka.pl/web`):

```bash
cd /www/przypierdolka.pl/web

mkdir -p storage/backups
cp config/local.php storage/backups/local.php.bak
cp config/database.php storage/backups/database.php.bak

git restore config/app.php public/assets/uploads/.htaccess
git restore config/database.php

git pull origin main

cp storage/backups/database.php.bak config/database.php
# local.php jest w .gitignore — pull go nie rusza; na wszelki wypadek:
test -f config/local.php || cp storage/backups/local.php.bak config/local.php
```

### Kolejne aktualizacje

```bash
cd /www/przypierdolka.pl/web
chmod +x bin/vps-pull.sh   # raz, po pierwszym pull ze skryptem
./bin/vps-pull.sh
```

Skrypt robi kopię `local.php` / `database.php`, cofa lokalne diffy w plikach z gita, robi `pull` i przywraca `database.php` jeśli zniknął.

---

## 5. Serwer WWW

### Nginx (zalecane na VPS)

Plik np. `/etc/nginx/sites-available/przypierdolka.pl`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name przypierdolka.pl www.przypierdolka.pl;

    root /www/przypierdolka.pl/public;
    index index.php;

    # Po certbot dodaj blok listen 443 ssl ...

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~* \.(css|js|woff2?|ttf|otf|eot|svg|png|jpe?g|gif|webp|ico)$ {
        expires 1y;
        add_header Cache-Control "public, max-age=31536000, immutable";
        access_log off;
        try_files $uri =404;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;  # dostosuj wersję PHP
    }

    location ~ /\. {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/przypierdolka.pl /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

SSL (Let's Encrypt):

```bash
sudo certbot --nginx -d przypierdolka.pl -d www.przypierdolka.pl
```

### Apache

```apache
<VirtualHost *:80>
    ServerName przypierdolka.pl
    DocumentRoot /www/przypierdolka.pl/public

    <Directory /www/przypierdolka.pl/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/przypierdolka-error.log
    CustomLog ${APACHE_LOG_DIR}/przypierdolka-access.log combined
</VirtualHost>
```

`public/.htaccess` jest już w repozytorium.

---

## 6. Uprawnienia

Serwer WWW (PHP-FPM / Apache) musi **zapisywać** cache, logi i uploady — w tym **`uploads/generated/`** (obrazki share WebP).

```bash
cd /www/przypierdolka.pl/web

# utwórz katalogi, jeśli brakuje
mkdir -p storage/cache storage/logs
mkdir -p public/assets/uploads/{stories,generated,avatars}

# Nginx/Apache zwykle jako www-data (dostosuj użytkownika, jeśli inny):
sudo chown -R www-data:www-data storage/ public/assets/uploads/
sudo chmod -R 775 storage/ public/assets/uploads/
```

Sprawdzenie (jako użytkownik www-data):

```bash
sudo -u www-data touch /www/przypierdolka.pl/web/public/assets/uploads/generated/.write-test \
  && sudo -u www-data rm /www/przypierdolka.pl/web/public/assets/uploads/generated/.write-test \
  && echo "OK — zapis działa"
```

Jeśli `Permission denied` przy `imagewebp` — to właśnie ten problem (właściciel katalogu to root zamiast www-data).

---

## 7. Composer (opcjonalnie)

```bash
cd /www/przypierdolka.pl
composer install --no-dev --optimize-autoloader
```

Aplikacja działa też bez tego kroku.

---

## 8. Weryfikacja

```bash
curl -I https://przypierdolka.pl/
curl -I https://przypierdolka.pl/admineu
curl -I https://przypierdolka.pl/llms.txt
```

W przeglądarce:
1. Strona główna ładuje historie
2. Logowanie `admin` / `admin1234` → zmiana hasła
3. Panel `/admineu` → Ustawienia
4. Upload awatara na koncie (test GD/WebP)

---

## 9. Typowe problemy

| Objaw | Rozwiązanie |
|-------|-------------|
| Błąd 500, „Błąd połączenia z bazą” | Sprawdź `config/local.php` → database |
| 404 na podstronach | Document root = `public/`, nie korzeń |
| Brak CSS/JS / **obrazki historii** | `app.url` musi być `https://…` (nie `http://`) — mixed content |
| Awatar się nie zapisuje | `php-gd` + WebP, chmod na `uploads/avatars/` |
| Biała strona | Tymczasowo `debug => true`, sprawdź `storage/logs/app-*.log` |

---

## Pliki kluczowe

| Plik | Rola |
|------|------|
| `database/install.sql` | **Jeden plik** — pełna baza |
| `config/local.php` | Twoja konfiguracja produkcyjna |
| `config/local.php.example` | Szablon do skopiowania na VPS |
| `config/README.md` | Git vs serwer — co commitować |
| `public/index.php` | Front controller |
| `public/.htaccess` | Rewrite (Apache) |
