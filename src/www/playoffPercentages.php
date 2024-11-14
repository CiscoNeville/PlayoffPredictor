<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
"http://www.w3.org/TR/html4/strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<!-- playoffPercentages.php
Inputs:
 ?app=yes			--- format for iOS app, otherwise for web browser 
-->

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>PlayoffPredictor.com percentage chances of making the playoffs</title>
	<meta name="Neville Aga" content="root" />
	<link type="text/css" rel="stylesheet" href="/style.css" />


   <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script> 


    <style>
      table,
      th ,
      td {
        padding: 0px;
        border: 1px solid #000000;
        border-radius: 30px;
        background-color: #FBFBF0;
        text-align:center;
      }
      img {
        align:left;
      }
      .trigger_popup_fricc {
    cursor: pointer;
    font-size: 20px;
    margin: 0px;
    display: inline-block;
    font-weight: bold;
}
    </style>
</head>

<body> 
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-988NB7TH39"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-988NB7TH39');
</script>
		
<?php	
$useApp = $_GET["app"];
$appScalingFactor = 1;

#<!--banner and menu-->    
if($useApp != 'true') {                # show this banner and settings if not called in the app
$appScalingFactor = 1;
    $banner = '/var/www/ppDocs/banner-and-menu.html';
$data = file($banner) or die('Could not read file!');
foreach ($data as $line) {
echo "$line";
}
echo '<p><b>Below are the current probabilities for each team to make the 12-team college football playoff</b><br> Data is based on games played to this point and future scheduling using a Monte-Carlo simulation of 1000 iterations.<br> The conference numbers indicate how many teams from that conference are expected to make the playoff. Updated 3 times each Saturday</p>';
}
?>  


<?php	
#Read in current playoff probabilities from file
$currentPlayoffProbabilitiesFile = '/home/neville/cfbPlayoffPredictor/data/current/currentPlayoffPercentages.txt';
$data = file_get_contents("$currentPlayoffProbabilitiesFile");

$lineFromText = explode("\n", $data);
$teamPlayoffProbability = array ();        				#PHP doc says it is best to explicitly define the array (equivalent of perl hash)
foreach($lineFromText as $line){
$teamData = explode(":", $line);
	$teamName = $teamData[0];
$teamPlayoffProbability[$teamName] = ($teamData[1] / 10);      #makes $teamPlayoffProbability[Georgia] = 320 / 10  = 32    .  Need to convert to 32% later on. Or should i use .32? 
}

#Read in team name to icon mapping
$teamIconsFile = '/home/neville/cfbPlayoffPredictor/data/teamIcons.txt';
$data = file_get_contents("$teamIconsFile");

$lineFromText = explode("\n", $data);
$teamIcon = array ();
foreach($lineFromText as $line)   {
    chop ($line);
#    unless (substr($line,0,1) == '#') {  #Do nothing. Ignore comment lines in input file
    $iconData = explode (' => ', $line);
    $teamIcon[$iconData[0]] = $iconData[1];
#   }
}     #at the conclusion of this block $teamIcon["Wyoming"] will be "Wyo" 

#Read in conference data for teams for the current year.

// Determine the appropriate year based on the current date
$current_date = new DateTime();
$year = $current_date->format('Y');
$month = $current_date->format('n');

// If it's January or February, use the previous year
if ($month < 3) {
    $year--;
}

// Construct the file path with the determined year
$file_path = "/home/neville/cfbPlayoffPredictor/data/{$year}/fbsTeams.json";

// Read and decode the JSON file. Initialize $conference array
$json_data = file_get_contents($file_path);
$teams_data = json_decode($json_data, true);
$conference = array();

// Define the Power 4 conferences
$power4 = ['SEC', 'B10', 'B12', 'ACC'];

// Populate the $conference array
foreach ($teams_data as $team) {
    if (in_array($team['conference'], $power4)) {
        // If the conference is in Power 4, keep it as is
        $conference[$team['team_name']] = $team['conference'];
    } else {
        // For all other conferences, map to "G5"
        $conference[$team['team_name']] = "G5";
    }
}




?>




<!-- make a 6 column table, by conference -->
<!-- absolute positioning, 1415-10X for top positioning where X is the % chance to make playoff -->
<table style="width:771px;border:5px;cellpadding:0px;cellspacing:0px">
<tr> 




<?php

