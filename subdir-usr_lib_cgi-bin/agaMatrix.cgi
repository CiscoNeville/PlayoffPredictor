#!/usr/bin/perl
##############
#
# agaMatrix.cgi
# Compute and display computer ratings by the Aga Playoff Predictor Matrix method.
# The Aga Playoff Predictor Matrix method uses ideas first proposed by Wes Colley in the Colley Matrix method
# with the addition of a single team to represent all Division 1 FCS schools
# Margin of Victory is added to the formula post 2021
#
##############


use strict;
use Math::MatrixReal;
use Data::Dumper;
use CGI;       #to pass ?useMarginOfVictory=yes in URL
use experimental 'smartmatch';     #gets rid of CLI warning


# set $seasonYear automatically based on current date. Can clobber if needed (below)
my $seasonYear = 1900 + (localtime)[5];
if ( ((localtime)[4] == 0) || ((localtime)[4] == 1) )  {$seasonYear = $seasonYear -1;}  #If in Jan or Feb, use last year for football season year
#$seasonYear = "2022";  #only for clobbering.  Not recommended.


my $fbsTeams = "/home/neville/cfbPlayoffPredictor/data/$seasonYear/fbsTeamNames.txt";
my $fbsIcons = "/home/neville/cfbPlayoffPredictor/data/teamIcons.txt";

