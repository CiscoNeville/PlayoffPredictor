#!/usr/bin/python3
##############
#
# calculateAgaRatings.py
# Use the scores to this point in the season to compute the Aga Matrix
# AgaMatrix is defined as Colley Matrix with all IAA teams repreented by a single team and using MoV
#
# 1 argument mandatory - model weights (eta maximizing or ATS) input as "Eta" or "ATS"
#
# Can be called with 1 argument (use current data)
#  or called with 3 arguments (historical data-  year, week) 
#
#
# Input files: 
#   ncfScoresFile - contains results of games played up to that point in the season
#
# Output files:
#   CurrentCalculatedRatings{Eta|ATS}.txt  - agaMatrix ratings of all teams to that point in season. Used to populate homepage and sort for analyze schedule  (current invocation)
#    or Week{X}CalculatedRatings{Eta|ATS}.txt  - agaMatrix ratings {historical invocation}
#
##############

from datetime import datetime
import math
import sys
import re
import numpy as np
np.set_printoptions(linewidth=320, suppress=True, precision=4, threshold=100)  # prevent scrolling on prinout of large matricies and prevent scientific notation in output

# Initialize variables
ncfScoresFile = ""
fbsTeams = ""
calculatedRatingsFile = ""
year = None
week = None
alpha = -0.5
now = datetime.now()

# If called with zero arguments, error out
if len(sys.argv) == 1:
    print("Usage: calculateAgaRatings.py Eta|ATS [year week]")
    print("Example: calculateAgaRatings.py ATS 2014 9")
    print("model weights of Eta or ATS is mandatory. Year and week are optional")
    sys.exit(1)


modelWeights = sys.argv[1]

if modelWeights not in ["Eta", "ATS"]:  
    print("Model weights must be either 'Eta' or 'ATS' only")
    sys.exit(1)


# If called with one argument, use current data
if len(sys.argv) == 2:
    year = now.year
    if now.month < 3:
        year -= 1  # If in Jan or Feb, use last year for football season year
    ncfScoresFile = f"/home/neville/cfbPlayoffPredictor/data/current/ncfScoresFile.txt"
    fbsTeams = f"/home/neville/cfbPlayoffPredictor/data/{year}/fbsTeamNames.txt"
    calculatedRatingsFile = f"/home/neville/cfbPlayoffPredictor/data/current/CurrentCalculatedRatings{modelWeights}.txt"
# If called with three arguments, use historical data
elif len(sys.argv) == 4:
    year, week = int(sys.argv[2]), int(sys.argv[3])
    if 1947 <= year <= 2050 and 1 <= week <= 17:
        ncfScoresFile = f"/home/neville/cfbPlayoffPredictor/data/{year}/week{week}/Week{week}NcfScoresFile.txt"
        fbsTeams = f"/home/neville/cfbPlayoffPredictor/data/{year}/fbsTeamNames.txt"
        calculatedRatingsFile = f"/home/neville/cfbPlayoffPredictor/data/{year}/week{week}/Week{week}CalculatedRatings{modelWeights}.txt"
    else:
        print("Usage: calculateAgaRatings.py Eta|ATS [year week]")
        print("Example: calculateAgaRatings.py Eta 2014 9")
        sys.exit(1)
else:
    print("Usage: calculateAgaRatings.py Eta|ATS [year week]")
    print("Example: calculateAgaRatings.py Eta 2014 9")
    sys.exit(1)



#ATS MoV values
def calculate_mov_factor_ATS(mov):
    if (abs(mov) == 1): movFactor = -0.9
    if (abs(mov) == 2): movFactor = -0.8
    if (abs(mov) == 3): movFactor = -0.5
    if (abs(mov) == 4): movFactor = -0.4
    if (abs(mov) == 5): movFactor = -0.35
    if (abs(mov) == 6): movFactor = -0.3
    if (abs(mov) == 7): movFactor = -0.2
    if (abs(mov) == 8): movFactor = -0.1
    if (abs(mov) == 9): movFactor = -0.05
    if (abs(mov) == 10): movFactor = -0.04
    if (abs(mov) == 11): movFactor = -0.03
    if (abs(mov) == 12): movFactor = -0.02
    if (abs(mov) == 13): movFactor = -0.01
    if (abs(mov) == 14): movFactor = 0
    if (abs(mov) == 15): movFactor = 0.02
    if (abs(mov) == 16): movFactor = 0.05
    if (abs(mov) == 17): movFactor = 0.1
    if (abs(mov) == 18): movFactor = 0.15
    if (abs(mov) == 19): movFactor = 0.16
    if (abs(mov) == 20): movFactor = 0.17
    if (abs(mov) == 21): movFactor = 0.19
    if (abs(mov) == 22): movFactor = 0.2
    if (abs(mov) == 23): movFactor = 0.21
    if (abs(mov) == 24): movFactor = 0.22
    if (abs(mov) == 25): movFactor = 0.3
    if (abs(mov) == 26): movFactor = 0.32
    if (abs(mov) == 27): movFactor = 0.34
    if (abs(mov) == 28): movFactor = 0.36
    if (abs(mov) == 29): movFactor = 0.38
    if (abs(mov) == 30): movFactor = 0.4
    if (abs(mov) == 31): movFactor = 0.42
    if (abs(mov) == 32): movFactor = 0.44
    if (abs(mov) == 33): movFactor = 0.46
    if (abs(mov) == 34): movFactor = 0.48
    if (abs(mov) >= 35): movFactor = 0.5

    return movFactor



