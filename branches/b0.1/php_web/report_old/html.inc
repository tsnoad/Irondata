<?php
	$conf = parse_ini_file("../conf.ini", true);
?>
<html>
	<head>
<?php
	/* Mostly used for redirection from the logout page */
	echo $meta
?>     
		<title><?php echo $conf['company']['title']?></title>
		<link href="style.css" rel="stylesheet" type="text/css" media="screen" /> 
		<script type="text/javascript" src="javascript/scriptaculous/prototype.js"></script>
		<script type="text/javascript" src="javascript/scriptaculous/scriptaculous.js"></script>
		<script type="text/javascript" src="javascript/common.php"></script>
		<script type="text/javascript" src="javascript/colourpicker.js"></script>
		<style type="text/css">@import url(javascript/jscalendar/skins/aqua/theme.css);</style>
		<script type="text/javascript" src="javascript/jscalendar/calendar.js"></script>
		<script type="text/javascript" src="javascript/jscalendar/lang/calendar-en.js"></script>
		<script type="text/javascript" src="javascript/jscalendar/calendar-setup.js"></script>
	</head>
	<body>
    	<h2><?php echo $conf['company']['title']?></h2>
        <div id='menu'>
<?php
	if (!$_SESSION['username']) {
    	echo "<form method='post'>
		<h3>Please enter your username and password</h3>
        	<label for='username'>Username: </label><input type='text' name='username' id='username'><br>
            <label for='password'>Password: </label><input type='password' name='password' id='password'><br><br>
            <input type='submit' value='Logon'><br>
        	</form>
            ";
    }
    echo "<p><span id='err'>".$_SESSION['msg']."</span>";
    $_SESSION['msg'] = "";
    /* If the user is not logged in don't do anything below */
     if ($_SESSION['username']) {
?>
        </div>
		
         <div id='main'>
			
			<div class='sidemenu'>
				<div class='sidetitle'>
<?php
echo "<a href='index.php'><img onmouseover='javascript:this.src=\"images/home_hover.png\";' onmouseout='javascript:this.src=\"images/home.png\";' border='0' src='images/home.png' alt='Reports' title='Reports'></a> ";
		if ($_SESSION['admin'] == "t" || $_SESSION['functions']['Administration Functions']['access'] == "t") {
			echo "<a href='admin.php'><img onmouseover='javascript:this.src=\"images/admin_hover.png\";' onmouseout='javascript:this.src=\"images/admin.png\";' border='0' src='images/admin.png' alt='Administration' title='Administration'></a> ";
		}
		echo "<a href='scheduler.php'><img onmouseover='javascript:this.src=\"images/calendar_hover.png\";' onmouseout='javascript:this.src=\"images/calendar.png\";' border='0' src='images/calendar.png' alt='Scheduling' title='Scheduling'></a> ";
		echo "<a href='logout.php'><img onmouseover='javascript:this.src=\"images/logout_hover.png\";' onmouseout='javascript:this.src=\"images/logout.png\";' border='0' src='images/logout.png' alt='Logout' title='Logout'></a> <br/>";
?>
					<h3><?php echo $menutitle?></h3>
				</div>
				<div class='navbody'>
<?php
	if ($menustyle == "Admin") {
		echo adminSidebar();
	} else if ($menustyle == "None") {
		echo "";
	} else {
		echo reportSidebar();
	}
?>
				</div>
			</div>
			
			<div class='maincontent'>
				
				<div class='maintitle'>
<?php
		echo "<span style='position: relative; float: right'>";
                if ($saveicons) {
                        echo "<a href='".$saveicons."'><img onmouseover='javascript:this.src=\"images/save_hover.png\";' onmouseout='javascript:this.src=\"images/save.png\";' border='0' src='images/save.png' alt='Save' title='Save'></a> ";
                        echo "<img src='images/seperator.png' alt='seperator'> ";
                }
                if ($pdficons) {
			echo "<a href='".$pdficons."'><img onmouseover='javascript:this.src=\"images/pdf_hover.png\";' onmouseout='javascript:this.src=\"images/pdf.png\";' border='0' src='images/pdf.png' alt='Download PDF' title='Download PDF'></a> ";
                        echo "<img src='images/seperator.png' alt='seperator'> ";
                }
		/* There is a possiblity of a URL hack attack, ie guess at report_id.
		 * This will be fixed later
		 */
		if ($mainicons) {
			if ($_SESSION['admin'] == "t" || $_SESSION['functions']['Run Report Templates']['access'] == "t" || $_SESSION['reports'][$_GET['saved_report']]['owner']==$_SESSION['username']) {
				echo "<a href='".$mainiconURL."&command=run_report'><img onmouseover='javascript:this.src=\"images/run_hover.png\";' onmouseout='javascript:this.src=\"images/run.png\";' border='0' src='images/run.png' alt='Run' title='Run'></a> ";
			}
			if ($_SESSION['admin'] == "t" || $_SESSION['functions']['Create Report Templates']['access'] == "t") {
				echo "<a href='".$mainiconURL."&command=new'><img onmouseover='javascript:this.src=\"images/new_hover.png\";' onmouseout='javascript:this.src=\"images/new.png\";' border='0' src='images/new.png' alt='New' title='New'></a> ";
			}
			if ($_SESSION['admin'] == "t" || $_SESSION['functions']['Edit Report Templates']['access'] == "t" || $_SESSION['reports'][$_GET['saved_report']]['owner']==$_SESSION['username']) {
				echo "<a href='".$mainiconURL."&command=edit_template'><img onmouseover='javascript:this.src=\"images/edit_hover.png\";' onmouseout='javascript:this.src=\"images/edit.png\";' border='0' src='images/edit.png' alt='Edit' title='Edit'></a> ";
			}
			if ($_SESSION['admin'] == "t" || $_SESSION['functions']['Delete Report Templates']['access'] == "t" || $_SESSION['reports'][$_GET['saved_report']]['owner']==$_SESSION['username']) {
				echo "<a href='#' onclick='javascript:userCheck(\"".$mainiconURL."&command=delete_template\", \"Are you sure you want to delete this object.\\nThis action is irreversible.\")'><img onmouseover='javascript:this.src=\"images/delete_hover.png\";' onmouseout='javascript:this.src=\"images/delete.png\";' border='0' src='images/delete.png' alt='Delete' title='Delete'></a> ";
			}
		}
		echo "</span>";
		echo "<b class='title'>".$maintitle."</b>";
?>
				</div>
				<div class='mainbody'>
<?php
	     if ($_SESSION['username']) {
	    	echo $main;
	    }
    }
?>     
			</div>
			</div>
		</div>
        <br/>
        <br/>
        <br/>
	</body>
</html>
