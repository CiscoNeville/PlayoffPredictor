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

	<option selected value="2024">2024</option>
	<option value="2023">2023</option>
	<option value="2022">2022</option>
	<option value="2021">2021</option>
	<option value="2020">2020</option>
	<option value="2019">2019</option>
	<option value="2018">2018</option>
	<option value="2017">2017</option>
	<option value="2016">2016</option>
	<option value="2015">2015</option>
	<option value="2014">2014</option>
	<option value="2013">2013</option>
	<option value="2012">2012</option>
	<option value="2011">2011</option>
	<option value="2010">2010</option>
	<option value="2009">2009</option>
	<option value="2008">2008</option>
	<option value="2007">2007</option>
	<option value="2006">2006</option>
	<option value="2005">2005</option>
	<option value="2004">2004</option>
	<option value="2003">2003</option>
	<option value="2002">2002</option>
	<option value="2001">2001</option>
	<option value="2000">2000</option>
	<option value="1999">1999</option>
	<option value="1998">1998</option>
	<option value="1997">1997</option>
	<option value="1996">1996</option>
	<option value="1995">1995</option>
	<option value="1994">1994</option>
	<option value="1993">1993</option>
	<option value="1992">1992</option>
	<option value="1991">1991</option>
	<option value="1990">1990</option>
</select>
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

