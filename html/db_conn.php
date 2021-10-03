<?php
$servername = "172.19.0.2";
$username = "mysql_user";
$password = "1234ABC";
$dbname = "toolingdb";


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}



?>
