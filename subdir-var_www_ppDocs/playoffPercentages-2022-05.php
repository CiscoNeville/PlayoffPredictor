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

<p>The computer has simulated the season 1000 times with the computer ratings and committee bias as calculated now.<br>Click a team to see how they made the playoffs</p>

	
<?php	
#Read in current calculated ratings, so can see elo winning probabilities
$currentPlayoffProbabilitiesFile = '/home/neville/cfbPlayoffPredictor/data/current/CurrentPlayoffProbabilities.txt';
$data = file_get_contents("$currentCalculatedRatingsFile");

$lineFromText = explode("\n", $data);
$teamRating = array ();        				#PHP doc says it is best to explicitly define the array (equivalent of perl hash)
foreach($lineFromText as $line){
$teamData = explode(":", $line);
	$teamName = $teamData[0];
$teamRating[$teamName] = $teamData[1];      #makes $teamRating[Georgia] = 0.950
}
#var_dump($teamRating);


?>


<!-- make a 6 column table, by conference -->
<!-- absolute positioning, 1415-10X for top positioning where X is the % chance to make playoff -->
<table style="width:95%;border:5px;cellpadding:0px;cellspacing:0px">
<tr> 

<td style="border-radius: 5px; background-color:#FFFFFF""> SEC: 125%
    <table style="border:1px; border-radius: 10px" background="yardstick.png" width="150px" height="1048px">
        <td style="position:absolute;  top:895px; left:30px; width:100px; line-height:50px; height:50px">  <img src="http://sports.cbsimg.net/images/collegefootball/logos/50x50/BAMA.png" align="left"> 52%</td>
        <td class="trigger_popup_fricc" style="position:absolute;  top:1095px; left:30px; width:100px; line-height:50px; height:50px">  <img src="http://sports.cbsimg.net/images/collegefootball/logos/50x50/UGA.png" align="left"> 32% </td> 
        <td style="position:absolute;  top:1145px; left:30px; width:100px; line-height:50px; height:50px">  <img src="http://sports.cbsimg.net/images/collegefootball/logos/50x50/MISS.png" align="left"> 27%</td>
        <td style="position:absolute;  top:1345px; left:30px; width:100px; line-height:25px; height:25px">  <img src="http://sports.cbsimg.net/images/collegefootball/logos/25x25/TENN.png" align="left"> 7%</td>
        <td style="position:absolute;  top:1365px; left:30px; width:100px; line-height:25px; height:25px">  <img src="http://sports.cbsimg.net/images/collegefootball/logos/25x25/MISSST.png" align="left"> 5%</td>
        <td style="position:absolute;  top:1395px; left:30px; width:100px; line-height:25px; height:25px">  <img src="http://sports.cbsimg.net/images/collegefootball/logos/25x25/LSU.png"  align="left" > 2%</td>
    </table>
</td>


<td style="border-radius: 5px; background-color:#FFFFFF""> BigTen: 107%
    <table style="border:1px; border-radius: 10px" background="yardstick.png" width="150px" height="1048px" >
        <td style="position:absolute;  top:755px; left:188px; width:100px; line-height:50px; height:50px; text-align:right">  <img src="http://sports.cbsimg.net/images/collegefootball/logos/50x50/OHIOST.png" align="left"> 66%</td>
        <td style="position:absolute;  top:1135px; left:188px; width:100px; line-height:50px; height:50px; text-align:right">  <img src="http://sports.cbsimg.net/images/collegefootball/logos/50x50/PSU.png" align="left"> 28%</td>
        <td style="position:absolute;  top:1285px; left:188px; width:100px; line-height:25px; height:25px; text-align:right">  <img src="http://sports.cbsimg.net/images/collegefootball/logos/25x25/MICH.png" align="left"> 13%</td>
<!--            <tr>   <td style="position:absolute;  top:1407px; left:170px; width:100px; height:25px; text-align:right">  <img src="http://sports.cbsimg.net/images/collegefootball/logos/25x25/MD.png" align="left"> 0.3%</td>  </tr> -->    
<!--            <tr>   <td style="position:absolute;  top:1415px; left:170px; width:100px; height:25px; text-align:right">  <img src="http://sports.cbsimg.net/images/collegefootball/logos/25x25/ILL.png" align="left"> 0.1%</td>  </tr>  -->   
    </table>
</td>
   

<td style="border-radius: 5px; background-color:#FFFFFF"">  Big12: 59%
    <table style="border:1px; border-radius: 10px" background="yardstick.png" width="150px" height="1048px">
    <a class="button" href="#popup_flight_travlDil1"> <td style="position:absolute;  top:975px; left:346px; width:100px; line-height:50px; height:50px" >  <img src="http://sports.cbsimg.net/images/collegefootball/logos/50x50/KANSAS.png" align="left"> 44%</td> </a>
        <td style="position:absolute;  top:1295px; left:346px; width:100px; line-height:25px; height:25px">  <img src="http://sports.cbsimg.net/images/collegefootball/logos/25x25/TCU.png" align="left"> 12%</td> 
        <td style="position:absolute;  top:1395px; left:346px; width:100px; line-height:25px; height:25px">  <img src="http://sports.cbsimg.net/images/collegefootball/logos/25x25/OKLAST.png" align="left"> 2%</td>    
        <td style="position:absolute;  top:1420px; left:346px; width:100px; line-height:25px; height:25px">  <img src="http://sports.cbsimg.net/images/collegefootball/logos/25x25/KSTATE.png" align="left"> 1%</td>          
