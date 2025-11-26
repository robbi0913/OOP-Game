<!DOCTYPE html>
<html>
<head>
    <title>SUDOKU CLASH</title>
<style>
    table, td {
        border: 2px solid;
    }
    td {
        font-size: 48pt;
        text-align: center;
        width: 48pt;
        height: 48pt;
    }
    .shaded {
        background-color: #aaa;
    }
</style>
</head>
<body>

<?php
$board = getPuzzle();

echo ("<table>\n");

for ($r = 0; $r < 9; $r++) {
    echo "<tr>";

    $rMiddle = ($r >= 3 && $r < 6);

    for ($c = 0; $c < 9; $c++) {
        $cMiddle = ($c >= 3 && $c < 6);

        $class = ($rMiddle xor $cMiddle) ? ' class="shaded"' : '';

        if ($board[$r][$c] == 0) {
            echo "<td$class contenteditable='true'></td>";
        } else {
            echo "<td$class>" . $board[$r][$c] . "</td>";
        }
    }

    echo "</tr>\n";
}

echo "</table>\n";


/* will generate a sudoku puzzle */
function getPuzzle() {
    $board = [];
    $cells = [];

    for ($r = 0; $r < 9; $r++) {
        $board[$r] = array(0,0,0,0,0,0,0,0,0);

        for ($c = 0; $c < 9; $c++) {
            $cells[] = array($r, $c);
        }
    }

    getASolution($board, 0, 0);

    // hidden solution :3
    for ($r = 0; $r < 9; $r++) {
        echo "<!-- " . implode(' ', $board[$r]) . " -->\n";
    }

    shuffle($cells);

    foreach ($cells as $cell) {
        $r = $cell[0];
        $c = $cell[1];

        $n = $board[$r][$c];
        $board[$r][$c] = 0;

        if (getNumSolutions($board, 0, 0) != 1) {
            $board[$r][$c] = $n;
        }
    }

    return $board;
}


/* finds a valid sudoku solution??? */
function getASolution(&$board, $r, $c) {
    if ($r == 9) {
        return true;
    }

    $nextC = ($c < 8) ? $c + 1 : 0;
    $nextR = ($nextC == 0) ? $r + 1 : $r;

    if ($board[$r][$c] > 0) {
        return getASolution($board, $nextR, $nextC);
    }

    $possibles = getPossibles($board, $r, $c);
    shuffle($possibles);

    foreach ($possibles as $possible) {
        if ($possible > 0) {
            $board[$r][$c] = $possible;

            if (getASolution($board, $nextR, $nextC)) {
                return true;
            }
        }
    }

    $board[$r][$c] = 0;
    return false;
}


/* will get possible values for cells */
function getPossibles($board, $r, $c) {
    $possible = array_fill(1, 9, true);

    // row
    for ($i = 0; $i < 9; $i++) {
        $possible[$board[$r][$i]] = false;
    }

    // column
    for ($i = 0; $i < 9; $i++) {
        $possible[$board[$i][$c]] = false;
    }

    // 3x3 block
    $r0 = floor($r / 3) * 3;
    $c0 = floor($c / 3) * 3;

    for ($i = 0; $i < 3; $i++) {
        for ($j = 0; $j < 3; $j++) {
            $possible[$board[$r0 + $i][$c0 + $j]] = false;
        }
    }

    $list = [];
    foreach ($possible as $num => $ok) {
        if ($ok) $list[] = $num;
    }

    return $list;
}


/*counts the solution*/
function getNumSolutions($board, $r, $c) {
    if ($r == 9) return 1;

    $nextC = ($c < 8) ? $c + 1 : 0;
    $nextR = ($nextC == 0) ? $r + 1 : $r;

    if ($board[$r][$c] > 0) {
        return getNumSolutions($board, $nextR, $nextC);
    }

    $count = 0;
    $possibles = getPossibles($board, $r, $c);

    foreach ($possibles as $n) {
        $board[$r][$c] = $n;
        $count += getNumSolutions($board, $nextR, $nextC);

        if ($count > 1) return $count;
    }

    return $count;
}

?>

</body>
</html>
