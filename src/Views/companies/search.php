<?php

/**
 * Αναζήτηση επιχειρήσεων — GET /companies/search
 *
 * ΓΙΑΤΙ ΓΡΑΦΤΗΚΕ ΤΩΡΑ: η διαδρομή και ο controller υπήρχαν από την αρχή,
 * το view ποτέ. Κάθε επίσκεψη στη σελίδα κατέληγε σε σφάλμα ή σε λευκή
 * οθόνη, ανάλογα με τις ρυθμίσεις εμφάνισης σφαλμάτων.
 *
 * ΤΙ ΔΕΙΧΝΕΙ ΚΑΙ ΤΙ ΟΧΙ: τα δεδομένα έρχονται ήδη φιλτραρισμένα από τον
 * controller μέσα από λίστα επιτρεπτών πεδίων. Το view ΔΕΝ αποφασίζει τι
 * επιτρέπεται να φανεί — απλώς εμφανίζει ό,τι του δόθηκε. Στοιχεία
 * επικοινωνίας δεν υπάρχουν καν στα δεδομένα, οπότε δεν μπορούν να
 * διαρρεύσουν από εδώ.
 *
 * Μεταβλητές: $companies, $pagination
 */

$pageTitle = 'Αναζήτηση Επιχειρήσεων';

include ROOT_DIR . '/src/Views/partials/header.php';

$companies = $companies ?? [];
$pagination = $pagination ?? ['page' => 1, 'pages' => 1, 'total' => 0];

$q = static fn(string $k): string => htmlspecialchars((string) ($_GET[$k] ?? ''), ENT_QUOTES, 'UTF-8');
$viewerLoggedIn = \Drivejob\Core\Session::has('user_id');
?>

