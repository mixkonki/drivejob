<?php

namespace Drivejob\Services\Driver;

use Drivejob\Helpers\GreekText;
use TCPDF;

/**
 * ΤΟ ΒΙΟΓΡΑΦΙΚΟ ΣΕ PDF — δεύτερος renderer του DriverCvService.
 * (ξαναγράφτηκε 30/08/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΙ ΗΤΑΝ ΛΑΘΟΣ ΣΤΗΝ ΠΡΩΤΗ ΓΡΑΦΗ
 * ══════════════════════════════════════════════════════════════════════
 *
 * «Το βιογραφικό είναι μία σύνοψη της επισκόπησης ενώ θα έπρεπε να είναι
 * βιογραφικό.» Σωστό, και η αιτία ήταν δομική:
 *
 *  - ΞΕΚΙΝΟΥΣΕ ΜΕ ΚΑΤΑΛΟΓΟ ΑΔΕΙΩΝ. Ένα βιογραφικό ξεκινά με το ποιος
 *    είσαι και τι έχεις κάνει· οι πιστοποιήσεις έρχονται μετά.
 *  - ΚΑΜΙΑ ΣΥΝΟΨΗ. Ο αναγνώστης έπρεπε να συνθέσει μόνος του την εικόνα
 *    από 15 σειρές «ετικέτα: τιμή».
 *  - ΚΑΜΙΑ ΟΜΑΔΟΠΟΙΗΣΗ. Δίπλωμα, ΠΕΙ, ADR, ταχογράφος, ειδικές άδειες
 *    και μηχανήματα έργου σε μία ενιαία στοίβα.
 *  - ΚΑΜΙΑ ΗΛΙΚΙΑ — από τα πρώτα που κοιτάζει ένας εργοδότης.
 *  - ΤΟΝΟΙ ΣΕ ΚΕΦΑΛΑΙΑ: «ΤΥΠΙΚΆ ΠΡΟΣΌΝΤΑ», «ΠΡΟΫΠΗΡΕΣΊΑ».
 *
 * ══════════════════════════════════════════════════════════════════════
 *  Η ΝΕΑ ΔΟΜΗ — ΣΕΙΡΑ ΒΙΟΓΡΑΦΙΚΟΥ, ΟΧΙ ΣΕΙΡΑ ΒΑΣΗΣ ΔΕΔΟΜΕΝΩΝ
 * ══════════════════════════════════════════════════════════════════════
 *
 *   1. Κεφαλίδα     — όνομα, ηλικία, έδρα, επικοινωνία
 *   2. Προφίλ       — δύο-τρεις γραμμές: ποιος είναι
 *   3. Προϋπηρεσία  — ΠΡΩΤΑ, γιατί αυτή προσλαμβάνεται
 *   4. Άδειες &     — σε ΟΜΑΔΕΣ, όπως στην οθόνη
 *      πιστοποιήσεις
 *   5. Επιμόρφωση   — σεμινάρια
 *   6. Γλώσσες & δεξιότητες
 *
 * Η ομαδοποίηση έρχεται έτοιμη από τον service ($cv['qualifications']):
 * ό,τι βλέπει ο οδηγός στην οθόνη είναι ό,τι στέλνει στον εργοδότη.
 */
class DriverCvPdf
{
    private const RED = [179, 38, 30];
    private const GREY = [120, 128, 140];
    private const DARK = [17, 24, 39];
    private const LINE = [223, 226, 230];

    private const LEFT = 15;
    private const RIGHT = 195;

    private TCPDF $pdf;
    private array $cv;
    private array $profile;

    public function __construct(array $cv, array $profile)
    {
        $this->cv = $cv;
        $this->profile = $profile;
    }

    public function render(): string
    {
        $this->pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $this->pdf->SetCreator('DriveJob');
        $this->pdf->SetAuthor('DriveJob');
        $this->pdf->SetTitle('Βιογραφικό — ' . ($this->cv['identity']['full_name'] ?? ''));
        $this->pdf->SetMargins(self::LEFT, 14, 15);
        $this->pdf->SetAutoPageBreak(true, 16);
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        $this->pdf->AddPage();

        $this->header();
        $this->summary();
        $this->experience();
        $this->qualifications();
        $this->certifications();
        $this->languagesAndSkills();
        $this->footer();

        return $this->pdf->Output('cv.pdf', 'S');
    }

