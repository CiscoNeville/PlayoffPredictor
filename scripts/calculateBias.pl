#!/usr/bin/perl
################
#
# calculateBias.pl
# Determine the FBS playoff committee bias per team 
# Computes a playoff committee rating bias (rbCV)  which is the difference of where the aga matrix computes each teams  true rating and the listed ratings of the selection committee
# output a file that 
# this script needs to be run once a week on Tuesdays after 6:30pm by the admin
#
# first week this is valid for is week 9
#
# usage: calculateBias.pl year week
#
# input files: Week{X}CalculatedRatings.txt  week 9-15,  committeeCurrentRankingsFile week 9-15
#
# output files: CommitteeBias file week 9-15
#
##############



use strict;
#use Number::Format;
#use Data::Dumper;


if ($#ARGV != 1) {     #should be 2 aruments on CLI
print "Usgae: calculateBias.pl year week\n";
print "example- calculateBias.pl 2014 9\n";
die;
 }

my ($year,$week) = @ARGV;

if (($year < 2014) || ($year > 2050))  {
print "year should be 2014 - 2050\n";
die;
 }

if (($week < 9) || ($week > 15))  {
print "week should be between 9 - 15    -   15 reserved only for 2014,2019 season.  Otherwise 9-14\n";
die;
 }


#use these files for input and output
my $committeeCurrentRankingsFile = "/home/neville/cfbPlayoffPredictor/data/$year/week$week/Week$week"."PlayoffCommitteeRankings.txt";   #I know this week's committee rankings  - for POV, think of this script being run on wednesday
my $agaComputerRatingsInput = "/home/neville/cfbPlayoffPredictor/data/$year/week$week/Week$week"."CalculatedRatings.txt";
my $committeeBiasFile = "/home/neville/cfbPlayoffPredictor/data/$year/week$week/Week$week"."CommitteeBiasFile.txt";     #so I can compute this weeks bias file.  this is the output file of this script. It is a calculated committee bias file.


my $fbsTeams = "/home/neville/cfbPlayoffPredictor/data/$year/fbsTeamNames.txt";

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


my @teams=keys(%team);
my $numberOfTeams = $#teams + 1;  #$#teams starts counting at zero

my @agaRating;
my @computerRankingsToSort;

print "number of teams is $numberOfTeams\n\n";


my $j=0;      #there is probably a better way to do this

open (AGARATINGSFILE, "<$agaComputerRatingsInput") or die "$! error trying to open";     
for my $ratingsInput (<AGARATINGSFILE>) {
my ($team, $rating, $record) = (split /:/, $ratingsInput);
$computerRankingsToSort[$j] = $rating . ":" . $team;
$agaRating[$j] = $rating;
$j++;
}


my @computerRankingsSorted = reverse sort @computerRankingsToSort;

my @committeeRankings;  #should go Georgia, Alabama, Notre Dame, 
my @computerRankings;    #should go Georgia, Alabama, Wisconsin
my @computerRatings;     #should go .899,  .890,  .881


#append computer rank number to team
for (my $i=1; $i<$#teams+2; $i++)  {
$computerRankingsSorted[$i] = $computerRankingsSorted[$i] . ":" . $i ; 
}

for (my $i=0; $i<$#teams+1; $i++)  {
(my $a, $computerRankings[$i], my $c) = split (':', $computerRankingsSorted[$i]) ; 
}


#Read in playoff committee rankings from file
my $committeeInput;
my @committeeRanking;
my @committeeRating;
my @ratingBias;


open (COMMITTEECURRENTRANKINGS, "<$committeeCurrentRankingsFile") or die "$! error trying to open";
my $k =1;
for $committeeInput (<COMMITTEECURRENTRANKINGS>) {
my ($teamCRanking, $teamC) = split(':', $committeeInput);
$committeeRanking[$teamH{$teamC}] = "$teamCRanking";
chomp $teamC;
$committeeRankings[$k-1] = "$teamC";
$k++;
}
close COMMITTEECURRENTRANKINGS;


@computerRatings = reverse sort @agaRating;    #$computerRatings[5] will be my rating of the 5th team (like .850)

