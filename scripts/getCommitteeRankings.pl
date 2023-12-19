#!/usr/bin/perl
##############
#
# get the current committee rankings into the rankings file
# Run this on Tuesdays after the committee rankings are announced
# First week this is valid for is week 9
#
# ***note that collegefootballplayoff.com is one week ahead -- that is their week 10 is my week 9***
# 
# Usage:getCommitteeRankings.pl year week
#
# Input files: none
#
# Output files:
#   currentPlayoffCommitteeRankings - playoff committee rankings for the previous week
#
#
# the committee ratings for each team are obtained my mapping the committee rankings to a committee rating using 
# the difference in computer rating for the committees ranking vs the computer ranking for that position in the committee rating  (2017+)
#
##############

use strict;
use WWW::Mechanize;
 
if ($#ARGV != 1) {     #should be 2 aruments on CLI
print "Usgae: getCommitteeRankings.pl year week\n";
print "example- getCommitteeRankings.pl 2014 9\n";
die;
 }

my ($year,$week) = @ARGV;

if (($year < 2014) || ($year > 2050))  {
print "year should be 2014 - 2050\n";
die;
 }

if (($week < 1) || ($week > 15))  {
print "week should be between 1 - 15\n";
die;
 }

my $nextWeek = $week +1;  #IMPORTANT--- If I want to see how the committee ranked teams at the end of week 11s games, I have to pull week 12 of data -- their calendar is one week ahead and goes weeks 10-16.   Mine goes weeks 9-15

#pull from ESPN CFP committee rankings
my $baseurl = "http://espn.go.com/college-football/playoffPicture";    #use this line alone for the current poll
#$baseurl = $baseurl."/_/week/$week/year/$year";  #append here for historical data.  2016- needs this $week. 
$baseurl = $baseurl."/_/week/$nextWeek/year/$year";  #append here for historical data. 2017- needs $nextWeek


my $browser = WWW::Mechanize->new();
my $html;
$browser->get($baseurl);
die $browser->response->status_line unless $browser->success;
$html = $browser->content;
#open (RANKINGS, ">/tmp/rankings1.html") or die "$! error trying to overwrite";
#print RANKINGS "$html\n";

my @committeeRankings = ($html =~ m/<tr class=".+?"><td><a href=".+?">(.+?)<\/a>/g)   ;       #should go Georgia, Alabama, Notre Dame, 
my @computerRankings;                                                                            #should go Georgia, Alabama, Wisconsin
my @computerRatings;                                                                             #should go .899,  .890,  .881


for (my $i = 0; $i<$#committeeRankings+1; $i++ ) {      
my $j=$i+1;        
print "$j".". $committeeRankings[$i] \n"; 
}

#clean up some bad name tagging on thier part. hopefully this will not be needed come week 10 2015, but it seems to be needed on aug 2015 for 2014 data. Note, still needed in 2017
for (my $i = 0; $i<$#committeeRankings+1; $i++ ) {  
if ($committeeRankings[$i] eq "Miss St")  {$committeeRankings[$i] = "Mississippi State"}
if ($committeeRankings[$i] eq "FSU")  {$committeeRankings[$i] = "Florida State"}
if ($committeeRankings[$i] eq "Florida St")  {$committeeRankings[$i] = "Florida State"}
if ($committeeRankings[$i] eq "OSU")  {$committeeRankings[$i] = "Ohio State"}
if ($committeeRankings[$i] eq "ECU")  {$committeeRankings[$i] = "East Carolina"}
if ($committeeRankings[$i] eq "Washington St")  {$committeeRankings[$i] = "Washington State"}
if ($committeeRankings[$i] eq "C. Carolina")  {$committeeRankings[$i] = "Coastal Carolina"}
if ($committeeRankings[$i] eq "Oklahoma St")  {$committeeRankings[$i] = "Oklahoma State"}
if ($committeeRankings[$i] eq "Mississippi St")  {$committeeRankings[$i] = "Mississippi State"}
if ($committeeRankings[$i] eq "Kansas St")  {$committeeRankings[$i] = "Kansas State"}
if ($committeeRankings[$i] eq "Oregon St")  {$committeeRankings[$i] = "Oregon State"}
if ($committeeRankings[$i] eq "Appalachian St")  {$committeeRankings[$i] = "Appalachian State"}
if ($committeeRankings[$i] eq "Boise St")  {$committeeRankings[$i] = "Boise State"}
if ($committeeRankings[$i] eq "San Diego St")  {$committeeRankings[$i] = "San Diego State"}




}


# Verify all top 25 team names are elements of the database of teams for that year
# read in teams for this year
my $fbsTeams = "/home/neville/cfbPlayoffPredictor/data/$year/fbsTeamNames.txt";
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

for (my $i = 0 ; $i <=24 ; $i++) {
if (!exists $teamH{$committeeRankings[$i]}) {
 print "\n ---> $committeeRankings[$i] does not exist in team names for $year -- please clean up bad tagging manually  <---\n\n";
}
}




# write out top 25 to file
my $committeeRankingsFile = "/home/neville/cfbPlayoffPredictor/data/$year/week$week/Week$week"."PlayoffCommitteeRankings.txt";

if (($week < 9) || ($week == 17)) {
$committeeRankingsFile = "/home/neville/cfbPlayoffPredictor/data/$year/week$week/Week$week"."APRankings.txt";  #use the moniker APRankings if it is not a cfp committee week
}


open (CFPRANKINGS, ">$committeeRankingsFile") or die "$! error trying to overwrite";
for (my $i = 1 ; $i <=24 ; $i++) {
print CFPRANKINGS "$i:$committeeRankings[$i-1]\n";
}
print CFPRANKINGS "25:$committeeRankings[24]";  #this prevents an extra CRLF in the output file

close CFPRANKINGS;