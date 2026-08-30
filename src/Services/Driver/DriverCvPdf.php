<?php

namespace Drivejob\Services\Driver;

use Drivejob\Helpers\OperatorSpecialities;
use Drivejob\Helpers\SpecialLicenseTypes;
use TCPDF;

/**
 * Το βιογραφικό σε PDF — Ο ΔΕΥΤΕΡΟΣ RENDERER του DriverCvService.
 * (30/08/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  Η ΑΡΧΗ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Αυτή η κλάση ΔΕΝ διαβάζει τη βάση, ΔΕΝ μεταφράζει κωδικούς, ΔΕΝ
 * υπολογίζει διάρκειες. Δέχεται τη δομή που φτιάχνει ο DriverCvService
 * — την ίδια που τυπώνει και η καρτέλα Επισκόπηση — και τη ζωγραφίζει
 * σε σελίδα.
 *
 * Έτσι, ό,τι βλέπει ο οδηγός στην οθόνη είναι ΑΚΡΙΒΩΣ ό,τι στέλνει στον
 * εργοδότη. Αν προστεθεί πεδίο, μπαίνει μία φορά στον service και
 * εμφανίζεται και στα δύο.
 *
 * ΓΙΑΤΙ TCPDF: είναι ήδη στο composer.json του project και υποστηρίζει
 * ελληνικά με τη γραμματοσειρά dejavusans. Το FPDF (επίσης παρόν) δεν
 * γράφει UTF-8 χωρίς πρόσθετα.
 *
 * ΤΑ ΤΥΠΙΚΑ ΠΡΟΣΟΝΤΑ έρχονται από το ΩΜΟ προφίλ και όχι από τον
 * CvService: η ομαδοποίησή τους ζει σήμερα στην όψη
 * (_qualification-groups.php). Όταν μετακινηθεί στον service, αυτή η
 * κλάση θα διαβάζει κι αυτά από εκεί — το σχόλιο μένει ως σημάδι.
 */
class DriverCvPdf
{
    private const RED = [179, 38, 30];
    private const GREY = [107, 114, 128];
    private const DARK = [17, 24, 39];

    private TCPDF $pdf;
    private array $cv;
    private array $profile;

    public function __construct(array $cv, array $profile)
    {
        $this->cv = $cv;
        $this->profile = $profile;
    }

    /** @return string Το PDF ως bytes. */
    public function render(): string
    {
        $this->pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $this->pdf->SetCreator('DriveJob');
        $this->pdf->SetAuthor('DriveJob');
        $this->pdf->SetTitle('Βιογραφικό — ' . ($this->cv['identity']['full_name'] ?? ''));
        $this->pdf->SetMargins(15, 15, 15);
        $this->pdf->SetAutoPageBreak(true, 18);
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        $this->pdf->AddPage();

        $this->header();
        $this->qualifications();
        $this->experience();
        $this->certifications();
        $this->languagesAndSkills();
        $this->footer();

        return $this->pdf->Output('cv.pdf', 'S');
    }

    // ══════════════════════════════════════════════════════════════════

    private function header(): void
    {
        $id = $this->cv['identity'];

        $this->pdf->SetFont('dejavusans', 'B', 20);
        $this->pdf->SetTextColor(...self::DARK);
        $this->pdf->Cell(0, 10, $id['full_name'], 0, 1);

        $this->pdf->SetFont('dejavusans', '', 9.5);
        $this->pdf->SetTextColor(...self::GREY);

        // Επικοινωνία σε μία γραμμή: ένα PDF βιογραφικό δεν έχει λόγο να
        // σπαταλά τέσσερις σειρές για τηλέφωνο και email.
        $contact = array_filter([
            $id['location'] ?: null,
            $id['phone'] ?: null,
            $id['landline'] ?: null,
            $id['email'] ?: null,
        ]);
        if ($contact) {
            $this->pdf->MultiCell(0, 5, implode('  ·  ', $contact), 0, 'L');
        }

        $reach = $id['reach'] ?? [];
        if (!empty($reach['declared'])) {
            $extra = $reach['label'];
            if (!empty($reach['travel'])) {
                $extra .= '  ·  Δέχομαι ταξίδια εκτός έδρας';
            }
            $this->pdf->MultiCell(0, 5, $extra, 0, 'L');
        }

        if (!empty($id['rating']['count'])) {
            $this->pdf->MultiCell(
                0,
                5,
                'Αξιολόγηση: ' . number_format($id['rating']['value'], 1) . '/5 (' . $id['rating']['count'] . ')',
                0,
                'L'
            );
        }

        $this->pdf->Ln(2);
        $this->rule();
    }

