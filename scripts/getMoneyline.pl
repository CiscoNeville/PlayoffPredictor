#!/usr/bin/perl
##############
#
# Pull from the-odds-api.com for money line for that week
# Intended to run on Tuesday nights to get data ready for the upcoming saturday
#
#
# Input files: none
#
# Output files:
#   ncfMoneylineFile - contains moneyline for games played the next week in the season
#
# 
##############

use strict;
use warnings;
use HTML::Parser;
use Data::Dumper;
use WWW::Mechanize;
use HTML::TokeParser;
use File::Copy;
use JSON;
use Data::Dumper;
use feature qw/ say /;

my $ncfMoneylineFile = "/home/neville/cfbPlayoffPredictor/data/current/ncfMoneylineFile.txt";


my $weekNumber;    # goes from 1 to 16
my $gameStatus;
my $aTeamName;
my $aTotal;
my $hTeamName;
my $hTotal;
my $team1;
my $team2;
my %team1;
my %team2;
my $score1;
my $score2;
my %status;
my $moneyline1;
my $moneyline2;

my $json;
my $week=0;


open (NCFMONEYLINEFILE, ">$ncfMoneylineFile") or die "$! error trying to overwrite";

print "getting  data\n";



my (@ops) = @_;
#$confId = $ops[0];
#$seasonYear = $ops[1];
#$seasonType = $ops[2];
#$weekNumber = $ops[3];

my $apiKey = "3a1d2f296f531995b99287fb928dda29";
my $baseurl ="https://api.the-odds-api.com/v4/sports/americanfootball_ncaaf/odds?regions=us&oddsFormat=american&apiKey=$apiKey";   #v3 scant data. use v4 here

my $browser = WWW::Mechanize->new();
my $html;
$browser->get($baseurl);
die $browser->response->status_line unless $browser->success;
$html = $browser->content;


open (SCORES1, ">/tmp/moneyline.json") or die "$! error trying to overwrite";
print SCORES1 "$html\n";

my $data = decode_json $html;
 
#print Dumper $data;
my $dumpedData = Dumper $data;
open (SCORES2, ">/tmp/moneylineV4.txt") or die "$! error trying to overwrite";
print SCORES2 "$dumpedData";




my @events = @{ $data -> {"data"}    };			 #understand that events points to a [] , so use a @array

for (my $i=0; $i<100 ; $i++)  {                                         #I should really do a foreach here instead...
if ( ( @{$events[$i] ->  {"teams"} }   )) {               #again, i shuld use foreach...  #dont need the word defined here, depricated if (defined())
my @competitors = @{ $events[$i] ->  {"teams"}      };


$team2 = $competitors[0];                   	#check home / away
$team1 = $competitors[1];

#print "team1 is $team1\n";
#print "team2 is $team2\n";


my @money = @{ $events[$i] -> {"sites"}   };    # the json variable sites is where moneline stuff is stored
my $money = @money; #size of @money
#print "$money\n";
#print "$money[0]\n";
#print "$money[1]\n";


if  ($money > 0)   {

for (my $i=0 ; $i<$money ; $i++)  {


my $site =   $money[$i] -> {"site_key"} ;     #notice no {   } around argument

#print "$site\n";


if ($site eq "draftkings") {     #sugarhouse, barstool, unibet, betrivers, draftkings



my @money2 = %{ $money[$i] -> {"odds"}  };
#print "@money\n";
#print "$money[0]\n";   
#print "$money[1]\n";

my @money3 = @{ $money2[1] };       #notice I can do this without the -> construct
#print "@money3\n";
$moneyline1 = $money3[1];
$moneyline2 = $money3[0];



print "$team1 $moneyline1 - $team2 $moneyline2\n";
print NCFMONEYLINEFILE ""; 

}
}
}
}

}






print "All done.\n";

close NCFMONEYLINEFILE;

