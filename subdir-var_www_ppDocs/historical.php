<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
"http://www.w3.org/TR/html4/strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>PlayoffPredictor.com FAQ</title>
		<meta name="author" content="root" />
		<!-- Date: 2014-11-09 -->
        <link type="text/css" rel="stylesheet" href="style.css" />
    </head>
  
  
    <body>
    
 <!-- Google Analytics Code-->    
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-57061804-1', 'auto');
  ga('send', 'pageview');

</script>
   
    
    
<!--banner and menu-->    
   <?php 
$banner = '/var/www/ppDocs/banner-and-menu.html';
$data = file($banner) or die('Could not read file!');
foreach ($data as $line) {
echo "$line";
}
    ?>  



<h2>Historical data</h2>

Pick a season and week to display historical data of predicted committee rankings, actual playoff committee rankings and computer rankings for that week<br>


<form action="/cgi-bin/agaPPMatrix2.cgi" method="post" target="iframe_a">   
	<table border="0px" cellpadding="4px" cellspacing="1px">

<tr class=\"border_bottom\">
<td> </td>
<td><select name="year">
	<option value="2014">2014</option>
	<option value="2015">2015</option>
	<option value="2016">2016</option>
	<option value="2017">2017</option>
	<option value="2018">2018</option>
	<option value="2019">2019</option>	
	<option value="2020">2020</option>
	<option value="2021">2021</option>
	<option value="2022">2022</option></select>
	 </td>
<td> </td>
<td><select name="week">
	<option value="Week1">Week 1</option>
	<option value="Week2">Week 2</option>
	<option value="Week3">Week 3</option>
	<option value="Week4">Week 4</option>
	<option value="Week5">Week 5</option>
	<option value="Week6">Week 6</option>
	<option value="Week7">Week 7</option>
	<option value="Week8">Week 8</option>
	<option value="Week9">Week 9</option>
	<option value="Week10">Week 10</option>
	<option value="Week11">Week 11</option>
	<option value="Week12">Week 12</option>
	<option value="Week13">Week 13</option>
	<option value="Week14">Week 14  (conference championship week)</option>
	<option value="Week15">Week 15  (Army-Navy week)</option>
	<option value="Week17">Bowls</option>
	</select> </td>
<td>	<b>	<input type="submit" value="Show past rankings" /><br>	</b> </td>

</tr>


	</table>
	

	
	</form>
	


 <iframe src="/week_definitions.html" name="iframe_a" style="border:none" width="1000" height="2000"></iframe>





	</body>
</html>

