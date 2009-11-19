<html>
	<head>
		<title>IronData</title>
		<link href="style.css" rel="stylesheet" type="text/css" media="screen" /> 
		<script type="text/javascript" src="javascript/common.php"></script>
		<script type="text/javascript">
			djConfig = { isDebug: false };
		</script>
		<script type="text/javascript" src="javascript/dojo/dojo.js"></script>
		<script type="text/javascript">
			dojo.require("dojo.event.*");
			dojo.require("dojo.io.*");
			dojo.require("dojo.widget.*");
			dojo.require("dojo.form.*");
			dojo.require("dojo.lfx.*");
			dojo.addOnLoad(ironDataInit);
			
			function serenityInit() {
				var footer = dojo.byId("footer");
				footer.setAttribute('displayed', 'off');
				
				var body = dojo.byId("body");
				body.setAttribute('displayed', 'off');
			}
		</script>
	</head>
	
	<body>
	<div id='layout_container' dojoType='LayoutContainer'>
		<div id='layout_header' dojoType='ContentPane' layoutAlign='top'>
		    	<h2>IronData</h2>
		    	<!-- The HEADER element -->
			<div id='header' style='display: none;'>
			<!--<span>search: </span><input id='search' type='text' value='' />
			<a href='logout.php'>Logout</a>-->
			</div>
		</div>
		<div id='layout_footer' dojoType='ContentPane' layoutAlign='bottom'>
			<p>&copy; Looking Glass Solutions 2007</p>
		</div>
	    	<!-- The MENU element -->
		<div id='menu' dojoType='AccordionContainer' layoutAlign='left'>
		</div>

		<div id='layout_body' dojoType='LayoutContainer' layoutAlign='client'>
		    	<!-- The FOOTER element -->
			<div id='footer' dojoType='ContentPane' layoutAlign='bottom' style='display: none;'>
			</div>
		    	<!-- The BODY element -->
			<div id='main' dojoType='ContentPane' layoutAlign='client'>
<!-- 
	if ($responseHTML['message']) {
		echo "<p id='element_err'>".$_SESSION['msg']."</p>";
		$_SESSION['msg'] = "";
	}
-->  
			</div>
		</div>
	</div>
	<div id='layout_block' dojoType='dialog' bgColor='white' bgOpacity='0.5' toggle='fade' toggleDuration='250'>
		<a id="layout_block_close" href="#">[X]</a>
		<div id='message' dojoType='ContentPane'>
		</div>
	</div>

	</body>
</html>
