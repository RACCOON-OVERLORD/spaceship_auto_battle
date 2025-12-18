<?php 
require_once __DIR__ . '/room.php';
require_once __DIR__ . '/pilot.php';

class Spaceship {
    private string $name;
    private int $maxHealth;
    private int $health;
    private int $attackPower;
    private int $defense;
    private int $speed;
    private ?Pilot $pilot;
    private array $rooms;
    private ?int $dbId;

    public function __construct(string $name, int $health, int $attackPower, int $defense = 3, int $speed = 50) {
        $this->name = $name;
        $this->maxHealth = $health;
        $this->health = $health;
        $this->attackPower = $attackPower;
        $this->defense = $defense;
        $this->speed = $speed;
        $this->pilot = null;
        $this->rooms = [];
        $this->dbId = null;
    }

    public function assignPilot(Pilot $pilot): void {
        $this->pilot = $pilot;
        $this->addMessage("{$pilot->getName()} is toegewezen aan {$this->name}!");
    }

    public function attack(Spaceship $target): int {
        $baseDamage = $this->attackPower;
        
        // Pilot bonus
        if ($this->pilot) {
            $pilotBonus = $this->pilot->getLevel();
            $baseDamage += $pilotBonus;
        }

        // Critical hit chance
        $critChance = rand(1, 10);
        $criticalHit = false;
        if ($critChance <= 10) {
            $baseDamage = (int)($baseDamage * 1.5);
            $criticalHit = true;
        }

        // Calculate damage with defense
        $actualDamage = max(1, $baseDamage - $target->getDefense());
        $target->takeDamage($actualDamage);
        
        $message = "";
        if ($criticalHit) {
            $message .= "CRITICAL HIT! ";
        }
        $message .= "{$this->name} valt {$target->getName()} aan voor {$actualDamage} schade!";
        $this->addMessage($message);
        
        return $actualDamage;
    }

    public function takeDamage(int $damage): void {
        $this->health -= $damage;
        if ($this->health < 0) {
            $this->health = 0;
        }
    }

    public function battle(Spaceship $opponent): string {
        $battleLog = "=== SPACE BATTLE ===<br>";
        $battleLog .= "{$this->name} VS {$opponent->getName()}<br><br>";
        
        $round = 1;
        while (!$this->isDestroyed() && !$opponent->isDestroyed()) {
            $battleLog .= "--- Ronde {$round} ---<br>";
            
            // Snelheid bepaalt wie eerst aanvalt
            if ($this->speed >= $opponent->getSpeed()) {
                $this->attack($opponent);
                $battleLog .= $this->getLastMessage() . "<br>";
                if (!$opponent->isDestroyed()) {
                    $opponent->attack($this);
                    $battleLog .= $opponent->getLastMessage() . "<br>";
                }
            } else {
                $opponent->attack($this);
                $battleLog .= $opponent->getLastMessage() . "<br>";
                if (!$this->isDestroyed()) {
                    $this->attack($opponent);
                    $battleLog .= $this->getLastMessage() . "<br>";
                }
            }
            
            $battleLog .= "{$this->name}: {$this->health}/{$this->maxHealth} HP<br>";
            $battleLog .= "{$opponent->getName()}: {$opponent->getHealth()}/{$opponent->getMaxHealth()} HP<br><br>";
            
            $round++;
            
            if ($round > 10) {
                $battleLog .= "Battle timeout! Gelijkspel!<br>";
                $this->setMessage($battleLog);
                return "draw";
            }
        }
        
        $winner = $this->isDestroyed() ? $opponent->getName() : $this->name;
        $battleLog .= "{$winner} heeft gewonnen!<br>";
        $battleLog .= "====================<br><br>";
        
        $this->setMessage($battleLog);
        
        // XP toekennen aan winnaar
        if (!$this->isDestroyed() && $this->pilot) {
            $xpGained = 30 + ($opponent->getAttackPower());
            $this->pilot->gainExperience($xpGained);
        }
        
        return $this->isDestroyed() ? "loss" : "win";
    }

    public function repair(int $amount): void {
        $this->health = min($this->maxHealth, $this->health + $amount);
        $this->setMessage("{$this->name} is gerepareerd voor {$amount} HP!");
    }

    public function isDestroyed(): bool {
        return $this->health <= 0;
    }

    public function __toString(): string {
        $status = $this->isDestroyed() ? "VERNIETIGD" : "Actief";
        $pilotInfo = $this->pilot ? " | Pilot: " . $this->pilot->getName() : " | Geen pilot";
        return "Spaceship: {$this->name} | HP: {$this->health}/{$this->maxHealth} | ATK: {$this->attackPower} | DEF: {$this->defense} | SPD: {$this->speed}{$pilotInfo} | {$status}";
    }

