<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
"http://www.w3.org/TR/html4/strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<!-- PredictFutureWeeks.php
Inputs:
 ?app=yes			--- format for iOS app, otherwise for web browser 
 ?fullSeason=yes  	--- retrieves full season, otherwise next week only
 ?topTeams=N  		--- uses top N teams only, otherwise all teams
-->

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>PlayoffPredictor.com - Predict next week's cfb committee rankings</title>
	<meta name="Neville Aga" content="root" />
	<link type="text/css" rel="stylesheet" href="/style.css" />
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
		
<?php	
$useApp = $_GET["app"];
$useFullSeason = $_GET["fullSeason"];
$useTop15 = $_GET["top15"];

?>


<!--banner and menu-->    
<?php 
if($useApp != 'yes') {                #do not show this banner if called in the app
$banner = '/var/www/ppDocs/banner-and-menu.html';
$data = file($banner) or die('Could not read file!');
foreach ($data as $line) {
echo "$line";
}
}
?>  




		
<h3>Predict what the playoff committee rankings will be by filling in your picks for the weekend</h3>
		
<p>PlayoffPredictor.com can build a very accurate representation of the top 4 teams for next week. Simply fill in your expectation of the winners of this week's slate of games and PlayoffPredictor.com will give you what the resulting poll will look like on Tuesday!</p>
		
<p>For each pairing, just select the winner for each game. You do not need to fill in all the games, however the result will be more accurate with the more games you enter.</p>	

<p>Percentages to the left and right of each time are their <a href="/eloSingleGamePrediction.php">elo victory probabilities</a>, and team ratings here *do* consider margin of victory.</p>

<p>Home field advantage *is* taken into consideration for future probabilities. The home team gets a rating boost of 0.04 for weeks 1-13.  Week 14 games are assumed to be neutral site</p>

<?php
#Put links based on the page that was called
if ($useFullSeason!="yes" && $useTop15 =="yes") {   #case 1 - just 1 week and top 15
echo "<h4>Want more accuracy? <a href=/predictFutureWeeks-unified.php?app=$useApp&top15=no&fullSeason=no>Try picking all FBS scheduled games for next week</a></h4>";
}
if ($useFullSeason!="yes" && $useTop15 !="yes") {   #case 2 - just 1 week and all teams
echo "<h4>Too many games to choose? <a href=/predictFutureWeeks-unified.php?app=$useApp&top15=yes&fullSeason=no>Try picking games being played by the top 15 teams next week</a></h4>";
}
if ($useFullSeason=="yes" && $useTop15 =="yes") {   #case 3 - all weeks and top 15
echo "<h4>Want more accuracy? <a href=/predictFutureWeeks-unified.php?app=$useApp&top15=no&fullSeason=yes>Try picking all FBS future scheduled games</a></h4>";
}
if ($useFullSeason=="yes" && $useTop15 !="yes") {   #case 4 - all weeks and all teams
echo "<h4>Too many games to choose? <a href=/predictFutureWeeks-unified.php?app=$useApp&top15=yes&fullSeason=yes>Try picking from only the top 15 teams</a></h4>";
}
?>



		
<form action="/cgi-bin/agaPPMatrix.cgi" method="post">   
<table style="width:55%;border:0px;cellpadding:4px;cellspacing:1px">
	
<?php	
#Read in current calculated ratings, so can see elo winning probabilities
$currentCalculatedRatingsFile = '/home/neville/cfbPlayoffPredictor/data/current/CurrentCalculatedRatings.txt';
$data = file_get_contents("$currentCalculatedRatingsFile");

$lineFromText = explode("\n", $data);
$teamRating = array ();        				#PHP doc says it is best to explicitly define the array (equivalent of perl hash)
foreach($lineFromText as $line){
$teamData = explode(":", $line);
	$teamName = $teamData[0];
$teamRating[$teamName] = $teamData[1];      #makes $teamRating[Georgia] = 0.950
}
#var_dump($teamRating);




if ($useTop15 == 'yes') {

$topTeamFile = '/home/neville/cfbPlayoffPredictor/data/current/currentPlayoffCommitteeRankings.txt';
$data = file_get_contents("$topTeamFile");

$lineFromText = explode("\n", $data);
$row=0;
foreach($lineFromText as $line) {
		$teamData = explode(":", $line);
		
	if($row <15) {
		echo "";
#		echo "$teamData[1]<br>";
$topTeams[] = $teamData[1]; 
		echo "";
			$row++;
		}
	}
}




