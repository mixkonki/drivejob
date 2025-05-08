<?php
require_once 'claude-helper.php';

// Δημιουργία αντικειμένου του βοηθού Claude
$claude = new ClaudeHelper();

// Δοκιμαστική ερώτηση
$question = "Πώς μπορώ να βελτιστοποιήσω τα δρομολόγια για έναν στόλο φορτηγών 10 οχημάτων που εξυπηρετούν 50 διαφορετικά σημεία παράδοσης σε αστική περιοχή;";

// Αποστολή ερώτησης και λήψη απάντησης
$answer = $claude->askClaude($question);

// Εμφάνιση της απάντησης
header('Content-Type: text/html; charset=utf-8');
echo "<h1>Δοκιμή Claude API</h1>";
echo "<h2>Ερώτηση:</h2>";
echo "<p>{$question}</p>";
echo "<h2>Απάντηση:</h2>";
echo "<p>{$answer}</p>";
?>
