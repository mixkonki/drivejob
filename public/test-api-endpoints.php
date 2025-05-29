<?php

/**
 * Test API Endpoints
 */

require_once '../src/bootstrap.php';

use Drivejob\Core\Session;

// Start session
Session::start();

// Set test driver ID
Session::set('user_id', 26);
Session::set('user_role', 'driver');
Session::set('user_type', 'drivers');

?>
<!DOCTYPE html>
<html lang="el">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test API Endpoints</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .endpoint-test {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .response-box {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            max-height: 300px;
            overflow-y: auto;
        }

        .success {
            color: #28a745;
        }

        .error {
            color: #dc3545;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h1>API Endpoints Test</h1>

        <div class="endpoint-test">
            <h3>1. Driver Matches API</h3>
            <p>Endpoint: <code><?php echo BASE_URL; ?>api/matching/driver/matches?limit=5</code></p>
            <button class="btn btn-primary" onclick="testDriverMatches()">Test</button>
            <div id="driver-matches-response" class="response-box" style="display:none;"></div>
        </div>

        <div class="endpoint-test">
            <h3>2. Job Candidates API</h3>
            <p>Endpoint: <code><?php echo BASE_URL; ?>api/matching/job/candidates?job_id=2&limit=10</code></p>
            <button class="btn btn-primary" onclick="testJobCandidates()">Test</button>
            <div id="job-candidates-response" class="response-box" style="display:none;"></div>
        </div>

        <div class="endpoint-test">
            <h3>3. Calculate Match API</h3>
            <p>Endpoint: <code><?php echo BASE_URL; ?>api/matching/calculate?driver_id=26&job_id=2</code></p>
            <button class="btn btn-primary" onclick="testCalculateMatch()">Test</button>
            <div id="calculate-match-response" class="response-box" style="display:none;"></div>
        </div>

        <div class="endpoint-test">
            <h3>4. Match Insights API</h3>
            <p>Endpoint: <code><?php echo BASE_URL; ?>api/matching/insights?driver_id=26&job_id=2</code></p>
            <button class="btn btn-primary" onclick="testMatchInsights()">Test</button>
            <div id="match-insights-response" class="response-box" style="display:none;"></div>
        </div>

        <div class="endpoint-test">
            <h3>5. Company Jobs API</h3>
            <p>Endpoint: <code><?php echo BASE_URL; ?>api/company/jobs</code></p>
            <button class="btn btn-primary" onclick="testCompanyJobs()">Test</button>
            <div id="company-jobs-response" class="response-box" style="display:none;"></div>
        </div>
    </div>

    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';

        function testDriverMatches() {
            const responseDiv = document.getElementById('driver-matches-response');
            responseDiv.style.display = 'block';
            responseDiv.innerHTML = 'Loading...';

            fetch(`${BASE_URL}api/matching/driver/matches?limit=5`)
                .then(response => response.json())
                .then(data => {
                    responseDiv.innerHTML = `<pre class="${data.success ? 'success' : 'error'}">${JSON.stringify(data, null, 2)}</pre>`;
                })
                .catch(error => {
                    responseDiv.innerHTML = `<pre class="error">Error: ${error.message}</pre>`;
                });
        }

        function testJobCandidates() {
            const responseDiv = document.getElementById('job-candidates-response');
            responseDiv.style.display = 'block';
            responseDiv.innerHTML = 'Loading...';

            fetch(`${BASE_URL}api/matching/job/candidates?job_id=2&limit=10`)
                .then(response => response.json())
                .then(data => {
                    responseDiv.innerHTML = `<pre class="${data.success ? 'success' : 'error'}">${JSON.stringify(data, null, 2)}</pre>`;
                })
                .catch(error => {
                    responseDiv.innerHTML = `<pre class="error">Error: ${error.message}</pre>`;
                });
        }

        function testCalculateMatch() {
            const responseDiv = document.getElementById('calculate-match-response');
            responseDiv.style.display = 'block';
            responseDiv.innerHTML = 'Loading...';

            fetch(`${BASE_URL}api/matching/calculate?driver_id=26&job_id=2`)
                .then(response => response.json())
                .then(data => {
                    responseDiv.innerHTML = `<pre class="${data.success ? 'success' : 'error'}">${JSON.stringify(data, null, 2)}</pre>`;
                })
                .catch(error => {
                    responseDiv.innerHTML = `<pre class="error">Error: ${error.message}</pre>`;
                });
        }

        function testMatchInsights() {
            const responseDiv = document.getElementById('match-insights-response');
            responseDiv.style.display = 'block';
            responseDiv.innerHTML = 'Loading...';

            fetch(`${BASE_URL}api/matching/insights?driver_id=26&job_id=2`)
                .then(response => response.json())
                .then(data => {
                    responseDiv.innerHTML = `<pre class="${data.success ? 'success' : 'error'}">${JSON.stringify(data, null, 2)}</pre>`;
                })
                .catch(error => {
                    responseDiv.innerHTML = `<pre class="error">Error: ${error.message}</pre>`;
                });
        }

        function testCompanyJobs() {
            const responseDiv = document.getElementById('company-jobs-response');
            responseDiv.style.display = 'block';
            responseDiv.innerHTML = 'Loading...';

            // First, let's set company session
            fetch(`${BASE_URL}api/company/jobs`)
                .then(response => response.json())
                .then(data => {
                    responseDiv.innerHTML = `<pre class="${data.success ? 'success' : 'error'}">${JSON.stringify(data, null, 2)}</pre>`;
                })
                .catch(error => {
                    responseDiv.innerHTML = `<pre class="error">Error: ${error.message}</pre>`;
                });
        }
    </script>
</body>

</html>