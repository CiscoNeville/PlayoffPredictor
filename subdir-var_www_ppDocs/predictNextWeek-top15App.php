<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
"http://www.w3.org/TR/html4/strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>PlayoffPredictor.com - Predict next week's cfb committee rankings</title>
		<meta name="Neville Aga" content="root" />
	   <link type="text/css" rel="stylesheet" href="/styleApp.css" />
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
		
		

<h4>Who will win the following games?</a></h4>


		
<form action="/cgi-bin/agaPPMatrixApp.cgi" method="post">   
	<table border="0px" cellpadding="4px" cellspacing="1px">
	
<?php	

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






$file = '/home/neville/cfbPlayoffPredictor/data/current/ncfScheduleFile.txt';
$data = file($file) or die('Could not read file!');

$i=0;
foreach ($data as $line) {
$input1 = explode(":", $line);
$teams = explode(" - ", $input1[1]);	


$top=0;
for ($g=0 ;$g<10  ;$g++ ) {
if ( ( $topTeams[$g]== $teams[0])  ||  ($topTeams[$g] == trim($teams[1])) ) {
$top=1;	
}		
}



$offsetDays = -6;   #an offset so the right week can be pulled up in different years. will need to change this value year to year

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
	
	
	if (($input1[0] == "$nextWeekUp") && ($top==1))  {
	
echo "<tr class=\"border_bottom\">";	
echo "<td><input type=\"radio\" name=\"game"."$i"."result\" value=\"$teams[0] 1-0 $teams[1]\">$teams[0]</td>";	
echo "<td>vs.</td>";
echo "<td>     </td>";
echo "<td>     </td>";
echo "<td><input type=\"radio\" name=\"game"."$i"."result\" value=\"$teams[1] 1-0 $teams[0]\">$teams[1]</td>";
echo "</tr>";	

$i++;
	
	}	
	
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
	<input type="submit" id="mybutton" value="Predict rankings with these winners" /><br>
	</b>
	
	</form>



		
		

	</body>
</html>
