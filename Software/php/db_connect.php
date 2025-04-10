<?php 
//set the database host (localhost for local development)
$host = 'localhost';

//set the default PostgreSQL port
$port = '5432';

//set the name of the PostgreSQL database
$dbname = 'Enterprise-Pro';

//set the database username
$user = 'postgres';

//set the password for the database user
$password = 'DBAdmin123';

try {
    //create a new PDO instance to connect to the PostgreSQL database
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname", //connection string (DSN)
        $user,                                         //username
        $password,                                     //password
        [
            //enable exceptions for error handling
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

            //return results as associative arrays by default
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    //run a simple query to verify the connection works
    $pdo->query("SELECT 1");
} 
//if an exception occurs, the catch block will handle it
catch (PDOException $e) {
    //terminate the script and output the connection error
    die("❌ Database connection failed: " . $e->getMessage());
}
?>
