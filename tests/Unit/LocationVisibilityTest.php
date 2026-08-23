<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Drivejob\Services\Visibility;

/**
 * Γενίκευση τοποθεσίας πριν την πρόσληψη.
 *
 * ΓΙΑΤΙ ΕΧΕΙ ΞΕΧΩΡΙΣΤΟ ΑΡΧΕΙΟ: όπως και το ContactMaskingTest, δεν χρειάζεται
 * βάση — είναι καθαρή συνάρτηση, άρα τρέχει και στο CI.
 *
 * ΤΙ ΠΡΟΣΤΑΤΕΥΕΙ: στον κλάδο των μεταφορών η διεύθυνση ΕΙΝΑΙ ταυτότητα. Το
 * «6ο χλμ. Θεσσαλονίκης–Μουδανιών» ταυτοποιεί την εταιρεία σε δέκα
 * δευτερόλεπτα με μια αναζήτηση χάρτη. Αν κρύψουμε την επωνυμία αλλά
 * δείξουμε τη διεύθυνση, δεν κρύψαμε τίποτα — προσθέσαμε ένα βήμα.
 *
 * Το πεδίο `location` είναι ελεύθερο κείμενο από φόρμα με αυτόματη
 * συμπλήρωση Google Places, οπότε έρχεται σε δύο ασύμβατα σχήματα. Αυτά τα
 * τεστ κλειδώνουν ότι και τα δύο καταλήγουν σε πόλη.
 */
class LocationVisibilityTest extends TestCase
{
    /**
     * @dataProvider cityFirstFormat
     */
    public function testCityFirstFormatReturnsCity(string $input, string $expected): void
    {
        $this->assertSame($expected, Visibility::publicLocation(['location' => $input]));
    }

    public static function cityFirstFormat(): array
    {
        return [
            'πόλη και χώρα'      => ['Θέρμη, Ελλάδα', 'Θέρμη'],
            'χώρα στα αγγλικά'   => ['Θεσσαλονίκη, Greece', 'Θεσσαλονίκη'],
            'πόλη, νομός, χώρα'  => ['Αθήνα, Αττική, Ελλάδα', 'Αθήνα'],
            'σκέτη πόλη'         => ['Βέροια', 'Βέροια'],
        ];
    }

    /**
     * Το επικίνδυνο σχήμα: η οδός έρχεται ΠΡΩΤΗ.
     *
     * Μια υλοποίηση που κρατά απλώς το πρώτο τμήμα περνά όλα τα παραπάνω
     * τεστ και αποτυγχάνει εδώ — δημοσιεύοντας ακριβώς τη διεύθυνση που
     * υποτίθεται ότι κρύβει.
     *
     * @dataProvider streetFirstFormat
     */
    public function testStreetIsNeverPublished(string $input, string $expected): void
    {
        $result = Visibility::publicLocation(['location' => $input]);

        $this->assertSame($expected, $result);
        $this->assertDoesNotMatchRegularExpression(
            '/\d/',
            $result,
            'Η δημόσια τοποθεσία δεν πρέπει να περιέχει αριθμό — αριθμός σημαίνει οδός, χιλιόμετρο ή ΤΚ.'
        );
    }

    public static function streetFirstFormat(): array
    {
        return [
            'λεωφόρος με αριθμό' => ['Λεωφ. Γεωργικής Σχολής 45, Θέρμη, Greece', 'Θέρμη'],
            'χιλιομετρική θέση'  => ['6ο χλμ. Θεσσαλονίκης–Μουδανιών, Θέρμη, Ελλάδα', 'Θέρμη'],
            'με ταχ. κώδικα'     => ['Εγνατία 154, 546 36, Θεσσαλονίκη, Greece', 'Θεσσαλονίκη'],
        ];
    }

    public function testCityFieldWinsOverAddress(): void
    {
        $this->assertSame(
            'Βέροια',
            Visibility::publicLocation([
                'city' => 'Βέροια',
                'address' => 'Βέροια, 3ο χλμ. Βέροιας–Νάουσας',
            ]),
            'Όταν υπάρχει καθαρό πεδίο πόλης, δεν μαντεύουμε από τη διεύθυνση.'
        );
    }

    /**
     * @dataProvider emptyValues
     */
    public function testEmptyInputDoesNotCrash($source): void
    {
        $this->assertSame('Δεν καθορίστηκε', Visibility::publicLocation($source));
    }

    public static function emptyValues(): array
    {
        return [
            'κενό'          => [['location' => '']],
            'μόνο χώρα'     => [['location' => 'Ελλάδα']],
            'τίποτα'        => [[]],
            'κενή πόλη'     => [['city' => '   ', 'location' => '']],
        ];
    }
}
