<?php

use Drivejob\Core\Session;

require_once ROOT_DIR . '/src/Views/partials/header.php';
?>

<div class="container mt-4">
    <h1>Ταιριάσματα Εταιρείας</h1>

    <div class="card">
        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Όλα τα Ταιριάσματα</h5>
            <a href="/company/<?= $_SESSION['user_id'] ?>/update-matches" class="btn btn-light btn-sm">Ενημέρωση Ταιριασμάτων</a>
        </div>
        <div class="card-body">
            <?php if (empty($matches['results'])): ?>
                <p class="text-muted">Δεν υπάρχουν ταιριάσματα αυτή τη στιγμή.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Όνομα Οδηγού</th>
                                <th>Τοποθεσία</th>
                                <th>Τύπος Οχήματος</th>
                                <th>Εμπειρία</th>
                                <th>Ποσοστό Ταιριάσματος</th>
                                <th>Ενέργειες</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($matches['results'] as $match): ?>
                                <tr>
                                    <td><?= htmlspecialchars($match['first_name'] . ' ' . $match['last_name']) ?></td>
                                    <td><?= htmlspecialchars($match['city'] . ', ' . $match['country']) ?></td>
                                    <td><?= htmlspecialchars($match['vehicle_type']) ?></td>
                                    <td><?= htmlspecialchars($match['experience_years']) ?> χρόνια</td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $match['match_score'] ?>%;" aria-valuenow="<?= $match['match_score'] ?>" aria-valuemin="0" aria-valuemax="100"><?= round($match['match_score']) ?>%</div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="/driver/<?= $match['id'] ?>/profile" class="btn btn-primary btn-sm">Προβολή Προφίλ</a>
                                        <a href="/job-offer/create/<?= $match['id'] ?>" class="btn btn-success btn-sm">Προσφορά</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Σελιδοποίηση -->
                <?php if ($matches['pagination']['pages'] > 1): ?>
                    <nav aria-label="Σελιδοποίηση">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>" aria-label="Προηγούμενη">
                                        <span aria-hidden="true">&laquo;</span>
                                        <span class="sr-only">Προηγούμενη</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $matches['pagination']['pages']; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&limit=<?= $limit ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $matches['pagination']['pages']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>" aria-label="Επόμενη">
                                        <span aria-hidden="true">&raquo;</span>
                                        <span class="sr-only">Επόμενη</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once ROOT_DIR . '/src/Views/partials/footer.php'; ?>