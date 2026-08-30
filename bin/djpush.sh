#!/usr/bin/env bash
#
# djpush — μία εντολή για να πάει ο κώδικας online. (31/08/2026)
#
# ══════════════════════════════════════════════════════════════════════
#  ΓΙΑΤΙ ΥΠΑΡΧΕΙ
# ══════════════════════════════════════════════════════════════════════
#
# Στις 30/08 μαζεύτηκαν τρία πακέτα ασχολίαστα και δουλέψαμε ώρες
# κοιτάζοντας παλιά έκδοση στο drivejob.gr. Τρία πράγματα φταίγανε:
#
#   1. Το `.git/index.lock` που άφηνε πίσω ένα `git status` του βοηθού
#      (δεν έχει δικαίωμα διαγραφής στα αρχεία του Κώστα) και μπλόκαρε
#      το commit με μήνυμα που έμοιαζε σοβαρό.
#   2. Το `git add && git commit && git push` σε τρία κομμάτια — αν
#      σκάσει το πρώτο, τα υπόλοιπα δεν τρέχουν και δεν είναι προφανές.
#   3. Καμία επιβεβαίωση ότι το push πραγματικά έφυγε.
#
# Χρήση:
#     djpush "τι άλλαξε"
#     djpush              (χωρίς μήνυμα — βάζει ημερομηνία)
#
# Εγκατάσταση: μία γραμμή στο ~/.zshrc — δες το τέλος του αρχείου.

djpush() {
    local repo="$HOME/Herd/drivejob"
    local msg="${*:-Ενημέρωση $(date '+%d/%m/%Y %H:%M')}"

    cd "$repo" 2>/dev/null || { echo "✗ Δεν βρέθηκε ο φάκελος $repo"; return 1; }

    # Το κλείδωμα που μένει πίσω από εργαλεία χωρίς δικαίωμα διαγραφής.
    # Ασφαλές: αν έτρεχε πραγματικά git, το επόμενο βήμα θα το πει.
    if [ -f .git/index.lock ]; then
        rm -f .git/index.lock && echo "• καθαρίστηκε παλιό index.lock"
    fi

    # Τίποτα να στείλουμε; Δεν φτιάχνουμε κενό commit.
    if [ -z "$(git status --porcelain)" ]; then
        echo "• Καμία αλλαγή — τίποτα να ανέβει."
        echo "  Τελευταίο commit: $(git log -1 --format='%s' 2>/dev/null)"
        return 0
    fi

    echo "── Θα ανέβουν ──────────────────────────────"
    git status --short
    echo "────────────────────────────────────────────"

    git add -A || { echo "✗ Το git add απέτυχε"; return 1; }
    git commit -m "$msg" || { echo "✗ Το commit απέτυχε"; return 1; }

    if git push; then
        echo ""
        echo "✓ Ανέβηκε: $msg"
        echo "  Το deploy ξεκίνησε. Σε 2-3 λεπτά:"
        echo "  → https://drivejob.gr/drivers/profile"
        echo "  Αν δεν δεις αλλαγές: Cmd+Shift+R (cache του browser)"
    else
        echo "✗ Το push απέτυχε — το commit έγινε τοπικά."
        echo "  Δοκίμασε ξανά: git push"
        return 1
    fi
}

# Πόσο πίσω είναι η παραγωγή; Απαντά χωρίς να αγγίξει τίποτα.
#
# ΧΩΡΙΣ ΠΑΡΕΝΘΕΣΕΙΣ ΣΤΑ ΜΗΝΥΜΑΤΑ (31/08): η πρώτη γραφή έγραφε
# «4 αρχείο(α) ΔΕΝ έχουν γίνει commit». Ο Κώστας αντέγραψε τη γραμμή
# ολόκληρη στο terminal, το zsh διάβασε το «αρχείο(α)» ως glob με
# qualifier και απάντησε «unknown file attribute: ^». Η έξοδος ενός
# εργαλείου πρέπει να είναι ασφαλής ακόμη κι αν κολληθεί κατά λάθος.
djstatus() {
    cd "$HOME/Herd/drivejob" 2>/dev/null || return 1

    echo "Τελευταίο commit: $(git log -1 --format='%s  — %cr')"

    local unpushed dirty
    unpushed=$(git log --oneline @{u}..HEAD 2>/dev/null | wc -l | tr -d ' ')
    dirty=$(git status --porcelain | wc -l | tr -d ' ')

    if [ "$unpushed" = "0" ] && [ "$dirty" = "0" ]; then
        echo "✓ Όλα ανεβασμένα στο drivejob.gr"
        return 0
    fi

    [ "$unpushed" != "0" ] && echo "⚠ $unpushed commit δεν έχουν σταλεί στο GitHub"
    [ "$dirty" != "0" ] && echo "⚠ $dirty αρχεία δεν έχουν γίνει commit"

    echo ""
    echo "Αντίγραψε ΜΟΝΟ την επόμενη γραμμή:"
    echo ""
    echo "djpush \"$(date '+%d/%m')\" "
}

# ══════════════════════════════════════════════════════════════════════
#  ΕΓΚΑΤΑΣΤΑΣΗ (μία φορά)
#
#  echo 'source ~/Herd/drivejob/bin/djpush.sh' >> ~/.zshrc && source ~/.zshrc
#
#  Το script ζει μέσα στο repo, οπότε ενημερώνεται μαζί με τον κώδικα —
#  δεν χρειάζεται να ξαναπειραχτεί το ~/.zshrc ποτέ.
# ══════════════════════════════════════════════════════════════════════
