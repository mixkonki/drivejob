<?php

use Drivejob\Core\Session;

require_once ROOT_DIR . '/src/Views/partials/header.php';
?>

<div class="container mt-4">
    <h1>Ταιριάσματα Οδηγού</h1>

    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Προτεινόμενες Αγγελίες</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recommendations)): ?>
                        <p class="text-muted">Δεν υπάρχουν προτεινόμενες αγγελίες αυτή τη στιγμή.</p>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($recommendations as $recommendation): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($recommendation['title']) ?></h5>
                                            <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($recommendation['company_name']) ?></h6>
                                            <p class="card-text">
                                                <strong>Τοποθεσία:</strong> <?= htmlspecialchars($recommendation['location']) ?><br>
                                                <strong>Τύπος Οχήματος:</strong> <?= htmlspecialchars($recommendation['vehicle_type']) ?><br>
                                                <strong>Μισθός:</strong> <?= htmlspecialchars($recommendation['salary_min']) ?> - <?= htmlspecialchars($recommendation['salary_max']) ?> €<br>
                                                <strong>Ποσοστό Ταιριάσματος:</strong> <span class="badge bg-success"><?= round($recommendation['match_score']) ?>%</span>
                                            </p>
                                            <a href="/job-listing/<?= $recommendation['id'] ?>" class="btn btn-primary">Προβολή Αγγελίας</a>
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
            <a href="/driver/<?= $_SESSION['user_id'] ?>/update-matches" class="btn btn-light btn-sm">Ενημέρωση Ταιριασμάτων</a>
        </div>
        <div class="card-body">
            <?php if (empty($matches['results'])): ?>
                <p class="text-muted">Δεν υπάρχουν ταιριάσματα αυτή τη στιγμή.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Τίτλος</th>
                                <th>Εταιρεία</th>
                                <th>Τοποθεσία</th>
                                <th>Τύπος Οχήματος</th>
                                <th>Μισθός</th>
                                <th>Ποσοστό Ταιριάσματος</th>
                                <th>Ενέργειες</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($matches['results'] as $match): ?>
                                <tr>
                                    <td><?= htmlspecialchars($match['title']) ?></td>
                                    <td><?= htmlspecialchars($match['company_name']) ?></td>
                                    <td><?= htmlspecialchars($match['location']) ?></td>
                                    <td><?= htmlspecialchars($match['vehicle_type']) ?></td>
                                    <td><?= htmlspecialchars($match['salary_min']) ?> - <?= htmlspecialchars($match['salary_max']) ?> €</td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $match['match_score'] ?>%;" aria-valuenow="<?= $match['match_score'] ?>" aria-valuemin="0" aria-valuemax="100"><?= round($match['match_score']) ?>%</div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="/job-listing/<?= $match['id'] ?>" class="btn btn-primary btn-sm">Προβολή</a>
                                        <a href="/job-application/create/<?= $match['id'] ?>" class="btn btn-success btn-sm">Αίτηση</a>
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