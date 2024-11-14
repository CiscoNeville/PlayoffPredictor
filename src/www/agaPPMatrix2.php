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

   <?php

#!/usr/bin/perl
##############
#
# agaMatrix.pl
# Determine the FBS playoff committee bias per team and predict the future playoff committee rankings by Implement the 
# Aga Playoff Predictor Matrix method.
# The Aga Playoff Predictor Matrix method uses ideas first proposed by Wes Colley in the Colley Matrix method
# with the addition of a single team to represent all 1AA schools
# Furthermore, the method computes a playoff committee rating bias (rbCV)  which is used to predict the order the playoff committee will rank the teams in the future 
# on the dataset plus an team bias determined by the committee
#
##############



use strict;
#use CGI ':standard';   #commenting this out to overwrite an error.  is this file in use?
use Math::MatrixReal;
#use Number::Format;
use Data::Dumper;




#Define FBS teams
my %team = ( "1" => "Boston College", "2" => "Clemson", "3" => "Duke", "4" => "Florida State", "5" => "Georgia Tech", "6" => "Louisville", "7" => "Miami (FL)", "8" => "North Carolina", "9" => "North Carolina State", "10" => "Pittsburgh", "11" => "Syracuse", "12" => "Virginia", "13" => "Virginia Tech", "14" => "Wake Forest"); 
%team = (%team, "15" => "Baylor", "16" => "Iowa State", "17" => "Kansas", "18" => "Kansas State", "19" => "Oklahoma", "20" => "Oklahoma State", "21" => "TCU", "22" => "Texas", "23" => "Texas Tech", "24" => "West Virginia");
%team = (%team, "25" => "Illinois", "26" => "Indiana", "27" => "Iowa", "28" => "Maryland", "29" => "Michigan", "30" => "Michigan State", "31" => "Minnesota", "32" => "Nebraska", "33" => "Northwestern", "34" => "Ohio State", "35" => "Penn State", "36" => "Purdue", "37" => "Rutgers", "38" => "Wisconsin");
%team = (%team, "39" => "Arizona", "40" => "Arizona State", "41" => "California", "42" => "Colorado", "43" => "Oregon", "44" => "Oregon State", "45" => "Stanford", "46" => "UCLA", "47" => "USC", "48" => "Utah", "49" => "Washington", "50" => "Washington State");
%team = (%team, "51" => "Alabama", "52" => "Arkansas", "53" => "Auburn", "54" => "Florida", "55" => "Georgia", "56" => "Kentucky", "57" => "LSU", "58" => "Mississippi State", "59" => "Missouri", "60" => "Ole Miss", "61" => "South Carolina", "62" => "Tennessee", "63" => "Texas A&M", "64" => "Vanderbilt");   #ESPN calls the team Ole Miss, not Mississippi
%team = (%team, "65" => "Cincinnati", "66" => "Connecticut", "67" => "East Carolina", "68" => "Houston", "69" => "Memphis", "70" => "SMU", "71" => "South Florida", "72" => "Temple", "73" => "Tulane", "74" => "Tulsa", "75" => "UCF");
%team = (%team, "76" => "Florida Atlantic", "77" => "Florida International", "78" => "Louisiana Tech", "79" => "Marshall", "80" => "Middle Tennessee", "81" => "North Texas", "82" => "Old Dominion", "83" => "Rice", "84" => "Southern Miss", "85" => "UAB", "86" => "UTEP", "87" => "UTSA", "88" => "Western Kentucky");
%team = (%team, "89" => "Akron", "90" => "Ball State", "91" => "Bowling Green", "92" => "Buffalo", "93" => "Central Michigan", "94" => "Eastern Michigan", "95" => "Kent State", "96" => "Massachusetts", "97" => "Miami (OH)", "98" => "Northern Illinois", "99" => "Ohio", "100" => "Toledo", "101" => "Western Michigan");
%team = (%team, "102" => "Air Force", "103" => "Boise State", "104" => "Colorado State", "105" => "Fresno State", "106" => "Hawaii", "107" => "Nevada", "108" => "New Mexico", "109" => "San Diego State", "110" => "San Jose State", "111" => "UNLV", "112" => "Utah State", "113" => "Wyoming");
%team = (%team, "114" => "Appalachian State", "115" => "Arkansas State", "116" => "Georgia Southern", "117" => "Georgia State", "118" => "Idaho", "119" => "Louisiana-Lafayette", "120" => "Louisiana-Monroe", "121" => "New Mexico State", "122" => "South Alabama", "123" => "Texas State", "124" => "Troy");
%team = (%team, "125" => "Army", "126" => "BYU", "127" => "Navy", "128" => "Notre Dame");
%team = (%team, "129" => "1AA");  #Represent any and all 1AA teams by a single team 

