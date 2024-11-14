<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
"http://www.w3.org/TR/html4/strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>PlayoffPredictor.com - Predict next week's cfb committee rankings</title>
		<meta name="Neville Aga" content="root" />
	   <link type="text/css" rel="stylesheet" href="/style.css" /> 
	</head>
	<body style="font-family:arial">
		
		
<!-- Google Analytics Code-->    
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-988NB7TH39"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-988NB7TH39');
</script>
		
	
		
<!--banner and menu-->    
   <?php 

if ($_GET['app'] != 'true') {   # only display in web
$banner = '/var/www/ppDocs/banner-and-menu.html';
$data = file($banner) or die('Could not read file!');
foreach ($data as $line) {
echo "$line";
}
} else {
echo '<img src="/playoff-predictor.jpg" alt="pp banner">';  #put a non-clickable banner for the top of the app
}
    ?>  

		
		<h3>Predict the winning probabilities of a single game</h3>
		
		



<p>Here you can use an Elo-based calculation to predict the winning probailities for two given teams.  The winning probabilities are based on the ratings of each team. Home field and committee bias can optionally be included. </p>
<p>The probabilities are computed with a <a href="https://blog.agafamily.com/2022/09/09/elo-predictions-for-college-football-base-and-divisor/">base equal to 1000 and a divisor of 1</a></p>



	<img src = "./elo_formula.png"> 
	<br><br>
	<i>where:</i><br>
	 - P(A) = probability team A wins against team B <br>
	 - r<sub>A</sub> = rating of team A <br>
	 - r<sub>B</sub> = rating of team B <br>
	 
<br>
The team ratings can be either biased (include data from the playoff committee) or be unbiased (purely using the computer rating of the team).<br>The computer ratings *do* include margin of victory data. <br>
	<br><br> 
	These probabilities are modeled from <a href="https://en.wikipedia.org/wiki/Elo_rating_system">elo ratings</a>, commonly used in competitive Chess <br>
</p>	
<form autocomplete="off" action="/cgi-bin/eloSingleGame.cgi" method="post"> 

	
<!--	<table border="0px" cellpadding="4px" cellspacing="1px"> -->


<hr>
<br>

 <table style="font-family:arial; background-color:#ffffff">
<tr><td><center style="font-size: 1.4em">Show winning probabilities of a specific game  (enter your teams below)</center></td></tr>
<tr></tr>
<tr><td></td></tr>

<tr>
 <td>
    <div class="autocomplete" style="width:250px;">	
	<input type="text" name="team1" id="team1ID" value="" placeholder="enter AWAY Team 1 name here">
    </div>

    &nbsp  &nbsp  &nbsp vs.

 <div class="autocomplete" style="width:250px;">	
<input type="text" name="team2" id="team2ID" value=""  placeholder="enter HOME Team 2 name here">  
</div>

 </td> 
</tr>




<tr><td></td></tr>
<tr><td></td></tr>

	
	

	
</td></tr>

<tr><td></td></tr>
<tr><td></td></tr>

<tr><td><input type="checkbox" name="useBiased" value"useBiasedTrue"> Use biased ratings (playoff committee bias)</td></tr>
<tr><td><input type="checkbox" name="useHomeFieldAdvantage" value"useHomeFieldAdvantageTrue" checked> Add home field advantage into home teams rating</td></tr>
<tr><td></td></tr>


</table> 


<?php
if ($_GET['app'] == 'true') {
echo '<input type="hidden" name="callingFromApp" value="true">';
}
?>



	<br><br>	<b>	<input type="submit" id="mybutton" value="Show winning probabilities" /><br>	</b>
	

	
</form>		



<script>

