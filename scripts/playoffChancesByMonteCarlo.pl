#!/usr/bin/perl
##############
#
# playoffChancesByMonteCarlo.pl
# Determine probability for top 4 by running 1000 simulations with elo win percentages
#
##############
#
#



use strict;
use warnings;   #comment out -- too much in error log
use CGI ':standard';
use Math::MatrixReal;
#use Number::Format;
#use Sort::Naturally;
use Data::Dumper;
use List::MoreUtils qw(first_index);



#ensure call with 1 argument - # of itierations
my $itierations;
if ($#ARGV == 0) {  
$itierations = $ARGV[0];
}

#called with wrong aruments, die
else {
print "Usgae: playoffChancesByMonteCarlo.pl  [num_itierations]  \n";
print "example- playoffChancesByMonteCarlo.pl  1000    \n";
print "only itierations = 1000 output results to /data/current/currentPlayoffPercentages.txt file    \n";
die; 
}



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
my @significantUpsets;     #teams with rating over .70 which got upset?
my @week14Results;    #to capture week 14 contestants and results

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

my %isInTop4;
my %isTheTop4;
my $thisTop4Result;


# set $year automatically based on current date. Can clobber if needed (below)
my $year = 1900 + (localtime)[5];
if ( ((localtime)[4] == 0) || ((localtime)[4] == 1) )  {$year = $year -1;}  #If in Jan or Feb, use last year for football season year
$year = 2023;


if ($year ge 2018) {
 $useMarginOfVictory = 1;   #default is to use it from 2018 season onwards. Does not need to be called dynamically from previous page, just needs to be selectable here in backend code is enough
 $alpha = -0.1;   #this is the weighting of MoV relative to W/L. At this point chosen as -0.1 based on 2021 game research data. Should weight in future based on math.  Expressed as a negative number. Cij = Cii = alpha.   Bi = -alpha.  
 #HATE TO DO it like the above, but -0.1 gets weird rating for 1AA for 2021 week 17. Have to investigate
}



my $ncfScoresFile = "/home/neville/cfbPlayoffPredictor/data/current/ncfScoresFile.txt";  
my $ncfScheduleFile = "/home/neville/cfbPlayoffPredictor/data/current/ncfScheduleFile.txt";  
my $averageCommitteeBiasFile = '/home/neville/cfbPlayoffPredictor/data/current/CurrentAveragedCommitteeBiasFile.txt';
my $currentCalculatedRatingsFile = '/home/neville/cfbPlayoffPredictor/data/current/CurrentCalculatedRatings.txt';

my $fbsTeams = "/home/neville/cfbPlayoffPredictor/data/"."$year"."/fbsTeamNames.txt";

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




