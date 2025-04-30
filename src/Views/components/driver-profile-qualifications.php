<?php

namespace Drivejob\Utils;

use FPDF;

class DriverResumePDF
{
    private $pdf;
    private $driver;
    private $driverLicenses;
    private $driverLicenseTypes;
    private $driverSkills;
    private $driverSpecialLicenses;
    private $driverAdrCertificates;
    private $driverOperatorLicenses;
    private $driverTachographCard;
    private $averageRating;

    /**
     * Κατασκευαστής της κλάσης
     *
     * @param array $driver Τα δεδομένα του οδηγού
     * @param array $options Επιπλέον δεδομένα όπως άδειες, δεξιότητες κλπ.
     */
    public function __construct($driver, $options = [])
    {
        // Φόρτωση του FPDF αν δεν έχει φορτωθεί ήδη
        if (!class_exists('FPDF')) {
            require_once ROOT_DIR . '/vendor/fpdf/fpdf.php';
        }

        $this->pdf = new FPDF('P', 'mm', 'A4');
        $this->pdf->SetAuthor('DriveJob.gr');
        $this->pdf->SetTitle('Βιογραφικό Οδηγού - ' . $driver['first_name'] . ' ' . $driver['last_name']);
        $this->pdf->SetSubject('Βιογραφικό Οδηγού');
        $this->pdf->SetCreator('DriveJob.gr');
        $this->driver = $driver;
        $this->driverLicenses = isset($options['driverLicenses']) ? $options['driverLicenses'] : [];
        $this->driverLicenseTypes = isset($options['driverLicenseTypes']) ? $options['driverLicenseTypes'] : [];
        $this->driverSkills = isset($options['driverSkills']) ? $options['driverSkills'] : [];
        $this->driverSpecialLicenses = isset($options['driverSpecialLicenses']) ? $options['driverSpecialLicenses'] : [];
        $this->driverAdrCertificates = isset($options['driverAdrCertificates']) ? $options['driverAdrCertificates'] : [];
        $this->driverOperatorLicenses = isset($options['driverOperatorLicenses']) ? $options['driverOperatorLicenses'] : [];
        $this->driverTachographCard = isset($options['driverTachographCard']) ? $options['driverTachographCard'] : null;
        $this->averageRating = isset($options['averageRating']) ? $options['averageRating'] : null;
    }

    /**
     * Δημιουργεί το PDF του βιογραφικού
     *
     * @return string Το όνομα του αρχείου που αποθηκεύτηκε
     */
    public function generate()
    {
        $this->setupDocument();
        $this->addHeader();
        $this->addPersonalInfo();
        $this->addQualifications();
        $this->addSkills();
        $this->addExperience();
        $this->addEducation();
        $this->addLanguages();
        $this->addAdditionalInfo();
        $this->addFooter();
// Δημιουργία του φακέλου uploads/resumes αν δεν υπάρχει
        $uploadDir = ROOT_DIR . '/public/uploads/resumes';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Δημιουργία του ονόματος αρχείου
        $filename = 'drivejob_resume_' . $this->driver['id'] . '_' . time() . '.pdf';
        $filePath = $uploadDir . '/' . $filename;
// Αποθήκευση του PDF
        $this->pdf->Output('F', $filePath);
// Επιστροφή του URL του αρχείου
        return 'uploads/resumes/' . $filename;
    }

    /**
     * Ρυθμίζει τις βασικές παραμέτρους του εγγράφου
     */
    private function setupDocument()
    {
        $this->pdf->AddPage();
        $this->pdf->SetMargins(20, 20, 20);
// Προσθήκη ελληνικών γραμματοσειρών αν υπάρχουν
        if (file_exists(ROOT_DIR . '/vendor/fpdf/font/DejaVu.php')) {
            $this->pdf->AddFont('DejaVu', '', 'DejaVu.php');
            $this->pdf->AddFont('DejaVu', 'B', 'DejaVuB.php');
            $this->pdf->SetFont('DejaVu', '', 12);
        } else {
        // Εναλλακτικά χρησιμοποιούμε τις προεπιλεγμένες γραμματοσειρές
            $this->pdf->SetFont('Arial', '', 12);
        }
    }

