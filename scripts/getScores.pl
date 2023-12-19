#!/usr/bin/perl
##############
#
# Build the current FBS results and future schedule files by scraping ESPN ncf scoreboard for a whole season
#
# Input files: none
#
# Output files:
#   ncfScoresFile - contains results of games played up to that point in the season
#   ncfScheduleFile - contains all upcoming scheduled games for the rest of the season
# 
##############

#Use these variable locations when processing on production debian server
#my $ncfScoresFile = "/home/neville/cfbPlayoffPredictor/data/current/ncfScoresFile.txt";
#my $ncfScheduleFile = "/home/neville/cfbPlayoffPredictor/data/current/ncfScheduleFile.txt";


#Use these variable locations when processing on local macbook
my $ncfScoresFile = "/Users/neaga/Dropbox/documents/playoff predictor/github_repo/PlayoffPredictor/data/current/ncfScoresFile.txt";
my $ncfScheduleFile = "/Users/neaga/Dropbox/documents/playoff predictor/github_repo/PlayoffPredictor/data/current/ncfScheduleFile.txt";


#############################################

#Hold temporary score files.  copy at end of script so atomic changeover to new data
my $tempScoresFile = "/tmp/tempScoresFile.txt";
my $tempScheduleFile = "/tmp/tempScheduleFile.txt";



use utf8;
use strict;
use warnings;
use HTML::Parser;
use Data::Dumper;
use WWW::Mechanize;
use HTML::TokeParser;
use File::Copy;
use JSON;
use feature qw/ say /;

my $confId;    #80 is all FBS
my $seasonType;   # 2 = regular season;  3 = bowls  
my $weekNumber;    # goes from 1 to 16
my $gameStatus;
my $aTeamName;
my $aTotal;
my $hTeamName;
my $hTotal;
my %team1;
my %team2;
my $score1;
my $score2;
my %status;
my $gameScoreEntered =  0;
my $gameScheduleEntered = 0;

# set $seasonYear automatically based on current date. Can clobber if needed (below)
my $seasonYear = 1900 + (localtime)[5];
if ( ((localtime)[4] == 0) || ((localtime)[4] == 1) )  {$seasonYear = $seasonYear -1;}  #If in Jan or Feb, use last year for football season year
#$seasonYear = "2023";





