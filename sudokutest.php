<?php
session_start();
// To: Rob-Bisexual E. Pez
// From: The other Japanese guy

// Please pay attention to the notes that I've left in the code, they'll help you navigate this whole mess.
// I must warn you that the "algorithm" part used to generate the puzzle is rather delicate (and confusing), I suggest that you leave it be.
// Also, if this whole thing looks disjointed and inefficient, that's because it is - I primarily recycled bits of code from sources that I could find on the internet and put them together.

// Best of luck to you :)

// This block is for creating and validating the puzzle, it can also solve it
function createEmptyBoard() {
    return array_fill(0, 9, array_fill(0, 9, 0));
}

function isSafe($board, $row, $col, $num) {
    // Sets rows and columns
    for ($i = 0; $i < 9; $i++) {
        if ($board[$row][$i] === $num) return false;
        if ($board[$i][$col] === $num) return false;
    }
    // Sets the 3x3 box
    $startRow = $row - ($row % 3);
    $startCol = $col - ($col % 3);
    for ($r = 0; $r < 3; $r++) {
        for ($c = 0; $c < 3; $c++) {
            if ($board[$startRow + $r][$startCol + $c] === $num) return false;
        }
    }
    return true;
}

function hasConflictAt($board, $row, $col) {
    $val = $board[$row][$col];
    if ($val === 0) return false;
    // Rows
    for ($c = 0; $c < 9; $c++) {
        if ($c === $col) continue; // Skip current cell
        if ($board[$row][$c] === $val) return true;
    }
    // Columns
    for ($r = 0; $r < 9; $r++) {
        if ($r === $row) continue; // Skip current cell
        if ($board[$r][$col] === $val) return true;
    }
    //Box
    $startRow = $row - ($row % 3);
    $startCol = $col - ($col % 3);
    for ($r = 0; $r < 3; $r++) {
        for ($c = 0; $c < 3; $c++) {
            $rr = $startRow + $r;
            $cc = $startCol + $c;
            if ($rr === $row && $cc === $col) continue; // Skip current cell
            if ($board[$rr][$cc] === $val) return true;
        }
    }
    return false;
}

function findEmpty($board, &$row, &$col) {
    // Finds which boxes are em
    for ($r = 0; $r < 9; $r++) {
        for ($c = 0; $c < 9; $c++) {
            if ($board[$r][$c] === 0) {
                $row = $r; $col = $c;
                return true;
            }
        }
    }
    return false;
}

function solveSudoku(&$board) {
    // Solves the puzzle
    $row = 0; $col = 0;
    if (!findEmpty($board, $row, $col)) return true;
    $nums = range(1, 9);
    shuffle($nums);
    // Tries sequential for solver, random for generator
    foreach ($nums as $num) {
        if (isSafe($board, $row, $col, $num)) {
            $board[$row][$col] = $num;
            if (solveSudoku($board)) return true;
            $board[$row][$col] = 0;
        }
    }
    return false;
}

function solveSudokuCountSolutions($board, $limit = 2) {
    // Counts solutions up to $limit (for uniqueness check, just so we don't get the same thing more than once)
    $count = 0;
    $stack = [$board];
    while (!empty($stack)) {
        $b = array_pop($stack);
        $row = 0; $col = 0;
        if (!findEmpty($b, $row, $col)) {
            $count++;
            if ($count >= $limit) return $count;
            continue;
        }
        for ($num = 1; $num <= 9; $num++) {
            if (isSafe($b, $row, $col, $num)) {
                $b[$row][$col] = $num;
                $stack[] = $b;
                $b[$row][$col] = 0;
            }
        }
    }
    return $count;
}

function generateFullBoard() {
    $board = createEmptyBoard();
    solveSudoku($board);
    return $board;
}

function removeCellsForPuzzle($fullBoard, $clues = 35) {
    // Starts from the full board, removes cells while keeping unique solution (the puzzle is made)
    $puzzle = $fullBoard;
    $cells = [];
    for ($r = 0; $r < 9; $r++) for ($c = 0; $c < 9; $c++) $cells[] = [$r, $c];
    shuffle($cells);
    // Ensures that there are clues (minimum: 17)
    $toRemove = 81 - max(17, min(81, $clues));
    foreach ($cells as [$r, $c]) {
        if ($toRemove <= 0) break;
        $backup = $puzzle[$r][$c];
        $puzzle[$r][$c] = 0;
        // Check uniqueness
        if (solveSudokuCountSolutions($puzzle, 2) !== 1) {
            $puzzle[$r][$c] = $backup;
            // If not, then revert
        } else {
            $toRemove--;
        }
    }
    return $puzzle;
}
// This is where the main block ends

