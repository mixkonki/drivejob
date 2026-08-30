<?php

namespace Drivejob\Services\Score;

use RuntimeException;
use ZipArchive;

/**
 * Αναγνώστης του «Αναλυτικού Λογαριασμού Ασφάλισης» e-ΕΦΚΑ (.xlsx).
 * (01/09/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΧΤΙΣΜΕΝΟΣ ΠΑΝΩ ΣΕ ΠΡΑΓΜΑΤΙΚΑ ΑΡΧΕΙΑ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Ο Κώστας ανέβασε τα ΤΡΙΑ δικά του exports (01/09) και βγήκαν τρία
 * διαφορετικά σχήματα — ένα ανά «ζωή» του ίδιου ανθρώπου:
 *
 *   ΙΚΑ (φορέας 21001):  Α.Μ. Εργοδότη + ΕΠΩΝΥΜΙΑ Εργοδότη + Ημέρες
 *                        ασφάλισης ανά μήνα (25 = πλήρης) + αποδοχές.
 *                        ΜΙΣΘΩΤΟΣ.
 *   ΤΕΒΕ/ΤΣΑ (21013):    χωρίς εργοδότη, Έτη/Μήνες/Ημέρες ανά περίοδο.
 *                        ΑΥΤΟΑΠΑΣΧΟΛΟΥΜΕΝΟΣ (προ-ΟΑΕΕ ταμείο).
 *   ΟΑΕΕ (99999):        στήλη «Ειδικότητα» = 'ΟΑΕΕ', μήνες ανά περίοδο.
 *                        ΑΥΤΟΑΠΑΣΧΟΛΟΥΜΕΝΟΣ.
 *
 * Άρα η απάντηση στο «τα ένσημα αφορούν μόνο μισθωτούς» είναι ΟΧΙ:
 * ο ίδιος λογαριασμός ασφάλισης περιέχει ΚΑΙ τις περιόδους μη μισθωτού
 * (ΟΑΕΕ/ΤΕΒΕ/ΤΣΑ). Ό,τι ΔΕΝ λέει για τον αυτοαπασχολούμενο είναι το
 * ΑΝΤΙΚΕΙΜΕΝΟ της δουλειάς — αυτό το λέει ο ΚΑΔ της ΑΑΔΕ (βλ.
 * InsuranceRecordCollector).
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ ΧΩΡΙΣ ΒΙΒΛΙΟΘΗΚΗ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Το project δεν έχει PhpSpreadsheet και δεν θα την προσθέσουμε για ένα
 * αρχείο με σταθερό σχήμα: ~30MB εξαρτήσεις για 100 γραμμές ανάγνωσης.
 * Το .xlsx είναι zip με XML — ZipArchive + SimpleXML αρκούν, και τα
 * shared hosting τα έχουν πάντα.
 *
 * ΠΡΟΣΟΧΗ ΑΚΕΡΑΙΟΤΗΤΑΣ: το xlsx ΔΕΝ φέρει υπογραφή — επεξεργάζεται με
 * ένα Excel. Γι' αυτό οι περίοδοι αποθηκεύονται ως «ανεπιβεβαίωτες» και
 * μετράνε ΜΕΙΩΜΕΝΕΣ μέχρι να ελεγχθούν (βλ. collector). Το αδιάβλητο
 * έγγραφο είναι η Βεβαίωση με κωδικό επαλήθευσης docs.gov.gr — όταν
 * μπει εκείνος ο έλεγχος, το verified γυρίζει σε 1.
 */
final class InsuranceXlsx
{
    /** Ταμεία μη μισθωτών: ΟΑΕΕ (99999) και προκάτοχοι (ΤΕΒΕ/ΤΣΑ 21013 κ.ά.). */
    private const SELF_EMPLOYED_FUNDS = ['99999', '21013', '21014', '21015'];

    /** ΙΚΑ: 25 ημέρες ασφάλισης = πλήρης μήνας. */
    private const DAYS_PER_MONTH = 25;