<!--            <tr>   <td style="position:absolute;  top:1437px; left:320px; width:100px; height:25px">  <img src="http://sports.cbsimg.net/images/collegefootball/logos/25x25/TEXAS.png" align="left"> 0.1%</td>  </tr> -->
    </table>
</td>

<td style="border-radius: 5px; background-color:#FFFFFF"> ACC: 65%
    <table style="border:1px; border-radius: 10px" background="yardstick.png" width="150px" height="1048px">
        <td style="position:absolute;  top:985px; left:502px; width:100px; line-height:50px; height:50px">  <img src="http://sports.cbsimg.net/images/collegefootball/logos/50x50/CLEM.png" align="left"> 43%</td>  
        <td style="position:absolute;  top:1255px; left:502px; width:100px; line-height:25px; height:25px">  <img src="http://sports.cbsimg.net/images/collegefootball/logos/25x25/CUSE.png" align="left"> 16%</td>   
        <td style="position:absolute;  top:1370px; left:502px; width:100px; line-height:25px; height:25px">  <img src="http://sports.cbsimg.net/images/collegefootball/logos/25x25/WAKE.png" align="left"> 3%</td>  
        <td style="position:absolute;  top:1395px; left:502px; width:100px; line-height:25px; height:25px">  <img src="http://sports.cbsimg.net/images/collegefootball/logos/25x25/NCST.png" align="left"> 2%</td>  
        <td style="position:absolute;  top:1420px; left:502px; width:100px; line-height:25px; height:25px">  <img src="http://sports.cbsimg.net/images/collegefootball/logos/25x25/FSU.png" align="left"> 1%</td>  
<!--            <tr>   <td style="position:absolute;  top:1437px; left:470px; width:100px; height:25px; text-align:right">  <img src="http://sports.cbsimg.net/images/collegefootball/logos/25x25/MD.png" align="left"> 0.3%</td>  </tr>  -->
    </table>
</td>


<td style="border-radius: 5px; background-color:#FFFFFF""> Pac12: 41%
    <table style="border:1px; border-radius: 10px" background="yardstick.png" width="150px" height="1048px">
        <td style="position:absolute;  top:1230px; left:656px; width:100px; line-height:25px; height:25px">  <img src="http://sports.cbsimg.net/images/collegefootball/logos/25x25/USC.png" align="left"> 18%</td> 
        <td style="position:absolute;  top:1265px; left:656px; width:100px; line-height:25px; height:25px">  <img src="http://sports.cbsimg.net/images/collegefootball/logos/25x25/UCLA.png" align="left"> 15%</td>              
        <td style="position:absolute;  top:1335px; left:656px; width:100px; line-height:25px; height:25px">  <img src="http://sports.cbsimg.net/images/collegefootball/logos/25x25/OREG.png" align="left"> 8%</td>  
    </table>
</td>


<td style="border-radius: 5px; background-color:#FFFFFF""> G5: 1%
    <table style="border:1px; border-radius: 10px" background="yardstick.png" width="150px" height="1048px">
        <td style="position:absolute;  top:1420px; left:810px; width:100px; line-height:25px; height:25px">   <img src="http://sports.cbsimg.net/images/collegefootball/logos/25x25/JMAD.png" align="left"> 1%</td>   
    </table>
</td>













</tr>


</table>


<div class="hover_bkgr_fricc">
    <span class="helper"></span>
    <div>
        <div class="popupCloseButton">&times;</div>
        <p>
LSU beats Tennessee in week 6<br>
Utah beats UCLA in week 6<br>
Tennessee beats Georgia in week 10<br>
Ole Miss beats Alabama in week 11<br>
Maryland beats Ohio State in week 12<br>
Georgia beats Ole Miss (SEC Championship)<br>
</p>
    </div>
</div>


<div id="popup_flight_travlDil1" class="overlay_flight_traveldil">
	<div class="popup_flight_travlDil">
		<h2>Kansas Path</h2>
		<a class="close_flight_travelDl" href="#">&times;</a>
		<div class="content_flightht_travel_dil">
<p>
LSU beats Tennessee in week 6<br>
Utah beats UCLA in week 6<br>
Tennessee beats Georgia in week 10<br>
Ole Miss beats Alabama in week 11<br>
Maryland beats Ohio State in week 12<br>
Georgia beats Ole Miss (SEC Championship)<br>
</p>
        </div>
	</div>
</div>


<div id="popup_flight_travlDil2" class="overlay_flight_traveldil">
	<div class="popup_flight_travlDil">
		<h2>TCU path</h2>
		<a class="close_flight_travelDl" href="#">&times;</a>
		<div class="content_flightht_travel_dil">
			2nd POPUP
		</div>
	</div>
</div>






<script>
    $(window).load(function () {
    $(".trigger_popup_fricc").click(function(){
       $('.hover_bkgr_fricc').show();
    });
    $('.hover_bkgr_fricc').click(function(){
        $('.hover_bkgr_fricc').hide();
    });
    $('.popupCloseButton').click(function(){
        $('.hover_bkgr_fricc').hide();
    });
});
</script>


</body>
</HTML>