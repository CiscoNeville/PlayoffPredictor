#!/usr/bin/perl
##############
#
# agaMatrixApp.cgi
# Determine the FBS playoff committee bias per team by Implement the 
# Aga Playoff Predictor Matrix method.
# This script is for use by the playoffPredictor App -- elimiates banner graphic and nav bar
#
##############



use strict;
use Math::MatrixReal;
#use Number::Format;
use Data::Dumper;


# set $seasonYear automatically based on current date. Can clobber if needed (below)
my $seasonYear = 1900 + (localtime)[5];
if (localtime[4] lt 3) {$seasonYear = $seasonYear -1;}  #If in Jan or Feb, use last year for football season year
#$seasonYear = "2019";


my $fbsTeams = "/home/neville/cfbPlayoffPredictor/data/$seasonYear/fbsTeamNames.txt";
my $fbsIcons = "/home/neville/cfbPlayoffPredictor/data/teamIcons.txt";

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





my $ncfScoresFile = "/home/neville/cfbPlayoffPredictor/data/current/ncfScoresFile.txt";
my $ncfScheduleFile = "/home/neville/cfbPlayoffPredictor/data/current/ncfScheduleFile.txt";

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
my $useMarginOfVictory = 0;   #if enabled uses >17 and >33 to give extra wins / losses for ratings
my %seasonWins;
my %seasonLosses;

my @teams=keys(%team);
my $numberOfTeams = $#teams + 1;  #$#teams starts counting at zero

my $rematch = 0;

#Read in team to icon mapping
my %teamIcon; #declare it here and then fill in the College name -> icon name mapping below.
open (FBSICONS, "<", $fbsIcons) or die "Can't open the file $fbsIcons";
while (my $line = <FBSICONS> )   {
    chomp ($line);
   unless (substr($line,0,1) eq '#') {  #Do nothing. Ignore comment lines in input file
    my ($a, $b) = split(" => ", $line);   #this will result in $key holding the name of a team (1st thing before split)
    $teamIcon{$a} = $b;
   }
}     #at the conclusion of this block $teamIcon{"Wyoming"} will be "Wyo" 



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


#read input data from a file.
my $scoreInput;
my $k;      #there is probably a better way to do this
open (NCFSCORESFILE, "<$ncfScoresFile") or die "$! error trying to open";

my $ncfScoresFileModTime = (stat($ncfScoresFile))[9];     #get last date/time ncfScoreFile updated
$ncfScoresFileModTime = localtime($ncfScoresFileModTime);  #human readable

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

#Figure out which team won and give it to the @results array in 1-0 format
 if ($hTotal > $aTotal)  {    #home team won
$results[$k] = "$hTeamName 1-0 $aTeamName";
$seasonWins{$hTeamName}++;
$seasonLosses{$aTeamName}++;

if ($useMarginOfVictory == 1) {
#If home team won by 17 or more, give them another win, and the loser another loss.  This is temporary, until I can figure out best way to map scores (or 99.9% win times) to the matrix
 if ($hTotal >= ($aTotal+17))  {    #home team won
$k++;
$results[$k] = "$hTeamName 1-0 $aTeamName";
}

#If home team won by 33 or more, give them yet another win, and the loser another loss
 if ($hTotal >= ($aTotal+33))  {    #home team won
$k++;
$results[$k] = "$hTeamName 1-0 $aTeamName";
}}


}
else {    #away team won. No ties anymore...
$results[$k] = "$aTeamName 1-0 $hTeamName";
$seasonWins{$aTeamName}++;
$seasonLosses{$hTeamName}++;

if ($useMarginOfVictory == 1) {
#If away team won by 17 or more, give them another win, and the loser another loss
 if ($aTotal >= ($hTotal+17))  {    #away team gets win credit
$k++;
$results[$k] = "$aTeamName 1-0 $hTeamName";
}

#If away team won by 33 or more, give them yet another win, and the loser another loss
 if ($aTotal >= ($hTotal+33))  {    #away team gets win credit
$k++;
$results[$k] = "$aTeamName 1-0 $hTeamName";
}}


}
$k++;

}

close NCFSCORESFILE;









#populate the matrix with the data

#create zeros for initial wins and losses for every team
for (my $i = 1; $i<$numberOfTeams+1; $i++ ) {
 $teamWins[$i]=0;
 $teamLosses[$i]=0;
}



#Find an individual game winner
for (my $i = 0; $i<$#results+1; $i++ ) {
($resultsWinner, $resultsLoser) = (split / 1-0 /, $results[$i]);


#print "resultsWinner is  $resultsWinner\n";
#print "resultsLoser is  $resultsLoser\n\n";


#now, populate that one win and loss into the cM,
for (my $j = 1; $j<$#teams+3; $j++ ) {           #have not figured out why +3 here...
 if ($team{$j} eq $resultsWinner)  {  
$teamNumberWinner = $j;    
   $teamWins[$j]++;
   
}
 if ($team{$j} eq $resultsLoser)  {
  $teamNumberLoser = $j;
   $teamLosses[$j]++;
}
}

#print "teamNumberWinner is $teamNumberWinner\n\n";
#print "teamNumberLoser is $teamNumberLoser\n\n";

$rematch = $cM->element($teamNumberWinner,$teamNumberLoser);   #the matrix is symmetrical, so i only have to do this once

   $cM->assign($teamNumberWinner,$teamNumberLoser,-1 + $rematch);     #for 1st rematch   -2 inseted in the array
   $cM->assign($teamNumberLoser,$teamNumberWinner,-1 + $rematch);     
 
}


