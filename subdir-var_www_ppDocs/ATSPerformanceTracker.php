<?php
// Function to read and parse the spread data file
function readSpreadData($filename) {
    $spreads = [];
    $lines = file($filename, FILE_IGNORE_NEW_LINES);
    foreach ($lines as $line) {
        $parts = explode(':', $line);
        if (count($parts) >= 7) {
            $key = $parts[0] . ':' . $parts[1] . ':' . $parts[3] . ':' . $parts[4];
            $spreads[$key] = floatval($parts[5]);
        }
    }
    return $spreads;
}

// Function to read and parse the game data file
function readGameData($filename) {
    $games = [];
    $lines = file($filename, FILE_IGNORE_NEW_LINES);
    foreach ($lines as $line) {
        $parts = explode(':', $line);
        if (count($parts) == 2) {
            $week = trim($parts[0]);
            $teams = explode(' - ', $parts[1]);
            if (count($teams) == 2) {
                $games[] = [
                    'year' => '2024', // Assuming all games are for 2024 
                    'week' => '8',  //just pull 8 now
                    'awayTeam' => trim($teams[0]),
                    'homeTeam' => trim($teams[1]),
                    'prediction' => null, // We'll need to set this later
                    'actual' => null // We'll need to set this later
                ];
            }
        }
    }
    return $games;
}


// Function to determine the bet based on the spread and prediction
function determineBet($spread, $prediction) {
    $threshold = 0.5; // You can adjust this value
    if (abs($prediction - $spread) >= $threshold) {
        return ($prediction < $spread) ? 'Away' : 'Home';
    }
    return 'No Pick';
}

// Function to check if the bet was successful
function isBetSuccessful($spread, $actual, $betOn) {
    if ($betOn == 'Home') {
        return $actual < $spread;
    } elseif ($betOn == 'Away') {
        return $actual > $spread;
    }
    return false;
}

//Function to get model prediction of score differential
function getClosest($prob) {
	global $spread;      #required to make $spread available in this function, since $spread is defined outside the function.

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



// Read the spread data
$spreads = readSpreadData('/home/neville/cfbPlayoffPredictor/data/2024/week8/ncfSpreads.txt');


// Read in current calculated ratings, so can see elo winning probabilities
$currentCalculatedRatingsFile = '/home/neville/cfbPlayoffPredictor/data/current/CurrentCalculatedRatings.txt';
$data = file_get_contents("$currentCalculatedRatingsFile");

$lineFromText = explode("\n", $data);
$teamRating = array ();        				#PHP doc says it is best to explicitly define the array (equivalent of perl hash)
foreach($lineFromText as $line){
$teamData = explode(":", $line);
	$teamName = $teamData[0];
$teamRating[$teamName] = $teamData[1];      #makes $teamRating[Georgia] = 0.950
}


#Map visiting team win probability to point spread
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


$file = '/home/neville/cfbPlayoffPredictor/data/current/ncfScheduleFile.txt';
$data = file($file) or die('Could not read file!');

$i=0;
foreach ($data as $line) {
$input1 = explode(":", $line);
$teams = explode(" - ", $input1[1]);	


#2023-08 - formatting $teams[1] to rid \n at the end
$teams[1] = rtrim($teams[1]);

$nextWeekUp = "week 8";
$nextWeekUpNumber = explode(" ", $nextWeekUp);
$nextWeekUpNumber = $nextWeekUpNumber[1];


$awayTeam = $teams[0];
$homeTeam = $teams[1];

#echo "away team is ---$awayTeam--- and home team is ---$homeTeam---<br>";
#echo "week is $week  <br>";
#echo "teamRating of Alabama is $teamRating[Alabama]  <br>";


$divisor = 1;  #base = 1000 divisor =1


$probA = 1/(1+(1000**(($teamRating[$awayTeam]-(($teamRating[$homeTeam])+$homeFieldAdvantageNumber))/$divisor)));
$probB = 1/(1+(1000**((($teamRating[$homeTeam]+$homeFieldAdvantageNumber)-$teamRating[$awayTeam])/$divisor)));

#note these are exponenets, so on week 1 everyone will have 50% chance to win, regarless of rating.


#round the probabilities to 2 significant digits and output %
$probA = round ($probA,2); 
$probA = $probA * 100;
$probB = round ($probB,2);
$probB = $probB * 100;
$predictedSpread = getClosest($probB/100);

}



// Output the HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PlayoffPredictor.com ATS Betting Performance Tracker</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid black; padding: 8px; text-align: left; }
        .success { background-color: #90EE90; }
        .failure { background-color: #FFCCCB; }
    </style>
	<link type="text/css" rel="stylesheet" href="/style.css" />
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





    <h1>Betting Performance Tracker</h1>
    
    <h2>Selected Period</h2>
    <p>
        <?php
        $totalGames = 0;
        $correctPicks = 0;
        $atsPicks = 0;
        $totalError = 0;
        
        // Calculate these values based on your data
        // ...

        echo "$totalGames games<br>";
        echo "$correctPicks - " . ($totalGames - $correctPicks) . " (" . number_format($correctPicks / $totalGames, 3) . ")<br>";
        echo "$atsPicks - " . ($totalGames - $atsPicks) . " ATS (" . number_format($atsPicks / $totalGames, 2) . ")<br>";
        echo number_format($totalError / $totalGames, 2) . " MAE";
        ?>
    </p>
    
    <table>
        <tr>
            <th>Season</th>
            <th>Week</th>
            <th>Away Team</th>
            <th>Home Team</th>
            <th>Spread</th>
            <th>Prediction</th>
            <th>ATS Model Pick</th>
            <th>Actual</th>
        </tr>
        <?php
        // Your existing loop to process game data
        foreach ($gameData as $game) {
            $year = $game['year'];
            $week = $game['week'];
            $awayTeam = $game['awayTeam'];
            $homeTeam = $game['homeTeam'];
            $prediction = $game['prediction'];
            $actual = $game['actual'];
            
            $spreadKey = "$season:week $week:$awayTeam:$homeTeam";
            $spread = isset($spreads[$spreadKey]) ? $spreads[$spreadKey] : 'N/A';
            
            $betOn = determineBet($spread, $prediction);
            $betSuccessful = isBetSuccessful($spread, $actual, $betOn);
            
            $rowClass = ($betOn != 'No Pick' && $betSuccessful) ? 'success' : (($betOn != 'No Pick') ? 'failure' : '');
            
            echo "<tr class='$rowClass'>";
            echo "<td>$season</td>";
            echo "<td>$week</td>";
            echo "<td>$awayTeam</td>";
            echo "<td>$homeTeam</td>";
            echo "<td>$spread</td>";
            echo "<td>$prediction</td>";
            echo "<td>" . ($betOn == 'Home' ? "$homeTeam $spread" : ($betOn == 'Away' ? "$awayTeam +" . abs($spread) : 'No Pick')) . "</td>";
            echo "<td>$actual</td>";
            echo "</tr>";
        }
        ?>
    </table>
</body>
</html>