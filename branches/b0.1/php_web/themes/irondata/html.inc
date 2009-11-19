<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<title>IronData | <?php echo ucwords($data->title);?></title>
		<style type="text/css">
			@import "<?php echo $scriptroot;?>dijit/themes/tundra/tundra.css";
			@import "<?php echo $scriptroot;?>dojo/resources/dojo.css";

		</style>
		<link href="<?php echo $themeroot; ?>style.css" rel="stylesheet" type="text/css" media="screen" />
		<link href="<?php echo $themeroot; ?>style.css" rel="stylesheet" type="text/css" media="print" />
		<link href="<?php echo $themeroot; ?>dojo.css" rel="stylesheet" type="text/css" media="screen" />
		<link href="<?php echo $themeroot; ?>dojo.css" rel="stylesheet" type="text/css" media="print" />
		<style type="text/css">
			<?php echo $display->style; ?>
		</style>

		<script type="text/javascript" src="<?php echo $scriptroot; ?>/dojo/dojo.js" djConfig="parseOnLoad: true"></script>
		<script type="text/javascript" src="<?php echo $scriptroot; ?>/dojo/irondata_dojo.js" djConfig="parseOnLoad: true"></script>

		<script type="text/javascript" src="<?php echo $scriptroot; ?>/custom.js"></script>
		<script type="text/javascript">
			var webroot = '<?php echo $webroot;?>';
			var id = '<?php echo $this->id;?>';
			<?php echo $display->js;?>
		</script>
		<?php if ($display->js_href): ?>
			<script type="text/javascript" src="<?php echo $webroot; ?>modules/active/<?php echo $display->js_href; ?>"></script>
		<?php endif; ?>
	</head>
	<body class='tundra'>
		<div class="container">
			<div style="height: 0px;">&nbsp;</div>
			<div class="container2">
				<h1>Irondata</h1>
				<p class='h1attach'>Open Source Data Warehousing.</p>

				<?php echo $display->top; ?>
				<?php echo $main; ?>
			</div>
			<div class="footer">
				<div class="footer_container">
					<p>Irondata version &#946; 0.1</p>
					<p>IronData is an open source data warehouse system licensed under the <a href="http://www.gnu.org/licenses/gpl-3.0.txt">GNU GPL</a>.</p>
<!-- 					<p>All logos and trademarks relating to IronData are the property of <a href="http://www.lgsolutions.com.au/">Looking Glass Solutions</a>.</p> -->
				</div>
			</div>
		</div>
	</body>
</html>