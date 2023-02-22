#!/bin/bash

#excute this from cron to:
# 1. get scores
# 2. calcualte ratings
# 3. calculate rankings

/home/neville/cfbPlayoffPredictor/scripts/getScores.pl
sleep 5
/home/neville/cfbPlayoffPredictor/scripts/calculateAgaRatings.pl
/home/neville/cfbPlayoffPredictor/scripts/calculateCurrentPredictedRankings.pl
