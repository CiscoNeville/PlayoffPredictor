<!-- ATSPerformanceTracker.php
Inputs:
 ?app=yes		--- format for iOS app, otherwise for web browser 
 ?week=yes  	--- Mandatory
 ?year=N  		--- Mandatory
-->


<?php

$useApp = $_GET["app"];
$week = $_GET["week"];
$year = $_GET["year"];

// The previous week is last week, except for bowl week (week 17) where previous is 15
$previousWeek = $week -1;
if ($week == 17) {$previousWeek = 15;}


// Function to read and parse the spread data file
function readSpreadData($filename, $week) {
    $spreads = [];
    $lines = file($filename, FILE_IGNORE_NEW_LINES);
	// 2024:week 10:401636915:Arizona State:Oklahoma State:3:57.5:Bovada - ncf Spreads example line
    foreach ($lines as $line) {
		// Skip lines that start with #
        $trimmedLine = trim($line);
        if ($trimmedLine !== '' && $trimmedLine[0] === '#') {
            continue;
        }

        $parts = explode(':', $line);
        if (count($parts) >= 7) {
			$cfbd_week = $parts[1];  
			if ($week == 17) {$cfbd_week = "week 17";}  //cfbd returns week 1 for bowl week -- need to rewrite it to 17
            $key = $parts[0] . ':' . $cfbd_week . ':' . $parts[3] . ':' . $parts[4];  //making a key of "2024:week10:Arizona State:Oklahoma State"
            $spreads[$key] = floatval($parts[5]);  //making $spreads["2024:week10:Arizona State:Oklahoma State"] = 3    . Certainly could do it as $spreads[401636915] = 3
        }
    }
    return $spreads;
}


// Function to read and parse the ratings data file
// done with with ATS ratings
function readATSRatingsData($filename) {
    $ratings = [];
    $lines = file($filename, FILE_IGNORE_NEW_LINES);
	// Vanderbilt:0.6104:3-2 -  week n-1 calculated ratings example line
    foreach ($lines as $line) {
        $parts = explode(':', $line);
		$team = $parts[0];
        $ratings[$team] = $parts[1];
    }
    return $ratings;
}



// Function to extract score and team from a part
function extractTeamAndScore($part) {
    $part = trim($part);
    // Find the number (score) in the string
    if (preg_match('/(\d+)/', $part, $matches)) {
        $score = (int)$matches[0];
        // Remove the score to get team name, then trim
        $team = trim(preg_replace('/\d+/', '', $part));
        return [$team, $score];
    }
    return [null, null];
}

// Function to read the scores to this point in the week
function readScoresData($filename) {
    $scores = [];
    $lines = file($filename, FILE_IGNORE_NEW_LINES);
    
    // week 9: Final : Auburn 24 - Kentucky 10 - example line
    foreach ($lines as $line) {
        $parts = explode(':', $line);
        $weekPlayed = $parts[0];
        $scoreLine = $parts[2];  // Fixed variable name case
        
        $teams = explode("-", trim($scoreLine));
        
        // Get away team info
        list($awayTeam, $awayScore) = extractTeamAndScore($teams[0]);
        // Get home team info
        list($homeTeam, $homeScore) = extractTeamAndScore($teams[1]);
        
        // Calculate differential from home team perspective
        $scores["$awayTeam:$homeTeam"] = $awayScore - $homeScore;
    }
    return $scores; 
}


// Function to determine the bet based on the spread and prediction
function determineBet($spread, $prediction) {
	$threshold = 0.5; // You can adjust this value

	// Determine if Home or Away is favored
	$homeIsFavored = $spread < 0;

	// Calculate the absolute difference between prediction and spread
	$difference = abs($prediction - $spread);
	if ($difference >= $threshold) {
	if ($homeIsFavored) {
	// Home team is favored
	return ($prediction > $spread) ? 'Away' : 'Home';
	 } else {
	// Away team is favored
	return ($prediction < $spread) ? 'Home' : 'Away';
	 }
	 }
	return 'No Pick';
	}





$winningBets = 0;
$losingBets = 0;
$betWL = "";

