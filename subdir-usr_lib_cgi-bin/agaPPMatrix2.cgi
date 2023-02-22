#!/usr/bin/perl
##############
#
# agaPPMatrix2.cgi
# Determine the FBS playoff committee bias per team and predict the future playoff committee rankings by implementing the 
# Aga Playoff Predictor Matrix method.
# The Aga Playoff Predictor Matrix method uses ideas first proposed by Wes Colley in the Colley Matrix method
# with the addition of a single team to represent all 1AA schools
# Furthermore, the method computes a playoff committee rating bias (rbCV)  which is used to predict the order the playoff committee will rank the teams in the future 
# on the dataset plus an team bias determined by the committee
#
##############
#
#
# This file has to be completely redone to pull historicals reading in from files, not by reading scores and computing  1/1/2022
#



use strict;
use warnings;   #comment out -- too much in error log
use CGI ':standard';
use Math::MatrixReal;
#use Number::Format;
#use Sort::Naturally;
use Data::Dumper;
use List::MoreUtils qw(first_index);



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


my $mov;
my $movFactor;
my $alpha = 0;     #If alpha=0 this should simplify to original colley matrix.
my $useMarginOfVictory = 0;   #will get set to 1 if using year >= 2018

my %seasonWins;
my %seasonLosses;

my @agaRating;
my $rematch =0;

my @sortedOutput1;
my @sortedOutput2;
my @teamWithAgaRank;

my @agaPPRanking;
my @agaComputerRanking;
my @committeeRanking;      #these 3 are necessary to compute an eta between computer to committee and PP prediction to committee actual

my $q = CGI -> new;

my $year;
my $week;
my $weekNumber;

$year = $q->param("year");

if ($year ge 2018) {
 $useMarginOfVictory = 1;   #default is to use it from 2018 season onwards. Does not need to be called dynamically from previous page, just needs to be selectable here in backend code is enough
 $alpha = -0.1;   #this is the weighting of MoV relative to W/L. At this point chosen as -0.1 based on 2021 game research data. Should weight in future based on math.  Expressed as a negative number. Cij = Cii = alpha.   Bi = -alpha.  
 #HATE TO DO it like the above, but -0.1 gets weird rating for 1AA for 2021 week 17. Have to investigate
}

$week = $q->param("week");
$week =~ m/Week(\d+)/;
$weekNumber = $1;


my $ncfScoresFile = "/home/neville/cfbPlayoffPredictor/data/$year/week$weekNumber/Week$weekNumber"."NcfScoresFile.txt";  
my $committeeRankingsFile = '/home/neville/cfbPlayoffPredictor/data/'."$year".'/'.  "week$weekNumber" .  '/'  .       "$week".'PlayoffCommitteeRankings.txt';
my $averageCommitteeBiasFile = '/home/neville/cfbPlayoffPredictor/data/'."$year".'/'.  "week$weekNumber" .  '/'  .       "$week".'AverageCommitteeBiasFile.txt';



my $fbsTeams = "/home/neville/cfbPlayoffPredictor/data/"."$year"."/fbsTeamNames.txt";



#Check to make sure week 15 of a 14 week season (2015-2018) is not being called.  If so, insturct this week only exists for Army-Navy game
if (($weekNumber == 15) && ($year != 2014)) {
 print header();
print start_html(-title => " predicted rankings",  -style=>{-src=>'/style.css'});

print "<p>Week 15 consists only of the Army-Navy game.  Week 14 is the final cfb ranking and prediction for $year. <br><br>Please enter another year/week to research. </p>";
 
exit;
}




#Read in FBS teams for this year
my (%team,%teamH) = ();   #initialize both hases in one line
open (FBSTEAMS, "<", $fbsTeams) or die "Can't open the file $fbsTeams";
while (my $line = <FBSTEAMS> )   {
    chomp ($line);
    my ($a, $b) = split(" => ", $line);   #this will result in $key holding the name of a team (1st thing before split)
    $team{$b} = $a;
    $teamH{$a} = $b;
}     #at the conclusion of this block $team{4} will be "Florida State" and $teamH{Auburn} will be "53"
my @teams=keys(%team);                     #this creates @teams array which has elements like ("127" , "32" , "90" , "118" ,... , "34") - not sure of the value of that
my $numberOfTeams = $#teams + 1;  #$#teams starts counting at zero