function autocomplete(inp, arr) {
  /*the autocomplete function takes two arguments,
  the text field element and an array of possible autocompleted values:*/
  var currentFocus;
  /*execute a function when someone writes in the text field:*/
  inp.addEventListener("input", function(e) {
      var a, b, i, val = this.value;
      /*close any already open lists of autocompleted values*/
      closeAllLists();
      if (!val) { return false;}
      currentFocus = -1;
      /*create a DIV element that will contain the items (values):*/
      a = document.createElement("DIV");
      a.setAttribute("id", this.id + "autocomplete-list");
      a.setAttribute("class", "autocomplete-items");
      /*append the DIV element as a child of the autocomplete container:*/
      this.parentNode.appendChild(a);
      /*for each item in the array...*/
      for (i = 0; i < arr.length; i++) {
        /*check if the item starts with the same letters as the text field value:*/
        if (arr[i].substr(0, val.length).toUpperCase() == val.toUpperCase()) {
          /*create a DIV element for each matching element:*/
          b = document.createElement("DIV");
          /*make the matching letters bold:*/
          b.innerHTML = "<strong>" + arr[i].substr(0, val.length) + "</strong>";
          b.innerHTML += arr[i].substr(val.length);
          /*insert a input field that will hold the current array item's value:*/
          b.innerHTML += "<input type='hidden' value='" + arr[i] + "'>";
          /*execute a function when someone clicks on the item value (DIV element):*/
              b.addEventListener("click", function(e) {
              /*insert the value for the autocomplete text field:*/
              inp.value = this.getElementsByTagName("input")[0].value;
              /*close the list of autocompleted values,
              (or any other open lists of autocompleted values:*/
              closeAllLists();
          });
          a.appendChild(b);
        }
      }
  });
  /*execute a function presses a key on the keyboard:*/
  inp.addEventListener("keydown", function(e) {
      var x = document.getElementById(this.id + "autocomplete-list");
      if (x) x = x.getElementsByTagName("div");
      if (e.keyCode == 40) {
        /*If the arrow DOWN key is pressed,
        increase the currentFocus variable:*/
        currentFocus++;
        /*and and make the current item more visible:*/
        addActive(x);
      } else if (e.keyCode == 38) { //up
        /*If the arrow UP key is pressed,
        decrease the currentFocus variable:*/
        currentFocus--;
        /*and and make the current item more visible:*/
        addActive(x);
      } else if (e.keyCode == 13) {
        /*If the ENTER key is pressed, prevent the form from being submitted,*/
        e.preventDefault();
        if (currentFocus > -1) {
          /*and simulate a click on the "active" item:*/
          if (x) x[currentFocus].click();
        }
      }
  });
  function addActive(x) {
    /*a function to classify an item as "active":*/
    if (!x) return false;
    /*start by removing the "active" class on all items:*/
    removeActive(x);
    if (currentFocus >= x.length) currentFocus = 0;
    if (currentFocus < 0) currentFocus = (x.length - 1);
    /*add class "autocomplete-active":*/
    x[currentFocus].classList.add("autocomplete-active");
  }
  function removeActive(x) {
    /*a function to remove the "active" class from all autocomplete items:*/
    for (var i = 0; i < x.length; i++) {
      x[i].classList.remove("autocomplete-active");
    }
  }
  function closeAllLists(elmnt) {
    /*close all autocomplete lists in the document,
    except the one passed as an argument:*/
    var x = document.getElementsByClassName("autocomplete-items");
    for (var i = 0; i < x.length; i++) {
      if (elmnt != x[i] && elmnt != inp) {
      x[i].parentNode.removeChild(x[i]);
    }
  }
}
/*execute a function when someone clicks in the document:*/
document.addEventListener("click", function (e) {
    closeAllLists(e.target);
});
}