// This second block sets the puzzle up
function generatePuzzle($clues = 35) {
    $full = generateFullBoard();
    $puzzle = removeCellsForPuzzle($full, $clues);
    return [$puzzle, $full];
}

function isCompletedCorrect($current, $solution) {
    for ($r = 0; $r < 9; $r++) {
        for ($c = 0; $c < 9; $c++) {
            if ($current[$r][$c] !== $solution[$r][$c]) return false;
        }
    }
    return true;
}

function sanitizeInputCell($value) {
    $v = trim($value);
    if ($v === '' || $v === '0') return 0;
    if (!ctype_digit($v)) return 0;
    $n = intval($v);
    return ($n >= 1 && $n <= 9) ? $n : 0;
}
// This is where the second block ends

// This block handles the game (i.e. gameplay)
$action = isset($_POST['action']) ? $_POST['action'] : null;

if ($action === 'new') {
    $_SESSION = [];
    session_regenerate_id(true);
    // Difficulty by clues (the value is more so a target, doing it differently would break the main block)
    $clues = isset($_POST['clues']) ? max(17, min(81, intval($_POST['clues']))) : 35;
    [$puzzle, $solution] = generatePuzzle($clues);
    $_SESSION['puzzle'] = $puzzle;
    $_SESSION['solution'] = $solution;
    $_SESSION['current'] = $puzzle;
    $_SESSION['message'] = '';
    $_SESSION['conflicts'] = [];
    $_SESSION['start_ms'] = (int)(microtime(true) * 1000);
}

// Handles the puzzle generation
if (!isset($_SESSION['puzzle'])) {
    $clues = 35;
    [$puzzle, $solution] = generatePuzzle($clues);
    $_SESSION['puzzle'] = $puzzle;
    $_SESSION['solution'] = $solution;
    $_SESSION['current'] = $puzzle;
    $_SESSION['message'] = '';
    $_SESSION['conflicts'] = [];
    $_SESSION['start_ms'] = (int)(microtime(true) * 1000);
}

// Handles the checking of the puzzle
if ($action === 'check') {
    $current  = $_SESSION['current'];
    $puzzle   = $_SESSION['puzzle'];
    $solution = $_SESSION['solution'];
    // Tracks conflicted cells
    $errors = [];
    $conflicts = [];
    for ($r = 0; $r < 9; $r++) {
        for ($c = 0; $c < 9; $c++) {
            $name = "cell_{$r}_{$c}";
            if ($puzzle[$r][$c] !== 0) {
                $current[$r][$c] = $puzzle[$r][$c];
                continue;
            }
            if (isset($_POST[$name])) {
                $val = sanitizeInputCell($_POST[$name]);
                $current[$r][$c] = $val;
                if ($val !== 0 && hasConflictAt($current, $r, $c)) {
                    $errors[] = "Conflict at row " . ($r+1) . ", col " . ($c+1);
                    $conflicts["$r-$c"] = true;
                }
            }
        }
    }
    // Saves conflicted cells
    $_SESSION['current']   = $current;
    $_SESSION['conflicts'] = $conflicts;
    if (empty($errors)) {
        if (isCompletedCorrect($current, $solution)) {
            $_SESSION['message'] = '🎉 Congratulations! You solved the puzzle.';
        } else {
            $_SESSION['message'] = 'No conflicts detected.';
        }
    } else {
        $_SESSION['message'] = 'Conflicts: ' . implode('; ', $errors);
    }
}

// The almighty [Solve Button]
if ($action === 'solve') {
    $_SESSION['current']   = $_SESSION['solution'];
    $_SESSION['conflicts'] = [];
    $_SESSION['message']   = 'Solution revealed.';
}

// Helps "guide" the HTML (i.e. makes it so that the HTML part is set up as is)
function renderCell($r, $c, $puzzle, $current, $conflicts) {
    $val = $current[$r][$c];
    $isFixed = $puzzle[$r][$c] !== 0;

    // The cells
    $classes = ['cell'];
    if ($isFixed) $classes[] = 'fixed';
    if ($val === 0) $classes[] = 'empty';
    if (isset($conflicts["$r-$c"])) $classes[] = 'conflict';

    // Sub-grid borders
    if ($c % 3 === 0) $classes[] = 'box-left';
    if ($r % 3 === 0) $classes[] = 'box-top';
    if ($c === 8) $classes[] = 'box-right';
    if ($r === 8) $classes[] = 'box-bottom';
    $name = "cell_{$r}_{$c}";
    if ($isFixed) {
        return '<td class="'.implode(' ', $classes).'"><div class="fixed-num">'.htmlspecialchars($val).'</div></td>';
    } else {
        $display = $val === 0 ? '' : htmlspecialchars((string)$val);
        return '<td class="'.implode(' ', $classes).'"><input type="text" inputmode="numeric" pattern="[1-9]" maxlength="1" name="'.$name.'" value="'.$display.'" /></td>';
    }
}


