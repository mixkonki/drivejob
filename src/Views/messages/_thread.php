<?php

/**
 * Κοινή όψη συνομιλίας οδηγού & εταιρείας. (ξαναγράφτηκε 01/09/2026)
 *
 * Ίδια ιστορία με το _inbox.php: αντικατέστησε δύο legacy δίδυμα views
 * με Bootstrap από CDN (μπλοκαρισμένο από το CSP) και διπλό DOCTYPE.
 * Φυσαλίδες αριστερά (ο άλλος) / δεξιά (εγώ), φόρμα απάντησης από κάτω.
 *
 * Περιμένει στο scope:
 *   $pageTitle    — θέμα συνομιλίας
 *   $conversation — subject, job_title + counterpart_name (το βάζει ο wrapper)
 *   $messages     — sender_type, message, created_at (ASC)
 *   $meType       — 'driver' ή 'company' (ποιες φυσαλίδες πάνε δεξιά)
 *   $backUrl      — σύνδεσμος επιστροφής στο κουτί εισερχομένων
 */
?>

<main class="msg-page">
    <div class="thread-wrap">
        <header class="thread-head">
            <a class="thread-back" href="<?php echo $backUrl; ?>">&larr; Όλα τα μηνύματα</a>
            <h1><?php echo htmlspecialchars($conversation['subject'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="thread-meta">
                Με: <strong><?php echo htmlspecialchars($conversation['counterpart_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <?php if (!empty($conversation['job_title'])) : ?>
                    · Θέση: <?php echo htmlspecialchars($conversation['job_title'], ENT_QUOTES, 'UTF-8'); ?>
                <?php endif; ?>
            </p>
        </header>

        <div class="thread-box">
            <?php if (empty($messages)) : ?>
                <p class="thread-empty">Δεν υπάρχουν μηνύματα σε αυτή τη συνομιλία.</p>
            <?php else : ?>
                <?php foreach ($messages as $m) : ?>
                    <?php $mine = $m['sender_type'] === $meType; ?>
                    <div class="bubble-row<?php echo $mine ? ' is-mine' : ''; ?>">
                        <div class="bubble">
                            <?php echo nl2br(htmlspecialchars($m['message'], ENT_QUOTES, 'UTF-8')); ?>
                            <span class="bubble-when"><?php echo date('d/m/Y H:i', strtotime($m['created_at'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <form method="post" action="" class="thread-reply">
            <input type="hidden" name="csrf_token" value="<?php echo \Drivejob\Core\CSRF::token(); ?>">
            <label class="sr-only" for="thread-message">Μήνυμα</label>
            <textarea id="thread-message" name="message" rows="3" required
                placeholder="Γράψε την απάντησή σου..."></textarea>
            <button type="submit" class="btn-primary">Αποστολή</button>
        </form>
    </div>
</main>

<style>
    .thread-wrap { max-width: 760px; margin: 0 auto; padding: 1.2rem 1rem 2.5rem; width: 100%; box-sizing: border-box; }
    .thread-back { color: #6b7280; text-decoration: none; font-size: .88rem; }
    .thread-back:hover { text-decoration: underline; }
    .thread-head h1 { margin: .45rem 0 .2rem; font-size: 1.35rem; color: #1f2937; }
    .thread-meta { margin: 0 0 1rem; color: #6b7280; font-size: .9rem; }

    .thread-box {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 10px;
        padding: 1.1rem; display: flex; flex-direction: column; gap: .55rem;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .06);
    }
    .thread-empty { color: #6b7280; text-align: center; margin: 1.5rem 0; }

    .bubble-row { display: flex; }
    .bubble-row.is-mine { justify-content: flex-end; }
    .bubble {
        max-width: 78%; padding: .55rem .8rem .4rem; border-radius: 12px;
        background: #f3f4f6; color: #1f2937; font-size: .93rem; line-height: 1.45;
        border-bottom-left-radius: 4px;
    }
    .bubble-row.is-mine .bubble {
        background: #aa3636; color: #fff;
        border-bottom-left-radius: 12px; border-bottom-right-radius: 4px;
    }
    .bubble-when { display: block; font-size: .72rem; opacity: .65; margin-top: .25rem; text-align: right; }

    .thread-reply { display: flex; gap: .6rem; margin-top: .9rem; align-items: flex-end; }
    .thread-reply textarea {
        flex: 1; padding: .6rem .75rem; border: 1px solid #d1d5db; border-radius: 8px;
        font: inherit; resize: vertical; box-sizing: border-box; background: #fff;
    }
    .thread-reply .btn-primary { white-space: nowrap; }
    .sr-only {
        position: absolute; width: 1px; height: 1px; margin: -1px;
        overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;
    }
</style>