$conferenceColumn = array(
    "SEC" => "30",
    "B10" => "188",
    "B12" => "346",
    "ACC" => "502",
    "G5" => "656",
#    "P2" => "810",
) ;

$overlap =array();   #for overlapping visuals. if $overlap contains 301 that the SEC 1% slot is filled(needs 2).  3030 is the SEC 30% slot (needs 4). 18862 is B10 62% slot.


$conferenceTotalPercentage = array("SEC" => 0, "B10" => 0, "B12" => 0, "ACC" => 0, "Pac12" => 0, "G5" => 0);    #set all to 0 to start




asort($teamPlayoffProbability);  #do this so I am working up the stack - starting with the 1% chances and ending at 100%. duplicates are pushed up visually
foreach ($teamPlayoffProbability as $team => $playoffProbability) {
    if ($playoffProbability > 1){     #dont bother displaying if chances are less than 1%
    $playoffProbability = round($playoffProbability, 0);    #for displaying and computation of v height, round to a whole number percentage
    $visualOffset = 0;
    $playoffProbabilityHeight = $playoffProbability;

    $c = $conference[$team];     #$c will be ACC, B12, etc
    #$c = isset($conference[$team]) ? $conference[$team] : 'G5';  // $c will be ACC, B12, etc., or G5 if not found
    $e = str_pad($playoffProbability, 3, '0', STR_PAD_LEFT);
    $d = "$conferenceColumn[$c]$e";
    #print "xxx$d&&&<br>";
    while (in_array($d, $overlap)  ) {
        if ($playoffProbabilityHeight>=90){     #should allow 3 teams in 1 conferece at 100% to display. Breaks at 4
            $playoffProbabilityHeight = $playoffProbabilityHeight - 5;
            $d = $d -4;
        } else {
        $playoffProbabilityHeight = $playoffProbabilityHeight + 1;
        $d++;
        }
    }
    $f = str_pad($playoffProbabilityHeight, 3, '0', STR_PAD_LEFT);
    $g = "$conferenceColumn[$c]$f";




    if ($useApp == 'true'){
        $vPosition = 1060 - 10.2*$playoffProbabilityHeight*$appScalingFactor;
        } else {
        $vPosition = 1410 - 10.2*$playoffProbabilityHeight;
    }




    $overlap[] = $g;
    $overlap[] = $g+1;




    $hPosition = $conferenceColumn[$conference[$team]];     
    $iconSize = '25x25';
    $boxHeight = '25';
    if ($playoffProbability >= 20) { #make the large icons if the percentage chance is 20% or greater
    $iconSize = '50x50';
    $boxHeight = '50';
}
if ($playoffProbability >= 18) { #take 5 spots if you are over 18%
    $overlap[] = $g+2;
    $overlap[] = $g+3;
    $overlap[] = $g+4;
}




    $conferenceTotalPercentage[$conference[$team]] = $conferenceTotalPercentage[$conference[$team]] + ($playoffProbability / 100);
    print '<td style="position:absolute;  top:';
    print "$vPosition";
    print 'px; left:';
    print "$hPosition";
    print "px; width:100px; line-height:$boxHeight";
    print "px; height:$boxHeight";
    print 'px; text-align:right">  <img src="https://sports.cbsimg.net/images/collegefootball/logos/';
    print "$iconSize";
    print '/';
    print "$teamIcon[$team]";
    print '.png" align="left">'; 
    print "$playoffProbability%";
    print "</td>\n"; 
}
}

foreach ($conferenceColumn as $conferenceName => $junk) {
    print '<td style="border-radius: 5px; background-color:#FFFFFF; color:blue; font-family: verdana; font-size: 120%"> ';
    print "$conferenceName: ";
    print number_format($conferenceTotalPercentage[$conferenceName], 2);
    if ($useApp != 'true'){
    print '<table style="border:1px; border-radius: 10px"; background="yardstick.png"; width="150px"; height="1048px">';
    } else {
    print '<table style="border: 1px solid; border-radius: 10px; background-image: url(\'yardstick.png\'); background-size: 100% 100%; width: 150px; height: 1048px;">';
    }
    print '</table>';
    print '</td>';
}




?>

</tr>


</table>

<?php
if ($useApp == 'true'){
    print '<br><br><br><br><br><h2>yes, iPhone.<br> There is no Android app, and no plans to ever produce one.<br>Sorry Raju and Spencer.</h2>';
    } 
?>


</body>
</HTML>