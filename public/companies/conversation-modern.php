<?php
// Modern Conversation View
require_once __DIR__ . '/../../src/bootstrap.php';

use Drivejob\Core\Session;
use Drivejob\Core\Database;
use Drivejob\Middleware\AuthenticationMiddleware;

// Require login
AuthenticationMiddleware::requireLogin();

$pdo = Database::getInstance()->getConnection();
$conversationId = $_GET['id'] ?? null;

if (!$conversationId) {
    header('Location: ' . BASE_URL . Session::get('user_role') . '/messages');
    exit();
}

// Get conversation details
$stmt = $pdo->prepare("
    SELECT c.*, 
           u1.first_name as user1_name, u1.last_name as user1_lastname,
           u2.first_name as user2_name, u2.last_name as user2_lastname
    FROM conversations c
    JOIN users u1 ON c.user1_id = u1.id
    JOIN users u2 ON c.user2_id = u2.id
    WHERE c.id = ? AND (c.user1_id = ? OR c.user2_id = ?)
");
$stmt->execute([$conversationId, Session::get('user_id'), Session::get('user_id')]);
$conversation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conversation) {
    header('Location: ' . BASE_URL . Session::get('user_role') . '/messages');
    exit();
}

// Determine other user
$otherUserId = $conversation['user1_id'] == Session::get('user_id') 
    ? $conversation['user2_id'] 
    : $conversation['user1_id'];
    
$otherUserName = $conversation['user1_id'] == Session::get('user_id')
    ? $conversation['user2_name'] . ' ' . $conversation['user2_lastname']
    : $conversation['user1_name'] . ' ' . $conversation['user1_lastname'];

