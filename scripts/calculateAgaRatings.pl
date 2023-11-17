#!/usr/bin/perl
##############
#
# calculateAgaRatings.pl
# Use the scores to this point in the season to compute the Aga Matrix
# AgaMatrix is defined as Colley Matrix with all IAA teams repreented by a single team and using MoV
#
# Can be called with 0 arguments (use current data)
#  or called with 2 arguments (historical data) 
#
#
# Input files: 
#   ncfScoresFile - contains results of games played up to that point in the season
#
# Output files:
#   CurrentCalculatedRatings.txt  - agaMatrix ratings of all teams to that point in season. Used to populate homepage and sort for analyze schedule  (current invocation)
#    or Week{X}CalculatedRatings.txt  - agaMatrix ratings {historical invocation}
#
##############


use strict;
use Math::MatrixReal;
use Data::Dumper;
use experimental 'smartmatch';     #gets rid of CLI warning

#print "number of CLI arguments is $#ARGV\n";


my $ncfScoresFile;  
my $fbsTeams;
my $calculatedRatingsFile;
my ($year,$week);

#If called with zero arguments use current data
if ($#ARGV == -1) {  
$year = 1900 + (localtime)[5];
if (localtime[4] lt 3) {$year = $year -1;}  #If in Jan or Feb, use last year for football season year
$ncfScoresFile = "/home/neville/cfbPlayoffPredictor/data/current/ncfScoresFile.txt";  
$fbsTeams = "/home/neville/cfbPlayoffPredictor/data/$year/fbsTeamNames.txt";
$calculatedRatingsFile = "/home/neville/cfbPlayoffPredictor/data/current/CurrentCalculatedRatings.txt";
}

#If called with two arguments use historical data
elsif ($#ARGV == 1) {  
($year,$week) = @ARGV;
if (($year < 1947) || ($year > 2050))  {
print "year should be 1947 - 2050\n";
die;
 }
if (($week < 1) || ($week > 17))  {
print "week should be between 1 - 17    -   15 reserved only for 2014 season.  Otherwise 9-14 and 17\n";
die;
 }
 
$ncfScoresFile = "/home/neville/cfbPlayoffPredictor/data/$year/week$week/Week$week"."NcfScoresFile.txt";   
$fbsTeams = "/home/neville/cfbPlayoffPredictor/data/$year/fbsTeamNames.txt";
$calculatedRatingsFile = "/home/neville/cfbPlayoffPredictor/data/$year/week$week/Week$week"."CalculatedRatings.txt";
}