<style>
    .cs-wrap { max-width: 1100px; margin: 0 auto; padding: 1.5rem 1rem 4rem; }

    .cs-head { margin-bottom: 1.25rem; }
    .cs-head h1 { margin: 0 0 .35rem; font-size: 1.6rem; }
    .cs-head p { margin: 0; color: #6b7280; font-size: .95rem; }

    .cs-filters {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: .75rem;
        align-items: end;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }

    .cs-field label {
        display: block;
        font-size: .8rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: .3rem;
    }

    .cs-field input, .cs-field select {
        width: 100%;
        padding: .55rem .7rem;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font: inherit;
        font-size: .92rem;
        background: #fff;
    }

    .cs-actions { display: flex; gap: .5rem; }

    .cs-btn {
        padding: .55rem 1.1rem;
        border-radius: 6px;
        border: 1px solid transparent;
        font: inherit;
        font-size: .92rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        text-align: center;
    }

    .cs-btn-go { background: #b3262e; color: #fff; }
    .cs-btn-go:hover { background: #8e1c1c; }
    .cs-btn-clear { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }

    .cs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1rem;
    }

    .cs-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 1.1rem;
        display: flex;
        flex-direction: column;
        gap: .5rem;
    }

    .cs-card-top { display: flex; align-items: center; gap: .75rem; }

    .cs-logo {
        width: 46px; height: 46px;
        border-radius: 8px;
        object-fit: contain;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        flex: 0 0 auto;
    }

    .cs-logo-blank {
        width: 46px; height: 46px;
        border-radius: 8px;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        display: flex; align-items: center; justify-content: center;
        color: #9ca3af; font-weight: 700; font-size: 1.05rem;
        flex: 0 0 auto;
    }

    .cs-name { font-weight: 700; font-size: 1.02rem; line-height: 1.3; }
    .cs-name a { color: #111827; text-decoration: none; }
    .cs-name a:hover { color: #b3262e; }

    .cs-place { font-size: .86rem; color: #6b7280; display: flex; align-items: center; gap: .3rem; }

    .cs-desc {
        font-size: .89rem;
        color: #4b5563;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .cs-meta {
        display: flex;
        flex-wrap: wrap;
        gap: .4rem;
        margin-top: auto;
        padding-top: .6rem;
        border-top: 1px solid #f3f4f6;
    }

    .cs-chip {
        font-size: .76rem;
        padding: .18rem .55rem;
        border-radius: 999px;
        background: #f3f4f6;
        color: #4b5563;
        white-space: nowrap;
    }

    .cs-chip-ok { background: #e8f5ec; color: #226b3c; }

    .cs-empty {
        padding: 3rem 1rem;
        text-align: center;
        color: #6b7280;
        background: #fff;
        border: 1px dashed #d1d5db;
        border-radius: 10px;
    }

    .cs-note {
        background: #f8f9fa;
        border-left: 3px solid #b3262e;
        border-radius: 0 8px 8px 0;
        padding: .8rem 1rem;
        font-size: .88rem;
        color: #4b5563;
        margin-bottom: 1.25rem;
    }

    .cs-pager {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 1rem;
        margin-top: 2rem;
        font-size: .92rem;
        color: #6b7280;
    }

    .cs-pager a { color: #b3262e; font-weight: 600; text-decoration: none; }
</style>

<main class="cs-wrap">
    <div class="cs-head">
        <h1>Επιχειρήσεις μεταφορών</h1>
        <p>
            <?php echo (int) ($pagination['total'] ?? count($companies)); ?>
            εγγεγραμμένες επιχειρήσεις στην πλατφόρμα
        </p>
    </div>

    <?php if (!$viewerLoggedIn) : ?>
        <p class="cs-note">
            Συνδέσου για να δεις την επωνυμία κάθε επιχείρησης και να υποβάλεις αίτηση στις αγγελίες της.
        </p>
    <?php endif; ?>

    <form method="get" action="<?php echo BASE_URL; ?>companies/search" class="cs-filters">
        <div class="cs-field">
            <label for="cs-name">Επωνυμία</label>
            <input type="text" id="cs-name" name="name" value="<?php echo $q('name'); ?>" placeholder="π.χ. Μεταφορική">
        </div>

        <div class="cs-field">
            <label for="cs-location">Περιοχή</label>
            <input type="text" id="cs-location" name="location" value="<?php echo $q('location'); ?>" placeholder="π.χ. Θεσσαλονίκη">
        </div>

        <div class="cs-field">
            <label for="cs-industry">Κλάδος</label>
            <input type="text" id="cs-industry" name="industry" value="<?php echo $q('industry'); ?>" placeholder="π.χ. Εμπορευματικές">
        </div>

        <div class="cs-field">
            <label for="cs-sort">Ταξινόμηση</label>
            <select id="cs-sort" name="sort_by">
                <?php
                $sorts = [
                    'last_login' => 'Πρόσφατη δραστηριότητα',
                    'company_name' => 'Επωνυμία',
                    'rating' => 'Αξιολόγηση',
                    'fleet_size' => 'Μέγεθος στόλου',
                ];
                $cur = $_GET['sort_by'] ?? 'last_login';
                foreach ($sorts as $val => $label) :
                    ?>
                    <option value="<?php echo $val; ?>" <?php echo $cur === $val ? 'selected' : ''; ?>>
                        <?php echo $label; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="cs-actions">
            <button type="submit" class="cs-btn cs-btn-go">Αναζήτηση</button>
            <a href="<?php echo BASE_URL; ?>companies/search" class="cs-btn cs-btn-clear">Καθαρισμός</a>
        </div>
    </form>

    <?php if (empty($companies)) : ?>
        <div class="cs-empty">
            <p><strong>Δεν βρέθηκαν επιχειρήσεις με αυτά τα κριτήρια.</strong></p>
            <p>Δοκίμασε λιγότερα φίλτρα ή διαφορετική περιοχή.</p>
        </div>
    <?php else : ?>
        <div class="cs-grid">
            <?php foreach ($companies as $company) :
                $name = (string) ($company['company_name'] ?? 'Επιχείρηση');
                $cid = (int) ($company['id'] ?? 0);
                $place = trim((string) ($company['location'] ?? ''));
                $logo = $company['company_logo'] ?? null;
                ?>
                <article class="cs-card">
                    <div class="cs-card-top">
                        <?php if (!empty($logo)) : ?>
                            <img class="cs-logo" src="<?php echo BASE_URL . htmlspecialchars((string) $logo, ENT_QUOTES, 'UTF-8'); ?>"
                                 alt="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php else : ?>
                            <div class="cs-logo-blank" aria-hidden="true">
                                <?php echo htmlspecialchars(mb_substr($name, 0, 1), ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>

                        <div>
                            <div class="cs-name">
                                <?php if ($viewerLoggedIn && $cid > 0) : ?>
                                    <a href="<?php echo BASE_URL; ?>companies/profile/<?php echo $cid; ?>">
                                        <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                <?php else : ?>
                                    <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
                                <?php endif; ?>
                            </div>

                            <?php if ($place !== '' && $place !== 'Δεν καθορίστηκε') : ?>
                                <div class="cs-place">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor"
                                         stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 21s-6.5-5.4-6.5-10a6.5 6.5 0 1 1 13 0c0 4.6-6.5 10-6.5 10Z"/>
                                        <circle cx="12" cy="10.6" r="2.4"/>
                                    </svg>
                                    <?php echo htmlspecialchars($place, ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($company['description'])) : ?>
                        <p class="cs-desc"><?php echo htmlspecialchars((string) $company['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>

                    <div class="cs-meta">
                        <?php if (!empty($company['is_verified'])) : ?>
                            <span class="cs-chip cs-chip-ok">Επιβεβαιωμένη</span>
                        <?php endif; ?>

                        <?php if (!empty($company['fleet_size'])) : ?>
                            <span class="cs-chip"><?php echo (int) $company['fleet_size']; ?> οχήματα</span>
                        <?php endif; ?>

                        <?php if (!empty($company['industry'])) : ?>
                            <span class="cs-chip"><?php echo htmlspecialchars((string) $company['industry'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>

                        <?php if (!empty($company['rating_count'])) : ?>
                            <span class="cs-chip">
                                ★ <?php echo number_format((float) ($company['rating'] ?? 0), 1); ?>
                                (<?php echo (int) $company['rating_count']; ?>)
                            </span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php
        $page = (int) ($pagination['page'] ?? 1);
        $pages = (int) ($pagination['pages'] ?? 1);
        if ($pages > 1) :
            $qs = $_GET;
            ?>
            <nav class="cs-pager">
                <?php if ($page > 1) :
                    $qs['page'] = $page - 1; ?>
                    <a href="?<?php echo htmlspecialchars(http_build_query($qs), ENT_QUOTES, 'UTF-8'); ?>">← Προηγούμενη</a>
                <?php endif; ?>

                <span>Σελίδα <?php echo $page; ?> από <?php echo $pages; ?></span>

                <?php if ($page < $pages) :
                    $qs['page'] = $page + 1; ?>
                    <a href="?<?php echo htmlspecialchars(http_build_query($qs), ENT_QUOTES, 'UTF-8'); ?>">Επόμενη →</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php include ROOT_DIR . '/src/Views/partials/footer.php'; ?>