#create the matrix



#create blank matricies
$cM = new Math::MatrixReal($numberOfTeams,$numberOfTeams);
$rCV = new Math::MatrixReal($numberOfTeams,1);    #column vector is this notation. lots of rows, 1 column
$bCV = new Math::MatrixReal($numberOfTeams,1);

#create zeros for initial wins and losses for every team
for (my $i = 1; $i<$numberOfTeams+1; $i++ ) {
 $teamWins[$i]=0;
 $teamLosses[$i]=0;
 $seasonWins{$team{$i}}=0;
 $seasonLosses{$team{$i}}=0;
}

#Verify data for this week/year exists
unless (-e $ncfScoresFile)
{
print header();
print start_html(-title => " predicted rankings",  -style=>{-src=>'/style.css'});

print "<p>Looks like there is no data yet for $week $year. <br>Please enter another year/week to research. </p>";
exit;
  
 }


#assign the diagonal row of the CM
for (my $i = 1; $i<$#teams+2; $i++ ) {        # dont understand why +2, shuoldnt it be +1
 $cM->assign($i,$i,2);     #start with the diagonal entries at +2
}

#assign the b column vector all 1s  (this, with the diagonals at 2 corresponds to 1/2)
for (my $i = 1; $i<$#teams+2; $i++ ) {        # dont understand why +2, shuoldnt it be +1
 $bCV->assign($i,1,1);  
}








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

#populate the matrix with the data



#populate the matrix with the data
$mov=abs($hTotal - $aTotal);


#$movFactor=((1/80)*$mov)-.0125;                #simple linear MoV
$movFactor=((atan2(.1*$mov-1.7,1))/2.53)+.4001;   #atan MoV
#$movFactor=log($mov)/log(80);                   #log MoV, base 80

#add in elements of the matrix that are not dependent on who won
my $x = $cM->element($teamH{$hTeamName},$teamH{$aTeamName}); 
$cM->assign($teamH{$hTeamName},$teamH{$aTeamName},$x-1+(-$alpha*$movFactor));
$cM->assign($teamH{$aTeamName},$teamH{$hTeamName},$x-1+(-$alpha*$movFactor));  #symmetric matrix, so I don't have to read in this value prior -- assume it is the same as x

my $d1 = $cM->element($teamH{$hTeamName},$teamH{$hTeamName});
$cM->assign($teamH{$hTeamName},$teamH{$hTeamName},$d1+1+($alpha*$movFactor));
my $d2 = $cM->element($teamH{$aTeamName},$teamH{$aTeamName});
$cM->assign($teamH{$aTeamName},$teamH{$aTeamName},$d2+1+($alpha*$movFactor));


my $b1 = $bCV->element($teamH{$hTeamName},1);
my $b2 = $bCV->element($teamH{$aTeamName},1);

#Figure out which team won and give it to the @results array in 1-0 format
 if ($hTotal > $aTotal)  {    #home team won
$results[$k] = "$hTeamName 1-0 $aTeamName";

$seasonWins{$hTeamName} = $seasonWins{$hTeamName} + 1;
$seasonLosses{$aTeamName}++;


$bCV->assign($teamH{$hTeamName},1,$b1+0.5+(-$alpha*$movFactor));
$bCV->assign($teamH{$aTeamName},1,$b2-0.5+($alpha*$movFactor));

}
else {    #away team won. No ties anymore...
$results[$k] = "$aTeamName 1-0 $hTeamName";
$seasonWins{$aTeamName}++;
$seasonLosses{$hTeamName}++;

$bCV->assign($teamH{$aTeamName},1,$b2+0.5+(-$alpha*$movFactor));
$bCV->assign($teamH{$hTeamName},1,$b1-0.5+($alpha*$movFactor));


}
$k++;

}
close NCFSCORESFILE;






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
$rCVofI = $1;    #remember, $rCVofI is coming in formatted as scientifc notation
$rCVofI = sprintf("%.3f", $rCVofI);    #Hate using sprintf here, should use something more modern. use of %.10g will lead to loss of conversion to decimal for numbers right around 0 - specifically 0.0000012345 will still come in scientific notation of 1.23E-6.  %.3f will round to thousands (both pos and negative)

