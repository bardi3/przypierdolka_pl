# Wdrożenie przypierdolka.pl na serwer

Krótka instrukcja: co wgrać, gdzie wpisać dane i jak sprawdzić, że serwis działa.

---

## 1. Wymagania serwera

| Wymaganie | Wartość |
|-----------|---------|
| PHP | **8.2+** |
| Rozszerzenia | `pdo_mysql`, `json`, `mbstring`, **`gd`** (z obsługą **WebP**) |
| Baza | MySQL 8 lub MariaDB 10.4+ |
| Serwer WWW | Apache z `mod_rewrite` **lub** Nginx |
| Composer | **Opcjonalny** — aplikacja działa bez `vendor/` |

---

## 2. Wgranie plików

Wgraj całe repozytorium na serwer (FTP, SFTP, git clone).

**Document root musi wskazywać na katalog `public/`**, nie na korzeń projektu.

```
/home/user/przypierdolka/          ← korzeń repozytorium
/home/user/przypierdolka/public/  ← document root (tu wchodzi ruch HTTP)
```

Przykład Apache (VirtualHost):

```apache
DocumentRoot /home/user/przypierdolka/public
<Directory /home/user/przypierdolka/public>
    AllowOverride All
    Require all granted
</Directory>
```

Plik `public/.htaccess` jest już w repozytorium — przepisuje żądania do `index.php`.

---

## 3. Baza danych — import SQL

### Krok A: utwórz bazę w panelu hostingu

W cPanel / DirectAdmin / phpMyAdmin utwórz:
- bazę danych (np. `user_przypierdolka`)
- użytkownika MySQL z pełnymi uprawnieniami do tej bazy
- zapisz: **host**, **nazwa bazy**, **login**, **hasło**

### Krok B: edytuj plik przed importem

Otwórz `database/install.sql` i w linii:

```sql
USE `przypierdolka`;
```

zmień `przypierdolka` na **rzeczywistą nazwę bazy** z panelu hostingu.

Na VPS, jeśli baza nie istnieje, odkomentuj blok `CREATE DATABASE` na początku pliku.

### Krok C: import

**phpMyAdmin:** wybierz bazę → Import → wybierz `database/install.sql` → Wykonaj.

**SSH:**

```bash
mysql -u TWOJ_LOGIN -p TWOJA_BAZA < database/install.sql
```

> **Uwaga:** plik zawiera `DROP TABLE` — importuj tylko na **pustą** lub nową bazę.

### Dane startowe po imporcie

| Login | Hasło (domyślne) | Rola |
|-------|------------------|------|
| `admin` | `admin1234` | administrator |
| `moderator` | `moderator1234` | moderator |

**Zmień hasła natychmiast** po pierwszym logowaniu (konto → ustawienia).

Panel admina: `https://twoja-domena.pl/admineu`

---

## 4. Konfiguracja aplikacji — `config/local.php`

To najważniejszy krok. Plik **nie jest w repozytorium** — musisz go utworzyć ręcznie.

```bash
cp config/local.php.example config/local.php
```

Edytuj `config/local.php` i uzupełnij:

```php
<?php

declare(strict_types=1);

return [
    // --- Baza danych (dane z panelu hostingu) ---
    'database' => [
        'host'     => '127.0.0.1',        // często localhost lub adres z panelu
        'port'     => 3306,
        'database' => 'user_przypierdolka', // nazwa bazy
        'username' => 'user_dblogin',       // login MySQL
        'password' => 'TWOJE_HASLO_DB',     // hasło MySQL
    ],

    // --- Aplikacja ---
    'app' => [
        'env'   => 'production',
        'url'   => 'https://przypierdolka.pl',  // pełny URL z https, bez końcowego /
        'debug' => false,
    ],

    // --- Sekrety (OBOWIĄZKOWO wygeneruj nowe!) ---
    'security' => [
        'ip_salt' => 'losowy-ciag-min-32-znaki',
        'app_key' => 'inny-losowy-ciag-min-32-znaki',
    ],
];
```

### Generowanie sekretów (SSH lub lokalnie)

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Uruchom dwa razy — jeden wynik wklej jako `ip_salt`, drugi jako `app_key`.

### Co gdzie wpisać — skrót

| Co | Gdzie |
|----|-------|
| Host / login / hasło / nazwa bazy MySQL | `config/local.php` → `database` |
| Adres strony (https://…) | `config/local.php` → `app.url` |
| Wyłączenie trybu debug | `config/local.php` → `app.debug` = `false` |
| Losowe sekrety | `config/local.php` → `security.ip_salt`, `security.app_key` |
| Tytuł strony, opis SEO | panel admina → Ustawienia (lub tabela `settings` w bazie) |

Domyślne dane w `config/database.php` (root / puste hasło) **nie zmieniaj** — nadpisuje je `local.php`.

---

## 5. Uprawnienia katalogów

Serwer WWW musi móc zapisywać cache, logi i uploady:

```bash
chmod -R 775 storage/
chmod -R 775 public/assets/uploads/
```

Na shared hostingu często wystarczy ustawić właściciela na użytkownika WWW (np. `www-data`).

Wymagane katalogi (już w repo, muszą być zapisywalne):
- `storage/cache/`
- `storage/logs/`
- `public/assets/uploads/stories/`
- `public/assets/uploads/generated/`
- `public/assets/uploads/avatars/`

---

## 6. Composer (opcjonalnie)

```bash
composer install --no-dev --optimize-autoloader
```

Aplikacja działa też bez tego kroku — autoloader jest wbudowany w `bootstrap.php`.

---

## 7. Sprawdzenie po wdrożeniu

1. Otwórz `https://twoja-domena.pl` — strona główna z 3 przykładowymi historiami.
2. Zaloguj się jako `admin` / `admin1234`.
3. Wejdź na `https://twoja-domena.pl/admineu` — panel administracyjny.
4. Sprawdź upload awatara na koncie użytkownika (wymaga GD + WebP).
5. Sprawdź `https://twoja-domena.pl/llms.txt` — generuje się automatycznie przy pierwszym wejściu.

### Typowe problemy

| Objaw | Przyczyna | Rozwiązanie |
|-------|-----------|-------------|
| Błąd 500 „Błąd połączenia z bazą” | Złe dane w `local.php` | Sprawdź host, login, hasło, nazwę bazy |
| 404 na wszystkich podstronach | Zły document root lub brak rewrite | Document root = `public/`, włącz `AllowOverride` |
| Biała strona | `debug` wyłączony, błąd PHP | Tymczasowo `debug => true`, sprawdź `storage/logs/` |
| Brak stylów / JS | Zły `app.url` | Ustaw pełny URL z https w `local.php` |
| Awatar się nie zapisuje | Brak GD/WebP lub uprawnień | Zainstaluj `php-gd`, chmod na `uploads/avatars/` |

---

## 8. Nginx (alternatywa dla Apache)

```nginx
server {
    listen 80;
    server_name przypierdolka.pl;
    root /home/user/przypierdolka/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}
```

---

## Pliki kluczowe

| Plik | Rola |
|------|------|
| `database/install.sql` | Jednorazowy import bazy (schemat + dane startowe) |
| `config/local.php` | Twoja konfiguracja produkcyjna (nie commituj!) |
| `config/local.php.example` | Szablon do skopiowania |
| `public/index.php` | Front controller |
| `public/.htaccess` | Rewrite dla Apache |