    // ══════════════════════════════════════════════════════════════════
    //  1. ΚΕΦΑΛΙΔΑ
    // ══════════════════════════════════════════════════════════════════

    private function header(): void
    {
        $id = $this->cv['identity'];

        /*
         * ΦΩΤΟΓΡΑΦΙΑ — μόνο αν την έχει επιλέξει ο οδηγός.
         *
         * Ο διακόπτης «Φωτογραφία» της οθόνης πρέπει να κάνει κάτι ΚΑΙ
         * εδώ: ένας διακόπτης που αλλάζει την προεπισκόπηση αλλά όχι το
         * αρχείο που στέλνεται είναι χειρότερος από κανέναν.
         *
         * Το identity['photo'] έρχεται ήδη null όταν η επιλογή είναι
         * κλειστή — ο service το φιλτράρει, όχι εδώ.
         */
        $textWidth = 0;
        if (!empty($id['photo'])) {
            $file = ROOT_DIR . '/public/' . ltrim((string) $id['photo'], '/');
            if (is_file($file) && is_readable($file)) {
                try {
                    // Δεξιά επάνω: δεν σπρώχνει το όνομα, δεν κλέβει πλάτος
                    // από τη γραμμή επικοινωνίας.
                    $this->pdf->Image($file, self::RIGHT - 26, 14, 26, 26, '', '', '', true, 300, '', false, false, 0, 'CT');
                    $textWidth = 30;
                } catch (\Throwable $e) {
                    // Χαλασμένο ή άγνωστου τύπου αρχείο: το βιογραφικό
                    // βγαίνει χωρίς φωτογραφία αντί να μη βγαίνει καθόλου.
                    $textWidth = 0;
                }
            }
        }

        // Το κείμενο σταματά πριν από τη φωτογραφία όταν αυτή υπάρχει.
        $w = $textWidth > 0 ? (self::RIGHT - self::LEFT - $textWidth) : 0;

        $this->pdf->SetFont('dejavusans', 'B', 19);
        $this->pdf->SetTextColor(...self::DARK);
        $this->pdf->Cell($w, 9, $id['full_name'], 0, 1);

        // Ηλικία και έδρα στην ίδια σειρά με το όνομα από κάτω: είναι τα
        // δύο πρώτα που ζυγίζει ο εργοδότης.
        $line1 = array_filter([
            $id['age_label'] ?: null,
            $id['location'] ?: null,
        ]);
        if ($line1) {
            $this->pdf->SetFont('dejavusans', '', 10);
            $this->pdf->SetTextColor(...self::GREY);
            $this->pdf->Cell($w, 5.5, implode('  ·  ', $line1), 0, 1);
        }

        $line2 = array_filter([
            $id['phone'] ?: null,
            $id['landline'] ?: null,
            $id['email'] ?: null,
        ]);
        if ($line2) {
            $this->pdf->SetFont('dejavusans', '', 9.5);
            $this->pdf->Cell($w, 5, implode('  ·  ', $line2), 0, 1);
        }

        $reach = $id['reach'] ?? [];
        if (!empty($reach['declared'])) {
            $extra = $reach['label'];
            if (!empty($reach['travel'])) {
                $extra .= '  ·  ταξίδια εκτός έδρας';
            }
            $this->pdf->Cell($w, 5, $extra, 0, 1);
        }

        if (!empty($id['rating']['count'])) {
            $this->pdf->Cell($w, 5, 'Αξιολόγηση ' . number_format($id['rating']['value'], 1) . '/5 από '
                . $id['rating']['count'] . ' εργοδότες', 0, 1);
        }

        $this->pdf->Ln(1.5);
        // Κάτω από τη φωτογραφία, αν αυτή είναι ψηλότερα από το κείμενο.
        $y = max($this->pdf->GetY(), $textWidth > 0 ? 43 : 0);
        $this->pdf->SetY($y);
        $this->pdf->SetDrawColor(...self::RED);
        $this->pdf->SetLineWidth(0.7);
        $this->pdf->Line(self::LEFT, $y, self::RIGHT, $y);
        $this->pdf->Ln(3);
    }

    // ══════════════════════════════════════════════════════════════════
    //  2. ΠΡΟΦΙΛ
    // ══════════════════════════════════════════════════════════════════