// Get messages
$stmt = $pdo->prepare("
    SELECT m.*, u.first_name, u.last_name 
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.conversation_id = ?
    ORDER BY m.created_at ASC
");
$stmt->execute([$conversationId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark messages as read
$stmt = $pdo->prepare("
    UPDATE messages 
    SET is_read = 1 
    WHERE conversation_id = ? AND receiver_id = ?
");
$stmt->execute([$conversationId, Session::get('user_id')]);

include ROOT_DIR . '/src/Views/partials/header.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/messaging-modern.css">

<div class="messaging-container">
    <!-- Conversations Sidebar -->
    <div class="conversations-sidebar">
        <div class="conversations-header">
            <h2>Μηνύματα</h2>
        </div>
        <div id="conversationsList">
            <!-- Will be loaded via AJAX -->
        </div>
    </div>
    
    <!-- Chat Window -->
    <div class="chat-window">
        <div class="chat-header">
            <div class="chat-user-info">
                <div class="chat-user-avatar">
                    <?php echo substr($otherUserName, 0, 1); ?>
                </div>
                <div class="chat-user-details">
                    <h3><?php echo htmlspecialchars($otherUserName); ?></h3>
                    <div class="chat-user-status">Online</div>
                </div>
            </div>
            <div class="chat-actions">
                <button class="chat-action-btn" title="Αναζήτηση">
                    <i class="fas fa-search"></i>
                </button>
                <button class="chat-action-btn" onclick="showUserActions()" title="Περισσότερα">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
            </div>
        </div>
        
        <div class="messages-area" id="messagesArea">
            <?php if (empty($messages)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">💬</div>
                    <div class="empty-state-text">Ξεκινήστε τη συνομιλία</div>
                    <div class="empty-state-subtext">Στείλτε το πρώτο μήνυμα</div>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $message): ?>
                    <div class="message <?php echo $message['sender_id'] == Session::get('user_id') ? 'sent' : 'received'; ?>">
                        <div class="message-content">
                            <div class="message-bubble" oncontextmenu="showMessageMenu(event, <?php echo $message['id']; ?>)">
                                <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                            </div>
                            <div class="message-time">
                                <?php echo date('H:i', strtotime($message['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="message-input-area">
            <form id="messageForm" onsubmit="sendMessage(event)">
                <div class="message-input-container">
                    <div class="message-input-wrapper">
                        <textarea 
                            class="message-input" 
                            id="messageInput"
                            placeholder="Γράψτε ένα μήνυμα..."
                            rows="1"
                            required
                        ></textarea>
                        <button type="button" class="attachment-btn" onclick="selectFile()">
                            <i class="fas fa-paperclip"></i>
                        </button>
                        <input type="file" id="fileInput" style="display: none;" onchange="handleFileSelect(event)">
                    </div>
                    <button type="submit" class="send-btn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </form>
            <div id="attachmentPreview"></div>
        </div>
    </div>
</div>

<!-- User Actions Modal -->
<div id="userActionsModal" class="user-actions-modal" style="display: none;" onclick="closeModal(event)">
    <div class="user-actions-content">
        <div class="user-actions-header">Ενέργειες</div>
        <div class="user-action-item" onclick="muteConversation()">
            <i class="fas fa-bell-slash"></i> Σίγαση ειδοποιήσεων
        </div>
        <div class="user-action-item" onclick="searchInConversation()">
            <i class="fas fa-search"></i> Αναζήτηση στη συνομιλία
        </div>
        <div class="user-action-item danger" onclick="blockUser(<?php echo $otherUserId; ?>)">
            <i class="fas fa-ban"></i> Αποκλεισμός χρήστη
        </div>
        <div class="user-action-item danger" onclick="reportUser(<?php echo $otherUserId; ?>)">
            <i class="fas fa-flag"></i> Αναφορά χρήστη
        </div>
    </div>
</div>

<!-- Message Context Menu -->
<div id="messageContextMenu" class="message-context-menu" style="display: none;">
    <div class="context-menu-item" onclick="copyMessage()">
        <i class="fas fa-copy"></i> Αντιγραφή
    </div>
    <div class="context-menu-item" onclick="replyToMessage()">
        <i class="fas fa-reply"></i> Απάντηση
    </div>
    <div class="context-menu-item" onclick="forwardMessage()">
        <i class="fas fa-share"></i> Προώθηση
    </div>
    <div class="context-menu-item danger" onclick="deleteMessage()">
        <i class="fas fa-trash"></i> Διαγραφή
    </div>
</div>

<script>
// Auto-resize textarea
const messageInput = document.getElementById('messageInput');
messageInput.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = this.scrollHeight + 'px';
});

// Send message
function sendMessage(e) {
    e.preventDefault();
    const message = messageInput.value.trim();
    if (!message) return;
    
    // AJAX call to send message
    fetch('<?php echo BASE_URL; ?>api/messages/send', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            conversation_id: <?php echo $conversationId; ?>,
            receiver_id: <?php echo $otherUserId; ?>,
            message: message
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add message to UI
            addMessageToUI(data.message, 'sent');
            messageInput.value = '';
            messageInput.style.height = 'auto';
            scrollToBottom();
        }
    });
}

// Add message to UI
function addMessageToUI(message, type) {
    const messagesArea = document.getElementById('messagesArea');
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}`;
    messageDiv.innerHTML = `
        <div class="message-content">
            <div class="message-bubble">
                ${message.text}
            </div>
            <div class="message-time">
                ${new Date().toLocaleTimeString('el-GR', {hour: '2-digit', minute: '2-digit'})}
            </div>
        </div>
    `;
    messagesArea.appendChild(messageDiv);
}

// Scroll to bottom
function scrollToBottom() {
    const messagesArea = document.getElementById('messagesArea');
    messagesArea.scrollTop = messagesArea.scrollHeight;
}

// User actions
function showUserActions() {
    document.getElementById('userActionsModal').style.display = 'flex';
}

function closeModal(e) {
    if (e.target.classList.contains('user-actions-modal')) {
        e.target.style.display = 'none';
    }
}

function blockUser(userId) {
    if (confirm('Είστε σίγουροι ότι θέλετε να αποκλείσετε αυτόν τον χρήστη;')) {
        // Implement block user
        console.log('Block user:', userId);
    }
}

// File handling
function selectFile() {
    document.getElementById('fileInput').click();
}

function handleFileSelect(e) {
    const file = e.target.files[0];
    if (file) {
        // Show preview
        const preview = document.getElementById('attachmentPreview');
        preview.innerHTML = `
            <div class="attachment-preview">
                <div class="attachment-icon">
                    <i class="fas fa-file"></i>
                </div>
                <div class="attachment-details">
                    <div class="attachment-name">${file.name}</div>
                    <div class="attachment-size">${(file.size / 1024).toFixed(2)} KB</div>
                </div>
                <button class="remove-attachment" onclick="removeAttachment()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
    }
}

function removeAttachment() {
    document.getElementById('fileInput').value = '';
    document.getElementById('attachmentPreview').innerHTML = '';
}

// Initialize
scrollToBottom();
</script>

<?php include ROOT_DIR . '/src/Views/partials/footer.php'; ?>
