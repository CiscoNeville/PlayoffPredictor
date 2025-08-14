<html>

<head>
<meta name=Title content="2024 ncf playoff committee rating biases">
<meta name=Keywords content="ncf playoff committee rating biases 2024">
<title>2024 ncf playoff committee rating biases</title>
<link type="text/css" rel="stylesheet" href="style.css" />
</head>

<body link=blue vlink=purple>
	
	
<!-- Google Analytics Code-->    
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

	
<table class="rating-biases-table" border=0 cellpadding=0 cellspacing=0 width=1097 style='border-collapse: collapse;table-layout:fixed;width:1097pt'>

 <col width=114 style='width:114pt'>
 <col width=116 style='width:116pt'>
 <col width=27 style='width:27pt'>
 <col width=71 span=6 style='width:71pt'>
 <col width=21 style='width:21pt'>
 <col class=xl68 width=131 span=3 style='width:131pt'>

 <tr height=23 style='height:23.0pt'>
  <td height=23 class=xl70 colspan=4 width=328 style='height:23.0pt;width:328pt'>PlayoffPredictor.com rating biases for 2024 season:</td>
 </tr>
 <tr>
  <td style='background-color:ffcccc'>  High (positive) numbers mean that the playoff committee values a team more than the computer values that team (team is overrated by committee)</td>
 </tr>
 <tr>
  <td style='background-color:f7ffe6'>  Low (negative) numbers mean that the computer values a team more than the playoff committee values that team (team is <font class="font13">underrated</font> by committee)</font></td>
 </tr>
 
 <tr>
 	<td>A zero for the bias means that the playoff committee and the computer are in agreement on the rating for that team</td>
 </tr>

<tr>
	<td>blank cells mean that team is out of the top 25 in both the committee and computer for that week</td>
</tr>

<tr>
	<td><hr></hr></td>
</tr>
<tr>
	<td></td>
</tr>
<tr>
	<td>Notes on week numbers:</td>
</tr>
<tr>
	<td>&nbsp Week 13 is the last regular season week (Auburn-Alabama week)</td>
</tr>
<tr>
	<td>&nbsp Week 14 is conference championship week</td>
</tr>


	<!--&nbsp The model only uses committee data from weeks 9 through 13 (total of 5 weeks of data) to predict final poll.-->



 <tr height=14 style='height:14.0pt'></tr>
 <tr height=14 style='height:14.0pt'></tr>

<tr height=16 style='height:16.0pt'>
  <td height=16 class=xl70 style='height:16.0pt'>&nbsp;</td>
  <td colspan=8 class=xl71 style='background-color:cceeff'    >Rating Biases</td>
  <td></td>
  <td class=xl65></td>  <!-- this makes a yellow bar above the labels for each column -->
  <td class=xl65></td>
  <td class=xl65></td>
 </tr>
 <tr height=15 style='height:15.0pt'>
  <td height=15 class=xl70 style='height:15.0pt'>Team</td>
  <td class=xl72>Average of all weeks</td>
  <td class=xl72>&nbsp;</td>
  <td class=xl72>Week 9</td>
  <td class=xl72>Week 10</td>
  <td class=xl72>Week 11</td>
  <td class=xl72>Week 12</td>
  <td class=xl72>Week 13</td>
  <td class=xl72>Week 14</td>
<!--  <td class=xl72>Week 15</td>      #todo - will need to reformat styles to get this column inserted. for now leave it off-->
<!-- #to understand - some seasons do sec and other conference champtionship games on week 14, some on week 15. 2014 and 2019 were week 15s. This should be automated in code, todo -->

  <td></td>
  <td class=xl72>Maximum Rating Bias</td>
  <td class=xl72>Minimum Rating Bias</td>
  <td class=xl72>Range</td>
 </tr>
 
 
 
 
 
<?php

$fullSeasonBias = '/home/neville/cfbPlayoffPredictor/data/2024/fullSeasonCommitteeBiasMatrix.txt';
$data = file_get_contents("$fullSeasonBias");

$lineFromText = explode("\n", $data);
$row=0;
foreach($lineFromText as $line) {

	if ($line == '') { #ignore empty last line
		continue;
	}

	$biasData = explode(":", $line);

	#here I should round everything to 3 significant digits, except for 0. ToDo.
	#if ((length($biasData[8])) == 6 ) {
	#$biasData[8] = substr ($biasData[8],0,5);   # want this to 3 significant digits, not 4
	#}

		
	if ($biasData[8] > .03) {echo " <tr height=15 style='height:15.0pt; background-color:ffcccc'    >";}  
	elseif ($biasData[8] < -.03)  {echo " <tr height=15 style='height:15.0pt; background-color:f7ffe6'    >";}
	else  {echo " <tr height=15 style='height:15.0pt; background-color:f2f2f2'    >";}

	echo " <td height=15 style='height:15.0pt'>$biasData[0]</td>  ";
	echo "  <td class=xl73>$biasData[8]</td>  ";
	echo "  <td class=xl74></td> "; 
	echo "  <td class=xl73>$biasData[1]</td> ";
	echo "  <td class=xl73>$biasData[2]</td> ";
	echo "  <td class=xl73>$biasData[3]</td> ";
	echo "  <td class=xl73>$biasData[4]</td> ";
	echo "  <td class=xl73>$biasData[5]</td> ";
	echo "  <td class=xl73>$biasData[6]</td> "; 
	#	echo "  <td class=xl73>$biasData[7]</td> ";   #todo - need sheet formatting before I can use this
	echo "  <td class=xl73></td> ";
	echo "  <td class=xl73>$biasData[9]</td> ";
	echo "  <td class=xl73>$biasData[10]</td> ";
	echo "  <td class=xl73>$biasData[11]</td> ";
	echo "  </tr> ";
	}

 	
?>
 




</table>
	

<br><br>
<p>
	<a href="./2014NcfRatingBiases.php">Click here for 2014 Rating Biases</a><br>
	<a href="./2015NcfRatingBiases.php">Click here for 2015 Rating Biases</a><br>
	<a href="./2016NcfRatingBiases.php">Click here for 2016 Rating Biases</a><br>
	<a href="./2017NcfRatingBiases.php">Click here for 2017 Rating Biases</a><br>
	<a href="./2018NcfRatingBiases.php">Click here for 2018 Rating Biases</a><br>
	<a href="./2019NcfRatingBiases.php">Click here for 2019 Rating Biases</a><br>
	<a href="./2020NcfRatingBiases.php">Click here for 2020 Rating Biases</a><br>
    <a href="./2021NcfRatingBiases.php">Click here for 2021 Rating Biases</a><br>
	<a href="./2022NcfRatingBiases.php">Click here for 2022 Rating Biases</a><br>
	<a href="./2023NcfRatingBiases.php">Click here for 2023 Rating Biases</a><br>
</p>



</body>

</html>
