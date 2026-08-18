<?php
/**
 * View μηνυμάτων — μεταφέρθηκε αυτούσιο από το src/Legacy/drivers-messages.php (Πακέτο 5.5).
 * Μεταβλητές από MessagesController.
 */
?>
<!DOCTYPE html>
<html lang="el">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - DriveJob</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/styles.css">
    <style>
        .conversation-item {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .conversation-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .conversation-item.unread {
            background-color: #f0f8ff;
            border-color: #007bff;
        }

        .company-logo {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .unread-badge {
            background-color: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .last-message {
            color: #666;
            font-size: 14px;
            max-width: 100%;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 20px;
        }
    </style>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/messaging-modern.css">
</head>

<body>
    <?php include ROOT_DIR . '/src/Views/partials/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-envelope"></i> <?php echo $pageTitle; ?></h2>
                    <a href="<?php echo BASE_URL; ?>drivers/profile" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Πίσω στο Προφίλ
                    </a>
                </div>

                <?php if (empty($conversations)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h4>Δεν έχετε μηνύματα</h4>
                        <p>Όταν οι εταιρείες επικοινωνήσουν μαζί σας, τα μηνύματα θα εμφανιστούν εδώ.</p>
                    </div>
                <?php else: ?>
                    <div class="conversations-list">
                        <?php foreach ($conversations as $conversation): ?>
                            <div class="conversation-item <?php echo $conversation['unread_count'] > 0 ? 'unread' : ''; ?>"
                                onclick="window.location.href='<?php echo BASE_URL; ?>drivers/conversation?id=<?php echo $conversation['id']; ?>'">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <?php if ($conversation['company_logo']): ?>
                                            <img src="<?php echo BASE_URL . $conversation['company_logo']; ?>"
                                                alt="<?php echo htmlspecialchars($conversation['company_name']); ?>"
                                                class="company-logo">
                                        <?php else: ?>
                                            <div class="company-logo bg-secondary d-flex align-items-center justify-content-center text-white">
                                                <i class="fas fa-building"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h5 class="mb-1">
                                                    <?php echo htmlspecialchars($conversation['subject']); ?>
                                                    <?php if ($conversation['unread_count'] > 0): ?>
                                                        <span class="unread-badge"><?php echo $conversation['unread_count']; ?> νέα</span>
                                                    <?php endif; ?>
                                                </h5>
                                                <p class="mb-1">
                                                    <strong><?php echo htmlspecialchars($conversation['company_name']); ?></strong> -
                                                    <?php echo htmlspecialchars($conversation['job_title']); ?>
                                                </p>
                                                <?php if ($conversation['last_message']): ?>
                                                    <p class="last-message mb-0">
                                                        <?php echo htmlspecialchars(mb_substr($conversation['last_message'], 0, 150) . (strlen($conversation['last_message']) > 150 ? "..." : "")); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted">
                                                    <?php
                                                    $date = new DateTime($conversation['updated_at']);
                                                    echo $date->format('d/m/Y H:i');
                                                    ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include ROOT_DIR . '/src/Views/partials/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>