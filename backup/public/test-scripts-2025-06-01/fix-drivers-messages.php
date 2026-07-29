<?php
require_once __DIR__ . '/../src/bootstrap.php';

echo "<h1>Διόρθωση Drivers Messages</h1>";

// Read the file
$filePath = __DIR__ . '/drivers/messages.php';
$content = file_get_contents($filePath);

// Replace content with message
$content = str_replace('SELECT content FROM messages', 'SELECT message FROM messages', $content);

// Write back
file_put_contents($filePath, $content);

echo "<p>✅ Διορθώθηκε το field name από 'content' σε 'message'</p>";

// Also check drivers/conversation.php if it exists
$conversationPath = __DIR__ . '/drivers/conversation.php';
if (file_exists($conversationPath)) {
    $convContent = file_get_contents($conversationPath);
    if (strpos($convContent, 'content') !== false) {
        $convContent = str_replace(
            ["m.content", "content FROM messages", "'content'"],
            ["m.message", "message FROM messages", "'message'"],
            $convContent
        );
        file_put_contents($conversationPath, $convContent);
        echo "<p>✅ Διορθώθηκε και το drivers/conversation.php</p>";
    }
}

echo "<p><a href='" . BASE_URL . "drivers/messages'>Test Drivers Messages</a></p>";
