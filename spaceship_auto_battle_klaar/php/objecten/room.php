<?php
// room.php - Room inheritance hierarchy
// Abstract base class and concrete room implementations

// Abstract base class for all rooms (Object Inheritance example)
abstract class Room {
    protected string $name;
    protected int $level;
    protected int $capacity;
    protected float $efficiency;
    
    public function __construct(string $name, int $level = 1, int $capacity = 1, float $efficiency = 1.0) {
        $this->name = $name;
        $this->level = $level;
        $this->capacity = $capacity;
        $this->efficiency = $efficiency;
    }
    
    // Abstract methods - must be implemented by subclasses
    abstract public function getRoomType(): string;
    abstract public function getBonusEffect(): array;
    
    // Upgrade the room to next level
    public function upgrade(): void {
        $this->level++;
        $this->efficiency += 0.1;
    }
    
    public function __toString(): string {
        return "{$this->name} (Level {$this->level}) - Type: {$this->getRoomType()} | Efficiency: {$this->efficiency}";
    }
    
    // Getters
    public function getName(): string { return $this->name; }
    public function getLevel(): int { return $this->level; }
    public function getCapacity(): int { return $this->capacity; }
    public function getEfficiency(): float { return $this->efficiency; }
}

// ===== CONCRETE ROOM IMPLEMENTATIONS (Inheritance) =====

// Bridge (Command Center) - increases accuracy and critical hit chance
class Bridge extends Room {
    public function getRoomType(): string {
        return "bridge";
    }
    
    public function getBonusEffect(): array {
        return [
            'accuracy_bonus' => 0.05 * $this->level * $this->efficiency,
            'critical_chance' => 0.02 * $this->level * $this->efficiency
        ];
    }
}

// Engine Room - increases speed and damage
class EngineRoom extends Room {
    public function getRoomType(): string {
        return "engine";
    }
    
    public function getBonusEffect(): array {
        return [
            'speed_bonus' => 5 * $this->level * $this->efficiency,
            'damage_bonus' => 2 * $this->level * $this->efficiency
        ];
    }
}

// Weapon Room - increases attack power
class WeaponRoom extends Room {
    public function getRoomType(): string {
        return "weapon";
    }
    
    public function getBonusEffect(): array {
        return [
            'attack_bonus' => 5 * $this->level * $this->efficiency,
            'reload_speed' => 0.1 * $this->level * $this->efficiency
        ];
    }
}

// Medical Bay - increases healing and health regeneration
class MedicalBay extends Room {
    public function getRoomType(): string {
        return "medical";
    }
    
    public function getBonusEffect(): array {
        return [
            'healing_bonus' => 10 * $this->level * $this->efficiency,
            'health_regen' => 2 * $this->level * $this->efficiency
        ];
    }
}

// Shield Room - increases defense
class ShieldRoom extends Room {
    public function getRoomType(): string {
        return "shield";
    }
    
    public function getBonusEffect(): array {
        return [
            'defense_bonus' => 3 * $this->level * $this->efficiency,
            'shield_regen' => 1 * $this->level * $this->efficiency
        ];
    }
}
?>
