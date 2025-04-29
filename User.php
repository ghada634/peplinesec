<?php

class User
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Méthode pour ajouter un utilisateur
    public function addUser($username, $password)
    {
        $stmt = $this->db->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
        return $stmt->execute([
            ':username' => $username,
            ':password' => password_hash($password, PASSWORD_DEFAULT)
        ]);
    }

    // Méthode pour vérifier si un utilisateur existe
    public function userExists($username)
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Méthode pour récupérer un utilisateur
    public function getUser($username)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Méthode pour authentifier un utilisateur
    public function authenticateUser($username, $password)
    {
        $user = $this->getUser($username);
        if ($user && password_verify($password, $user['password'])) {
            return true;
        }
        return false;
    }
}