#Eta MoV values
def calculate_mov_factor_Eta(mov):
    if 1 <= abs(mov) <= 2: movFactor = -0.2  # close win, negative movFactor
    elif 3 <= abs(mov) <= 24: movFactor = 0     # standard win, no mov weight given
    elif 25 <= abs(mov) <= 34: movFactor = 0.2   # solid win
    elif abs(mov) >= 35: movFactor = 0.3   # blowout win

    return movFactor




# Read in FBS teams for this year
team = {}
teamH = {}
with open(fbsTeams, 'r') as fbsTeamsFile:
    for index, line in enumerate(fbsTeamsFile):  # use enumerate to get 0-based indices
        a, b = line.split(' => ')    #Duke => 3 from the input file.  should redo this to just a list without => 3
        team[a.strip()] = index   #index will start at 0, so team[Duke]=2, not 3.
teams = list(team.keys())
numberOfTeams = len(teams)
#print(f"number of teams for {year} is {numberOfTeams}")

# Initialize matrices
aM = np.zeros((numberOfTeams, numberOfTeams))  # 135x135 matrix of zeros, index from 0 to 134
rCV = np.zeros((numberOfTeams,1))                  # 135x1 column vector of zeros
bCV = np.zeros((numberOfTeams,1))                  # 135x1 column vector of zeros

# Create a dictionary to store season wins and losses for each team
seasonWins = {}
seasonLosses = {}
for t in teams:
    seasonWins[t] = 0
    seasonLosses[t] = 0

# Assign the diagonal of the Aga Matrix
for i in range(numberOfTeams):
    aM[i][i] = 2

# Assign the b column vector
for i in range(numberOfTeams):
    bCV[i] = 1

# Read input data from a file
k = 0
results = []
with open(ncfScoresFile, 'r') as ncfScoresFile:
    for scoreInput in ncfScoresFile:
        weekPlayed, gameStatus, scoreLine = scoreInput.split(':')
        awayResult, homeResult = scoreLine.split(' - ')  
    
        # Modified regex to capture everything up to the last number on each side
        m = re.search(r" (.+) (\d+)$", awayResult) 
        aTeamName, aTotal = m.group(1).strip(), int(m.group(2))
    
        n = re.search(r"(.+) (\d+)$", homeResult)
        hTeamName, hTotal = n.group(1).strip(), int(n.group(2))
    
        if aTeamName not in team.keys():
            aTeamName = "1AA"
        if hTeamName not in team.keys():
            hTeamName = "1AA"

        mov = hTotal - aTotal

        if modelWeights == "Eta": movFactor = calculate_mov_factor_Eta(mov)
        elif modelWeights == "ATS": movFactor = calculate_mov_factor_ATS(mov)

        i = team[hTeamName]
        j = team[aTeamName]

        if hTotal > aTotal:
            results.append(f"{hTeamName} 1-0 {aTeamName}")
            seasonWins[hTeamName] += 1
            seasonLosses[aTeamName] += 1
            aM[i][j] = aM[i][j] - 1 + alpha * movFactor
            aM[j][i] = aM[j][i] - 1 - alpha * movFactor
            aM[i][i] = aM[i][i] + 1 + alpha * movFactor
            aM[j][j] = aM[j][j] + 1 - alpha * movFactor
            bCV[i] = bCV[i] + 0.5
            bCV[j] = bCV[j] - 0.5
        else:
            results.append(f"{aTeamName} 1-0 {hTeamName}")
            seasonWins[aTeamName] += 1
            seasonLosses[hTeamName] += 1
            aM[i][j] = aM[i][j] - 1 - alpha * movFactor
            aM[j][i] = aM[j][i] - 1 + alpha * movFactor
            aM[i][i] = aM[i][i] + 1 - alpha * movFactor
            aM[j][j] = aM[j][j] + 1 + alpha * movFactor
            bCV[j] = bCV[j] + 0.5
            bCV[i] = bCV[i] - 0.5
        k += 1


#check matrix consutructed correctly
#print(f"top 10x10 of aM is:\n{aM[0:10, 0:10]}")     
#print(f"aM is:\n{aM}\n{aM.shape}")

# Get inverse and solve for rCV
aM_inv = np.linalg.inv(aM)
rCV = aM_inv @ bCV   # @ is the matrix multiplcation operator
#print(f"rCV is:\n{rCV}\n{rCV.shape}")

# Get team ratings
agaRating = [float(f'{x:.4f}'.rstrip('0').rstrip('.')) for x in np.round(rCV, decimals=4).flatten()] #round to 4 decimals and float->string->float to get 0.6 not 0.6000

# Sort and write ratings to file
ratings = [(float(agaRating[i]), teams[i], seasonWins[teams[i]], seasonLosses[teams[i]]) 
           for i in range(len(teams))]
sortedRatings = sorted(ratings, reverse=True)

with open(calculatedRatingsFile, 'w') as outFile:
    outFile.write(f"# Aga calculated ratings with {modelWeights} maximizing weights for Week {week} year {year} - Generated {now}\n")
    for rating, team, wins, losses in sortedRatings:
        outFile.write(f"{team}:{rating}:{wins}-{losses}\n")

print(f"calculateAgaRatings with {modelWeights} model weights done for year {year} week {week}. First 3 entries are:\n1. {sortedRatings[0][1]}\t- {sortedRatings[0][0]}\n2. {sortedRatings[1][1]}\t- {sortedRatings[1][0]}\n3. {sortedRatings[2][1]}\t- {sortedRatings[2][0]}\nOutput written to", calculatedRatingsFile)