    /**
     * Προσθέτει την κεφαλίδα του βιογραφικού
     */
    private function addHeader()
    {
        // Λογότυπο DriveJob αν υπάρχει
        if (file_exists(ROOT_DIR . '/public/img/logo.png')) {
            $this->pdf->Image(ROOT_DIR . '/public/img/logo.png', 20, 10, 40);
        }

        $this->pdf->SetFont('Arial', 'B', 16);
        $this->pdf->SetY(20);
        $this->pdf->SetX(70);
        $this->pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-7', 'ΒΙΟΓΡΑΦΙΚΟ ΟΔΗΓΟΥ'), 0, 1, 'L');
        $this->pdf->SetFont('Arial', 'B', 14);
        $this->pdf->SetX(70);
        $this->pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-7', $this->driver['first_name'] . ' ' . $this->driver['last_name']), 0, 1, 'L');
// Προσθήκη φωτογραφίας προφίλ αν υπάρχει
        if (isset($this->driver['profile_image']) && !empty($this->driver['profile_image']) && file_exists(ROOT_DIR . '/public/' . $this->driver['profile_image'])) {
            $this->pdf->Image(ROOT_DIR . '/public/' . $this->driver['profile_image'], 160, 20, 30);
        }

        $this->pdf->Line(20, 45, 190, 45);
        $this->pdf->Ln(10);
    }

