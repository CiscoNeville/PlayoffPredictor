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
use JSON;
use List::Util;



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

my @agaRating;    #legacy way.  access with $agaRating[35] or $agaRating[$teamH{Georgia}]
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

my %isInTop12;
my %isTheTop12;
my $thisTop12Result;

my %agaRating;   #The way this should have been done to start - access with $agaRating{Georgia}
my %agaRanking;

my %conferenceChampion;   # $conferenceChampion{SEC} is Auburn   
my %championStatus;       # $championStatus{Auburn} is "SEC Champion" 


# set $year automatically based on current date. Can clobber if needed (below)
my $year = 1900 + (localtime)[5];
if ( ((localtime)[4] == 0) || ((localtime)[4] == 1) )  {$year = $year -1;}  #If in Jan or Feb, use last year for football season year
#$year = 2023;


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

# Use fbsTeams.json in 2024 for team conference data.  use it exclusively by 2025 and no longer fbsTeamNmaes.txt - todo
my $fbsTeamsJSON = "/home/neville/cfbPlayoffPredictor/data/"."$year"."/fbsTeams.json";

# Read the JSON file
open my $fh, '<', $fbsTeamsJSON or die "Cannot open $fbsTeamsJSON: $!";
my $json_text = do { local $/; <$fh> };
close $fh;

# Decode JSON
my $teams_data = decode_json($json_text);






