#!/usr/bin/perl
##############
#
# calculateAverageBias.pl
# take the input of the committeeBiasFile s for the previous weeks of a season (from weeks 10-15)
# and calculate the average bias for a team for the whole season
# get the week number of input from the cli
#
# usage: calculateAverageBias.pl year week
#
# input files: committeeBias file(s)  week 9-15
#
# output files: averageCommitteeBias file week 9-15
#
#
#
##############



use strict;
use Math::MatrixReal;
use Data::Dumper;
use Statistics::Basic qw(:all ipres=4);   #ipres sets the precision of the mean function - 3 significant digits


if ($#ARGV != 1) {     #should be 2 aruments on CLI
print "Usgae: calculateAverageBias.pl year week\n";
print "example- calculateAverageBias.pl 2014 10\n";
die;
 }

my ($year,$week) = @ARGV;

if (($year < 2014) || ($year > 2050))  {   #think I'll do this after I'm 90 years old? 
print "year should be between 2014 and 2050\n";
die;
 }


# How to understand weeks - important - take the saturday of week 1 and count the number of days from that day to the 
#  satur(day) of the SEC championship game.  If the count is 92 days then the week 14=the last week / final poll, and Army-Navy=week 15
#  if the count is 99 days then week 15=last week / final poll and army-navy= week 16
my $firstPollWeek = 9;  #usual situation. This was the case for 2015-2018
if ($year eq "2019") {
 $firstPollWeek = 10;
}
my $lastPollWeek = 14;  #usual situation. Week 14 is SEC Championship week. 
if ($year eq "2019") {
 $lastPollWeek = 15;
}


if (($week < $firstPollWeek) || ($week > $lastPollWeek))  {
print "week should be between $firstPollWeek - $lastPollWeek for the year $year\n";
die;
 }


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



my $teamName;
my $teamBias;
my $numberOfTeams = $#teams + 1;  #$#teams starts counting at zero
my $n = $week;  # week number of input  
my $k;
my $committeeBiasFile;
my $averageCommitteeBiasFile;
my $biasInput;
my $z;

my $m = $firstPollWeek -1 ;
my $p;


#Create the weekly biases as a matrix --  best way I can figure to do it at this point
my $biasMatrix = new Math::MatrixReal($numberOfTeams, $n-$m);  #minus 8 becasue week 10 should be 2 columns wide in a "normal" (weeks 9-14) year, for example




for (   $k=$firstPollWeek;   $k<=$week;  $k++   ) {   

$committeeBiasFile = "/home/neville/cfbPlayoffPredictor/data/$year/week$k/Week$k" . "CommitteeBiasFile.txt";     # input file for script	
open (SINGLEWEEKCOMMITTEEBIASFILE, "<$committeeBiasFile") or die "$! error trying to open";     

my $r = 0;
for $biasInput (<SINGLEWEEKCOMMITTEEBIASFILE>) {
 $r = $r +1;
($teamName, $teamBias) = (split / ratingBias is /, $biasInput);
 $p = $k - $m;
 $biasMatrix->assign($r, $p, $teamBias);      #format of matrix is [team, week]
}

close SINGLEWEEKCOMMITTEEBIASFILE;


}

#my $value = $biasMatrix->element (1, 2);
#print "$value\n";


#print $biasMatrix;

#print $biasMatrix->element (1,1);   #these are the weeks of Boston College's bias
#print $biasMatrix->element (1,2);
#print $biasMatrix->element (1,3);
#print $biasMatrix->element (1,4);
#print $biasMatrix->element (1,5);
#print $biasMatrix->element (1,6);

#print "\n";
#print $biasMatrix->element (2,1); #these are the weeks of Clemson's bias
#print $biasMatrix->element (2,2);
#print $biasMatrix->element (2,3);
#print $biasMatrix->element (2,4);
#print $biasMatrix->element (2,5);
#print $biasMatrix->element (2,6);
#print "\n";



$averageCommitteeBiasFile =  "/home/neville/cfbPlayoffPredictor/data/$year/week$week/Week$week" . "AverageCommitteeBiasFile.txt";     # output file for script
#$averageCommitteeBiasFile = "/tmp/Week$week" . "AverageCommitteeBiasFile.txt";   # testing




open (AVERAGECOMMITTEEBIASFILE, ">$averageCommitteeBiasFile") or die "$! error trying to overwrite";   
for (my $i=1; $i<$#teams+2; $i++ ) {    
my @w;
for ($z=$firstPollWeek ;  $z<=$week ; $z++) {
my $value = $biasMatrix->element ($i, $z-$m);
#print "$team{$i} ratingBias for week $z is $value"; 
push (@w, $value);
}
my $mean = mean (  @w );
print "average ratingBias of $team{$i} for weeks 9-$week is $mean\n";
print AVERAGECOMMITTEEBIASFILE "$team{$i} averageRatingBiasThroughWeek$week is $mean\n";
}
close AVERAGECOMMITTEEBIASFILE;
print "output through week number $week written to $averageCommitteeBiasFile\n";