    /**
     * Προσθέτει τα προσωπικά στοιχεία του οδηγού
     */
    private function addPersonalInfo()
    {
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-7', 'ΠΡΟΣΩΠΙΚΑ ΣΤΟΙΧΕΙΑ'), 0, 1, 'L', true);
        $this->pdf->Ln(2);
        $this->pdf->SetFont('Arial', '', 11);
// Διεύθυνση
        if (isset($this->driver['address']) && !empty($this->driver['address'])) {
            $address = $this->driver['address'];
            if (isset($this->driver['address_number']) && !empty($this->driver['address_number'])) {
                $address .= ' ' . $this->driver['address_number'];
            }
            if (isset($this->driver['postal_code']) && !empty($this->driver['postal_code'])) {
                $address .= ', ' . $this->driver['postal_code'];
            }
            if (isset($this->driver['city']) && !empty($this->driver['city'])) {
                $address .= ' ' . $this->driver['city'];
            }
            if (isset($this->driver['country']) && !empty($this->driver['country'])) {
                $address .= ', ' . $this->driver['country'];
            }

            $this->pdf->SetFont('Arial', 'B', 10);
            $this->pdf->Cell(40, 7, iconv('UTF-8', 'ISO-8859-7', 'Διεύθυνση:'), 0, 0);
            $this->pdf->SetFont('Arial', '', 10);
            $this->pdf->Cell(0, 7, iconv('UTF-8', 'ISO-8859-7', $address), 0, 1);
        }

        // Τηλέφωνο
        if (isset($this->driver['phone']) && !empty($this->driver['phone'])) {
            $this->pdf->SetFont('Arial', 'B', 10);
            $this->pdf->Cell(40, 7, iconv('UTF-8', 'ISO-8859-7', 'Τηλέφωνο:'), 0, 0);
            $this->pdf->SetFont('Arial', '', 10);
            $this->pdf->Cell(0, 7, $this->driver['phone'], 0, 1);
        }

        // Σταθερό
        if (isset($this->driver['landline']) && !empty($this->driver['landline'])) {
            $this->pdf->SetFont('Arial', 'B', 10);
            $this->pdf->Cell(40, 7, iconv('UTF-8', 'ISO-8859-7', 'Σταθερό:'), 0, 0);
            $this->pdf->SetFont('Arial', '', 10);
            $this->pdf->Cell(0, 7, $this->driver['landline'], 0, 1);
        }

        // Email
        if (isset($this->driver['email']) && !empty($this->driver['email'])) {
            $this->pdf->SetFont('Arial', 'B', 10);
            $this->pdf->Cell(40, 7, iconv('UTF-8', 'ISO-8859-7', 'Email:'), 0, 0);
            $this->pdf->SetFont('Arial', '', 10);
            $this->pdf->Cell(0, 7, $this->driver['email'], 0, 1);
        }

        // Ημερομηνία γέννησης
        if (isset($this->driver['birth_date']) && !empty($this->driver['birth_date'])) {
            $this->pdf->SetFont('Arial', 'B', 10);
            $this->pdf->Cell(40, 7, iconv('UTF-8', 'ISO-8859-7', 'Ημ. Γέννησης:'), 0, 0);
            $this->pdf->SetFont('Arial', '', 10);
            $this->pdf->Cell(0, 7, date('d/m/Y', strtotime($this->driver['birth_date'])), 0, 1);
        }

        // Οικογενειακή κατάσταση
        if (isset($this->driver['marital_status']) && !empty($this->driver['marital_status'])) {
            $maritalStatus = '';
            switch ($this->driver['marital_status']) {
                case 'single':
                    $maritalStatus = 'Άγαμος/η';
                    break;
                case 'married':
                    $maritalStatus = 'Έγγαμος/η';
                    break;
                case 'divorced':
                    $maritalStatus = 'Διαζευγμένος/η';
                    break;
                case 'widowed':
                    $maritalStatus = 'Χήρος/α';
                    break;
                default:
                    $maritalStatus = $this->driver['marital_status'];
            }

            $this->pdf->SetFont('Arial', 'B', 10);
            $this->pdf->Cell(40, 7, iconv('UTF-8', 'ISO-8859-7', 'Οικογ. Κατάσταση:'), 0, 0);
            $this->pdf->SetFont('Arial', '', 10);
            $this->pdf->Cell(0, 7, iconv('UTF-8', 'ISO-8859-7', $maritalStatus), 0, 1);
        }

        // Στρατιωτικές υποχρεώσεις
        if (isset($this->driver['military_service']) && !empty($this->driver['military_service'])) {
            $militaryService = '';
            switch ($this->driver['military_service']) {
                case 'completed':
                    $militaryService = 'Εκπληρωμένες';
                    break;
                case 'exempt':
                        $militaryService = 'Απαλλαγή';
                    break;
                case 'postponed':
                    $militaryService = 'Αναβολή';
                    break;
                case 'not_required':
                    $militaryService = 'Δεν απαιτείται';
                    break;
                default:
                    $militaryService = $this->driver['military_service'];
            }

            $this->pdf->SetFont('Arial', 'B', 10);
            $this->pdf->Cell(40, 7, iconv('UTF-8', 'ISO-8859-7', 'Στρατιωτικές Υποχρ.:'), 0, 0);
            $this->pdf->SetFont('Arial', '', 10);
            $this->pdf->Cell(0, 7, iconv('UTF-8', 'ISO-8859-7', $militaryService), 0, 1);
        }

        $this->pdf->Ln(5);
    }