for (my $l=1; $l<=$itierations; $l++) {
    %conferenceChampion = ();      #clear the hashes at the beginning of each iteration
    %championStatus = ();

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


#if (1==2)     #skip this all if week 14 games are already in schedule  #todo - make this better
#{


# Group teams by conference
my %conferences;
foreach my $team (@$teams_data) {
    push @{$conferences{$team->{conference}}}, $team->{team_name};
}

#set up conference champion variables
my ($SEC_Champion, $Big10_Champion, $Big12_Champion, $ACC_Champion, $AAC_Champion, $MountainWest_Champion, $SubBelt_Champion, $CUSA_Champion, $MidAmerican_Champion);  


# Function to get top two teams by rating for a given conference
sub get_top_two_teams {
    my ($conference_name) = @_;
    my @conference_teams = @{$conferences{$conference_name}};
    my ($top1, $top2, $top1_rating, $top2_rating) = (undef, undef, -1, -1);

    foreach my $team (@conference_teams) {
        my $team_id = $teamH{$team};
        my $rating = $agaRating[$team_id];
        if ($rating > $top1_rating) {
            $top2 = $top1;
            $top2_rating = $top1_rating;
            $top1 = $team;
            $top1_rating = $rating;
        } elsif ($rating > $top2_rating) {
            $top2 = $team;
            $top2_rating = $rating;
        }
    }

    return ($top1, $top2, $top1_rating, $top2_rating);
}

# Set up all week 14 matches
# just using highest 2 rated teams in a conference. need to put in conference record, 3 way tie, divisions (if those ever come back) - ToDo. 

#my $conference_name = "SEC";
my ($secNo1, $secNo2, $secNo1Rating, $secNo2Rating) = get_top_two_teams("SEC");
#print "SEC #1 was $secNo1 with a rating of $agaRating[$teamH{$secNo1}]\n";
#print "SEC #2 was $secNo2 with a rating of $agaRating[$teamH{$secNo2}]\n";

my ($bigTenNo1, $bigTenNo2, $bigTenNo1Rating, $bigTenNo2Rating) = get_top_two_teams("B10");
#print "BigTen #1 was $bigTenNo1 with a rating of $agaRating[$teamH{$bigTenNo1}]\n";
#print "BigTen #2 was $bigTenNo2 with a rating of $agaRating[$teamH{$bigTenNo2}]\n";

my ($accNo1, $accNo2, $accNo1Rating, $accNo2Rating) = get_top_two_teams("ACC");
#print "ACC #1 was $accNo1 with a rating of $agaRating[$teamH{$accNo1}]\n";
#print "ACC #2 was $accNo2 with a rating of $agaRating[$teamH{$accNo2}]\n";

my ($big12No1, $big12No2, $big12No1Rating, $big12No2Rating) = get_top_two_teams("B12");
#print "big12 #1 was $big12No1 with a rating of $agaRating[$teamH{$big12No1}]\n";
#print "big12 #2 was $big12No2 with a rating of $agaRating[$teamH{$big12No2}]\n";

my ($aacNo1, $aacNo2, $aacNo1Rating, $aacNo2Rating) = get_top_two_teams("AAC");
#print "AAC #1 was $aacNo1 with a rating of $agaRating[$teamH{$aacNo1}]\n";
#print "AAC #2 was $aacNo2 with a rating of $agaRating[$teamH{$aacNo2}]\n";

my ($cusaNo1, $cusaNo2, $cusaNo1Rating, $cusaNo2Rating) = get_top_two_teams("CUSA");
#print "CUSA #1 was $cusaNo1 with a rating of $agaRating[$teamH{$cusaNo1}]\n";
#print "CUSA #2 was $cusaNo2 with a rating of $agaRating[$teamH{$cusaNo2}]\n";

my ($midAmNo1, $midAmNo2, $midAmNo1Rating, $midAmNo2Rating) = get_top_two_teams("Mid-American");
#print "MidAm #1 was $midAmNo1 with a rating of $agaRating[$teamH{$midAmNo1}]\n";
#print "MidAm #2 was $midAmNo2 with a rating of $agaRating[$teamH{$midAmNo2}]\n";

my ($sbNo1, $sbNo2, $sbNo1Rating, $sbNo2Rating) = get_top_two_teams("Sun Belt");
#print "Sun Belt #1 was $sbNo1 with a rating of $agaRating[$teamH{$sbNo1}]\n";
#print "Sun Belt #2 was $sbNo2 with a rating of $agaRating[$teamH{$sbNo2}]\n";

my ($mwNo1, $mwNo2, $mwNo1Rating, $mwNo2Rating) = get_top_two_teams("Mountain West");
#print "Mountain West #1 was $mwNo1 with a rating of $agaRating[$teamH{$mwNo1}]\n";
#print "Mountain West #2 was $mwNo2 with a rating of $agaRating[$teamH{$mwNo2}]\n";








#these stay the same for all week 14 games for now
my $divisor = 1;  #base = 1000,  divisor =1
my $homeFieldAdvantageNumber =0; #this is week 14


sub simulate_championship {
    my ($conf_name, $team1, $team2) = @_;
    
    my ($aTeamName, $hTeamName) = ($team1, $team2);
    
    # Matrix updates
    my $x = $cM->element($teamH{$hTeamName}, $teamH{$aTeamName});
    $cM->assign($teamH{$hTeamName}, $teamH{$aTeamName}, $x - 1 + (-$alpha * $movFactor));
    $cM->assign($teamH{$aTeamName}, $teamH{$hTeamName}, $x - 1 + (-$alpha * $movFactor));
    
    my $d1 = $cM->element($teamH{$hTeamName}, $teamH{$hTeamName});
    $cM->assign($teamH{$hTeamName}, $teamH{$hTeamName}, $d1 + 1 + ($alpha * $movFactor));
    
    my $d2 = $cM->element($teamH{$aTeamName}, $teamH{$aTeamName});
    $cM->assign($teamH{$aTeamName}, $teamH{$aTeamName}, $d2 + 1 + ($alpha * $movFactor));
    
    my $b1 = $bCV->element($teamH{$hTeamName}, 1);
    my $b2 = $bCV->element($teamH{$aTeamName}, 1);
    
    # Probability calculation
    my $probA = 1 / (1 + (1000 ** (($computerRating{$aTeamName} - ($computerRating{$hTeamName} + $homeFieldAdvantageNumber)) / $divisor)));
    
    my $rand = rand();
    my ($winner, $loser);
    
    if ($rand <= $probA) {
        $winner = $hTeamName;
        $loser = $aTeamName;
        $bCV->assign($teamH{$hTeamName}, 1, $b1 + 0.5 + (-$alpha * $movFactor));
        $bCV->assign($teamH{$aTeamName}, 1, $b2 - 0.5 + ($alpha * $movFactor));
    } else {
        $winner = $aTeamName;
        $loser = $hTeamName;
        $bCV->assign($teamH{$aTeamName}, 1, $b2 + 0.5 + (-$alpha * $movFactor));
        $bCV->assign($teamH{$hTeamName}, 1, $b1 - 0.5 + ($alpha * $movFactor));
    }
    
    $seasonWins{$winner}++;
    $seasonLosses{$loser}++;
    $conferenceChampion{$conf_name} = $winner;
    $championStatus{$winner} = "- $conf_name Champion";

    my $result = "$winner 1-0 $loser";
    my $week14Result = "$winner beats $loser ($conf_name Championship)";

    
    return ($result, $week14Result, $winner);
}

# play conference championship games:
my ($secResult, $secWeek14Result, $SEC_Champion) = simulate_championship("SEC", $secNo1, $secNo2);
$results[$k++] = $secResult;
push @week14Results, $secWeek14Result;

my ($bigTenResult, $bigTenWeek14Result, $Big10_Champion) = simulate_championship("B10", $bigTenNo1, $bigTenNo2);
$results[$k++] = $bigTenResult;
push @week14Results, $bigTenWeek14Result;

my ($accResult, $accWeek14Result, $ACC_Champion) = simulate_championship("ACC", $accNo1, $accNo2);
$results[$k++] = $accResult;
push @week14Results, $accWeek14Result;

my ($big12Result, $big12Week14Result, $Big12_Champion) = simulate_championship("B12", $big12No1, $big12No2);
$results[$k++] = $big12Result;
push @week14Results, $big12Week14Result;


#G5 championship games
my ($aacResult, $aacWeek14Result, $AAC_Champion) = simulate_championship("AAC", $aacNo1, $aacNo2);
$results[$k++] = $aacResult;
push @week14Results, $aacWeek14Result;

my ($mwResult, $mwWeek14Result, $MountainWest_Champion) = simulate_championship("Mountain West", $mwNo1, $mwNo2);
$results[$k++] = $mwResult;
push @week14Results, $mwWeek14Result;

my ($sbResult, $sbWeek14Result, $SunBelt_Champion) = simulate_championship("Sun Belt", $sbNo1, $sbNo2);   #sunbelt uses divisions - TODO
$results[$k++] = $sbResult;
push @week14Results, $sbWeek14Result;

my ($cusaResult, $cusaWeek14Result, $CUSA_Champion) = simulate_championship("CUSA", $cusaNo1, $cusaNo2);
$results[$k++] = $aacResult;
push @week14Results, $cusaWeek14Result;

my ($midAmResult, $midAmWeek14Result, $MidAmerican_Champion) = simulate_championship("Mid-American", $midAmNo1, $midAmNo2);
$results[$k++] = $midAmResult;
push @week14Results, $midAmWeek14Result;






#recompute the matrix
my ($dim, $base, $LRM);  # $LRM is the LR_Matrix defined in MatrixReal and returned by the method decompose_LR
$LRM = $cM->decompose_LR();
if ( ($dim,$rCV,$base) =  $LRM->solve_LR($bCV) ) {
#print "great, it solved the matrix\n"; #Note, I don't actually have to iterate. The Matrix solution takes care of that for me !
#print "r is \n"; #print "$rCV\n\n";
}
else {print "crap, there was no solution to the Colley Matrix  (This should not have happened\n)"; die;}

#}


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
foreach my $index (0 .. $#sortedOutput) {
    my ($teamRating, $blank, $team, $record) = split /:/, $sortedOutput[$index];
    $agaRating[$teamH{$team}] = $teamRating;
    $agaRating{$team} = $teamRating;
    $agaRanking{$team} = $index+1;
}


#print "Georgia rating is $agaRating{Georgia} ranked #$agaRanking{Georgia} with $seasonWins{Georgia} wins and $seasonLosses{Georgia} losses\n";   #new good way
#print "LSU rating is $agaRating[$teamH{LSU}]\n";   #old bad way



#pick up $teams_data from line 130 for conference affiliation
#top 4 conference champions get byes in round 1
#5th highest ranked conference champion gets a 5-12 slot
#other 7 slots are filled with highest rated teams


for (my $m=0; $m<=19; $m++) {
    my ($teamRating, $blank, $team, $record) = split /:/, $sortedOutput[$m]; #$sortedOutput already has the teams in rank order
    print "#$agaRanking{$team} $team - $seasonWins{$team}-$seasonLosses{$team}  $championStatus{$team}\n";
}


#print "$conferenceChampion{SEC}\n";



my @champions = ();  #reset these at the beginning of each run
my @non_champions = ();
my @final_selection = ();

# First, separate champions and non-champions
for my $team_info (@sortedOutput) {
    my ($teamRating, $blank, $team, $record) = split /:/, $team_info;
    if (exists $championStatus{$team}) {
        push @champions, $team;
    } else {
        push @non_champions, $team;
    }
}

# Select top 5 champions
push @final_selection, @champions[0..4];

# Fill the rest with top 7 non-champions
push @final_selection, @non_champions[0..6];

# Now @final_selection contains the desired 12 teams

# Print the results and update isInTop12
for (my $m = 0; $m < 12; $m++) {
    my $team = $final_selection[$m];
    my ($teamRating, $blank, $dummy, $record) = split /:/, (grep { /:$team:/ } @sortedOutput)[0];
#   print "#$agaRanking{$team} $team - $seasonWins{$team}-$seasonLosses{$team} $championStatus{$team}\n";
    $isInTop12{$team}++;
}

my @top12Result = @final_selection;


@top12Result = sort @top12Result;
print "Top 12 computer this iteration post week 14 are (sorted alphabetically): ";
$thisTop12Result = "$top12Result[0]:$top12Result[1]:$top12Result[2]:$top12Result[3]:$top12Result[4]:$top12Result[5]:$top12Result[6]:$top12Result[7]:$top12Result[8]:$top12Result[9]:$top12Result[10]:$top12Result[11]";
print "$thisTop12Result\n";

$isTheTop12{"$thisTop12Result"} = $isTheTop12{"$thisTop12Result"} + 1;



#print "$l\n";


}