// Function to check if the bet was successful
function isBetSuccessful($spread, $actual, $betOn) {
    global $winningBets, $losingBets, $betWL;  // Declare access to global variables
	$betWL = "";  //reset to blank at start of function
    
    // Skip counting if no bet or game not played
    if ($betOn == 'No Pick' || $actual == 'Not Played') {
		return false;
    }
    
    $isSuccess = false;
    if ($betOn == 'Home') {
        $isSuccess = $actual < $spread;
    } elseif ($betOn == 'Away') {
        $isSuccess = $actual > $spread;
    }
    
    // Count the result
    if ($isSuccess) {
        $winningBets++;
		$betWL = "W";
    } else {
        $losingBets++;
		$betWL = "L";
    }
    
    return $isSuccess;
}


$straightUpWin = 0;
$straightUpLoss = 0;
$straightUpRight = "";

// Function to check if the bet picked the correct winning team straight up
function isBetStraightUpRight($prediction, $actual) {
	global $straightUpWin, $straightUpLoss, $straightUpRight;
	$straightUpRight = "";

    // Skip counting if game not played
    if ($actual == 'Not Played') {
		return false;
    }

    // Compare if both numbers are positive or both are negative
    if (($prediction > 0 && $actual > 0) || ($prediction < 0 && $actual < 0)) {
        $straightUpWin++;
		$straightUpRight = "W";
    } else {
        $straightUpLoss++;
		$straightUpRight = "L";
    }

}

//Function to compute the error in completed games
function computeError($prediction, $actual, $spread) {
	global $totalError;
	    
	// Skip counting if game not played
	if ($actual == 'Not Played') {
		return false;
	}

	$error = abs($prediction - $actual);
	$totalError = $totalError + $error;
	return $error;
}

//Function to compute Vegas total error in completed games
function computeVegasError($actual, $spread) {
	global $totalVegasError;
	    
	// Skip counting if game not played
	if ($actual == 'Not Played') {
		return false;
	}

	$vegasError = abs($spread - $actual);
	$totalVegasError = $totalVegasError + $vegasError;
	return $vegasError;
}



//Function to determine what scorefile to use (current or historical)
function getScoresFilePath($year, $week) {
    // Base path for all score files
    $basePath = "/home/neville/cfbPlayoffPredictor/data";
    
    // Get current server year and month
    $currentYear = (int)date('Y');
    $currentMonth = (int)date('n');

    // If it's January or February, adjust the current year to previous year
    if ($currentMonth <= 2) {
        $currentYear--;
    }
    
    // Convert $year to integer for comparison
    $year = (int)$year;
    
    // Determine which file path to use
    if ($year === $currentYear) {
        // Use current scores file
        return "{$basePath}/current/ncfScoresFile.txt";
    } else {
        // Use archived scores file from specific year and week
        return "{$basePath}/{$year}/week{$week}/Week{$week}NcfScoresFile.txt";
    }
}



