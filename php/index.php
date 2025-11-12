
<?php 
require_once __DIR__ . '/../objecten/spaceship.php';
$spaceship = new Spaceship("Dicktweet", 10, 2, 5);
$spaceship->__getName();
$spaceship->__setName("DickTweet2");
$spaceship->__getHealth();
$spaceship->__setHealth(15);
$spaceship->__getattackpower();
$spaceship->__setattackpower(5);
$spaceship->__getlength();
$spaceship->__setlength(10);
echo $spaceship->name;
echo "\n";
echo $spaceship->health;
echo "\n";
echo $spaceship->attackPower;  
echo "\n";
echo $spaceship->length;
?>