<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
"http://www.w3.org/TR/html4/strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Scoreboard</title>
		<meta name="author" content="root" />
		  <link type="text/css" rel="stylesheet" href="style.css" />
	</head>
	<body>

<!-- Google Analytics Code-->    
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-57061804-1', 'auto');
  ga('send', 'pageview');

</script>

<!--banner and menu-->    
   <?php 
$banner = '/var/www/ppDocs/banner-and-menu.html';
$data = file($banner) or die('Could not read file!');
foreach ($data as $line) {
echo "$line";
}
    ?>  

<table class="buckets">

	<td class="bucket1"><center><b>Scoreboard</b></center><hr>
  	<h4>PlayoffPredictor.com scoreboaard</h4>




 <p> Simple page to print out scores in format needed for my excel sheet - note seasonYear is hardset - tofix </p>

<table> 
<?php
$fbsTeamNames = [];

#read in FBS team names
$seasonYear = 2024;
$fbsTeams = "/home/neville/cfbPlayoffPredictor/data/$seasonYear/fbsTeamNames.txt";
$data = file($fbsTeams) or die('Could not read file!');

$i=0;
foreach ($data as $line) {
$lineIn = explode(" => ", $line);
array_push($fbsTeamNames, $lineIn[0]);
#echo "$fbsTeamNames[1]\n";
}
#echo "$fbsTeamNames[127]\n";
#var_dump($fbsTeamNames);

$file = '/home/neville/cfbPlayoffPredictor/data/current/ncfScoresFile.txt';
$data = file($file) or die('Could not read file!');

$i=0;
foreach ($data as $line) {
$lineIn = explode(":", $line);
$week = $lineIn[0];
$status = trim($lineIn[1]);
$teams = explode(" - ", $lineIn[2]);	

$away = $teams[0];
$home = $teams[1]; 

$awayTeam = trim(preg_replace('/\d+$/', '', $away));
$awayScore = preg_replace('/^\D+/', '', $away);

$homeTeam = trim(preg_replace('/\d+$/', '', $home));
$homeScore = preg_replace('/^\D+/', '', $home);

if (in_array($awayTeam, $fbsTeamNames)) {
#do nothing
} else {
	$awayTeam = "1AA";
}

if (in_array($homeTeam, $fbsTeamNames)) {
	#do nothing
	} else {
		$homeTeam = "1AA";
	}

echo "<tr>";
echo "<td>$week</td><td>$status</td><td>$awayTeam</td><td>$awayScore</td><td>$homeTeam</td><td>$homeScore</td>";
echo "</tr>";



}

# Now do spread data
echo "<br><br>";

// Iterate through weeks 1 to 17
for ($week = 1; $week <= 17; $week++) {
    $file = "/home/neville/cfbPlayoffPredictor/data/2024/week{$week}/ncfSpreads.txt";

    // Check if the file exists
    if (file_exists($file)) {
        $data = file($file);

        $i = 0;
        foreach ($data as $line) {
            // Skip lines starting with #
            if (strpos($line, '#') === 0) {
                continue;
            }

            $lineIn = explode(":", $line);
            $year = $lineIn[0];
			$weekNum = $lineIn[1];
            $id = $lineIn[2];
            $awayTeam = $lineIn[3];
            $homeTeam = $lineIn[4];
            $spread = $lineIn[5];
            $overUnder = $lineIn[6];
            $provider = $lineIn[7];

            // Check if the team is an FBS team
            if (in_array($awayTeam, $fbsTeamNames)) {
                // do nothing
            } else {
                $awayTeam = "1AA";
            }

            if (in_array($homeTeam, $fbsTeamNames)) {
                // do nothing
            } else {
                $homeTeam = "1AA";
            }

            echo "<tr>";
            echo "<td>$weekNum</td><td>$id</td><td>$awayTeam</td><td>$homeTeam</td><td>$spread</td><td>$overUnder</td><td>$provider</td>";
            echo "</tr>";
        }
    } else {
        // File does not exist for the current week, continue with the next iteration
        continue;
    }
}



?>




	</body>
</html>

