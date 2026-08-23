#!/usr/bin/env bash
#
# Έλεγχος cache — τρέξ' το όποτε «οι αλλαγές δεν εμφανίζονται».
#
# ΓΙΑΤΙ ΥΠΑΡΧΕΙ: χάσαμε ώρες ψάχνοντας γιατί η φόρμα εγγραφής οδηγού
# φόρτωνε χωρίς στιλ. Το αρχείο ήταν σωστό στον server. Ο browser έδειχνε
# status 200. Η αιτία φάνηκε μόνο όταν κοιτάξαμε το content-type: το
# /css/drivers-registration.css επέστρεφε text/html — τη σελίδα
# «This Site is Under Maintenance», κολλημένη στο edge cache του παρόχου.
#
# Αυτό το script κάνει σε 5 δευτερόλεπτα τον έλεγχο που μας πήρε ώρες.
#
# Χρήση:
#   bash scripts/check-cache.sh                    # παραγωγή
#   bash scripts/check-cache.sh http://drivejob.test

set -u

BASE="${1:-https://drivejob.gr}"
BASE="${BASE%/}"

GREEN=$'\033[0;32m'; RED=$'\033[0;31m'; YELLOW=$'\033[0;33m'; DIM=$'\033[2m'; OFF=$'\033[0m'

fails=0
warns=0

echo
echo "Έλεγχος cache στο ${BASE}"
echo "────────────────────────────────────────────────────────────"

# ── 1. Στατικά αρχεία: σωστός τύπος περιεχομένου; ──────────────────
#
# Ο πιο ύπουλος τρόπος να σπάσει ένα site: ο server επιστρέφει 200, αλλά
# το περιεχόμενο είναι HTML αντί για CSS. Ο browser το αγνοεί σιωπηλά.

check_type() {
    local path="$1" expected="$2"
    local headers ctype status

    headers=$(curl -sS -I -L --max-time 15 "${BASE}${path}" 2>/dev/null)
    status=$(printf '%s' "$headers" | awk 'BEGIN{s=""} /^HTTP\//{s=$2} END{print s}')
    ctype=$(printf '%s' "$headers" | tr -d '\r' | awk -F': ' 'tolower($1)=="content-type"{print tolower($2)}' | tail -1)

    if [ "$status" != "200" ]; then
        printf '%s ✗ %s%s  → status %s\n' "$RED" "$path" "$OFF" "${status:-καμία απόκριση}"
        fails=$((fails + 1))
        return
    fi

    case "$ctype" in
        *"$expected"*)
            printf '%s ✓ %s%s  %s%s%s\n' "$GREEN" "$path" "$OFF" "$DIM" "$ctype" "$OFF"
            ;;
        *text/html*)
            printf '%s ✗ %s%s  → επιστρέφει HTML αντί για %s\n' "$RED" "$path" "$OFF" "$expected"
            printf '   %sΑΥΤΟ ΕΙΝΑΙ ΤΟ ΓΝΩΣΤΟ ΠΡΟΒΛΗΜΑ: το cache του παρόχου κρατά σελίδα\n' "$DIM"
            printf '   συντήρησης στη θέση του αρχείου. Καθάρισε το cache από το StackCP.%s\n' "$OFF"
            fails=$((fails + 1))
            ;;
        *)
            printf '%s ⚠ %s%s  → %s (περίμενα %s)\n' "$YELLOW" "$path" "$OFF" "${ctype:-άγνωστο}" "$expected"
            warns=$((warns + 1))
            ;;
    esac
}

echo
echo "1. Τύπος περιεχομένου στατικών αρχείων"
check_type "/css/styles.css"                 "text/css"
check_type "/css/drivers-registration.css"   "text/css"
check_type "/css/company-registration.css"   "text/css"
check_type "/img/logo.png"                   "image/"
check_type "/manifest.json"                  "json"
check_type "/sw.js"                          "javascript"

