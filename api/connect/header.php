<?php 
header("Content-Type: application/json");
session_start();
include("connect.php");
error_reporting(1);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// header("Access-Control-Allow-Origin: https://truesdb.com");
header("Access-Control-Allow-Origin: http://localhost:4000");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
?>