#Read in FBS teams for this year
my (%team,%teamH) = ();   #initialize both hases in one line
open (FBSTEAMS, "<", $fbsTeams) or die "Can't open the file $fbsTeams";
while (my $line = <FBSTEAMS> )   {
    chomp ($line);
    my ($a, $b) = split(" => ", $line);   #this will result in $key holding the name of a team (1st thing before split)
    if ($a=='1AA') {$a='FCS'}   #is fbsTeamName file contains 1AA, switch it to FCS
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

my $mov;
my $movFactor;
my $alpha = 0;     #If alpha=0 this should simplify to original colley matrix.
my $useMarginOfVictory = CGI->new()->param('useMarginOfVictory');   
if ($useMarginOfVictory eq 'yes') {
   $useMarginOfVictory = 1;
   $alpha = -0.5;   #this is the weighting of MoV relative to W/L. At this point chosen as ta. Should weight in future based on math.  Expressed as a negative number. Cij = Cii = alpha.   Bi = -alpha.  
}

my $calculationType = 'most deserving';
my $useATSmValues = CGI->new()->param('useATSmValues'); 
if ($useATSmValues eq 'yes') {
   $useATSmValues = 1;
   $calculationType = 'ATS predictive';
}

my %seasonWins;
my %seasonLosses;
my %teamRating;

my $rankingNumber;

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


# Determine if a team is a FCS team and assign them to the team "FCS"
if (  ($aTeamName ~~ [values %team])    )  {
#that's great, away team is FBS. Do Nothing
}
else {   #otherwise it was a FCS team
$aTeamName = "FCS";
}


if (  ($hTeamName ~~ [values %team])    )  {    #Rarely happens -- an FBS team plays a game on the road against a FCS team
#that's great, home team is FBS. Do Nothing
}
else {   #otherwise it was a FCS team
$hTeamName = "FCS";
}


#print "aTeamName is $aTeamName\n";
#print "aTotal is $aTotal\n";
#print "hTeamName is $hTeamName\n";
#print "hTotal is $hTotal\n";


#populate the matrix with the data
$mov=($hTotal - $aTotal);


#tiered MoV AS PER PAPER WHICH WILL BE CANON
$movFactor = 0; #standard game
if (($mov>=1) && ($mov<=2)) {$movFactor=-0.2} elsif (($mov<=-1) && ($mov>=-2)) {$movFactor=+0.2} #close game type 1
if (($mov>=25) && ($mov<=34)) {$movFactor=+0.2} elsif (($mov<=-25) && ($mov>=-34)) {$movFactor=-0.2} #blowout type 1
if ($mov>=35) {$movFactor=+0.3} elsif ($mov<=-35) {$movFactor=-0.3} #blowout type 2


#ATS MoV values
if ($useATSmValues == 999) {    #changed from ==1 to ==999 becuase I don't want to use this
   if ($mov == 1) {$movFactor = -0.9}
   if ($mov == 2) {$movFactor = -0.8}
   if ($mov == 3)  {$movFactor = -0.5}
   if ($mov == 4)  {$movFactor = -0.4}
   if ($mov == 5)  {$movFactor = -0.35}
   if ($mov == 6)  {$movFactor = -0.3}
   if ($mov == 7)  {$movFactor = -0.2}
   if ($mov == 8)  {$movFactor = -0.1}
   if ($mov == 9)  {$movFactor = -0.05}
   if ($mov == 10)  {$movFactor = -0.04}
   if ($mov == 11)  {$movFactor = -0.03}
   if ($mov == 12)  {$movFactor = -0.02}
   if ($mov == 13)  {$movFactor = -0.01}
   if ($mov == 14)  {$movFactor = 0}
   if ($mov == 15)  {$movFactor = 0.02}
   if ($mov == 16)  {$movFactor = 0.05}
   if ($mov == 17)  {$movFactor = 0.1}
   if ($mov == 18)  {$movFactor = 0.15}
   if ($mov == 19)  {$movFactor = 0.16}
   if ($mov == 20)  {$movFactor = 0.17}
   if ($mov == 21)  {$movFactor = 0.19}
   if ($mov == 22)  {$movFactor = 0.2}
   if ($mov == 23)  {$movFactor = 0.21}
   if ($mov == 24)  {$movFactor = 0.22}
   if ($mov == 25)  {$movFactor = 0.3}
   if ($mov == 26)  {$movFactor = 0.32}
   if ($mov == 27)  {$movFactor = 0.34}
   if ($mov == 28)  {$movFactor = 0.36}
   if ($mov == 29)  {$movFactor = 0.38}
   if ($mov == 30)  {$movFactor = 0.4}
   if ($mov == 31)  {$movFactor = 0.42}
   if ($mov == 32)  {$movFactor = 0.44}
   if ($mov == 33)  {$movFactor = 0.46}
   if ($mov == 34)  {$movFactor = 0.48}
   if ($mov >= 35)  {$movFactor = 0.5}

   if ($mov == -1) {$movFactor = 0.9}
   if ($mov == -2) {$movFactor = 0.8}
   if ($mov == -3)  {$movFactor = 0.5}
   if ($mov == -4)  {$movFactor = 0.4}
   if ($mov == -5)  {$movFactor = 0.35}
   if ($mov == -6)  {$movFactor = 0.3}
   if ($mov == -7)  {$movFactor = 0.2}
   if ($mov == -8)  {$movFactor = 0.1}
   if ($mov == -9)  {$movFactor = 0.05}
   if ($mov == -10)  {$movFactor = 0.04}
   if ($mov == -11)  {$movFactor = 0.03}
   if ($mov == -12)  {$movFactor = 0.02}
   if ($mov == -13)  {$movFactor = 0.01}
   if ($mov == -14)  {$movFactor = 0}
   if ($mov == -15)  {$movFactor = -0.02}
   if ($mov == -16)  {$movFactor = -0.05}
   if ($mov == -17)  {$movFactor = -0.1}
   if ($mov == -18)  {$movFactor = -0.15}
   if ($mov == -19)  {$movFactor = -0.16}
   if ($mov == -20)  {$movFactor = -0.17}
   if ($mov == -21)  {$movFactor = -0.19}
   if ($mov == -22)  {$movFactor = -0.2}
   if ($mov == -23)  {$movFactor = -0.21}
   if ($mov == -24)  {$movFactor = -0.22}
   if ($mov == -25)  {$movFactor = -0.3}
   if ($mov == -26)  {$movFactor = -0.32}
   if ($mov == -27)  {$movFactor = -0.34}
   if ($mov == -28)  {$movFactor = -0.36}
   if ($mov == -29)  {$movFactor = -0.38}
   if ($mov == -30)  {$movFactor = -0.4}
   if ($mov == -31)  {$movFactor = -0.42}
   if ($mov == -32)  {$movFactor = -0.44}
   if ($mov == -33)  {$movFactor = -0.46}
   if ($mov == -34)  {$movFactor = -0.48}
   if ($mov <= -35)  {$movFactor = -0.5}
}





#add in elements of the matrix
my $x = $cM->element($teamH{$hTeamName},$teamH{$aTeamName}); 
$cM->assign($teamH{$hTeamName},$teamH{$aTeamName},$x-1+($alpha*$movFactor));
#my $z = $x-1+($alpha*$movFactor);
#print "h,a -> $z\n";

my $y = $cM->element($teamH{$aTeamName},$teamH{$hTeamName}); 
$cM->assign($teamH{$aTeamName},$teamH{$hTeamName},$y-1+(-$alpha*$movFactor));  
#print "a,h -> $z\n";


my $d1 = $cM->element($teamH{$hTeamName},$teamH{$hTeamName});   #diagonal
$cM->assign($teamH{$hTeamName},$teamH{$hTeamName},$d1+1+($alpha*$movFactor));
#print "h,h -> $z\n";


my $d2 = $cM->element($teamH{$aTeamName},$teamH{$aTeamName});
$cM->assign($teamH{$aTeamName},$teamH{$aTeamName},$d2+1+(-$alpha*$movFactor));
#print "a,a -> $z\n";

my $b1 = $bCV->element($teamH{$hTeamName},1);
my $b2 = $bCV->element($teamH{$aTeamName},1);

#Figure out which team won and give it to the @results array in 1-0 format
 if ($hTotal > $aTotal)  {    #home team won
$results[$k] = "$hTeamName 1-0 $aTeamName";

$seasonWins{$hTeamName} = $seasonWins{$hTeamName} + 1;
$seasonLosses{$aTeamName}++;


$bCV->assign($teamH{$hTeamName},1,$b1+0.5);
$bCV->assign($teamH{$aTeamName},1,$b2-0.5);
}
else {    #away team won. No ties anymore...
$results[$k] = "$aTeamName 1-0 $hTeamName";
$seasonWins{$aTeamName}++;
$seasonLosses{$hTeamName}++;

$bCV->assign($teamH{$aTeamName},1,$b2+0.5);
$bCV->assign($teamH{$hTeamName},1,$b1-0.5);


}
$k++;
}
close NCFSCORESFILE;