$puzzle    = $_SESSION['puzzle'];
$current   = $_SESSION['current'];
$message   = $_SESSION['message'];
$conflicts = $_SESSION['conflicts'];
// This is where the headaches end, for the two of us.
// I haven't implemented a way to make it a multiplayer game yet, I don't know how to do that part - I'm sure you can figure that out.
// If you can't we'll just use it as is and think of a mechanic later.
?>

<!DOCTYPE html>
<!-- To: Frontend members
     From: D-nice's lap dog

     All you have to do is change this part, ignore everything above "<!DOCTYPE html>", that's none of your concern.
     I don't think you need guidance from this point on as HTML/CSS is relatively straightforward, that and I trust your capabilities.
     I (HIGHLY) suggest that you create a copy of this sudoku file before you make any major changes.

     Best of luck to all of you :)
-->

<!-- Rearranged everything and made the ui be like my base ui - Dennice -->
<html lang="en">
<head>
<meta charset="UTF-8">
<title>(Cool name) Sudoku</title>
<style>
:root {
    --cream: #FFFBCD;
    --orange: #F4993E; 
    --blue: #53CBED;
    --blue-dark: #105C90;
    --cell-light: #FFFFFF;
    --cell-dark: #FFFAEC;
    --border-color-thick: #DA8534;
    --text-color: #333333;
}

body {
    margin: 0;
    padding: 0;
    background: radial-gradient(circle at 50% 50%, var(--cream) 0%, #FFF3D6 100%);
    font-family: 'Fredoka One', 'Comic Sans MS', cursive, sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--text-color);
    min-height: 90vh;
}

.container {
    padding: 30px;
    background: white;
    border-radius: 25px;
    border: 5px solid var(--orange);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15), 0 0 0 10px rgba(244,153,62,0.1);
    width: 95vw;
    max-width: 900px;
    position: relative;
}

h1 {
    text-align: center;
    color: var(--orange);
    font-size: 2.2em;
    margin-bottom: 10px;
}

#timer {
    font-size: 22px;
    text-align: center;
    font-weight: bold;
    color: var(--blue-dark);
    margin-bottom: 10px;
}

.controls {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.btn {
    padding: 10px 16px;
    border-radius: 14px;
    font-size: 1.1em;
    font-weight: bold;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 0 rgba(0,0,0,0.2);
    transition: transform 0.2s ease-in,
                box-shadow 0.2s ease-in,
                background-color 0.2s ease-in;
}

.btn-primary {
    background-color: var(--blue);
    color: white;
    box-shadow: 0 4px 0 var(--blue-dark);
}

.btn-danger {
    background-color: var(--orange);
    color: white;
    box-shadow: 0 4px 0 #CD7C2A;
}

.btn:hover {
    transform: translateY(-2px);
}

.message {
    text-align: center;
    background: var(--blue);
    padding: 12px;
    border-radius: 14px;
    color: white;
    font-size: 1.1em;
    margin-bottom: 10px;
}

table.sudoku {
    margin: 0 auto;
    border-collapse: collapse;
    background-color: var(--border-color-thick);
    padding: 8px;
    border-radius: 18px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.2);
}

table.sudoku td {
    width: 65px;
    height: 65px;
    text-align: center;
    border: 1px solid var(--border-color-thick);
    border-radius: 10px;
    overflow: hidden;
    background: var(--cell-light);
    font-size: 28px;
    font-weight: bold;
}

table.sudoku td:nth-child(odd) {
    background: var(--cell-light);
}
table.sudoku td:nth-child(even) {
    background: var(--cell-dark);
}

.fixed {
    background-color: #F7B974  !important;
    color: white;
    font-weight: bold;
}

.fixed-num {
    line-height: 65px;
    font-size: 26px;
}

table.sudoku input {
    width: 100%;
    height: 100%;
    border: none;
    background: none;
    font-size: 28px;
    text-align: center;
    outline: none;
    font-weight: bold;
    color: var(--orange);
}


.conflict input {
    background-color: #ffdddd !important;
    color: red;
    border: 2px solid red;
}

.box-top    { border-top: 4px solid var(--border-color-thick) !important; }
.box-bottom { border-bottom: 4px solid var(--border-color-thick) !important; }
.box-left   { border-left: 4px solid var(--border-color-thick) !important; }
.box-right  { border-right: 4px solid var(--border-color-thick) !important; }

