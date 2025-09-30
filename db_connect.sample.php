<?php

define('DB_SERVER', 'SERVER_IP_ADDRESS');
define('DB_USERNAME', 'USERNAME');
define('DB_PASSWORD', 'PASSWORD');
define('DB_NAME', 'DATABASE_NAME');

// Attempt to connect to the MySQL database using the mysqli extension.
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check the connection for any errors.
if($conn->connect_error){
    // If there is an error, terminate the script and display the error message.
    die("ERROR: Connection failed. " . $conn->connect_error);
}
?>