my %teamH = ( "Boston College" => "1", "Clemson" => "2", "Duke" => "3", "Florida State" => "4", "Georgia Tech" => "5", "Louisville" => "6", "Miami (FL)" => "7", "North Carolina" => "8", "North Carolina State" => "9", "Pittsburgh" => "10", "Syracuse" => "11", "Virginia" => "12", "Virginia Tech" => "13", "Wake Forest" => "14"); 
%teamH = (%teamH, "Baylor" => "15" , "Iowa State" => "16" , "Kansas" => "17" , "Kansas State" => "18" , "Oklahoma" => "19" , "Oklahoma State" => "20" , "TCU" => "21" , "Texas" => "22" , "Texas Tech" => "23" , "West Virginia" => "24");
%teamH = (%teamH, "Illinois" => "25" , "Indiana" => "26" , "Iowa" => "27" , "Maryland" => "28" , "Michigan" => "29" , "Michigan State" => "30" , "Minnesota" => "31" , "Nebraska" => "32" , "Northwestern" => "33" , "Ohio State" => "34" , "Penn State" => "35" , "Purdue" => "36" , "Rutgers" => "37" , "Wisconsin" => "38");
%teamH = (%teamH, "Arizona" => "39" , "Arizona State" => "40" , "California" => "41" , "Colorado" => "42" , "Oregon" => "43" , "Oregon State" => "44" , "Stanford" => "45" , "UCLA" => "46" , "USC" => "47" , "Utah" => "48" , "Washington" => "49" , "Washington State" => "50");
%teamH = (%teamH, "Alabama" => "51" , "Arkansas" => "52" , "Auburn" => "53" , "Florida" => "54" , "Georgia" => "55" , "Kentucky" => "56" , "LSU" => "57" , "Mississippi State" => "58" , "Missouri" => "59" , "Ole Miss" => "60" , "South Carolina" => "61" , "Tennessee" => "62" , "Texas A&M" => "63" , "Vanderbilt" => "64");   #ESPN calls the team Ole Miss => not Mississippi
%teamH = (%teamH, "Cincinnati" => "65" , "Connecticut" => "66" , "East Carolina" => "67" , "Houston" => "68" , "Memphis" => "69" , "SMU" => "70" , "South Florida" => "71" , "Temple" => "72" , "Tulane" => "73" , "Tulsa" => "74" , "UCF" => "75");
%teamH = (%teamH, "Florida Atlantic" => "76" , "Florida International" => "77" , "Louisiana Tech" => "78" , "Marshall" => "79" , "Middle Tennessee" => "80" , "North Texas" => "81" , "Old Dominion" => "82" , "Rice" => "83" , "Southern Miss" => "84" , "UAB" => "85" , "UTEP" => "86" , "UTSA" => "87" , "Western Kentucky" => "88");
%teamH = (%teamH, "Akron" => "89" , "Ball State" => "90" , "Bowling Green" => "91" , "Buffalo" => "92" , "Central Michigan" => "93" , "Eastern Michigan" => "94" , "Kent State" => "95" , "Massachusetts" => "96" , "Miami (OH)" => "97" , "Northern Illinois" => "98" , "Ohio" => "99" , "Toledo" => "100" , "Western Michigan" => "101");
%teamH = (%teamH, "Air Force" => "102" , "Boise State" => "103" , "Colorado State" => "104" , "Fresno State" => "105" , "Hawaii" => "106" , "Nevada" => "107" , "New Mexico" => "108" , "San Diego State" => "109" , "San Jose State" => "110" , "UNLV" => "111" , "Utah State" => "112" , "Wyoming" => "113");
%teamH = (%teamH, "Appalachian State" => "114" , "Arkansas State" => "115" , "Georgia Southern" => "116" , "Georgia State" => "117" , "Idaho" => "118" , "Louisiana-Lafayette" => "119" , "Louisiana-Monroe" => "120" , "New Mexico State" => "121" , "South Alabama" => "122" , "Texas State" => "123" , "Troy" => "124");
%teamH = (%teamH, "Army" => "125" , "BYU" => "126" , "Navy" => "127" , "Notre Dame" => "128");
%teamH = (%teamH, "1AA" => "129");  #Represent any and all 1AA teams by a single team 



