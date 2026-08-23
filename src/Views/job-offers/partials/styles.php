<?php

/**
 * Στυλ των προσφορών εργασίας.
 *
 * Η βάση είναι κοινή με τις αιτήσεις — είναι η ίδια οπτική γλώσσα, απλώς
 * ιδωμένη από την ανάποδη πλευρά. Εδώ προστίθεται μόνο ό,τι δεν υπάρχει
 * εκεί: η φόρμα αποστολής προσφοράς.
 */

include ROOT_DIR . '/src/Views/partials/app-styles.php';
?>
<style>
    .app-form { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px;
                padding: 1.5rem; max-width: 780px; }
    .app-form fieldset { border: 0; padding: 0; margin: 0 0 1.75rem; }
    .app-form legend { font-weight: 600; font-size: .95rem; padding: 0;
                       margin-bottom: .9rem; color: #111827; }

    .app-field { margin-bottom: 1rem; }
    .app-field label { display: block; font-size: .85rem; font-weight: 600;
                       color: #374151; margin-bottom: .3rem; }
    .app-field input[type="text"], .app-field input[type="number"],
    .app-field input[type="date"], .app-field select, .app-field textarea,
    .app-field input[type="file"] {
        width: 100%; padding: .55rem .7rem; border: 1px solid #d1d5db;
        border-radius: 6px; font-family: inherit; font-size: .95rem;
        background: #fff; color: #111827; box-sizing: border-box;
    }
    .app-field textarea { min-height: 130px; resize: vertical; }
    .app-field input:focus, .app-field select:focus, .app-field textarea:focus {
        outline: 2px solid #b3261e33; border-color: #b3261e;
    }
    .app-field .hint { display: block; font-size: .78rem; color: #6b7280; margin-top: .3rem; }
    .app-field .err { display: block; font-size: .8rem; color: #991b1b; margin-top: .3rem; }
    .app-field.has-err input, .app-field.has-err select, .app-field.has-err textarea {
        border-color: #dc2626;
    }

    .app-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1rem; }
    .app-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0 1rem; }

    .app-required { color: #b3261e; }

    .app-submit { background: #b3261e; color: #fff; border: 0; border-radius: 6px;
                  padding: .7rem 1.5rem; font-size: 1rem; font-weight: 600;
                  font-family: inherit; cursor: pointer; }
    .app-submit:hover { background: #8f1e18; }

    .app-note { background: #eff6ff; border-left: 3px solid #3b82f6; color: #1e3a8a;
                padding: .85rem 1rem; border-radius: 0 6px 6px 0; margin-bottom: 1.25rem;
                font-size: .9rem; }

    .app-files { display: grid; gap: .5rem; }
    .app-files a { color: #b3261e; font-weight: 600; text-decoration: none; }

    @media (max-width: 700px) {
        .app-row, .app-row-3 { grid-template-columns: 1fr; }
    }
</style>