#solve for rCV
my $dim;
my $base;
my $LRM;  # this is the LR_Matrix defined in MatrixReal and returned by the method decompose_LR

$LRM = $cM->decompose_LR();

if ( ($dim,$rCV,$base) =  $LRM->solve_LR($bCV) ) {
#print "great, it solved the matrix\n";
}
else {
print "crap, there was no solution to the Matrix  (This should not have happened\n)";
}


#print out the ratings and records for each team
 print "Content-type: text/html\n\n";
 print " <html> <head> <title>PlayoffPredictor.com formula based most deserving rankings and ratings of teams</title>";
 print ' <meta name="viewport" content="width=device-width, initial-scale=1"> ';
 print " <link type=\"text\/css\" rel=\"stylesheet\" href=\"\/style.css\" \/> <\/head>";

#1st split the ratings out into an array with an index for each team
my @output;

for (my $i = 1; $i<$#teams+2; $i++ ) {       #logic
#get the team rating in human readable form
my $rCVofI = $rCV->row($i);
$rCVofI =~ /\[(.+?)\]/;
$rCVofI = $1;
$rCVofI = sprintf("%.3f", $rCVofI);   #%.3f does 3 digits of precision, both pos and neg numbers
$teamRating{$team{$i}}=$rCVofI;


my $teamDisplayWidth='"13%"';   #add width=$teamDisplayWidth after valign middle

#print "$team{$i} record is $seasonWins{$team{$i}} and $seasonLosses{$team{$i}}  - rating is $teamRating{$team{$i}} <br>\n";
$output[$i] = "$teamRating{$team{$i}}\:\:<td> <img src=\"https://sports.cbsimg.net/images/collegefootball/logos/50x50/$teamIcon{$team{$i}}.png\" valign=\"middle\" width=$teamDisplayWidth>  \<a href=\"\/analyzeSchedule.php?team1=$team{$i}&app=$isCalledInApp\">$team{$i}<\/a> </td> <td>$teamRating{$team{$i}}</td>  <td>$seasonWins{$team{$i}} - $seasonLosses{$team{$i}}</td></tr>";  #put the rating in front so I can sort on it
}