my $ncfScoresFile = "/root/cfbPlayoffPredictor/ncfScoresFile.txt";
my $ncfScheduleFile = "/root/cfbPlayoffPredictor/ncfScheduleFile.txt";
my $committeeCurrentRankingsFile = "/root/cfbPlayoffPredictor/CurrentPlayoffCommitteeRankings.txt";
my $committeeBiasFile = "/root/cfbPlayoffPredictor/committeeBiasFile.txt";
my $agaMatrixFile = "/root/cfbPlayoffPredictor/CurrentAgaMatrix.txt";
my $lastWeekNcfScoresFile = "/root/cfbPlayoffPredictor/ncfScoresFile.txt";

my $cM;   # the Colley Matrix in line 17 of the colley matrix method
my $rCV;  # the r column-vector in line 17 of the colley matrix method
my $bCV;  # the b column-vector in line 17 of the colley matrix method

my @results;
my $resultsWinner;
my $resultsLoser;

my $teamNumberWinner;
my $teamNumberLoser;

my @teamWins;
my @teamLosses;

my @teams=keys(%team);
my $numberOfTeams = $#teams + 1;  #$#teams starts counting at zero

my @agaRating;
my $rematch =0;

my @sortedOutput1;
my @sortedOutput2;
my @teamWithAgaRank;


#get the right set of scores to use
#my $date = $_GET["date"];
my $date = '2014Week3';

if ($date eq '') {  #no date entered - use latest data
 $ncfScoresFile = "/root/cfbPlayoffPredictor/ncfScoresFile.txt";
}
else {
 $date =~ m/(\d\d\d\d)Week(\d+)/;
my $year = $1;
my $week = $2; 

 $ncfScoresFile = '/root/cfbPlayoffPredictor/' . "$year" . '/Week' . "$week" . 'NcfScoresFile.txt';  
 }







#create the matrix



#create blank matricies
$cM = new Math::MatrixReal($numberOfTeams,$numberOfTeams);
$rCV = new Math::MatrixReal($numberOfTeams,1);    #column vector is this notation. lots of rows, 1 column
$bCV = new Math::MatrixReal($numberOfTeams,1);

#print "colley matrix is  $cM\n\n";
#print "r is $rCV\n\n";
#print "b is $bCV\n\n";

#read input data from a file.
my $scoreInput;
my $k;      #there is probably a better way to do this
open (NCFSCORESFILE, "<$ncfScoresFile") or die "$! error trying to open";     
for $scoreInput (<NCFSCORESFILE>) {
#   print $scoreInput;

($resultsWinner, $resultsLoser) = (split /:/, $scoreInput);

$scoreInput =~ m/Final.*: (.+?) (\d+) - (.+?) (\d+)/;

my $aTeamName = $1;
my $aTotal = $2;
my $hTeamName = $3;
my $hTotal = $4;


# Determine if a team is a 1AA team and assign them to the team "1AA"
if (  ($aTeamName ~~ [values %team])    )  {
#that's great, away team is FBS. Do Nothing
}
else {   #otherwise it was a 1AA team
$aTeamName = "1AA";
}


if (  ($hTeamName ~~ [values %team])    )  {    #Like it would ever happen -- a 1A team plays a game on the road against a 1AA team
#that's great, home team is FBS. Do Nothing
}
else {   #otherwise it was a 1AA team
$hTeamName = "1AA";
}



#print "aTeamName is $aTeamName\n";
#print "aTotal is $aTotal\n";
#print "hTeamName is $hTeamName\n";
#print "hTotal is $hTotal\n\n";

#Figure out which team won and give it to the @results array in 1-0 format
 if ($hTotal > $aTotal)  {    #home team won
$results[$k] = "$hTeamName 1-0 $aTeamName";


##I wanna see all the 1AA teams that won
#if (  $hTeamName eq "1AA"    )  {
#print "a 1AA team beat $aTeamName at home!\n";
#}



}
else {    #away team won. No ties anymore...
$results[$k] = "$aTeamName 1-0 $hTeamName";

##I wanna see all the 1AA teams that won
#if (  $aTeamName eq "1AA"    )  {
#print "a 1AA team beat $hTeamName\n";
#}


}
$k++;


}

