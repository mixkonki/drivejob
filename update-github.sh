#!/bin/bash

# Ενημέρωση του GitHub με τις αλλαγές που έχουμε κάνει
# Αυτό το script προσθέτει, κάνει commit και push τις αλλαγές στο GitHub

# Έλεγχος αν το Git είναι εγκατεστημένο
if ! command -v git &> /dev/null; then
    echo "Το Git δεν είναι εγκατεστημένο. Παρακαλώ εγκαταστήστε το Git."
    exit 1
fi

# Έλεγχος αν είμαστε σε Git repository
if [ ! -d .git ]; then
    echo "Δεν βρέθηκε Git repository. Παρακαλώ εκτελέστε 'git init' πρώτα."
    exit 1
fi

# Προσθήκη όλων των αλλαγών
echo "Προσθήκη όλων των αλλαγών..."
git add .

# Commit των αλλαγών
echo "Commit των αλλαγών..."
git commit -m "Βελτιώσεις στην ασφάλεια των διαδρομών αυθεντικοποίησης και προσθήκη MCP servers για διαχείριση αρχείων μαθήματος και χρονοδιαγραμμάτων"

# Push των αλλαγών
echo "Push των αλλαγών στο GitHub..."
git push origin main

echo "Η ενημέρωση του GitHub ολοκληρώθηκε με επιτυχία!"
