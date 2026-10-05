# Φάση Δ — «Καθαρή εκκίνηση» (runbook)

Γράφτηκε 05/10/2026. Τα τρία εργαλεία ζουν στο `bin/` και τρέχουν παντού
όπου τρέχει η εφαρμογή (καθαρό PHP, χωρίς mysqldump/exec).

Στον server: `cd ~/drivejob && /usr/php83/usr/bin/php bin/<εργαλείο>`.
Τοπικά (Mac): `cd ~/Herd/drivejob && php bin/<εργαλείο>`.

## 0. Πριν ξεκινήσεις

- Ο Κώστας έχει ολοκληρώσει τις δοκιμές του με τα demo δεδομένα.
- Το τελευταίο deploy είναι πράσινο (`php bin/health-cli.php` → 11/11).

## 1. Backup με δοκιμασμένη επαναφορά

```
php bin/backup-cli.php --verify
```

Περιμένεις «Επαλήθευση OK». Αν στον server βγάλει «denied»: ο χρήστης της
βάσης δεν μπορεί να φτιάξει προσωρινή βάση. Φτιάξε από το StackCP μια
δεύτερη άδεια βάση (π.χ. `drivejob_verify`) με τον ίδιο χρήστη και τρέξε
`--verify-into=drivejob_verify` (αδειάζει μετά).

Το αρχείο μένει στο `storage/backups/` (εκτός git, εκτός web). Κατέβασέ το
και σε δικό σου δίσκο: `scp drivejob.gr@ssh.gb.stackcp.com:drivejob/storage/backups/*.sql.gz ~/Desktop/`.

## 2. Εκκαθάριση demo — πρώτα dry-run

```
php bin/beta-cleanup-cli.php --include-garbage --keep=info@thessdrive.gr
```

Διάβασε τη λίστα ονομάτων. Ό,τι ΔΕΝ πρέπει να σβηστεί → πρόσθεσέ το στο
`--keep=`. Ό,τι λείπει → `--ids=driver:NN,company:NN`.

Μετά, με το backup της τελευταίας ώρας στη θέση του (αλλιώς αρνείται):

```
php bin/beta-cleanup-cli.php --include-garbage --keep=info@thessdrive.gr --apply
```

Όλα σε μία συναλλαγή· σε αποτυχία δεν αλλάζει τίποτα.

## 3. Admin λογαριασμοί

```
php bin/admin-cli.php list
php bin/admin-cli.php create --email=kostas@thessdrive.gr
php bin/admin-cli.php reset-password --email=admin@drivejob.gr
```

Το συνθηματικό τυπώνεται μία φορά — στο password manager αμέσως. Σύνδεση
από το κοινό `/auth/login` → `/admin/dashboard`. Ο παλιός `admin@drivejob.gr`
είτε παίρνει νέο συνθηματικό είτε `disable`.

## 4. Τακτικό backup (cron στο StackCP)

StackCP → Cron Jobs → καθημερινά 04:10:

```
cd /home/sites/<path>/drivejob && /usr/php83/usr/bin/php bin/backup-cli.php --keep=14 --quiet
```

(Η πλήρης διαδρομή φαίνεται με `pwd` μέσα στο ~/drivejob.) Μία φορά τον
μήνα: `--verify` με το χέρι, για να ξέρουμε ότι επαναφέρεται.

## 5. Επαναφορά — αν χρειαστεί ποτέ

```
php bin/backup-cli.php --restore=storage/backups/drivejob_YYYYmmdd_HHMMSS.sql.gz --into=drivejob --yes
```

Σβήνει και ξαναφτιάχνει τους πίνακες της βάσης-στόχου. Πρώτα σε δοκιμαστική
βάση, μετά στην κανονική.
