<!DOCTYPE html>
<html>
<head>
    <title>bbclock - Home</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/core.css">
    <link rel="stylesheet" href="css/punch.css">
    <link rel="stylesheet" href="css/calendar.css">

    <!-- Javascript -->
    <script src="js/date.js"></script>
    <script src="js/core.js"></script>
    <script src="js/calendar.js"></script>

    <script src="js/bbclock.js"></script>
    <script src="js/home.js"></script>
</head>
<body>
    <div class="container">
        <nav closed>
            <span class="brand">bbclock</span>
            <div class="items">
                <a href="../bbclock/" active>Home</a>
                <?php
                    $year = date('Y');
                    for($i = $year; $i >= 2018; $i--) {
                        echo "<a href=\"yearly.php?year=$i\">$i Punches</a>";
                    }
                ?>
                <?php
                    $year = date('Y');
                    for($i = $year; $i >= 2018; $i--) {
                        echo "<a href=\"report.php?year=$i\">$i Report</a>";
                    }
                ?>
            </div>
        </nav>
        <main>
            <div class="calendar"></div>
            <div class="punch-container"></div>
        </main>
    </div>
</body>
</html>