<?php
echo "Requested URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "Base URL: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "<br>";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "<br>";