for (my $l=1; $l<=$itierations; $l++) {

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



#assign the diagonal row of the CM
for (my $i = 1; $i<$#teams+2; $i++ ) {        # dont understand why +2, shuoldnt it be +1
 $cM->assign($i,$i,2);     #start with the diagonal entries at +2
}

#assign the b column vector all 1s  (this, with the diagonals at 2 corresponds to 1/2)
for (my $i = 1; $i<$#teams+2; $i++ ) {        # dont understand why +2, shuoldnt it be +1
 $bCV->assign($i,1,1);  
}



#read input data for finished scores from a file.
my $scoreInput;
my $k = 0;      #there is probably a better way to do this
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


#read input data for teams current bias data
my @ratingBias;
open (AVERAGECOMMITTEEBIASFILE, "<$averageCommitteeBiasFile") or die "$! error trying to open";
for my $line (<AVERAGECOMMITTEEBIASFILE>) {
$line =~ m/(.+?) averageRatingBiasThroughWeek\d+ is (.+)/;
$ratingBias [$teamH{$1}] = $2;
}
#$ratingBias [$teamH{"1AA"}] = 0;     # need this so addition here is defined later
close AVERAGECOMMITTEEBIASFILE;

#print "georgia ratingBias is $ratingBias[$teamH{'Georgia'}]\n";

my %computerRating;
#read input data for teams current computer ratings
open (CURRENTCALCULATEDRATINGSFILE, "<$currentCalculatedRatingsFile") or die "$! error trying to open";
for my $data (<CURRENTCALCULATEDRATINGSFILE>) {
my ($teamName, $teamRating, $record) = (split /:/, $data); 
$computerRating{"$teamName"} = $teamRating + $ratingBias[$teamH{"$teamName"}];      #makes $computerRating{Alabama} = 0.900 + .050 = 0.950
}
#print "georgia computerRating is $computerRating{'Georgia'}\n";





my $scheduleInput;
#read input data for future schedule from a file.
open (NCFSCHEDULEFILE, "<$ncfScheduleFile") or die "$! error trying to open";     
for $scheduleInput (<NCFSCHEDULEFILE>) {
my ($week, $contestants) = (split /:/, $scheduleInput);
my ($aTeamName, $hTeamName) = (split / - /, $contestants);
chomp $hTeamName;

# Determine if a team is a 1AA team and assign them to the team "1AA"
unless (  ($aTeamName ~~ [values %team])    )  {$aTeamName = "1AA"; }
unless (  ($hTeamName ~~ [values %team])    )  {$hTeamName = "1AA"; }  

$movFactor = 0;  #for future wins and losses we assume no MoV - that is final scores are 14-0

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
my $divisor = 1;  #base = 1000,  divisor =1

my $homeFieldAdvantageNumber;
if ($week ne "week 14"){$homeFieldAdvantageNumber = 0.05;} else {$homeFieldAdvantageNumber = 0;}

my $probA = 1/(1+(1000**(($computerRating{"$aTeamName"}-(($computerRating{"$hTeamName"})+$homeFieldAdvantageNumber))/$divisor)));
my $probB = 1/(1+(1000**((($computerRating{"$hTeamName"}+$homeFieldAdvantageNumber)-$computerRating{"$aTeamName"})/$divisor)));


my $rand = rand();
if ($rand <= $probA)  {    #home team won
$results[$k] = "$hTeamName 1-0 $aTeamName";
$seasonWins{$hTeamName}++;
$seasonLosses{$aTeamName}++;
$bCV->assign($teamH{$hTeamName},1,$b1+0.5+(-$alpha*$movFactor));
$bCV->assign($teamH{$aTeamName},1,$b2-0.5+($alpha*$movFactor));
#print "$hTeamName beats $aTeamName in $week\n";
#if this is a significant upset capture the result
if ( ($computerRating{"$aTeamName"} >= 0.8) &&    ($computerRating{"$aTeamName"} > $computerRating{"$hTeamName"})  )  {
push @significantUpsets, "$hTeamName beats $aTeamName in $week";

}
}
else {    #away team won. No ties.
$results[$k] = "$aTeamName 1-0 $hTeamName";
$seasonWins{$aTeamName}++;
$seasonLosses{$hTeamName}++;
$bCV->assign($teamH{$aTeamName},1,$b2+0.5+(-$alpha*$movFactor));
$bCV->assign($teamH{$hTeamName},1,$b1-0.5+($alpha*$movFactor));
#print "$aTeamName beats $hTeamName in $week\n";
#if this is a significant upset capture the result
if ( ($computerRating{"$hTeamName"} >= 0.8) &&    ($computerRating{"$hTeamName"} > $computerRating{"$aTeamName"})  )  {
push @significantUpsets, "$aTeamName beats $hTeamName in $week";

}
}





$k++;
}
close NCFSCHEDULEFILE;








#solve for rCV
my ($dim, $base, $LRM);  # $LRM is the LR_Matrix defined in MatrixReal and returned by the method decompose_LR
$LRM = $cM->decompose_LR();
if ( ($dim,$rCV,$base) =  $LRM->solve_LR($bCV) ) {
#print "great, it solved the matrix\n"; #Note, I don't actually have to iterate. The Matrix solution takes care of that for me !
#print "r is \n"; #print "$rCV\n\n";
}
else {print "crap, there was no solution to the Colley Matrix  (This should not have happened\n)"; die;}




#print out the team ratings
#1st split the ratings out into an array with an index for each team

my @output;

for (my $i = 1; $i<$#teams+2; $i++ ) {        # dont understand why +2, shuoldnt it be +1
 
#get the team rating in human readable form
my $rCVofI = $rCV->row($i);
$rCVofI =~ /\[(.+?)\]/;
$rCVofI = $1;    #remember, $rCVofI is coming in formatted as scientifc notation
$rCVofI = sprintf("%.3f", $rCVofI);    #Hate using sprintf here, should use something more modern. use of %.10g will lead to loss of conversion to decimal for numbers right around 0 - specifically 0.0000012345 will still come in scientific notation of 1.23E-6.  %.3f will round to thousands (both pos and negative)
#print "$team{$i}  - rating is $rCVofI \n";

$output[$i] = "$rCVofI\:\:$team{$i}:$rCVofI: record is $seasonWins{$team{$i}} and $seasonLosses{$team{$i}}\n";  #put the rating in front so I can sort on it
}



# there is a bug in perl. The below puts a warning for "Use of uninitialized value in sort at..." to remove it have to upgrade to perl 5.32+. Will do that after season is over, or after good backups exist
my @sortedOutput = reverse sort @output;   # This sorts alphabetically, not numerically.  .8 comes before .7, but -.06 comes before -.03

#print "@sortedOutput\n";   # gives this weird 60 before it prints the array




#get new ratings into array
foreach (@sortedOutput) {
my ($teamRating,$blank,$team,$record) = (split /:/, $_);
$agaRating[$teamH{$team}] = $teamRating;
}


#print "Top 4 computer this iteration at week 13 end:\n";
#print "$sortedOutput[0] : $sortedOutput[1] : $sortedOutput[2] : $sortedOutput[3]";

















#Now, have to set up week 14 matches
#will have to set up logic. All TODO:
#  is week 14 already in ncfScheduleFile?
#  division head-head winner
#  three way tie
# Hard code the SEC divsions in here. Look at expanding 20xxTeamNames with logic for conference and division
my @secEastTeam = ("Georgia", "South Carolina", "Kentucky", "Missouri", "Florida", "Tennessee", "Vanderbilt");
my @secWestTeam = ("Auburn", "Alabama", "Ole Miss", "Mississippi State", "LSU", "Arkansas", "Texas A&M");
my ($secEastWinner, $secEastWinnerRating);
for (my $i=0; $i<=6; $i++) {
if ( $agaRating[$teamH{$secEastTeam[$i]}] > $secEastWinnerRating ) {
    $secEastWinner = $secEastTeam[$i];
    $secEastWinnerRating = $agaRating[$teamH{$secEastTeam[$i]}];
}
#print "sec East team $secEastTeam[$i] rating is $agaRating[$teamH{$secEastTeam[$i]}]\n";
}
my ($secWestWinner, $secWestWinnerRating);
for (my $i=0; $i<=6; $i++) {
if ( $agaRating[$teamH{$secWestTeam[$i]}] > $secWestWinnerRating ) {
    $secWestWinner = $secWestTeam[$i];
    $secWestWinnerRating = $agaRating[$teamH{$secWestTeam[$i]}];
}
}
#$secWestWinner = "LSU";   #If you need to clobber
#$secWestWinnerRating = $agaRating[$teamH{"LSU"}];
#print "sec East winner was $secEastWinner with a rating of $agaRating[$teamH{$secEastWinner}]\n";
#print "sec West winner was $secWestWinner with a rating of $agaRating[$teamH{$secWestWinner}]\n";


#BigTen
my @bigTenWestTeam = ("Nebraska", "Minnesota", "Wisconsin", "Northwestern", "Iowa", "Illinois", "Purdue");
my @bigTenEastTeam = ("Michigan", "Michigan State", "Indiana", "Ohio State", "Penn State", "Rutgers", "Maryland");
my ($bigTenEastWinner, $bigTenEastWinnerRating);
for (my $i=0; $i<=6; $i++) {
if ( $agaRating[$teamH{$bigTenEastTeam[$i]}] > $bigTenEastWinnerRating ) {
    $bigTenEastWinner = $bigTenEastTeam[$i];
    $bigTenEastWinnerRating = $agaRating[$teamH{$bigTenEastTeam[$i]}];
}
}
my ($bigTenWestWinner, $bigTenWestWinnerRating);
for (my $i=0; $i<=6; $i++) {
if ( $agaRating[$teamH{$bigTenWestTeam[$i]}] > $bigTenWestWinnerRating ) {
    $bigTenWestWinner = $bigTenWestTeam[$i];
    $bigTenWestWinnerRating = $agaRating[$teamH{$bigTenWestTeam[$i]}];
}
}


#Pac12
#my @pac12NorthTeam = ("Washington", "Washington State", "Oregon", "Oregon State", "Colorado", "Utah");
#my @pac12SouthTeam = ("California", "Stanford", "USC", "UCLA", "Arizona State", "Arizona");
#my ($pac12SouthWinner, $pac12SouthWinnerRating);
#for (my $i=0; $i<=5; $i++) {
#if ( $agaRating[$teamH{$pac12SouthTeam[$i]}] > $pac12SouthWinnerRating ) {
#    $pac12SouthWinner = $pac12SouthTeam[$i];
#    $pac12SouthWinnerRating = $agaRating[$teamH{$pac12SouthTeam[$i]}];
#}
#}
#my ($pac12NorthWinner, $pac12NorthWinnerRating);
#for (my $i=0; $i<=5; $i++) {
#if ( $agaRating[$teamH{$pac12NorthTeam[$i]}] > $pac12NorthWinnerRating ) {
#    $pac12NorthWinner = $pac12NorthTeam[$i];
#    $pac12NorthWinnerRating = $agaRating[$teamH{$pac12NorthTeam[$i]}];
#}
#}
#print "pac12 N winner was $pac12NorthWinner with a rating of $agaRating[$teamH{$pac12NorthWinner}]\n";
#print "pac12 S winner was $pac12SouthWinner with a rating of $agaRating[$teamH{$pac12SouthWinner}]\n";
my @pac12Team = ("Washington", "Washington State", "Oregon", "Oregon State", "Colorado", "Utah", "California", "Stanford", "USC", "UCLA", "Arizona State", "Arizona");
my ($pac12No1, $pac12No2, $pac12No1Rating, $pac12No2Rating);
for (my $i=0; $i<=11; $i++) {
if ( $agaRating[$teamH{$pac12Team[$i]}] > $pac12No1Rating ) {
    $pac12No1 = $pac12Team[$i];
    $pac12No1Rating = $agaRating[$teamH{$pac12Team[$i]}];
}
}
for (my $i=0; $i<=11; $i++) {
if (( $agaRating[$teamH{$pac12Team[$i]}] > $pac12No2Rating )  &&   ($pac12Team[$i] ne $pac12No1))    {
    $pac12No2 = $pac12Team[$i];
    $pac12No2Rating = $agaRating[$teamH{$pac12Team[$i]}];
}
}
#print "pac12 #1 was $pac12No1 with a rating of $agaRating[$teamH{$pac12No1}]\n";
#print "pac12 #2 was $pac12No2 with a rating of $agaRating[$teamH{$pac12No2}]\n";



#ACC
my @accAtlanticTeam = ("Clemson", "Florida State", "Syracuse", "NC State", "Wake Forest", "Boston College", "Louisville");
my @accCoastalTeam = ("Virginia Tech", "Duke", "North Carolina", "Miami", "Pittsburgh", "Virginia", "Georgia Tech");
my ($accCoastalWinner, $accCoastalWinnerRating);
for (my $i=0; $i<=6; $i++) {
if ( $agaRating[$teamH{$accCoastalTeam[$i]}] > $accCoastalWinnerRating ) {
    $accCoastalWinner = $accCoastalTeam[$i];
    $accCoastalWinnerRating = $agaRating[$teamH{$accCoastalTeam[$i]}];
}
}
my ($accAtlanticWinner, $accAtlanticWinnerRating);
for (my $i=0; $i<=6; $i++) {
if ( $agaRating[$teamH{$accAtlanticTeam[$i]}] > $accAtlanticWinnerRating ) {
    $accAtlanticWinner = $accAtlanticTeam[$i];
    $accAtlanticWinnerRating = $agaRating[$teamH{$accAtlanticTeam[$i]}];
}
}
#print "ACC C winner was $accCoastalWinner with a rating of $agaRating[$teamH{$accCoastalWinner}]\n";
#print "ACC A winner was $accAtlanticWinner with a rating of $agaRating[$teamH{$accAtlanticWinner}]\n";



#Big12
my @big12Team = ("Iowa State", "Kansas", "Kansas State", "West Virginia", "Oklahoma", "Oklahoma State", "TCU", "Baylor", "Texas Tech", "Texas");
my ($big12No1, $big12No2, $big12No1Rating, $big12No2Rating);
for (my $i=0; $i<=9; $i++) {
if ( $agaRating[$teamH{$big12Team[$i]}] > $big12No1Rating ) {
    $big12No1 = $big12Team[$i];
    $big12No1Rating = $agaRating[$teamH{$big12Team[$i]}];
}
}
for (my $i=0; $i<=9; $i++) {
if (( $agaRating[$teamH{$big12Team[$i]}] > $big12No2Rating )  &&   ($big12Team[$i] ne $big12No1))    {
    $big12No2 = $big12Team[$i];
    $big12No2Rating = $agaRating[$teamH{$big12Team[$i]}];
}
}
#print "big12 #1 was $big12No1 with a rating of $agaRating[$teamH{$big12No1}]\n";
#print "big12 #2 was $big12No2 with a rating of $agaRating[$teamH{$big12No2}]\n";






#CUSA and others TODO








#these stay the same for all week 14 games for now
my $divisor = 1;  #base = 1000,  divisor =1
my $homeFieldAdvantageNumber =0; #this is week 14

#set up and decide SEC championship matchup
my ($aTeamName, $hTeamName) = ($secEastWinner, $secWestWinner);
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
my $probA = 1/(1+(1000**(($computerRating{"$aTeamName"}-(($computerRating{"$hTeamName"})+$homeFieldAdvantageNumber))/$divisor)));
my $probB = 1/(1+(1000**((($computerRating{"$hTeamName"}+$homeFieldAdvantageNumber)-$computerRating{"$aTeamName"})/$divisor)));
my $rand = rand();
if ($rand <= $probA)  {    #home team won
$results[$k] = "$hTeamName 1-0 $aTeamName";
$seasonWins{$hTeamName}++;
$seasonLosses{$aTeamName}++;
$bCV->assign($teamH{$hTeamName},1,$b1+0.5+(-$alpha*$movFactor));
$bCV->assign($teamH{$aTeamName},1,$b2-0.5+($alpha*$movFactor));
push @week14Results, "$hTeamName beats $aTeamName (SEC Championship)";
}
else {    #away team won. No ties.
$results[$k] = "$aTeamName 1-0 $hTeamName";
$seasonWins{$aTeamName}++;
$seasonLosses{$hTeamName}++;
$bCV->assign($teamH{$aTeamName},1,$b2+0.5+(-$alpha*$movFactor));
$bCV->assign($teamH{$hTeamName},1,$b1-0.5+($alpha*$movFactor));
push @week14Results, "$aTeamName beats $hTeamName (SEC Championship)";
}
$k++;

#decide BigTen Champion
($aTeamName, $hTeamName) = ($bigTenEastWinner, $bigTenWestWinner);
#add in elements of the matrix that are not dependent on who won
$x = $cM->element($teamH{$hTeamName},$teamH{$aTeamName}); 
$cM->assign($teamH{$hTeamName},$teamH{$aTeamName},$x-1+(-$alpha*$movFactor));
$cM->assign($teamH{$aTeamName},$teamH{$hTeamName},$x-1+(-$alpha*$movFactor));  #symmetric matrix, so I don't have to read in this value prior -- assume it is the same as x
$d1 = $cM->element($teamH{$hTeamName},$teamH{$hTeamName});
$cM->assign($teamH{$hTeamName},$teamH{$hTeamName},$d1+1+($alpha*$movFactor));
$d2 = $cM->element($teamH{$aTeamName},$teamH{$aTeamName});
$cM->assign($teamH{$aTeamName},$teamH{$aTeamName},$d2+1+($alpha*$movFactor));
$b1 = $bCV->element($teamH{$hTeamName},1);
$b2 = $bCV->element($teamH{$aTeamName},1);
$probA = 1/(1+(1000**(($computerRating{"$aTeamName"}-(($computerRating{"$hTeamName"})+$homeFieldAdvantageNumber))/$divisor)));
$probB = 1/(1+(1000**((($computerRating{"$hTeamName"}+$homeFieldAdvantageNumber)-$computerRating{"$aTeamName"})/$divisor)));
$rand = rand();
if ($rand <= $probA)  {    #home team won
$results[$k] = "$hTeamName 1-0 $aTeamName";
$seasonWins{$hTeamName}++;
$seasonLosses{$aTeamName}++;
$bCV->assign($teamH{$hTeamName},1,$b1+0.5+(-$alpha*$movFactor));
$bCV->assign($teamH{$aTeamName},1,$b2-0.5+($alpha*$movFactor));
push @week14Results, "$hTeamName beats $aTeamName (B10 Championship)";
}
else {    #away team won. No ties.
$results[$k] = "$aTeamName 1-0 $hTeamName";
$seasonWins{$aTeamName}++;
$seasonLosses{$hTeamName}++;
$bCV->assign($teamH{$aTeamName},1,$b2+0.5+(-$alpha*$movFactor));
$bCV->assign($teamH{$hTeamName},1,$b1-0.5+($alpha*$movFactor));
push @week14Results, "$aTeamName beats $hTeamName (B10 Championship)";
}
$k++;


#decide Pac12 Champion
($aTeamName, $hTeamName) = ($pac12No1, $pac12No2);
#add in elements of the matrix that are not dependent on who won
$x = $cM->element($teamH{$hTeamName},$teamH{$aTeamName}); 
$cM->assign($teamH{$hTeamName},$teamH{$aTeamName},$x-1+(-$alpha*$movFactor));
$cM->assign($teamH{$aTeamName},$teamH{$hTeamName},$x-1+(-$alpha*$movFactor));  #symmetric matrix, so I don't have to read in this value prior -- assume it is the same as x
$d1 = $cM->element($teamH{$hTeamName},$teamH{$hTeamName});
$cM->assign($teamH{$hTeamName},$teamH{$hTeamName},$d1+1+($alpha*$movFactor));
$d2 = $cM->element($teamH{$aTeamName},$teamH{$aTeamName});
$cM->assign($teamH{$aTeamName},$teamH{$aTeamName},$d2+1+($alpha*$movFactor));
$b1 = $bCV->element($teamH{$hTeamName},1);
$b2 = $bCV->element($teamH{$aTeamName},1);
$probA = 1/(1+(1000**(($computerRating{"$aTeamName"}-(($computerRating{"$hTeamName"})+$homeFieldAdvantageNumber))/$divisor)));
$probB = 1/(1+(1000**((($computerRating{"$hTeamName"}+$homeFieldAdvantageNumber)-$computerRating{"$aTeamName"})/$divisor)));
$rand = rand();
if ($rand <= $probA)  {    #home team won
$results[$k] = "$hTeamName 1-0 $aTeamName";
$seasonWins{$hTeamName}++;
$seasonLosses{$aTeamName}++;
$bCV->assign($teamH{$hTeamName},1,$b1+0.5+(-$alpha*$movFactor));
$bCV->assign($teamH{$aTeamName},1,$b2-0.5+($alpha*$movFactor));
push @week14Results, "$hTeamName beats $aTeamName (Pac12 Championship)";
}
else {    #away team won. No ties.
$results[$k] = "$aTeamName 1-0 $hTeamName";
$seasonWins{$aTeamName}++;
$seasonLosses{$hTeamName}++;
$bCV->assign($teamH{$aTeamName},1,$b2+0.5+(-$alpha*$movFactor));
$bCV->assign($teamH{$hTeamName},1,$b1-0.5+($alpha*$movFactor));
push @week14Results, "$aTeamName beats $hTeamName (Pac12 Championship)";
}
$k++;


#decide ACC Champion
($aTeamName, $hTeamName) = ($accAtlanticWinner, $accCoastalWinner);
#add in elements of the matrix that are not dependent on who won
$x = $cM->element($teamH{$hTeamName},$teamH{$aTeamName}); 
$cM->assign($teamH{$hTeamName},$teamH{$aTeamName},$x-1+(-$alpha*$movFactor));
$cM->assign($teamH{$aTeamName},$teamH{$hTeamName},$x-1+(-$alpha*$movFactor));  #symmetric matrix, so I don't have to read in this value prior -- assume it is the same as x
$d1 = $cM->element($teamH{$hTeamName},$teamH{$hTeamName});
$cM->assign($teamH{$hTeamName},$teamH{$hTeamName},$d1+1+($alpha*$movFactor));
$d2 = $cM->element($teamH{$aTeamName},$teamH{$aTeamName});
$cM->assign($teamH{$aTeamName},$teamH{$aTeamName},$d2+1+($alpha*$movFactor));
$b1 = $bCV->element($teamH{$hTeamName},1);
$b2 = $bCV->element($teamH{$aTeamName},1);
$probA = 1/(1+(1000**(($computerRating{"$aTeamName"}-(($computerRating{"$hTeamName"})+$homeFieldAdvantageNumber))/$divisor)));
$probB = 1/(1+(1000**((($computerRating{"$hTeamName"}+$homeFieldAdvantageNumber)-$computerRating{"$aTeamName"})/$divisor)));
$rand = rand();
if ($rand <= $probA)  {    #home team won
$results[$k] = "$hTeamName 1-0 $aTeamName";
$seasonWins{$hTeamName}++;
$seasonLosses{$aTeamName}++;
$bCV->assign($teamH{$hTeamName},1,$b1+0.5+(-$alpha*$movFactor));
$bCV->assign($teamH{$aTeamName},1,$b2-0.5+($alpha*$movFactor));
push @week14Results, "$hTeamName beats $aTeamName (ACC Championship)";
}
else {    #away team won. No ties.
$results[$k] = "$aTeamName 1-0 $hTeamName";
$seasonWins{$aTeamName}++;
$seasonLosses{$hTeamName}++;
$bCV->assign($teamH{$aTeamName},1,$b2+0.5+(-$alpha*$movFactor));
$bCV->assign($teamH{$hTeamName},1,$b1-0.5+($alpha*$movFactor));
push @week14Results, "$aTeamName beats $hTeamName (ACC Championship)";
}
$k++;


#decide Big12 Champion
($aTeamName, $hTeamName) = ($big12No1, $big12No2);
#add in elements of the matrix that are not dependent on who won
$x = $cM->element($teamH{$hTeamName},$teamH{$aTeamName}); 
$cM->assign($teamH{$hTeamName},$teamH{$aTeamName},$x-1+(-$alpha*$movFactor));
$cM->assign($teamH{$aTeamName},$teamH{$hTeamName},$x-1+(-$alpha*$movFactor));  #symmetric matrix, so I don't have to read in this value prior -- assume it is the same as x
$d1 = $cM->element($teamH{$hTeamName},$teamH{$hTeamName});
$cM->assign($teamH{$hTeamName},$teamH{$hTeamName},$d1+1+($alpha*$movFactor));
$d2 = $cM->element($teamH{$aTeamName},$teamH{$aTeamName});
$cM->assign($teamH{$aTeamName},$teamH{$aTeamName},$d2+1+($alpha*$movFactor));
$b1 = $bCV->element($teamH{$hTeamName},1);
$b2 = $bCV->element($teamH{$aTeamName},1);
$probA = 1/(1+(1000**(($computerRating{"$aTeamName"}-(($computerRating{"$hTeamName"})+$homeFieldAdvantageNumber))/$divisor)));
$probB = 1/(1+(1000**((($computerRating{"$hTeamName"}+$homeFieldAdvantageNumber)-$computerRating{"$aTeamName"})/$divisor)));
$rand = rand();
if ($rand <= $probA)  {    #home team won
$results[$k] = "$hTeamName 1-0 $aTeamName";
$seasonWins{$hTeamName}++;
$seasonLosses{$aTeamName}++;
$bCV->assign($teamH{$hTeamName},1,$b1+0.5+(-$alpha*$movFactor));
$bCV->assign($teamH{$aTeamName},1,$b2-0.5+($alpha*$movFactor));
push @week14Results, "$hTeamName beats $aTeamName (Big12 Championship)";
}
else {    #away team won. No ties.
$results[$k] = "$aTeamName 1-0 $hTeamName";
$seasonWins{$aTeamName}++;
$seasonLosses{$hTeamName}++;
$bCV->assign($teamH{$aTeamName},1,$b2+0.5+(-$alpha*$movFactor));
$bCV->assign($teamH{$hTeamName},1,$b1-0.5+($alpha*$movFactor));
push @week14Results, "$aTeamName beats $hTeamName (Big12 Championship)";
}
$k++;



















#recompute the matrix
my ($dim, $base, $LRM);  # $LRM is the LR_Matrix defined in MatrixReal and returned by the method decompose_LR
$LRM = $cM->decompose_LR();
if ( ($dim,$rCV,$base) =  $LRM->solve_LR($bCV) ) {
#print "great, it solved the matrix\n"; #Note, I don't actually have to iterate. The Matrix solution takes care of that for me !
#print "r is \n"; #print "$rCV\n\n";
}
else {print "crap, there was no solution to the Colley Matrix  (This should not have happened\n)"; die;}




# week 14 stuff now all completed, get top 4 for this season itieration
my @output;  #should re-initalize
for (my $i = 1; $i<$#teams+2; $i++ ) {        # dont understand why +2, shuoldnt it be +1
#get the team rating in human readable form
my $rCVofI = $rCV->row($i);
$rCVofI =~ /\[(.+?)\]/;
$rCVofI = $1;    #remember, $rCVofI is coming in formatted as scientifc notation
$rCVofI = sprintf("%.3f", $rCVofI);    #Hate using sprintf here, should use something more modern. use of %.10g will lead to loss of conversion to decimal for numbers right around 0 - specifically 0.0000012345 will still come in scientific notation of 1.23E-6.  %.3f will round to thousands (both pos and negative)
$output[$i] = "$rCVofI\:\:$team{$i}:$rCVofI: record is $seasonWins{$team{$i}} and $seasonLosses{$team{$i}}\n";  #put the rating in front so I can sort on it
}

# there is a bug in perl. The below puts a warning for "Use of uninitialized value in sort at..." to remove it have to upgrade to perl 5.32+. Will do that after season is over, or after good backups exist
my @sortedOutput = reverse sort @output;   # This sorts alphabetically, not numerically.  .8 comes before .7, but -.06 comes before -.03

#print "@sortedOutput\n";   # gives this weird 60 before it prints the array

#get new ratings into array
foreach (@sortedOutput) {
my ($teamRating,$blank,$team,$record) = (split /:/, $_);
$agaRating[$teamH{$team}] = $teamRating;
}


#print "Georgia rating is $agaRating[$teamH{Georgia}]\n";
#print "LSU rating is $agaRating[$teamH{LSU}]\n";

#print "Top 4 computer this iteration post week 14 are:\n";
#print "$sortedOutput[0]";
#print "$sortedOutput[1]";
#print "$sortedOutput[2]";
#print "$sortedOutput[3]";





my @top4Result;  #re-init each itieration

for (my $m=0; $m<=3; $m++) {
my ($teamRating,$blank,$team,$record) = (split /:/, $sortedOutput[$m]);
$isInTop4{"$team"} = $isInTop4{"$team"} + 1;
$top4Result[$m] = $team;
}

@top4Result = sort @top4Result;
print "Top 4 computer this iteration post week 14 are (sorted alphabetically): ";
$thisTop4Result = "$top4Result[0]:$top4Result[1]:$top4Result[2]:$top4Result[3]";
print "$thisTop4Result\n";

$isTheTop4{"$thisTop4Result"} = $isTheTop4{"$thisTop4Result"} + 1;



#print "$l\n";


}



