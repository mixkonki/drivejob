#!/usr/bin/env bash
#
# Έλεγχος διαρροής προσωπικών δεδομένων.
#
# ═══════════════════════════════════════════════════════════════════════════
#  ΓΙΑΤΙ ΥΠΑΡΧΕΙ
# ═══════════════════════════════════════════════════════════════════════════
#
# Σε μία μέρα βρέθηκαν ΤΡΕΙΣ ανεξάρτητες διαρροές, και οι τρεις με την ίδια
# αιτία: ένα ερώτημα `SELECT *` που έφτανε αυτούσιο στην απόκριση.
#
#   1. /job-listings          → contact_email, contact_phone κάθε αγγελίας
#   2. /api/matching/…        → ονοματεπώνυμο και email 20 οδηγών ανά αγγελία
#   3. /companies/search      → password hash, reset_token, ΑΦΜ κάθε εταιρίας
#
# Καμία δεν έδινε σφάλμα. Καμία δεν φαινόταν στη σελίδα. Και οι τρεις ήταν
# ορατές με μία εντολή curl, χωρίς λογαριασμό.
#
# Ο έλεγχος με το μάτι δεν τις πιάνει: το πρόβλημα δεν είναι σε αυτό που
# ΒΛΕΠΕΙΣ, αλλά σε αυτό που ΤΑΞΙΔΕΥΕΙ δίπλα του. Αυτό το script κοιτάζει το
# δεύτερο.
#
# ═══════════════════════════════════════════════════════════════════════════
#  ΧΡΗΣΗ
# ═══════════════════════════════════════════════════════════════════════════
#
#   bash scripts/check-leaks.sh                      # παραγωγή, ανώνυμος
#   bash scripts/check-leaks.sh http://drivejob.test # άλλο περιβάλλον
#
# Για έλεγχο και με συνδεδεμένους ρόλους, δώσε διαπιστευτήρια:
#
#   DJ_DRIVER='email:password' DJ_COMPANY='email:password' \
#     bash scripts/check-leaks.sh
#
# ΜΟΝΑ εισαγωγικά, όχι διπλά: ένα συνθηματικό που περιέχει «!» μέσα σε
# διπλά εισαγωγικά το ερμηνεύει το bash ως αναφορά στο ιστορικό εντολών
# («event not found») και το αίτημα φεύγει με λάθος κωδικό.
#
# ΤΡΕΞΕ ΤΟ: μετά από κάθε αλλαγή σε controller ή repository, και οπωσδήποτε
# πριν από κάθε ανέβασμα στην παραγωγή.

set -u

BASE="${1:-https://drivejob.gr}"
BASE="${BASE%/}"

RED=$'\033[0;31m'; GRN=$'\033[0;32m'; YEL=$'\033[0;33m'; DIM=$'\033[2m'; OFF=$'\033[0m'

fails=0
checked=0

# ── Τι θεωρείται διαρροή ────────────────────────────────────────────────
#
# Χωρισμένα σε δύο βαθμίδες. Τα πρώτα δεν επιτρέπεται να φύγουν ΠΟΤΕ, από
# κανένα endpoint, σε κανέναν ρόλο — ούτε καν στον ίδιο τον χρήστη, γιατί
# δεν τα χρειάζεται καμία σελίδα.

NEVER='password password_hash reset_token verification_token remember_token api_key secret'

# Τα δεύτερα είναι προσωπικά δεδομένα: επιτρέπονται μόνο όταν ο θεατής έχει
# κερδίσει την πρόσβαση (βλ. Visibility). Σε ανώνυμο αίτημα δεν έχουν θέση.
PERSONAL='contact_email contact_phone vat_number tax_number iban afm social_security'

TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT

# ── Σύνδεση (προαιρετική) ───────────────────────────────────────────────

login() {  # $1=creds "email:pass"  $2=jar
    [ -z "${1:-}" ] && return 1
    local email="${1%%:*}" pass="${1#*:}" jar="$2" tok

    tok=$(curl -sS -c "$jar" --max-time 15 "$BASE/auth/login" 2>/dev/null \
          | grep -oE 'name="csrf_token" value="[^"]*"' | head -1 | sed 's/.*value="\(.*\)"/\1/')
    [ -z "$tok" ] && return 1

    local dest
    dest=$(curl -sS -b "$jar" -c "$jar" --max-time 15 -X POST "$BASE/auth/login" \
        --data-urlencode "csrf_token=$tok" \
        --data-urlencode "email=$email" \
        --data-urlencode "password=$pass" \
        -o /dev/null -w '%{redirect_url}' 2>/dev/null)

    # Η ΕΠΑΛΗΘΕΥΣΗ ΓΙΝΕΤΑΙ ΑΠΟ ΤΟΝ ΠΡΟΟΡΙΣΜΟ ΤΗΣ ΑΝΑΚΑΤΕΥΘΥΝΣΗΣ.
    #
    # Η ύπαρξη cookie δεν αποδεικνύει τίποτα — συνεδρία παίρνει και ο
    # ανώνυμος επισκέπτης. Ένα σταθερό URL δοκιμής δεν κάνει ούτε αυτό,
    # γιατί κάθε ρόλος έχει διαφορετικές σελίδες.
    #
    # Η επιτυχής σύνδεση στέλνει τον χρήστη στον πίνακά του· η αποτυχία τον
    # γυρίζει πίσω στη φόρμα σύνδεσης. Αυτό ισχύει για κάθε ρόλο.
    case "$dest" in
        *login*|'') return 1;;
        *) return 0;;
    esac
}