/*Get the array containing all the team names by reading in teams for the current year:*/
var teamNames = ["Boston College","Clemson","Duke","Florida State","Georgia Tech","Louisville","Miami","North Carolina","NC State","Pittsburgh","Syracuse","Virginia","Virginia Tech","Wake Forest","Baylor","Iowa State","Kansas","Kansas State","Oklahoma","Oklahoma State","TCU","Texas","Texas Tech","West Virginia","Illinois","Indiana","Iowa","Maryland","Michigan","Michigan State","Minnesota","Nebraska","Northwestern","Ohio State","Penn State","Purdue","Rutgers","Wisconsin","Arizona","Arizona State","California","Colorado","Oregon","Oregon State","Stanford","UCLA","USC","Utah","Washington","Washington State","Alabama","Arkansas","Auburn","Florida","Georgia","Kentucky","LSU","Mississippi State","Missouri","Ole Miss","South Carolina","Tennessee","Texas A&M","Vanderbilt","Cincinnati","UConn","East Carolina","Houston","Memphis","SMU","South Florida","Temple","Tulane","Tulsa","UCF","Charlotte","Florida Atlantic","Florida International","Louisiana Tech","Marshall","Middle Tennessee","North Texas","Old Dominion","Rice","Southern Miss","UTEP","UTSA","Western Kentucky","Akron","Ball State","Bowling Green","Buffalo","Central Michigan","Eastern Michigan","Kent State","UMass","Miami (OH)","Northern Illinois","Ohio","Toledo","Western Michigan","Air Force","Boise State","Colorado State","Fresno State","Hawaii","Nevada","New Mexico","San Diego State","San Jose State","UNLV","Utah State","Wyoming","Appalachian State","Arkansas State","Georgia Southern","Georgia State","Liberty","Louisiana","UL Monroe","New Mexico State","South Alabama","Texas State","Troy","Army","BYU","Navy","Notre Dame","UAB","Coastal Carolina","1AA"]	;
	
/*initiate the autocomplete function on the "teamID" element, and pass along the team array as possible autocomplete values:*/
autocomplete(document.getElementById("team1ID"), teamNames);
	
</script>


<script>

function autocomplete2(inp, arr) {
  /*the autocomplete function takes two arguments,
  the text field element and an array of possible autocompleted values:*/
  var currentFocus;
  /*execute a function when someone writes in the text field:*/
  inp.addEventListener("input", function(e) {
      var a, b, i, val = this.value;
      /*close any already open lists of autocompleted values*/
      closeAllLists();
      if (!val) { return false;}
      currentFocus = -1;
      /*create a DIV element that will contain the items (values):*/
      a = document.createElement("DIV");
      a.setAttribute("id", this.id + "autocomplete-list");
      a.setAttribute("class", "autocomplete-items");
      /*append the DIV element as a child of the autocomplete container:*/
      this.parentNode.appendChild(a);
      /*for each item in the array...*/
      for (i = 0; i < arr.length; i++) {
        /*check if the item starts with the same letters as the text field value:*/
        if (arr[i].substr(0, val.length).toUpperCase() == val.toUpperCase()) {
          /*create a DIV element for each matching element:*/
          b = document.createElement("DIV");
          /*make the matching letters bold:*/
          b.innerHTML = "<strong>" + arr[i].substr(0, val.length) + "</strong>";
          b.innerHTML += arr[i].substr(val.length);
          /*insert a input field that will hold the current array item's value:*/
          b.innerHTML += "<input type='hidden' value='" + arr[i] + "'>";
          /*execute a function when someone clicks on the item value (DIV element):*/
              b.addEventListener("click", function(e) {
              /*insert the value for the autocomplete text field:*/
              inp.value = this.getElementsByTagName("input")[0].value;
              /*close the list of autocompleted values,
              (or any other open lists of autocompleted values:*/
              closeAllLists();
          });
          a.appendChild(b);
        }
      }
  });
  /*execute a function presses a key on the keyboard:*/
  inp.addEventListener("keydown", function(e) {
      var x = document.getElementById(this.id + "autocomplete-list");
      if (x) x = x.getElementsByTagName("div");
      if (e.keyCode == 40) {
        /*If the arrow DOWN key is pressed,
        increase the currentFocus variable:*/
        currentFocus++;
        /*and and make the current item more visible:*/
        addActive(x);
      } else if (e.keyCode == 38) { //up
        /*If the arrow UP key is pressed,
        decrease the currentFocus variable:*/
        currentFocus--;
        /*and and make the current item more visible:*/
        addActive(x);
      } else if (e.keyCode == 13) {
        /*If the ENTER key is pressed, prevent the form from being submitted,*/
        e.preventDefault();
        if (currentFocus > -1) {
          /*and simulate a click on the "active" item:*/
          if (x) x[currentFocus].click();
        }
      }
  });
  function addActive(x) {
    /*a function to classify an item as "active":*/
    if (!x) return false;
    /*start by removing the "active" class on all items:*/
    removeActive(x);
    if (currentFocus >= x.length) currentFocus = 0;
    if (currentFocus < 0) currentFocus = (x.length - 1);
    /*add class "autocomplete-active":*/
    x[currentFocus].classList.add("autocomplete-active");
  }
  function removeActive(x) {
    /*a function to remove the "active" class from all autocomplete items:*/
    for (var i = 0; i < x.length; i++) {
      x[i].classList.remove("autocomplete-active");
    }
  }
  function closeAllLists(elmnt) {
    /*close all autocomplete lists in the document,
    except the one passed as an argument:*/
    var x = document.getElementsByClassName("autocomplete-items");
    for (var i = 0; i < x.length; i++) {
      if (elmnt != x[i] && elmnt != inp) {
      x[i].parentNode.removeChild(x[i]);
    }
  }
}
/*execute a function when someone clicks in the document:*/
document.addEventListener("click", function (e) {
    closeAllLists(e.target);
});
}


