#!/usr/bin/perl
##############
#
# eloSingleGame.cgi
# pull ratings of 2 teams and use ELO to give each win probabilities
#
##############


use strict;
use Math::MatrixReal;
#use Number::Format;
use Data::Dumper;

my $fbsTeams = "/home/neville/cfbPlayoffPredictor/data/2019/fbsTeamNames.txt";
my $fbsIcons = "/home/neville/cfbPlayoffPredictor/data/teamIcons.txt";
my $currentCalculatedRatingsFile = "/home/neville/cfbPlayoffPredictor/data/current/CurrentCalculatedRatings.txt";
my $averagedCommitteeBiasFile = "/home/neville/cfbPlayoffPredictor/data/current/CurrentAveragedCommitteeBiasFile.txt";
my (@teamWins, @teamLosses);
my ($teamInput, $teamName, $teamRating, $MoVRecord);
my $bias;
my (%rating,%roundedRating,%biasedRating,%roundedBiasedRating);
my $ratingDifference;

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



open (NCFRATINGSFILE, "<$currentCalculatedRatingsFile") or die "$! error trying to open";
for $teamInput (<NCFRATINGSFILE>) {
($teamName, $teamRating, $MoVRecord) = (split /:/, $teamInput);
$rating{$teamName} = $teamRating;
$roundedRating{$teamName} = sprintf "%.3f", $rating{$teamName};
}
close NCFRATINGSFILE;

 
open (NCFBIASFILE, "<$averagedCommitteeBiasFile") or die "$! error trying to open";
for $teamInput (<NCFBIASFILE>) {
($teamName, $bias) = (split / averageRatingBiasThroughWeek.+? is /, $teamInput);
$biasedRating{$teamName} = $rating{$teamName} + $bias;
$roundedBiasedRating{$teamName} = sprintf "%.3f", $biasedRating{$teamName};
}
close NCFBIASFILE;


#see if this script is being called in the iPhone app.  If so, will need to format output differently
use CGI;
#my $isCalledInApp = CGI->new()->param('app');   #look in the URL for ?app=   iPhone browsing will set it to 'true'


my $q = CGI -> new;
my $team1 = $q->param("team1");
my $team2 = $q->param("team2");
my $week= $q->param("weekNumber");
my $useBiased = $q->param("useBiased");
my $useHomeFieldAdvantage = $q->param("useHomeFieldAdvantage");
my $homeFieldAdvantageRatingBoost = 0.04;


my $isCalledInApp = $q->param("callingFromApp");


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
 print " <body> <h1>Calculated win probabilities</h1> <p></p>";
}
else {
 print " <body> <h3>Calculated true team rankings<br> from football.playoffpredictor.com </h3><p></p>";
}






#calculate the probabilities



if ($useBiased eq "on") {
 print "<i>Ratings have been adjusted to the playoff committee bias </i> <br>";
 #$roundedRating{$team1} = $roundedBiasedRating{$team1};   #just clobbering. I know this is terrible
 #$roundedRating{$team2} = $roundedBiasedRating{$team2}; 
 $rating{$team1} = $biasedRating{$team1};
 $rating{$team2} = $biasedRating{$team2};
 $roundedRating{$team1} = sprintf("%.3f", $rating{$team1});
 $roundedRating{$team2} = sprintf("%.3f", $rating{$team2});



} else {
 print "<i>Ratings have not been adjusted for playoff committee bias. Using pure computer team ratings </i><br>";
}

if ($useHomeFieldAdvantage eq "on") {
    $rating{$team2} = $rating{$team2} + 0.040 ;
    $roundedRating{$team2} = sprintf("%.3f", $rating{$team2});
    print "<i>An extra 0.04 rating points have been given to $team{$teamH{$team2}} for home field advantage </i><br><br>";
}


$ratingDifference = $rating{$team2} - $rating{$team1};

print "<br>";


print "$team{$teamH{$team1}}  rating is $roundedRating{$team1} <br>";
print "$team{$teamH{$team2}}  rating is $roundedRating{$team2} <br>";
print "<br>";


print "<br>";


my $divisor = 1;  #

my $probA = 1/(1+(1000**(($rating{$team2}-$rating{$team1})/$divisor)));
my $probB = 1/(1+(1000**(($rating{$team1}-$rating{$team2})/$divisor)));

#round the probabilities to 2 significant digits and output %
$probA = sprintf "%.0f%%", $probA * 100;
$probB = sprintf "%.0f%%", $probB * 100;


print "Probability of $team1 beating $team2 is $probA<br>";
print "Probability of $team2 beating $team1 is $probB<br>";
	



print "<br><br>";


my $roundedRatingDifference = sprintf("%.3f", $ratingDifference);
print "The total rating difference between $team{$teamH{$team2}} and $team{$teamH{$team1}} ";

if (($useHomeFieldAdvantage eq "on") && ($useBiased eq "on")) {
print "with home field advantage and playoff committee bias ";
}

if (($useHomeFieldAdvantage eq "") && ($useBiased eq "on")) {
print "with playoff committee bias ";
}

if (($useHomeFieldAdvantage eq "on") && ($useBiased eq "")) {
print "with home field advantage ";
}



print "is  $roundedRatingDifference <br>";





print "</body>";

print "</html>";





