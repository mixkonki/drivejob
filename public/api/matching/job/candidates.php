<?php
// API endpoint for job candidates
require_once '../../../../src/bootstrap.php';

// Start session to access authentication
session_start();

// Include the controller
$controller = new \Drivejob\Controllers\Api\MatchingController();

// Call the method
$controller->getJobCandidates();
