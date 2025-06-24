<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Συνομιλία - DriveJob</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .messages-container {
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            background: #f8f9fa;
        }
        
        .message {
            margin-bottom: 20px;
        }
        
        .message.sent {
            text-align: right;
        }
        
        .message-bubble {
            display: inline-block;
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 15px;
            position: relative;
        }
        
        .message.sent .message-bubble {
            background: #007bff;
            color: white;
        }
        
        .message.received .message-bubble {
            background: white;
            border: 1px solid #e0e0e0;
        }
        
        .message-time {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .message.sent .message-time {
            color: #ccc;
        }
    </style>
</head>
<body>
    <?php include ROOT_DIR . '/src/Views/partials/header.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><?php echo htmlspecialchars($conversation['subject']); ?></h2>
                        <p class="text-muted mb-0">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($conversation['first_name'] . ' ' . $conversation['last_name']); ?> - 
                            <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($conversation['job_title']); ?>
                        </p>
                    </div>
                    <a href="<?php echo BASE_URL; ?>companies/messages" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Πίσω στα Μηνύματα
                    </a>
                </div>
                
                <div class="messages-container mb-4">
                    <?php foreach ($messages as $message): ?>
                        <div class="message <?php echo $message['sender_type'] === 'company' ? 'sent' : 'received'; ?>">
                            <div class="message-bubble">
                                <div class="message-text"><?php echo nl2br(htmlspecialchars($message['message'])); ?></div>
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
                
                <form method="POST" action="<?php echo BASE_URL; ?>api/messaging/send.php" class="reply-form">
                    <input type="hidden" name="conversation_id" value="<?php echo $conversationId; ?>">
                    <input type="hidden" name="driver_id" value="<?php echo $conversation['driver_id']; ?>">
                    <input type="hidden" name="job_id" value="<?php echo $conversation['job_id']; ?>">
                    <div class="mb-3">
                        <textarea name="message" class="form-control" rows="3" placeholder="Γράψτε το μήνυμά σας..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Αποστολή
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <?php include ROOT_DIR . '/src/Views/partials/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>