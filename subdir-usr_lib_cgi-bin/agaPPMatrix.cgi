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
use CGI ':standard';
use Math::MatrixReal;
#use Number::Format;
use Data::Dumper;

# set $seasonYear automatically based on current date. Can clobber if needed (below)
my $seasonYear = 1900 + (localtime)[5];
if (localtime[4] lt 3) {$seasonYear = $seasonYear -1;}  #If in Jan or Feb, use last year for football season year
#$seasonYear = "2000";


my $fbsTeams = "/home/neville/cfbPlayoffPredictor/data/$seasonYear/fbsTeamNames.txt";

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







my $ncfScoresFile = "/home/neville/cfbPlayoffPredictor/data/current/ncfScoresFile.txt";
my $ncfScheduleFile = "/home/neville/cfbPlayoffPredictor/data/current/ncfScheduleFile.txt";
my $committeeCurrentRankingsFile = "/home/neville/cfbPlayoffPredictor/data/current/CurrentPlayoffCommitteeRankings.txt";
my $averagedCommitteeBiasFile = "/home/neville/cfbPlayoffPredictor/data/current/CurrentAveragedCommitteeBiasFile.txt";  
my $agaMatrixFile = "/home/neville/cfbPlayoffPredictor/data/current/CurrentAgaMatrix.txt";
my $lastWeekNcfScoresFile = "/home/neville/cfbPlayoffPredictor/data/current/ncfScoresFile.txt";

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


my $mov;
my $movFactor;
my $alpha = 0;     #If alpha=0 this should simplify to original colley matrix.
my $useMarginOfVictory = CGI->new()->param('useMarginOfVictory');
if ($useMarginOfVictory eq 'yes') {
   $useMarginOfVictory = 1;
   $alpha = -0.1;   #this is the weighting of MoV relative to W/L. At this point chosen as -0.1 based on 2021 game research data. Should weight in future based on math.  Expressed as a negative number. Cij = Cii = alpha.   Bi = -alpha.  
}
my %seasonWins;
my %seasonLosses;




my @agaRating;
my $rematch =0;

my @sortedOutput1;
my @sortedOutput2;
my @teamWithAgaRank;

my $u;
my $v;


my $fbsIcons = "/home/neville/cfbPlayoffPredictor/data/teamIcons.txt";
#Read in team to icon mapping
my %teamIcon; #declare it here and then fill in the College name -> icon name mapping below.
open (FBSICONS, "<", $fbsIcons) or die "Can't open the file $fbsIcons";
while (my $line = <FBSICONS> )   {
    chomp ($line);
   unless (substr($line,0,1) eq '#') {  #Do nothing. Ignore comment lines in input file
    my ($a, $b) = split(" => ", $line);   
    $teamIcon{$a} = $b;
   }
}     #at the conclusion of this block $teamIcon{"Wyoming"} will be "Wyo" 




#create blank matricies
$cM = new Math::MatrixReal($numberOfTeams,$numberOfTeams);
$rCV = new Math::MatrixReal($numberOfTeams,1);    #column vector is this notation. lots of rows, 1 column
$bCV = new Math::MatrixReal($numberOfTeams,1);

#print "colley matrix is  $cM\n\n";
#print "r is $rCV\n\n";
#print "b is $bCV\n\n";

