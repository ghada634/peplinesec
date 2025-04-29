<?php

require_once __DIR__ . '/../User.php';
use PHPUnit\Framework\TestCase;
class UserTest extends TestCase
{
    private $db;
    private $user;
// Configuration avant chaque test
    protected function setUp(): void
    {
        // Création d'une instance de la connexion à la base de données (ici, avec SQLite pour les tests)
        $this->db = new PDO('sqlite::memory:');
// Utilisation de la base de données en mémoire pour les tests
        $this->db->exec("CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT, password TEXT)");
// Création de l'objet User qui sera testé
        $this->user = new User($this->db);
    }

    // Test de l'ajout d'un utilisateur
    public function testAddUser()
    {
        // Ajouter un utilisateur
        $result = $this->user->addUser('testuser', 'password123');
// Vérifier que l'utilisateur a bien été ajouté (l'ID de l'utilisateur est non nul)
        $this->assertTrue($result);
// Vérifier si l'utilisateur existe maintenant dans la base de données
        $user = $this->user->getUser('testuser');
        $this->assertNotNull($user);
        $this->assertEquals('testuser', $user['username']);
    }

    // Test pour vérifier si un utilisateur existe
    public function testUserExists()
    {
        // Ajouter un utilisateur
        $this->user->addUser('testuser', 'password123');
// Vérifier si l'utilisateur existe
        $user = $this->user->userExists('testuser');
        $this->assertNotEmpty($user);
// Vérifier un utilisateur inexistant
        $user = $this->user->userExists('nonexistentuser');
        $this->assertEmpty($user);
    }

    // Test pour vérifier l'authentification de l'utilisateur
    public function testAuthenticateUser()
    {
        // Ajouter un utilisateur
        $this->user->addUser('testuser', 'password123');
// Récupérer l'utilisateur et vérifier le mot de passe
        $user = $this->user->getUser('testuser');
        $this->assertNotEmpty($user);
        $this->assertTrue(password_verify('password123', $user['password']));
// Tester l'authentification avec un mauvais mot de passe
        $this->assertFalse($this->user->authenticateUser('testuser', 'wrongpassword'));
    }

    // Nettoyage après chaque test
    protected function tearDown(): void
    {
        // Fermer la connexion à la base de données (bien que SQLite en mémoire se ferme automatiquement)
        $this->db = null;
    }

    // Ajouter un echo à la fin pour indiquer que tous les tests sont passés avec succès
    public static function tearDownAfterClass(): void
    {
        echo "Tous les tests ont été effectués avec succès.\n";
    }
}