$file = '/home/neville/cfbPlayoffPredictor/data/current/ncfScheduleFile.txt';
$data = file($file) or die('Could not read file!');

$i=0;
foreach ($data as $line) {
$input1 = explode(":", $line);
$teams = explode(" - ", $input1[1]);	


$top=0;
for ($g=0 ;$g<15  ;$g++ ) {
if ( ( $topTeams[$g]== $teams[0])  ||  ($topTeams[$g] == trim($teams[1])) ) {
$top=1;	
}		
}







$offsetDays = -5;   #an offset so the right week can be pulled up in different years. will need to change this value year to year
#the Army-Navy game should be 350 days + the offset (usually negative) from Jan 1. Use that as a yardstick
#so if you go to google and say "days from Jan 1 2021 to Dec 11 2021 (the day of army-navy)" and it comes back 344, use an offset of -6 for that year
#2021 value was -6
#2022 value is -5, even though by army-navy it should be -7


$daysSinceJan1 = date('z');  #returns the number of days since Jan 1 of that year. 
						#will need to compute appropriate if / then each year
#echo "$daysSinceJan1 <br>";  
#echo "$daysSinceJan1 + $offsetDays <br>";
	if(( ($daysSinceJan1 + $offsetDays) >= 90) && ( ($daysSinceJan1 + $offsetDays) < 243)) {
$nextWeekUp = "week 1";
		}	
	if(( ($daysSinceJan1 + $offsetDays)  >= 243) && ( ($daysSinceJan1 + $offsetDays)  < 250)) {
$nextWeekUp = "week 2";
		}
	if(( ($daysSinceJan1 + $offsetDays)  >= 250) && ( ($daysSinceJan1 + $offsetDays)  < 257)) {
$nextWeekUp = "week 3";
		}
	if(( ($daysSinceJan1 + $offsetDays)  >= 257) && ( ($daysSinceJan1 + $offsetDays)  < 264)) {
$nextWeekUp = "week 4";
		}
	if(( ($daysSinceJan1 + $offsetDays)  >= 264) && ( ($daysSinceJan1 + $offsetDays)  < 271)) {
$nextWeekUp = "week 5";
		}
	if((  ($daysSinceJan1 + $offsetDays) >= 271) && (($daysSinceJan1 + $offsetDays) < 278)) {
$nextWeekUp = "week 6";
		}
	if((  ($daysSinceJan1 + $offsetDays) >= 278) && (($daysSinceJan1 + $offsetDays) < 285)) {
$nextWeekUp = "week 7";
		}
	if((  ($daysSinceJan1 + $offsetDays) >= 285) && (($daysSinceJan1 + $offsetDays) < 292)) {
$nextWeekUp = "week 8";
		}
	if((  ($daysSinceJan1 + $offsetDays) >= 292) && (($daysSinceJan1 + $offsetDays) < 299)) {
$nextWeekUp = "week 9";
		}
	if((  ($daysSinceJan1 + $offsetDays) >= 299) && (($daysSinceJan1 + $offsetDays) < 306)) {
$nextWeekUp = "week 10";
		}
	if((  ($daysSinceJan1 + $offsetDays) >= 306) && (($daysSinceJan1 + $offsetDays) < 313)) {
$nextWeekUp = "week 11";
		}
	if((  ($daysSinceJan1 + $offsetDays) >= 313) && (($daysSinceJan1 + $offsetDays) < 320)) {
$nextWeekUp = "week 12";
		}
	if((  ($daysSinceJan1 + $offsetDays) >= 320) && (($daysSinceJan1 + $offsetDays) < 327)) {
$nextWeekUp = "week 13";
		}
	if((  ($daysSinceJan1 + $offsetDays) >= 327) && (($daysSinceJan1 + $offsetDays) < 334)) {
$nextWeekUp = "week 14";
		}
	if((  ($daysSinceJan1 + $offsetDays) >= 334) && (($daysSinceJan1 + $offsetDays) < 341)) {
$nextWeekUp = "week 15";
		}
	if((  ($daysSinceJan1 + $offsetDays) >= 341) || (($daysSinceJan1 + $offsetDays) < 90)) {
$nextWeekUp = "week 17";
}

$nextWeekUpNumber = explode(" ", $nextWeekUp);
$nextWeekUpNumber = $nextWeekUpNumber[1];


#If displaying a whole season put a heading for each week
if ($useFullSeason=="yes")  {
if ($input1[0] != $thisWeek)  {
	echo "<tr><td>";
	echo "<br><br><b><center>$input1[0]</center></b>";
	echo "</td></tr>";
} 
$thisWeek = $input1[0];	
}	

$awayTeam = $teams[0];
$homeTeam = $teams[1];
$homeTeam = rtrim($homeTeam);    #coming back with a single space at the end

#echo "away team is ---$awayTeam--- and home team is ---$homeTeam---<br>";
#echo "week is $week  <br>";
#echo "teamRating of Alabama is $teamRating[Alabama]  <br>";


$divisor = 1;  #original is base = week #, divisor = .4.  New is base = 1000 divisor =1
#$week = $nextWeekUpNumber;    #get this from the current date, todo

if ($thisWeek!="week 14"){
$homeFieldAdvantageNumber = 0.04;
} else {
	$homeFieldAdvantageNumber = 0;
}

$probA = 1/(1+(1000**(($teamRating[$awayTeam]-(($teamRating[$homeTeam])+$homeFieldAdvantageNumber))/$divisor)));
$probB = 1/(1+(1000**((($teamRating[$homeTeam]+$homeFieldAdvantageNumber)-$teamRating[$awayTeam])/$divisor)));

#note these are exponenets, so on week 1 everyone will have 50% chance to win, regarless of rating.


#round the probabilities to 2 significant digits and output %
$probA = round ($probA,2); 
$probA = $probA * 100;
$probB = round ($probB,2);
$probB = $probB * 100;







if (    (($input1[0] == "$nextWeekUp") && ($top==1))   ||   (($input1[0] == "$nextWeekUp") && ($useTop15!="yes"))     ||   (($useFullSeason == "yes") && ($useTop15!="yes"))      ||   (($useFullSeason == "yes") && ($top==1))       )  {
	
echo "<tr class=\"border_bottom\">";	
echo "<td style=\"color:gray;width:1%\">$probB%</td>";
if($probB>50){         # edit this and the one below to 50% for games over 50% the checkbox is filled in at page startup. #todo - add javascript to fine tune this number
echo "<td style=\"text-align:right;width:5%\"><input type=\"radio\" name=\"game"."$i"."result\" value=\"$teams[0] 1-0 $teams[1]\" checked=\"checked\">$teams[0]</td>";	
}else{
echo "<td style=\"text-align:right\"><input type=\"radio\" name=\"game"."$i"."result\" value=\"$teams[0] 1-0 $teams[1]\">$teams[0]</td>";	
}

echo "<td style=\"text-align:center;width:2%\"    >vs.</td>";

if($probA>50){
echo "<td style=\"width:10%\"    ><input type=\"radio\" name=\"game"."$i"."result\" value=\"$teams[1] 1-0 $teams[0]\" checked=\"checked\">$teams[1]</td>";
}else{
echo "<td style=\"width:10%\"    ><input type=\"radio\" name=\"game"."$i"."result\" value=\"$teams[1] 1-0 $teams[0]\">$teams[1]</td>";
}

echo "<td style=\"color:gray;width:1%\">$probA%</td>";
echo "</tr>";	

$i++;

} 

}
	


