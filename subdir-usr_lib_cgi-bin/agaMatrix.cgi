#!/usr/bin/perl
##############
#
# agaMatrix.cgi
# Compute and display computer ratings by the Aga Playoff Predictor Matrix method.
# The Aga Playoff Predictor Matrix method uses ideas first proposed by Wes Colley in the Colley Matrix method
# with the addition of a single team to represent all 1AA schools
# Margin of Victory is added to the formula post 2021
#
##############



use strict;
use Math::MatrixReal;
#use Number::Format;
use Data::Dumper;
use CGI;       #to pass ?useMarginOfVictory=yes in URL
use experimental 'smartmatch';     #gets rid of CLI warning


# set $seasonYear automatically based on current date. Can clobber if needed (below)
my $seasonYear = 1900 + (localtime)[5];
if ( ((localtime)[4] == 0) || ((localtime)[4] == 1) )  {$seasonYear = $seasonYear -1;}  #If in Jan or Feb, use last year for football season year
#$seasonYear = "2000";


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

my @teams=keys(%team);
my $numberOfTeams = $#teams + 1;  #$#teams starts counting at zero


my $mov;
my $movFactor;
my $alpha = 0;     #If alpha=0 this should simplify to original colley matrix.
my $useMarginOfVictory = CGI->new()->param('useMarginOfVictory');   
if ($useMarginOfVictory eq 'yes') {
   $useMarginOfVictory = 1;
   $alpha = -0.333;   #this is the weighting of MoV relative to W/L. At this point chosen as ta. Should weight in future based on math.  Expressed as a negative number. Cij = Cii = alpha.   Bi = -alpha.  
}
my %seasonWins;
my %seasonLosses;

#see if this script is being called in the iPhone app.  If so, will need to format output differently
my $isCalledInApp = CGI->new()->param('app');   #look in the URL for ?app=   iPhone browsing will set it to 'true'


#Read in team to icon mapping
my %teamIcon; #declare it here and then fill in the College name -> icon name mapping below.
open (FBSICONS, "<", $fbsIcons) or die "Can't open the file $fbsIcons";
while (my $line = <FBSICONS> )   {
    chomp ($line);
   unless (substr($line,0,1) eq '#') {  #Do nothing. Ignore comment lines in input file
    my ($a, $b) = split(" => ", $line);   
    $teamIcon{$a} = $b;
   }
}     #at the conclusion of this block $teamIcon{"Wyoming"} will be "Wyo" 


#create blank matricies
$cM = new Math::MatrixReal($numberOfTeams,$numberOfTeams);
$rCV = new Math::MatrixReal($numberOfTeams,1);    #column vector is this notation. lots of rows, 1 column
$bCV = new Math::MatrixReal($numberOfTeams,1);

#print "colley matrix is  $cM\n\n";
#print "r is $rCV\n\n";
#print "b is $bCV\n\n";

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


if (  ($hTeamName ~~ [values %team])    )  {    #Rarely happens -- an FBS team plays a game on the road against a FCS team
#that's great, home team is FBS. Do Nothing
}
else {   #otherwise it was a 1AA team
$hTeamName = "1AA";
}


#print "aTeamName is $aTeamName\n";
#print "aTotal is $aTotal\n";
#print "hTeamName is $hTeamName\n";
#print "hTotal is $hTotal\n\n";


#populate the matrix with the data
$mov=abs($hTotal - $aTotal);


#$movFactor=((1/80)*$mov)-.0125;                #simple linear MoV
#$movFactor=((atan2(.1*$mov-1.7,1))/2.53)+.4001;   #atan MoV
#$movFactor=log($mov)/log(80);                   #log MoV, base 80

#tiered MoV
#if ($mov==1) {$movFactor=0;}
#if ($mov>=1 && $mov<=16) {$movFactor=0;}
#if ($mov>=17 && $mov<=31) {$movFactor=0.5;}
#if ($mov>=32) {$movFactor=1;}
#if ($mov>=9 && $mov<=16) {$movFactor=0.03125*($mov-9)+.25;}
#if ($mov>=17) {$movFactor=((atan2(.08*$mov-2.6,1))/4.8)+.7;}
$movFactor=0.01*($mov-1);   #simple linear MoV



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
print "crap, there was no solution to the Matrix  (This should not have happened\n)";
}


#print out the team ratings
#1st split the ratings out into an array with an index for each team

my @output;

for (my $i = 1; $i<$#teams+2; $i++ ) {        # dont understand why +2, shuoldnt it be +1 
#get the team rating in human readable form
my $rCVofI = $rCV->row($i);
$rCVofI =~ /\[(.+?)\]/;
$rCVofI = $1;
$rCVofI = sprintf("%.3f", $rCVofI);   #%.3f does 3 digits of precision, both pos and neg numbers

#$rCVofI = substr $rCVofI, 0, 5;     # truncate out anything under thousandnths. note, this casues a loss of precision. display only.
#if ($rCVofI eq 0.5)  {     #get 0.5 entries in 0.500 format for later sorting  
# $rCVofI = "0.500";
#}
#print "$team{$i} record is $teamWins[$i] and $teamLosses[$i]  - rating is $rCVofI \n";
$output[$i] = "$rCVofI\:\:<td> <img src=\"http://sports.cbsimg.net/images/collegefootball/logos/50x50/$teamIcon{$team{$i}}.png\" valign=\"middle\" width=\"13%\">  \<a href=\"\/analyzeSchedule.php?team1=$team{$i}\">$team{$i}<\/a> </td> <td>$rCVofI</td>  <td>$seasonWins{$team{$i}} - $seasonLosses{$team{$i}}</td></tr>";  #put the rating in front so I can sort on it
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



#print out the ratings and records teams
 print "Content-type: text/html\n\n";
 print " <html> <head> <title>PlayoffPredictor.com formula based true rankings and ratings of teams</title>";
 print " <link type=\"text\/css\" rel=\"stylesheet\" href=\"\/style.css\" \/> <\/head>";
 
#Print the banner and menu if not called from app
if ($isCalledInApp ne 'true') {
my $data;
my $banner = '/var/www/ppDocs/banner-and-menu.html';
open (BANNER, "<$banner") or die "$! error trying to open";  
for $data (<BANNER>) {
print "$data";
}
close BANNER;
}


#print correct heading for app / not app experience and open the <body> tag
if ($isCalledInApp ne 'true') {
 print " <body> <h1>Calculated true team rankings</h1> <h2>Aga elo method - Last update of scores $ncfScoresFileModTime CST</h2><p></p>";
}
else {
 print " <body> <h3>Calculated true team rankings<br> from football.playoffpredictor.com </h3> <h4>Last update of scores $ncfScoresFileModTime CST</h4><p></p>";
}

if ($useMarginOfVictory == 1) {
 print "<p>Margin of victory <em><b>has</b></em> been considered in these rankings | <a href=\"/cgi-bin/agaMatrix.cgi?useMarginOfVictory=no\">Recompute without margin of victory</a></p>";
}
else {
 print "<p>Margin of victory <em><b>has not</b></em> been considered in these rankings  | <a href=\"/cgi-bin/agaMatrix.cgi?useMarginOfVictory=yes\">Recompute with margin of victory</a>   </p> ";
}




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

print "<br><br><br>";

print "</body>";
print "</HTML>";