/*Get the array containing all the team names by reading in teams for the current year:*/
var teamNames = ["Boston College","Clemson","Duke","Florida State","Georgia Tech","Louisville","Miami","North Carolina","NC State","Pittsburgh","Syracuse","Virginia","Virginia Tech","Wake Forest","Baylor","Iowa State","Kansas","Kansas State","Oklahoma","Oklahoma State","TCU","Texas","Texas Tech","West Virginia","Illinois","Indiana","Iowa","Maryland","Michigan","Michigan State","Minnesota","Nebraska","Northwestern","Ohio State","Penn State","Purdue","Rutgers","Wisconsin","Arizona","Arizona State","California","Colorado","Oregon","Oregon State","Stanford","UCLA","USC","Utah","Washington","Washington State","Alabama","Arkansas","Auburn","Florida","Georgia","Kentucky","LSU","Mississippi State","Missouri","Ole Miss","South Carolina","Tennessee","Texas A&M","Vanderbilt","Cincinnati","UConn","East Carolina","Houston","Memphis","SMU","South Florida","Temple","Tulane","Tulsa","UCF","Charlotte","Florida Atlantic","Florida International","Louisiana Tech","Marshall","Middle Tennessee","North Texas","Old Dominion","Rice","Southern Miss","UTEP","UTSA","Western Kentucky","Akron","Ball State","Bowling Green","Buffalo","Central Michigan","Eastern Michigan","Kent State","UMass","Miami (OH)","Northern Illinois","Ohio","Toledo","Western Michigan","Air Force","Boise State","Colorado State","Fresno State","Hawaii","Nevada","New Mexico","San Diego State","San Jose State","UNLV","Utah State","Wyoming","Appalachian State","Arkansas State","Georgia Southern","Georgia State","Liberty","Louisiana","UL Monroe","New Mexico State","South Alabama","Texas State","Troy","Army","BYU","Navy","Notre Dame","UAB","Coastal Carolina","1AA"]	;
	
/*initiate the autocomplete function on the "teamID" element, and pass along the team array as possible autocomplete values:*/
autocomplete2(document.getElementById("team2ID"), teamNames);
	
</script>


	</body>
</html>

