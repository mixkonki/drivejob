<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Session;

// Start session and simulate company login
Session::start();
$_SESSION['user_id'] = 4; // Thessdrive IKE
$_SESSION['user_role'] = 'company';
$_SESSION['user_name'] = 'Thessdrive IKE';

?>
<!DOCTYPE html>
<html lang="el">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Messaging with Email</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h2>Test Messaging System with Email</h2>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Αποστολή Δοκιμαστικού Μηνύματος</h5>

                <form id="testForm">
                    <div class="mb-3">
                        <label class="form-label">Driver ID:</label>
                        <input type="number" class="form-control" id="driverId" value="30" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Job ID:</label>
                        <input type="number" class="form-control" id="jobId" value="18" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Θέμα:</label>
                        <input type="text" class="form-control" id="subject" value="Δοκιμαστικό μήνυμα" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Μήνυμα:</label>
                        <textarea class="form-control" id="message" rows="4" required>Αυτό είναι ένα δοκιμαστικό μήνυμα για να ελέγξουμε το σύστημα messaging με email integration.</textarea>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sendEmail" checked>
                            <label class="form-check-label" for="sendEmail">
                                Αποστολή email notification
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Αποστολή Μηνύματος</button>
                </form>

                <div id="result" class="mt-3"></div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <h5 class="card-title">Πληροφορίες</h5>
                <p><strong>Logged in as:</strong> Company ID 4 (Thessdrive IKE)</p>
                <p><strong>API Endpoint:</strong> /api/messaging/send.php</p>
                <p><strong>Email Config:</strong> smtp.thessdrive.gr:587</p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('testForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<div class="alert alert-info">Αποστολή μηνύματος...</div>';

            const data = {
                driver_id: parseInt(document.getElementById('driverId').value),
                job_id: parseInt(document.getElementById('jobId').value),
                subject: document.getElementById('subject').value,
                message: document.getElementById('message').value,
                send_email: document.getElementById('sendEmail').checked
            };

            try {
                const response = await fetch('/drivejob/public/api/messaging/send.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(data)
                });

                const responseText = await response.text();
                console.log('Raw response:', responseText);

                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (e) {
                    throw new Error('Invalid JSON response: ' + responseText);
                }

                if (result.success) {
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h6>Επιτυχία!</h6>
                            <p>${result.message}</p>
                            <p>Conversation ID: ${result.conversation_id}</p>
                            <p>Email sent: ${result.email_sent ? 'Yes' : 'No'}</p>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <h6>Σφάλμα</h6>
                            <p>${result.message || result.error}</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error:', error);
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h6>Σφάλμα</h6>
                        <p>${error.message}</p>
                    </div>
                `;
            }
        });
    </script>
</body>

</html>