    /**
     * Προσθέτει τα τυπικά προσόντα του οδηγού
     */
    private function addQualifications()
    {
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-7', 'ΤΥΠΙΚΑ ΠΡΟΣΟΝΤΑ'), 0, 1, 'L', true);
        $this->pdf->Ln(2);
        $this->pdf->SetFont('Arial', '', 11);
// 1. Άδειες Οδήγησης
        if (!empty($this->driverLicenseTypes)) {
            $this->pdf->SetFont('Arial', 'B', 10);
            $this->pdf->Cell(0, 7, iconv('UTF-8', 'ISO-8859-7', 'Άδειες Οδήγησης:'), 0, 1);
            $this->pdf->SetFont('Arial', '', 10);
            $licenseText = implode(', ', $this->driverLicenseTypes);
            $this->pdf->MultiCell(0, 7, iconv('UTF-8', 'ISO-8859-7', $licenseText), 0, 'L');
            $this->pdf->Ln(2);
        }

        // 2. ΠΕΙ
        $hasPEI = false;
        $peiText = '';
        if (!empty($this->driverLicenses)) {
            foreach ($this->driverLicenses as $license) {
                if (isset($license['has_pei']) && $license['has_pei'] == 1) {
                    $hasPEI = true;
                    if (strpos($license['license_type'], 'C') !== false && isset($license['pei_expiry_c'])) {
                        $peiText .= 'ΠΕΙ-C';
                        if (isset($license['pei_expiry_c'])) {
                                $peiText .= ' (Λήξη: ' . date('d/m/Y', strtotime($license['pei_expiry_c'])) . ')';
                        }
                        $peiText .= ', ';
                    }

                    if (strpos($license['license_type'], 'D') !== false && isset($license['pei_expiry_d'])) {
                        $peiText .= 'ΠΕΙ-D';
                        if (isset($license['pei_expiry_d'])) {
                            $peiText .= ' (Λήξη: ' . date('d/m/Y', strtotime($license['pei_expiry_d'])) . ')';
                        }
                        $peiText .= ', ';
                    }
                }
            }

            if (!empty($peiText)) {
                $peiText = rtrim($peiText, ', ');
                $this->pdf->SetFont('Arial', 'B', 10);
                $this->pdf->Cell(0, 7, iconv('UTF-8', 'ISO-8859-7', 'Πιστοποιητικό Επαγγελματικής Ικανότητας (ΠΕΙ):'), 0, 1);
                $this->pdf->SetFont('Arial', '', 10);
                $this->pdf->MultiCell(0, 7, iconv('UTF-8', 'ISO-8859-7', $peiText), 0, 'L');
                $this->pdf->Ln(2);
            }
        }

        // 3. Κάρτα Ψηφιακού Ταχογράφου
        if (isset($this->driver['tachograph_card']) && $this->driver['tachograph_card']) {
            $this->pdf->SetFont('Arial', 'B', 10);
            $this->pdf->Cell(0, 7, iconv('UTF-8', 'ISO-8859-7', 'Κάρτα Ψηφιακού Ταχογράφου:'), 0, 1);
            $this->pdf->SetFont('Arial', '', 10);
            $tacho_text = 'Κάτοχος Κάρτας Ψηφιακού Ταχογράφου';
            if (isset($this->driverTachographCard) && isset($this->driverTachographCard['expiry_date'])) {
                $tacho_text .= ' (Λήξη: ' . date('d/m/Y', strtotime($this->driverTachographCard['expiry_date'])) . ')';
            }

            $this->pdf->MultiCell(0, 7, iconv('UTF-8', 'ISO-8859-7', $tacho_text), 0, 'L');
            $this->pdf->Ln(2);
        }

        // 4. Πιστοποιητικό ADR
        if (isset($this->driver['adr_certificate']) && $this->driver['adr_certificate']) {
            $this->pdf->SetFont('Arial', 'B', 10);
            $this->pdf->Cell(0, 7, iconv('UTF-8', 'ISO-8859-7', 'Πιστοποιητικό ADR:'), 0, 1);
            $this->pdf->SetFont('Arial', '', 10);
            $adrText = 'Κάτοχος Πιστοποιητικού ADR';
            if (isset($this->driver['adr_classes']) && !empty($this->driver['adr_classes'])) {
                $adrText .= ' - Κατηγορίες: ' . $this->driver['adr_classes'];
            }

            if (isset($this->driver['adr_certificate_expiry']) && !empty($this->driver['adr_certificate_expiry'])) {
                $adrText .= ' (Λήξη: ' . date('d/m/Y', strtotime($this->driver['adr_certificate_expiry'])) . ')';
            }

            $this->pdf->MultiCell(0, 7, iconv('UTF-8', 'ISO-8859-7', $adrText), 0, 'L');
            $this->pdf->Ln(2);
        }

        // 5. Άδεια Χειριστή Μηχανημάτων Έργου
        if (isset($this->driver['operator_license']) && $this->driver['operator_license']) {
            $this->pdf->SetFont('Arial', 'B', 10);
            $this->pdf->Cell(0, 7, iconv('UTF-8', 'ISO-8859-7', 'Άδεια Χειριστή Μηχανημάτων Έργου:'), 0, 1);
            $this->pdf->SetFont('Arial', '', 10);
            $operatorText = 'Κάτοχος Άδειας Χειριστή Μηχανημάτων Έργου';
            if (!empty($this->driverOperatorLicenses)) {
                $operatorText = '';
                foreach ($this->driverOperatorLicenses as $operatorLicense) {
                    $operatorText .= 'Ειδικότητα ' . $operatorLicense['speciality'];
                    if (isset($operatorLicense['sub_specialities']) && !empty($operatorLicense['sub_specialities'])) {
                            $groupedSubSpecialties = [];
                        foreach ($operatorLicense['sub_specialities'] as $subSpecialty) {
                            $group = isset($subSpecialty['group_type']) ? $subSpecialty['group_type'] : 'Άλλο';
                            if (!isset($groupedSubSpecialties[$group])) {
                                        $groupedSubSpecialties[$group] = [];
                            }
                            $groupedSubSpecialties[$group][] = $subSpecialty['sub_speciality'];
                        }

                        foreach ($groupedSubSpecialties as $group => $subspecialties) {
                            $operatorText .= "\n  Ομάδα " . $group . ': ';
                            $operatorText .= implode(', ', $subspecialties);
                        }
                    }

                    $operatorText .= "\n";
                }
            } elseif (isset($this->driver['operator_license_type']) && !empty($this->driver['operator_license_type'])) {
                $operatorText .= ' - ' . $this->driver['operator_license_type'];
            }

            $this->pdf->MultiCell(0, 7, iconv('UTF-8', 'ISO-8859-7', $operatorText), 0, 'L');
            $this->pdf->Ln(2);
        }

        // 6. Ειδικές Άδειες
        if (!empty($this->driverSpecialLicenses)) {
            $this->pdf->SetFont('Arial', 'B', 10);
            $this->pdf->Cell(0, 7, iconv('UTF-8', 'ISO-8859-7', 'Ειδικές Άδειες:'), 0, 1);
            $this->pdf->SetFont('Arial', '', 10);
            $specialLicensesText = '';
            foreach ($this->driverSpecialLicenses as $specialLicense) {
                $specialLicensesText .= '- ' . $specialLicense['license_type'];
                if (isset($specialLicense['expiry_date']) && !empty($specialLicense['expiry_date'])) {
                    $specialLicensesText .= ' (Λήξη: ' . date('d/m/Y', strtotime($specialLicense['expiry_date'])) . ')';
                }

                $specialLicensesText .= "\n";
            }

            $this->pdf->MultiCell(0, 7, iconv('UTF-8', 'ISO-8859-7', $specialLicensesText), 0, 'L');
            $this->pdf->Ln(2);
        }

        $this->pdf->Ln(5);
    }

