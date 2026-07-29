<?php
session_start();
?>
<!DOCTYPE html>
<html>

<head>
    <title>Session Debug</title>
</head>

<body>
    <h1>Session Debug</h1>
    <pre>
<?php
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session Data:\n";
print_r($_SESSION);
?>
    </pre>
</body>

</html>