# ── 2. Κεφαλίδες cache: λέει ο server τι θέλει; ────────────────────
#
# Όταν λείπει το Cache-Control, ο πάροχος μαντεύει. Ό,τι μαντέψει,
# το κρατά όσο θέλει — και δεν υπάρχει τρόπος να το προβλέψεις.

echo
echo "2. Κεφαλίδες Cache-Control"

cc_of() {
    curl -sS -I -L --max-time 15 "$1" 2>/dev/null \
        | tr -d '\r' | awk -F': ' 'tolower($1)=="cache-control"{print tolower($2)}' | tail -1
}

check_cache_header() {
    local path="$1" want="$2" label="$3"
    local value fresh

    value=$(cc_of "${BASE}${path}")

    if [ -z "$value" ]; then
        # ΚΡΙΣΙΜΗ ΔΙΑΚΡΙΣΗ: λείπει η κεφαλίδα επειδή ο κανόνας είναι λάθος,
        # ή επειδή ο πάροχος σερβίρει αποθηκευμένη απόκριση από ΠΡΙΝ τον
        # κανόνα; Το ίδιο URL με τυχαία παράμετρο παρακάμπτει το edge cache
        # και απαντά στο ερώτημα αμέσως.
        fresh=$(cc_of "${BASE}${path}?dj-cachebust=1")

        if [ -n "$fresh" ]; then
            printf '%s ✗ %s%s  → ΚΟΛΛΗΜΕΝΟ ΣΤΟ EDGE CACHE ΤΟΥ ΠΑΡΟΧΟΥ\n' "$RED" "$path" "$OFF"
            printf '   %sη ρύθμιση είναι ΣΩΣΤΗ — με ?dj-cachebust=1 επιστρέφει: %s\n' "$DIM" "$fresh"
            printf '   ο πάροχος σερβίρει αντίγραφο αποθηκευμένο πριν την αλλαγή.\n'
            printf '   ➜ Το StackCP του netmind ΔΕΝ έχει κουμπί Purge. Άλλαξε το\n'
            printf '     ΠΕΡΙΕΧΟΜΕΝΟ του αρχείου (π.χ. CACHE_VERSION στο sw.js) —\n'
            printf '     νέο ETag σημαίνει ότι το cache υποχρεώνεται να το ξαναπάρει.%s\n' "$OFF"
        else
            printf '%s ✗ %s%s  → ΚΑΜΙΑ κεφαλίδα Cache-Control (%s)\n' "$RED" "$path" "$OFF" "$label"
            printf '   %sούτε με παράκαμψη cache — ο κανόνας στο .htaccess δεν εφαρμόζεται.%s\n' "$DIM" "$OFF"
        fi
        fails=$((fails + 1))
    elif printf '%s' "$value" | grep -q "$want"; then
        printf '%s ✓ %s%s  %s%s%s\n' "$GREEN" "$path" "$OFF" "$DIM" "$value" "$OFF"
    else
        printf '%s ⚠ %s%s  → %s\n' "$YELLOW" "$path" "$OFF" "$value"
        printf '   %sπερίμενα να περιέχει «%s» (%s)%s\n' "$DIM" "$want" "$label" "$OFF"
        warns=$((warns + 1))
    fi
}

check_cache_header "/" "no-store" "σελίδες: ποτέ σε cache"

# Το sw.js και το manifest ζητούνται από τη σελίδα ΜΕ αποτύπωμα ?v=<mtime>,
# όχι γυμνά. Ελέγχουμε το URL που χρησιμοποιεί πραγματικά η εφαρμογή —
# αλλιώς μετράμε ένα αντίγραφο που κανείς δεν ζητά πια.
#
# Ο λόγος που φτάσαμε εδώ: το edge cache του παρόχου κρατούσε το γυμνό
# /sw.js και ΔΕΝ επικύρωνε — αγνοούσε το ETag. Το γυμνό URL σέρβιρε v2
# ενώ ο δίσκος είχε v3. Χωρίς κουμπί Purge, η μόνη διέξοδος ήταν να
# σταματήσουμε να ζητάμε αυτό το URL.
home=$(curl -sS -L --max-time 15 "${BASE}/" 2>/dev/null)

