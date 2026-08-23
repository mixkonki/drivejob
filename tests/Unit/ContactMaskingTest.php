<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Drivejob\Services\Visibility;

/**
 * Απόκρυψη στοιχείων επικοινωνίας.
 *
 * Ξεχωριστά από το VisibilityTest επειδή ΔΕΝ χρειάζονται βάση: είναι καθαρές
 * συναρτήσεις. Έτσι τρέχουν και στο CI, όπου δεν υπάρχει MySQL και τα τεστ
 * που εξαρτώνται από αυτήν παρακάμπτονται.
 *
 * Ο σκοπός της απόκρυψης: ο οδηγός να βλέπει ΟΤΙ υπάρχει στοιχείο
 * επικοινωνίας, χωρίς να μπορεί να το χρησιμοποιήσει πριν η εταιρεία δείξει
 * ενδιαφέρον. Αν η απόκρυψη αφήνει αρκετό ώστε να μαντευτεί το πρωτότυπο,
 * δεν προσφέρει τίποτα.
 */
class ContactMaskingTest extends TestCase
{
    public function testEmailKeepsDomainButHidesUser(): void
    {
        $masked = Visibility::maskEmail('kostas.michailidis@hotmail.gr');

        $this->assertStringContainsString('@hotmail.gr', $masked, 'Ο πάροχος μένει ορατός.');
        $this->assertStringNotContainsString('kostas.michailidis', $masked, 'Το όνομα χρήστη δεν πρέπει να διαβάζεται.');
        $this->assertStringStartsWith('k', $masked, 'Το πρώτο γράμμα βοηθά τον χρήστη να αναγνωρίσει ποιο email είναι.');
    }

    public function testShortEmailIsStillHidden(): void
    {
        $masked = Visibility::maskEmail('ab@x.gr');

        $this->assertStringNotContainsString('ab@', $masked);
        $this->assertStringContainsString('@x.gr', $masked);
    }

    public function testPhoneKeepsPrefixAndLastDigits(): void
    {
        $masked = Visibility::maskPhone('6972964602');

        $this->assertStringNotContainsString('6972964602', $masked);
        $this->assertStringStartsWith('697', $masked, 'Το πρόθεμα δείχνει αν είναι κινητό ή σταθερό.');
        $this->assertStringEndsWith('02', $masked);
    }

    public function testPhoneIgnoresFormatting(): void
    {
        $this->assertSame(
            Visibility::maskPhone('6972964602'),
            Visibility::maskPhone('697 296 4602'),
            'Τα κενά δεν αλλάζουν το αποτέλεσμα για τον ίδιο αριθμό.'
        );

        $this->assertSame(
            Visibility::maskPhone('6972964602'),
            Visibility::maskPhone('697-296-4602'),
            'Ούτε οι παύλες.'
        );
    }

    /**
     * Κενές ή άκυρες τιμές δεν πρέπει να αποκαλύπτουν τίποτα ούτε να σκάνε.
     */
    public function testEmptyAndInvalidValues(): void
    {
        $this->assertSame('•••', Visibility::maskEmail(null));
        $this->assertSame('•••', Visibility::maskEmail(''));
        $this->assertSame('•••', Visibility::maskEmail('χωρίς-παπάκι'));
        $this->assertSame('•••', Visibility::maskPhone(null));
        $this->assertSame('•••', Visibility::maskPhone(''));
        $this->assertSame('•••', Visibility::maskPhone('12'), 'Πολύ σύντομος αριθμός: δεν αποκαλύπτεται καθόλου.');
    }
}