#called with wrong aruments, die
else {
print "Usgae: calculateAgaRatings.pl  [year week]  \n";
print "example- calculateAgaRatings.pl 2014 9\n";
die; 
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
my %seasonWins;
my %seasonLosses;

my $useMarginOfVictory = 1;   
my $mov;
my $movFactor;
my $alpha = -0.5;     #If alpha=0 this should simplify to original colley matrix.


my @agaRating;


print "number of teams is $numberOfTeams\n";
#create the matrix



#create blank matricies
$cM = new Math::MatrixReal($numberOfTeams,$numberOfTeams);
$rCV = new Math::MatrixReal($numberOfTeams,1);    #column vector is this notation. lots of rows, 1 column
$bCV = new Math::MatrixReal($numberOfTeams,1);

#create zeros for initial wins and losses for every team
for (my $i = 1; $i<$numberOfTeams+1; $i++ ) {
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
#print "FCS team $aTeamName $aTotal - $hTeamName $hTotal (FBS) on the road\n";
$aTeamName = "1AA";
}


if (  ($hTeamName ~~ [values %team])    )  {    #Like it would ever happen -- a 1A team plays a game on the road against a 1AA team
#that's great, home team is FBS. Do Nothing
}
else {   #otherwise it was a 1AA team
#print "FCS team $hTeamName $hTotal played FBS $aTeamName $aTotal at home\n";
$hTeamName = "1AA";
}

#populate the matrix with the data
$mov=($hTotal - $aTotal);


#tiered MoV AS PER PAPER WHICH WILL BE CANON
$movFactor = 0; #standard game
if (($mov>=1) && ($mov<=2)) {$movFactor=-0.2} elsif (($mov<=-1) && ($mov>=-2)) {$movFactor=+0.2} #close game type 1
if (($mov>=25) && ($mov<=34)) {$movFactor=+0.2} elsif (($mov<=-25) && ($mov>=-34)) {$movFactor=-0.2} #blowout type 1
if ($mov>=35) {$movFactor=+0.3} elsif ($mov<=-35) {$movFactor=-0.3} #blowout type 2

#add in elements of the matrix
my $x = $cM->element($teamH{$hTeamName},$teamH{$aTeamName});   #row,column so bottom left element of the 2 teams, assuming home team is lower team number
$cM->assign($teamH{$hTeamName},$teamH{$aTeamName},$x-1+($alpha*$movFactor));
my $y = $cM->element($teamH{$aTeamName},$teamH{$hTeamName});                #top right
$cM->assign($teamH{$aTeamName},$teamH{$hTeamName},$y-1+(-$alpha*$movFactor)); 

my $d1 = $cM->element($teamH{$hTeamName},$teamH{$hTeamName});     #diagonal top-left
$cM->assign($teamH{$hTeamName},$teamH{$hTeamName},$d1+1+($alpha*$movFactor));
my $d2 = $cM->element($teamH{$aTeamName},$teamH{$aTeamName});     #diagonal bottom-right
$cM->assign($teamH{$aTeamName},$teamH{$aTeamName},$d2+1+(-$alpha*$movFactor));


my $b1 = $bCV->element($teamH{$hTeamName},1);
my $b2 = $bCV->element($teamH{$aTeamName},1);

#Figure out which team won and give it to the @results array in 1-0 format
 if ($hTotal > $aTotal)  {    #home team won
$results[$k] = "$hTeamName 1-0 $aTeamName";

$seasonWins{$hTeamName} = $seasonWins{$hTeamName} + 1;
$seasonLosses{$aTeamName}++;


$bCV->assign($teamH{$hTeamName},1,$b1+0.5);
$bCV->assign($teamH{$aTeamName},1,$b2-0.5);

}
else {    #away team won. No ties anymore...
$results[$k] = "$aTeamName 1-0 $hTeamName";
$seasonWins{$aTeamName}++;
$seasonLosses{$hTeamName}++;

$bCV->assign($teamH{$aTeamName},1,$b2+0.5);
$bCV->assign($teamH{$hTeamName},1,$b1-0.5);


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
}
else {
print "crap, there was no solution to the Colley Matrix  (This should not have happened\n)";
die;
}

#print out the team ratings
#1st split the ratings out into an array with an index for each team


for (my $i = 1; $i<$#teams+2; $i++ ) {        # dont understand why +2, shuoldnt it be +1
 
#get the team rating in human readable form
my $rCVofI = $rCV->row($i);
$rCVofI =~ /\[(.+?)\]/;
$rCVofI = $1;
$rCVofI = sprintf("%.10g", $rCVofI);
#$rCVofI = substr $rCVofI, 0, 5;     # truncate out anything under thousandnths. note, this casues a loss of precision. display only.
if ($rCVofI eq 0.5)  {     #get 0.5 entries in 0.500 format for later sorting  
 $rCVofI = "0.500";
}

$agaRating[$i] = $rCVofI;
#print "$team{$i} agaRating is $rCVofI\n";
}






#print out sorted ratings for home page and analyze schedule use
open (CALCULATEDRATINGSFILE, ">$calculatedRatingsFile") or die "$! error trying to overwrite";
my @ratings;
my @sortedRatings;

for (my $i=1; $i<$#teams+2; $i++ ) {    
push @ratings, "$agaRating[$i]:$team{$i}:$agaRating[$i]: record is $seasonWins{$team{$i}} and $seasonLosses{$team{$i}}"
}
@sortedRatings = reverse sort @ratings;
for (my $i=0; $i<$#teams+1; $i++ ) {    
$sortedRatings[$i] =~ /(.+?):(.+?):(.+?):(.+)/;   #need to greedily take the last part (no : delimiter at the end)
$sortedRatings[$i] = "$2:$3:$4";   #get rid of the rating in the front (needed earlier to sort) 
#print  "$sortedRatings[$i]\n";
print CALCULATEDRATINGSFILE "$sortedRatings[$i]\n";
}
print "calculateAgaRatings done. Output written to $calculatedRatingsFile\n";
close CALCULATEDRATINGSFILE;







