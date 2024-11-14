<!-- predictions_cfbd.php
output CSV on Tuesdays for predictions site-->


<?php
$week = $_GET["week"];
$year = $_GET["year"];

$previousWeek = $week -1;




// Function to read and parse the spread data file
function readSpreadData($filename) {
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
            $key = $parts[0] . ':' . $parts[1] . ':' . $parts[2] . ':' . $parts[3] . ':' . $parts[4];  //making a key of "2024:week10:401636915:Arizona State:Oklahoma State"
            $spreads[$key] = floatval($parts[5]);  //making $spreads["2024:week10:401636915:Arizona State:Oklahoma State"] = 3    . Certainly could do it as $spreads[401636915] = 3
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





?>
















<!-- Output the HTML  -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PlayoffPredictor.com ATS inputs in CSV for predictions.collegefootballdata.com</title>

	<link type="text/css" rel="stylesheet" href="style.css" />
</head>
<body>





    <p>

    </p>
    


<?php
// Main loop to process game data

// Read the spread data
$spreads = readSpreadData("/home/neville/cfbPlayoffPredictor/data/$year/week$week/ncfSpreads.txt");
#after this, $spreads['2024:week 8:4688834:UCF:Iowa State'] = -13.5,  the actual game spread, for example


// Read in current calculated ATS ratings, so can see elo winning probabilities
$lastWeekCalculatedRatingsFile = "/home/neville/cfbPlayoffPredictor/data/$year/week{$previousWeek}/Week{$previousWeek}CalculatedRatingsATS.txt";
$ratings = readATSRatingsData("$lastWeekCalculatedRatingsFile");
#after this, $ratings['UCF'] = .754, for example




$divisor = 1;  #base = 1000 divisor =1
$homeFieldAdvantageNumber = 0.05;  # need to add neutral site games #todo

$i=0;
foreach ($spreads as $key => $spread) {
	// Parse the key components
	list($year, $weekName, $gameID, $awayTeam, $homeTeam) = explode(':', $key);   #$weekName would be "week 10"
    
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



	echo "$gameID,$homeTeam,$awayTeam,$prediction <br>";
}



?>




</body>
</html>