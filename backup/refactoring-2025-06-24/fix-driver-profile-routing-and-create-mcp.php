<?php
echo "<h1>Διόρθωση Driver Profile Routing & Δημιουργία MCP Servers</h1>";

// 1. Update .htaccess for public driver profiles
echo "<h2>1. Ενημέρωση .htaccess για Public Driver Profiles</h2>";

$htaccessContent = '<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Handle various registration URLs
    RewriteRule ^registration/?$ driver-registration.php [L]
    RewriteRule ^register/?$ driver-registration.php [L]
    RewriteRule ^drivers-registration/?$ driver-registration.php [L]
    
    # Handle profile routes
    RewriteRule ^profile/?$ driver-profile.php [L]
    RewriteRule ^profile/([0-9]+)/?$ public-profile.php?id=$1 [L,QSA]
    
    # Handle other routes
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ $1.php [L]
</IfModule>';

file_put_contents(__DIR__ . '/drivers/.htaccess', $htaccessContent);
echo "<p>✅ Ενημερώθηκε το drivers/.htaccess με rule για public profiles</p>";

// 2. Create public-profile.php
echo "<h2>2. Δημιουργία Public Driver Profile</h2>";

$publicProfileContent = '<?php
require_once __DIR__ . \'/../../src/bootstrap.php\';

use Drivejob\Core\Database;

