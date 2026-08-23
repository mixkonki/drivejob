<?php

/**
 * Κοινή απόδοση της κατάστασης προσφοράς.
 *
 * Οι τιμές αντιστοιχούν 1:1 στο enum της στήλης job_offers.status. Είναι
 * σκόπιμα ΠΑΡΑΛΛΗΛΕΣ με τις αιτήσεις — η πλατφόρμα δεν πρέπει να μιλάει
 * δύο γλώσσες για το ίδιο πράγμα ανάλογα με το ποιος ξεκίνησε.
 *
 * Η μόνη διαφορά είναι ότι η προσφορά λήγει (expired), ενώ η αίτηση όχι:
 * μια προσφορά έχει ημερομηνία έναρξης που περνάει.
 */

if (!function_exists('offerStatus')) {
    function offerStatus(?string $status): array
    {
        $map = [
            'pending'   => ['Σε αναμονή',  '#f59e0b'],
            'viewed'    => ['Εξετάστηκε',  '#3b82f6'],
            'accepted'  => ['Έγινε δεκτή', '#16a34a'],
            'rejected'  => ['Απορρίφθηκε', '#dc2626'],
            'withdrawn' => ['Αποσύρθηκε',  '#6b7280'],
            'expired'   => ['Έληξε',       '#6b7280'],
        ];

        return $map[$status] ?? ['Άγνωστη κατάσταση', '#6b7280'];
    }
}

if (!function_exists('offerStatusBadge')) {
    function offerStatusBadge(?string $status): string
    {
        [$label, $color] = offerStatus($status);

        return sprintf(
            '<span class="app-status" style="background:%s1a; color:%s; border:1px solid %s55;">%s</span>',
            $color,
            $color,
            $color,
            htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        );
    }
}

if (!function_exists('offerDate')) {
    function offerDate(?string $value, string $format = 'd/m/Y H:i'): string
    {
        if (empty($value) || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return '—';
        }
        $ts = strtotime($value);

        return $ts ? date($format, $ts) : '—';
    }
}

if (!function_exists('offerJobType')) {
    function offerJobType(?string $type): string
    {
        $map = [
            'full_time'  => 'Πλήρης απασχόληση',
            'part_time'  => 'Μερική απασχόληση',
            'contract'   => 'Σύμβαση έργου',
            'temporary'  => 'Προσωρινή',
            'freelance'  => 'Ελεύθερος επαγγελματίας',
            'internship' => 'Πρακτική άσκηση',
            'seasonal'   => 'Εποχική',
        ];

        if (empty($type)) {
            return '—';
        }

        return $map[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }
}

if (!function_exists('offerSalary')) {
    /**
     * Ο μισθός σε μία φράση.
     *
     * Οι τρεις στήλες (min, max, period) γεμίζουν σπάνια και οι τρεις. Αντί
     * κάθε view να ξαναγράφει τους ίδιους τέσσερις ελέγχους, μπαίνουν εδώ.
     */
    function offerSalary($min, $max, ?string $period): string
    {
        $periods = [
            'hour'  => 'ανά ώρα',
            'day'   => 'ημερησίως',
            'week'  => 'εβδομαδιαίως',
            'month' => 'μηνιαίως',
            'year'  => 'ετησίως',
        ];

        $min = ($min === null || $min === '' || (float) $min <= 0) ? null : (float) $min;
        $max = ($max === null || $max === '' || (float) $max <= 0) ? null : (float) $max;

        if ($min === null && $max === null) {
            return 'Κατόπιν συμφωνίας';
        }

        $fmt = fn(float $v): string => number_format($v, 0, ',', '.') . '€';

        if ($min !== null && $max !== null && $min != $max) {
            $amount = $fmt($min) . ' – ' . $fmt($max);
        } else {
            $amount = $fmt($min ?? $max);
        }

        $suffix = $period && isset($periods[$period]) ? ' ' . $periods[$period] : '';

        return $amount . $suffix;
    }
}