#assign the diagonal row of the CM
for (my $i = 1; $i<$#teams+2; $i++ ) {        # dont understand why +2, shuoldnt it be +1
 $cM->assign($i,$i,2+$teamWins[$i]+$teamLosses[$i]);     #the diagonal matrix entries correspond to total games played +2
}




#have to caputure each teams total wins and losses to populate bCV
#see if I did it right above



#Print out the record of each team at this point, if you want
#for (my $i = 1; $i<$numberOfTeams+1; $i++ ) {
#print "$team{$i} record is $teamWins[$i] and $teamLosses[$i]\n";
#}






#input for bCV.   
for (my $i = 1; $i<$#teams+2; $i++ ) {        # dont understand why +2, shuoldnt it be +1
 my $bi=($teamWins[$i]-$teamLosses[$i]);
 $bi=$bi/2;
 $bi=$bi +1;
 $bCV->assign($i,1,$bi);     #bi = 1 + (nwi-nli)/2
}



#print "\n\n";
#print "colley matrix is \n";
#print "$cM\n\n\n";


#print "b is \n";
#print "$bCV\n\n";






#solve for rCV
my $dim;
my $base;
my $LRM;  # this is the LR_Matrix defined in MatrixReal and returned by the method decompose_LR


$LRM = $cM->decompose_LR();


if ( ($dim,$rCV,$base) =  $LRM->solve_LR($bCV) ) {
#print "great, it solved the matrix\n";
#Note, I don't actually have to iterate. The Matrix solution takes care of that for me !

#print "r is \n";
#print "$rCV\n\n";

}
else {
print "crap, there was no solution to the Colley Matrix  (This should not have happened\n)";
die;
}


#print out the team ratings
#1st split the ratings out into an array with an index for each team

my @output;

for (my $i = 1; $i<$#teams+2; $i++ ) {        # dont understand why +2, shuoldnt it be +1
 
#get the team rating in human readable form
my $rCVofI = $rCV->row($i);
$rCVofI =~ /\[(.+?)\]/;
$rCVofI = $1;
$rCVofI = sprintf("%.10g", $rCVofI);
#$rCVofI = format_number($rCVofI, 4);
$rCVofI = substr $rCVofI, 0, 5;     # truncate out anything under thousandnths. note, this casues a loss of precision. display only.
if ($rCVofI eq 0.5)  {     #get 0.5 entries in 0.500 format for later sorting  
 $rCVofI = "0.500";
}

#print "$team{$i} record is $teamWins[$i] and $teamLosses[$i]  - rating is $rCVofI \n";

$output[$i] = "$rCVofI\:\:<td> <img src=\"http://sports.cbsimg.net/images/collegefootball/logos/50x50/$teamIcon{$team{$i}}.png\" valign=\"middle\" width=\"13%\">  \<a href=\"\/analyzeScheduleApp.php?team1=$team{$i}\">$team{$i}<\/a> </td> <td>$rCVofI</td>  <td>$seasonWins{$team{$i}} - $seasonLosses{$team{$i}}</td></tr>";  #put the rating in front so I can sort on it


}


#my @sortedOutput;
#$Data::Dumper::Sortkeys = sub { [reverse sort keys %{$_[0]}] };   #sort in reverse order - best teams 1st
#print Dumper(@output);



my @sortedOutput = sort @output;
my @sortedOutput1;
my @sortedOutput2;



#print "\n\n\n";
#print "@sortedOutput\n";   # gives this weird 60 before it prints the array



foreach (@sortedOutput) {
 my @separated = split('::', $_);
 push @sortedOutput1, $separated[1];    #everything after the :
}




for (my $i=1; $i<$#teams+2; $i++ ) {        # dont understand why +2, shuoldnt it be +1
 
$sortedOutput2[$i] = $sortedOutput1[$#teams+2-$i];
chomp $sortedOutput2[$i];

}


#print out the top 25 teams

 print "Content-type: text/html\n\n";
 print " <html> <head> <title>PlayoffPredictor.com formula based true rankings and ratings of teams</title>";
 print " <link type=\"text\/css\" rel=\"stylesheet\" href=\"\/style.css\" \/> <\/head>";
 
print "<h3>This version is 6 years old. Please upgrade to the new version of this app on the App Store titled \"<a href=\"https://apps.apple.com/us/app/cfb-playoff-predictor/id6462927364\">College Football Playoff Calc</a>\"</h3><p></p> ";
 
print " <body> <h2>Aga matrix method</h2><h4>Last update of scores $ncfScoresFileModTime CST</h4><p></p>";
#print out all the teams
print "<table  cellpadding=\"4px\" cellspacing=\"0px\" sortable>";
my $i=0;
#$sortedOutput2[$0] = "<tr><th>Rank</th><th>Team Name</th><th>Rating</th><th>Record</th>";
foreach (@sortedOutput2) {
 if ($i != 0) {
  
if ($i % 2 == 1) {
  print "<tr bgcolor=\"#D0D0D0\"><td>$i.</td>   $_";
  } else {
   print "<tr bgcolor=\"#FFFFFF\"><td>$i.</td>   $_";
   }
    
 $i++;

}  else {
print "<tr bgcolor=\"#FFFF00\"><th>Rank</th><th>Team Name</th><th>Rating</th><th>Record</th>";
$i++; 
 }
}
print "</table>";

if ($useMarginOfVictory == 0) {print "Margin of victory <b>has not</b> been used in this calculation<br>";}
else {print "Margin of victory <b>has</b> been used in this calculation<br>";}



print "</body>";

#print "@sortedOutput2\n";   # gives this weird 60 before it prints the array


#print "$sortedOutput2[0]\n";
#print "$sortedOutput2[1]\n";
#print "$sortedOutput2[2]\n";
#print "$sortedOutput2[3]\n";
#print "$sortedOutput2[4]\n";






