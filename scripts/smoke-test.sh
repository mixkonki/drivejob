#!/bin/bash
# ============================================================
# DriveJob Smoke Tests — Πακέτο 0
# Τρέχει μετά από ΚΑΘΕ πακέτο refactoring.
# Χρήση:  bash scripts/smoke-test.sh
# Όλοι οι έλεγχοι είναι read-only (κανένα γράψιμο στη βάση).
# ============================================================

BASE="${DRIVEJOB_URL:-http://drivejob.test}"
PASS=0
FAIL=0
FAILED_TESTS=""

check() {
    local name="$1"; local expected="$2"; local actual="$3"
    if [ "$actual" = "$expected" ]; then
        printf "  ✅ %-45s [%s]\n" "$name" "$actual"
        PASS=$((PASS+1))
    else
        printf "  ❌ %-45s [περίμενα %s, πήρα %s]\n" "$name" "$expected" "$actual"
        FAIL=$((FAIL+1)); FAILED_TESTS="$FAILED_TESTS\n  - $name"
    fi
}

check_contains() {
    local name="$1"; local url="$2"; local needle="$3"
    local body; body=$(curl -sL --max-time 10 "$url")
    if echo "$body" | grep -q "$needle"; then
        printf "  ✅ %-45s [περιέχει «%s»]\n" "$name" "$needle"
        PASS=$((PASS+1))
    else
        printf "  ❌ %-45s [ΔΕΝ περιέχει «%s»]\n" "$name" "$needle"
        FAIL=$((FAIL+1)); FAILED_TESTS="$FAILED_TESTS\n  - $name"
    fi
}

status_of() { curl -s -o /dev/null -w "%{http_code}" --max-time 10 "$1"; }

echo "🔥 DriveJob Smoke Tests — $BASE — $(date '+%d/%m/%Y %H:%M')"
echo "───────────────────────────────────────────────────────────"

echo "— Σελίδες —"
check "Αρχική σελίδα"                    "200" "$(status_of "$BASE/")"
check "Σελίδα σύνδεσης"                  "200" "$(status_of "$BASE/auth/login")"
check "Αγγελίες εργασίας"                "200" "$(status_of "$BASE/job-listings")"
check "Εγγραφή οδηγού"                   "200" "$(status_of "$BASE/drivers/register")"
check "Εγγραφή εταιρείας"                "200" "$(status_of "$BASE/companies/register")"

echo "— Περιεχόμενο —"
check_contains "Αρχική: τίτλος"          "$BASE/"            "DriveJob"
check_contains "Login: φόρμα"            "$BASE/auth/login"  "Σύνδεση"
check_contains "Αγγελίες: αποτελέσματα"  "$BASE/job-listings" "Αποτελέσματα"

echo "— Ασφάλεια —"
# Ο router επιστρέφει σελίδα 404 με status 200 (γνωστό θέμα, διόρθωση στο Πακέτο 3).
# Εδώ ελέγχουμε το ουσιώδες: να ΜΗΝ διαρρέει το περιεχόμενο των αρχείων.
ENV_BODY=$(curl -sL --max-time 10 "$BASE/config/.env")
if echo "$ENV_BODY" | grep -q "DB_HOST"; then
    printf "  ❌ %-45s [ΔΙΑΡΡΟΗ ΠΕΡΙΕΧΟΜΕΝΟΥ!]\n" "Το .env ΔΕΝ διαρρέει"; FAIL=$((FAIL+1)); FAILED_TESTS="$FAILED_TESTS\n  - Διαρροή .env"
else
    printf "  ✅ %-45s [δεν διαρρέει περιεχόμενο]\n" "Το .env ΔΕΝ διαρρέει"; PASS=$((PASS+1))
fi
SRC_BODY=$(curl -sL --max-time 10 "$BASE/src/helpers.php")
if echo "$SRC_BODY" | grep -q "<?php"; then
    printf "  ❌ %-45s [ΔΙΑΡΡΟΗ ΚΩΔΙΚΑ!]\n" "Το /src ΔΕΝ διαρρέει"; FAIL=$((FAIL+1)); FAILED_TESTS="$FAILED_TESTS\n  - Διαρροή /src"
