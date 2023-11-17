#!/usr/bin/env python3
##############
#
# Pull from api.collegefootballdata.com for money line for that week
# Intended to run on Tuesday nights to get data ready for the upcoming saturday
#
# usage: ./getSpreads.py 2023 2
#
# Input files: none
#
# Output files:
#   ncfSpreads - contains spreads for games played the next week in the season
#
# 
##############

import sys
import requests
import json
import datetime

# Get the current date and time
current_datetime = datetime.datetime.now()

# List of FBS conferences
fbs_conferences = ['SEC', 'Big 12', 'ACC', 'Pac-12', 'Big Ten', 'FBS Independents', 'Mid-American', 'Mountain West', 'American Athletic', 'Conference USA', 'Sun Belt']

# Team name mapping dictionary. 
team_name_mapping = {
    'San José State': 'San Jose State',
    "Hawai'i": "Hawaii",
    'UMass': 'Massachusetts',
    'Connecticut': 'UConn',
    'Southern Mississippi': 'Southern Miss',
    'UT San Antonio': 'UTSA',
    'Sam Houston State': 'Sam Houston',
    'Louisiana Monroe': 'UL Monroe',
}


def main():
    # Check if exactly 3 arguments are passed (including the script name)
    if len(sys.argv) != 3:
        print("Usage: python getSpreads.py year week")
        sys.exit(1)  # Exit with an error code

    year = sys.argv[1]
    weekNumber = sys.argv[2]
    seasonType = 'regular'

    NcfSpreadsFile = f"/home/neville/cfbPlayoffPredictor/data/{year}/week{weekNumber}/ncfSpreads.txt"

    # Check if weekNumber is 17 and update seasonType accordingly
    if weekNumber == "17":
        seasonType = 'postseason'
        weekNumber = '1'

    print(f"getting spread data for year={year}, weekNumber={weekNumber}, seasonType={seasonType} from api.collegefootballdata.com")

    url = "https://api.collegefootballdata.com/lines"

    querystring = {"year":year,"week":weekNumber,"seasonType":seasonType}

    payload = ""
    headers = {"Authorization": "Bearer UjhZIjHcXgMNPQRHsiaRG/tiFZe8Gzu4yJyVcN+R9YeH6j/2WnmXYOTaCFHyiPHQ"}

    response = requests.request("GET", url, data=payload, headers=headers, params=querystring)
    #print(response.text)
    data = json.loads(response.text)

    with open(NcfSpreadsFile, 'w') as file:
        file.write(f"#Data retrieved {current_datetime}\n")
        file.write("#year : week : id : awayTeam : HomeTeam : spread : overUnder : provider\n")

        game_count = 0
        # Iterate through the list of games
        for game in data:
            homeTeam = game['homeTeam']
            awayTeam = game['awayTeam']
            homeConference = game['homeConference']
            id = game['id']

            # Convert team names using the mapping dictionary. If not in dictionary no change made
            homeTeam = team_name_mapping.get(homeTeam, homeTeam)
            awayTeam = team_name_mapping.get(awayTeam, awayTeam)

            provider = get_provider(game)

            # Check if 'lines' is populated and has at least one provider
            if 'lines' in game and len(game['lines']) > 0 and homeConference in fbs_conferences:
                first_provider = game['lines'][0]['provider']
                spread = game['lines'][0]['spread']
                overUnder = game['lines'][0]['overUnder']

                #print(f"Home Team: {homeTeam}")
                #print(f"Away Team: {awayTeam}")
                #print(f"First Provider: {first_provider}")
                #print(f"Spread: {spread}")
                #print(f"Over/Under: {overUnder}")
                #print()
                file.write(f"{year}:week {weekNumber}:{id}:{awayTeam}:{homeTeam}:{spread}:{overUnder}:{provider}\n")
                game_count += 1

    print(f"All done. wrote {game_count} lines")            


def get_provider(game):
    # Check if 'lines' is populated and has the provider "Bovada"
    for line in game['lines']:
        if 'provider' in line and line['provider'] == 'Bovada':
            return 'Bovada'
    # If "bovada" is not found, return the first provider under lines
    if 'lines' in game and len(game['lines']) > 0:
        return game['lines'][0]['provider']
    # Default to empty string if no provider is available
    return ''


if __name__ == "__main__":
    main()



