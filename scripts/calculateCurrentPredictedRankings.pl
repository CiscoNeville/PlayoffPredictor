#!/usr/bin/perl
##############
#
# calculateCurrentPredictedRankings.pl
# Use the current AgaMatrix plus AverageCommitteeBiasFile to determine what the next poll will look like if the week ends now
#
# Input files: 
#   (year)/fbsTeamNames.txt
#   CurrentCalculatedRatingsFile.txt
#   CurrentAveragedCommitteeBiasFile.txt
#
# Output files:
#   CurrentPredictedRankings.txt
#
#
# note that agaMatrixRatings.txt and CurrentCalculatedRatings.txt have duplicate info. agaMatrixRatings.txt is to be retired. 
#
##############

use strict;
use Math::MatrixReal;
use Data::Dumper;

#get year from current date/time
my $year = 1900 + (localtime)[5];
if (localtime[4] lt 3) {$year = $year -1;}  #If in Jan or Feb, use last year for football season year

my $fbsTeams = "/home/neville/cfbPlayoffPredictor/data/$year/fbsTeamNames.txt";
my $CurrentCalculatedRatingsFile = "/home/neville/cfbPlayoffPredictor/data/current/CurrentCalculatedRatings.txt";
my $CurrentAveragedCommitteeBiasFile = "/home/neville/cfbPlayoffPredictor/data/current/CurrentAveragedCommitteeBiasFile.txt";
my $CurrentPredictedRankingsFile = "/home/neville/cfbPlayoffPredictor/data/current/CurrentPredictedRankings.txt";

my $teamName;
my $teamAgaRating;
my $teamAvgRatingBias;
my $line;
my $record;
my @teamPredictedCommitteeRating;



#Read in FBS teams for this year
my (%team,%teamH) = ();   #initialize both hases in one line
open (FBSTEAMS, "<", $fbsTeams) or die "Can't open the file $fbsTeams";
while (my $line = <FBSTEAMS> )   {
    chomp ($line);
    my ($a, $b) = split(" => ", $line);   #this will result in $key holding the name of a team (1st thing before split)
    $team{$b} = $a;
    $teamH{$a} = $b;
}     #at the conclusion of this block $team{4} will be "Florida State" and $teamH{Auburn} will be "53"
my $numberOfTeams = scalar keys %teamH;    #this exactly equals number of teams since there is no 0 dictionary-key to worry about
#print "$numberOfTeams\n"; #can check it here if you want to


#input in current calculated ratings  (obsoletes agaMatrix ratings file)
my %predictedCommitteeRating;
open (CURRENTCALCULATEDRATINGS, "<$CurrentCalculatedRatingsFile") or die "$! error trying to open";
for $line (<CURRENTCALCULATEDRATINGS>) {
($teamName, $teamAgaRating,$record) = (split /:/, $line);
$predictedCommitteeRating{$teamName} = $teamAgaRating;     #start the predicted committee rating with my rating  #old was $predictedCommitteeRating{$teamH{$teamName}}
}
close CURRENTCALCULATEDRATINGS;

#print "original predictedCommitteeRating (aka computer rating) of Auburn is $predictedCommitteeRating{Auburn} \n";
#print "original predictedCommitteeRating (aka computer rating) of Alabama is $predictedCommitteeRating{Alabama} \n";
#print "original predictedCommitteeRating (aka computer rating) of Tulsa is $predictedCommitteeRating{Tulsa} \n";


#get bias into a hash and add in the bias
my %teamBias;
open (CURRENTAVGBIAS, "<$CurrentAveragedCommitteeBiasFile") or die "$! error trying to open";
while (my $line = <CURRENTAVGBIAS>)  {
     chomp ($line);
     ($teamName, $teamAvgRatingBias) = (split / averageRatingBiasThroughWeek\d+ is /, $line);
     $teamBias{$teamName} = $teamAvgRatingBias;
     $predictedCommitteeRating{$teamName} = $predictedCommitteeRating{$teamName} + $teamBias{$teamName} ;  #add bias in to get predicted team rating
}
close CURRENTAVGBIAS;

#print "teamBias of Auburn is $teamBias{Auburn} \n";
#print "teamBias of Alabama is $teamBias{Alabama} \n";
#print "teamBias of Tulsa is $teamBias{Tulsa} \n";
#
#print 'new $predictedCommitteeRating{Auburn} is' . " $predictedCommitteeRating{Auburn} \n";
#print 'new $predictedCommitteeRating{Alabama} is' . " $predictedCommitteeRating{Alabama} \n";
#print 'new $predictedCommitteeRating{Tulsa} is' . " $predictedCommitteeRating{Tulsa} \n";

#at the end of this $predictedCommitteeRating{Auburn} will be meaningful, but note, $predictedCommitteeRating{53} is not correct and meaningless. 
#If you had to do that, use $predictedCommitteeRating{$team{53}}

##print out the results
#for (my $i=1; $i<($numberOfTeams+1); $i++ ) {    
#print "$team{$i} predictedCommitteeRating= $predictedCommitteeRating{$team{$i}}\n" ;
#}
#print "$team{53} predictedCommitteeRating= $predictedCommitteeRating{$team{53}}\n" ;
#print "Auburn predictedCommitteeRating= $predictedCommitteeRating{Auburn}\n" ;



#sort the results
my @sortedPCR = sort { $predictedCommitteeRating{$b} <=> $predictedCommitteeRating{$a} } keys %predictedCommitteeRating ;



#test by printing the whole array out
#foreach my $key ( @sortedPCR ) {
#    print "$key:$predictedCommitteeRating{$key}:\n";
#}




#print top 25 results to file
open (PREDICTEDRANKINGS, ">$CurrentPredictedRankingsFile") or die "$! error trying to overwrite";

foreach my $key ( @sortedPCR[0..24] ) {
    print PREDICTEDRANKINGS "$key:$predictedCommitteeRating{$key}:\n";
}

print "calculateCurrentPredictedRankings done. Output (top 25 results) written to $CurrentPredictedRankingsFile\n";
close PREDICTEDRANKINGS;
