#!/usr/bin/python3
##############
#
# ATSPerformance.py
# calculate ATS performance for a given week
#
# Called with 2 arguments:
#   ./ATSPerformance.py <year> <week> 
#
#
# Input files: 
#   ncfSpreads.txt - Spread data for this week in the folder /data/year/week
#   Week<week-1>CalculatedRatings.txt - Ratings from last week in the folder /data/year/week-1
#   Week<week>NcFScoresFile.txt - Game results from this week in the folder /data/year/week
#
# Output files:
#   none
#
##############

import math
import sys
import requests
import json
import datetime
import numpy
import pandas
import cfbd

# Get the CURrent date and time
current_datetime = datetime.datetime.now()

def main():
    # Check if exactly 3 arguments are passed (including the script name)
    if len(sys.argv) != 3:
        print("Usage: python ATSPerformace.py year week")
        sys.exit(1)  # Exit with an error code

    year = sys.argv[1]
    weekNumber = int(sys.argv[2])
    weekNumberMinusOne = weekNumber -1 
    seasonType = 'regular'

    SpreadsFile = f"/home/neville/cfbPlayoffPredictor/data/{year}/week{weekNumber}/Week{weekNumber}Spreads.txt"

#    # Check if weekNumber is 17 and update seasonType accordingly
#    if weekNumber == "17":
#        seasonType = 'postseason'
#        weekNumber = '1'

    InputRatingsFile = f"/home/neville/cfbPlayoffPredictor/data/{year}/week{weekNumberMinusOne}/Week{weekNumberMinusOne}CalculatedRatings.txt"

    NcfScoresFile = f"/home/neville/cfbPlayoffPredictor/data/{year}/week{weekNumber}/Week{weekNumber}NcfScoresFile.txt"
 
 
    print(f"SpreadsFile is        {SpreadsFile}")
    print(f"Input Ratings File is {InputRatingsFile}")
    print(f"Ncf Scores Files is   {NcfScoresFile}")
 











if __name__ == "__main__":
    main()
 

