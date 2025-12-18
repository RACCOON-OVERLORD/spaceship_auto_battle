<?php
// Database.php - Singleton pattern for database connection and CRUD operations

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $host = 'localhost';
        $dbname = 'spaceship_db';
        $username = 'root';
        $password = 'root';
        
        try {
            $this->connection = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    // Singleton pattern - get or create database instance
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    // Get PDO connection
    public function getConnection(): PDO {
        return $this->connection;
    }
    
    // Save a spaceship and optionally its pilot
    public function saveSpaceship(Spaceship $spaceship, ?Pilot $pilot = null): int {
        $conn = $this->getConnection();
        
        // Start transaction
        $conn->beginTransaction();
        
        try {
            // Save pilot if exists
            $pilotId = null;
            if ($pilot) {
                $stmt = $conn->prepare("INSERT INTO pilots (name, experience, rank, level) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $pilot->getName(),
                    $pilot->getExperience(),
                    $pilot->getRank(),
                    $pilot->getLevel()
                ]);
                $pilotId = $conn->lastInsertId();
            }
            
            // Save spaceship
            $stmt = $conn->prepare("INSERT INTO spaceships (name, max_health, health, attack_power, defense, speed, pilot_id, saved) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([
                $spaceship->getName(),
                $spaceship->getMaxHealth(),
                $spaceship->getHealth(),
                $spaceship->getAttackPower(),
                $spaceship->getDefense(),
                $spaceship->getSpeed(),
                $pilotId
            ]);
            
            $spaceshipId = $conn->lastInsertId();
            
            $conn->commit();
            return $spaceshipId;
            
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }
    
    // Load a spaceship by ID
    public function loadSpaceship(int $id): ?array {
        $conn = $this->getConnection();
        
        $stmt = $conn->prepare("
            SELECT s.*, p.name as pilot_name, p.experience, p.rank, p.level as pilot_level
            FROM spaceships s 
            LEFT JOIN pilots p ON s.pilot_id = p.id 
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        return $data;
    }
    
    // Load all rooms for a spaceship
    public function loadRooms(int $spaceshipId): array {
        $conn = $this->getConnection();
        
        $stmt = $conn->prepare("SELECT * FROM rooms WHERE spaceship_id = ?");
        $stmt->execute([$spaceshipId]);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $results ?: [];
    }
    
    // Delete a spaceship and its associated rooms and pilot
    public function deleteSpaceship(int $id): bool {
        $conn = $this->getConnection();
        
        try {
            $conn->beginTransaction();
            
            // Get pilot ID first
            $stmt = $conn->prepare("SELECT pilot_id FROM spaceships WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data && $data['pilot_id']) {
                // Delete pilot
                $stmt = $conn->prepare("DELETE FROM pilots WHERE id = ?");
                $stmt->execute([$data['pilot_id']]);
            }
            
            // Delete spaceship (rooms will cascade delete)
            $stmt = $conn->prepare("DELETE FROM spaceships WHERE id = ?");
            $stmt->execute([$id]);
            
            $conn->commit();
            return true;
            
        } catch (Exception $e) {
            $conn->rollBack();
            return false;
        }
    }
    
    // Get all saved spaceships
    public function getSavedSpaceships(): array {
        $conn = $this->getConnection();
        
        $stmt = $conn->prepare("
            SELECT s.*, p.name as pilot_name 
            FROM spaceships s 
            LEFT JOIN pilots p ON s.pilot_id = p.id 
            WHERE s.saved = 1 
            ORDER BY s.created_at DESC
        ");
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $results ?: [];
    }
    
    // Update an existing spaceship
    public function updateSpaceship(Spaceship $spaceship, ?Pilot $pilot = null): bool {
        $conn = $this->getConnection();
        
        if (!$spaceship->getDatabaseId()) {
            return false;
        }
        
        try {
            $conn->beginTransaction();
            
            // Update pilot if exists
            if ($pilot) {
                // Check if pilot already has an ID in database
                $stmt = $conn->prepare("
                    UPDATE pilots 
                    SET name = ?, experience = ?, rank = ?, level = ?
                    WHERE id = (SELECT pilot_id FROM spaceships WHERE id = ?)
                ");
                $stmt->execute([
                    $pilot->getName(),
                    $pilot->getExperience(),
                    $pilot->getRank(),
                    $pilot->getLevel(),
                    $spaceship->getDatabaseId()
                ]);
            }
            
            // Update spaceship
            $stmt = $conn->prepare("
                UPDATE spaceships 
                SET name = ?, max_health = ?, health = ?, attack_power = ?, defense = ?, speed = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $spaceship->getName(),
                $spaceship->getMaxHealth(),
                $spaceship->getHealth(),
                $spaceship->getAttackPower(),
                $spaceship->getDefense(),
                $spaceship->getSpeed(),
                $spaceship->getDatabaseId()
            ]);
            
            // Delete old rooms
            $stmt = $conn->prepare("DELETE FROM rooms WHERE spaceship_id = ?");
            $stmt->execute([$spaceship->getDatabaseId()]);
            
            // Insert new rooms
            $rooms = $spaceship->getRooms();
            foreach ($rooms as $room) {
                $stmt = $conn->prepare("INSERT INTO rooms (spaceship_id, room_type, name, level, capacity, efficiency) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $spaceship->getDatabaseId(),
                    $room->getRoomType(),
                    $room->getName(),
                    $room->getLevel(),
                    $room->getCapacity(),
                    $room->getEfficiency()
                ]);
            }
            
            $conn->commit();
            return true;
            
        } catch (Exception $e) {
            $conn->rollBack();
            return false;
        }
    }
}
?>