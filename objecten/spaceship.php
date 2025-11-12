<?php 
class Spaceship {
    public string $name;
    public int $length;
    public int $health;
    public int $attackPower;

    public function __construct(string $name, int $health, int $attackPower, int $length) {
        $this->name = $name;
        $this->health = $health;
        $this->attackPower = $attackPower;
        $this->length = $length;
    }

    public function attack($target) {
        $target->health -= $this->attackPower;
        if ($target->health < 0) {
            $target->health = 0;
        }
    }

    public function isDestroyed() {
        return $this->health <= 0;

    }
        public function __getName():string {
            return $this->name;
        }
        public function __setName(string $name) :void {
            $this->name =  $name;
        }
                public function __getHealth():int {
            return $this->health;
        }
        public function __setHealth(int $health) :void {
            $this->health =  $health;
        }
                        public function __getattackpower():int {
            return $this->attackPower;
        }
        public function __setattackpower(int $attackPower) :void {
            $this->attackPower =  $attackPower;
        }
                        public function __getlength():int {
            return $this->length;
        }
        public function __setlength(int $length) :void {
            $this->length =  $length;
        }
}   