ROLES="anon"
if login "${DJ_DRIVER:-}" "$TMP/driver"; then ROLES="$ROLES driver"; fi
if login "${DJ_COMPANY:-}" "$TMP/company"; then ROLES="$ROLES company"; fi

# ── Ο έλεγχος ───────────────────────────────────────────────────────────

probe() {  # $1=διαδρομή  $2=ρόλος
    local path="$1" role="$2" jar="" body found=""

    [ "$role" != "anon" ] && jar="-b $TMP/$role"

    body=$(curl -sS --max-time 15 -H "X-Requested-With: XMLHttpRequest" \
           $jar "$BASE$path" 2>/dev/null | head -c 400000)

    # Μόνο αποκρίσεις JSON έχουν νόημα εδώ.
    case "$body" in
        \{*|\[*) ;;
        *) return 0;;
    esac

    checked=$((checked + 1))

    for k in $NEVER; do
        printf '%s' "$body" | grep -q "\"$k\"" && found="$found $k(ΠΟΤΕ)"
    done

    if [ "$role" = "anon" ]; then
        for k in $PERSONAL; do
            printf '%s' "$body" | grep -q "\"$k\":\"[^\"]" && found="$found $k"
        done
        printf '%s' "$body" | grep -qE '"[a-z_]*email[a-z_]*":"[^"]+@' && found="$found email"
    fi

    if [ -n "$found" ]; then
        printf '%s ✗ %-42s [%s]%s%s\n' "$RED" "$path" "$role" "$found" "$OFF"
        fails=$((fails + 1))
    fi
}

echo
echo "Έλεγχος διαρροών στο ${BASE}"
echo "Ρόλοι: ${ROLES}"
echo "────────────────────────────────────────────────────────────────"
echo

# Οι διαδρομές που επιστρέφουν δεδομένα. Πρόσθεσε εδώ κάθε νέο endpoint.
PATHS="
/job-listings
/companies/search
/drivers/search
/api/matching/driver/matches
/api/matching/job/candidates
/api/fleet/vehicles
/api/drivers/stats
/job-applications/my-applications
/job-applications/company-applications
"

for p in $PATHS; do
    for r in $ROLES; do
        probe "$p" "$r"
    done
done

echo "────────────────────────────────────────────────────────────────"

if [ "$fails" -gt 0 ]; then
    printf '%sΒΡΕΘΗΚΑΝ %s ΔΙΑΡΡΟΕΣ%s  (σε %s αποκρίσεις JSON)\n\n' "$RED" "$fails" "$OFF" "$checked"
    echo "Η διόρθωση είναι ΠΑΝΤΑ λίστα επιτρεπτών πεδίων, ποτέ αφαίρεση:"
    echo
    echo "    \$public = ['id', 'title', 'city'];"
    echo "    \$row = array_intersect_key(\$row, array_flip(\$public));"
    echo
    printf '%sΤο unset() των «κακών» πεδίων αποτυγχάνει την πρώτη φορά που\n' "$DIM"
    printf 'θα προστεθεί νέα στήλη στον πίνακα.%s\n' "$OFF"
    exit 1
fi

printf '%sΚαμία διαρροή σε %s αποκρίσεις JSON%s\n' "$GRN" "$checked" "$OFF"

if [ "$ROLES" = "anon" ]; then
    printf '%sΕλέγχθηκε μόνο ο ανώνυμος — η δημόσια πλευρά.\n' "$YEL"
    printf 'Για πλήρη έλεγχο, με ΜΟΝΑ εισαγωγικά:\n\n'
    printf "  DJ_DRIVER='email:pass' DJ_COMPANY='email:pass' bash %s\n\n" "$0"
    printf '%s(διπλά εισαγωγικά σπάνε σε συνθηματικά με «!» — το bash τα\n' "$DIM"
    printf 'διαβάζει ως ιστορικό εντολών)%s\n' "$OFF"
elif [ -n "${DJ_DRIVER:-}${DJ_COMPANY:-}" ]; then
    # Αν δόθηκαν διαπιστευτήρια αλλά κάποιος ρόλος λείπει, ο έλεγχος είναι
    # ελλιπής και ΔΕΝ πρέπει να περάσει για πλήρης.
    for want in driver company; do
        var="DJ_$(printf '%s' "$want" | tr '[:lower:]' '[:upper:]')"
        eval "given=\${$var:-}"
        case " $ROLES " in
            *" $want "*) ;;
            *) [ -n "$given" ] && printf '%s⚠ Η σύνδεση ως %s απέτυχε — ο ρόλος δεν ελέγχθηκε.%s\n' "$YEL" "$want" "$OFF";;
        esac
    done
fi
