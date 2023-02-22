<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>college football Playoff Predictor</title>
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






<?php

 function superman($teamIn, $colNum) {
 
$year = $_GET["year"];
$week = $_GET["week"];


#If $week is nonzero then use a historical scorefile and schedulefile and teamNames for that $week and $year
#note the historical schedulefile is not implemented at this time. only scores
if ($year != '') {
$ncfScoresFile = "/home/neville/cfbPlayoffPredictor/data/$year/week$week/Week$week"."NcfScoresFile.txt";	
#$ncfScheduleFile = "/home/neville/cfbPlayoffPredictor/data/$year/week$week/Week$week"."NcfScheduleFile.txt";
$fbsTeams = "/home/neville/cfbPlayoffPredictor/data/$year/fbsTeamNames.txt";
$currentCalculatedRatingsFile = "/home/neville/cfbPlayoffPredictor/data/$year/week$week/Week$week"."CalculatedRatings.txt";	
}

#else use current scorefile and schedulefile, and teamNames for this year
else {
$year = date("Y");    #php gets the current year as 4 digits		
$ncfScoresFile = "/home/neville/cfbPlayoffPredictor/data/current/ncfScoresFile.txt";
$ncfScheduleFile = "/home/neville/cfbPlayoffPredictor/data/current/ncfScheduleFile.txt";
$fbsTeams = "/home/neville/cfbPlayoffPredictor/data/$year/fbsTeamNames.txt";
$currentCalculatedRatingsFile = "/home/neville/cfbPlayoffPredictor/data/current/CurrentCalculatedRatings.txt";	
}





##Read in FBS teams for this year
$data = file_get_contents("$fbsTeams");
$lineFromText = explode("\n", $data);
foreach($lineFromText as $line) {
		$teamData = explode(" => ", $line);
$teamName = $teamData[0];
$teamID = $teamData[1];
$team[$teamID] = "$teamName";    #add something to the array like 4 => Florida State
$teamH[$teamName] = "$teamID";    #add something to the array like Auburn => 53
}  #at the conclusion of this block $team{4} will be "Florida State" and $teamH{Auburn} will be "53"

 
$teamR = array();    #Team Rating
$teamRk = array();   #Team Ranking


$teamCount = count($team);
$teamCount++;



$k = 0;
$r = 0;
$s = 0;
$wins=0;
$losses=0;
@resultsWon;
@resultsLost;
@futureSchedule;
$scoreInput;
$scheduleInput;

#get current ratings for all teams into hash
$data = file_get_contents("$currentCalculatedRatingsFile");
$lineFromText = explode("\n", $data);
$row=0;

foreach($lineFromText as $line) {
		$teamData = explode(":", $line);
$teamName = $teamData[0];
$teamRating = $teamData[1];
$teamR{"$teamName"} .= $teamRating;
}


#determine current rankings for all teams into hash
for ($i=1; $i<$teamCount+2; $i++) {         #$i needs to go to number of teams this year plus 2, else results show html code
  for ($j=1; $j<$teamCount+2; $j++)  {
#print "$teamR{$team{$i}}   - $teamR{$team{$j}}      \n";
if ( $teamR{$team{$i}}  <= $teamR{$team{$j}}  )   {
 $teamRk{$i} = $teamRk{$i} + 1;
# print "Rk is $teamRk{$i}  \n";
 }
}
 }

#$q = $teamH["1AA"];
#echo "$team[$q] ranking is $teamRk[$q]<br>";





		
	
#$q = $teamH["Baylor"];
#echo "$team[$q] ranking is $teamRk[$q]<br>";	



#read in past scores, and capture any that match team in question
$data = file_get_contents("$ncfScoresFile");
$lineFromText = explode("\n", $data);
foreach ($lineFromText as $scoreInput) {
	


preg_match('/Final.*: (.+?) (\d+) - (.+?) (\d+)/',$scoreInput,$stuff);

$aTeamName = $stuff[1];
$aTotal = $stuff[2];
$hTeamName = $stuff[3];
$hTotal = $stuff[4];

# Determine if a team is a 1AA team and add the tag "1AA"
if ( in_array("$aTeamName", $team)   )  {
#that's great, away team is FBS. Do Nothing
}
else {   #otherwise it was a 1AA team
$aTeamName = "(FCS) $aTeamName";
if ($teamR{"$aTeamName" } == '') {
$teamR{"$aTeamName"} .= $teamR{'1AA'};
$q = $teamH["1AA"];
$teamRk{"$aTeamName"} .= $teamRk[$q];
$teamH{"$aTeamName"} .= $teamH["1AA"];
#echo "teamRk of $aTeamName is $teamRk[$q]       <br>";
}
}

if (  in_array("$hTeamName", $team)     )  {    #Like it would ever happen -- a 1A team plays a game on the road against a 1AA team
#that's great, home team is FBS. Do Nothing
}
else {   #otherwise it was a 1AA team
$hTeamName = "(FCS) $hTeamName";
if ($teamR{"$hTeamName"} == '') {
$teamR{"$hTeamName"} .= $teamR{'1AA'};
$q = $teamH["1AA"];
$teamRk{"$hTeamName"} .= $teamRk[$q];
$teamH{"$hTeamName"} .= $teamH["1AA"];
}
}

#Does this game involve my team under consideration?
if ( $hTeamName == $teamIn  ) {

#Add this result to my team's schedule/results array
if ($hTotal > $aTotal)  {    #my team won
$q = $teamH[$aTeamName];
$p = ($teamRk[$q] * 5) + 20; #orig = 290
$o = (($colNum - 1) * 379) + 16;
$resultsWon[$r] = "$teamRk[$q]:<div id=\"inner\" style=\"position: absolute; line-height:12px; top:$p"."px; left:$o"."px; width:350px; height:12 px; background-color:#008000\"> <font color=\"#FFF0FF\">W-($hTotal-$aTotal) - $aTeamName  (Rank #$teamRk[$q])</font></div>";
$wins = $wins + 1;
$r++;
}
else  {    #my team lost...
$q = $teamH[$aTeamName];
$p = ($teamRk[$q] * 5) + 20;
$o = (($colNum - 1) * 379) + 16;
$resultsLost[$s] = "$teamRk[$q]:<DIV id=\"inner\" style=\"position: absolute; line-height:12px; top:$p"."px; left:$o"."px; width:350px; height:12px; background-color:#A00000\"> <font color=\"#F0FFFF\">L-($hTotal-$aTotal) - $aTeamName  (Rank #$teamRk[$q])</font></div>";
$losses = $losses + 1;
$s++;
}
}


if ( $aTeamName == $teamIn  ) {

#Add this result to my team's schedule/results array

 if ($aTotal > $hTotal)  {    #my team won
 $q = $teamH[$hTeamName];
 $p = ($teamRk[$q] * 5) + 20;
 $o = (($colNum - 1) * 379) + 16;
 $resultsWon[$r] = "$teamRk[$q]:<div id=\"inner\" style=\"position: absolute; line-height:12px; top:$p"."px; left:$o"."px; width:350px; height:12px; background-color:#008000\"> <font color=\"#FFF0FF\">W-($aTotal-$hTotal) - $hTeamName  (Rank #$teamRk[$q])</font></div>";
$wins = $wins + 1;
$r++;
}
else {    #my team lost...
$q = $teamH[$hTeamName];
$p = ($teamRk[$q] * 5) + 20;
$o = (($colNum - 1) * 379) + 16;
 $resultsLost[$s] = "$teamRk[$q]:<DIV id=\"inner\" style=\"position: absolute; line-height:12px; top:$p"."px; left:$o"."px; width:350px; height:12px; background-color:#A00000\"> <font color=\"#F0FFFF\">L-($aTotal-$hTotal) - $hTeamName  (Rank #$teamRk[$q])</font></div>";
$losses = $losses + 1;
$s++;
}
}






}
 
 rsort($resultsWon);
 rsort($resultsLost);




#read in future schedule, capture any the match team in question
$data = file_get_contents("$ncfScheduleFile");
$lineFromText = explode("\n", $data);
foreach ($lineFromText as $scheduleInput) {

chop ($scheduleInput);


preg_match('/week (\d+?):(.+?) - (.+)/',$scheduleInput,$stuff);   #note, no non-greedy for the last term (or else just gets one letter)

$week = $stuff[1];
$aTeam = $stuff[2];
$hTeam = $stuff[3];



# Determine if a team is a 1AA team and add the tag "1AA"
if ( in_array("$aTeam", $team)   )  {
#that's great, away team is FBS. Do Nothing
}
else {   #otherwise it was a 1AA team
$aTeam = "(FCS) $aTeam";
if ($teamR{"$aTeam" } == '') {
$teamR{"$aTeam"} .= $teamR{'1AA'};
$q = $teamH["1AA"];
$teamRk{"$aTeam"} .= $teamRk[$q];
$teamH{"$aTeam"} .= $teamH["1AA"];
#echo "teamRk of $aTeam is $teamRk[$q]       <br>";
}
}

if (  in_array("$hTeam", $team)     )  {    #Like it would ever happen -- a 1A team plays a game on the road against a 1AA team
#that's great, home team is FBS. Do Nothing
}
else {   #otherwise it was a 1AA team
$hTeamName = "(FCS) $hTeam";
if ($teamR{"$hTeam"} == '') {
$teamR{"$hTeam"} .= $teamR{'1AA'};
$q = $teamH["1AA"];
$teamRk{"$hTeam"} .= $teamRk[$q];
$teamH{"$hTeam"} .= $teamH["1AA"];
}
}






#Does this game involve my team under consideration?
if ( $hTeam == $teamIn  ){
$q = $teamH[$aTeam];
$p = ($teamRk[$q] * 5) + 20; #original = 290
$o = (($colNum - 1) * 379) + 16;
$futureSchedule[$k] = "$teamRk[$q]:<DIV id=\"inner\" style=\"position: absolute; line-height:12px; top:$p"."px; left:$o"."px; width:350px; height:12px; background-color:#FFFDD8\"> &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp           vs $aTeam (Rank #$teamRk[$q])</div>";
$k++;
}


if  ( $aTeam == $teamIn  ){
$q = $teamH[$hTeam];
$p = ($teamRk[$q] * 5) + 20;
$o = (($colNum - 1) * 379) + 16;
$futureSchedule[$k] = "$teamRk[$q]:<DIV id=\"inner\" style=\"position: absolute; line-height:12px; top:$p"."px; left:$o"."px; width:350px; height:12px; background-color:#FFFDD8\"> &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp           at $hTeam (Rank #$teamRk[$q])</div>";
$k++;
}


}




echo "<td>";			
echo "<table bgcolor =\"#FFFFFF\" border=\"0px\" cellpadding=\"\" cellspacing=\"\" width=\"359px\" height=\"700px\"  frame=\"box\">";


echo "<div id=\"outer\" style=\"position:relative\">";



#compile the team result
$resume = array();
$q = $teamH[$teamIn];
$o = (($colNum - 1) * 379) + 16;
print "<tr><th><DIV id=\"inner\" style=\"position: absolute; top:10px; left:$o"."px; width:350px; height:20px;\"> <font color=\"#000040\">           #$teamRk[$q] $teamIn ($wins-$losses)  -  $teamR[$teamIn] </font></div>   </th></tr>";  
print "	<tr><td> <DIV id=\"inner\" style=\"position: absolute; top:18px; left:$o"."px; width:350px; height:4px;\">       <hr>      </div></td></tr>";


#print " ---$wins wins"."---\n";
for ($m=0; $m<count($resultsWon); $m++) {
#print "$resultsWon[$m]\n";
$resume[] = "$resultsWon[$m]";
}

#print " ---$losses losses"."---\n";
for ($m=0; $m<count($resultsLost); $m++) {
#print "$resultsLost[$m]\n";
$resume[] = "$resultsLost[$m]\n";
}

#print " ---future games"."---\n";
for ($m=0; $m<count($futureSchedule); $m++) {
#print "$futureSchedule[$m]\n";
$resume[] = "$futureSchedule[$m]\n";
}
#print "\n";

sort ($resume, SORT_NUMERIC); 
#echo " <br>----------------------------------------------- <br> ";



$z = array();
#html output of results positioned absolutely
for ($k=0; $k<=count($resume); $k++) {
preg_match('/(.+?):(.+)/',$resume[$k],$stuff);   #note, no non-greedy for the last term (or else just gets one letter)

#here is where you would need some logic to snuff out overlapping text
$good = $stuff[2];
$offset=0;

#while  ( (in_array($stuff[1] + $offset,$z)) || (in_array($stuff[1] + 1 + $offset,$z))    ) {  #this place already taken   - original line
while  ( (in_array($stuff[1] + $offset,$z))   ) {  #this place already taken
$offset++;

#echo "$stuff[1]<br>";
#echo "$offset<br>";
#echo "$z<br>";
#echo "$good<br>";

	
#rewrite good with the new position data
preg_match('/top:(.+?)px/',$good,$goodder);
$pattern = "/top:$goodder[1]px/";
$goodder[1] = $goodder[1] + 6;   #original is + 6*$offset. That was wrong. When $offset was big (4 or 5) then the multiplication would put the team too far down the page.
$replacement = "top:$goodder[1]px";

$good = preg_replace($pattern, $replacement ,$good);
}	

#mark the spots as taken
$z[] = $stuff[1] + $offset;
$z[] = $stuff[1] + 1 + $offset;	#$z[] will be an array containing approx the ranks and +1 of teams played. For example, if Liberty has played #5, #41, #91, #92 then z[0]=5, z[1]=6, z[2]=41, z[3]=42, z[4]=91, z[5]=92, z[6]=93, z[7]=94





echo "<tr>$good</tr>";
}


echo "</div>";
echo "</table>";
echo "</td>";


echo "<td>&nbsp &nbsp</td>";    #puts some separation between each teams column of results



}  #   this is the end of the superman function