    /**
     * Προσθέτει τις δεξιότητες του οδηγού
     */
    private function addSkills()
    {
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-7', 'ΔΕΞΙΟΤΗΤΕΣ ΟΔΗΓΟΥ'), 0, 1, 'L', true);
        $this->pdf->Ln(2);
        if (!empty($this->driverSkills)) {
            $skillLabels = [
                'defensive_driving' => 'Αμυντική Οδήγηση',
                'eco_driving' => 'Οικολογική Οδήγηση',
                'night_driving' => 'Νυχτερινή Οδήγηση',
                'mountain_driving' => 'Οδήγηση σε Ορεινές Περιοχές',
                'extreme_conditions' => 'Οδήγηση σε Ακραίες Συνθήκες',
                'loading_securing' => 'Φόρτωση & Ασφάλιση Φορτίου',
                'emergency_response' => 'Αντιμετώπιση Έκτακτων Καταστάσεων',
                'first_aid' => 'Πρώτες Βοήθειες',
                'dangerous_goods' => 'Διαχείριση Επικίνδυνων Εμπορευμάτων',
                'tacograph_compliance' => 'Συμμόρφωση με Ταχογράφο',
                'customer_service' => 'Εξυπηρέτηση Πελατών',
                'time_management' => 'Διαχείριση Χρόνου',
                'route_planning' => 'Σχεδιασμός Διαδρομής',
                'conflict_resolution' => 'Επίλυση Συγκρούσεων',
                'multilingual' => 'Πολύγλωσσος',
                'vehicle_maintenance' => 'Συντήρηση Οχήματος',
                'troubleshooting' => 'Αντιμετώπιση Βλαβών',
                'digital_tachograph' => 'Ψηφιακός Ταχογράφος',
                'gps_systems' => 'Συστήματα GPS',
                'logistics_software' => 'Λογισμικό Logistics'
            ];
            $this->pdf->SetFont('Arial', '', 10);
            $skills = [];
            foreach ($skillLabels as $key => $label) {
                if (isset($this->driverSkills[$key]) && $this->driverSkills[$key] == 1) {
                    $skills[] = '- ' . $label;
                }
            }

            // Διαίρεση των δεξιοτήτων σε δύο στήλες
            $skillCount = count($skills);
            $colSkills = ceil($skillCount / 2);
// Πρώτη στήλη
            $this->pdf->SetX(20);
            for ($i = 0; $i < $colSkills && $i < $skillCount; $i++) {
                $this->pdf->Cell(85, 7, iconv('UTF-8', 'ISO-8859-7', $skills[$i]), 0, 1);
            }

            // Δεύτερη στήλη
            $this->pdf->SetXY(105, $this->pdf->GetY() - ($colSkills * 7));
            for ($i = $colSkills; $i < $skillCount; $i++) {
                $this->pdf->Cell(85, 7, iconv('UTF-8', 'ISO-8859-7', $skills[$i]), 0, 1);
            }

            // Επιπλέον δεξιότητες
            if (isset($this->driver['additional_skills']) && !empty($this->driver['additional_skills'])) {
                $this->pdf->Ln(3);
                $this->pdf->SetX(20);
                $this->pdf->SetFont('Arial', 'B', 10);
                $this->pdf->Cell(0, 7, iconv('UTF-8', 'ISO-8859-7', 'Επιπλέον Δεξιότητες:'), 0, 1);
                $this->pdf->SetFont('Arial', '', 10);
                $this->pdf->MultiCell(0, 7, iconv('UTF-8', 'ISO-8859-7', $this->driver['additional_skills']), 0, 'L');
            }
        } else {
            $this->pdf->SetFont('Arial', '', 10);
            $this->pdf->Cell(0, 7, iconv('UTF-8', 'ISO-8859-7', 'Δεν έχουν καταχωρηθεί ειδικές δεξιότητες.'), 0, 1);
        }

        $this->pdf->Ln(5);
    }

