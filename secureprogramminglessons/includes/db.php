<?php


// PDO db connection
$host = 'localhost';  // Dit moet overeenkomen met de servicenaam van MySQL in docker-compose.yml
$db   = 'mydb'; // De naam van je database
$user = 'root'; // Je MySQL-gebruikersnaam
$pass = 'root'; // Je MySQL-wachtwoord
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>