#print "@results\n";
my $u;
foreach $u (@significantUpsets) {
print "$u\n";
}
#print "-------------\n";
#foreach $u (@week14Results) {
#print "$u\n";
#}



print "===============\n";
#print "Alabama was in $isInTop12{'Alabama'} times\n";

foreach my $key ( sort { $isInTop12{$b} <=> $isInTop12{$a} } keys %isInTop12 ) {
   print "$key was in $isInTop12{$key} times\n"; 
}

print "\n";

#while (my ($k,$v)=each %isTheTop12){print "$k $v\n"}   #if you want to print out every top12 that occured to see which occurs most often

my @mostCommonTop12 = sort { $isTheTop12{$a} <=> $isTheTop12{$b} } keys %isTheTop12;
my $theMostCommonAlphabatizedTiedOccurence = $mostCommonTop12[-1];
print "Most common top 12 is $theMostCommonAlphabatizedTiedOccurence $isTheTop12{ $theMostCommonAlphabatizedTiedOccurence } occurences. (May be others tied) \n";

#print "@mostCommonTop12 \n";  #print out all the top12s that came out.

#print "@agaPPRanking <br><br>";
#print "@agaComputerRanking <br><br>";
#print "@committeeRanking <br><br>";


if ($itierations == 1000) {       #only output file if 1000 itierations. otherwise just run to see results on cli

my $playoffChancesFile = "/home/neville/cfbPlayoffPredictor/data/current/currentPlayoffPercentages.txt"; 
open (PCFILE, ">$playoffChancesFile") or die "$! error trying to overwrite";
foreach my $key ( sort { $isInTop12{$b} <=> $isInTop12{$a} } keys %isInTop12 ) {
   print PCFILE "$key:$isInTop12{$key}\n"; 
}
close PCFILE;
}

my $finishTime = time() - $^T;
print "program runtime = $finishTime seconds\n";