#print "$team{$i} record is $teamWins[$i] and $teamLosses[$i]  - rating is $rCVofI \n";

$output[$i] = "$rCVofI\:\:$team{$i}:$rCVofI: record is $seasonWins{$team{$i}} and $seasonLosses{$team{$i}}\n";  #put the rating in front so I can sort on it
}



my @sortedOutput = sort @output;   # This sorts alphabetically, not numerically.  .8 comes before .7, but -.06 comes before -.03

#TODO FIX SORTING OF NEGATIVE RATING TEAMS
my @sortedOutputPositive;
my @sortedOutputNegative;
#
foreach (@sortedOutput) {
  if ( (substr ($_,0,1)) eq '-' ) {    #just look at that 1st character only
    push (@sortedOutputNegative, $_);
  } else {
    push (@sortedOutputPositive, $_);
  }
}
@sortedOutputNegative = reverse sort @sortedOutputNegative;
#
#@sortedOutput = @sortedOutputNegative;
#push (@sortedOutput, @sortedOutputPositive);


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
push (@agaComputerRanking, $separated[0]) ;        #to pupulate an array with just names of teams in computer rank order - used by eta computation for accuracy to committee

}
shift @agaComputerRanking;   #get the #1 team as element 0 of this array



#print "sortedOutput2 of 0 is $sortedOutput2[0]    \n";
#print "sortedOutput2 of 1 is $sortedOutput2[1]    \n";




my @ratingBias;
#get the right rating biases from a file
if ($weekNumber == "10" || $weekNumber == "11" || $weekNumber == "12" || $weekNumber == "13" || $weekNumber == "14"  || $weekNumber == "15"       ) {
open (AVERAGECOMMITTEEBIASFILE, "<$averageCommitteeBiasFile") or die "$! error trying to open";

for my $line (<AVERAGECOMMITTEEBIASFILE>) {
$line =~ m/(.+?) averageRatingBiasThroughWeek\d\d is (.+)/;
$ratingBias [$teamH{$1}] = $2;

}
close AVERAGECOMMITTEEBIASFILE;
}
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

#get committe rankings now, so I can display eta, if applicable
if ( ($weekNumber >= 9) && ($weekNumber <=14)    ){
open (COMMITTEERANKINGS, "<$committeeRankingsFile") or die "$! error trying to open";     
my $line;
for ($k=1;$k<=25; $k++) {
 $line = <COMMITTEERANKINGS>;
 $line = (split /:/,  $line) [1];
chomp $line;
push (@committeeRanking, $line);
}
close COMMITTEERANKINGS;
}



##########################################
print header();
print start_html(-title => " predicted rankings",  -style=>{-src=>'/style.css'});




##Print the banner and menu from cgi
#my $data;
#my $banner = '/var/www/ppDocs/banner-and-menu.html';
#open (BANNER, "<$banner") or die "$! error trying to open";  
#for $data (<BANNER>) {
#print "$data";
#}
#close BANNER;





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


print "<h2>$year $week rankings</h2>";



print "<table class=\"buckets\" width=\"100%\" border=\"5px\" cellpadding=\"4px\" cellspacing=\"6px\" >";
print "<tr>";

  my @predictedCommitteeRating;
  my @sortedPredictedCommitteeRating;

  
  
  



  
  for ($k=1;$k<$numberOfTeams+1;$k++) {

$predictedCommitteeRating[$k] = $agaRating[$k] + $ratingBias[$k];
$sortedPredictedCommitteeRating[$k] = "$predictedCommitteeRating[$k] $team{$k}";    #aint actually sorted yet
#print "$team{$k}   $predictedCommitteeRating[$k]\n";
}
 @sortedPredictedCommitteeRating = reverse sort @sortedPredictedCommitteeRating;
  
  
