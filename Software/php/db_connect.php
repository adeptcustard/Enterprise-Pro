<?php
$host ='localhost';
//name of database
$dbname = 'Enterprise-Pro';
//name of user for database
$user = 'postgres';
//password for user
$password = 'DBAdmin123';

try {
    //create a new PDO connection for PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);

    //set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    //if connection fails, display error message
    die("❌ Database connection failed: " . $e->getMessage());
}
?>
