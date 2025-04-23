<?php
require_once 'config/db.php';

$result = $conn->query("SHOW CREATE TABLE categories");
if ($result) {
    $row = $result->fetch_assoc();
    var_dump($row);
} else {
    echo "Error: " . $conn->error;
} 