#subroutine scrapeScores
sub scrapeScoresESPN {
my (@ops) = @_;
$confId = $ops[0];
$seasonYear = $ops[1];
$seasonType = $ops[2];
$weekNumber = $ops[3];


my $baseurl ="http://scores.espn.go.com/college-football/scoreboard/_/group/$confId/year/$seasonYear/seasontype/$seasonType/week/$weekNumber";
# new url syntax is restful -- http://scores.espn.go.com/college-football/scoreboard/_/group/80/year/2015/seasontype/2/week/1
# but the json response is buried deep inside and have to use regex to pull it out

my $browser = WWW::Mechanize->new();
my $html;
$browser->get($baseurl);
die $browser->response->status_line unless $browser->success;
$html = $browser->content;

#open (SCORES1, ">/tmp/scores.html") or die "$! error trying to overwrite";
#print SCORES1 "$html\n";


#$html =~ /(.+)<\/script><script>window.espn.scoreboardData 	= (.+?)\;window.espn.scoreboardSettings/sg;        #this is where to find the json prior to 2021-11-18
$html =~ /\"evts\"\:(.+?)\,\"crntSzn/sg;        #this is where to find the json after to 2021-11-19
my $json = $1;  #this should be all the espn json
$json = '{ "evts":' . $json;      #put the key that starts the json string back in the beginning
$json = $json . '}' ;             #balance and end the json


use open qw/ :std :encoding(utf-8) /;
$json =~ s/[^a-zA-Z0-9,\[\]\{\}\"\'\\\/\-\:\ \&\(\)]//g;    #have to get rid of some special characters non UTF8 in the espn html   \[\]\{\}\"\'\\\/\-\:\ \(\)\&\é  SJSU is problem



#open (SCORES2, ">/tmp/scores.json") or die "$! error trying to overwrite";
#print SCORES2 "$json\n";
#close SCORES2;


#my ($k, $v);

#$json = utf8::downgrade($json);   #ESPN propduces some weird characters 
my $data = decode_json $json;


#print Dumper $data;
my $dumpedData = Dumper $data;
#open (SCORES3, ">/tmp/scores3.txt") or die "$! error trying to overwrite";
#print SCORES3 "$dumpedData";
#close SCORES3;


my @evts = @{ $data -> {"evts"}    };			 #understand that evts points to a [] , so use a @array
my $i1=0;  #this is a bad, bad hack to get things done today. later figure out why it prints 2x for each game (foreach loop), and get it to do it only once
foreach $a (@evts)  {              #this says for all the games that are defined for any week iterate that many times, not more (or less). Need this because defined () is depricated. 100 games is the most for a week. typical week 1 has 89 games, typical week 15 has 1-8
$i1++;
#print '$a is '."$a \n";    # $a is a HASH(0x1234)
#my %aDeref = %{$a};
#while ( ($k,$v) = each %aDeref ) {
#    print "$k => $v\n";
#}
#print "end of hash \n";


#look in the competitors array for the data you want
my @competitors = @{ $a -> {"competitors"}    };
my $i2=0;
foreach $b (@competitors)  {              #this foreach will execute exactly 2 times since there are 2 "competitors" subkeys (0 and 1) per evts[n] key, if that makes sense
$i2++;
#print '$b is '."$b \n";    # $b is a HASH
#my %bDeref = %{$b};
#while ( ($k,$v) = each %bDeref ) {
#    print "$k => $v\n";
#}

my $team2 = $competitors[0] -> {"location"}     ;                   	#im doing team2 here because espn changed their order and I want $competitors[0] is the away team
my $team1 = $competitors[1] -> {"location"}     ;

#if present, change the accent in San José State to just San Jose State   
$team1 =~ s/Jos State/Jose State/g;
$team2 =~ s/Jos State/Jose State/g;
#if present, change the accent in Hawai'i to just Hawaii
if ($team1 eq 'Hawai\'i') {$team1 = 'Hawaii';}    #used to be $team1 =~ s/Hawai&#39;i/Hawaii/g; 
if ($team2 eq 'Hawai\'i') {$team2 = 'Hawaii';}

#pull game status to see if final, scheduled, postponed, or delayed 
my %status = %{ $a -> {"status"}  };   #understand that status points to a {} , so use a %hash (?)
my $gameStatus = $status{'description'};       #use $status{'description'} as opposed to $status{'detail'}. $status{'description'} just says 'Final', where $status{'detail'} says Final/2OT


#change $weekNumber from 1 to 17 if this is pulled for the bowls
if ( $seasonType eq 3 ) { $weekNumber = 17;}



#verify this is not a TBD vs TBD game, don't bother logging anything if it is.  This is for bowl games before they are announced.
if (($team1 ne 'TBD')  && ($i2 == 1)) {

if ($gameStatus eq 'Final') {  
$score2 = $competitors[0] -> {"score"}    ;  
$score1 = $competitors[1] -> {"score"}    ;


print "week $weekNumber: $gameStatus : $team1 $score1 - $team2 $score2\n";
print TEMP_SCORESFILE "week $weekNumber: $gameStatus : $team1 $score1 - $team2 $score2\n";   #do not use 1-0 format, depricated
$gameScoreEntered++;
}


elsif (($gameStatus eq 'Canceled') || ( $gameStatus eq 'Postponed')) { #make sure the game is not cancelled / postponed   
print "Canceled|Postponed game. week $weekNumber: $gameStatus : $team1 vs $team2\n";
}


else {  #game still to be played out

print "week $weekNumber: $gameStatus : $team1 vs $team2\n";    #$type{'description'} should say "Scheduled"
print TEMP_SCHEDULEFILE "week $weekNumber:$team1 - $team2\n"; 
$gameScheduleEntered++; 
}
}  #end of if TBD vs TBD check

}  #end of foreach $b (@competitors)  { 

}  #end of foreach $a (@evts)  { 

}  #end of sub scrapeScoresESPN







open (TEMP_SCORESFILE, ">$tempScoresFile") or die "$! error trying to overwrite";
open (TEMP_SCHEDULEFILE, ">$tempScheduleFile") or die "$! error trying to overwrite";




#get regular season games
for (my $k = 1; $k<=16; $k++ ) {     #15 is the final regular season week - specifically the army-navy week post 2015
print "getting $seasonYear week $k\n";
scrapeScoresESPN (80, $seasonYear, 2, $k);
}


#get bowl games 
print "getting $seasonYear bowl games\n";  # (season 3 week 1 = my week 17
scrapeScoresESPN (80,$seasonYear,3,1);



close TEMP_SCORESFILE;
close TEMP_SCHEDULEFILE;


#Copy temp files to real files
copy($tempScoresFile, $ncfScoresFile) or die "Copy failed: $!";
copy($tempScheduleFile, $ncfScheduleFile) or die "Copy failed: $!";



print "Output writtten to $ncfScoresFile   ($gameScoreEntered rows)\n";
print "Output writtten to $ncfScheduleFile ($gameScheduleEntered rows)\n";

print "All done.\n";