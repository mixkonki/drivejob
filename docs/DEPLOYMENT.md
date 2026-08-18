# DriveJob — Οδηγός Ανάπτυξης σε Παραγωγή (Runbook)

_Πακέτο 9 · Τελευταία ενημέρωση: 18/08/2026_

---

## 0. Τι χρειάζεσαι πριν ξεκινήσεις

| Απαίτηση | Σημείωση |
|---|---|
| **Domain** | π.χ. `drivejob.gr` — με δυνατότητα αλλαγής DNS |
| **PHP 8.3+** με επεκτάσεις `pdo_mysql`, `gd`, `zip`, `mbstring`, `curl` | το Dockerfile τα εγκαθιστά |
| **MySQL 8 / MariaDB 10.6+** | **Προσοχή:** το Render ΔΕΝ προσφέρει managed MySQL — χρειάζεται εξωτερική |
| **Μόνιμος αποθηκευτικός χώρος** | για `storage/` (αρχεία χρηστών) — αλλιώς χάνονται σε κάθε deploy |
| **SMTP** | για emails επαλήθευσης & ειδοποιήσεων |
| **ANTHROPIC_API_KEY** | για το AI matching |
| **Πιστοποιητικό SSL** | οι περισσότερες πλατφόρμες το δίνουν αυτόματα (Let's Encrypt) |

---

## 1. Μεταβλητές περιβάλλοντος

Ορίζονται στην πλατφόρμα (Render → Environment, cPanel → .env αρχείο, κ.λπ.).
**Ποτέ στο git.**

```bash
# --- Εφαρμογή ---
APP_ENV=production          # ΚΡΙΣΙΜΟ: κρύβει σφάλματα, ενεργοποιεί ασφαλή cookies
APP_DEBUG=false
APP_URL=https://drivejob.gr # ΚΡΙΣΙΜΟ: χωρίς αυτό τα cron/emails βγάζουν λάθος links

# --- Βάση δεδομένων ---
DB_HOST=...
DB_PORT=3306
DB_DATABASE=drivejob
DB_USERNAME=...
DB_PASSWORD=...

# --- Email (SMTP) ---
SMTP_HOST=...
SMTP_PORT=587
SMTP_USERNAME=...
SMTP_PASSWORD=...
SMTP_FROM_EMAIL=info@drivejob.gr
SMTP_FROM_NAME="DriveJob"
EMAIL_DEBUG=false

# --- AI ---
ANTHROPIC_API_KEY=sk-ant-...

# --- Backups (προαιρετικό) ---
BACKUP_RETENTION_DAYS=14
```

> **Έλεγχος:** αν το `APP_ENV` δεν είναι `production`, τα σφάλματα PHP εμφανίζονται στους χρήστες.
> Ο κώδικας πέφτει σε `production` όταν η μεταβλητή λείπει — αλλά όρισέ την ρητά.

---

## 2. Πρώτη εγκατάσταση

```bash
# 1. Κώδικας
git clone https://github.com/mixkonki/drivejob.git && cd drivejob

# 2. Εξαρτήσεις (χωρίς dev πακέτα)
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 3. Βάση: εισαγωγή σχήματος + δεδομένων
mysql -h "$DB_HOST" -u "$DB_USERNAME" -p "$DB_DATABASE" < διαδρομή/προς/backup.sql

# 4. Migrations (idempotent — τρέχουν με ασφάλεια ξανά)
php database/migrations/add_updated_at_to_drivers.php
php database/migrations/remove_criminal_record.php
php database/migrations/create_ai_usage_log.php
php database/migrations/add_terms_accepted_at.php

# 5. Δικαιώματα γραφής
mkdir -p storage/uploads storage/backups storage/queue/matching logs
chmod -R 775 storage logs

# 6. Λογαριασμός διαχειριστή
php devtools/reset-admin-password.php
```

---

## 3. Έλεγχοι μετά το deploy

```bash
curl -s https://drivejob.gr/health          # → {"status":"ok",...}
bash scripts/smoke-test.sh                  # 16 έλεγχοι (άλλαξε το BASE_URL μέσα)
```

Χειροκίνητα στον browser:

1. Αρχική φορτώνει, λογότυπο σωστό, **HTTPS με πράσινο λουκέτο**
2. Εγγραφή δοκιμαστικού οδηγού → **έρχεται email επαλήθευσης**
3. Σύνδεση → επεξεργασία προφίλ → αποθήκευση
4. Ανέβασμα αρχείου → εμφανίζεται· **αποσύνδεση → το URL του αρχείου δίνει 403**
5. `/privacy`, `/terms` ανοίγουν · cookie banner εμφανίζεται μία φορά
6. Δημιουργία αγγελίας από εταιρεία → ο οδηγός τη βλέπει

---

## 4. Χρονοπρογραμματισμένες εργασίες (cron)

```cron
# Ταιριάσματα: άδειασμα ουράς κάθε 15 λεπτά
*/15 * * * * cd /path/to/drivejob && php devtools/run-matching-worker.php 100 >> logs/cron-worker.log 2>&1

# AI σκορ: ωριαία (κοστίζει — δες όρια στο ai_configuration)
0 * * * * cd /path/to/drivejob && php scripts/cron/update-matching-scores.php >> logs/cron-ai.log 2>&1

# Ειδοποιήσεις λήξης αδειών: μία φορά την ημέρα, πρωί
30 8 * * * cd /path/to/drivejob && php scripts/cron/check_expiring_licenses.php >> logs/cron-licenses.log 2>&1

# Αντίγραφο ασφαλείας βάσης: κάθε βράδυ 03:00
0 3 * * * cd /path/to/drivejob && bash scripts/backup-database.sh >> logs/backup.log 2>&1
```

> Σε πλατφόρμες χωρίς cron (Render free tier): χρησιμοποίησε **Cron Jobs** service ή εξωτερικό
> scheduler (cron-job.org) που καλεί ένα προστατευμένο endpoint.

**Έλεγχος κόστους AI:** τα ημερήσια όρια είναι στον πίνακα `ai_configuration`
(`ai_daily_request_limit`, `ai_daily_cost_limit_usd`). Η κατανάλωση φαίνεται στο `ai_usage_log`
και στο admin dashboard.

---

## 5. Παρακολούθηση

| Τι | Πού |
|---|---|
| Υγεία εφαρμογής | `GET /health` — βάλε το σε uptime monitor (π.χ. UptimeRobot, κάθε 5′) |
| Σφάλματα PHP | `logs/php_errors.log` |
| Σφάλματα εφαρμογής | `logs/app.log` |
| KPIs ταιριάσματος | `/admin/dashboard` (samples, cache hit, queue depth) |
| Κόστος AI | `ai_usage_log` · admin dashboard |
| Cron | `logs/cron-*.log` — έλεγξε ότι γράφουν |

---

## 6. Ενημέρωση (deploy νέας έκδοσης)

```bash
cd /path/to/drivejob
bash scripts/backup-database.sh          # 1. ΠΑΝΤΑ backup πρώτα
git pull origin main                     # 2. νέος κώδικας
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php database/migrations/<νέα_migration>.php   # 3. αν υπάρχει
curl -s https://drivejob.gr/health       # 4. έλεγχος
```

---

## 7. Επαναφορά (rollback)

```bash
# Κώδικας: πίσω στην προηγούμενη έκδοση
git log --oneline -5
git checkout <προηγούμενο_commit>
composer install --no-dev --optimize-autoloader

# Βάση: από το τελευταίο αντίγραφο
zcat storage/backups/drivejob-ΗΗΗΗΜΜΗΗ-ΩΩΛΛ.sql.gz | mysql -h "$DB_HOST" -u "$DB_USERNAME" -p "$DB_DATABASE"
```

> Τα migrations είναι **idempotent και μόνο-προσθετικά** (προσθέτουν στήλες/πίνακες,
> δεν σβήνουν δεδομένα) — η επαναφορά κώδικα συνήθως αρκεί χωρίς επαναφορά βάσης.

---

## 8. Ασφάλεια — τι ισχύει ήδη

- Κωδικοί με **bcrypt** · CSRF σε όλες τις φόρμες
- Αρχεία χρηστών **εκτός webroot** με έλεγχο ιδιοκτησίας ανά αίτημα
- **Ένα entry point** (`public/index.php`) — τίποτα άλλο δεν σερβίρεται
- Κεφαλίδες: CSP, HSTS (σε HTTPS), X-Frame-Options, nosniff, Referrer-Policy, Permissions-Policy
- Cookies συνεδρίας: `HttpOnly`, `SameSite=Lax`, **`Secure` αυτόματα σε HTTPS**
- Σφάλματα **δεν εμφανίζονται ποτέ** σε παραγωγή
- Μυστικά μόνο σε env vars · `.dockerignore` αποκλείει `.env` από την εικόνα
- CI: PHPStan + PHPUnit **enforcing** σε κάθε push

### Πριν το άνοιγμα στο κοινό
- [ ] Rotation του παλιού `OPENAI_API_KEY` και του SMTP κωδικού (είναι στο git history)
- [ ] Αντίγραφο DPA Anthropic στον φάκελο GDPR
- [ ] Συμπλήρωση παρόχου SMS στην Πολιτική Απορρήτου + DPA μαζί του
- [ ] Δοκιμή επαναφοράς από backup (μη δοκιμασμένο backup = ανύπαρκτο backup)

---

## 9. Συγκεκριμένα βήματα για netmind.gr (το hosting μας)

Ο λογαριασμός έχει ήδη: `drivejob.gr` ως primary domain, SSH, Git Version Control,
MySQL (unlimited), Scheduled Tasks (cron), SSL/TLS, Timeline Backups.

### 9.1 Προετοιμασία στο panel
1. **Change PHP Version** → επίλεξε **PHP 8.3 ή 8.4**.
   Στο **PHP Configuration** βεβαιώσου ότι είναι ενεργά: `pdo_mysql`, `gd`, `zip`, `mbstring`, `curl`, `openssl`, `fileinfo`.
   Ρύθμισε `memory_limit ≥ 256M`, `upload_max_filesize ≥ 10M`, `post_max_size ≥ 12M`.
2. **MySQL Databases** → δημιούργησε βάση `drivejob_prod` + χρήστη με **ισχυρό κωδικό** και πλήρη δικαιώματα σε αυτήν.
   ⚠️ Ποτέ τον root χρήστη της βάσης για την εφαρμογή.
3. **SSH Access** → ενεργοποίησε και κράτα τα στοιχεία σύνδεσης.
4. **SSL/TLS** → έκδοση πιστοποιητικού για `drivejob.gr` + `www.drivejob.gr` (Let's Encrypt) και **Force HTTPS**.

### 9.2 Ανέβασμα κώδικα (μέσω Git — προτεινόμενο)
**Git Version Control** → Create → repository URL `https://github.com/mixkonki/drivejob.git`,
branch `main`, φάκελος προορισμού π.χ. `~/drivejob`.
*(Ιδιωτικό repo: χρειάζεται deploy key ή personal access token.)*

Εναλλακτικά μέσω SSH:
```bash
cd ~ && git clone https://github.com/mixkonki/drivejob.git drivejob
```

### 9.3 Ρύθμιση μέσω SSH
```bash
cd ~/drivejob
composer install --no-dev --optimize-autoloader   # αν λείπει: php ~/composer.phar install ...
npm ci && npm run build                            # αν υπάρχει node· αλλιώς ανέβασε τοπικά το public/js/vendor/

cp .env.example .env && nano .env      # συμπλήρωσε (βλ. §1) — ΚΡΙΣΙΜΑ:
                                       #   APP_ENV=production
                                       #   APP_DEBUG=false
                                       #   APP_URL=https://drivejob.gr
                                       #   DB_* του βήματος 9.1
chmod 600 .env

mkdir -p storage/uploads storage/backups storage/queue/matching logs
chmod -R 775 storage logs
```

### 9.4 Βάση δεδομένων
```bash
# Εισαγωγή σχήματος/δεδομένων από τοπικό αντίγραφο
mysql -u ΧΡΗΣΤΗΣ -p drivejob_prod < drivejob-backup.sql

# Migrations (idempotent)
php database/migrations/add_updated_at_to_drivers.php
php database/migrations/remove_criminal_record.php
php database/migrations/create_ai_usage_log.php
php database/migrations/add_terms_accepted_at.php

php devtools/reset-admin-password.php   # λογαριασμός διαχειριστή
```
> ✅ Τα migrations διαβάζουν αυτόματα τη σύνδεση από το `.env` (Πακέτο 9) —
> δεν χρειάζεται καμία χειροκίνητη προσαρμογή.

### 9.5 Document root → `public/`  (ΚΡΙΣΙΜΟ)
Το `drivejob.gr` πρέπει να δείχνει στο **`~/drivejob/public`**, όχι στο `~/drivejob`.
Στο panel: **Domains** → drivejob.gr → Document Root → `/drivejob/public`.

Αν το panel δεν το επιτρέπει, εναλλακτική: symlink του `public_html` →
```bash
mv ~/public_html ~/public_html.bak
ln -s ~/drivejob/public ~/public_html
```
> ❌ **Ποτέ** ολόκληρο το `~/drivejob` ως document root — θα εκτίθεντο `.env`, `src/`, `storage/`.

### 9.6 Scheduled Tasks (cron)
Πρόσθεσε 4 εργασίες (προσάρμοσε τη διαδρομή PHP αν χρειάζεται):

| Συχνότητα | Εντολή |
|---|---|
| κάθε 15′ | `cd ~/drivejob && php devtools/run-matching-worker.php 100 >> logs/cron-worker.log 2>&1` |
| ωριαία | `cd ~/drivejob && php scripts/cron/update-matching-scores.php >> logs/cron-ai.log 2>&1` |
| ημερησίως 08:30 | `cd ~/drivejob && php scripts/cron/check_expiring_licenses.php >> logs/cron-licenses.log 2>&1` |
| ημερησίως 03:00 | `cd ~/drivejob && bash scripts/backup-database.sh >> logs/backup.log 2>&1` |

### 9.7 Επαλήθευση

**Πρώτα ο αυτόματος προέλεγχος** — ελέγχει PHP, επεκτάσεις, όρια, δικαιώματα, .env και βάση:
```bash
php devtools/preflight-check.php
```
Δείχνει ✅/⚠️/❌ ανά σημείο και τερματίζει με κωδικό 1 αν υπάρχει σφάλμα. **Μην κάνεις deploy με ❌.**

Μετά:
```bash
curl -s https://drivejob.gr/health          # → {"status":"ok",...}
curl -sI https://drivejob.gr | grep -i strict-transport   # HSTS ενεργό
curl -s https://drivejob.gr/.env            # → ΠΡΕΠΕΙ 403/404, ΠΟΤΕ περιεχόμενο
```
Και ο κατάλογος ελέγχων της §3.

### 9.8 Ενεργοποίηση Security Headers του panel
Στην ενότητα **CDN → Security Headers** μπορείς να προσθέσεις κεφαλίδες σε επίπεδο διακομιστή.
Η εφαρμογή τις στέλνει ήδη — απόφυγε **διπλές** κεφαλίδες (ιδίως CSP), γιατί οι browsers
εφαρμόζουν την αυστηρότερη και μπορεί να «σπάσουν» χάρτες/εικονίδια.
