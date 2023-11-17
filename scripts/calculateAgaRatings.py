#!/usr/bin/python3
##############
#
# calculateAgaRatings.py
# Use the scores to this point in the season to compute the Aga Matrix
# AgaMatrix is defined as Colley Matrix with all IAA teams repreented by a single team and using MoV
#
# Can be called with 0 arguments (use current data)
#  or called with 2 arguments (historical data) 
#
#
# Input files: 
#   ncfScoresFile - contains results of games played up to that point in the season
#
# Output files:
#   CurrentCalculatedRatings.txt  - agaMatrix ratings of all teams to that point in season. Used to populate homepage and sort for analyze schedule  (current invocation)
#    or Week{X}CalculatedRatings.txt  - agaMatrix ratings {historical invocation}
#
##############

from datetime import datetime
import math
import sys

# Initialize variables
ncfScoresFile = ""
fbsTeams = ""
calculatedRatingsFile = ""
year = None
week = None
alpha = -0.5

# If called with zero arguments, use current data
if len(sys.argv) == 1:
    now = datetime.now()
    year = now.year
    if now.month < 3:
        year -= 1  # If in Jan or Feb, use last year for football season year
    ncfScoresFile = f"/home/neville/cfbPlayoffPredictor/data/current/ncfScoresFile.txt"
    fbsTeams = f"/home/neville/cfbPlayoffPredictor/data/{year}/fbsTeamNames.txt"
    calculatedRatingsFile = "/home/neville/cfbPlayoffPredictor/data/current/CurrentCalculatedRatings.txt"
# If called with two arguments, use historical data
elif len(sys.argv) == 3:
    year, week = int(sys.argv[1]), int(sys.argv[2])
    if 1947 <= year <= 2050 and 1 <= week <= 17:
        ncfScoresFile = f"/home/neville/cfbPlayoffPredictor/data/{year}/week{week}/Week{week}NcfScoresFile.txt"
        fbsTeams = f"/home/neville/cfbPlayoffPredictor/data/{year}/fbsTeamNames.txt"
        calculatedRatingsFile = f"/home/neville/cfbPlayoffPredictor/data/{year}/week{week}/Week{week}CalculatedRatings.txt"
    else:
        print("Usage: calculateAgaRatings.py [year week]")
        print("Example: calculateAgaRatings.py 2014 9")
        sys.exit(1)
else:
    print("Usage: calculateAgaRatings.py [year week]")
    print("Example: calculateAgaRatings.py 2014 9")
    sys.exit(1)

# Read in FBS teams for this year
team = {}
teamH = {}
with open(fbsTeams, 'r') as fbsTeamsFile:
    for line in fbsTeamsFile:
        a, b = line.split(' => ')
        team[b.strip()] = a.strip()
        teamH[a.strip()] = b.strip()
teams = list(team.keys())
numberOfTeams = len(teams)

cM = [[0] * numberOfTeams for _ in range(numberOfTeams)]
rCV = [0] * numberOfTeams
bCV = [0] * numberOfTeams

# Create a dictionary to store season wins and losses for each team
seasonWins = {}
seasonLosses = {}
for t in teams:
    seasonWins[t] = 0
    seasonLosses[t] = 0

# Assign the diagonal of the Colley Matrix
for i in range(numberOfTeams):
    cM[i][i] = 2

# Assign the b column vector
for i in range(numberOfTeams):
    bCV[i] = 1

# Read input data from a file
k = 0
results = []
with open(ncfScoresFile, 'r') as ncfScoresFile:
    for scoreInput in ncfScoresFile:
        resultsWinner, resultsLoser = scoreInput.split(':')
        m = re.search(r"Final.*: (.+?) (\d+) - (.+?) (\d+)", scoreInput)
        aTeamName, aTotal, hTeamName, hTotal = m.group(1, 2, 3, 4)

        if aTeamName not in team.values():
            aTeamName = "1AA"
        if hTeamName not in team.values():
            hTeamName = "1AA"

        mov = hTotal - aTotal

        # Tiered MoV as per paper
        movFactor = 0
        if 1 <= mov <= 2 or -2 <= mov <= -1:
            movFactor = -0.2  # Close game type 1
        if 25 <= mov <= 34 or -34 <= mov <= -25:
            movFactor = 0.2  # Blowout type 1
        if mov >= 35 or mov <= -35:
            movFactor = 0.3  # Blowout type 2

        i = teamH[hTeamName]
        j = teamH[aTeamName]
        cM[i][j] = cM[i][j] - 1 + alpha * movFactor
        cM[j][i] = cM[j][i] - 1 - alpha * movFactor
        cM[i][i] = cM[i][i] + 1 + alpha * movFactor
        cM[j][j] = cM[j][j] + 1 - alpha * movFactor

        b1 = bCV[i]
        b2 = bCV[j]

        if hTotal > aTotal:
            results.append(f"{hTeamName} 1-0 {aTeamName}")
            seasonWins[hTeamName] += 1
            seasonLosses[aTeamName] += 1
            bCV[i] = b1 + 0.5
            bCV[j] = b2 - 0.5
        else:
            results.append(f"{aTeamName} 1-0 {hTeamName}")
            seasonWins[aTeamName] += 1
            seasonLosses[hTeamName] += 1
            bCV[j] = b2 + 0.5
            bCV[i] = b1 - 0.5
        k += 1

# Solve for rCV
cM = np.array(cM)
bCV = np.array(bCV)
dim = np.linalg.matrix_rank(cM)
LRM = np.linalg.cholesky(cM)
try:
    rCV = np.linalg.solve(LRM, bCV)
except np.linalg.LinAlgError:
    print("Error: No solution to the Colley Matrix.")
    sys.exit(1)

# Get team ratings
agaRating = [round(r, 10) for r in rCV]

# Sort ratings for output
ratings = []
sortedRatings = []
for i in range(len(teams)):
    ratings.append(f"{agaRating[i]}:{teams[i]}:{agaRating[i]}: record is {seasonWins[teams[i]]} and {seasonLosses[teams[i]]}")
sortedRatings = sorted(ratings, reverse=True)

# Write calculated ratings to a file
with open(calculatedRatingsFile, 'w') as outFile:
    for sr in sortedRatings:
        sr = sr.split(':')
        outFile.write(f"{sr[1]}:{sr[2]}:{sr[3]}\n")

print("calculateAgaRatings done. Output written to", calculatedRatingsFile)