    private function summary(): void
    {
        $text = trim((string) ($this->cv['summary'] ?? ''));
        if ($text === '') {
            return;
        }

        $this->pdf->SetFont('dejavusans', '', 10);
        $this->pdf->SetTextColor(60, 66, 76);
        $this->pdf->MultiCell(0, 5.2, $text, 0, 'L');
        $this->pdf->Ln(1);
        $this->pdf->SetTextColor(...self::DARK);
    }

    // ══════════════════════════════════════════════════════════════════
    //  ΚΟΙΝΑ ΔΟΜΙΚΑ
    // ══════════════════════════════════════════════════════════════════

    /**
     * Τίτλος ενότητας. Τα κεφαλαία περνούν από τον GreekText: το
     * mb_strtoupper κρατά τους τόνους («ΠΡΟΫΠΗΡΕΣΊΑ»), που στα ελληνικά
     * κεφαλαία δεν μπαίνουν.
     */
    private function section(string $title, string $note = ''): void
    {
        if ($this->pdf->GetY() > 252) {
            $this->pdf->AddPage();
        }
        $this->pdf->Ln(2.5);

        $y = $this->pdf->GetY();
        $this->pdf->SetFont('dejavusans', 'B', 10.5);
        $this->pdf->SetTextColor(...self::RED);
        $this->pdf->Cell(0, 6, GreekText::upper($title), 0, 0);

        if ($note !== '') {
            $this->pdf->SetFont('dejavusans', '', 8.5);
            $this->pdf->SetTextColor(...self::GREY);
            $this->pdf->Cell(0, 6, $note, 0, 0, 'R');
        }
        $this->pdf->Ln(6);

        $this->pdf->SetDrawColor(...self::LINE);
        $this->pdf->SetLineWidth(0.25);
        $this->pdf->Line(self::LEFT, $this->pdf->GetY() - 0.6, self::RIGHT, $this->pdf->GetY() - 0.6);
        $this->pdf->Ln(1.2);
        $this->pdf->SetTextColor(...self::DARK);
    }

    /** Υποτίτλος ομάδας μέσα σε ενότητα (π.χ. «Άδεια Οδήγησης»). */
    private function subSection(string $title): void
    {
        if ($this->pdf->GetY() > 258) {
            $this->pdf->AddPage();
        }
        $this->pdf->Ln(1.2);
        $this->pdf->SetFont('dejavusans', 'B', 9);
        $this->pdf->SetTextColor(70, 76, 88);
        $this->pdf->Cell(0, 5, $title, 0, 1);
        $this->pdf->SetTextColor(...self::DARK);
    }

    private function pageBreakIfNeeded(float $limit = 262): void
    {
        if ($this->pdf->GetY() > $limit) {
            $this->pdf->AddPage();
        }
    }

    // ══════════════════════════════════════════════════════════════════
    //  3. ΠΡΟΫΠΗΡΕΣΙΑ — ΠΡΩΤΑ
    // ══════════════════════════════════════════════════════════════════

    private function experience(): void
    {
        $exp = $this->cv['experience'];
        if ($exp['count'] === 0) {
            return;
        }

        $this->section('Προϋπηρεσία', 'Σύνολο ' . $exp['total_label']);

        foreach ($exp['items'] as $item) {
            $this->pageBreakIfNeeded(256);

            // Διάρκεια αριστερά σε δική της στήλη — έτσι διαβάζεται η
            // πορεία κάθετα, όπως σε κάθε βιογραφικό.
            $y = $this->pdf->GetY();
            $this->pdf->SetFont('dejavusans', '', 8.5);
            $this->pdf->SetTextColor(...self::GREY);
            $this->pdf->MultiCell(38, 4.6, $item['period_label'] ?: $item['duration_label'], 0, 'L', false, 1, self::LEFT, $y);

            $this->pdf->SetXY(self::LEFT + 40, $y);
            $this->pdf->SetFont('dejavusans', 'B', 10);
            $this->pdf->SetTextColor(...self::DARK);
            $title = $item['category_label'];
            if ($item['type_label'] !== '') {
                $title .= ' — ' . $item['type_label'];
            }
            $this->pdf->MultiCell(0, 4.8, $title . ($item['current'] ? '  (τρέχουσα)' : ''), 0, 'L');

            $meta = array_filter([
                $item['duration_label'],
                $item['transport_label'] ?: null,
                $item['employment_label'] ?: null,
            ]);
            if ($meta) {
                $this->pdf->SetX(self::LEFT + 40);
                $this->pdf->SetFont('dejavusans', '', 8.8);
                $this->pdf->SetTextColor(...self::GREY);
                $this->pdf->MultiCell(0, 4.4, implode('  ·  ', $meta), 0, 'L');
            }

            if ($item['description'] !== '') {
                $this->pdf->SetX(self::LEFT + 40);
                $this->pdf->SetFont('dejavusans', '', 9);
                $this->pdf->SetTextColor(60, 66, 76);
                $this->pdf->MultiCell(0, 4.5, $item['description'], 0, 'L');
            }

            $this->pdf->SetTextColor(...self::DARK);
            $this->pdf->Ln(1.6);
        }
    }