sw_url=$(printf '%s' "$home" | grep -oE "serviceWorker\.register\('[^']+'" | head -1 | sed "s/.*'\(.*\)'/\1/")
mf_url=$(printf '%s' "$home" | grep -oE 'rel="manifest" href="[^"]+"' | head -1 | sed 's/.*href="\(.*\)"/\1/')

if [ -n "$sw_url" ]; then
    check_cache_header "${sw_url#"$BASE"}" "no-cache" "service worker: πάντα φρέσκος"
else
    printf '%s ⚠ %sδεν βρέθηκε καταχώρηση service worker στην αρχική\n' "$YELLOW" "$OFF"
    warns=$((warns + 1))
fi

if [ -n "$mf_url" ]; then
    check_cache_header "${mf_url#"$BASE"}" "no-cache" "manifest: πάντα φρέσκο"
else
    check_cache_header "/manifest.json" "no-cache" "manifest: πάντα φρέσκο"
fi

# ── 3. Αποτύπωμα έκδοσης: μπαίνει το ?v= στις σελίδες; ─────────────
#
# Το Asset::url() είναι η πρώτη γραμμή άμυνας. Αν λείπει από μια σελίδα,
# αυτή η σελίδα είναι εκτεθειμένη ξανά στο ίδιο πρόβλημα.

echo
echo "3. Αποτύπωμα έκδοσης (?v=) στη φόρμα εγγραφής"

page=$(curl -sS -L --max-time 15 "${BASE}/drivers/register" 2>/dev/null)
total=$(printf '%s' "$page" | grep -oE '(href|src)="[^"]*\.(css|js)[^"]*"' | wc -l | tr -d ' ')
stamped=$(printf '%s' "$page" | grep -oE '(href|src)="[^"]*\.(css|js)\?v=[0-9]+"' | wc -l | tr -d ' ')

if [ "$total" = "0" ]; then
    printf '%s ⚠ %sδεν βρέθηκαν αναφορές σε css/js — φόρτωσε η σελίδα;\n' "$YELLOW" "$OFF"
    warns=$((warns + 1))
elif [ "$stamped" = "$total" ]; then
    printf '%s ✓ %s%s/%s αρχεία με αποτύπωμα\n' "$GREEN" "$OFF" "$stamped" "$total"
else
    printf '%s ⚠ %s%s/%s αρχεία με αποτύπωμα — τα υπόλοιπα δεν περνούν από Asset::url()\n' \
        "$YELLOW" "$OFF" "$stamped" "$total"
    printf '%s' "$page" | grep -oE '(href|src)="[^"]*\.(css|js)"' | sed 's/^/     /'
    warns=$((warns + 1))
fi

# ── Σύνοψη ─────────────────────────────────────────────────────────

echo
echo "────────────────────────────────────────────────────────────"
if [ "$fails" -gt 0 ]; then
    printf '%sΒΡΕΘΗΚΑΝ %s ΣΦΑΛΜΑΤΑ%s' "$RED" "$fails" "$OFF"
    [ "$warns" -gt 0 ] && printf ' και %s προειδοποιήσεις' "$warns"
    echo
    echo
    echo "Τι να κάνεις, με τη σειρά:"
    echo "  1. StackCP → Cache → Purge (καθαρίζει το edge cache του παρόχου)"
    echo "  2. Ξανατρέξε αυτό το script"
    echo "  3. Αν επιμένει: cd ~/drivejob && touch public/css/*.css"
    echo "     (αλλάζει το mtime, άρα και το ?v= — νέο URL που κανένα cache δεν έχει)"
    exit 1
elif [ "$warns" -gt 0 ]; then
    printf '%sΌλα λειτουργούν, με %s προειδοποιήσεις%s\n' "$YELLOW" "$warns" "$OFF"
    exit 0
else
    printf '%sΌλα καθαρά — κανένα πρόβλημα cache%s\n' "$GREEN" "$OFF"
    exit 0
fi
