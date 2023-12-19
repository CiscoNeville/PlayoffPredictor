#!/bin/bash

# use as getTuesdayNightData <year> <week>
# example: ./getTuesdayNightData 2021 11

# run this from cron once per week from week 9 thorugh week 13


week=$2
year=$1

weekpcr='Week'${week}'PlayoffCommitteeRankings.txt'
weekacbf='Week'${week}'AverageCommitteeBiasFile.txt'

echo "--- running ~/cfbPlayoffPredictor/scripts/getCommitteeRankings.pl $year $week ---"
/home/neville/cfbPlayoffPredictor/scripts/getCommitteeRankings.pl $year $week
sleep 5

echo "--- running ~/cfbPlayoffPredictor/scripts/calculateBias.pl $year $week ---"
/home/neville/cfbPlayoffPredictor/scripts/calculateBias.pl $year $week
sleep 5

echo "--- running ~/cfbPlayoffPredictor/scripts/calculateAverageBias.pl $year $week ---"
/home/neville/cfbPlayoffPredictor/scripts/calculateAverageBias.pl $year $week
sleep 5

echo "--- running ~/cfbPlayoffPredictor/scripts/fullSeasonCommitteeBiasMatrix.pl $year ---"
/home/neville/cfbPlayoffPredictor/scripts/fullSeasonCommitteeBiasMatrix.pl $year
sleep 5

echo "--- copying -data/$year/week$week/$weekpcr to -data/current/currentPlayoffCommitteeRankings.txt ---"
echo "--- copying -data/$year/week$week/$weekacbf to -data/current/CurrentAveragedCommitteeBiasFile.txt ---"
cp /home/neville/cfbPlayoffPredictor/data/$year/week$week/$weekpcr /home/neville/cfbPlayoffPredictor/data/current/currentPlayoffCommitteeRankings.txt
cp /home/neville/cfbPlayoffPredictor/data/$year/week$week/$weekacbf /home/neville/cfbPlayoffPredictor/data/current/CurrentAveragedCommitteeBiasFile.txt

echo "--- running ~/cfbPlayoffPredictor/scripts/calculateAgaRatings.pl ---"
/home/neville/cfbPlayoffPredictor/scripts/calculateAgaRatings.pl
sleep 5

echo "--- running ~/cfbPlayoffPredictor/scripts/calculateCurrentPredictedRankings.pl ---"
/home/neville/cfbPlayoffPredictor/scripts/calculateCurrentPredictedRankings.pl

