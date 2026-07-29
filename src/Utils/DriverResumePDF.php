<?php

namespace Drivejob\Utils;

use TCPDF;

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
        // Δημιουργία αντικειμένου TCPDF με υποστήριξη UTF-8
        $this->pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
// Ρύθμιση πληροφοριών εγγράφου
        $this->pdf->SetCreator('DriveJob.gr');
        $this->pdf->SetAuthor('DriveJob.gr');
        $this->pdf->SetTitle('Βιογραφικό Οδηγού - ' . $driver['first_name'] . ' ' . $driver['last_name']);
        $this->pdf->SetSubject('Βιογραφικό Οδηγού');
// Απενεργοποίηση της προεπιλεγμένης κεφαλίδας/υποσέλιδου
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
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
        $this->pdf->Output($filePath, 'F');
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
// Χρήση γραμματοσειράς που υποστηρίζει ελληνικά
        $this->pdf->SetFont('dejavusans', '', 12);
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

        $this->pdf->SetFont('dejavusans', 'B', 16);
        $this->pdf->SetY(20);
        $this->pdf->SetX(70);
        $this->pdf->Cell(0, 10, 'ΒΙΟΓΡΑΦΙΚΟ ΟΔΗΓΟΥ', 0, 1, 'L');
        $this->pdf->SetFont('dejavusans', 'B', 14);
        $this->pdf->SetX(70);
        $this->pdf->Cell(0, 10, $this->driver['first_name'] . ' ' . $this->driver['last_name'], 0, 1, 'L');
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
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->Cell(0, 10, 'ΠΡΟΣΩΠΙΚΑ ΣΤΟΙΧΕΙΑ', 0, 1, 'L', true);
        $this->pdf->Ln(2);
        $this->pdf->SetFont('dejavusans', '', 11);
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

            $this->pdf->SetFont('dejavusans', 'B', 10);
            $this->pdf->Cell(50, 7, 'Διεύθυνση:', 0, 0);
            $this->pdf->SetFont('dejavusans', '', 10);
            $this->pdf->Cell(0, 7, $address, 0, 1);
        }

        // Τηλέφωνο
        if (isset($this->driver['phone']) && !empty($this->driver['phone'])) {
            $this->pdf->SetFont('dejavusans', 'B', 10);
            $this->pdf->Cell(50, 7, 'Τηλέφωνο:', 0, 0);
            $this->pdf->SetFont('dejavusans', '', 10);
            $this->pdf->Cell(0, 7, $this->driver['phone'], 0, 1);
        }

        // Σταθερό
        if (isset($this->driver['landline']) && !empty($this->driver['landline'])) {
            $this->pdf->SetFont('dejavusans', 'B', 10);
            $this->pdf->Cell(50, 7, 'Σταθερό:', 0, 0);
            $this->pdf->SetFont('dejavusans', '', 10);
            $this->pdf->Cell(0, 7, $this->driver['landline'], 0, 1);
        }

        // Email
        if (isset($this->driver['email']) && !empty($this->driver['email'])) {
            $this->pdf->SetFont('dejavusans', 'B', 10);
            $this->pdf->Cell(50, 7, 'Email:', 0, 0);
            $this->pdf->SetFont('dejavusans', '', 10);
            $this->pdf->Cell(0, 7, $this->driver['email'], 0, 1);
        }

        // Ημερομηνία γέννησης
        if (isset($this->driver['birth_date']) && !empty($this->driver['birth_date'])) {
            $this->pdf->SetFont('dejavusans', 'B', 10);
            $this->pdf->Cell(50, 7, 'Ημ. Γέννησης:', 0, 0);
            $this->pdf->SetFont('dejavusans', '', 10);
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

            $this->pdf->SetFont('dejavusans', 'B', 10);
            $this->pdf->Cell(50, 7, 'Οικογ. Κατάσταση:', 0, 0);
            $this->pdf->SetFont('dejavusans', '', 10);
            $this->pdf->Cell(0, 7, $maritalStatus, 0, 1);
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

            $this->pdf->SetFont('dejavusans', 'B', 10);
            $this->pdf->Cell(50, 7, 'Στρατιωτικές Υποχρ.:', 0, 0);
            $this->pdf->SetFont('dejavusans', '', 10);
            $this->pdf->Cell(0, 7, $militaryService, 0, 1);
        }

        $this->pdf->Ln(5);
    }

    /**
     * Προσθέτει τα τυπικά προσόντα του οδηγού
     */
    private function addQualifications()
    {
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->Cell(0, 10, 'ΤΥΠΙΚΑ ΠΡΟΣΟΝΤΑ', 0, 1, 'L', true);
        $this->pdf->Ln(2);
        $this->pdf->SetFont('dejavusans', '', 11);
// 1. Άδειες Οδήγησης
        if (!empty($this->driverLicenseTypes)) {
            $this->pdf->SetFont('dejavusans', 'B', 10);
            $this->pdf->Cell(0, 7, 'Άδειες Οδήγησης:', 0, 1);
            $this->pdf->SetFont('dejavusans', '', 10);
            $licenseText = implode(', ', $this->driverLicenseTypes);
            $this->pdf->MultiCell(0, 7, $licenseText, 0, 'L');
            $this->pdf->Ln(2);
        }

        // 2. ΠΕΙ
        $hasPEI = false;
        $peiText = '';
        if (!empty($this->driverLicenses)) {
            $peiTypes = [];
            foreach ($this->driverLicenses as $license) {
                if (isset($license['has_pei']) && $license['has_pei'] == 1) {
                    $hasPEI = true;
                    if (strpos($license['license_type'], 'C') !== false) {
                        if (!in_array('ΠΕΙ-C', $peiTypes)) {
                                $peiTypes[] = 'ΠΕΙ-C';
                        }
                    }

                    if (strpos($license['license_type'], 'D') !== false) {
                        if (!in_array('ΠΕΙ-D', $peiTypes)) {
                            $peiTypes[] = 'ΠΕΙ-D';
                        }
                    }
                }
            }

            if (!empty($peiTypes)) {
                $peiText = implode(', ', $peiTypes);
                $this->pdf->SetFont('dejavusans', 'B', 10);
                $this->pdf->Cell(0, 7, 'Πιστοποιητικό Επαγγελματικής Ικανότητας (ΠΕΙ):', 0, 1);
                $this->pdf->SetFont('dejavusans', '', 10);
                $this->pdf->MultiCell(0, 7, $peiText, 0, 'L');
                $this->pdf->Ln(2);
            }
        }

        // 3. Κάρτα Ψηφιακού Ταχογράφου
        if (isset($this->driver['tachograph_card']) && $this->driver['tachograph_card']) {
            $this->pdf->SetFont('dejavusans', 'B', 10);
            $this->pdf->Cell(0, 7, 'Κάρτα Ψηφιακού Ταχογράφου:', 0, 1);
            $this->pdf->SetFont('dejavusans', '', 10);
            $tacho_text = 'Κάτοχος Κάρτας Ψηφιακού Ταχογράφου';
            $this->pdf->MultiCell(0, 7, $tacho_text, 0, 'L');
            $this->pdf->Ln(2);
        }

        // 4. Πιστοποιητικό ADR
        if (isset($this->driver['adr_certificate']) && $this->driver['adr_certificate']) {
            $this->pdf->SetFont('dejavusans', 'B', 10);
            $this->pdf->Cell(0, 7, 'Πιστοποιητικό ADR:', 0, 1);
            $this->pdf->SetFont('dejavusans', '', 10);
            $adrText = 'Κάτοχος Πιστοποιητικού ADR';
            if (isset($this->driver['adr_classes']) && !empty($this->driver['adr_classes'])) {
                $adrText .= ' - Κατηγορίες: ' . $this->driver['adr_classes'];
            }



            $this->pdf->MultiCell(0, 7, $adrText, 0, 'L');
            $this->pdf->Ln(2);
        }

        // 5. Άδεια Χειριστή Μηχανημάτων Έργου
        if (isset($this->driver['operator_license']) && $this->driver['operator_license']) {
            $this->pdf->SetFont('dejavusans', 'B', 10);
            $this->pdf->Cell(0, 7, 'Άδεια Χειριστή Μηχανημάτων Έργου:', 0, 1);
            $this->pdf->SetFont('dejavusans', '', 10);
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

            $this->pdf->MultiCell(0, 7, $operatorText, 0, 'L');
            $this->pdf->Ln(2);
        }

        // 6. Ειδικές Άδειες
        if (!empty($this->driverSpecialLicenses)) {
            $this->pdf->SetFont('dejavusans', 'B', 10);
            $this->pdf->Cell(0, 7, 'Ειδικές Άδειες:', 0, 1);
            $this->pdf->SetFont('dejavusans', '', 10);
            $specialLicensesText = '';
            foreach ($this->driverSpecialLicenses as $specialLicense) {
                $specialLicensesText .= '- ' . $specialLicense['license_type'];
                $specialLicensesText .= "\n";
            }

            $this->pdf->MultiCell(0, 7, $specialLicensesText, 0, 'L');
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
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->Cell(0, 10, 'ΔΕΞΙΟΤΗΤΕΣ ΟΔΗΓΟΥ', 0, 1, 'L', true);
        $this->pdf->Ln(5);
// Αύξηση του κενού

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
            $this->pdf->SetFont('dejavusans', '', 10);
// Δημιουργία πίνακα με τις ενεργές δεξιότητες
            $skills = [];
            foreach ($skillLabels as $key => $label) {
                if (isset($this->driverSkills[$key]) && $this->driverSkills[$key] == 1) {
                    $skills[] = '- ' . $label;
                }
            }

            // Χωρισμός των δεξιοτήτων σε δύο στήλες
            $leftColumnSkills = array();
            $rightColumnSkills = array();

            for ($i = 0; $i < count($skills); $i++) {
                if ($i % 2 == 0) {
                    $leftColumnSkills[] = $skills[$i];
                } else {
                    $rightColumnSkills[] = $skills[$i];
                }
            }

            // Μετατροπή των πινάκων σε κείμενο
            $leftColumnText = implode("\n", $leftColumnSkills);
            $rightColumnText = implode("\n", $rightColumnSkills);
// Δημιουργία στηλών με MultiCell
            $this->pdf->SetY($this->pdf->GetY());
            $startX = $this->pdf->GetX();
// Πρώτη στήλη
            $this->pdf->SetX($startX);
            $this->pdf->MultiCell(90, 7, $leftColumnText, 0, 'L');
// Δεύτερη στήλη - τοποθετείται δίπλα στην πρώτη
            $this->pdf->SetXY($startX + 95, $this->pdf->GetY() - (count($leftColumnSkills) * 7));
            $this->pdf->MultiCell(90, 7, $rightColumnText, 0, 'L');
// Μετακίνηση στο κάτω μέρος της ψηλότερης στήλης
            $leftHeight = count($leftColumnSkills) * 7;
            $rightHeight = count($rightColumnSkills) * 7;
            $this->pdf->SetY($this->pdf->GetY() + max(0, $rightHeight - $leftHeight) + 5);
// Επιπλέον δεξιότητες
            if (isset($this->driver['additional_skills']) && !empty($this->driver['additional_skills'])) {
                $this->pdf->SetFont('dejavusans', 'B', 10);
                $this->pdf->Cell(0, 7, 'Επιπλέον Δεξιότητες:', 0, 1);
                $this->pdf->SetFont('dejavusans', '', 10);
                $this->pdf->MultiCell(0, 7, $this->driver['additional_skills'], 0, 'L');
            }
        } else {
            $this->pdf->SetFont('dejavusans', '', 10);
            $this->pdf->Cell(0, 7, 'Δεν έχουν καταχωρηθεί ειδικές δεξιότητες.', 0, 1);
        }

        $this->pdf->Ln(5);
    }

    /**
     * Προσθέτει την εργασιακή εμπειρία του οδηγού
     */
    private function addExperience()
    {
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->Cell(0, 10, 'ΕΠΑΓΓΕΛΜΑΤΙΚΗ ΕΜΠΕΙΡΙΑ', 0, 1, 'L', true);
        $this->pdf->Ln(2);
        $this->pdf->SetFont('dejavusans', '', 10);
// Χρόνια εμπειρίας
        if (isset($this->driver['experience_years']) && $this->driver['experience_years'] > 0) {
            $this->pdf->SetFont('dejavusans', 'B', 10);
            $this->pdf->Cell(60, 7, 'Συνολικά Έτη Εμπειρίας:', 0, 0);
            $this->pdf->SetFont('dejavusans', '', 10);
            $this->pdf->Cell(0, 7, $this->driver['experience_years'], 0, 1);
        }

        // Εργασιακή εμπειρία
        if (isset($this->driver['work_experience']) && !empty($this->driver['work_experience'])) {
            $this->pdf->SetFont('dejavusans', 'B', 10);
            $this->pdf->Cell(0, 7, 'Περιγραφή Εργασιακής Εμπειρίας:', 0, 1);
            $this->pdf->SetFont('dejavusans', '', 10);
            $this->pdf->MultiCell(0, 7, $this->driver['work_experience'], 0, 'L');
        } else {
            $this->pdf->MultiCell(0, 7, 'Δεν έχει καταχωρηθεί αναλυτική εργασιακή εμπειρία.', 0, 'L');
        }

        $this->pdf->Ln(5);
    }

    /**
     * Προσθέτει πληροφορίες για την εκπαίδευση του οδηγού
     */
    private function addEducation()
    {
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->Cell(0, 10, 'ΕΚΠΑΙΔΕΥΣΗ', 0, 1, 'L', true);
        $this->pdf->Ln(2);
        $this->pdf->SetFont('dejavusans', '', 10);
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

            $this->pdf->SetFont('dejavusans', 'B', 10);
            $this->pdf->Cell(60, 7, 'Ανώτατο Επίπεδο Εκπαίδευσης:', 0, 0);
            $this->pdf->SetFont('dejavusans', '', 10);
            $this->pdf->Cell(0, 7, $educationLevel, 0, 1);
        }

        $this->pdf->Ln(5);
    }

    /**
     * Προσθέτει πληροφορίες για τις γλώσσες του οδηγού
     */
    private function addLanguages()
    {
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->Cell(0, 10, 'ΓΛΩΣΣΙΚΕΣ ΙΚΑΝΟΤΗΤΕΣ', 0, 1, 'L', true);
        $this->pdf->Ln(2);
        $this->pdf->SetFont('dejavusans', '', 10);
        $languageLevels = [
            'native' => 'Μητρική',
            'fluent' => 'Άριστα',
            'good' => 'Καλά',
            'basic' => 'Βασικά'
        ];
// Ελληνικά
        if (isset($this->driver['language_greek']) && !empty($this->driver['language_greek'])) {
            $this->pdf->SetFont('dejavusans', 'B', 10);
            $this->pdf->Cell(40, 7, 'Ελληνικά:', 0, 0);
            $this->pdf->SetFont('dejavusans', '', 10);
            $this->pdf->Cell(0, 7, $languageLevels[$this->driver['language_greek']], 0, 1);
        }

        // Αγγλικά
        if (isset($this->driver['language_english']) && !empty($this->driver['language_english'])) {
            $this->pdf->SetFont('dejavusans', 'B', 10);
            $this->pdf->Cell(40, 7, 'Αγγλικά:', 0, 0);
            $this->pdf->SetFont('dejavusans', '', 10);
            $this->pdf->Cell(0, 7, $languageLevels[$this->driver['language_english']], 0, 1);
        }

        // Γερμανικά
        if (isset($this->driver['language_german']) && !empty($this->driver['language_german'])) {
            $this->pdf->SetFont('dejavusans', 'B', 10);
            $this->pdf->Cell(40, 7, 'Γερμανικά:', 0, 0);
            $this->pdf->SetFont('dejavusans', '', 10);
            $this->pdf->Cell(0, 7, $languageLevels[$this->driver['language_german']], 0, 1);
        }

        // Γαλλικά
        if (isset($this->driver['language_french']) && !empty($this->driver['language_french'])) {
            $this->pdf->SetFont('dejavusans', 'B', 10);
            $this->pdf->Cell(40, 7, 'Γαλλικά:', 0, 0);
            $this->pdf->SetFont('dejavusans', '', 10);
            $this->pdf->Cell(0, 7, $languageLevels[$this->driver['language_french']], 0, 1);
        }

        // Ιταλικά
        if (isset($this->driver['language_italian']) && !empty($this->driver['language_italian'])) {
            $this->pdf->SetFont('dejavusans', 'B', 10);
            $this->pdf->Cell(40, 7, 'Ιταλικά:', 0, 0);
            $this->pdf->SetFont('dejavusans', '', 10);
            $this->pdf->Cell(0, 7, $languageLevels[$this->driver['language_italian']], 0, 1);
        }

        // Άλλη γλώσσα
        if (
            isset($this->driver['language_other_name']) && !empty($this->driver['language_other_name']) &&
            isset($this->driver['language_other_level']) && !empty($this->driver['language_other_level'])
        ) {
            $this->pdf->SetFont('dejavusans', 'B', 10);
            $this->pdf->Cell(40, 7, $this->driver['language_other_name'] . ':', 0, 0);
            $this->pdf->SetFont('dejavusans', '', 10);
            $this->pdf->Cell(0, 7, $languageLevels[$this->driver['language_other_level']], 0, 1);
        }

        $this->pdf->Ln(5);
    }

    /**
     * Προσθέτει επιπλέον πληροφορίες για τον οδηγό
     */
    private function addAdditionalInfo()
    {
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->Cell(0, 10, 'ΕΠΙΠΛΕΟΝ ΠΛΗΡΟΦΟΡΙΕΣ', 0, 1, 'L', true);
        $this->pdf->Ln(2);
        $this->pdf->SetFont('dejavusans', '', 10);
// Σχετικά με εμένα
        if (isset($this->driver['about_me']) && !empty($this->driver['about_me'])) {
            $this->pdf->SetFont('dejavusans', 'B', 10);
            $this->pdf->Cell(0, 7, 'Σχετικά με εμένα:', 0, 1);
            $this->pdf->SetFont('dejavusans', '', 10);
            $this->pdf->MultiCell(0, 7, $this->driver['about_me'], 0, 'L');
            $this->pdf->Ln(3);
        }

        // Βαθμολογία
        if (isset($this->averageRating) && $this->averageRating > 0) {
            $this->pdf->SetFont('dejavusans', 'B', 10);
            $this->pdf->Cell(40, 7, 'Μέση Βαθμολογία:', 0, 0);
            $this->pdf->SetFont('dejavusans', '', 10);
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
        $this->pdf->SetFont('dejavusans', 'I', 8);
        $this->pdf->Cell(0, 10, 'Το βιογραφικό δημιουργήθηκε αυτόματα από το DriveJob.gr - ' . date('d/m/Y'), 0, 0, 'C');
    }
}