//Function to get model prediction of score differential from probabilities. Map visiting team win probability to point spread
function getClosest($prob) {
$spread = array(
	"0" => -100,
	"0.001" => -40,
	"0.003" => -39,
	"0.006" => -38,
	"0.01" => -37,
	"0.012" => -36,
	"0.014" => -35,
	"0.016" => -34,
	"0.018" => -33,
	"0.02" => -32,
	"0.022" => -31,
	"0.025" => -30,
	"0.027" => -29,
	"0.029" => -28,
	"0.032" => -27,
	"0.034" => -26,
	"0.036" => -25,
	"0.038" => -24,
	"0.045" => -23,
	"0.051" => -22,
	"0.053" => -21,
	"0.06" => -20,
	"0.063" => -19,
	"0.086" => -18,
	"0.11" => -17,
	"0.115" => -16,
	"0.126" => -15,
	"0.149" => -14.5,
	"0.165" => -14,
	"0.17" => -13.5,
	"0.174" => -13,
	"0.184" => -12.5,
	"0.194" => -12,
	"0.201" => -11.5,
	"0.208" => -11,
	"0.226" => -10.5,
	"0.245" => -10,
	"0.25" => -9.5,
	"0.254" => -9,
	"0.262" => -8.5,
	"0.27" => -8,
	"0.297" => -7.5,
	"0.323" => -7,
	"0.336" => -6.5,
	"0.349" => -6,
	"0.359" => -5.5,
	"0.369" => -5,
	"0.381" => -4.5,
	"0.394" => -4,
	"0.426" => -3.5,
	"0.458" => -3,
	"0.466" => -2.5,
	"0.475" => -2,
	"0.488" => -1.5,
	"0.495" => -1,
	"0.4999" => -0.5,
	"0.5001" => 0,
	"0.505" => "+0.5",
	"0.512" => "+1",
	"0.525" => "+1.5",
	"0.534" => "+2",
	"0.542" => "+2.5",
	"0.574" => "+3",
	"0.606" => "+3.5",
	"0.619" => "+4",
	"0.631" => "+4.5",
	"0.641" => "+5",
	"0.651" => "+5.5",
	"0.664" => "+6",
	"0.677" => "+6.5",
	"0.703" => "+7",
	"0.73" => "+7.5",
	"0.738" => "+8",
	"0.746" => "+8.5",
	"0.75" => "+9",
	"0.755" => "+9.5",
	"0.774" => "+10",
	"0.792" => "+10.5",
	"0.799" => "+11",
	"0.806" => "+11.5",
	"0.816" => "+12",
	"0.826" => "+12.5",
	"0.83" => "+13",
	"0.835" => "+13.5",
	"0.851" => "+14",
	"0.874" => "+14.5",
	"0.885" => "+15",
	"0.89" => "+16",
	"0.914" => "+17",
	"0.937" => "+18",
	"0.94" => "+19",
	"0.947" => "+20",
	"0.949" => "+21",
	"0.955" => "+22",
	"0.962" => "+23",
	"0.964" => "+24",
	"0.966" => "+25",
	"0.968" => "+26",
	"0.971" => "+27",
	"0.973" => "+28",
	"0.975" => "+29",
	"0.978" => "+30",
	"0.98" => "+31",
	"0.982" => "+32",
	"0.984" => "+33",
	"0.986" => "+34",
	"0.988" => "+35",
	"0.99" => "+36",
	"0.994" => "+37",
	"0.997" => "+38",
	"0.999" => "+39",
	"1.0" => "+40",
);

	$closestKey = null;
	$closestDiff = PHP_FLOAT_MAX;

	foreach ($spread as $key => $value) {
		$diff = abs($prob - floatval($key));
		if ($diff < $closestDiff) {
			$closestDiff = $diff;
			$closestKey = $key;
		}
	}
	return $spread[strval($closestKey)];
}


//Function to calculate Error improvement over Vegas
function calculateVegasGain($spread, $prediction, $actual) {
    // Check if actual is blank, "Not Played", or not numeric
    if ($actual === "" || $actual === "Not Played" || !is_numeric($actual)) {
        return "";
    }

    // Convert all inputs to floats to ensure proper calculation
    $spread = floatval($spread);
    $prediction = floatval($prediction);
    $actual = floatval($actual);
    
    // Calculate the absolute difference between prediction and actual
    $predictionError = abs($prediction - $actual);
    
    // Calculate the absolute difference between spread and actual
    $spreadError = abs($spread - $actual);
    
    // If prediction is closer to actual than spread (smaller error),
    // return the difference as a positive number
    // If spread is closer (or equal), return the difference as a negative number
    $vegasGain = $spreadError - $predictionError;
    
    // Round to 1 decimal place
    return round($vegasGain, 1);
}






#Read in team name to icon mapping
$teamIconsFile = '/home/neville/cfbPlayoffPredictor/data/teamIcons.txt';
$data = file_get_contents("$teamIconsFile");
$lineFromText = explode("\n", $data);
global $teamIcon;  // Declare it global
$teamIcon = array();

foreach($lineFromText as $line) {
    $line = trim($line);
    
    // Skip empty lines and comments
    if (empty($line) || substr($line, 0, 1) == '#') {
        continue;
    }
    
    $iconData = explode(' => ', $line);
    if (count($iconData) == 2) {
        $teamIcon[$iconData[0]] = $iconData[1];
    }
}

function getTeamIcon($teamName, $default = 'NCAA') {
    global $teamIcon;  // Access the global array inside the function
    return isset($teamIcon[$teamName]) ? $teamIcon[$teamName] : $default;
}

// Usage example:
// $icon = getTeamIcon("Wyoming");  // Returns "Wyo"
// $icon = getTeamIcon("Some FCS Team");  // Returns "NCAA"




?>
















