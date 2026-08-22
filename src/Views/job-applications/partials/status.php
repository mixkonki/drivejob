<?php

/**
 * Κοινή απόδοση της κατάστασης αίτησης.
 *
 * Οι τιμές αντιστοιχούν 1:1 στο enum της στήλης job_applications.status —
 * κάθε νέα τιμή πρέπει να προστεθεί και εδώ, αλλιώς εμφανίζεται ως «άγνωστη».
 */

if (!function_exists('applicationStatus')) {
    function applicationStatus(?string $status): array
    {
        $map = [
            'pending'     => ['Σε αναμονή',   '#f59e0b'],
            'viewed'      => ['Εξετάστηκε',   '#3b82f6'],
            'shortlisted' => ['Προεπιλογή',   '#8b5cf6'],
            'hired'       => ['Προσλήφθηκε',  '#16a34a'],
            'rejected'    => ['Απορρίφθηκε',  '#dc2626'],
            'withdrawn'   => ['Αποσύρθηκε',   '#6b7280'],
        ];

        return $map[$status] ?? ['Άγνωστη κατάσταση', '#6b7280'];
    }
}

if (!function_exists('applicationStatusBadge')) {
    function applicationStatusBadge(?string $status): string
    {
        [$label, $color] = applicationStatus($status);

        return sprintf(
            '<span class="app-status" style="background:%s1a; color:%s; border:1px solid %s55;">%s</span>',
            $color,
            $color,
            $color,
            htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        );
    }
}

if (!function_exists('applicationDate')) {
    function applicationDate(?string $value): string
    {
        if (empty($value)) {
            return '—';
        }
        $ts = strtotime($value);

        return $ts ? date('d/m/Y H:i', $ts) : '—';
    }
}

if (!function_exists('applicationJobType')) {
    /**
     * Ελληνική ετικέτα για το enum job_listings.job_type.
     */
    function applicationJobType(?string $type): string
    {
        $map = [
            'full_time'  => 'Πλήρης απασχόληση',
            'part_time'  => 'Μερική απασχόληση',
            'contract'   => 'Σύμβαση έργου',
            'temporary'  => 'Προσωρινή',
            'freelance'  => 'Ελεύθερος επαγγελματίας',
            'internship' => 'Πρακτική άσκηση',
        ];

        if (empty($type)) {
            return '—';
        }

        return $map[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }
}
