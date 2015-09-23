<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en-US">
<head>
	<meta charset="utf-8" />
	<title>{% if title is defined %}{{ title }} - {% endif %}{{ siteName }}</title>
	<link rel="home" href="{{ siteUrl }}" />

	<style type="text/css">
		body { margin: 50px; font-family: sans-serif; background: #fff; }
		#container { margin: 0 auto; width: 700px;
			-webkit-box-shadow: 0 2px 10px rgba(0,0,0,0.1);
			        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
		}

		
		
		

		#content { display: block; padding: 25px; background: #fff; }
		#content h1 { margin-top: 0; font-size: 35px; font-weight: bold; }
	</style>
</head>

<body>
	<div id="">
		<header id="header">
			<h1></h1>

			<nav>
				
			</nav>
		</header>

		<main id="content" role="main">
				<?php 
					include("../includes/index.php")
				?>	
		</main>

		<footer id="footer">
			
		</footer>
	</div>
</body>
</html>