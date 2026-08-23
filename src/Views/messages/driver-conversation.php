<?php
/**
 * View μηνυμάτων — μεταφέρθηκε αυτούσιο από το src/Legacy/drivers-conversation.php (Πακέτο 5.5).
 * Μεταβλητές από MessagesController.
 */
?>
<!DOCTYPE html>
<html lang="el">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - DriveJob</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?= \Drivejob\Helpers\Asset::css('css/styles.css') ?>
    <style>
        .conversation-header {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            padding: 20px;
            margin-bottom: 20px;
        }

        .messages-container {
            max-height: 500px;
            overflow-y: auto;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .message {
            margin-bottom: 20px;
            display: flex;
        }

        .message.sent {
            justify-content: flex-end;
        }

        .message.received {
            justify-content: flex-start;
        }

        .message-bubble {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            position: relative;
        }

        .message.sent .message-bubble {
            background-color: #007bff;
            color: white;
        }

        .message.received .message-bubble {
            background-color: #e9ecef;
            color: #333;
        }

        .message-time {
            font-size: 12px;
            margin-top: 5px;
            opacity: 0.7;
        }

        .message.sent .message-time {
            text-align: right;
            color: #e0e0e0;
        }

        .message.received .message-time {
            text-align: left;
            color: #666;
        }

        .reply-form {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .company-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .company-logo-small {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <?php include ROOT_DIR . '/src/Views/partials/header.php'; ?>

    <div class="container mt-4">
        <div class="conversation-header">
            <div class="d-flex justify-content-between align-items-center">
                <div class="company-info">
                    <?php if ($conversation['company_logo']): ?>
                        <img src="<?php echo BASE_URL . $conversation['company_logo']; ?>"
                            alt="<?php echo htmlspecialchars($conversation['company_name']); ?>"
                            class="company-logo-small">
                    <?php else: ?>
                        <div class="company-logo-small bg-secondary d-flex align-items-center justify-content-center text-white">
                            <i class="fas fa-building"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h4 class="mb-0"><?php echo htmlspecialchars($conversation['subject']); ?></h4>
                        <p class="mb-0 text-muted">
                            <?php echo htmlspecialchars($conversation['company_name']); ?> -
                            <?php echo htmlspecialchars($conversation['job_title']); ?>
                        </p>
                    </div>
                </div>
                <a href="<?php echo BASE_URL; ?>drivers/messages" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Πίσω
                </a>
            </div>
        </div>

        <div class="messages-container">
            <?php foreach ($messages as $message): ?>
                <div class="message <?php echo $message['sender_type'] === 'driver' ? 'sent' : 'received'; ?>">
                    <div class="message-bubble">
                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                        </div>
                        <div class="message-time">
                            <?php
                            $date = new DateTime($message['created_at']);
                            echo $date->format('d/m/Y H:i');
                            ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($conversation['status'] === 'active'): ?>
            <div class="reply-form">
                <h5 class="mb-3">Απάντηση</h5>
                <form method="POST">
                    <div class="mb-3">
                        <textarea class="form-control" name="message" rows="4"
                            placeholder="Γράψτε το μήνυμά σας..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Αποστολή
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Αυτή η συνομιλία έχει κλείσει.
            </div>
        <?php endif; ?>
    </div>

    <?php include ROOT_DIR . '/src/Views/partials/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-scroll to bottom of messages
        document.addEventListener('DOMContentLoaded', function() {
            const messagesContainer = document.querySelector('.messages-container');
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        });
    </script>
</body>

</html>