    /**
     * @return array{periods: array<int, array>, warnings: string[]}
     *   κάθε period: fund, fund_kind (employee|self_employed),
     *   employer_name, date_from, date_to, months (float)
     */
    public function parse(string $path): array
    {
        $rows = $this->readSheet($path);
        if (count($rows) < 2) {
            throw new RuntimeException('Το αρχείο δεν περιέχει γραμμές δεδομένων.');
        }

        $head = array_map(static fn($v) => trim((string) $v), $rows[0]);
        $col = array_flip($head);

        // Αναγνώριση σχήματος: αυτές οι στήλες υπάρχουν σε ΟΛΑ τα exports.
        foreach (['Κωδικός Φορέα', 'Από', 'Έως'] as $required) {
            if (!isset($col[$required])) {
                throw new RuntimeException(
                    'Το αρχείο δεν μοιάζει με Αναλυτικό Λογαριασμό Ασφάλισης e-ΕΦΚΑ '
                    . '(λείπει η στήλη «' . $required . '»).'
                );
            }
        }

        $get = static function (array $row, string $name) use ($col): string {
            return isset($col[$name], $row[$col[$name]]) ? trim((string) $row[$col[$name]]) : '';
        };

        /*
         * Οι γραμμές ΕΠΑΝΑΛΑΜΒΑΝΟΝΤΑΙ ανά κλάδο (ΣΥΝΤΑΞΗ/ΥΓΕΙΑ/ΟΑΕΔ…)
         * και ανά τύπο αποδοχών (τακτικές/δώρα/επιδόματα). Χωρίς
         * απαλοιφή, ο ίδιος μήνας θα μετριόταν 2-4 φορές — μετρημένο
         * στα αρχεία: 345 γραμμές → 86 πραγματικές περίοδοι.
         *
         * Κλειδί: (φορέας, εργοδότης, από, έως). Για τα ΙΚΑ κρατάμε το
         * ΜΕΓΙΣΤΟ ημερών της ομάδας (η γραμμή «Δώρο» έχει 0 ημέρες —
         * το άθροισμα θα ήταν σωστό μόνο κατά σύμπτωση).
         */
        $periods = [];
        $warnings = [];

        foreach (array_slice($rows, 1) as $row) {
            $fund = $get($row, 'Κωδικός Φορέα');
            if ($fund === '') {
                continue;
            }

            $from = $this->date($get($row, 'Από'));
            $to = $this->date($get($row, 'Έως'));
            if (!$from || !$to) {
                continue;
            }

            $employer = $get($row, 'Επωνυμία Εργοδότη');
            $employerId = $get($row, 'Α.Μ. Εργοδότη');
            $key = $fund . '|' . $employerId . '|' . $from . '|' . $to;

            $isEmployee = isset($col['Α.Μ. Εργοδότη']);
            if ($isEmployee) {
                $days = (float) str_replace(',', '.', $get($row, 'Ημέρες') ?: '0');
                if (!isset($periods[$key])) {
                    $periods[$key] = [
                        'fund' => $fund,
                        'fund_kind' => 'employee',
                        'employer_name' => $employer,
                        'date_from' => $from,
                        'date_to' => $to,
                        'months' => 0.0,
                        '_days' => 0.0,
                    ];
                }
                $periods[$key]['_days'] = max($periods[$key]['_days'], $days);
                // Η επωνυμία λείπει από κάποιες γραμμές της ομάδας — κρατάμε όποια βρεθεί.
                if ($employer !== '' && $periods[$key]['employer_name'] === '') {
                    $periods[$key]['employer_name'] = $employer;
                }
            } else {
                if (isset($periods[$key])) {
                    continue;   // δεύτερος κλάδος της ίδιας περιόδου
                }
                $y = (int) ($get($row, 'Έτη') ?: 0);
                $m = (int) ($get($row, 'Μήνες') ?: 0);
                $d = (int) ($get($row, 'Ημέρες') ?: 0);
                $periods[$key] = [
                    'fund' => $fund,
                    'fund_kind' => in_array($fund, self::SELF_EMPLOYED_FUNDS, true)
                        ? 'self_employed' : 'employee',
                    'employer_name' => $employer,
                    'date_from' => $from,
                    'date_to' => $to,
                    'months' => $y * 12 + $m + $d / 30,
                ];
            }
        }

        foreach ($periods as &$p) {
            if (isset($p['_days'])) {
                $p['months'] = $p['_days'] / self::DAYS_PER_MONTH;
                unset($p['_days']);
            }
            $p['months'] = round($p['months'], 2);
        }
        unset($p);

        if (!$periods) {
            $warnings[] = 'Δεν αναγνωρίστηκε καμία περίοδος ασφάλισης.';
        }

        return ['periods' => array_values($periods), 'warnings' => $warnings];
    }

