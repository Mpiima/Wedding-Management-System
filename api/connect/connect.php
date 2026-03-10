<?php
$hostname = 'localhost';
$database = 'estore_db';
$username = 'root';
$password = '';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $dbh = new PDO("mysql:host=$hostname;dbname=$database", $username, $password);
   
    }
catch(PDOException $e)
    {
    echo $e->getMessage();
    }

?>