.footer {
    margin-top: 20px;
    text-align: center;
    font-size: 0.9em;
}

/*instructions tab*/

.help-icon {
    position: absolute;
    top: 15px;
    right: 20px;
    width: 35px;
    height: 35px;
    background: var(--orange);
    color: white;
    border-radius: 50%;
    font-size: 22px;
    font-weight: bold;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    box-shadow: 0 4px 0 #CD7C2A;
    transition: 0.2s;
}

.help-icon:hover {
    transform: translateY(-2px);
}

.menu-icon {
    position: absolute;
    top: 15px;
    left: 20px;
    width: 35px;
    height: 35px;
    background: var(--orange);
    color: white;
    border-radius: 50%;
    font-size: 22px;
    font-weight: bold;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    box-shadow: 0 4px 0 #CD7C2A;
    transition: 0.2s;
}

.menu-icon:hover {
    transform: translateY(-2px);
}

/*intructions screen*/
.hidden {
    display: none;
}

#instructions {
    text-align: center;
    z-index: 10;
    position: relative;
}

@import url('https://fonts.googleapis.com/css2?family=Exo+2:wght@300;400;500;600;700&display=swap');

.title {
    font-family: 'Exo 2', system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
    font-size: 3.5rem;
    font-weight: bold;
    color: #105B8F;
    letter-spacing: 8px;
    gap: 15px;
    margin-bottom: 50px;
    text-shadow: -3px 2px 0 #ffffff;
}

.instructions-text {
    background: #FFFFF5;
    border: 4px solid #8CF1E5;
    outline: 4px solid #f2993e;
    color: #105B8F;
    font-size: 1.2rem;
    font-weight: 500;
    line-height: 1.8;
    padding: 40px 50px;
    border-radius: 12px;
    max-width: 600px;
    margin: 40px auto 60px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.instructions-text i {
    color: #f2993e;
    font-style: italic;
    font-weight: 600;
}

/* Back Button */
.back-button {
    background: #f2993e;
    color: #f5e6d3;
    border: none;
    padding: 15px 60px;
    font-size: 1.3rem;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 3px;
    border-radius: 8px;
    cursor: pointer;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
    transition: all 0.3s ease;
}

.back-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
    background: #d9822f;    
}

.back-button:active {
    transform: translateY(-1px);
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.3);
}

#info {
    display: flex;
    justify-content: center;
    gap: 8vw;
    margin: 15px 0;
    align-items: center;
}

strong {
    text-align: center;
}

</style>
</head>

<body>

<audio id="bg-music" src="hope-cinematic-loop-273335.mp3" autoplay loop></audio>

<div class="container">
    <div class="help-icon" onclick="showInstructions()">?</div>

    <div class="help-icon" onclick="toggleSound()"
    style="right: 70px;"
    >🕪</div>
    
    <!-- fullscreen -->
    <div class="help-icon" onclick="toggleFullscreen()"
    style="left: 70px;"
    >🗖</div>

    

<div class="footer">
    <div onclick="goBackHome()" class="menu-icon">☰</div>
    
</div>

    <h1>Sudoku Clash</h1>

<!-- 2p wowowowwow amazing, it's 2:56am -->
<div id="info">
    
    <strong 
        id="player1-btn" class="btn btn-primary" style="min-width: 100px;">
        Player 1<br><span id="player1-display">0</span> pts
</strong>

    <div id="timer"
         data-start="<?php echo htmlspecialchars((string)$_SESSION['start_ms']); ?>"
         data-solved="<?php echo !empty($_SESSION['solved']) ? '1' : '0'; ?>">
      Time: 00:00
    </div>

    <strong
    id="player2-btn" class="btn" style="
    min-width: 100px;
    background-color: var(--orange);">
        Player 2<br><span id="player2-display">0</span> pts
    </strong>
    
    </div>

    <form method="post">
        <div class="controls">
            <label>Clues:
                <input type="number" name="clues" min="17" max="81"
                       value="<?php echo isset($_POST['clues']) ? intval($_POST['clues']) : 35; ?>">
            </label>
            <button class="btn btn-primary" type="submit" name="action" value="new">New Game</button>
            <button class="btn" type="submit" name="action" value="check">Check</button>
            <button class="btn btn-danger" type="submit" name="action" value="solve">Solve</button>
        </div>

        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <table class="sudoku">
            <tbody>
            <?php $conflicts = isset($_SESSION['conflicts']) ? $_SESSION['conflicts'] : []; ?>
            <?php for ($r = 0; $r < 9; $r++): ?>
                <tr>
                <?php for ($c = 0; $c < 9; $c++): ?>
                    <?php echo renderCell($r, $c, $puzzle, $current, $conflicts); ?>
                <?php endfor; ?>
                </tr>
            <?php endfor; ?>
            </tbody>
        </table>
    </form>