    /**
     * Προσθέτει την εργασιακή εμπειρία του οδηγού
     */
    private function addExperience()
    {
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-7', 'ΕΠΑΓΓΕΛΜΑΤΙΚΗ ΕΜΠΕΙΡΙΑ'), 0, 1, 'L', true);
        $this->pdf->Ln(2);
        $this->pdf->SetFont('Arial', '', 10);
// Χρόνια εμπειρίας
        if (isset($this->driver['experience_years']) && $this->driver['experience_years'] > 0) {
            $this->pdf->SetFont('Arial', 'B', 10);
            $this->pdf->Cell(60, 7, iconv('UTF-8', 'ISO-8859-7', 'Συνολικά Έτη Εμπειρίας:'), 0, 0);
            $this->pdf->SetFont('Arial', '', 10);
            $this->pdf->Cell(0, 7, $this->driver['experience_years'], 0, 1);
        }

        // Εργασιακή εμπειρία
        if (isset($this->driver['work_experience']) && !empty($this->driver['work_experience'])) {
            $this->pdf->SetFont('Arial', 'B', 10);
            $this->pdf->Cell(0, 7, iconv('UTF-8', 'ISO-8859-7', 'Περιγραφή Εργασιακής Εμπειρίας:'), 0, 1);
            $this->pdf->SetFont('Arial', '', 10);
            $this->pdf->MultiCell(0, 7, iconv('UTF-8', 'ISO-8859-7', $this->driver['work_experience']), 0, 'L');
        } else {
            $this->pdf->MultiCell(0, 7, iconv('UTF-8', 'ISO-8859-7', 'Δεν έχει καταχωρηθεί αναλυτική εργασιακή εμπειρία.'), 0, 'L');
        }

        $this->pdf->Ln(5);
    }

