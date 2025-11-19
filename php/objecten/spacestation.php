<?php 
class SpaceStation {
    private string $name;
    private int $capacity;
    private int $shieldStrength;

    public function __construct(string $name, int $capacity, int $shieldStrength) {
        $this->name = $name;
        $this->capacity = $capacity;
        $this->shieldStrength = $shieldStrength;
    }

    public function repair(Spaceship $ship, int $amount): void {
        $ship->repair($amount);
        $this->setMessage("{$this->name} heeft {$ship->getName()} gerepareerd!");
    }

    public function __toString(): string {
        return "SpaceStation: {$this->name} | Capaciteit: {$this->capacity} | Shield: {$this->shieldStrength}";
    }

    // Message handling
    private function setMessage(string $message): void {
        $_SESSION['message'] = $message;
    }

    // Getters
    public function getName(): string {
        return $this->name;
    }

    public function getCapacity(): int {
        return $this->capacity;
    }

    public function getShieldStrength(): int {
        return $this->shieldStrength;
    }
}

class Game {
    public Spaceship $playerShip;
    public Pilot $player;
    public int $credits;
    public int $level;
    public array $enemies;

    public function __construct(string $playerName, string $shipName) {
        $this->player = new Pilot($playerName, 0, "Recruit");
        $this->playerShip = new Spaceship($shipName, 120, 25, 5, 60);
        $this->playerShip->assignPilot($this->player);
        $this->credits = 150;
        $this->level = 1;
        $this->generateEnemies();
    }

    private function generateEnemies(): void {
        // Star Wars themed enemies - moeilijkheid neemt toe met level
        $this->enemies = [
            new Spaceship("TIE Fighter", 50 + ($this->level * 5), 12 + ($this->level * 1), 2, 70),
            new Spaceship("TIE Interceptor", 70 + ($this->level * 8), 15 + ($this->level * 2), 3, 75),
            new Spaceship("TIE Bomber", 90 + ($this->level * 10), 18 + ($this->level * 2), 4, 55),
            new Spaceship("TIE Advanced", 110 + ($this->level * 12), 22 + ($this->level * 3), 5, 65),
            new Spaceship("Imperial Star Destroyer", 200 + ($this->level * 20), 30 + ($this->level * 4), 10, 40),
            new Spaceship("Slave I", 80 + ($this->level * 8), 20 + ($this->level * 2), 6, 70),
            new Spaceship("TIE Defender", 130 + ($this->level * 15), 25 + ($this->level * 3), 7, 80),
            new Spaceship("AT-AT Walker", 250 + ($this->level * 25), 35 + ($this->level * 4), 15, 20),
            new Spaceship("X-Wing Fighter", 60 + ($this->level * 6), 16 + ($this->level * 2), 3, 75),
            new Spaceship("Death Star", 500 + ($this->level * 50), 50 + ($this->level * 5), 25, 10)
        ];
        
        // Selecteer 3 willekeurige tegenstanders voor dit level
        $randomKeys = array_rand($this->enemies, 3);
        $this->enemies = [
            $this->enemies[$randomKeys[0]],
            $this->enemies[$randomKeys[1]],
            $this->enemies[$randomKeys[2]]
        ];
    }

    public function battle(int $enemyIndex): void {
        if (!isset($this->enemies[$enemyIndex])) {
            $this->setMessage("Ongeldige tegenstander!");
            return;
        }

        $enemy = $this->enemies[$enemyIndex];
        $result = $this->playerShip->battle($enemy);
        
        if ($result === "win") {
            $reward = 40 + ($this->level * 15);
            $this->credits += $reward;
            $this->addMessage("Je hebt {$reward} credits verdiend!");
            $this->level++;
            $this->generateEnemies();
            
            // Speciale bonus voor het verslaan van sterke tegenstanders
            if (strpos($enemy->getName(), "Death Star") !== false || 
                strpos($enemy->getName(), "Star Destroyer") !== false) {
                $bonus = 100;
                $this->credits += $bonus;
                $this->addMessage("SPECIALE BONUS! +{$bonus} credits voor het verslaan van een " . $enemy->getName() . "!");
            }
        } elseif ($result === "loss") {
            $this->setMessage("Je schip is vernietigd! Game Over! De " . $enemy->getName() . " was te sterk.");
        }
    }

    public function repair(): void {
        if ($this->credits < 30) {
            $this->setMessage("Niet genoeg credits! Je hebt 30 credits nodig.");
            return;
        }
        
        $this->credits -= 30;
        $this->playerShip->repair(80);
    }

    public function upgrade(int $choice): void {
        $costs = [1 => 80, 2 => 60, 3 => 100, 4 => 50];
        
        if (!isset($costs[$choice])) {
            $this->setMessage("Ongeldige upgrade keuze!");
            return;
        }
        
        if ($this->credits < $costs[$choice]) {
            $this->setMessage("Niet genoeg credits!");
            return;
        }
        
        $this->credits -= $costs[$choice];
        
        switch ($choice) {
            case 1: $this->playerShip->upgradeAttack(8); break;
            case 2: $this->playerShip->upgradeDefense(4); break;
            case 3: $this->playerShip->upgradeHealth(30); break;
            case 4: $this->playerShip->upgradeSpeed(16); break;
        }
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

    public function getCredits(): int { 
        return $this->credits; 
    }
}
?>