    // Message handling
    private function addMessage(string $message): void {
        if (!isset($_SESSION['message'])) {
            $_SESSION['message'] = "";
        }
        $_SESSION['message'] .= $message . "<br>";
    }

    private function setMessage(string $message): void {
        $_SESSION['message'] = $message;
    }

    private function getLastMessage(): string {
        return isset($_SESSION['message']) ? $_SESSION['message'] : "";
    }

    // Getters
    public function getName(): string { return $this->name; }
    public function getHealth(): int { return $this->health; }
    public function getMaxHealth(): int { return $this->maxHealth; }
    public function getAttackPower(): int { return $this->attackPower; }
    public function getDefense(): int { return $this->defense; }
    public function getSpeed(): int { return $this->speed; }
    public function getPilot(): ?Pilot { return $this->pilot; }
    
    // Upgrade methods
    public function upgradeAttack(int $amount): void {
        $this->attackPower += $amount;
        $this->setMessage("Attack Power verhoogd naar {$this->attackPower}!");
    }
    
    public function upgradeDefense(int $amount): void {
        $this->defense += $amount;
        $this->setMessage("Defense verhoogd naar {$this->defense}!");
    }
    
    public function upgradeHealth(int $amount): void {
        $this->maxHealth += $amount;
        $this->health += $amount;
        $this->setMessage("Max Health verhoogd naar {$this->maxHealth}!");
    }
    
    public function upgradeSpeed(int $amount): void {
        $this->speed += $amount;
        $this->setMessage("Speed verhoogd naar {$this->speed}!");
    }

    // Room management
    public function addRoom(Room $room): void {
        $this->rooms[] = $room;
        $this->addMessage("{$room->getName()} toegevoegd aan het schip!");
    }

    public function upgradeRoom(int $roomIndex): bool {
        if (!isset($this->rooms[$roomIndex])) return false;
        $this->rooms[$roomIndex]->upgrade();
        return true;
    }

    public function getRooms(): array {
        return $this->rooms;
    }

    public function getRoomCount(): int {
        return count($this->rooms);
    }

    public function getRoomInfo(): string {
        $info = "Kamers aan boord:\n";
        foreach ($this->rooms as $i => $room) {
            $info .= "  {$i}. {$room}\n";
        }
        return $info;
    }

    // Database operations
    public function saveToDatabase(?Pilot $pilot = null): int {
        require_once __DIR__ . '/database.php';
        $db = Database::getInstance();
        $this->dbId = $db->saveSpaceship($this, $pilot);

        if ($this->dbId) {
            $conn = $db->getConnection();
            foreach ($this->rooms as $room) {
                $stmt = $conn->prepare("INSERT INTO rooms (spaceship_id, room_type, name, level, capacity, efficiency) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $this->dbId,
                    $room->getRoomType(),
                    $room->getName(),
                    $room->getLevel(),
                    $room->getCapacity(),
                    $room->getEfficiency()
                ]);
            }
        }

        return $this->dbId;
    }

    public function loadFromDatabase(int $id): bool {
        require_once __DIR__ . '/database.php';
        $db = Database::getInstance();
        $data = $db->loadSpaceship($id);
        if (!$data) return false;

        $this->dbId = $id;
        $this->name = $data['name'];
        $this->maxHealth = (int)$data['max_health'];
        $this->health = (int)$data['health'];
        $this->attackPower = (int)$data['attack_power'];
        $this->defense = (int)$data['defense'];
        $this->speed = (int)$data['speed'];

        // Load rooms
        $this->rooms = [];
        $roomsData = $db->loadRooms($id);
        foreach ($roomsData as $r) {
            $room = $this->createRoomFromData($r);
            if ($room) $this->rooms[] = $room;
        }

        return true;
    }

    private function createRoomFromData(array $data): ?Room {
        $map = [
            'bridge' => Bridge::class,
            'engine' => EngineRoom::class,
            'weapon' => WeaponRoom::class,
            'medical' => MedicalBay::class,
            'shield' => ShieldRoom::class
        ];

        $type = $data['room_type'] ?? null;
        if (!$type || !isset($map[$type])) return null;

        $class = $map[$type];
        return new $class(
            $data['name'],
            (int)$data['level'],
            (int)$data['capacity'],
            (float)$data['efficiency']
        );
    }

    public function deleteFromDatabase(?int $id = null): bool {
        $target = $id ?? $this->dbId;
        if (!$target) return false;
        require_once __DIR__ . '/database.php';
        $db = Database::getInstance();
        return $db->deleteSpaceship($target);
    }

    public function getDatabaseId(): ?int {
        return $this->dbId;
    }
}
?>