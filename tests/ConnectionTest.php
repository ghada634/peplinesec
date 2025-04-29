<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../connection.php';

class ConnectionTest extends TestCase
{
    public function testDatabaseConnection()
    {
        // Essayer de se connecter à la base de données
        $connection = new mysqli('localhost', 'root', '', 'edoc');

        // Ajouter un echo pour vérifier si la connexion réussit
        if ($connection->connect_error) {
            echo "Erreur de connexion : " . $connection->connect_error;
        } else {
            echo "Connexion réussie à la base de données.";
        }

        // Vérification de l'absence d'erreur de connexion
        $this->assertTrue($connection->connect_error == null);
    }
}
