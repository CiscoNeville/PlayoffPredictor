<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
"http://www.w3.org/TR/html4/strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>PlayoffPredictor.com FAQ</title>
		<meta name="author" content="root" />
		<!-- Date: 2014-11-09 -->
        <link type="text/css" rel="stylesheet" href="style.css" />
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
   
    
    
<!--banner and menu-->    
   <?php 
$banner = '/var/www/ppDocs/banner-and-menu.html';
$data = file($banner) or die('Could not read file!');
foreach ($data as $line) {
echo "$line";
}
    ?>  



<h2>Frequently asked questions:</h2>
<h3>What is this site?</h3>
This site predicts how the college football committee will rank will rank the top 25 teams in college football, which determine bowl matchups and the 4 teams that make the college football playoff.

<br><br><br>
<h3>How confident are you that this site's predictions will actually match/foretell what the playoff committee actually decides?</h3>
Very confident.  Especially for the final poll of the year. The BCS computers typically got those spot on.  Let's just say it is more in line than Howard Schnellenberger's coach's poll ballots. <br><br>
Update: Nov 12, 2014:  Wow, this got that TCU would be in the top 4 instead of Bama -- so far 2/2 in predicting the top 4 right every time! <br><br>
<bold>Update: Dec 7, 2014:  Excellent!! This got every single top 4 correct every single week.  Called Ohio State in the final poll on Twitter on Dec 6:   <a href="https://twitter.com/CiscoNeville/status/541451272038010881"> https://twitter.com/CiscoNeville/status/541451272038010881 </a> </bold><br><br>
Update: December 10, 2017: Well, after 3 years of spot on predictions, the model failed for 2017.  The model did not call for Alabama over Ohio State, it did not even call for Ohio State... It called for a 3-loss Auburn to reach the final 4.  For 2018 I am going to tweak the model to use something like margin of victory to hopefully converge on better mathematical rankings earlier in the season. 

<br><br><br>
<h3>How are the predicted ratings calculated?</h3>
The predicted rankings for each team are computed with a computer based rating system to start with (a variant of the Colley Matrix method) and then adjusted with a ratings bias, calculated for each team as the difference between the computer rating and the committee rating. 
The result of the computer rankings will give each team a rating between approximately 0.0 and 1.0.  The ratings bias for each team will be between approximately -0.1 to +0.1.  Each team's rating bias is added to that team's computer rating and an effective playoff committee rating is calculated for each team.   The teams are then ranked from highest to lowest based on their effective playoff committee ratings.

<br><br><br>
<h3>What is the ratings bias?</h3>
Each Tuesday after the committee releases rankings, those rankings are mapped to a rating vale. The mapping is done with the expectation that the result of the computer model rates teams on a normal distribution curve with a mean of 0.5. The 1st place team in the committee gets assigned a committee rating of 1.0 with the curve continuing to the 25th place team getting assigned a committee rating of 0.695.   The computer model is run (independent of the committee rankings / ratings mapping) with the result of each team getting a calculated ratings value.  The ratings bias then becomes the difference between the playoff committee mapped rating to the computer model rating.  <br><br>
A positive rating bias indicates the committee places more value in a team than the computer would indicate and a negative rating bias indicates the committee places less value on a team than the computer indicates.<br><br>
Each week the rating biases are computed for each team, and the average rating bias across all weeks is used for the next week's calculation. 
<font color="#B80010">As long as the ratings biases do not materially change during the course of a season (for example, a single team has a high bias, but maintains that same bias week after week) then the method will very effectively predict the committee's actions. </font> <br><br>
Hey, this is the world of big data. If Google can predict what you are thinking before you think it, the method can predict the committee's output before the committee states it.


<br><br><br>
<h3>What is the underlying mathematical model for the calculated true ratings?</h3>
The calculated true ratings are based on a modified Colley Matrix method.  

<br><br><br>
<a name = "Modified"></a>
<h3>What do you mean by "modified" Colley Matrix</h3>
My one knock on the Colley Matrix method it is not in the math of the method, but in the way that games between FCS (1-AA) teams and FBS (1-A) teams are calculated.  Since there is very little connection between FCS and FBS teams, mathematically there is a high variance on how an individual FCS team compares to an individual FBS team (where there is more connection and hence less variance between 2 FBS teams).  The original Colley Matrix method to deal with this lack of connectedness was to simply ignore FBS vs FCS results. This had the net effect of giving a mathematical boost to teams who played FCS teams (whether they won or lost). <br>
 After Appalachian State beat a highly ranked Michigan in Ann Arbor in 2007, the Colley Matrix method was extended to "groups" of FCS teams. The net result is the FCS teams had a rating approximately equal to the worst FBS teams, and the matrix accounted for these games in a way that has a high accuracy to the human polls.  The modification made by playoffPredictor.com is to account all FCS teams as a single team. That keeps the matrix much "purer" and the math much simpler than a calculated number of FCS groups.   At the end of the season this single FCS team will have a record of something like 8 wins and 80 losses against all FBS opponents, giving the FCS schools a very low rating, significantly below the rating of even the worst FBS teams. To me, that's the right way to calculate it. You should be penalized heavily for scheduling an FCS team, and if you actually lose to an FCS team you should be severely penalized.<br><br>
So, the true computer ratings given on this site are based on the Colley Matrix with all FCS teams grouped into a single team.  <br>
In addition, starting with the 2018 season the computer rankings are modified to include margin of victory. More details on that in this <a href="http://blog.agafamily.com/?p=296">blog post</a>.





<br><br><br>
<h3>What team should I cheer for?</h3>
Auburn. You should cheer for Auburn University.  If you don't like that you can also cheer for the University of Oklahoma.  If you can't live with either of those options, you should probably find a different website.










	</body>
</html>

