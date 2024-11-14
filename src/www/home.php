<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>College Football Playoff Predictor</title>
     <link type="text/css" rel="stylesheet" href="style.css" />  

       <!-- Google Analytics Code-->    
       <!-- Google tag (gtag.js) -->
       <script async src="https://www.googletagmanager.com/gtag/js?id=G-988NB7TH39"></script>
       <script>
         window.dataLayer = window.dataLayer || [];
         function gtag(){dataLayer.push(arguments);}
         gtag('js', new Date());
       
         gtag('config', 'G-988NB7TH39');
       </script>
    </head>
  
    <body>  
<!--banner and menu-->    
   <?php 
$banner = '/var/www/ppDocs/banner-and-menu.html';
$data = file($banner) or die('Could not read file!');
foreach ($data as $line) {
echo "$line";
}
    ?>  




  <h4>Playoff Predictor for the 2024 college football season</h4>
  
  
  	<table class="buckets" width="100%" border="5" cellpadding="4" cellspacing="3" summary="layout for 3 columns">
  		
  	<colgroup>
  	 <col width="30%" >
  	 <col width="50%" >
  	 <col width="20%" >
   	</colgroup>
  
  <tr>
 
  	<td class="bucket1"><center><b>Predicted Rankings</b></center><hr>
  	<h4>Predicted committee top 4:</h4>
 




 
   
 
	
  				
 	   <?php 
 	   
 	   
$daysSinceJan1 = date('z');  #returns the number of days since Jan 1 of that year. 
	if(($daysSinceJan1 >= 303) || ($daysSinceJan1 < 30)) {   #basically Nov 1 - Jan 30
 	   
 	   
 	   
$ratings = '/home/neville/cfbPlayoffPredictor/data/current/CurrentPredictedRankings.txt';
$data = file_get_contents("$ratings");

$lineFromText = explode("\n", $data);
$row=0;
echo ' 	<div id="top4" name="top4">   ';
echo '	<table>';

foreach($lineFromText as $line) {
		$rankNum = $row+1;
		$teamData = explode(":", $line);
		
	if($row <=3) {
		echo "";
		echo "$rankNum".". ";
		echo " <a href=\"/analyzeSchedule.php?team1=$teamData[0]\">    $teamData[0] </a>  <br>   ";
		echo "";
			$row++;
		}
	}
	

echo  '	</table> ';
echo '</div> ';
echo ' <h4><a href="/currentPredictedRankings.php">Full playoff committee predicted rankings (top 25)</a> </h4>';
	}


if(($daysSinceJan1 >= 30) && ($daysSinceJan1 < 303)) { 
echo '<table><p>First predicted committee top 4 of the season will be available after the first committee poll is released (Tuesday November 5, 2024)</p></table> ';
}

    ?>
  	
  	
  	















<hr>

  	<h4>Computer top 4:</h4>
  	<div id="top4" name="top4">
  		  		
  		<table>
  				
  	   <?php 
$ratings = '/home/neville/cfbPlayoffPredictor/data/current/CurrentCalculatedRatings.txt';
$data = file_get_contents("$ratings");

$lineFromText = explode("\n", $data);
$row=0;
foreach($lineFromText as $line) {
		$rankNum = $row+1;
		$teamData = explode(":", $line);
		
	if($row <=3) {
		echo "";
		echo "$rankNum".". ";
		echo " <a href=\"/analyzeSchedule.php?team1=$teamData[0]\">  $teamData[0]  </a><br>";
		echo "";
			$row++;
		}
	}
    ?>  	
  	</table>
</div>





 	<h4><a href="/cgi-bin/agaMatrix.cgi?useMarginOfVictory=yes">True computer ratings (full list)</a> </h4>





  	<hr>
  	
  	



<hr>
  	<br>
  	<b> updated daily and constantly throughout the day on Saturdays as games go final.</b>
  	<br><br>
  	
  	</td>







	<td class="bucket3">
  		
<h3><font face="verdana" color="#000000">Who's in?</font> <font face="verdana" color="#CC3333">Come here to find out first!</font></h3>
<br>

<h5>Know the 2024 college football playoff positioning at all times</h5>
<p>PlayoffPredictor.com uses a combination of a mathematical computer based formula plus a calculated playoff committee bias in order to rank all top 25 teams. This top 25 list is a prediction of what the selection committee's top 25 rankings will be that are released on Tuesday nights. <b>The top 25 rankings is not an opinion of the teams rankings, rather it is a prediction on what the committe will do.</b></p>
<p>Additionally, using the menu at the top you can predict the future rankings the committee will give each team by filling out who the winners and losers of each game will be.</p>
<p> </p>



  	   <?php 
$daysSinceJan1 = date('z');  #returns the number of days since Jan 1 of that year. 
						#will need to compute appropriate if / then each year
#echo "$daysSinceJan1 <br>";  	  
	if(($daysSinceJan1 >= 296) && ($daysSinceJan1 < 303)) {
$nextWeekUp = "week 9";
$nextPollUp = "November 1st";
		}
	if(($daysSinceJan1 >= 303) && ($daysSinceJan1 < 310)) {
$nextWeekUp = "week 10";
$nextPollUp = "November 8th";
		}
	if(($daysSinceJan1 >= 310) && ($daysSinceJan1 < 317)) {
$nextWeekUp = "week 11";
$nextPollUp = "November 12th";
		}
	if(($daysSinceJan1 >= 317) && ($daysSinceJan1 < 324)) {
$nextWeekUp = "week 12";
$nextPollUp = "November 19th";
		}
	if(($daysSinceJan1 >= 324) && ($daysSinceJan1 < 331)) {
$nextWeekUp = "week 13";
$nextPollUp = "November 26th";
		}
	if(($daysSinceJan1 >= 331) && ($daysSinceJan1 < 338)) {
$nextWeekUp = "week 14";
$nextPollUp = "December 3rd - Final";
		}
	if(($daysSinceJan1 >= 338) && ($daysSinceJan1 < 345)) {
$nextWeekUp = "week 15";
$nextPollUp = "December 9th - Final";
		}
	
	if(($daysSinceJan1 >= 345) || ($daysSinceJan1 < 296)) {
$nextWeekUp = "next week's";
$nextPollUp = "Computer Rankings";
		}
		