    private function rule(): void
    {
        $y = $this->pdf->GetY();
        $this->pdf->SetDrawColor(...self::RED);
        $this->pdf->SetLineWidth(0.6);
        $this->pdf->Line(15, $y, 195, $y);
        $this->pdf->Ln(4);
    }

    private function sectionTitle(string $text): void
    {
        // Νέα σελίδα αν δεν χωράει ούτε ο τίτλος με δύο γραμμές κάτω του:
        // τίτλος μόνος στο τέλος σελίδας είναι σημάδι απροσεξίας.
        if ($this->pdf->GetY() > 255) {
            $this->pdf->AddPage();
        }
        $this->pdf->Ln(2);
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->SetTextColor(...self::RED);
        $this->pdf->Cell(0, 7, mb_strtoupper($text, 'UTF-8'), 0, 1);
        $this->pdf->SetTextColor(...self::DARK);
    }

    /** Γραμμή «ετικέτα: τιμή» με σταθερή στήλη ετικέτας. */
    private function row(string $label, string $value, string $note = ''): void
    {
        if ($value === '') {
            return;
        }
        $this->pdf->SetFont('dejavusans', 'B', 9.5);
        $this->pdf->SetTextColor(...self::DARK);
        $this->pdf->Cell(52, 5.5, $label, 0, 0);

        $this->pdf->SetFont('dejavusans', '', 9.5);
        $text = $value . ($note !== '' ? '   (' . $note . ')' : '');
        $this->pdf->MultiCell(0, 5.5, $text, 0, 'L');
    }

    // ══════════════════════════════════════════════════════════════════
    //  ΤΥΠΙΚΑ ΠΡΟΣΟΝΤΑ
    // ══════════════════════════════════════════════════════════════════

    private function qualifications(): void
    {
        $p = $this->profile;
        $this->sectionTitle('Τυπικά προσόντα');

        // Δίπλωμα
        $cats = array_column($p['licenses'] ?? [], 'license_type');
        if ($cats) {
            $this->row('Άδεια οδήγησης', implode(', ', $cats), $this->expiryNote($p['license_document_expiry'] ?? null));
        }
        if (!empty($p['license_number'])) {
            $this->row('Αριθμός άδειας', (string) $p['license_number']);
        }

        /*
         * ΠΕΙ — ΜΙΑ φορά το καθένα.
         *
         * Οι στήλες pei_expiry_c/d επαναλαμβάνονται σε κάθε γραμμή
         * κατηγορίας: οδηγός με C, CE, D, DE έβγαζε στο PDF τέσσερις
         * σειρές «ΠΕΙ Εμπορευμάτων / ΠΕΙ Επιβατών» με ίδιες ημερομηνίες.
         */
        $peiC = null;
        $peiD = null;
        foreach (($p['licenses'] ?? []) as $lic) {
            $peiC = $peiC ?? ($lic['pei_expiry_c'] ?: null);
            $peiD = $peiD ?? ($lic['pei_expiry_d'] ?: null);
        }
        if ($peiC) {
            $this->row('ΠΕΙ Εμπορευμάτων', 'Ναι', $this->expiryNote($peiC));
        }
        if ($peiD) {
            $this->row('ΠΕΙ Επιβατών', 'Ναι', $this->expiryNote($peiD));
        }

        $adr = $p['adr_certificates'][0] ?? null;
        if ($adr) {
            $this->row('Πιστοποιητικό ADR', (string) ($adr['adr_type'] ?? 'Ναι'), $this->expiryNote($adr['expiry_date'] ?? null));
        }

        $tacho = $p['tachograph_cards'][0] ?? null;
        if ($tacho) {
            $this->row('Κάρτα ταχογράφου', (string) ($tacho['card_number'] ?? 'Ναι'), $this->expiryNote($tacho['expiry_date'] ?? null));
        }

        // Άδειες χειριστή: μία γραμμή ανά ειδικότητα, όπως στην οθόνη
        $ops = $p['operator_licenses'] ?? [];
        usort($ops, static fn($a, $b) => ((int) ($a['speciality'] ?? 0)) <=> ((int) ($b['speciality'] ?? 0)));

        foreach ($ops as $op) {
            $spec = (string) ($op['speciality'] ?? '');
            $name = OperatorSpecialities::SPECIALITIES[$spec] ?? ('Ειδικότητα ' . $spec);
            $group = strtoupper((string) ($op['group_type'] ?? 'A'));
            $groupLabel = $group === 'M' ? 'μικτή ομάδα' : 'Ομάδα ' . $group . '΄';

            if (!empty($op['covers_all'])) {
                $detail = 'σύνολο μηχανημάτων';
            } else {
                $codes = [];
                foreach (($op['sub_specialities'] ?? []) as $sub) {
                    $codes[] = is_array($sub) ? ($sub['sub_speciality'] ?? '') : (string) $sub;
                }
                $detail = $codes ? implode(', ', $codes) : '—';
            }

            $this->row(
                $spec . 'η ειδικότητα χειριστή',
                $name,
                $groupLabel . ' · ' . $detail
            );
        }

        foreach (($p['special_licenses'] ?? []) as $sl) {
            $this->row(
                'Ειδική άδεια',
                SpecialLicenseTypes::label((string) $sl['license_type']),
                empty($sl['expiry_date']) ? 'αορίστου' : $this->expiryNote($sl['expiry_date'])
            );
        }
    }