#print "@results\n";
my $u;
foreach $u (@significantUpsets) {
print "$u\n";
}
print "-------------\n";
foreach $u (@week14Results) {
print "$u\n";
}



print "===============\n";
#print "Alabama was in $isInTop4{'Alabama'} times\n";

foreach my $key ( sort { $isInTop4{$b} <=> $isInTop4{$a} } keys %isInTop4 ) {
   print "$key was in $isInTop4{$key} times\n"; 
}

print "\n";

#while (my ($k,$v)=each %isTheTop4){print "$k $v\n"}   #if you want to print out every top4 that occured to see which occurs most often

my @mostCommonTop4 = sort { $isTheTop4{$a} <=> $isTheTop4{$b} } keys %isTheTop4;
my $theMostCommonAlphabatizedTiedOccurence = $mostCommonTop4[-1];
print "Most common top 4 is $theMostCommonAlphabatizedTiedOccurence $isTheTop4{ $theMostCommonAlphabatizedTiedOccurence } occurences. (May be others tied) \n";

#print "@mostCommonTop4 \n";  #print out all the top4s that came out.

#print "@agaPPRanking <br><br>";
#print "@agaComputerRanking <br><br>";
#print "@committeeRanking <br><br>";


if ($itierations == 1000) {       #only output file if 1000 itierations. otherwise just run to see results on cli

my $playoffChancesFile = "/home/neville/cfbPlayoffPredictor/data/current/currentPlayoffPercentages.txt"; 
open (PCFILE, ">$playoffChancesFile") or die "$! error trying to overwrite";
foreach my $key ( sort { $isInTop4{$b} <=> $isInTop4{$a} } keys %isInTop4 ) {
   print PCFILE "$key:$isInTop4{$key}\n"; 
}
close PCFILE;
}

my $finishTime = time() - $^T;
print "program runtime = $finishTime seconds\n";

