<html>
	<head>
		<title>Procast Reporting</title>
		<style type="text/css">
			@import "<?php echo $scriptroot;?>dijit/themes/tundra/tundra.css";
			@import "<?php echo $scriptroot;?>dojo/resources/dojo.css";
		</style>
		<link href="<?php echo $themeroot; ?>style.css" rel="stylesheet" type="text/css" media="screen" /> 
		<style type="text/css">
			<?php echo $style; ?>
		</style>
		<script type="text/javascript" src="<?php echo $scriptroot; ?>/dojo/dojo.js" djConfig="parseOnLoad: true"></script>
		<script type="text/javascript" src="<?php echo $scriptroot; ?>/custom.js"></script>
		<script type="text/javascript">
			dojo.require("dojo.parser");
			dojo.require("dijit.layout.LayoutContainer");
			dojo.require("dijit.layout.ContentPane");
			dojo.require("dijit.layout.AccordionContainer");
			dojo.require("dijit.form.TextBox");
			dojo.require("dijit.form.Textarea");
			dojo.require("dijit.form.CheckBox");
			dojo.require("dijit.form.FilteringSelect");
			dojo.require("dijit.form.Button");
			dojo.require("dijit.Dialog");
			dojo.require("dojo.dnd.Source");
		</script>
	</head>
	
	<body class='tundra'>
	<div id='layout_container' dojoType='dijit.layout.LayoutContainer'>
    <div dojoType="dijit.layout.ContentPane" layoutAlign="top" id="layout_header">
        <a href="<?php echo $webroot; ?>"><img align="left" src="<?php echo $webroot;?>themes/default/images/logo.png" /></a>
	<div id="menu_header" >
	<div id="menu" class="menu">

	</div>
	</div>
    </div>


	    	<!-- The MENU element -->
		<div id='menu' dojoType='dijit.layout.ContentPane' layoutAlign='left'>
			<?php echo $menu; ?>
		</div>

		<div id='layout_body' dojoType='dijit.layout.LayoutContainer' layoutAlign='client'>
			<div id='core_top' dojoType='dijit.layout.ContentPane' layoutAlign='top'>
				<?php echo $top; ?>
			</div>
			<div id='core_main' dojoType='dijit.layout.ContentPane' layoutAlign='client'>
				<?php echo $main; ?>
			</div>
		</div>
	</div>
	<div id='layout_block' dojoType='dijit.Dialog' bgColor='white' bgOpacity='0.5' toggle='fade' toggleDuration='250'>
	</div>

	</body>
</html>
