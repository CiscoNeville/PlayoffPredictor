<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
"http://www.w3.org/TR/html4/strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Current Predicted Rankings</title>
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

	<td class="bucket1"><center><b>Predicted Playoff Committee Rankings</b></center><hr>
  	<h4>Predicted committee top 25:</h4>





 <?php 
$ratings = '/home/neville/cfbPlayoffPredictor/data/current/CurrentPredictedRankings.txt';
$data = file_get_contents("$ratings");

$lineFromText = explode("\n", $data);
$row=0;
foreach($lineFromText as $line) {
		$rankNum = $row+1;
		$teamData = explode(":", $line);
		
	if($row <25) {
		echo "";
		echo "$rankNum".". ";
		echo "<a href=\"/analyzeSchedule.php?team1=$teamData[0]\">$teamData[0]</a>  ";
		echo "&nbsp ($teamData[1])<BR>";

		echo "";
			$row++;
		}
	}
    ?>  	




</td>




</table>
<br>
<p><em>About these rankings:</em> This is a prediction of the order teams will be ranked in the next Tuesday poll (<?php    echo date("F jS", strtotime("next Tuesday") );  ?>).<br> Predicted rankings are based on results from games played this season through today        
(<?php           echo date('l F jS');	?>).
	
	 This is not a listing of last week's poll (<?php    echo date("F jS", strtotime("last Tuesday"));  ?>) and the order here will be different from that poll. As games get played on Saturday the computer will update true computer rank for each team and that will make this list become more accurate as games go final on Saturday. </p>

	</body>
</html>

