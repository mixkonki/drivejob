<?php

use Drivejob\Core\Session;

require_once ROOT_DIR . '/src/Views/partials/header.php';
?>

<div class="container mt-4">
    <h1>Ταιριάσματα Αγγελίας</h1>

    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Προτεινόμενοι Οδηγοί</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recommendations)): ?>
                        <p class="text-muted">Δεν υπάρχουν προτεινόμενοι οδηγοί αυτή τη στιγμή.</p>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($recommendations as $recommendation): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($recommendation['first_name'] . ' ' . $recommendation['last_name']) ?></h5>
                                            <p class="card-text">
                                                <strong>Τοποθεσία:</strong> <?= htmlspecialchars($recommendation['city'] . ', ' . $recommendation['country']) ?><br>
                                                <strong>Τύπος Οχήματος:</strong> <?= htmlspecialchars($recommendation['vehicle_type']) ?><br>
                                                <strong>Εμπειρία:</strong> <?= htmlspecialchars($recommendation['experience_years']) ?> χρόνια<br>
                                                <strong>Ποσοστό Ταιριάσματος:</strong> <span class="badge bg-success"><?= round($recommendation['match_score']) ?>%</span>
                                            </p>
                                            <a href="/driver/<?= $recommendation['id'] ?>/profile" class="btn btn-primary">Προβολή Προφίλ</a>
                                            <a href="/job-offer/create/<?= $recommendation['id'] ?>" class="btn btn-success">Προσφορά</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Όλα τα Ταιριάσματα</h5>
            <a href="/job-listing/<?= $_GET['id'] ?? '' ?>/update-matches" class="btn btn-light btn-sm">Ενημέρωση Ταιριασμάτων</a>
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