close NCFSCORESFILE;

my $q = CGI -> new;

my $r;
my $i;
my $s;
my @userInputtedGames;

for (my $t=0; $t<1000; $t++) {
$s = "game"."$t"."result";
$r = $q->param("$s");

#have to remove non-breakable whitespce -- as the cgi enters this crap into the strings, i think
$r =~ s/\x0A//g;
$r =~ s/\x0D//g;


if ($r ne '') {
$results[$k] = "$r";
$k++;

$r =~ s/1-0/<\/b><\/font>beats<font color=\"red\"><b>/;
push @userInputtedGames, "<font color=\"green\"><b>$r <\/b><\/font><br>";

}
}

#for now hard code this
#$results[$k] = "Kansas State 1-0 Iowa State";
#$results[$k+1] = "Oklahoma 1-0 Baylor";
#$results[$k+2] = "Oklahoma State 1-0 Texas Tech";

#populate the matrix with the data

#create zeros for initial wins and losses for every team
for (my $i = 1; $i<$numberOfTeams+1; $i++ ) {
 $teamWins[$i]=0;
 $teamLosses[$i]=0;
}



#Find an individual game winner
for (my $i = 0; $i<$#results+1; $i++ ) {
($resultsWinner, $resultsLoser) = (split / 1-0 /, $results[$i]);


#print "resultsWinner is  $resultsWinner\n";
#print "resultsLoser is  $resultsLoser\n\n";


#now, populate that one win and loss into the cM,
for (my $j = 1; $j<$#teams+3; $j++ ) {           #have not figured out why +3 here...
 if ($team{$j} eq $resultsWinner)  {  
$teamNumberWinner = $j;    
   $teamWins[$j]++;
}
 if ($team{$j} eq $resultsLoser)  {
  $teamNumberLoser = $j;
   $teamLosses[$j]++;
}
}

#print "teamNumberWinner is $teamNumberWinner\n\n";
#print "teamNumberLoser is $teamNumberLoser\n\n";



$rematch = $cM->element($teamNumberWinner,$teamNumberLoser);   #the matrix is symmetrical, so i only have to do this once


   $cM->assign($teamNumberWinner,$teamNumberLoser,-1 + $rematch);     #for 1st rematch   -2 inseted in the array
   $cM->assign($teamNumberLoser,$teamNumberWinner,-1 + $rematch);     
}


#assign the diagonal row of the CM
for (my $i = 1; $i<$#teams+2; $i++ ) {        # dont understand why +2, shuoldnt it be +1
 $cM->assign($i,$i,2+$teamWins[$i]+$teamLosses[$i]);     #the diagonal matrix entries correspond to total games played +2
}




#have to caputure each teams total wins and losses to populate bCV
#see if I did it right above



#Print out the record of each team at this point, if you want
#for (my $i = 1; $i<$numberOfTeams+1; $i++ ) {
#print "$team{$i} record is $teamWins[$i] and $teamLosses[$i]\n";
#}






#input for bCV.   
for (my $i = 1; $i<$#teams+2; $i++ ) {        # dont understand why +2, shuoldnt it be +1
 my $bi=($teamWins[$i]-$teamLosses[$i]);
 $bi=$bi/2;
 $bi=$bi +1;
 $bCV->assign($i,1,$bi);     #bi = 1 + (nwi-nli)/2
}



#print "\n\n";
#print "colley matrix is \n";
#print "$cM\n\n\n";


#print "b is \n";
#print "$bCV\n\n";






#solve for rCV
my $dim;
my $base;
my $LRM;  # this is the LR_Matrix defined in MatrixReal and returned by the method decompose_LR


$LRM = $cM->decompose_LR();


if ( ($dim,$rCV,$base) =  $LRM->solve_LR($bCV) ) {
#print "great, it solved the matrix\n";
#Note, I don't actually have to iterate. The Matrix solution takes care of that for me !

#print "r is \n";
#print "$rCV\n\n";

}
else {
print "crap, there was no solution to the Colley Matrix  (This should not have happened\n)";
die;
}


#print out the team ratings
#1st split the ratings out into an array with an index for each team

my @output;

