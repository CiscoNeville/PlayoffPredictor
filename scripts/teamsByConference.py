#!/usr/bin/env python3

import json
import sys
from collections import defaultdict
from pathlib import Path

def read_json_file(file_path):
    with open(file_path, 'r') as f:
        return json.load(f)

def group_teams_by_conference(teams):
    conferences = defaultdict(list)
    for team in teams:
        conference = team.get('conference') or "Conference_N/A"
        conferences[conference].append(team['team_name'])
    return conferences

def print_teams_by_conference(conferences):
    total_teams = 0
    for conference, teams in conferences.items():
        print(f"{conference} - {len(teams)} teams total")
        for team in sorted(teams):
            print(f" {team}")
        print()  # Add a blank line between conferences
        total_teams += len(teams)
    print(f"Total number of teams: {total_teams}")

def main():
    if len(sys.argv) != 2:
        print("Usage: ./teamsByConference.py <year>")
        sys.exit(1)

    year = sys.argv[1]
    json_file = Path.home() / 'cfbPlayoffPredictor' / 'data' / year / 'fbsTeams.json'

    # Read the JSON file
    try:
        teams_data = read_json_file(json_file)
    except FileNotFoundError:
        print(f"Error: File not found: {json_file}")
        sys.exit(1)
    except json.JSONDecodeError:
        print(f"Error: Invalid JSON in file: {json_file}")
        sys.exit(1)

    # Group teams by conference
    grouped_teams = group_teams_by_conference(teams_data)

    # Print the results
    print_teams_by_conference(grouped_teams)

if __name__ == "__main__":
    main()