if ($useFullSeason=="yes") {   #allow user directed input if looking at a whole season
	#there are about 840 games in a regular scheduled season (weeks 1-14), so input would need to be 901-904 in order not to clobber a full season.

	echo "<tr></tr>";
	echo "<tr ><td></td><td><br><br><br><center>Input additional games of your own</center></td></tr>";
	echo "<tr></tr>";
	
	echo '<tr class="border_bottom">';
	echo '<td><input type="radio" name="game901result" id="901aButton" value=""><input type="text" name="game901aTeam" value="" id="901aTeam" onchange="populate901Function()"> </td>';
	echo '<td> vs.</td> ';
	echo '<td><input type="radio" name="game901result" id="901hButton" value=""><input type="text" name="game901hTeam" value="" id="901hTeam" onchange="populate901Function()"> </td>';
	echo '</tr>';
	
	echo '<tr class="border_bottom">';
	echo '<td><input type="radio" name="game902result" id="902aButton" value=""><input type="text" name="game902aTeam" value="" id="902aTeam" onchange="populate902Function()"> </td>';
	echo '<td> vs.</td> ';
	echo '<td><input type="radio" name="game902result" id="902hButton" value=""><input type="text" name="game902hTeam" value="" id="902hTeam" onchange="populate902Function()"> </td>';
	echo '</tr>';
	
	echo '<tr class="border_bottom">';
	echo '<td><input type="radio" name="game903result" id="903aButton" value=""><input type="text" name="game903aTeam" value="" id="903aTeam" onchange="populate903Function()"> </td>';
	echo '<td> vs.</td> ';
	echo '<td><input type="radio" name="game903result" id="903hButton" value=""><input type="text" name="game903hTeam" value="" id="903hTeam" onchange="populate903Function()"> </td>';
	echo '</tr>';
	
	echo '<tr class="border_bottom">';
	echo '<td><input type="radio" name="game904result" id="904aButton" value=""><input type="text" name="game904aTeam" value="" id="904aTeam" onchange="populate904Function()"> </td>';
	echo '<td> vs.</td> ';
	echo '<td><input type="radio" name="game904result" id="904hButton" value=""><input type="text" name="game904hTeam" value="" id="904hTeam" onchange="populate904Function()"> </td>';
	echo '</tr>';
	
	
	echo '<script>';
	echo 'function populate901Function() {';
	echo '    var x = document.getElementById("901aTeam").value;';
	echo '     var y = document.getElementById("901hTeam").value;';
	echo '    document.getElementById("901aButton").value = x + " 1-0 " + y;';
	echo '    document.getElementById("901hButton").value = y + " 1-0 " + x;';
	echo '}';
	
	
	echo 'function populate902Function() {';
	echo '    var x = document.getElementById("902aTeam").value;';
	echo '     var y = document.getElementById("902hTeam").value;';
	echo '    document.getElementById("902aButton").value = x + " 1-0 " + y;';
	echo '    document.getElementById("902hButton").value = y + " 1-0 " + x;';
	echo '}';
	
	echo 'function populate903Function() {';
	echo '    var x = document.getElementById("903aTeam").value;';
	echo '     var y = document.getElementById("903hTeam").value;';
	echo '    document.getElementById("903aButton").value = x + " 1-0 " + y;';
	echo '    document.getElementById("903hButton").value = y + " 1-0 " + x;';
	echo '}';
	
	echo 'function populate904Function() {';
	echo '    var x = document.getElementById("904aTeam").value;';
	echo '     var y = document.getElementById("904hTeam").value;';
	echo '    document.getElementById("904aButton").value = x + " 1-0 " + y;';
	echo '    document.getElementById("904hButton").value = y + " 1-0 " + x;';
	echo '}';
	echo '</script>';
	
	}





