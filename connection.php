<?php
$servername = "db";
$username = "edocuser";  
$password = "edocpassword"; 
$dbname = "edoc"; 

$database = new mysqli($servername, $username, $password, $dbname);

if ($database->connect_error) {
    die("Échec de la connexion : " . $database->connect_error);
}


