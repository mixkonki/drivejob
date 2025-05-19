<?php

use Drivejob\Core\Session;

require_once ROOT_DIR . '/src/Views/partials/header.php';
?>

<div class="container mt-4">
    <h1>Αποτελέσματα Αναζήτησης</h1>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Φίλτρα Αναζήτησης</h5>
                </div>
                <div class="card-body">
                    <form action="/search" method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="location" class="form-label">Τοποθεσία</label>
                            <input type="text" class="form-control" id="location" name="location" value="<?= htmlspecialchars($criteria['location'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="distance" class="form-label">Απόσταση (χλμ)</label>
                            <input type="number" class="form-control" id="distance" name="distance" min="1" max="500" value="<?= htmlspecialchars($criteria['distance'] ?? 50) ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="job_type" class="form-label">Τύπος Εργασίας</label>
                            <select class="form-select" id="job_type" name="job_type">
                                <option value="">Όλοι οι τύποι</option>
                                <option value="full_time" <?= ($criteria['job_type'] ?? '') === 'full_time' ? 'selected' : '' ?>>Πλήρης Απασχόληση</option>
                                <option value="part_time" <?= ($criteria['job_type'] ?? '') === 'part_time' ? 'selected' : '' ?>>Μερική Απασχόληση</option>
                                <option value="contract" <?= ($criteria['job_type'] ?? '') === 'contract' ? 'selected' : '' ?>>Σύμβαση</option>
                                <option value="temporary" <?= ($criteria['job_type'] ?? '') === 'temporary' ? 'selected' : '' ?>>Προσωρινή</option>
                                <option value="seasonal" <?= ($criteria['job_type'] ?? '') === 'seasonal' ? 'selected' : '' ?>>Εποχιακή</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="vehicle_type" class="form-label">Τύπος Οχήματος</label>
                            <select class="form-select" id="vehicle_type" name="vehicle_type">
                                <option value="">Όλοι οι τύποι</option>
                                <option value="car" <?= ($criteria['vehicle_type'] ?? '') === 'car' ? 'selected' : '' ?>>Αυτοκίνητο</option>
                                <option value="van" <?= ($criteria['vehicle_type'] ?? '') === 'van' ? 'selected' : '' ?>>Βαν</option>
                                <option value="truck" <?= ($criteria['vehicle_type'] ?? '') === 'truck' ? 'selected' : '' ?>>Φορτηγό</option>
                                <option value="bus" <?= ($criteria['vehicle_type'] ?? '') === 'bus' ? 'selected' : '' ?>>Λεωφορείο</option>
                                <option value="taxi" <?= ($criteria['vehicle_type'] ?? '') === 'taxi' ? 'selected' : '' ?>>Ταξί</option>
                                <option value="motorcycle" <?= ($criteria['vehicle_type'] ?? '') === 'motorcycle' ? 'selected' : '' ?>>Μοτοσικλέτα</option>
                                <option value="special" <?= ($criteria['vehicle_type'] ?? '') === 'special' ? 'selected' : '' ?>>Ειδικό Όχημα</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="salary_min" class="form-label">Ελάχιστος Μισθός (€)</label>
                            <input type="number" class="form-control" id="salary_min" name="salary_min" min="0" value="<?= htmlspecialchars($criteria['salary_min'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="salary_max" class="form-label">Μέγιστος Μισθός (€)</label>
                            <input type="number" class="form-control" id="salary_max" name="salary_max" min="0" value="<?= htmlspecialchars($criteria['salary_max'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">Αναζήτηση</button>
                            <a href="/search" class="btn btn-secondary">Καθαρισμός</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">Αποτελέσματα</h5>
        </div>
        <div class="card-body">
            <?php if (empty($results['results'])): ?>
                <p class="text-muted">Δεν βρέθηκαν αποτελέσματα που να ταιριάζουν με τα κριτήρια αναζήτησής σας.</p>
            <?php else: ?>
                <?php if (Session::get('role') === 'driver'): ?>
                    <!-- Αποτελέσματα για οδηγούς (αγγελίες εταιρειών) -->
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
                                <?php foreach ($results['results'] as $result): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($result['title']) ?></td>
                                        <td><?= htmlspecialchars($result['company_name']) ?></td>
                                        <td><?= htmlspecialchars($result['location']) ?></td>
                                        <td><?= htmlspecialchars($result['vehicle_type']) ?></td>
                                        <td><?= htmlspecialchars($result['salary_min']) ?> - <?= htmlspecialchars($result['salary_max']) ?> €</td>
                                        <td>
                                            <?php if (isset($result['match_score'])): ?>
                                                <div class="progress">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $result['match_score'] ?>%;" aria-valuenow="<?= $result['match_score'] ?>" aria-valuemin="0" aria-valuemax="100"><?= round($result['match_score']) ?>%</div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">Μη διαθέσιμο</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="/job-listing/<?= $result['id'] ?>" class="btn btn-primary btn-sm">Προβολή</a>
                                            <a href="/job-application/create/<?= $result['id'] ?>" class="btn btn-success btn-sm">Αίτηση</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <!-- Αποτελέσματα για εταιρείες (οδηγοί) -->
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
                                <?php foreach ($results['results'] as $result): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($result['first_name'] . ' ' . $result['last_name']) ?></td>
                                        <td><?= htmlspecialchars($result['city'] . ', ' . $result['country']) ?></td>
                                        <td><?= htmlspecialchars($result['vehicle_type']) ?></td>
                                        <td><?= htmlspecialchars($result['experience_years']) ?> χρόνια</td>
                                        <td>
                                            <?php if (isset($result['match_score'])): ?>
                                                <div class="progress">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $result['match_score'] ?>%;" aria-valuenow="<?= $result['match_score'] ?>" aria-valuemin="0" aria-valuemax="100"><?= round($result['match_score']) ?>%</div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">Μη διαθέσιμο</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="/driver/<?= $result['id'] ?>/profile" class="btn btn-primary btn-sm">Προβολή Προφίλ</a>
                                            <a href="/job-offer/create/<?= $result['id'] ?>" class="btn btn-success btn-sm">Προσφορά</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <!-- Σελιδοποίηση -->
                <?php if ($results['pagination']['pages'] > 1): ?>
                    <nav aria-label="Σελιδοποίηση">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>&<?= http_build_query(array_filter($criteria)) ?>" aria-label="Προηγούμενη">
                                        <span aria-hidden="true">&laquo;</span>
                                        <span class="sr-only">Προηγούμενη</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $results['pagination']['pages']; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&limit=<?= $limit ?>&<?= http_build_query(array_filter($criteria)) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $results['pagination']['pages']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>&<?= http_build_query(array_filter($criteria)) ?>" aria-label="Επόμενη">
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