#If no teams are left in Next Week, then say just click to go to predictions	
if ($i == 0)  {
echo "<tr><td>No more games are scheduled for this week.</td></tr>";
echo "<tr><td>Click the button below to see the predicted rankings</td></tr>";
}	    
	 	    
	    
?>




</table>
	<br>
	<b>
	<input type="submit" id="mybutton" value="Predict what the rankings will be with these winners" /><br>
	</b>
	
<input type="hidden" name="useMarginOfVictory" value="yes">

</form>


		
<br><br>
<!--  todo via javascript
<p>Pre-populate all home teams win</p>
<p>Pre-populate all higher rated teams win<p>
<p>clear radio checkboxes (done)<p>	



<input type="button" value="Pre-populate all home teams win" onclick="HomesWin();"> <br>


<input type="button" value="Pre-populate all teams with >XX% probability win" onclick="FavoritesWin();">  <br>

-->

<input type="button" value="Clear This Form" onclick="Clear();">  <br>

<script>
function Clear()
{
	for(var i=0;i<904;i++)
	clearRadioGroup('game' + i + 'result');
}

function clearRadioGroup(GroupName)
{
	var ele = document.getElementsByName(GroupName);
	  ele[0].checked = false;
	  ele[1].checked = false;
}

</script>




	</body>
</html>