    /**
     * Προσθέτει πληροφορίες για την εκπαίδευση του οδηγού
     */
    private function addEducation()
    {
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-7', 'ΕΚΠΑΙΔΕΥΣΗ'), 0, 1, 'L', true);
        $this->pdf->Ln(2);
        $this->pdf->SetFont('Arial', '', 10);
// Επίπεδο εκπαίδευσης
        if (isset($this->driver['education_level']) && !empty($this->driver['education_level'])) {
            $educationLevel = '';
            switch ($this->driver['education_level']) {
                case 'primary':
                    $educationLevel = 'Δημοτικό';
                    break;
                case 'secondary':
                    $educationLevel = 'Δευτεροβάθμια Εκπαίδευση';
                    break;
                case 'highschool':
                    $educationLevel = 'Λύκειο';
                    break;
                case 'vocational':
                    $educationLevel = 'Επαγγελματική Σχολή';
                    break;
                case 'college':
                    $educationLevel = 'ΙΕΚ/Κολλέγιο';
                    break;
                case 'university':
                    $educationLevel = 'Πανεπιστημιακή Εκπαίδευση';
                    break;
                case 'postgraduate':
                    $educationLevel = 'Μεταπτυχιακό';
                    break;
                default:
                    $educationLevel = $this->driver['education_level'];
            }

            $this->pdf->SetFont('Arial', 'B', 10);
            $this->pdf->Cell(60, 7, iconv('UTF-8', 'ISO-8859-7', 'Ανώτατο Επίπεδο Εκπαίδευσης:'), 0, 0);
            $this->pdf->SetFont('Arial', '', 10);
            $this->pdf->Cell(0, 7, iconv('UTF-8', 'ISO-8859-7', $educationLevel), 0, 1);
        }

        $this->pdf->Ln(5);
    }