    // ══════════════════════════════════════════════════════════════════
    //  4. ΑΔΕΙΕΣ & ΠΙΣΤΟΠΟΙΗΣΕΙΣ — ΣΕ ΟΜΑΔΕΣ
    // ══════════════════════════════════════════════════════════════════

    private function qualifications(): void
    {
        $groups = $this->cv['qualifications'] ?? [];
        if (!$groups) {
            return;
        }

        $this->section('Άδειες & πιστοποιήσεις');

        foreach ($groups as $group) {
            // Ομάδα όπου ΤΙΠΟΤΑ δεν κατέχεται δεν μπαίνει στο βιογραφικό:
            // ο εργοδότης δεν χρειάζεται λίστα με ό,τι ΔΕΝ έχει ο οδηγός.
            $owned = array_filter($group['items'], static fn($i) => empty($i['absent']));
            if (!$owned) {
                continue;
            }

            $note = '';
            if (!empty($group['meta'])) {
                $note = $group['meta']['key'] . ' ' . $group['meta']['value'];
            }

            $this->subSection($group['title'] . ($note !== '' ? '   ·   ' . $note : ''));

            foreach ($owned as $item) {
                $this->pageBreakIfNeeded(264);
                $this->qualItem($item);
            }
        }
    }

    private function qualItem(array $item): void
    {
        $left = [];
        if ($item['title'] !== '') {
            $left[] = $item['title'];
        }
        if (!empty($item['tag'])) {
            $left[] = '(' . $item['tag'] . ')';
        }
        $head = implode(' ', $left);

        // Κατηγορίες διπλώματος: μπαίνουν στον τίτλο, δεν αξίζουν σειρά.
        if (!empty($item['cats'])) {
            $head = ($head !== '' ? $head . ': ' : '') . implode(', ', $item['cats']);
        }

        $this->pdf->SetFont('dejavusans', 'B', 9);
        $this->pdf->SetTextColor(...self::DARK);
        if ($head !== '') {
            $this->pdf->MultiCell(0, 4.6, $head, 0, 'L');
        }

        if (!empty($item['subtitle'])) {
            $this->pdf->SetFont('dejavusans', '', 8.8);
            $this->pdf->SetTextColor(70, 76, 88);
            $this->pdf->MultiCell(0, 4.3, $item['subtitle'], 0, 'L');
        }

        // Λεπτομέρειες και λήξεις σε ΜΙΑ γραμμή: ένα PDF δεν έχει λόγο να
        // ξοδεύει τέσσερις σειρές για αριθμό και ημερομηνία.
        $meta = [];
        foreach ($item['lines'] as $line) {
            $meta[] = trim(($line['key'] !== '' ? $line['key'] . ' ' : '') . $line['value']);
        }
        foreach ($item['expiries'] as $exp) {
            $meta[] = $exp['label'] . ' ' . $exp['date'];
        }
        if ($meta) {
            $this->pdf->SetFont('dejavusans', '', 8.5);
            $this->pdf->SetTextColor(...self::GREY);
            $this->pdf->MultiCell(0, 4.2, implode('  ·  ', $meta), 0, 'L');
        }

        if (!empty($item['covers_all'])) {
            $this->pdf->SetFont('dejavusans', '', 8.8);
            $this->pdf->SetTextColor(70, 76, 88);
            $this->pdf->MultiCell(0, 4.2, 'Σύνολο μηχανημάτων της ειδικότητας', 0, 'L');
        } elseif (!empty($item['subs'])) {
            $parts = [];
            foreach ($item['subs'] as $sub) {
                $parts[] = $sub['code'] . ' ' . $sub['name'] . ($sub['group'] !== '' ? ' (' . $sub['group'] . ')' : '');
            }
            $this->pdf->SetFont('dejavusans', '', 8.5);
            $this->pdf->SetTextColor(70, 76, 88);
            $this->pdf->MultiCell(0, 4.2, implode(' · ', $parts), 0, 'L');
        }

        $this->pdf->SetTextColor(...self::DARK);
        $this->pdf->Ln(1);
    }

