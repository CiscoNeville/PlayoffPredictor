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

#<!--banner and menu-->    
if($useApp != 'yes') {                #do not show this banner if called in the app
$banner = '/var/www/ppDocs/banner-and-menu.html';
$data = file($banner) or die('Could not read file!');
foreach ($data as $line) {
echo "$line";
}
}
?>  

<p>Below are the current probabilities for each team to make the college football playoff<br> Data is based on games played to this point and future scheduling using a Monte-Carlo simulation of 1000 iterations.<br> Updated each week on Saturday afternoon, evening, and night.</p>

	
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

#Move this data to a file, but for now...
$conference = array(
    "Alabama" => "SEC",
    "Georgia" => "SEC",
    "Tennessee" => "SEC",
    "Ole Miss" => "SEC",
    "Mississippi State" => "SEC",
    "Ohio State" => "B10",
    "Michigan" => "B10",
    "Penn State" => "B10",
    "TCU" => "B12",
    "Kansas" => "B12",
    "Oklahoma State" => "B12",
    "Kansas State" => "B12",
    "Texas" => "B12",
    "Clemson" => "ACC",
    "Syracuse" => "ACC",
    "Wake Forest" => "ACC",
    "NC State" => "ACC",
    "UCLA" => "Pac12",
    "USC" => "Pac12",
    "Oregon" => "Pac12",
    "James Madison" => "G5",
) ;

?>




<!-- make a 6 column table, by conference -->
<!-- absolute positioning, 1415-10X for top positioning where X is the % chance to make playoff -->
<table style="width:925px;border:5px;cellpadding:0px;cellspacing:0px">
<tr> 




<?php

$conferenceColumn = array(
    "SEC" => "30",
    "B10" => "188",
    "B12" => "346",
    "ACC" => "502",
    "Pac2" => "656",
    "G5" => "810",
) ;

$overlap =array();   #for overlapping visuals. if $overlap contains 301 that the SEC 1% slot is filled(needs 2).  3030 is the SEC 30% slot (needs 4). 18862 is B10 62% slot.


$conferenceTotalPercentage = array("SEC" => 0, "B10" => 0, "B12" => 0, "ACC" => 0, "Pac2" => 0, "G5" => 0);    #set all to 0 to start




asort($teamPlayoffProbability);  #do this so I am working up the stack - starting with the 1% chances and ending at 100%. duplicates are pushed up visually
foreach ($teamPlayoffProbability as $team => $playoffProbability) {
    if ($playoffProbability > 1){     #dont bother displaying if chances are less than 1%
    $playoffProbability = round($playoffProbability, 0);    #for displaying and computation of v height, round to a whole number percentage
    $visualOffset = 0;
    $playoffProbabilityHeight = $playoffProbability;

    $c = $conference[$team];     #$c will be ACC, B12, etc
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







    $vPosition = 1410 - 10.2*$playoffProbabilityHeight;



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




    $conferenceTotalPercentage[$conference[$team]] = $conferenceTotalPercentage[$conference[$team]] + $playoffProbability;
    print '<td style="position:absolute;  top:';
    print "$vPosition";
    print 'px; left:';
    print "$hPosition";
    print "px; width:100px; line-height:$boxHeight";
    print "px; height:$boxHeight";
    print 'px; text-align:right">  <img src="http://sports.cbsimg.net/images/collegefootball/logos/';
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
    print "$conferenceTotalPercentage[$conferenceName]%";
    print '<table style="border:1px; border-radius: 10px" background="yardstick.png" width="150px" height="1048px">';
    print '</table>';
    print '</td>';
}


?>

</tr>


</table>







</body>
</HTML>