// Get driver ID from URL
$driverId = $_GET[\'id\'] ?? null;

if (!$driverId || !is_numeric($driverId)) {
    header(\'HTTP/1.0 404 Not Found\');
    include ROOT_DIR . \'/src/Views/errors/404.php\';
    exit();
}

$pdo = Database::getInstance()->getConnection();

// Get driver details with user info
$stmt = $pdo->prepare("
    SELECT 
        d.*,
        u.email,
        u.created_at as member_since,
        u.is_verified,
        u.is_active
    FROM drivers d
    JOIN users u ON d.user_id = u.id
    WHERE d.id = ? AND u.is_active = 1
");
$stmt->execute([$driverId]);
$driver = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$driver) {
    header(\'HTTP/1.0 404 Not Found\');
    include ROOT_DIR . \'/src/Views/errors/404.php\';
    exit();
}

// Get driver\'s ratings
$stmt = $pdo->prepare("
    SELECT 
        AVG(overall_rating) as avg_rating,
        COUNT(*) as total_reviews,
        AVG(punctuality_rating) as avg_punctuality,
        AVG(communication_rating) as avg_communication,
        AVG(professionalism_rating) as avg_professionalism,
        AVG(vehicle_condition_rating) as avg_vehicle_condition
    FROM company_reviews
    WHERE driver_id = ?
");
$stmt->execute([$driverId]);
$ratings = $stmt->fetch(PDO::FETCH_ASSOC);

// Get driver\'s experience
$stmt = $pdo->prepare("
    SELECT * FROM driver_vehicle_experience
    WHERE driver_id = ?
    ORDER BY years_experience DESC
");
$stmt->execute([$driverId]);
$experience = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get driver\'s certifications
$stmt = $pdo->prepare("
    SELECT * FROM driver_certifications
    WHERE driver_id = ? AND expiry_date > NOW()
    ORDER BY issue_date DESC
");
$stmt->execute([$driverId]);
$certifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get driver\'s skills
$skills = !empty($driver[\'skills\']) ? json_decode($driver[\'skills\'], true) : [];

$pageTitle = $driver[\'first_name\'] . \' \' . $driver[\'last_name\'] . \' - Προφίλ Οδηγού\';

include ROOT_DIR . \'/src/Views/partials/header.php\';
?>

<style>
.driver-public-profile {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.profile-header {
    background: white;
    border-radius: 10px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.profile-image {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 5px solid #f0f0f0;
}

.profile-info h1 {
    margin-bottom: 10px;
}

.profile-badges {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.badge-verified {
    background: #28a745;
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 14px;
}

.rating-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.rating-stars {
    color: #ffc107;
}

.info-section {
    background: white;
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.skill-tag {
    display: inline-block;
    background: #e9ecef;
    padding: 5px 15px;
    border-radius: 20px;
    margin: 5px;
    font-size: 14px;
}

.experience-item {
    border-left: 3px solid #007bff;
    padding-left: 20px;
    margin-bottom: 20px;
}

.certification-item {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.contact-section {
    background: #007bff;
    color: white;
    padding: 30px;
    border-radius: 10px;
    text-align: center;
}
</style>

<div class="driver-public-profile">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="row align-items-center">
            <div class="col-md-3 text-center">
                <?php if ($driver[\'profile_image\']): ?>
                    <img src="<?php echo BASE_URL . $driver[\'profile_image\']; ?>" 
                         alt="<?php echo htmlspecialchars($driver[\'first_name\'] . \' \' . $driver[\'last_name\']); ?>"
                         class="profile-image">
                <?php else: ?>
                    <div class="profile-image bg-secondary d-flex align-items-center justify-content-center">
                        <i class="fas fa-user fa-4x text-white"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-9 profile-info">
                <h1><?php echo htmlspecialchars($driver[\'first_name\'] . \' \' . $driver[\'last_name\']); ?></h1>
                <p class="text-muted mb-2">
                    <i class="fas fa-map-marker-alt"></i> 
                    <?php echo htmlspecialchars($driver[\'city\'] . \', \' . $driver[\'region\']); ?>
                </p>
                <p class="text-muted">
                    <i class="fas fa-calendar-alt"></i> 
                    Μέλος από <?php echo date(\'F Y\', strtotime($driver[\'member_since\'])); ?>
                </p>
                <div class="profile-badges">
                    <?php if ($driver[\'is_verified\']): ?>
                        <span class="badge-verified">
                            <i class="fas fa-check-circle"></i> Επαληθευμένος
                        </span>
                    <?php endif; ?>
                    <?php if ($driver[\'available_for_hire\']): ?>
                        <span class="badge-verified" style="background: #17a2b8;">
                            <i class="fas fa-briefcase"></i> Διαθέσιμος
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Ratings Section -->
    <?php if ($ratings[\'total_reviews\'] > 0): ?>
    <div class="rating-section">
        <h3><i class="fas fa-star"></i> Αξιολογήσεις</h3>
        <div class="row mt-3">
            <div class="col-md-4 text-center">
                <h2 class="rating-stars">
                    <?php 
                    $avgRating = round($ratings[\'avg_rating\'], 1);
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $avgRating) {
                            echo \'<i class="fas fa-star"></i>\';
                        } elseif ($i - 0.5 <= $avgRating) {
                            echo \'<i class="fas fa-star-half-alt"></i>\';
                        } else {
                            echo \'<i class="far fa-star"></i>\';
                        }
                    }
                    ?>
                </h2>
                <p><?php echo $avgRating; ?>/5 από <?php echo $ratings[\'total_reviews\']; ?> αξιολογήσεις</p>
            </div>
            <div class="col-md-8">
                <div class="mb-2">
                    <strong>Συνέπεια:</strong> <?php echo round($ratings[\'avg_punctuality\'], 1); ?>/5
                </div>
                <div class="mb-2">
                    <strong>Επικοινωνία:</strong> <?php echo round($ratings[\'avg_communication\'], 1); ?>/5
                </div>
                <div class="mb-2">
                    <strong>Επαγγελματισμός:</strong> <?php echo round($ratings[\'avg_professionalism\'], 1); ?>/5
                </div>
                <div class="mb-2">
                    <strong>Κατάσταση Οχήματος:</strong> <?php echo round($ratings[\'avg_vehicle_condition\'], 1); ?>/5
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- About Section -->
    <?php if (!empty($driver[\'bio\'])): ?>
    <div class="info-section">
        <h3><i class="fas fa-user"></i> Σχετικά με εμένα</h3>
        <p><?php echo nl2br(htmlspecialchars($driver[\'bio\'])); ?></p>
    </div>
    <?php endif; ?>

    <!-- Skills Section -->
    <?php if (!empty($skills)): ?>
    <div class="info-section">
        <h3><i class="fas fa-tools"></i> Δεξιότητες</h3>
        <div class="mt-3">
            <?php foreach ($skills as $skill): ?>
                <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Experience Section -->
    <?php if (!empty($experience)): ?>
    <div class="info-section">
        <h3><i class="fas fa-truck"></i> Εμπειρία Οδήγησης</h3>
        <div class="mt-3">
            <?php foreach ($experience as $exp): ?>
                <div class="experience-item">
                    <h5><?php echo htmlspecialchars($exp[\'vehicle_type\']); ?></h5>
                    <p class="mb-1">
                        <strong><?php echo $exp[\'years_experience\']; ?> χρόνια εμπειρίας</strong>
                    </p>
                    <?php if (!empty($exp[\'specific_experience\'])): ?>
                        <p class="text-muted"><?php echo htmlspecialchars($exp[\'specific_experience\']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Certifications Section -->
    <?php if (!empty($certifications)): ?>
    <div class="info-section">
        <h3><i class="fas fa-certificate"></i> Πιστοποιήσεις</h3>
        <div class="mt-3">
            <?php foreach ($certifications as $cert): ?>
                <div class="certification-item">
                    <h5><?php echo htmlspecialchars($cert[\'certification_name\']); ?></h5>
                    <p class="mb-1">
                        <i class="fas fa-building"></i> <?php echo htmlspecialchars($cert[\'issuing_authority\']); ?>
                    </p>
                    <p class="text-muted mb-0">
                        <i class="fas fa-calendar"></i> 
                        Έκδοση: <?php echo date(\'d/m/Y\', strtotime($cert[\'issue_date\'])); ?> - 
                        Λήξη: <?php echo date(\'d/m/Y\', strtotime($cert[\'expiry_date\'])); ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Contact Section -->
    <div class="contact-section">
        <h3>Ενδιαφέρεστε να συνεργαστείτε;</h3>
        <p>Επικοινωνήστε μαζί μου μέσω της πλατφόρμας DriveJob</p>
        <?php if (isset($_SESSION[\'user_id\']) && $_SESSION[\'user_role\'] === \'company\'): ?>
            <a href="<?php echo BASE_URL; ?>companies/send-message?driver_id=<?php echo $driverId; ?>" 
               class="btn btn-light btn-lg mt-3">
                <i class="fas fa-envelope"></i> Στείλτε Μήνυμα
            </a>
        <?php else: ?>
            <p class="mt-3">
                <a href="<?php echo BASE_URL; ?>login.php" class="text-white">
                    Συνδεθείτε ως επιχείρηση για να επικοινωνήσετε
                </a>
            </p>
        <?php endif; ?>
    </div>
</div>

<?php include ROOT_DIR . \'/src/Views/partials/footer.php\'; ?>
';

file_put_contents(__DIR__ . '/drivers/public-profile.php', $publicProfileContent);
echo "<p>✅ Δημιουργήθηκε το public-profile.php</p>";

// 3. Create MCP Servers directory structure
echo "<h2>3. Δημιουργία MCP Servers</h2>";

$mcpBaseDir = 'C:/Users/079/Documents/Cline/MCP/drivejob-mcp-servers';
if (!is_dir($mcpBaseDir)) {
    mkdir($mcpBaseDir, 0755, true);
}

// 3.1 Create messaging-server
echo "<h3>3.1 Messaging Server</h3>";
$messagingServerDir = $mcpBaseDir . '/messaging-server';
if (!is_dir($messagingServerDir)) {
    mkdir($messagingServerDir, 0755, true);
}

// package.json for messaging-server
$packageJson = '{
  "name": "drivejob-messaging-server",
  "version": "1.0.0",
  "description": "MCP server for DriveJob messaging system",
  "main": "index.js",
  "type": "module",
  "scripts": {
    "start": "node index.js"
  },
  "dependencies": {
    "@modelcontextprotocol/sdk": "^0.5.0",
    "mysql2": "^3.6.0",
    "ws": "^8.14.0"
  }
}';
file_put_contents($messagingServerDir . '/package.json', $packageJson);

// index.js for messaging-server
$messagingServerIndex = 'import { Server } from "@modelcontextprotocol/sdk/server/index.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import mysql from "mysql2/promise";
import WebSocket from "ws";

// Database configuration
const dbConfig = {
  host: "localhost",
  user: "root",
  password: "",
  database: "drivejobs"
};

// WebSocket server for real-time messaging
const wss = new WebSocket.Server({ port: 8080 });
const clients = new Map();

wss.on("connection", (ws, req) => {
  const userId = req.url.split("/").pop();
  clients.set(userId, ws);
  
  ws.on("close", () => {
    clients.delete(userId);
  });
});

// MCP Server
const server = new Server(
  {
    name: "drivejob-messaging-server",
    version: "1.0.0",
  },
  {
    capabilities: {
      tools: {},
    },
  }
);

// Tools
server.setRequestHandler("tools/list", async () => {
  return {
    tools: [
      {
        name: "sendMessage",
        description: "Στείλε μήνυμα σε συνομιλία",
        inputSchema: {
          type: "object",
          properties: {
            conversationId: { type: "number" },
            senderId: { type: "number" },
            senderType: { type: "string", enum: ["driver", "company"] },
            message: { type: "string" }
          },
          required: ["conversationId", "senderId", "senderType", "message"]
        }
      },
      {
        name: "getConversations",
        description: "Λήψη συνομιλιών χρήστη",
        inputSchema: {
          type: "object",
          properties: {
            userId: { type: "number" },
            userType: { type: "string", enum: ["driver", "company"] }
          },
          required: ["userId", "userType"]
        }
      },
      {
        name: "blockUser",
        description: "Μπλοκάρισμα χρήστη",
        inputSchema: {
          type: "object",
          properties: {
            blockerId: { type: "number" },
            blockedId: { type: "number" },
            blockerType: { type: "string", enum: ["driver", "company"] }
          },
          required: ["blockerId", "blockedId", "blockerType"]
        }
      }
    ]
  };
});

// Handle tool calls
server.setRequestHandler("tools/call", async (request) => {
  const { name, arguments: args } = request.params;
  const connection = await mysql.createConnection(dbConfig);
  
  try {
    switch (name) {
      case "sendMessage": {
        const { conversationId, senderId, senderType, message } = args;
        
        // Get receiver info
        const [conversation] = await connection.execute(
          "SELECT driver_id, company_id FROM conversations WHERE id = ?",
          [conversationId]
        );
        
        const receiverId = senderType === "driver" 
          ? conversation[0].company_id 
          : conversation[0].driver_id;
        
        // Insert message
        const [result] = await connection.execute(
          `INSERT INTO messages (conversation_id, sender_id, sender_type, message, created_at) 
           VALUES (?, ?, ?, ?, NOW())`,
          [conversationId, senderId, senderType, message]
        );
        
        // Update conversation
        await connection.execute(
          "UPDATE conversations SET updated_at = NOW() WHERE id = ?",
          [conversationId]
        );
        
        // Send via WebSocket if receiver is online
        const receiverWs = clients.get(receiverId.toString());
        if (receiverWs) {
          receiverWs.send(JSON.stringify({
            type: "new_message",
            conversationId,
            message,
            senderId,
            senderType,
            timestamp: new Date()
          }));
        }
        
        return {
          content: [{
            type: "text",
            text: `Message sent successfully. ID: ${result.insertId}`
          }]
        };
      }
      
      case "getConversations": {
        const { userId, userType } = args;
        const field = userType === "driver" ? "driver_id" : "company_id";
        
        const [conversations] = await connection.execute(
          `SELECT c.*, 
           (SELECT COUNT(*) FROM messages m 
            WHERE m.conversation_id = c.id 
            AND m.is_read = 0 
            AND m.sender_type != ?) as unread_count
           FROM conversations c 
           WHERE c.${field} = ? 
           ORDER BY c.updated_at DESC`,
          [userType, userId]
        );
        
        return {
          content: [{
            type: "text",
            text: JSON.stringify(conversations, null, 2)
          }]
        };
      }
      
      case "blockUser": {
        const { blockerId, blockedId, blockerType } = args;
        
        await connection.execute(
          `INSERT INTO user_blocks (blocker_id, blocked_id, blocker_type, created_at) 
           VALUES (?, ?, ?, NOW())`,
          [blockerId, blockedId, blockerType]
        );
        
        return {
          content: [{
            type: "text",
            text: "User blocked successfully"
          }]
        };
      }
      
      default:
        throw new Error(`Unknown tool: ${name}`);
    }
  } finally {
    await connection.end();
  }
});

// Start server
const transport = new StdioServerTransport();
await server.connect(transport);
console.error("DriveJob Messaging MCP Server running on stdio");
';
file_put_contents($messagingServerDir . '/index.js', $messagingServerIndex);
echo "<p>✅ Δημιουργήθηκε messaging-server</p>";

// 3.2 Create notification-server
echo "<h3>3.2 Notification Server</h3>";
$notificationServerDir = $mcpBaseDir . '/notification-server';
if (!is_dir($notificationServerDir)) {
    mkdir($notificationServerDir, 0755, true);
}

// package.json for notification-server
$notifPackageJson = '{
  "name": "drivejob-notification-server",
  "version": "1.0.0",
  "description": "MCP server for DriveJob notifications",
  "main": "index.js",
  "type": "module",
  "scripts": {
    "start": "node index.js"
  },
  "dependencies": {
    "@modelcontextprotocol/sdk": "^0.5.0",
    "nodemailer": "^6.9.0",
    "twilio": "^4.19.0"
  }
}';
file_put_contents($notificationServerDir . '/package.json', $notifPackageJson);

// index.js for notification-server
$notificationServerIndex = 'import { Server } from "@modelcontextprotocol/sdk/server/index.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import nodemailer from "nodemailer";
import twilio from "twilio";

// Email configuration
const emailTransporter = nodemailer.createTransport({
  host: "smtp.gmail.com",
  port: 587,
  secure: false,
  auth: {
    user: process.env.EMAIL_USER,
    pass: process.env.EMAIL_PASS
  }
});

// Twilio configuration (optional)
const twilioClient = process.env.TWILIO_SID && process.env.TWILIO_TOKEN
  ? twilio(process.env.TWILIO_SID, process.env.TWILIO_TOKEN)
  : null;

// MCP Server
const server = new Server(
  {
    name: "drivejob-notification-server",
    version: "1.0.0",
  },
  {
    capabilities: {
      tools: {},
    },
  }
);

// Tools
server.setRequestHandler("tools/list", async () => {
  return {
    tools: [
      {
        name: "sendEmail",
        description: "Στείλε email ειδοποίηση",
        inputSchema: {
          type: "object",
          properties: {
            to: { type: "string" },
            subject: { type: "string" },
            html: { type: "string" },
            text: { type: "string" }
          },
          required: ["to", "subject"]
        }
      },
      {
        name: "sendSMS",
        description: "Στείλε SMS ειδοποίηση",
        inputSchema: {
          type: "object",
          properties: {
            to: { type: "string" },
            message: { type: "string" }
          },
          required: ["to", "message"]
        }
      },
      {
        name: "sendPushNotification",
        description: "Στείλε push notification",
        inputSchema: {
          type: "object",
          properties: {
            userId: { type: "number" },
            title: { type: "string" },
            body: { type: "string" },
            data: { type: "object" }
          },
          required: ["userId", "title", "body"]
        }
      }
    ]
  };
});

// Handle tool calls
server.setRequestHandler("tools/call", async (request) => {
  const { name, arguments: args } = request.params;
  
  switch (name) {
    case "sendEmail": {
      const { to, subject, html, text } = args;
      
      const info = await emailTransporter.sendMail({
        from: "DriveJob <noreply@drivejob.gr>",
        to,
        subject,
        text: text || "DriveJob Notification",
        html: html || text
      });
      
      return {
        content: [{
          type: "text",
          text: `Email sent: ${info.messageId}`
        }]
      };
    }
    
    case "sendSMS": {
      if (!twilioClient) {
        throw new Error("SMS service not configured");
      }
      
      const { to, message } = args;
      
      const result = await twilioClient.messages.create({
        body: message,
        from: process.env.TWILIO_PHONE,
        to
      });
      
      return {
        content: [{
          type: "text",
          text: `SMS sent: ${result.sid}`
        }]
      };
    }
    
    case "sendPushNotification": {
      // This would integrate with Firebase or similar
      // For now, just log it
      const { userId, title, body, data } = args;
      
      console.log("Push notification:", { userId, title, body, data });
      
      return {
        content: [{
          type: "text",
          text: "Push notification queued"
        }]
      };
    }
    
    default:
      throw new Error(`Unknown tool: ${name}`);
  }
});

// Start server
const transport = new StdioServerTransport();
await server.connect(transport);
console.error("DriveJob Notification MCP Server running on stdio");
';
file_put_contents($notificationServerDir . '/index.js', $notificationServerIndex);
echo "<p>✅ Δημιουργήθηκε notification-server</p>";

// 3.3 Create file-upload-server
echo "<h3>3.3 File Upload Server</h3>";
$fileUploadServerDir = $mcpBaseDir . '/file-upload-server';
if (!is_dir($fileUploadServerDir)) {
    mkdir($fileUploadServerDir, 0755, true);
}

// package.json for file-upload-server
$filePackageJson = '{
  "name": "drivejob-file-upload-server",
  "version": "1.0.0",
  "description": "MCP server for DriveJob file handling",
  "main": "index.js",
  "type": "module",
  "scripts": {
    "start": "node index.js"
  },
  "dependencies": {
    "@modelcontextprotocol/sdk": "^0.5.0",
    "multer": "^1.4.5-lts.1",
    "sharp": "^0.33.0",
    "file-type": "^18.7.0"
  }
}';
file_put_contents($fileUploadServerDir . '/package.json', $filePackageJson);

// index.js for file-upload-server
$fileUploadServerIndex = 'import { Server } from "@modelcontextprotocol/sdk/server/index.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import sharp from "sharp";
import { fileTypeFromBuffer } from "file-type";
import fs from "fs/promises";
import path from "path";
import crypto from "crypto";

const UPLOAD_DIR = "C:/wamp64/www/drivejob/public/uploads";
const ALLOWED_TYPES = ["image/jpeg", "image/png", "image/gif", "application/pdf"];
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

// MCP Server
const server = new Server(
  {
    name: "drivejob-file-upload-server",
    version: "1.0.0
