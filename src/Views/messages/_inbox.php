<?php

/**
 * Κοινό «κουτί εισερχομένων» οδηγού & εταιρείας. (ξαναγράφτηκε 01/09/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΙ ΑΝΤΙΚΑΤΕΣΤΗΣΕ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Δύο σχεδόν πανομοιότυπα legacy views (driver-inbox / company-inbox) που:
 *   - φόρτωναν Bootstrap + Font Awesome από CDN — το style-src του CSP
 *     μας ΔΕΝ επιτρέπει το cdn.jsdelivr.net, οπότε στην παραγωγή η
 *     σελίδα έβγαινε ξεγυμνωμένη·
 *   - άνοιγαν δικό τους <!DOCTYPE html><head> και ΜΕΤΑ έκαναν include
 *     το header.php που ανοίγει άλλο ένα — δύο έγγραφα το ένα μέσα
 *     στο άλλο.
 *
 * Τώρα: ένα partial, καθόλου εξωτερικές εξαρτήσεις, κανονική ροή
 * header → περιεχόμενο → footer όπως όλες οι σύγχρονες σελίδες.
 *
 * Περιμένει στο scope:
 *   $pageTitle    — τίτλος σελίδας
 *   $conversations — γραμμές με: id, subject, job_title, unread_count,
 *                    last_message, updated_at, counterpart_name,
 *                    counterpart_image (nullable)
 *   $threadBase   — π.χ. BASE_URL . 'companies/conversation?id='
 *   $emptyText    — μήνυμα κενής λίστας
 */
?>

<main class="msg-page">
    <div class="msg-wrap">
        <header class="msg-head">
            <h1><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
        </header>

        <?php if (empty($conversations)) : ?>
            <div class="msg-empty">
                <p><strong>Δεν υπάρχουν συνομιλίες ακόμη.</strong></p>
                <p><?php echo htmlspecialchars($emptyText, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        <?php else : ?>
            <ul class="conv-list">
                <?php foreach ($conversations as $c) : ?>
                    <li>
                        <a class="conv-item<?php echo $c['unread_count'] > 0 ? ' is-unread' : ''; ?>"
                            href="<?php echo $threadBase . (int) $c['id']; ?>">
                            <span class="conv-avatar" aria-hidden="true">
                                <?php if (!empty($c['counterpart_image'])) : ?>
                                    <img src="<?php echo BASE_URL . htmlspecialchars($c['counterpart_image'], ENT_QUOTES, 'UTF-8'); ?>" alt="">
                                <?php else : ?>
                                    <?php echo htmlspecialchars(mb_strtoupper(mb_substr($c['counterpart_name'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
                                <?php endif; ?>
                            </span>
                            <span class="conv-body">
                                <span class="conv-top">
                                    <strong class="conv-name"><?php echo htmlspecialchars($c['counterpart_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <time class="conv-when"><?php echo date('d/m/Y H:i', strtotime($c['updated_at'])); ?></time>
                                </span>
                                <span class="conv-subject">
                                    <?php echo htmlspecialchars($c['subject'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if ($c['unread_count'] > 0) : ?>
                                        <span class="conv-unread"><?php echo (int) $c['unread_count']; ?> νέα</span>
                                    <?php endif; ?>
                                </span>
                                <?php if (!empty($c['last_message'])) : ?>
                                    <span class="conv-preview"><?php echo htmlspecialchars(mb_substr($c['last_message'], 0, 140), ENT_QUOTES, 'UTF-8'); ?><?php echo mb_strlen($c['last_message']) > 140 ? '…' : ''; ?></span>
                                <?php endif; ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</main>

<style>
    .msg-wrap { max-width: 760px; margin: 0 auto; padding: 1.2rem 1rem 2.5rem; width: 100%; box-sizing: border-box; }
    .msg-head h1 { margin: .3rem 0 1rem; font-size: 1.5rem; color: #1f2937; }

    .msg-empty {
        background: #fff; border: 1px dashed #d1d5db; border-radius: 10px;
        padding: 2.5rem 1.5rem; text-align: center; color: #6b7280;
    }
    .msg-empty strong { color: #374151; }
    .msg-empty p { margin: .25rem 0; }

    .conv-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .6rem; }
    .conv-item {
        display: flex; gap: .85rem; align-items: flex-start;
        background: #fff; border: 1px solid #e5e7eb; border-radius: 10px;
        padding: .9rem 1rem; text-decoration: none; color: inherit;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .06);
        transition: box-shadow .15s ease;
    }
    .conv-item:hover { box-shadow: 0 4px 12px rgba(15, 23, 42, .12); }
    .conv-item.is-unread { border-left: 4px solid #aa3636; padding-left: calc(1rem - 3px); }

    .conv-avatar {
        flex: 0 0 46px; width: 46px; height: 46px; border-radius: 50%;
        background: #aa3636; color: #fff; font-weight: 600; font-size: 1.1rem;
        display: flex; align-items: center; justify-content: center; overflow: hidden;
    }
    .conv-avatar img { width: 100%; height: 100%; object-fit: cover; }

    .conv-body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: .15rem; }
    .conv-top { display: flex; justify-content: space-between; gap: .8rem; align-items: baseline; }
    .conv-name { font-size: .97rem; color: #111827; }
    .conv-when { color: #9ca3af; font-size: .8rem; white-space: nowrap; }
    .conv-subject { font-size: .9rem; color: #374151; }
    .conv-unread {
        display: inline-block; background: #aa3636; color: #fff;
        font-size: .72rem; font-weight: 600; border-radius: 999px;
        padding: .1rem .55rem; margin-left: .4rem; vertical-align: middle;
    }
    .conv-preview {
        color: #6b7280; font-size: .86rem;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
</style>