echo "<h4><a href=\"/predictFutureWeeks-unified.php?app=no&top15=yes&fullSeason=no\">Try it yourself now! Select the $nextWeekUp game winners and determine what the $nextPollUp poll will look like now </a></h4><br>   ";

 
   ?>  



 	</td>
  	








  	<td class="bucket2"><center><a href="/2024NcfRatingBiases.php">  Average Committee Bias </a> --
  		

<div class="explain">What's this?</div>

<div class="tooltip">
     <div class="content">
        Committee Bias<br/>
        The bias indicates the level of agreement between the PlayoffPredictor's computer ranking and the committee ranking of a given team. <br><br> If a team has a high positive bias, the committee is ranking a team higher than the computer. If a team has a high negative bias, the computer is ranking that team higher than the committee.  If the bias is zero then the commitee and computer agree on the ranking for that team. <br><br> 



    </div>
</div>

 




</center><hr><br>

 		
  <table  border="0" cellpadding="0" cellspacing="0">		
  		
  	<?php
$fullSeasonBias = '/home/neville/cfbPlayoffPredictor/data/2024/fullSeasonCommitteeBiasMatrix.txt';

#if (file_exists ($fullSeasonBias)) { }              # The file needs to exist. Put some small nonzero file out there for all out years.
	
if (filesize($fullSeasonBias) > 100 ){	              # Filesize will be small if there is no data yet. This avoids a warning in the apache error log

$data = file_get_contents("$fullSeasonBias");
$lineFromText = explode("\n", $data);
echo "<tr bgcolor=#FF4D4D><td colspan=\"2\" align=\"center\" style=\"color:white; font-weight:bold; font-stretch:ultra-condensed; font-family:Arial; text-decoration:underline;  \" ><i>Heavily over-ranked teams</i></td></tr>";
foreach($lineFromText as $line) {
		$biasData = explode(":", $line);	
		
		if ($biasData[8] > 0.03) {	echo " <tr bgcolor=#FFAAAA style=\"font-family:Arial;\"> "; 
		echo " <td> $biasData[0] </td>  ";
		echo "  <td> $biasData[8] </td>  ";
		echo "  </tr> ";
		}}

$data = file_get_contents("$fullSeasonBias");
$lineFromText = explode("\n", $data);
echo "<tr bgcolor=#888888><td colspan=\"2\" align=\"center\" style=\"color:white; font-weight:bold; font-stretch:ultra-condensed; font-family:Arial; text-decoration:underline;  \"><i>Computer and playoff committee agreement</i></td></tr>";
foreach($lineFromText as $line) {
		$biasData = explode(":", $line);		
		if ($biasData[8] > -0.03 && $biasData[8] < 0.03) {	echo " <tr bgcolor=#C0CCCC style=\"font-family:Arial;\"    > "; 
		echo " <td> $biasData[0] </td>  ";
		echo "  <td> $biasData[8] </td>  ";
		echo "  </tr> ";
		}}

$data = file_get_contents("$fullSeasonBias");
$lineFromText = explode("\n", $data);
echo "<tr bgcolor=#0AAF0A><td colspan=\"2\" align=\"center\" style=\"color:white; font-weight:bold; font-stretch:ultra-condensed; font-family:Arial; text-decoration:underline;   \"><i>Heavily under-ranked teams</i></td></tr>";
foreach($lineFromText as $line) {
		$biasData = explode(":", $line);		
		if ($biasData[8] < -0.03) {	echo " <tr bgcolor=#88CC00 style=\"font-family:Arial;\"  > "; 
		echo " <td> $biasData[0] </td>  ";
		echo "  <td> $biasData[8] </td>  ";
		echo "  </tr> ";
		}}



} else {
echo "The first team biases will be available after the 1st college football playoff committee rankings on Nov 1.  ";	
	
}
 
 	
 	?>			
  		
  	</table>	
  		
  		
  		
  		
  		
  		</td>

  
  	
  	
  	
  </tr>
  
  <tr>

  	
  	
  </tr>
  
  	
  	</table>
 

<br>
PlayoffPredictor.com site by <a href="http://blog.agafamily.com">Neville Aga</a>    
<a href="https://twitter.com/CiscoNeville" class="twitter-follow-button" data-show-count="false">Follow @CiscoNeville</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>


 <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>

  
  <script>
	$(".explain").click(function(e) {
    var x = e.pageX - this.offsetLeft - 500;
    var y = e.pageY - this.offsetTop + 22;
    $(".tooltip").show().css({
        left: x,
        top: y
    }).delay(30000).fadeOut();
    return false;
});

$(".tooltip").click(function() {
    $(this).hide(); 
});	 	
		 </script>

&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp <a href="https://patreon.com/bePatron?u=81520373">Become a patron <img src="https://c5.patreon.com/internal/mobile/patreon-app-icon@3x.png" width="25" height="25">  </a>  </p><br>


<p style="vertical-align: middle;"> We now have an Appple app store app! Try it now -> 
	<a href="https://apps.apple.com/us/developer/neville-aga/id1280755735"><img src="Download_on_the_App_Store_Badge_US-UK_135x40.svg" style="vertical-align: middle;"></a>
</p>
    </body>
 
</html>