$year = $_GET["year"];
$weekNumber = $_GET["week"];

$team1In = $_GET["team1"];
$team2In = $_GET["team2"];
$team3In = $_GET["team3"];
$team4In = $_GET["team4"];
$team5In = $_GET["team5"];
#$compare = $_GET["compare"];       #the idea here is this will be 2 or 4 and will compare the teams next to this one
#& in Texas A&M treats the M as another variable -- clobber that problem
if ($team1In == "Texas A") { $team1In = "Texas A&M"; }
if ($team2In == "Texas A") { $team2In = "Texas A&M"; }
if ($team3In == "Texas A") { $team3In = "Texas A&M"; }
if ($team4In == "Texas A") { $team4In = "Texas A&M"; }
if ($team5In == "Texas A") { $team5In = "Texas A&M"; }
#echo "$team1In<br>";




#start the container table
echo "";
echo "<table>";
echo "<tr valign=\"top\">";		


#allow this script to compare up to 5 teams. 
#if you really want to see all 129 teams at once, then copy this to another file and do it that way. Don't modify this to deal with that situation
#same goes for if you want to see all teams in a conference at one shot	
#assume at the least team1 is always inputted
superman("$team1In", 1);	
	
	
	
if ($team2In != '') {
superman("$team2In", 2);	
}	else   {
print "<tr><th><DIV id=\"inner\" style=\"position: absolute; top:500px; left:390px; width:350px; height:20px;\"> Type another team name here  </div>   </th></tr>";	
echo "<tr><th><DIV id=\"inner\" style=\"position: absolute; top:520px; left:335px; width:400px; height:20px;\">    <form method=\"get\" action=\"analyzeScheduleApp.php\"> <input type=\"hidden\" name=\"team1\" value=\"$team1In\">  <input type=\"hidden\" name=\"year\" value=\"$year\"> <input type=\"hidden\" name=\"week\" value=\"$weekNumber\">  <input type=\"text\" name=\"team2\" value=\"\"> <input type=\"submit\" value=\"Compare!\">      </div>   </th></tr>";	
print "<tr><th><DIV id=\"inner\" style=\"position: absolute; top:560px; left:390px; width:350px; height:20px;\"> to compare their schedules to side-by-side  </div>   </th></tr>";	
}

if ($team2In != '')  {	

}	
	
	
#nov 14, 2014 9:55pm.  3 is enough for now.	


echo "</tr>";
echo "</table>";


?>



<!--
<br>

<DIV id="inner" style="position: absolute; top:700px; left:0px; width:350px; height:20px;">
Follow us on twitter:   
<a href="https://twitter.com/CiscoNeville" class="twitter-follow-button" data-show-count="false">Follow @CiscoNeville</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>
</div>
-->  
  

    </body>
 
</html>
