<?php
?>

<!DOCTYPE html>
<head> 
    <html lang="en">
    <meta charset="UTF-8">
    <title> Menu </title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div id="homepage">

            <div class="floating-tile tile1">1</div>
            <div class="floating-tile tile2">2</div>
            <div class="floating-tile tile3">4</div>
            <div class="floating-tile tile4">7</div>
            <div class="floating-tile tile5">5</div>
            <div class="floating-tile tile6">9</div>

        <div class="title"> SUD <span class="logo-o"></span> KU CLASH </div>


        <div class="menu">
            <button onclick="startGame()" class="menu-button"> PLAY </button>
            <button onclick="showScreen('options')" class="menu-button"> OPTIONS </button>
            <button onclick="showScreen('about')" class="menu-button"> ABOUT </button>
        </div>
    </div>

    <div id="options" class="hidden screen">

        <div class="title"> OPTIONS </div>

        <div class="options-list">
            <div class="option-row">
                <div class="option-label"> GLOW COLOR </div>
                <button class="option-button"></button>
            </div>
            
            <div class="option-row">
                <div class="option-label"> SOUND </div>
                <button class="option-button sound"> </button>
            </div>
            
            <div class="option-row">
                <div class="option-label"> FULLSCREEN </div>
                <button class="option-button fullscreen"></button>
            </div>
        </div>

        <button onclick="goBackHome()" class="back-button"> Back </button>

    </div>

</body>

<script>
    function startGame() {
        window.location.href = "sudokutest.php";
    }

    function showScreen(screenId) {
        document.getElementById("homepage").style.display = "none";
        document.querySelectorAll(".screen").forEach(div => {
            div.style.display = "none";
        });

        document.getElementById(screenId).style.display = "block";
    }

    function goBackHome() {
        document.querySelectorAll(".screen").forEach(div => {
            div.style.display = "none";
        });
        document.getElementById("homepage").style.display = "block";
    }

    
</script>


</html>