else
    printf "  ✅ %-45s [δεν διαρρέει κώδικα]\n" "Το /src ΔΕΝ διαρρέει"; PASS=$((PASS+1))
fi
check "Λάθος login δεν σκάει με 500"     "200" "$(curl -s -o /dev/null -w "%{http_code}" -L --max-time 10 \
    -d "email=smoke@test.invalid&password=wrongpass" "$BASE/auth/login")"

echo "— Uploads (Πακέτο 1) —"
# Ιδιωτικά αρχεία: χωρίς login πρέπει να απαντούν 403
RESUME=$(ls "$HOME/Herd/drivejob/storage/uploads/resumes/" 2>/dev/null | head -1)
if [ -n "$RESUME" ]; then
    check "Βιογραφικό χωρίς login → 403" "403" "$(status_of "$BASE/uploads/resumes/$RESUME")"
fi
LICENSE=$(ls "$HOME/Herd/drivejob/storage/uploads/license_images/" 2>/dev/null | head -1)
if [ -n "$LICENSE" ]; then
    check "Δίπλωμα χωρίς login → 403" "403" "$(status_of "$BASE/uploads/license_images/$LICENSE")"
fi
# Δημόσια: profile images πρέπει να σερβίρονται κανονικά (κωδικοποίηση κενών στο URL)
PIMG=$(ls "$HOME/Herd/drivejob/storage/uploads/profile_images/" 2>/dev/null | head -1 | sed 's/ /%20/g')
if [ -n "$PIMG" ]; then
    check "Εικόνα προφίλ (δημόσια) → 200" "200" "$(status_of "$BASE/uploads/profile_images/$PIMG")"
fi
# Path traversal: αρκεί να ΜΠΛΟΚΑΡΕΤΑΙ (400 από nginx, 403 από auth, ή 404 από realpath — όχι 200)
TRAV=$(status_of "$BASE/uploads/profile_images/..%2F..%2F..%2Fconfig%2F.env")
case "$TRAV" in
    400|403|404)
        printf "  ✅ %-45s [μπλοκαρίστηκε με %s]\n" "Path traversal μπλοκάρεται" "$TRAV"; PASS=$((PASS+1)) ;;
    *)
        printf "  ❌ %-45s [πέρασε με %s!]\n" "Path traversal μπλοκάρεται" "$TRAV"; FAIL=$((FAIL+1))
        FAILED_TESTS="$FAILED_TESTS\n  - Path traversal" ;;
esac

echo "— Βάση δεδομένων —"
DB_TABLES=$(php -r '
try { $p=new PDO("mysql:host=127.0.0.1;dbname=drivejob","root","");
echo $p->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=\"drivejob\"")->fetchColumn(); }
catch (Exception $e) { echo "ERR"; }' 2>/dev/null)
if [ "$DB_TABLES" != "ERR" ] && [ -n "$DB_TABLES" ] && [ "$DB_TABLES" -ge 60 ] 2>/dev/null; then
    printf "  ✅ %-45s [%s πίνακες]\n" "Σύνδεση βάσης + σχήμα" "$DB_TABLES"; PASS=$((PASS+1))
else
    printf "  ❌ %-45s [%s]\n" "Σύνδεση βάσης + σχήμα" "$DB_TABLES"; FAIL=$((FAIL+1))
    FAILED_TESTS="$FAILED_TESTS\n  - Σύνδεση βάσης"
fi

echo "───────────────────────────────────────────────────────────"
echo "Αποτέλεσμα: $PASS πέρασαν, $FAIL απέτυχαν"
if [ $FAIL -gt 0 ]; then
    echo -e "Αποτυχίες:$FAILED_TESTS"
    echo "⛔ ΜΗΝ κλείσεις το πακέτο — κάτι έσπασε."
    exit 1
else
    echo "🟢 Όλα καλά — μπορείς να προχωρήσεις."
fi
