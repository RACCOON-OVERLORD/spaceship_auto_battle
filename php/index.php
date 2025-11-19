<?php 
require_once __DIR__ . '/objecten/spaceship.php';
require_once __DIR__ . '/objecten/pilot.php';
require_once __DIR__ . '/objecten/spacestation.php';

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
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f0f0f0; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        .menu { background: #e0e0e0; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .button { background: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        .button:hover { background: #45a049; }
        .enemy { background: #ffebee; padding: 10px; margin: 5px 0; border-radius: 5px; }
        .message { background: #e3f2fd; padding: 10px; margin: 10px 0; border-radius: 5px; white-space: pre-line; }
        .stats { background: #f3e5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .form-group { margin: 10px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"] { padding: 8px; width: 200px; border: 1px solid #ccc; border-radius: 4px; }
    </style>
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
            </div>
            
        <?php else: 
            $game = $_SESSION['game'];
            ?>
            
            <!-- Game screen -->
            <?php if (!empty($_SESSION['message'])): ?>
                <div class="message"><?php echo $_SESSION['message']; ?></div>
                <?php $_SESSION['message'] = ""; // Clear message after displaying ?>
            <?php endif; ?>
            
            <div class="stats">
                <h3>Speler Info</h3>
                <pre><?php echo $game->player . "\n" . $game->playerShip; ?></pre>
                <p><strong>Credits:</strong> <?php echo $game->getCredits(); ?></p>
                <p><strong>Mission Level:</strong> <?php echo $game->level; ?></p>
            </div>
            
            <!-- Main Menu -->
            <div class="menu">
                <h3>Menu</h3>
                <form method="post">
                    <button type="submit" name="action" value="show_battle" class="button">Battle</button>
                    <button type="submit" name="action" value="repair" class="button">Repareren (30 credits)</button>
                    <button type="submit" name="action" value="show_upgrade" class="button">Upgrade Ship</button>
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
            <?php endif; ?>
            
        <?php endif; ?>
    </div>
</body>
</html>