    // ══════════════════════════════════════════════════════════════════
    //  5. ΕΠΙΜΟΡΦΩΣΗ
    // ══════════════════════════════════════════════════════════════════

    private function certifications(): void
    {
        $certs = $this->cv['certifications'];
        if ($certs['count'] === 0) {
            return;
        }

        $this->section('Επιμόρφωση & σεμινάρια');

        foreach ($certs['items'] as $c) {
            $this->pageBreakIfNeeded(266);

            $y = $this->pdf->GetY();
            $this->pdf->SetFont('dejavusans', '', 8.5);
            $this->pdf->SetTextColor(...self::GREY);
            $this->pdf->MultiCell(38, 4.5, $c['date_label'], 0, 'L', false, 1, self::LEFT, $y);

            $this->pdf->SetXY(self::LEFT + 40, $y);
            $this->pdf->SetFont('dejavusans', 'B', 9);
            $this->pdf->SetTextColor(...self::DARK);
            $this->pdf->MultiCell(0, 4.5, $c['title'], 0, 'L');

            $meta = array_filter([
                $c['provider'] ?: null,
                $c['category_label'] ?: null,
                $c['duration'] > 0 ? $c['duration'] . ' ώρες' : null,
                $c['expiry_label'] !== '' ? ($c['expired'] ? 'έληξε ' : 'λήξη ') . $c['expiry_label'] : null,
            ]);
            if ($meta) {
                $this->pdf->SetX(self::LEFT + 40);
                $this->pdf->SetFont('dejavusans', '', 8.5);
                $this->pdf->SetTextColor(...self::GREY);
                $this->pdf->MultiCell(0, 4.2, implode('  ·  ', $meta), 0, 'L');
            }

            $this->pdf->SetTextColor(...self::DARK);
            $this->pdf->Ln(0.8);
        }
    }

    // ══════════════════════════════════════════════════════════════════
    //  6. ΓΛΩΣΣΕΣ & ΔΕΞΙΟΤΗΤΕΣ
    // ══════════════════════════════════════════════════════════════════

    private function languagesAndSkills(): void
    {
        $langs = $this->cv['languages'];
        $skills = $this->cv['skills'];

        if (empty($langs) && empty($skills['groups'])) {
            return;
        }

        $this->section('Γλώσσες & δεξιότητες');

        if ($langs) {
            $parts = [];
            foreach ($langs as $l) {
                $parts[] = $l['name'] . ' (' . $l['level_label'] . ')';
            }
            $this->labelled('Γλώσσες', implode(', ', $parts));
        }

        foreach ($skills['groups'] as $g) {
            $this->labelled($g['label'], implode(', ', $g['items']));
        }
    }

    /** «Ετικέτα | τιμή» με σταθερή στήλη ετικέτας. */
    private function labelled(string $label, string $value): void
    {
        if ($value === '') {
            return;
        }
        $this->pageBreakIfNeeded(268);

        $y = $this->pdf->GetY();
        $this->pdf->SetFont('dejavusans', 'B', 8.8);
        $this->pdf->SetTextColor(70, 76, 88);
        $this->pdf->MultiCell(42, 4.6, $label, 0, 'L', false, 1, self::LEFT, $y);

        $this->pdf->SetXY(self::LEFT + 44, $y);
        $this->pdf->SetFont('dejavusans', '', 9);
        $this->pdf->SetTextColor(...self::DARK);
        $this->pdf->MultiCell(0, 4.6, $value, 0, 'L');
        $this->pdf->Ln(0.6);
    }

    private function footer(): void
    {
        $this->pdf->Ln(3);
        $this->pdf->SetFont('dejavusans', '', 7.5);
        $this->pdf->SetTextColor(...self::GREY);
        $this->pdf->MultiCell(0, 4, 'Βιογραφικό από το DriveJob · ' . date('d/m/Y') . ' · drivejob.gr', 0, 'C');
    }
}
