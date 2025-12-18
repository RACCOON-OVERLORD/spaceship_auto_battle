<?php 
require_once __DIR__ . '/objecten/spaceship.php';
require_once __DIR__ . '/objecten/pilot.php';
require_once __DIR__ . '/objecten/spacestation.php';
require_once __DIR__ . '/objecten/room.php';

session_start();

// Initialize message if not exists
if (!isset($_SESSION['message'])) {
    $_SESSION['message'] = "";
}

// Initialize game if not exists
if (!isset($_SESSION['game'])) {
    if (isset($_POST['player_name']) && !empty($_POST['player_name']) && isset($_POST['ship_name']) && !empty($_POST['ship_name'])) {
        $_SESSION['game'] = new Game($_POST['player_name'], $_POST['ship_name']);
        $_SESSION['message'] = "Welkom, " . $_POST['player_name'] . "! Je schip " . $_POST['ship_name'] . " is klaar voor actie!";
    }
}

// Handle actions
if (isset($_SESSION['game']) && isset($_POST['action'])) {
    $game = $_SESSION['game'];
    
    switch ($_POST['action']) {
        case 'battle':
            if (isset($_POST['enemy_index'])) {
                $game->battle((int)$_POST['enemy_index']);
            }
            break;
        case 'repair':
            $game->repair();
            break;
        case 'upgrade':
            if (isset($_POST['upgrade_type'])) {
                $game->upgrade((int)$_POST['upgrade_type']);
            }
            break;
        case 'add_room':
            if (isset($_POST['room_type'])) {
                $roomType = $_POST['room_type'];
                $roomName = $_POST['room_name'] ?? '';
                
                // Cost for adding room
                $roomCost = 100;
                if ($game->getCredits() >= $roomCost) {
                    $room = null;
                    switch($roomType) {
                        case 'bridge':
                            $room = new Bridge($roomName ?: "Command Bridge");
                            break;
                        case 'engine':
                            $room = new EngineRoom($roomName ?: "Main Engine");
                            break;
                        case 'weapon':
                            $room = new WeaponRoom($roomName ?: "Weapon Bay");
                            break;
                        case 'medical':
                            $room = new MedicalBay($roomName ?: "Med Bay");
                            break;
                        case 'shield':
                            $room = new ShieldRoom($roomName ?: "Shield Generator");
                            break;
                    }
                    
                    if ($room) {
                        $game->playerShip->addRoom($room);
                        $game->credits -= $roomCost;
                        $_SESSION['message'] = "Room toegevoegd! (-{$roomCost} credits)";
                    }
                } else {
                    $_SESSION['message'] = "Niet genoeg credits! Je hebt {$roomCost} credits nodig.";
                }
            }
            break;
        case 'upgrade_room':
            if (isset($_POST['room_index'])) {
                $game->upgradeRoom((int)$_POST['room_index']);
            }
            break;
        case 'save_game':
            $game->saveGame();
            break;
        case 'load_game':
            if (isset($_POST['save_id'])) {
                $game->loadGame((int)$_POST['save_id']);
            }
            break;
        case 'delete_save':
            if (isset($_POST['save_id'])) {
                $game->deleteSave((int)$_POST['save_id']);
            }
            break;
        case 'restart':
            session_destroy();
            header("Location: index.php");
            exit;
    }
    
    $_SESSION['game'] = $game;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Space Battle Simulator</title>
    <link rel="stylesheet" href="../css/star.css">
</head>
<body>
    <div class="container">
        <h1>Space Battle Simulator</h1>
        
        <?php if (!isset($_SESSION['game'])): ?>
            <!-- Start screen -->
            <div class="menu">
                <h3>Start je ruimte avontuur!</h3>
                <form method="post">
                    <div class="form-group">
                        <label for="player_name">Voer je naam in:</label>
                        <input type="text" id="player_name" name="player_name" required placeholder="Bijv: Luke Skywalker">
                    </div>
                    <div class="form-group">
                        <label for="ship_name">Kies je spaceship naam:</label>
                        <input type="text" id="ship_name" name="ship_name" required placeholder="Bijv: Millennium Falcon">
                    </div>
                    <button type="submit" class="button">Start Spel</button>
                </form>
                
                <!-- Load Game Section -->
                <?php 
                require_once __DIR__ . '/objecten/database.php';
                $db = Database::getInstance();
                $savedGames = $db->getSavedSpaceships();
                if (!empty($savedGames)): ?>
                    <div class="menu" style="margin-top: 20px;">
                        <h3>Of laad een opgeslagen spel:</h3>
                        <div class="save-list">
                            <?php foreach ($savedGames as $save): ?>
                                <div class="save-item">
                                    <span>
                                        <?php echo htmlspecialchars($save['name']); ?> 
                                        (<?php echo htmlspecialchars($save['pilot_name'] ?? 'Geen pilot'); ?>) - 
                                        HP: <?php echo $save['health']; ?>/<?php echo $save['max_health']; ?>
                                    </span>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="save_id" value="<?php echo $save['id']; ?>">
                                        <button type="submit" name="action" value="load_game" class="button" style="padding: 5px 10px;">Load</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
        <?php else: 
            $game = $_SESSION['game'];
            ?>
            
            <!-- Game screen -->
            <?php if (!empty($_SESSION['message'])): ?>
                <div class="message"><?php echo $_SESSION['message']; ?></div>
                <?php $_SESSION['message'] = ""; ?>
            <?php endif; ?>
            
            <div class="stats">
                <h3>Speler Info</h3>
                <pre><?php echo $game->player . "\n" . $game->playerShip; ?></pre>
                <p><strong>Credits:</strong> <?php echo $game->getCredits(); ?></p>
                <p><strong>Mission Level:</strong> <?php echo $game->level; ?></p>
            </div>
            
            <!-- Rooms Display -->
            <?php if ($game->playerShip->getRoomCount() > 0): ?>
                <div class="room-list">
                    <h3>Ship Rooms (<?php echo $game->playerShip->getRoomCount(); ?>)</h3>
                    <?php foreach ($game->playerShip->getRooms() as $index => $room): ?>
                        <div class="room-item">
                            <strong><?php echo htmlspecialchars($room->getName()); ?></strong> 
                            (Level <?php echo $room->getLevel(); ?>) - 
                            Type: <?php echo $room->getRoomType(); ?> | 
                            Efficiency: <?php echo number_format($room->getEfficiency(), 2); ?>
                            <div class="room-bonus">
                                Bonuses: 
                                <?php 
                                $bonuses = $room->getBonusEffect();
                                foreach ($bonuses as $key => $value) {
                                    echo $key . ": " . number_format($value, 2) . " ";
                                }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Main Menu -->
            <div class="menu">
                <h3>Menu</h3>
                <form method="post">
                    <button type="submit" name="action" value="show_battle" class="button">Battle</button>
                    <button type="submit" name="action" value="repair" class="button">Repareren (30 credits)</button>
                    <button type="submit" name="action" value="show_upgrade" class="button">Upgrade Ship</button>
                    <button type="submit" name="action" value="show_rooms" class="button">Room Management</button>
                    <button type="submit" name="action" value="show_saves" class="button">Save/Load Game</button>
                    <button type="submit" name="action" value="restart" class="button">Nieuw Spel</button>
                </form>
            </div>
            
            <!-- Battle Screen -->
            <?php if (isset($_POST['action']) && $_POST['action'] == 'show_battle'): ?>
                <div class="menu">
                    <h3>Kies je tegenstander:</h3>
                    <form method="post">
                        <?php foreach ($game->enemies as $index => $enemy): ?>
                            <div class="enemy">
                                <input type="radio" name="enemy_index" value="<?php echo $index; ?>" id="enemy<?php echo $index; ?>" required>
                                <label for="enemy<?php echo $index; ?>"><?php echo $enemy; ?></label>
                            </div>
                        <?php endforeach; ?>
                        <button type="submit" name="action" value="battle" class="button">Start Battle</button>
                        <button type="submit" name="action" value="menu" class="button">Terug</button>
                    </form>
                </div>
                
            <!-- Upgrade Screen -->
            <?php elseif (isset($_POST['action']) && $_POST['action'] == 'show_upgrade'): ?>
                <div class="menu">
                    <h3>Upgrade Menu:</h3>
                    <form method="post">
                        <div class="enemy">
                            <input type="radio" name="upgrade_type" value="1" id="upgrade1" required>
                            <label for="upgrade1">Attack +8 (80 credits)</label>
                        </div>
                        <div class="enemy">
                            <input type="radio" name="upgrade_type" value="2" id="upgrade2">
                            <label for="upgrade2">Defense +4 (60 credits)</label>
                        </div>
                        <div class="enemy">
                            <input type="radio" name="upgrade_type" value="3" id="upgrade3">
                            <label for="upgrade3">Max Health +30 (100 credits)</label>
                        </div>
                        <div class="enemy">
                            <input type="radio" name="upgrade_type" value="4" id="upgrade4">
                            <label for="upgrade4">Speed +16 (50 credits)</label>
                        </div>
                        <button type="submit" name="action" value="upgrade" class="button">Upgrade Kopen</button>
                        <button type="submit" name="action" value="menu" class="button">Terug</button>
                    </form>
                </div>
                
            <!-- Room Management Screen -->
            <?php elseif (isset($_POST['action']) && $_POST['action'] == 'show_rooms'): ?>
                <div class="menu">
                    <h3>Room Management</h3>
                    
                    <!-- Add New Room -->
                    <h4>Add New Room (100 credits)</h4>
                    <form method="post">
                        <div class="form-group">
                            <label for="room_name">Room Name:</label>
                            <input type="text" id="room_name" name="room_name" placeholder="Optional custom name">
                        </div>
                        <div class="enemy">
                            <input type="radio" name="room_type" value="bridge" id="room_bridge" required>
                            <label for="room_bridge">Bridge (Command Center) - Accuracy & Crit Chance</label>
                        </div>
                        <div class="enemy">
                            <input type="radio" name="room_type" value="engine" id="room_engine">
                            <label for="room_engine">Engine Room - Speed & Damage Bonus</label>
                        </div>
                        <div class="enemy">
                            <input type="radio" name="room_type" value="weapon" id="room_weapon">
                            <label for="room_weapon">Weapon Room - Attack Power & Reload Speed</label>
                        </div>
                        <div class="enemy">
                            <input type="radio" name="room_type" value="medical" id="room_medical">
                            <label for="room_medical">Medical Bay - Healing & Health Regen</label>
                        </div>
                        <div class="enemy">
                            <input type="radio" name="room_type" value="shield" id="room_shield">
                            <label for="room_shield">Shield Room - Defense & Shield Regen</label>
                        </div>
                        <button type="submit" name="action" value="add_room" class="button">Add Room</button>
                    </form>
                    
                    <!-- Upgrade Existing Rooms -->
                    <?php if ($game->playerShip->getRoomCount() > 0): ?>
                        <h4 style="margin-top: 20px;">Upgrade Existing Rooms</h4>
                        <form method="post">
                            <?php foreach ($game->playerShip->getRooms() as $index => $room): ?>
                                <div class="enemy">
                                    <input type="radio" name="room_index" value="<?php echo $index; ?>" id="room<?php echo $index; ?>" required>
                                    <label for="room<?php echo $index; ?>">
                                        <?php echo htmlspecialchars($room->getName()); ?> (Level <?php echo $room->getLevel(); ?>) 
                                        - Cost: <?php echo 50 + ($room->getLevel() * 20); ?> credits
                                    </label>
                                </div>
                            <?php endforeach; ?>
                            <button type="submit" name="action" value="upgrade_room" class="button">Upgrade Room</button>
                        </form>
                    <?php endif; ?>
                    
                    <form method="post" style="margin-top: 15px;">
                        <button type="submit" name="action" value="menu" class="button">Terug</button>
                    </form>
                </div>
                
            <!-- Save/Load Screen -->
            <?php elseif (isset($_POST['action']) && $_POST['action'] == 'show_saves'): ?>
                <div class="menu">
                    <h3>Save/Load Game</h3>
                    
                    <!-- Save Current Game -->
                    <form method="post">
                        <button type="submit" name="action" value="save_game" class="button">Save Current Game</button>
                    </form>
                    
                    <!-- Load/Delete Saved Games -->
                    <?php 
                    $savedGames = $game->getSavedGames();
                    if (!empty($savedGames)): ?>
                        <h4 style="margin-top: 20px;">Saved Games:</h4>
                        <div class="save-list">
                            <?php foreach ($savedGames as $save): ?>
                                <div class="save-item">
                                    <span>
                                        <strong><?php echo htmlspecialchars($save['name']); ?></strong><br>
                                        Pilot: <?php echo htmlspecialchars($save['pilot_name'] ?? 'Geen pilot'); ?> | 
                                        HP: <?php echo $save['health']; ?>/<?php echo $save['max_health']; ?> | 
                                        ATK: <?php echo $save['attack_power']; ?> | 
                                        Saved: <?php echo date('Y-m-d H:i', strtotime($save['created_at'])); ?>
                                    </span>
                                    <div>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="save_id" value="<?php echo $save['id']; ?>">
                                            <button type="submit" name="action" value="load_game" class="button" style="padding: 5px 10px;">Load</button>
                                            <button type="submit" name="action" value="delete_save" class="button" style="padding: 5px 10px; background: #ff4444;" onclick="return confirm('Weet je zeker dat je dit spel wilt verwijderen?')">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>Geen opgeslagen spellen gevonden.</p>
                    <?php endif; ?>
                    
                    <form method="post" style="margin-top: 15px;">
                        <button type="submit" name="action" value="menu" class="button">Terug</button>
                    </form>
                </div>
            <?php endif; ?>
            
        <?php endif; ?>
    </div>
</body>
</html>