for (my $i = 1; $i<$#teams+2; $i++ ) {        # dont understand why +2, shuoldnt it be +1
 
#get the team rating in human readable form
my $rCVofI = $rCV->row($i);
$rCVofI =~ /\[(.+?)\]/;
$rCVofI = $1;
$rCVofI = sprintf("%.10g", $rCVofI);
#$rCVofI = format_number($rCVofI, 4);
$rCVofI = substr $rCVofI, 0, 5;     # truncate out anything under thousandnths. note, this casues a loss of precision. display only.
if ($rCVofI eq 0.5)  {     #get 0.5 entries in 0.500 format for later sorting  
 $rCVofI = "0.500";
}

#print "$team{$i} record is $teamWins[$i] and $teamLosses[$i]  - rating is $rCVofI \n";

$output[$i] = "$rCVofI\:\:$team{$i}:$rCVofI: record is $teamWins[$i] and $teamLosses[$i]\n";  #put the rating in front so I can sort on it

}



#my @sortedOutput;
#$Data::Dumper::Sortkeys = sub { [reverse sort keys %{$_[0]}] };   #sort in reverse order - best teams 1st
#print Dumper(@output);



my @sortedOutput = sort @output;




#print "\n\n\n";
#print "@sortedOutput\n";   # gives this weird 60 before it prints the array



foreach (@sortedOutput) {
 my @separated = split('::', $_);
 push @sortedOutput1, $separated[1];    #everything after the ::
}