</div>

<div id="instructions" class="hidden screen">
    <div class="title"> How to play? </div>

    <div class="instructions-text">                    
        1. Type a number from 1 – 9 in the empty cells. <br>
        2. Use the <i>“Check”</i> button to detect conflicts.<br>
        <i>*Note: the check feature only checks for conflicts in cells that are currently filled.</i><br>
        <i>**Note: the difficulty you set is only a target, the actual clue count may vary.</i><br>
        <i>***Note: before you ask, yes - all of the generated puzzles are solvable.</i>
    </div>

    <button onclick="closeInstructions()" class="back-button"> START </button>
</div>

<!-- this plays the music pls dont touch uwu -->
<audio id="bg-music" autoplay loop>
    <source src="asset/bg-music.mp3" type="audio/mpeg">
</audio>




<!-- This is a timer, try to keep it as is. 
     It keeps breaking whenever I touch it. -->
<script>
(function(){
  const timerEl = document.getElementById('timer');
  const startMs = parseInt(timerEl.dataset.start || '0', 10);
  const solved  = timerEl.dataset.solved === '1';
  let timerInterval = null;

  function updateTimer() {
    const now = Date.now();
    const elapsed = Math.max(0, Math.floor((now - startMs) / 1000));
    const minutes = Math.floor(elapsed / 60);
    const seconds = elapsed % 60;
    timerEl.textContent = "Time: " +
      String(minutes).padStart(2,'0') + ":" +
      String(seconds).padStart(2,'0');
  }

  updateTimer();
  if (!solved) timerInterval = setInterval(updateTimer, 1000);
})();


function goBackHome() {
    window.location.href = "menu.php";
}

function showInstructions() {
    document.querySelector('.container').style.display = 'none';
    document.getElementById('instructions').classList.remove('hidden');
}

function closeInstructions() {
    document.getElementById('instructions').classList.add('hidden');
    document.querySelector('.container').style.display = 'block';
}

// I HATE PHP IM GONNA DO IT IN JS INSTEAD FUAH

let currentPlayer = 1; // dictates which player goes first

// default scores
let player1Score = 0;
let player2Score = 0;

// default owned cells
let ownedCells = {};

function handlePlayerMove(row, col, value) {
    if (!value) return;
    
    const cellKey = row + '-' + col;
    
    // this cehcks if cell is already owned
    if (!ownedCells[cellKey]) {
        // marks cell as owned
        ownedCells[cellKey] = currentPlayer;
        
        // adds points
        if (currentPlayer === 1) {
            player1Score += 10;
        } else {
            player2Score += 10;
        }
        
        // colors the cell
        const cellInput = document.querySelector(`input[name="cell_${row}_${col}"]`);
        if (cellInput) {
            const cell = cellInput.parentElement;
            cell.classList.add(currentPlayer === 1 ? 'owner-1' : 'owner-2');
        }
        
        document.getElementById('player1-display').textContent = player1Score;

        document.getElementById('player2-display').textContent = player2Score;

        document.getElementById('player1-btn').className = currentPlayer === 1 ? 'btn btn-primary' : 'btn';
        document.getElementById('player2-btn').className = currentPlayer === 2 ? 'btn btn-primary' : 'btn';
                                        // memento mori 'ACTIVE' : 'NOT ACTIVE'

        // swithces turns
        currentPlayer = currentPlayer === 1 ? 2 : 1;
    }
}

// Attach event listeners when page loads
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('table.sudoku input[type="text"]');
    inputs.forEach(input => {
        // Get row and column from input name
        const match = input.name.match(/cell_(\d+)_(\d+)/);
        if (match) {
            const row = parseInt(match[1]);
            const col = parseInt(match[2]);
            
            // Add event listener
            input.addEventListener('change', function() {
                handlePlayerMove(row, col, this.value);
            });
        }
    });
});

function toggleFullscreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen();
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
    }
}

// Auto-play the music when page loads
window.onload = function() {
    const music = document.getElementById('bg-music');
    music.play().catch(e => {
        console.log("Auto-play blocked by browser");
        // User will need to click manually
    });
};

function toggleSound() {
    const music = document.getElementById('bg-music');
    if (music.paused) {
        music.play();
    } else {
        music.pause();
    }
}

</script>

</body>
</html>