my $weekNumberMinusOne = $weekNumber -1;  
  
  
  print "<td valign=\"top\">";
    print "<h4>Predicted committee top 25</h4>";

 
 if ($weekNumber < 10) {
   print "<h5\">";
   print "Predicted Playoff Committee Rankings<br> are available after the conclusion<br> of games on week 10 <br>";
   print "</h5>";
  }
  elsif ($weekNumber > 16) {
   print "<h5\">";
   print "Predicted Playoff Committee Rankings<br> are not applicable after<br> the conculsion of bowls/playoff <br>";
   print "</h5>";
   }
 
  else {
 print "<h5 id=\"top4\">"; 
  for ($k=0;$k<4;$k++) {    
my $m = $k+1;
$sortedPredictedCommitteeRating[$k] =~ m/(.+?) (.+)/;
print "$m. <a href=\"/analyzeSchedule.php?team1=$2&year=$year&week=$weekNumber\" target=\"parent\" >$2</a>  &nbsp   [$1] <br>";
#print "$m. $sortedPredictedCommitteeRating[$k]<br>";
push (@agaPPRanking, $2) ;        #to pupulate an array with just names of teams in predicted rank order - used by eta computation for accuracy to committee
}
print "</h5>";

  for ($k=4;$k<25;$k++) {    
my $m = $k+1;
$sortedPredictedCommitteeRating[$k] =~ m/(.+?) (.+)/;
print "$m. <a href=\"/analyzeSchedule.php?team1=$2&year=$year&week=$weekNumber\" target=\"parent\" >$2</a>   &nbsp  [$1]<br>";
#print "$m. $sortedPredictedCommitteeRating[$k]<br>";
push (@agaPPRanking, $2) ;  
}

  for ($k=25;$k<$numberOfTeams;$k++) {    
my $m = $k+1;
$sortedPredictedCommitteeRating[$k] =~ m/(.+?) (.+)/;
push (@agaPPRanking, $2) ;    #need to get PP all the way to number of teams for that year to compute eta against top 25. Don't need to actually print anything out here
}



     print "<p>Based on games played through week $weekNumber<br>";
      print "and the average committee bias file through week $weekNumberMinusOne</p>";
      print "<p></p>";
      print "<hr>";



#compute Eta for predictions
my $i=1;
my @nonMatchingCfpCommitteeTeamNames;
my $diffPP = 0;
my $a;
my $c;
foreach $a (@committeeRanking) {
$c = (first_index { $_ eq $a } @agaPPRanking) + 1;
if ($c > 0) {
$diffPP = abs(log($i) - log($c)) + $diffPP;
} else {
   push (@nonMatchingCfpCommitteeTeamNames, "$a is a playoff committee name, but not a name that matches ESPNs defined names for $year. eta calculation is affected<br>");
}
$i++;
}
my $etaPP = exp(.04 * $diffPP);     #exp [(1/25) * sum of ln differences]
$etaPP = substr($etaPP,0,4);   #crude truncation

print "&nbsp &nbsp Predictor eta = $etaPP<br>";
print "@nonMatchingCfpCommitteeTeamNames";

}



print "<\/td>";


#   print "<br><br><br><br><br>";

  print "<td>";

print "<h4>Mathematical Raings of teams through week $weekNumber</h4>";
#print '<table>';