for (my $i=1; $i<$#teams+2; $i++ ) {        # dont understand why +2, shuoldnt it be +1
 
$sortedOutput2[$i] = $sortedOutput1[$#teams+2-$i];
chomp $sortedOutput2[$i];

$teamWithAgaRank[$i] = $sortedOutput2[$i];
$teamWithAgaRank[$i] =~ /(.+?):(.+?):(.+?)/;
$teamWithAgaRank[$i] = $1;
}



#print out the top 25 teams



#print out all the teams
foreach (@sortedOutput2) {
#  print "$_\n";


 my @separated = split(':', $_);

$agaRating[$teamH{$separated[0]}] = $separated[1];


#print "$separated[0]  rating  $separated[1]\n";


}

#print "sortedOutput2 of 0 is $sortedOutput2[0]    \n";
#print "sortedOutput2 of 1 is $sortedOutput2[1]    \n";




my @ratingBias;
#get the right rating biases from a file
open (COMMITTEEBIASFILE, "<$committeeBiasFile") or die "$! error trying to open";

for my $line (<COMMITTEEBIASFILE>) {
$line =~ m/(.+?) ratingBias is (.+)/;
$ratingBias [$teamH{$1}] = $2;

}
close COMMITTEEBIASFILE;

#print "auburn ratingBias is $ratingBias[$teamH{'Auburn'}]\n";







# calculate b'CV  =  bCV - A * r'CV
# where r'CV is the rating bias column vector


#print "$rCV\n\n";
#print "$cM\n\n";
#print "$bCV\n\n";

my $rbCV = new Math::MatrixReal($numberOfTeams,1);
for (my $i = 1; $i<$#teams+2; $i++ ) {        # dont understand why +2, shuoldnt it be +1
 $rbCV->assign($i,1,$ratingBias[$i]);     #bi = 1 + (nwi-nli)/2
}

#print "$rbCV\n\n";

my $ArbCV = new Math::MatrixReal($numberOfTeams,1);
$ArbCV = $cM -> multiply($rbCV);  

my $bpCV = new Math::MatrixReal($numberOfTeams,1);
$bpCV -> subtract($bCV,$ArbCV);  #b' = b - cM * rb     , where b', b and rb are all column vectors

#print "$bpCV\n\n";

##########################################


 ?>
 
 
 
print header();
print start_html(-title => "$r predicted rankings",  -style=>{-src=>'/style.css'});


print ' <a href="/"><img src="/playoff-predictor.jpg" alt="pp banner"> </a><br><br> ';








#print "<pre>";
#print "elements in array results is $#results<br>";
#print "results 500 is $results[500]<br>";
#print "results 600 is $results[600]<br>";
#($resultsWinner, $resultsLoser) = (split / 1-0 /, $results[500]);
#print "500 winner is "."\'"."$resultsWinner"."\'"." and loser is "."\'"."$resultsLoser"."\'"."<br>";
#($resultsWinner, $resultsLoser) = (split / 1-0 /, $results[600]);
#print "600 winner is "."\'"."$resultsWinner"."\'"." and loser is "."\'"."$resultsLoser"."\'"."<br>";
#($resultsWinner, $resultsLoser) = (split / 1-0 /, $results[500]);
#print "500 winner is $resultsWinner and loser is $resultsLoser<br>";
#($resultsWinner, $resultsLoser) = (split / 1-0 /, $results[600]);
#print "600 winner is $resultsWinner and loser is $resultsLoser<br>";
#print "</pre>";

#for (my $i = 1; $i<$#results+2; $i++ ) {        # dont understand why +2, shuoldnt it be +1
#print "$results[$i]<br>";
#}


print "<h3>With the hypothetical results of:</h3>";
print @userInputtedGames;

#print "<br><br>";



print "<table class=\"buckets\" width=\"80%\" border=\"5px\" cellpadding=\"4px\" cellspacing=\"6px\" >";
print "<tr>";

  my @predictedCommitteeRating;
  my @sortedPredictedCommitteeRating;

  
  
  



  
  for ($k=1;$k<129;$k++) {

$predictedCommitteeRating[$k] = $agaRating[$k] + $ratingBias[$k];
$sortedPredictedCommitteeRating[$k] = "$predictedCommitteeRating[$k] $team{$k}";    #aint actually sorted yet
#print "$team{$k}   $predictedCommitteeRating[$k]\n";
}
  
  
  
  
  
  
  print "<td valign=\"top\">";
    print "<h4>Predicted committee top 25</h4>";
  @sortedPredictedCommitteeRating = reverse sort @sortedPredictedCommitteeRating;
 print "<h5 id=\"top4\">"; 
  for ($k=0;$k<4;$k++) {    
my $m = $k+1;
$sortedPredictedCommitteeRating[$k] =~ m/(.+?) (.+)/;
print "$m. <a href=\"/analyzeSchedule.php?team1=$2\">$2</a>    ($1) <br>";
#print "$m. $sortedPredictedCommitteeRating[$k]<br>";
}
print "</h5>";

  for ($k=4;$k<25;$k++) {    
my $m = $k+1;
$sortedPredictedCommitteeRating[$k] =~ m/(.+?) (.+)/;
print "$m. <a href=\"/analyzeSchedule.php?team1=$2\">$2</a>    ($1)<br>";
#print "$m. $sortedPredictedCommitteeRating[$k]<br>";
}




print "<\/td>";


   print "<br><br><br><br><br>";

  print "<td>";

print "<h4>Mathematical Raings of teams with hypothetical results included</h4>";
#print '<table>';

for ($k=1;$k<130;$k++) {
 
 $sortedOutput2[$k] = "<a href=\"/analyzeSchedule.php?team1=$teamWithAgaRank[$k]\">" . $sortedOutput2[$k] ;
$sortedOutput2[$k] =~ s/:/     <\/a> [/;    #pretty things up
 $sortedOutput2[$k] =~ s/: record is /]    (/;
 $sortedOutput2[$k] =~ s/ and /-/;
 $sortedOutput2[$k] = $sortedOutput2[$k] . ")";
 
#print "<tr><td>$k.</td><td> $sortedOutput2[$k]</td></tr><br>";
print "$k. $sortedOutput2[$k]<br>";
 }
  
#print '</table>';  

#   print "<h4>Current committee top 25</h4>";
# open (COMMITTEECURRENTRANKINGS, "<$committeeCurrentRankingsFile") or die "$! error trying to open";     
#for $k (<COMMITTEECURRENTRANKINGS>) {
#print "$k<br>";
#}


<\/td>
 
    
  
<\/tr>
<\/table><br>



$sortedPredictedCommitteeRating[0] =~ m/(.+?) (.+)/;
my $topFour1 = $2;

$sortedPredictedCommitteeRating[1] =~ m/(.+?) (.+)/;
my $topFour2 = $2;

$sortedPredictedCommitteeRating[2] =~ m/(.+?) (.+)/;
my $topFour3 = $2;

$sortedPredictedCommitteeRating[3] =~ m/(.+?) (.+)/;
my $topFour4 = $2;



Tweet this top 4 <a href=\"https://twitter.com/share\" class=\"twitter-share-button\" data-url=\"http://playoffPredictor.com/predict\" data-text=\"My #cfbPlayoff top 4 are $topFour1, $topFour2, $topFour3 and $topFour4 . Find your top 4:\">Tweet this top 4</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>



  