shift @output;  #get rid of empty 0th element since $i going from 1 to $#teams

sub compare_sort
{
   if($a < $b)
   {
      return -1;
   }
   elsif($a == $b)  #numerical same rating, need to alphabetize on team name now
   {
       if(substr($a,82,10) gt substr($b,82,10)){    #this is just a hack to alphabatize based on filename of teamIcon.  Regex on actual team name would be better. Feeling lazy.
         return -1;
       } else {
         return 1;
       }
   }
   else
   {
      return 1;                       
   }
}

my @sortedOutput = sort compare_sort @output;   #reverse sort is needed to get teams with identical ratings in alphabetical order
my @sortedOutput2;

foreach (@sortedOutput) {
 my @separated = split('::', $_);
 unshift @sortedOutput2, $separated[1];   #reverse sort order, remove leading rating
}

 
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

#print out the Google analytics tag
print '<!-- Google tag (gtag.js) --><script async src="https://www.googletagmanager.com/gtag/js?id=G-988NB7TH39"></script><script>  window.dataLayer = window.dataLayer || [];  function gtag(){dataLayer.push(arguments);}  gtag(\'js\', new Date());  gtag(\'config\', \'G-988NB7TH39\');</script>';


#print correct heading for app / not app experience and open the <body> tag
if ($isCalledInApp ne 'true') {   #called from standard web browser
 print " <body> <h1>Calculated $calculationType team ratings & rankings</h1> <h2>Aga elo method - Last update of scores $ncfScoresFileModTime CST</h2><p></p>";
}
else {  #called from app
print " <body>  <h4>Last update of scores $ncfScoresFileModTime CST</h4><p></p>";
}

if ($useMarginOfVictory == 1) {
# print "<p>Margin of victory <em><b>has</b></em> been considered in these rankings | <a href=\"/cgi-bin/agaMatrix.cgi?useMarginOfVictory=no\">Recompute without margin of victory</a></p>";
}
else {
 print "<p>Margin of victory <em><b>has not</b></em> been considered in these rankings  | <a href=\"/cgi-bin/agaMatrix.cgi?useMarginOfVictory=yes\">Recompute with margin of victory</a>   </p> ";
}

my $lastTeamRating;

#print out all the teams
print "<table  cellpadding=\"4px\" cellspacing=\"0px\" sortable>";
my $i=1;

print "<tr bgcolor=\"#FFFF00\"><th>Rank</th><th>Team Name</th><th>Rating</th><th>Record</th>";

foreach (@sortedOutput2) {
  $rankingNumber = $i;

  #handle ties in rankings as ties and don't display them with a lower numerical ranking
 my @separated2 = split('<td>', $_);
if (@separated2[2] == $lastTeamRating) {
   $rankingNumber = '';
}

if ($i % 2 == 1) {   #logic to color every other output line grey then white (is it divisible by 2 with remainer of 1)
  print "<tr bgcolor=\"#D0D0D0\"><td>$rankingNumber.</td>   $_";
  } else {
  print "<tr bgcolor=\"#FFFFFF\"><td>$rankingNumber.</td>   $_";
   }
    
 $i++;  
 $lastTeamRating = $separated2[2];
}

print "</table>";

print "<br><br><br>";

print "</body>";
print "</HTML>";