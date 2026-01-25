<?php
// Φόρτωση του header
include ROOT_DIR . '/src/Views/partials/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">Διαγραφή Αγγελίας</h1>

            <?php include ROOT_DIR . '/src/Views/partials/alerts.php'; ?>

            <div class="card">
                <div class="card-body">
                    <div class="alert alert-danger">
                        <h4 class="alert-heading">Προσοχή!</h4>
                        <p>Είστε βέβαιοι ότι θέλετε να διαγράψετε την αγγελία με τίτλο: <strong><?= htmlspecialchars($listing['title']) ?></strong>;</p>
                        <p>Αυτή η ενέργεια δεν μπορεί να αναιρεθεί.</p>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="<?= BASE_URL ?>job-listings/show/<?= $listing['id'] ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Ακύρωση
                        </a>
                        <form action="<?= BASE_URL ?>job-listings/destroy/<?= $listing['id'] ?>" method="post">
                            <input type="hidden" name="csrf_token" value="<?= \Drivejob\Core\CSRF::generateToken() ?>">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Διαγραφή
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Φόρτωση του footer
include ROOT_DIR . '/src/Views/partials/footer.php';
?>