for ($k=1;$k<=$numberOfTeams;$k++) {
 
 $sortedOutput2[$k] = "<a href=\"/analyzeSchedule.php?team1=$teamWithAgaRank[$k]&year=$year&week=$weekNumber\" target=\"parent\" >" . $sortedOutput2[$k] ;
$sortedOutput2[$k] =~ s/:/     <\/a> [/;    #pretty things up
 $sortedOutput2[$k] =~ s/: record is /]    (/;
 $sortedOutput2[$k] =~ s/ and /-/;
 $sortedOutput2[$k] = $sortedOutput2[$k] . ")";
 
#print "<tr><td>$k.</td><td> $sortedOutput2[$k]</td></tr><br>";
print "$k. $sortedOutput2[$k]<br>";
#print "$output[$k]<br>";
}
  
#compute and print computer Eta
#problem discovered 9/2022 - The team names the cfp committee uses can be different than the team names espn uses. for example in 2015 ESPN used "Washington State" and committee used "Washington St"  This results in the eta calc taking a log of 0 and then exiting. 
#The fix could be normalizing cfp names to espn names, or something.   For now I'll just ignore and place a warning
my $i=1;
my @nonMatchingCfpCommitteeTeamNames;  #re-initialize 
my $diffComputer = 0;
my $a;
my $b;
if (scalar (@committeeRanking) == 25) {          #compute and print out the computer eta only if a committe ranking exists
foreach $a (@committeeRanking) {
$b = (first_index { $_ eq $a } @agaComputerRanking) + 1;
if ($b > 0) {
$diffComputer = abs(log($i) - log($b)) + $diffComputer;
} else {
  push (@nonMatchingCfpCommitteeTeamNames, "$a is a playoff committee name, but not a name that matches ESPNs defined names for $year. eta calculation is affected<br>");
}
$i++;
}
my $etaComputer = exp(.04 * $diffComputer);     #exp [(1/25) * sum of ln differences]
$etaComputer = substr($etaComputer,0,4);  #crude truncation
print "<hr><br>&nbsp &nbsp Computer eta = $etaComputer<br>";   #here is the eta character, I just cant get perl to print it - η
print "@nonMatchingCfpCommitteeTeamNames";

}
print "<\/td>";



my $weekNumberPlusOne = $weekNumber + 1;    
      print "<td valign=\"top\">";
    print "<h4>Actual committee top 25*</h4>";
 if ($weekNumber < 9) {
   print "<h5\">";
   print "Actual Playoff Committee Rankings<br> are available after the conclusion<br> of games on week 9<br>";
   print "</h5>";
      }
        elsif ($weekNumber > 16) {
   print "<h5\">";
   print "Actual Playoff Committee Rankings<br> are not applicable after<br> the conculsion of bowls/playoff <br>";
   print "</h5>";
   }
      
      
      
      
  else {
open (COMMITTEERANKINGS, "<$committeeRankingsFile") or die "$! error trying to open";     
my $line;
for ($k=1;$k<5; $k++) {
 $line = <COMMITTEERANKINGS>;
 $line = (split /:/,  $line) [1];
chomp $line;
print "$k $line <br>";
}
 print "<hr>";
 
for ($k=5;$k<=25; $k++) {
$line = <COMMITTEERANKINGS>;
 $line = (split /:/,  $line) [1];
chomp $line;
print "$k $line <br>";
}
close COMMITTEERANKINGS;
print "<\/td>";
    }
    
    
    
    
  
print "<\/tr>";
print "<\/table><br>";

print "*Note - CFP committee nomenclature for games through this week is called week $weekNumberPlusOne<br>";

$sortedPredictedCommitteeRating[0] =~ m/(.+?) (.+)/;
my $topFour1 = $2;

$sortedPredictedCommitteeRating[1] =~ m/(.+?) (.+)/;
my $topFour2 = $2;

$sortedPredictedCommitteeRating[2] =~ m/(.+?) (.+)/;
my $topFour3 = $2;

$sortedPredictedCommitteeRating[3] =~ m/(.+?) (.+)/;
my $topFour4 = $2;



print "Tweet this top 4 <a href=\"https://twitter.com/share\" class=\"twitter-share-button\" data-url=\"http://playoffPredictor.com/predict\" data-text=\"My #cfbPlayoff top 4 are $topFour1, $topFour2, $topFour3 and $topFour4 . Find your top 4:\">Tweet this top 4</a><br><br>";
print "<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>";

if ($useMarginOfVictory == 0) {print "Margin of victory <b>has not</b> been used in this calculation (year 2017 or previous)<br>";}
else {print "Margin of victory <b>has</b> been used in this calculation (year is 2018 or later)<br>";}



#print "@agaPPRanking <br><br>";
#print "@agaComputerRanking <br><br>";
#print "@committeeRanking <br><br>";

#for (my $i=0; $i<26; $i++) {
#print "---$committeeRanking[$i]---<br>";
#print "---$agaComputerRanking[$i]---<br>";
#print "---$agaPPRanking[$i]---<br><br>";
#}


print end_html();

  
  