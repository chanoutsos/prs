<?php
$host = 'localhost';
$port = 3307;
$db = 'prs_database';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
