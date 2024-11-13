#!/bin/bash

# use as doSundayNightArchive <year> <week>
# example: ./doSundayNightArchive 2021 11

# run this from cron once per week from week 1 thorugh week 17
#TODO - year should come from clock, and perhaps even week.


week=$2
year=$1

weekncf='Week'${week}'NcfScoresFile.txt'
weekcr='Week'${week}'CalculatedRatings.txt'


cp /home/neville/cfbPlayoffPredictor/data/current/ncfScoresFile.txt /home/neville/cfbPlayoffPredictor/data/$year/week$week/$weekncf
cp /home/neville/cfbPlayoffPredictor/data/current/CurrentCalculatedRatings.txt /home/neville/cfbPlayoffPredictor/data/$year/week$week/$weekcr


#adding separated Eta and ATS calculation
/home/neville/cfbPlayoffPredictor/scripts/calculateAgaRatings.py ATS ${year} ${week}
/home/neville/cfbPlayoffPredictor/scripts/calculateAgaRatings.py Eta ${year} ${week}