<?php
// public/index.php

// Αρχικοποίηση της εφαρμογής
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\FrontController;
use Drivejob\Core\CSRFMiddleware;

// Εκτέλεση των middleware
CSRFMiddleware::handle();

// Αρχικοποίηση του FrontController
FrontController::initialize();

// Δρομολόγηση της αίτησης
FrontController::dispatch();