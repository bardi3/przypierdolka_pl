# Konfiguracja — git vs serwer

## W repozytorium (commituj)

| Plik | Zawartość |
|------|-----------|
| `app.php` | Domyślne dev (`localhost:8000`, debug on) |
| `database.php` | Dev: `root` bez hasła — **bez sekretów produkcyjnych** |
| `security.php` | Domyślne limity; placeholdery soli (nadpisz na prod w `local.php`) |
| `routes.php` | Trasy aplikacji |
| `local.php.example` | Szablon produkcji — **tylko wzór, bez prawdziwych haseł** |

## Tylko na serwerze / lokalnie (NIE commituj)

| Plik | Gdzie |
|------|-------|
| `local.php` | VPS: `/www/przypierdolka.pl/web/config/local.php` |

Plik jest w `.gitignore`. Nadpisuje `database.php` — zawiera hasło MySQL, URL `https://` i sekrety.

**Na VPS:** wgraj `config/local.php` ręcznie (FTP/SFTP) albo utwórz na serwerze z `local.php.example`. `git pull` tego pliku nie nadpisze.

## Wdrożenie na VPS

```bash
cd /www/przypierdolka.pl/web
git pull   # lub pierwszy clone

cp config/local.php.example config/local.php
nano config/local.php   # uzupełnij hasło DB i sekrety

php -l config/local.php
mysql -u przypierdolka -p przypierdolka -e "SELECT 1;"
```

## Deweloper lokalnie

```bash
cp config/local.php.example config/local.php
# dostosuj database do swojego MySQL (np. root / puste hasło)
# ustaw app.url na http://localhost:8000 i debug => true
```

Lub użyj gotowego `config/local.php` (gitignore) z ustawieniami dev.
