# DriveJob

Πλατφόρμα σύνδεσης επαγγελματιών οδηγών με εταιρείες μεταφορών, με AI-powered matching.

**Stack:** PHP 8.3+ (custom MVC), MySQL 8, Composer, NPM/Webpack, OpenAI API.

## Τοπική εγκατάσταση (macOS με Laravel Herd + DBngin)

```bash
# 1. Κώδικας — ο φάκελος στο ~/Herd γίνεται αυτόματα http://drivejob.test
git clone https://github.com/mixkonki/drivejob.git ~/Herd/drivejob
cd ~/Herd/drivejob

# 2. Εξαρτήσεις
composer install
npm install && npm run build

# 3. Ρυθμίσεις — συμπλήρωσε OPENAI_API_KEY και SMTP_* στο .env
cp .env.example .env

# 4. Βάση δεδομένων
mysql -h 127.0.0.1 -u root -e "CREATE DATABASE drivejob CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -h 127.0.0.1 -u root drivejob < path/to/dump.sql   # ή τρέξε τα migrations από database/migrations/

# 5. Έλεγχος
bash scripts/smoke-test.sh
open http://drivejob.test
```

## Δομή

```
config/          Ρυθμίσεις (routes, database, email, openai — τα secrets ΜΟΝΟ στο .env)
public/          Web root (front controller: index.php)
src/             Εφαρμογή: Controllers, Models, Views, Services, Repositories, Core, RBAC
storage/uploads/ Αρχεία χρηστών — ΕΚΤΟΣ web root, σερβίρονται μέσω FileController με έλεγχο πρόσβασης
database/        Migrations (standalone PHP scripts)
scripts/         Εργαλεία (smoke-test.sh κ.ά.)
tests/           PHPUnit + Codeception
docs/            Τεκμηρίωση (docs/archive: ιστορικά reports)
```

## Χρήσιμες εντολές

```bash
bash scripts/smoke-test.sh   # 16 γρήγοροι έλεγχοι λειτουργίας — τρέξε μετά από κάθε αλλαγή
composer test                # PHPUnit
composer phpstan             # Στατική ανάλυση
npm run build                # Παραγωγή assets (tesseract bundle)
```

## Σημειώσεις ασφαλείας

- Κανένα secret στον κώδικα — όλα στο `.env` (φορτώνεται με phpdotenv στο bootstrap).
- Τα αρχεία χρηστών (βιογραφικά, διπλώματα) απαιτούν σύνδεση για προβολή· δημόσια είναι μόνο οι εικόνες προφίλ και τα λογότυπα.
- Δεν αποθηκεύονται ποινικά μητρώα (GDPR άρθρο 10) — μόνο υπεύθυνη δήλωση με timestamp.

---

**Συντηρητής:** Κώστας Μιχαηλίδης · kostas.michailidis@hotmail.gr
