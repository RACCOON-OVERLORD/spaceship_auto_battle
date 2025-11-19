<?php 
class Pilot {
    private string $name;
    private int $experience;
    private string $rank;
    private int $level;

    public function __construct(string $name, int $experience = 0, string $rank = "Recruit") {
        $this->name = $name;
        $this->experience = $experience;
        $this->rank = $rank;
        $this->level = 1;
    }

    public function gainExperience(int $amount): void {
        $this->experience += $amount;
        $this->addMessage("{$this->name} heeft {$amount} ervaring verdiend!");
        $this->checkLevelUp();
    }

    private function checkLevelUp(): void {
        $requiredXP = $this->level * 50;
        if ($this->experience >= $requiredXP) {
            $this->level++;
            $this->addMessage("LEVEL UP! {$this->name} is nu level {$this->level}!");
            $this->updateRank();
        }
    }

    private function updateRank(): void {
        $ranks = ["Recruit", "Ensign", "Lieutenant", "Captain", "Commander"];
        $rankIndex = min(floor($this->level / 2), count($ranks) - 1);
        $this->rank = $ranks[$rankIndex];
    }

    public function __toString(): string {
        return "Pilot: {$this->name} | Rang: {$this->rank} | Level: {$this->level} | XP: {$this->experience}";
    }

    // Message handling
    private function addMessage(string $message): void {
        if (!isset($_SESSION['message'])) {
            $_SESSION['message'] = "";
        }
        $_SESSION['message'] .= $message . "<br>";
    }

    // Getters
    public function getName(): string {
        return $this->name;
    }

    public function getExperience(): int {
        return $this->experience;
    }

    public function getRank(): string {
        return $this->rank;
    }

    public function getLevel(): int {
        return $this->level;
    }

    // Setters
    public function setName(string $name): void {
        $this->name = $name;
    }

    public function setExperience(int $experience): void {
        $this->experience = $experience;
    }

    public function setRank(string $rank): void {
        $this->rank = $rank;
    }
}
?>