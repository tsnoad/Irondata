<?php

$front = '<?xml version="1.0" encoding="iso-8859-1"?>
<request>
%SESSION%
	<module name="core">
		<command name="menu" />
		<command name="introducton" />
	</module>
</request>';

$submit = '<?xml version="1.0" encoding="iso-8859-1"?>
<request>
%SESSION%
<module name="%MOD%">
%QUERY%
</module>
</request>';

?>
