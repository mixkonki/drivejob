<?php
include ROOT_DIR . '/src/Views/partials/header.php';
?>

<div class="container mt-4">
    <h1>Αναζήτηση Οδηγών</h1>
    
    <form method="GET" action="<?php echo BASE_URL; ?>drivers/search" class="mb-4">
        <div class="row">
            <div class="col-md-3">
                <input type="text" name="city" class="form-control" placeholder="Πόλη" value="<?php echo $_GET['city'] ?? ''; ?>">
            </div>
            <div class="col-md-3">
                <select name="license_type" class="form-control">
                    <option value="">Όλες οι άδειες</option>
                    <option value="B">B - Επιβατικά</option>
                    <option value="C">C - Φορτηγά</option>
                    <option value="D">D - Λεωφορεία</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="number" name="experience_years" class="form-control" placeholder="Έτη εμπειρίας" min="0">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">Αναζήτηση</button>
            </div>
        </div>
    </form>
    
    <div class="row">
        <?php if (empty($drivers)): ?>
            <p>Δεν βρέθηκαν οδηγοί με τα κριτήρια αναζήτησης.</p>
        <?php else: ?>
            <?php foreach ($drivers as $driver): ?>
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($driver['first_name'] . ' ' . $driver['last_name']); ?></h5>
                            <p class="card-text">
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($driver['city'] ?? 'Δεν έχει οριστεί'); ?><br>
                                <i class="fas fa-briefcase"></i> <?php echo $driver['experience_years'] ?? 0; ?> έτη εμπειρίας<br>
                                <?php if ($driver['available_for_work']): ?>
                                    <span class="badge bg-success">Διαθέσιμος</span>
                                <?php endif; ?>
                            </p>
                            <a href="<?php echo BASE_URL; ?>drivers/profile/<?php echo $driver['id']; ?>" class="btn btn-sm btn-primary">Προβολή Προφίλ</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
include ROOT_DIR . '/src/Views/partials/footer.php';
?>