    private function expiryNote(?string $date): string
    {
        if (empty($date)) {
            return '';
        }
        $ts = strtotime($date);
        if ($ts === false) {
            return '';
        }
        return ($ts < time() ? 'έληξε ' : 'έως ') . date('m/Y', $ts);
    }

    // ══════════════════════════════════════════════════════════════════

    private function experience(): void
    {
        $exp = $this->cv['experience'];
        if ($exp['count'] === 0) {
            return;
        }

        $this->sectionTitle('Προϋπηρεσία — σύνολο ' . $exp['total_label']);

        foreach ($exp['items'] as $item) {
            if ($this->pdf->GetY() > 258) {
                $this->pdf->AddPage();
            }

            $this->pdf->SetFont('dejavusans', 'B', 10);
            $title = $item['category_label'];
            if ($item['type_label'] !== '') {
                $title .= ' — ' . $item['type_label'];
            }
            $this->pdf->MultiCell(0, 5.5, $title . ($item['current'] ? '  (τρέχουσα)' : ''), 0, 'L');

            $meta = array_filter([
                $item['duration_label'],
                $item['period_label'] ?: null,
                $item['transport_label'] ?: null,
                $item['employment_label'] ?: null,
            ]);
            $this->pdf->SetFont('dejavusans', '', 9);
            $this->pdf->SetTextColor(...self::GREY);
            $this->pdf->MultiCell(0, 5, implode('  ·  ', $meta), 0, 'L');

            if ($item['description'] !== '') {
                $this->pdf->SetTextColor(...self::DARK);
                $this->pdf->MultiCell(0, 5, $item['description'], 0, 'L');
            }

            $this->pdf->SetTextColor(...self::DARK);
            $this->pdf->Ln(1.5);
        }
    }

    private function certifications(): void
    {
        $certs = $this->cv['certifications'];
        if ($certs['count'] === 0) {
            return;
        }

        $this->sectionTitle('Σεμινάρια & πιστοποιητικά');

        foreach ($certs['items'] as $c) {
            if ($this->pdf->GetY() > 265) {
                $this->pdf->AddPage();
            }

            $this->pdf->SetFont('dejavusans', 'B', 9.5);
            $this->pdf->MultiCell(0, 5, $c['title'], 0, 'L');

            $meta = array_filter([
                $c['provider'] ?: null,
                $c['category_label'] ?: null,
                $c['date_label'] ?: null,
                $c['duration'] > 0 ? $c['duration'] . ' ώρες' : null,
                $c['expiry_label'] !== '' ? ($c['expired'] ? 'έληξε ' : 'λήξη ') . $c['expiry_label'] : null,
            ]);
            if ($meta) {
                $this->pdf->SetFont('dejavusans', '', 8.5);
                $this->pdf->SetTextColor(...self::GREY);
                $this->pdf->MultiCell(0, 4.5, implode('  ·  ', $meta), 0, 'L');
                $this->pdf->SetTextColor(...self::DARK);
            }
            $this->pdf->Ln(1);
        }
    }

    private function languagesAndSkills(): void
    {
        $langs = $this->cv['languages'];
        $skills = $this->cv['skills'];

        if (empty($langs) && empty($skills['groups'])) {
            return;
        }

        $this->sectionTitle('Γλώσσες & δεξιότητες');

        if ($langs) {
            $parts = [];
            foreach ($langs as $l) {
                $parts[] = $l['name'] . ' (' . $l['level_label'] . ')';
            }
            $this->row('Γλώσσες', implode(', ', $parts));
        }

        foreach ($skills['groups'] as $g) {
            $this->row($g['label'], implode(', ', $g['items']));
        }
    }

    private function footer(): void
    {
        $this->pdf->Ln(4);
        $this->pdf->SetFont('dejavusans', '', 7.5);
        $this->pdf->SetTextColor(...self::GREY);
        $this->pdf->MultiCell(
            0,
            4,
            'Δημιουργήθηκε από το DriveJob στις ' . date('d/m/Y') . ' · drivejob.gr',
            0,
            'C'
        );
    }
}