#print "$committeeRankings[0]   -   $computerRankings[0]    -  $computerRatings[0]  \n";  
#print "$committeeRankings[1]   -   $computerRankings[1]    -  $computerRatings[1]  \n";  
#print "$committeeRankings[2]   -   $computerRankings[2]    -  $computerRatings[2]  \n";  


#compare computer ranking to committee ranking, calculate and assign the bias
for (my $i = 1 ; $i <=25 ; $i++) {
for (my $j = 1 ; $j <=$#teams+2 ; $j++) {

if ($committeeRankings[$i-1] eq $computerRankings[$j-1])  {
my $bias =   ( $computerRatings[$i-1]  -  $computerRatings[$j-1]  );
#print "match on  $committeeRankings[$i-1]  ---   $i   $j   $computerRatings[$i-1]   $computerRatings[$j-1]  $bias \n";
my $length=length($committeeRankings[$i-1])  ;  # make printed output human readable friendly
$length = 20-$length;
my $pad = " " x $length;
print "$committeeRankings[$i-1]"." $pad "."  ---\t  committee rank=$i   computer rank=$j   bias=$bias \n";
 $ratingBias[$teamH{"$committeeRankings[$i-1]"}]  =  $bias;
}
}
}


#Assume committee rating equal to the 26th place of agaRating to any team not in the committee rankings, but in the top 25 of the Aga Matrix

my @sortedAgaRating = reverse sort @agaRating;  #$sortedAgaRating[25] will be the rating of my 26th team

for (my $i=0; $i<=25; $i++ ) {        
if (grep $_ eq $computerRankings[$i], @committeeRankings) {    #team is in both T25 of computer ranking and T25 of committee ranking
#print "$computerRankings[$i] was present in T25\n";   #Do nothing.
}
else {   #team is in top 25 of aga rating but not in top 25 of committee rankings
# print "$computerRankings[$i] was not present in T25\n";
my $bias =   ( $computerRatings[26]  -  $computerRatings[$i]  );    #at this point $i has incrimented, and $computerRating[26] is the computer rating of the 26th place team.
$ratingBias[$teamH{"$computerRankings[$i]"}]  =  $bias;
my $length=length($computerRankings[$i])  ;  # make printed output human readable friendly
$length = 20-$length;
my $pad = " " x $length;   
print "$computerRankings[$i]"." $pad "."  ---\t  committee rank=NR   computer rank=$i   bias=$bias \n";
}
}

#print "@committeeRankings\n";



#set all other ratingBias equal to 0  - I think they should go to zero, not undefined. That will make the average at week 14 for someone who gets in the poll at the end less significant.  We really want to capture the teams that stay over/underranked by the committee throughout the year.
for (my $i=1; $i<$#teams+2; $i++ ) {        # dont understand why +2, shuoldnt it be +1
if ($ratingBias[$i] eq '') {$ratingBias[$i] = 0;}    #this should catch bottom teams -- no committe ranking and not in aga top 25.
}





#If the ratingBias is in scientific notation, convert to decimal
for (my $i=1; $i<$#teams+2; $i++ ) {        #  +2 because 1AA is a team
if ($ratingBias[$i] =~ m/e/) {   #matches for an "e" in the x -e format of the number
$ratingBias[$i] = sprintf("%.3f", $ratingBias[$i]);
 }
 
#print "The ratingBias of $team{$i} is $ratingBias[$i]\n";
}



#print all the rating biases to a file
open (COMMITTEEBIASFILE, ">$committeeBiasFile") or die "$! error trying to overwrite";
for (my $i=1; $i<$#teams+1; $i++ ) {    #if I do $#teams+2 here I get 1AA. If I do $#teams+1 I dont get 1AA. The 1AA/FCS team is taken care of on the 3rd next line     
print COMMITTEEBIASFILE "$team{$i} ratingBias is $ratingBias[$i]\n";
}
print COMMITTEEBIASFILE "$team{$#teams+1} ratingBias is $ratingBias[$#teams+1]";  #gets rid of extra CRLF at last line
close COMMITTEEBIASFILE;

print "\nCommittee bias output for all teams written to $committeeBiasFile\n";