    /**
     * Προσθέτει πληροφορίες για τις γλώσσες του οδηγού
     */
    private function addLanguages()
    {
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-7', 'ΓΛΩΣΣΙΚΕΣ ΙΚΑΝΟΤΗΤΕΣ'), 0, 1, 'L', true);
        $this->pdf->Ln(2);
        $this->pdf->SetFont('Arial', '', 10);
        $languageLevels = [
            'native' => 'Μητρική',
            'fluent' => 'Άριστα',
            'good' => 'Καλά',
            'basic' => 'Βασικά'
        ];
// Ελληνικά
        if (isset($this->driver['language_greek']) && !empty($this->driver['language_greek'])) {
            $this->pdf->SetFont('Arial', 'B', 10);
            $this->pdf->Cell(40, 7, iconv('UTF-8', 'ISO-8859-7', 'Ελληνικά:'), 0, 0);
            $this->pdf->SetFont('Arial', '', 10);
            $this->pdf->Cell(0, 7, iconv('UTF-8', 'ISO-8859-7', $languageLevels[$this->driver['language_greek']]), 0, 1);
        }

        // Αγγλικά
        if (isset($this->driver['language_english']) && !empty($this->driver['language_english'])) {
            $this->pdf->SetFont('Arial', 'B', 10);
            $this->pdf->Cell(40, 7, iconv('UTF-8', 'ISO-8859-7', 'Αγγλικά:'), 0, 0);
            $this->pdf->SetFont('Arial', '', 10);
            $this->pdf->Cell(0, 7, iconv('UTF-8', 'ISO-8859-7', $languageLevels[$this->driver['language_english']]), 0, 1);
        }

        // Γερμανικά
        if (isset($this->driver['language_german']) && !empty($this->driver['language_german'])) {
            $this->pdf->SetFont('Arial', 'B', 10);
            $this->pdf->Cell(40, 7, iconv('UTF-8', 'ISO-8859-7', 'Γερμανικά:'), 0, 0);
            $this->pdf->SetFont('Arial', '', 10);
            $this->pdf->Cell(0, 7, iconv('UTF-8', 'ISO-8859-7', $languageLevels[$this->driver['language_german']]), 0, 1);
        }

        // Γαλλικά
        if (isset($this->driver['language_french']) && !empty($this->driver['language_french'])) {
            $this->pdf->SetFont('Arial', 'B', 10);
            $this->pdf->Cell(40, 7, iconv('UTF-8', 'ISO-8859-7', 'Γαλλικά:'), 0, 0);
            $this->pdf->SetFont('Arial', '', 10);
            $this->pdf->Cell(0, 7, iconv('UTF-8', 'ISO-8859-7', $languageLevels[$this->driver['language_french']]), 0, 1);
        }

        // Ιταλικά
        if (isset($this->driver['language_italian']) && !empty($this->driver['language_italian'])) {
            $this->pdf->SetFont('Arial', 'B', 10);
            $this->pdf->Cell(40, 7, iconv('UTF-8', 'ISO-8859-7', 'Ιταλικά:'), 0, 0);
            $this->pdf->SetFont('Arial', '', 10);
            $this->pdf->Cell(0, 7, iconv('UTF-8', 'ISO-8859-7', $languageLevels[$this->driver['language_italian']]), 0, 1);
        }

        // Άλλη γλώσσα
        if (
            isset($this->driver['language_other_name']) && !empty($this->driver['language_other_name']) &&
            isset($this->driver['language_other_level']) && !empty($this->driver['language_other_level'])
        ) {
            $this->pdf->SetFont('Arial', 'B', 10);
            $this->pdf->Cell(40, 7, iconv('UTF-8', 'ISO-8859-7', $this->driver['language_other_name'] . ':'), 0, 0);
            $this->pdf->SetFont('Arial', '', 10);
            $this->pdf->Cell(0, 7, iconv('UTF-8', 'ISO-8859-7', $languageLevels[$this->driver['language_other_level']]), 0, 1);
        }

        $this->pdf->Ln(5);
    }

    /**
     * Προσθέτει επιπλέον πληροφορίες για τον οδηγό
     */
    private function addAdditionalInfo()
    {
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-7', 'ΕΠΙΠΛΕΟΝ ΠΛΗΡΟΦΟΡΙΕΣ'), 0, 1, 'L', true);
        $this->pdf->Ln(2);
        $this->pdf->SetFont('Arial', '', 10);
// Σχετικά με εμένα
        if (isset($this->driver['about_me']) && !empty($this->driver['about_me'])) {
            $this->pdf->SetFont('Arial', 'B', 10);
            $this->pdf->Cell(0, 7, iconv('UTF-8', 'ISO-8859-7', 'Σχετικά με εμένα:'), 0, 1);
            $this->pdf->SetFont('Arial', '', 10);
            $this->pdf->MultiCell(0, 7, iconv('UTF-8', 'ISO-8859-7', $this->driver['about_me']), 0, 'L');
            $this->pdf->Ln(3);
        }

        // Βαθμολογία
        if (isset($this->averageRating) && $this->averageRating > 0) {
            $this->pdf->SetFont('Arial', 'B', 10);
            $this->pdf->Cell(40, 7, iconv('UTF-8', 'ISO-8859-7', 'Μέση Βαθμολογία:'), 0, 0);
            $this->pdf->SetFont('Arial', '', 10);
            $this->pdf->Cell(0, 7, number_format($this->averageRating, 1) . '/5', 0, 1);
        }

        $this->pdf->Ln(5);
    }

    /**
     * Προσθέτει το υποσέλιδο του βιογραφικού
     */
    private function addFooter()
    {
        $this->pdf->SetY(-20);
        $this->pdf->SetFont('Arial', 'I', 8);
        $this->pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-7', 'Το βιογραφικό δημιουργήθηκε αυτόματα από το DriveJob.gr - ' . date('d/m/Y')), 0, 0, 'C');
    }
}
