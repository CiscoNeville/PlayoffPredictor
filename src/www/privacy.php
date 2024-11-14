<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
"http://www.w3.org/TR/html4/strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Current Predicted Rankings</title>
		<meta name="author" content="root" />
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

<table class="buckets">

	<td class="bucket1"><center><b>Privacy policy</b></center><hr>
  	<h4>PlayoffPredictor.com privacy policy</h4>




 <p> playoffPredictor.com collects no information on anyone that uses the site.  No sign in is required, you are not required to identify yourself in any way to the site. There is no advertising. There is no monetization. You may be a patron if you want and donate there. </p>

	</body>
</html>