<!-- Output the HTML  -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PlayoffPredictor.com ATS Betting Performance Tracker</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid black; padding: 8px; text-align: center; }
        .success { background-color: #90EE90; }
        .failure { background-color: #FFCCCB; }
		.notPlayed { background-color: #EEEEEE; }
    </style>
	<link type="text/css" rel="stylesheet" href="style.css" />
	<script src="js/table-sort.js"></script>
	<script src="js/select-year-week.js"></script>
</head>
<body>


<!--banner and menu-->    
<?php 
if($useApp != 'true') {                #do not show this banner if called in the app
$banner = '/var/www/ppDocs/banner-and-menu.html';
$data = file($banner) or die('Could not read file!');
foreach ($data as $line) {
echo "$line";
}
}
?>


<div class="tracker-header">
    <h1>Betting Performance Tracker</h1>
    <div class="selector-container">
        <label>Year
            <select id="yearSelect" class="custom-select"></select>
        </label>
        <label>Week
            <select id="weekSelect" class="custom-select"></select>
        </label>
    </div>
    <div id="stats-container">
		<!-- javascript will move stuff here after calcs are complete -->
    </div>
</div>




    <p>
        <?php
        $totalGames = 0;
        $correctPicks = 0;
        $atsPicks = 0;
        $totalError = 0;
		$totalVegasError = 0;
        
        ?>
    </p>
    
	<div class="table-container">
    <table class="sortable">
		<thead>
			<tr style="background-color: #FFFACD;">
            	<th>Year</th>
            	<th>Week</th>
            	<th>Away Team</th>
            	<th>Home Team</th>
            	<th>Spread</th>
            	<th>Prediction</th>
            	<th>ATS Model Pick</th>
            	<th>Actual</th>
				<th>Error</th>
				<th>Vegas Gain</th>
				<th>Bet W/L</th>
				<th>SU W/L</th>
			</tr>
        </thead>

		<tbody>

<?php
// Main loop to process game data

// Read the spread data
$spreads = readSpreadData("/home/neville/cfbPlayoffPredictor/data/$year/week$week/ncfSpreads.txt", $week);
#after this, $spreads['2024:week 8:UCF:Iowa State'] = -13.5,  the actual game spread, for example


// Read in current calculated ATS ratings, so can see elo winning probabilities
$lastWeekCalculatedRatingsFile = "/home/neville/cfbPlayoffPredictor/data/$year/week{$previousWeek}/Week{$previousWeek}CalculatedRatingsATS.txt";
$ratings = readATSRatingsData("$lastWeekCalculatedRatingsFile");
#after this, $ratings['UCF'] = .754, for example



// Read in current scores, filter to current week
$ncfScoresFile = getScoresFilePath($year, $week);
$scores = readScoresData("$ncfScoresFile");
#echo "ncfScoresFile is $ncfScoresFile<br>";
#after this $scores['UCF:Iowa State'] = -17, meaning Iowa State was at home and they won by 17 points.  Since I don't have the gameId in that file, just using the home team as the key


#echo "Spread [2024:week 10:Kennesaw State:Western Kentucky] is: " . $spreads['2024:week 10:Kennesaw State:Western Kentucky'] . "<br>";
#echo "Rating [Western Kentucky] is: " . $ratings['Western Kentucky'] . "<br>";
#echo "Rating [Kennesaw State] is: " . $ratings['Kennesaw State'] . "<br>";
#echo "Score [Kennesaw State:Western Kentucky] is: " . $scores['Kennesaw State:Western Kentucky'] . "<br>";



$divisor = 1;  #base = 1000 divisor =1
$homeFieldAdvantageNumber = 0.05;  # need to add neutral site games #todo

$i=0;
foreach ($spreads as $key => $spread) {
	// Parse the key components
	list($year, $weekName, $awayTeam, $homeTeam) = explode(':', $key);   #$weekName would be "week 10"
    
	// Get team ratings
	$awayTeamRating = isset($ratings[$awayTeam]) ? $ratings[$awayTeam] : 'N/A';
	$homeTeamRating = isset($ratings[$homeTeam]) ? $ratings[$homeTeam] : 'N/A';
   
	// Check for score
	$score = 'Not Played';
	if (isset($scores["$awayTeam:$homeTeam"])) {
		$score = $scores["$awayTeam:$homeTeam"];
	}

	#echo "away team is ---$awayTeam--- and home team is ---$homeTeam---<br>";
	#echo "week is $week  <br>";
	#echo "teamRating of Alabama is $teamRating[Alabama]  <br>";


	$probA = 1/(1+(1000**(($awayTeamRating-(($homeTeamRating)+$homeFieldAdvantageNumber))/$divisor)));
	$probB = 1/(1+(1000**((($homeTeamRating+$homeFieldAdvantageNumber)-$awayTeamRating)/$divisor)));
	#note these are exponenets, so on week 1 everyone will have 50% chance to win, regarless of rating.

	#get prediction 
	$prediction = getClosest($probB);

	$actual = $score;
	$formattedActual = ($actual != 'N/A') ? (($actual > 0) ? '+' . $actual : $actual) : 'N/A';    #always show + or - sign

	$spreadKey = "$year:week $week:$awayTeam:$homeTeam";
	$spread = isset($spreads[$spreadKey]) ? $spreads[$spreadKey] : 'N/A';
	$formattedSpread = ($spread != 'N/A') ? (($spread > 0) ? '+' . $spread : $spread) : 'N/A';    #Format the spread to always show the sign


	$betOn = determineBet($spread, $prediction);
	$betSuccessful = isBetSuccessful($spread, $actual, $betOn);
	$betStraightUp = isBetStraightUpRight($prediction, $actual);
	$error = computeError($prediction, $actual, $spread);
	$vegasError = computeVegasError($actual, $spread);
	$vegasGain = calculateVegasGain($spread, $prediction, $actual);
	
	$baseIconURL = 'https://sports.cbsimg.net/images/collegefootball/logos';
	$iconSize = '25x25';
	$homeIcon = getTeamIcon($homeTeam);
	$awayIcon = getTeamIcon($awayTeam);

	$rowClass = ($actual == 'Not Played') ? 'notPlayed' : 
	(($betOn != 'No Pick' && $betSuccessful) ? 'success' : 
	($betOn != 'No Pick' ? 'failure' : ''));

	echo "<tr class='$rowClass'>";
	echo "<td>$year</td>";
	echo "<td>$week</td>";
	echo "<td>$awayTeam</td>";
	echo "<td>$homeTeam</td>";
	echo "<td>$formattedSpread</td>";
	echo "<td>$prediction</td>";
	echo "<td style='vertical-align: middle;'>" . ($betOn == 'Home' ?    #ternary operator - is $betOn equal to Home ? ifTrue : ifFalse
    	($spread < 0 ? 
        	"<img src='{$baseIconURL}/{$iconSize}/{$homeIcon}.png' /> $homeTeam $spread": #$spread is already negative in this 
        	"<img src='{$baseIconURL}/{$iconSize}/{$homeIcon}.png' /> $homeTeam +".abs($spread)
    	) : 
    	($betOn == 'Away' ?
        ($spread < 0 ? 
			# handle the weel 1 FCS playing FBS and take the points but I don't want to show the default NCAA logo on the pick
			($awayIcon != "NCAA" 
				? "<img src='{$baseIconURL}/{$iconSize}/{$awayIcon}.png' /> " 
				: "") . 
			"$awayTeam +" . abs($spread) :
			"<img src='{$baseIconURL}/{$iconSize}/{$awayIcon}.png' /> $awayTeam -".abs($spread) 
        ) :
        	'No Pick')) . "</td>";
	echo "<td>$formattedActual</td>";
	echo "<td>$error</td>";
	echo "<td>$vegasGain</td>";
	echo "<td>$betWL</td>";
	echo "<td>$straightUpRight</td>";
	echo "</tr>";

}


// After the loop, you can display the results:

$totalGames = $winningBets + $losingBets;


echo '<div id="stats-source" class="stats">';
echo "<p>$totalGames games<br></p>";
echo "<p class=bold>$straightUpWin - " . ($straightUpLoss) . " Straight Up (" . number_format($straightUpWin / $totalGames, 3) . ")<br> </p>";
echo "<p class=bold>$winningBets - " . ($totalGames - $winningBets) . " ATS (" . number_format($winningBets / $totalGames, 2) . ")<br> </p>";
echo number_format($totalError / $totalGames, 2) . " MAE -- (" . number_format($totalVegasError / $totalGames, 2) . " Vegas MAE)";
echo '</div>';


?>



		</tbody>

    </table>
</div>


</body>
</html>