#!/usr/bin/perl
##############
#
# fullSeasonCommitteeBiasMatrix.pl
# take the input of the committeeBiasFile s for all filled in weeks of a season (from weeks 9-14, normally)
# average them all up and sort it by overrated to underrated teams
# not using AveragedCommitteeBiasXXX.txt input files -- that is used for the predictor, not here. we recompute those here. 
# should be run by cron on Tuesdays after committe ratings are released
#
#
# usage: printAllCommitteeBiases.pl
#
# input files: committeeBias file(s)  week 9-14   (if they exist, not mandatory)
#
# output files: ~/cfbPlayoffPredictor/data/<year>/fullSeasonCommitteeBiasMatrix.txt
#               this output file is used for input of home.php and <year>NcfRatingBiases.php
##############


###### end of global variables

use strict;
use Math::MatrixReal;
use Data::Dumper;
use Statistics::Basic qw(:all ipres=4);   #ipres sets the precision of the mean function - 3 significant digits
use List::Util qw ( min max );         #for the min / max / range functionality


my ($year) = @ARGV;
if ($#ARGV != 0) {     #should be 1 aruments on CLI
print "Usgae: fullSeasonCommitteeBiasMatrix.pl  <year>\n";
print "example- fullSeasonCommitteeBiasMatrix.pl 2017\n";
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


my $committeeBiasMatrix = "/home/neville/cfbPlayoffPredictor/data/$year/fullSeasonCommitteeBiasMatrix.txt";   #file for output


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
my $k;
my $committeeBiasFile;
my $biasInput;
my $z;
my $mean;
my $max;
my $min;
my $range;
my $week;
my $m = $firstPollWeek -1 ;
my $p;


#Create the weekly biases as a matrix --  best way I can figure to do it at this point
my $biasMatrix = new Math::MatrixReal($numberOfTeams, 11);  #changing from n-8 to 11 to add avg, min, max, range data at end of matrix #minus 8 becasue week 10 should be 2 columns wide, for example


for (   $k=$firstPollWeek;   $k<=$lastPollWeek+1;  $k++   ) {   

$committeeBiasFile = "/home/neville/cfbPlayoffPredictor/data/$year/week$k/Week$k" . "CommitteeBiasFile.txt";     # input file for script	
if (-f $committeeBiasFile) {
$week = $k;   #this week has data in it. use it.

open (SINGLEWEEKCOMMITTEEBIASFILE, "<$committeeBiasFile") or die "$! error trying to open";     
my $r = 0;
for $biasInput (<SINGLEWEEKCOMMITTEEBIASFILE>) {
 $r = $r +1;
($teamName, $teamBias) = (split / ratingBias is /, $biasInput);
 $biasMatrix->assign($r, $k-$m, $teamBias);      #format of matrix is [team, week]
}
close SINGLEWEEKCOMMITTEEBIASFILE;

}

}



print "Input bias files exist through week $week\n";

#print $biasMatrix->element (1,1);   #these are the weeks of Boston College's bias
#print $biasMatrix->element (1,2);
#print $biasMatrix->element (1,3);
#print $biasMatrix->element (1,4);
#print $biasMatrix->element (1,5);
#print $biasMatrix->element (1,6);
#print $biasMatrix->element (1,7);


my @output;  # use this to create elements like mean:Auburn:0.1:0.2:0:0:0:0:0:mean:max:min:range   #mean has to be 1st so you can reverse sort it

for (my $i=1; $i<$#teams+2; $i++ ) {    
my @w;
for ($z=$firstPollWeek ;  $z<=$week ; $z++) {
my $value = $biasMatrix->element ($i, $z-$m);
#print "$team{$i} ratingBias for week $z is $value"; 
push (@w, $value);
}
$mean = mean (  @w );
$min = min @w;
$min = mean ( $min );   # this just makes the varibale 4 significant digits and gets rid of the line feed
$max = max @w;
$max = mean ( $max );  #again, 4 significant digits, no line feed
$range = $max - $min;
$range = mean ( $range );  # shouldnt have to do this....

$biasMatrix->assign($i, 8, $mean);   # average rating bias in column 8
$biasMatrix->assign($i, 9, $max);   # max rating bias in column 9
$biasMatrix->assign($i, 10, $min);   # min rating bias in column 10
$biasMatrix->assign($i, 11, $range);   # range rating bias in column 11


my $week1Bias = $biasMatrix->element ($i, 1);
my $week2Bias = $biasMatrix->element ($i, 2);
my $week3Bias = $biasMatrix->element ($i, 3);
my $week4Bias = $biasMatrix->element ($i, 4);
my $week5Bias = $biasMatrix->element ($i, 5);
my $week6Bias = $biasMatrix->element ($i, 6);
my $week7Bias = $biasMatrix->element ($i, 7);


$week1Bias = mean ( $week1Bias ); #my odd way to round to 4 significant digits
$week2Bias = mean ( $week2Bias );
$week3Bias = mean ( $week3Bias );
$week4Bias = mean ( $week4Bias );
$week5Bias = mean ( $week5Bias );
$week6Bias = mean ( $week6Bias );
$week7Bias = mean ( $week7Bias );


push (@output, "$mean:$team{$i}:$week1Bias:$week2Bias:$week3Bias:$week4Bias:$week5Bias:$week6Bias:$week7Bias:$mean:$max:$min:$range");

}

#test outputs for Auburn
#$mean = $biasMatrix->element (53, 8);
#$max = $biasMatrix->element (53, 9);
#$min = $biasMatrix->element (53, 10);
#$range = $biasMatrix->element (53, 11);
#
#print "average ratingBias of $team{53} for is $mean\n";
#print "max ratingBias of $team{53} for is $max\n";
#print "min ratingBias of $team{53} for is $min\n";
#print "range ratingBias of $team{53} for is $range\n";


#print the sorted nonzero rating biases to the output file

open (COMMITTEEBIASMATRIX, ">$committeeBiasMatrix") or die "$! error trying to overwrite";

print "Team : AvgBias : Max : Min : Range\n";
print "-----------------------------------------------------------------\n";
@output = sort { $b <=> $a } @output;      #notation for reverse numerical sort
for (my $i=0; $i<$#teams+2; $i++ ) {    
my ($mean, $outputTeam, $w1b, $w2b, $w3b, $w4b, $w5b, $w6b, $w7b, $mean2, $max, $min, $range) = split (':',$output[$i]);   #$mean2 is garbage, just so I only have the data once 
if ($mean != 0){
print  "$outputTeam  $w1b $w2b $w3b $w4b $w5b $w6b $w7b $mean  $max $min $range\n";
print COMMITTEEBIASMATRIX "$outputTeam:$w1b:$w2b:$w3b:$w4b:$w5b:$w6b:$w7b:$mean:$max:$min:$range\n";
}
}

close COMMITTEEBIASMATRIX;