#create zeros for initial wins and losses for every team
for (my $i = 1; $i<$numberOfTeams+1; $i++ ) {
 $teamWins[$i]=0;
 $teamLosses[$i]=0;
 $seasonWins{$team{$i}}=0;
 $seasonLosses{$team{$i}}=0;
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

my $ncfScoresFileModTime = (stat($ncfScoresFile))[9];     #get last date/time ncfScoreFile updated
$ncfScoresFileModTime = localtime($ncfScoresFileModTime);  #human readable

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


if (  ($hTeamName ~~ [values %team])    )  {    #Rarely happens -- an FBS team plays a game on the road against a FCS team
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

print header();    # put this in now so debugging can be commented in if needed.  No HTML is actually outputted until the bottom of the file.
my $q = CGI -> new;

my $r;
my $i;
my $s;
my @userInputtedGames;



for (my $t=0; $t<1000; $t++) {      #1,000 inputted games is enough for a whole season (~800 games)
$s = "game"."$t"."result";
$r = $q->param("$s");

#have to remove non-breakable whitespce -- as the cgi enters this crap into the strings, i think
$r =~ s/\x0A//g;
$r =~ s/\x0D//g;




if ($r ne '') {

#need to put some logic in here that is a user inputted team is not on the 1A list clobber that name to "1AA"
($u,$v) = (split / 1-0 /, $r);

# Determine if a team is a 1AA team and assign them to the team "1AA"
if (  ($u ~~ [values %team])    )  {
#that's great, away team is FBS. Do Nothing
}
else {   #otherwise it was a 1AA team
$u = "1AA";
}


if (  ($v ~~ [values %team])    )  {    #Like it would ever happen -- a 1A team plays a game on the road against a 1AA team
#that's great, home team is FBS. Do Nothing
}
else {   #otherwise it was a 1AA team
$v = "1AA";
}

$r = "$u 1-0 $v";


#this needs to be a subroutine instead of going through the lines twice. todo
#this adds the hypothetical results into the teams record for the printout
$seasonWins{$u}++;
$seasonLosses{$v}++;


$results[$k] = "$r";




$mov=1;    # for now predictions are based off 1-0 score, in future you could input score as well. To do, maybe if I want to do it later.   #the movFactor should be such that a 1 point victory equates to a 0 $movFactor
#$movFactor=((1/80)*$mov)-.0125;                #simple linear MoV
$movFactor=((atan2(.1*$mov-1.7,1))/2.53)+.4001;   #atan MoV
#$movFactor=log($mov)/log(80);                   #log MoV, base 80



#add in elements of the matrix that are not dependent on who won
my $x = $cM->element($teamH{$u},$teamH{$v}); 
$cM->assign($teamH{$u},$teamH{$v},$x-1+(-$alpha*$movFactor));
$cM->assign($teamH{$v},$teamH{$u},$x-1+(-$alpha*$movFactor));  #symmetric matrix, so I don't have to read in this value prior -- assume it is the same as x

my $d1 = $cM->element($teamH{$u},$teamH{$u});
$cM->assign($teamH{$u},$teamH{$u},$d1+1+($alpha*$movFactor));
my $d2 = $cM->element($teamH{$v},$teamH{$v});
$cM->assign($teamH{$v},$teamH{$v},$d2+1+($alpha*$movFactor));


my $b1 = $bCV->element($teamH{$u},1);
my $b2 = $bCV->element($teamH{$v},1);

#I think the HTML from the previous form always does 1-0 format. (never 0-1 format)
#home team won
$bCV->assign($teamH{$u},1,$b1+0.5+(-$alpha*$movFactor));
$bCV->assign($teamH{$v},1,$b2-0.5+($alpha*$movFactor));


$k++;



$r =~ s/1-0/<\/b><\/font>beats<font color=\"red\"><b>/;
push @userInputtedGames, "<font color=\"green\"><b>$r<\/b><\/font><br>";

}
}

#for now hard code this
#$results[$k] = "Kansas State 1-0 Iowa State";
#$results[$k+1] = "Oklahoma 1-0 Baylor";
#$results[$k+2] = "Oklahoma State 1-0 Texas Tech";

#populate the matrix with the data from the user inputted games


#INSERT HERE





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
$rCVofI = sprintf("%.3f", $rCVofI);

#$rCVofI = substr $rCVofI, 0, 5;     # truncate out anything under thousandnths. for positive numbers. hundreds for negative. fix. todo. note, this casues a loss of precision. display only.
#if ($rCVofI eq 0.5)  {     #get 0.5 entries in 0.500 format for later sorting  
# $rCVofI = "0.500";
#}
#print "$team{$i} record is $teamWins[$i] and $teamLosses[$i]  - rating is $rCVofI \n";
$output[$i] = "$rCVofI\:\:$team{$i}:$rCVofI: record is $seasonWins{$team{$i}} and $seasonLosses{$team{$i}}\n";  #put the rating in front so I can sort on it
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
open (COMMITTEEBIASFILE, "<$averagedCommitteeBiasFile") or die "$! error trying to open";

for my $line (<COMMITTEEBIASFILE>) {
$line =~ m/(.+?) averageRatingBiasThroughWeek\d+ is (.+)/;
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


print "<table class=\"buckets\" width=\"80%\" border=\"5px\" cellpadding=\"4px\" cellspacing=\"6px\" >";
print "<tr>";

  my @predictedCommitteeRating;
  my @sortedPredictedCommitteeRating;

  
  
  



  
  for ($k=1;$k<$numberOfTeams+1;$k++) {

$predictedCommitteeRating[$k] = $agaRating[$k] + $ratingBias[$k];
$sortedPredictedCommitteeRating[$k] = "$predictedCommitteeRating[$k] $team{$k}";    #aint actually sorted yet
#print "$team{$k}   $predictedCommitteeRating[$k]\n";
}
  
  
  
  
  
  
  print "<td valign=\"top\">";
    print "<h4>Predicted committee top 25</h4>";
  @sortedPredictedCommitteeRating = reverse sort @sortedPredictedCommitteeRating;


# only Print out predicted top 25 column piece on Wednesday after the 1st committee ratings are out - should be about OCt 28-Nov 1 ish
my ($sec,$min,$hour,$mday,$mon,$year,$wday,$daysSinceJan1,$isdst) = localtime();  #$daysSinceJan1 has the number of days since Jan 1 of that year. 
						

if(($daysSinceJan1 >= 296) && ($daysSinceJan1 < 348)) {     #will need to compute appropriate if / then each year

 print "<h5 id=\"top4\">"; 
  for ($k=0;$k<4;$k++) {    
my $m = $k+1;
$sortedPredictedCommitteeRating[$k] =~ m/(.+?) (.+)/;
print "$m. <img src=\"http://sports.cbsimg.net/images/collegefootball/logos/50x50/$teamIcon{$2}.png\">  <a href=\"/analyzeSchedule.php?team1=$2\">$2</a>    ($1) <br>";
}
print "</h5>";

  for ($k=4;$k<25;$k++) {    
my $m = $k+1;
$sortedPredictedCommitteeRating[$k] =~ m/(.+?) (.+)/;
print "$m. <a href=\"/analyzeSchedule.php?team1=$2\">$2</a>    ($1)<br>";
}


} else {
#otherwise state this and let them see mathematical ratings only
  print "<h5\">";
  print "Predicted Playoff Committee Rankings<br> are available after the first CFP<br> committee rankings are released <br>";
  print "(1st Tuesday in November)<br>";
  print "</h5>";
}

print "<\/td>";


   print "<br><br><br><br><br>";
   
   
   
  
   
#Print out the mathematical ratings    
  print "<td>";

print "<h4>Mathematical Raings of teams with hypothetical results included</h4>";
# output top4 differently if Predicted top 25 is not yet available
if(($daysSinceJan1 >= 296) && ($daysSinceJan1 < 338)) {       #will need to compute appropriate if / then each year. I would like to automate this from year to year. 
  #don't highlight the top 4 here, just print it all out
for ($k=1;$k<$numberOfTeams+1;$k++) {
 
 $sortedOutput2[$k] = "<a href=\"/analyzeSchedule.php?team1=$teamWithAgaRank[$k]\">" . $sortedOutput2[$k] ;
$sortedOutput2[$k] =~ s/:/     <\/a> [/;    #pretty things up
 $sortedOutput2[$k] =~ s/: record is /]    (/;
 $sortedOutput2[$k] =~ s/ and /-/;
 $sortedOutput2[$k] = $sortedOutput2[$k] . ")";
 
print "$k. $sortedOutput2[$k]<br>";
 }

 
 
} else {
#do highlight the top 4 here
print "<h5 id=\"top4\">"; 
for ($k=0;$k<4;$k++) {    
my $m = $k+1;
 $sortedOutput2[$m] = "<a href=\"/analyzeSchedule.php?team1=$teamWithAgaRank[$m]\">" . $sortedOutput2[$m] ;
 $sortedOutput2[$m] =~ s/:/     <\/a> [/;    #pretty things up
 $sortedOutput2[$m] =~ s/: record is /]    (/;
 $sortedOutput2[$m] =~ s/ and /-/;
 $sortedOutput2[$m] = $sortedOutput2[$m] . ")";
 
print "$m. <img src=\"http://sports.cbsimg.net/images/collegefootball/logos/50x50/$teamIcon{$teamWithAgaRank[$m]}.png\"> $sortedOutput2[$m]<br>";


}
print "</h5>";

  for ($k=5;$k<$numberOfTeams+1;$k++) {    
 $sortedOutput2[$k] = "<a href=\"/analyzeSchedule.php?team1=$teamWithAgaRank[$k]\">" . $sortedOutput2[$k] ;
 $sortedOutput2[$k] =~ s/:/     <\/a> [/;    #pretty things up
 $sortedOutput2[$k] =~ s/: record is /]    (/;
 $sortedOutput2[$k] =~ s/ and /-/;
 $sortedOutput2[$k] = $sortedOutput2[$k] . ")";
 
print "$k. $sortedOutput2[$k]<br>";


}
 
 









}
 
 



# Don't need this in here, have it for historical. Looks better here with 2 columns.  
#print '</table>';  

#   print "<h4>Current committee top 25</h4>";
# open (COMMITTEECURRENTRANKINGS, "<$committeeCurrentRankingsFile") or die "$! error trying to open";     
#for $k (<COMMITTEECURRENTRANKINGS>) {
#print "$k<br>";
#}


print "<\/td>";
 
    
  
print "<\/tr>";
print "<\/table><br>";



$sortedPredictedCommitteeRating[0] =~ m/(.+?) (.+)/;
my $topFour1 = $2;

$sortedPredictedCommitteeRating[1] =~ m/(.+?) (.+)/;
my $topFour2 = $2;

$sortedPredictedCommitteeRating[2] =~ m/(.+?) (.+)/;
my $topFour3 = $2;

$sortedPredictedCommitteeRating[3] =~ m/(.+?) (.+)/;
my $topFour4 = $2;



print "Tweet this top 4 <a href=\"https://twitter.com/share\" class=\"twitter-share-button\" data-url=\"http://football.playoffPredictor.com/predictNextWeek-top15.php\" data-text=\"My #cfbPlayoff top 4 are $topFour1, $topFour2, $topFour3 and $topFour4 . Find your top 4:\">Tweet this top 4</a><br><br>";
print "<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>";

if ($useMarginOfVictory == 1) {print "Margin of victory <b>has</b> been used in this calculation<br>";}
else {print "Margin of victory <b>has not</b> been used in this calculation<br>";}

print end_html();

  
  