    // ══════════════════════════════════════════════════════════════════
    //  Ο ΕΛΑΧΙΣΤΟΣ ΑΝΑΓΝΩΣΤΗΣ XLSX
    // ══════════════════════════════════════════════════════════════════

    /** @return array<int, array<int, string>> γραμμές × κελιά */
    private function readSheet(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Το αρχείο δεν είναι έγκυρο .xlsx.');
        }

        try {
            // Shared strings: τα κείμενα ζουν σε ξεχωριστό XML και τα
            // κελιά τα δείχνουν με αριθμό.
            $shared = [];
            $ss = $zip->getFromName('xl/sharedStrings.xml');
            if ($ss !== false) {
                $xml = simplexml_load_string($ss);
                foreach ($xml->si as $si) {
                    // Είτε σκέτο <t>, είτε rich text με πολλά <r><t>.
                    $text = '';
                    if (isset($si->t)) {
                        $text = (string) $si->t;
                    } else {
                        foreach ($si->r as $r) {
                            $text .= (string) $r->t;
                        }
                    }
                    $shared[] = $text;
                }
            }

            $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($sheet === false) {
                throw new RuntimeException('Το αρχείο δεν περιέχει φύλλο δεδομένων.');
            }

            $xml = simplexml_load_string($sheet);
            $rows = [];
            foreach ($xml->sheetData->row as $row) {
                $cells = [];
                foreach ($row->c as $c) {
                    // Η θέση της στήλης από την αναφορά κελιού (π.χ. C7 → 2).
                    $ref = (string) $c['r'];
                    $letters = preg_replace('/\d+/', '', $ref);
                    $i = 0;
                    foreach (str_split($letters) as $ch) {
                        $i = $i * 26 + (ord($ch) - 64);
                    }
                    $i--;

                    $type = (string) $c['t'];
                    if ($type === 's') {
                        $cells[$i] = $shared[(int) $c->v] ?? '';
                    } elseif ($type === 'inlineStr') {
                        $cells[$i] = (string) $c->is->t;
                    } else {
                        $cells[$i] = (string) $c->v;
                    }
                }
                if ($cells) {
                    // Συμπλήρωση κενών στηλών ώστε τα index να ευθυγραμμίζονται.
                    $max = max(array_keys($cells));
                    $line = array_fill(0, $max + 1, '');
                    foreach ($cells as $i => $v) {
                        $line[$i] = $v;
                    }
                    $rows[] = $line;
                }
            }

            return $rows;
        } finally {
            $zip->close();
        }
    }

    /** «01/01/2017» ή σειριακός αριθμός Excel → «Y-m-d», αλλιώς null. */
    private function date(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $value, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }
        // Excel serial (ημέρες από 30/12/1899) — για την περίπτωση που
        // κάποιο export γράφει τις ημερομηνίες ως αριθμούς.
        if (is_numeric($value) && (float) $value > 20000 && (float) $value < 60000) {
            return date('Y-m-d', (int) (((float) $value - 25569) * 86400));
        }
        return null;
    }
}
