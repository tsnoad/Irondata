#!/usr/bin/perl
#
# udpated - 16/02/2006
# ETL tool for Engineers Australia
# By Looking Glass Solutions - www.lgsolutions.com.au
#
#
use strict;

use DBI;
use XML::Simple;
#use Switch;
use Data::Dumper;
use FileHandle;
use Time::HiRes qw( usleep ualarm gettimeofday tv_interval );
# Added by Evan for Historical Reasons
use Time::Local;
use Storable;
use File::Slurp;

my $external_insert = undef();
my $historical_insert = undef();
my $historical_date = undef();
my $log_dir = 'Logs/';

my $threshold = 1;
sub db { 
	my $level = shift; 
	my @message = @_; 
	if ($level <= $threshold) {
		if ($level != 0) {
			print "\t";
		}
		print @message; print "\n"
	}; 
}


# Config default
$ENV{LANG}="C";

if ( scalar(@ARGV) > 4 ) {
	print "
etltool.pl [--external] [--historical date] /path/to/transforms/

etltool has changed again.  The config and transforms files have been merged, and wrapped in the xml tags <TransformSet></TransformSet>.

The transforms directory must look like this:
/blah/whatever/transforms/:
		transforms1.xml
		transforms2.xml
		transforms3.xml

Each transforms file must contain a config section and a transforms section.
";
exit;
}
if ( $ARGV[0] =~ /--external/ ) {
	$external_insert = shift @ARGV;
}

# Run the inserts on historical data 
if ( $ARGV[0] =~ /--historical/ ) {
        $historical_insert = shift @ARGV;
        $historical_date = shift @ARGV;
}

my $transforms_dir = shift @ARGV;
if ( !(-d $transforms_dir)) {
	print "You must give etltool the name of a directory.  $transforms_dir is not a directory.\n";
}

my ($errlog, $actionlog, $timelog);
my $transforms_dir_log = $transforms_dir;
$transforms_dir_log =~ s/\///g;
open ($errlog, '>>', $log_dir.'errorlog.txt') or warn "Couldn't open errorlog.txt for writing: $@$!\n"; #Open LOG file for any errors
open ($actionlog, '>', $log_dir.'actionlog.txt') or warn "Couldn't open actionlog.txt for writing: $@$!\n"; #Open LOG file for all actions
open ($timelog, '>>', $log_dir.'timelog.txt') or warn "Couldn't open timelog.txt for writing: $@$!\n"; #Open LOG file for all actions
=item Reads the list of transforms out of an XML file
=cut

#Returns a list of the files that we need to read for transform information.
#We process each file in turn
sub get_transforms 
	{
		db 1, "Scanning $transforms_dir for transforms\n";
		my @transform_sets = `ls $transforms_dir | grep xml`;
		@transform_sets = sort @transform_sets;
		db 2, "Found these transforms:\n @transform_sets\n";

		chomp foreach @transform_sets;
		return @transform_sets;
	}
=item queries

Returns a list of the queries to be processed.

Takes the HoH returned by XML simple

=cut

	sub queries
		{	
			my $trf = shift;
			if (ref($trf->{input}) ne "ARRAY") {
				$trf->{input} = [$trf->{input}];
			}
			return @{$trf->{input}};
		}
=item insert_timestamp

Inserts a timestamp into the timestamp table (specified in the config xml file).  This exact timestamp is used for all insertions while this program runs.
=cut

	sub insert_timestamp
		{
			my $dbh = shift;
			my $timestamp = shift;
			my $cfg = shift;
			if ($cfg->{timestamp}) 
				{
					my $insert_stamp = "INSERT INTO ". $cfg->{timestamp}->{table}. " (". $cfg->{timestamp}->{field}. ") VALUES ('$timestamp');";
					my $sth_out = $dbh->prepare($insert_stamp);
					db 3, "Preparing tiemstamp:". $insert_stamp ;
					if ($sth_out->execute) 
						{ db 1, "Timestamp " . $timestamp . " inserted into table " . $cfg->{timestamp}->{table} . ", field ".$cfg->{timestamp}->{field}. " \n";}
						else {db 1, "ERROR Inserting timestamp information - $DBI::errstr"; }
				}
				else { print "No timestamp field in config file - inserts will not be timestamped correctly\n";}
		}
=item final_queries

Takes a list of queries and runs them
=cut

sub final_queries {
	my $dbh = shift;
	my @queries = @_;
	
	db 1, "Running Queries";
	my @childs;
	foreach my $query(@queries) {
		my $pid = fork();
		if ($pid) {
			push(@childs, $pid);
		} elsif ($pid == 0) {
			db 1, "Query:\t" . $query ;
			# Set warning handler
			my $saved_warn_handler = $SIG{__WARN__};
			$SIG{__WARN__} = sub {};
			my $child_dbh = $dbh->clone();
			$SIG{__WARN__} = $saved_warn_handler;
			$dbh->{InactiveDestroy} = 1;
			undef $dbh;

		        my $sth_out = $child_dbh->prepare($query);
			if (!$sth_out->execute) {
				print "ERROR updating deleted items - $DBI::errstr in query $query\n";
			}
			exit(0);
		} else {
			die "couldn't fork\n";
		}

	}
	foreach (@childs) {
		waitpid($_, 0);
	}
}
=item source_dbh

Takes the familiar xml::simple HoH and opens a conntection to the source database, returning the handle.
=cut

	sub source_dbh
		{
			my $cfg = shift;
			my $password = $cfg->{password};
			#if (scalar(keys(%$password)) < 1) {$password = ""}
			my $db_err_handler;
			if ( $cfg->{dsn} =~ /Sybase/ ) {$db_err_handler = { syb_err_handler => \&err_handler }}
			if ( $cfg->{dsn} =~ /Pg/ ) {$db_err_handler = {}}
			#CONNECT TO INPUT DB
			my $dbh;
			# Anydata is the CSV driver for files
			if ( $cfg->{dsn} =~ /AnyData/ ) { 
				$dbh = DBI->connect($cfg->{dsn});
				my @files = (ref($cfg->{file})=~/ARRAY/)?@{$cfg->{file}}:($cfg->{file});
				foreach my $file ( @files ) {
					# We default to CSV if the config file doesn't specify
					if ( ! ( ($file->{format} eq "Pipe" ) || ($file->{format} eq "CSV" ) ) )
						{$file->{format} = "CSV"}
					db 2, "Using ". $file->{format} ." (DBD::AnyData) driver";
					db 1, "Loading table ". $file->{tablename} . " from file ". $file->{filename} ;
					my $contents = read_file( $file->{filename} );
					if ( $file->{prefilter} ) {eval '$contents =~ '.$file->{prefilter}. ';' ;}
					db 3, "Transformed file: ". $contents;
					$dbh->func( $file->{tablename}, $file->{format}, [$contents], {col_names => $file->{columns}}, 'ad_catalog' )
				}
			} else {
 				$dbh = DBI->connect($cfg->{dsn}, $cfg->{user}, $password, $db_err_handler); 
 			}
			die "Unable to connect to Source Database $@$! $DBI::errstr\n" unless $dbh;
			#$dbh->{Profile} = 1;
			#CONNECT TO OUTPUT DB
			return $dbh
		}
=item target_dbh

Opens a connection to the target database(using the XML::Simple HoH), based on config in config.xml
=cut

	sub target_dbh 
		{
			my $cfg = shift;
			my $dsn = $cfg->{target}->{dsn};
			my $user = $cfg->{target}->{user};
			my $password = $cfg->{target}->{password};
			#if (scalar(keys(%$password)) < 1) {$password = ""}
		
			my $dbh_out;
			if ( $password && !(ref($password) =~ /HASH/) )
				{ $dbh_out = DBI->connect( $dsn, $user, $password);}
				else { $dbh_out = DBI->connect( $dsn, $user);}
			die "Unable to connect to Target Database $@$! $DBI::errstr\n" unless $dbh_out;
			return $dbh_out;
		}
sub build_foreign_query
	{
		my $qry = shift;
		if ($qry->{inserttype} eq "foreign") 
			{
				my %foreign_query;	
				my @foreign_tables = super_split(";", $qry->{foreign});
				my $foreign_table;
				my $foreign_column;
				my @foreign_rows;
				my @foreign_columns;
				foreach $foreign_table (@foreign_tables) 
					{
						@foreign_rows = super_split("=", $foreign_table);
						my $id = $foreign_rows[0]."_id";
						$foreign_query{$id} ="(SELECT ";
						$foreign_query{$id} .= $id." FROM ".$foreign_rows[0]." WHERE ";
						@foreign_columns = split(",", $foreign_rows[1]);
						foreach $foreign_column (@foreign_columns) 
							{ $foreign_query{$id} .= " ".$foreign_column." = '%".$foreign_column."%' and"; }
						$foreign_query{$id} =~ s/and$//;
						$foreign_query{$id} .= " LIMIT 1) ";
						#print $foreign_query{$id}." ".$id." \n";
					}
				return \%foreign_query;
			}
		return undef;
		
	}
=item transforms

Extracts transforms from the qry node in xml::simple and returns them as a list

=cut

sub transforms
	{
		my $qry = shift;
		if ($qry->{transform})
			{
				if (ref($qry->{transform}) ne "ARRAY") 
					{ $qry->{transform} = [$qry->{transform}]; }
				return @{$qry->{transform}};
			}
		return ();
	}
=item rename_action

Renames a column as it is transferred to the target database
=cut
sub rename_action 
	{
		my $transform = shift;
		my $source_data = shift;
		my $mapping = {};	
		my $out_data = {};
		foreach my $col (keys %$source_data)
			{$mapping->{$col} = $col;}
		$mapping->{$transform->{in}} = $transform->{out};

		foreach my $col ( keys %$mapping )
			{ $out_data->{$mapping->{$col}} = $source_data->{$col};}
		return $out_data;
	}
=item join_action

This transform will take the contents of the columns you specify and join them together.  It will insert something like a comma between the joined data, based on what you put in the config file.
=cut
sub join_action
	{
		my $transform = shift;
		my $source_data = shift;
		my $out_data = {}  ;	
		my $out_col_name = $transform->{out};
		foreach my $colname ( super_split(",",$transform->{in}) )
			{ if (!exists($source_data->{$colname})) {print "Error!  Column name -->$colname<-- not found in source table!\n"; exit(1);}}
		my $out_col_data = join ($transform->{action},
			map { trim($source_data->{$_}) } super_split(",",$transform->{in}));
		#Copy all the columns from the source row into the target row, leaving the columns we just transformed
		foreach my $col (keys %$source_data) 
			{ 
				do { $out_data->{$col} = $source_data->{$col} }; # unless member_of ($col, super_split(",",$transform->{in})) 
			}
						
		#Add our shiney new column to the row
		$out_data->{$out_col_name} = $out_col_data;
		return $out_data;
	}
=item add_action

Add set an column to a value.  Note it will overwrite values in an existing column
=cut

sub add_action {
	my $transform = shift; 
	my $source_data = shift;
	my $out_data = Storable::dclone($source_data);
	$out_data->{$transform->{out}} = $transform->{value};
	return $out_data;
}
=item external_add_action

Add set an column to a value from an external source.  Note it will overwrite values in an existing column
=cut

sub external_add_action {
	my $transform = shift; 
	my $source_data = shift;
	my $dbh_sub = shift;
	my $out_data = Storable::dclone($source_data);

	$transform->{value} =~ /%(.*)%/;
	my $where = $1;
	my $whereval = $out_data->{$where};
	my $regvalue = $transform->{value};
	$regvalue =~ s/%(.*)%/$whereval/;
#	print "debug:\n=====\n",$regvalue,"\n=====end\n";
	if ($regvalue) {
		my $sth_sub = $dbh_sub->prepare($regvalue);
# print $actionlog $transform->{value}, "\n";
		my $results = $sth_sub->execute();
		my $result  = $sth_sub->fetch();
		$out_data->{$transform->{out}} = $result->[$where];
	} else {
		$out_data->{$transform->{out}} = undef;
	}
	return $out_data;
}

=item super_split

Just like split, but it always returns a list.  If perl's split doesn't find anything to match in its input, this version will return a list with one member, the original input.
=cut

sub super_split
	{
		my $delim = shift;
		my $str = shift;
		my @res = split($delim, $str);
		if (@res) {return @res};
		return ($str);
	}
sub delete_action
	{
		my $transform = shift; my $source_data = shift;
		my $out_data = Storable::dclone($source_data);
		my @delete_cols = super_split(',',$transform->{in});
		foreach my $col (@delete_cols) 
			{ delete $out_data->{$col}; }
		return $out_data;
	}
=item regex_action

Transforms a column and potentially puts it into another column
=cut

sub regex_action
	{
		my $transform = shift; 
    my $source_data = shift;
		my $out_data = Storable::dclone($source_data);
		db 3, "Source data is: ", Dumper($out_data);
		db 3, "Transform data is: ", Dumper($transform);
		my $temp = $out_data->{$transform->{in}};
		if ($temp || $temp == 0) 
			{
				db 3, "Transforming data: $temp";
				eval ('$temp =~ '. $transform->{action}. ';'); #runs the regex
				db 3, "Transformed data looks like: $temp";
				delete ($out_data->{$transform->{in}});
				$out_data->{$transform->{out}} = $temp;
				db 3, "Out data is: ", Dumper($out_data);
			}
		return $out_data;
	}
=item split_action

Splits one column into multiple columns, deletes the original column
=cut

sub split_action
	{
		my $transform = shift; my $source_data = shift;
		my $out_data = Storable::dclone($source_data);
		my @data = super_split($transform->{action},$source_data->{$transform->{in}});
		my @cols_out = split(',',$transform->{out});
		delete($out_data->{$transform->{in}});
		for (my $i=0; $i < @cols_out; $i++) 
			{ $out_data->{$cols_out[$i]} = $data[$i]; }
		return $out_data;
	}
sub drop_action
	{
		my $transform = shift; my $source_data = shift;
		my $out_data = Storable::dclone($source_data);

		my $temp = $source_data->{$transform->{in}};
		eval ('if ($temp =~ '. $transform->{action}. ') {
			$deleted_count++;
			$out_data = {};
			}');
		return undef;
		return $out_data;
	}
	
sub build_prepare_query 
	{
		my $qry = shift;
		my $source_data = shift;
		my $cfg = shift;
		my $tmptable;
		if ($cfg->{tmp} eq 'false') {
			$tmptable = $qry->{defaulttable};
		} else {
			$tmptable = $qry->{defaulttable} . "_tmp";
		}

		my @cols = ();
		my @vals = ();
		while (my ($col,$val) = each(%$source_data)) {
			push @cols, $col;
			push @vals, "?";
 		}
		
		push @cols, "start_date";
		push @vals, "?";
		my $temp = "INSERT INTO ". $tmptable . " (".join (",", @cols) .  ") VALUES (". join(",", @vals) . ");";
		db 3, "Prepare query is: ". $temp;
		return $temp;
	}
sub build_insert_query
	{

		my $qry = shift;
		my $timestamp = shift;
		my $source_data = shift;
		# Obselete Code: We use table_start_date now
		my @foreign_cols = ();
		my @foreign_vals = ();
		if ($qry->{inserttype} eq "foreign") {
			my %temp = &build_foreign_query($qry);
			while (my ($col,$val) = each( %temp )) {
				push @foreign_cols, $col;
				push @foreign_vals , $val;
			}
		}
		my @vals = ();
		#print "Working on ", Dumper $source_data, "\n";
		while (my ($col,$val) = each(%$source_data)) {
			if ( $val eq '' ) {
				push @vals, undef;
			} else {
				#$val =~ s/\\/\\\\/g;
				#$val =~ s/'/''/g;
				$val = trim($val);
				push @vals, $val;
			}
 		}
		
		push @vals, "$timestamp";
		return @vals;
	}

sub transform {	
	my $row_in_progress = shift;
	my $dbh_sub = shift;
	my @transforms = @_;
	db 2, "I have all these transforms to do: @transforms";
	foreach my $do (@transforms) { 
		foreach my $switch ( $do->{type} )  {
			db 2, "Performing transform: '$switch'";
			$switch =~ m"join" && do { $row_in_progress = join_action( $do, $row_in_progress);};
			$switch =~ m"split" && do {$row_in_progress = split_action( $do, $row_in_progress);};
			$switch =~ m"drop" && do {$row_in_progress = drop_action( $do, $row_in_progress);};
			$switch =~ m"regex" && do {$row_in_progress = regex_action($do, $row_in_progress); };
			$switch =~ m"delete" && do {$row_in_progress = delete_action($do, $row_in_progress); };
			$switch =~ m"rename" && do {$row_in_progress = rename_action( $do, $row_in_progress) };
			$switch =~ m"add" && do { $row_in_progress = add_action( $do, $row_in_progress) }; 		
			$switch =~ m"external_add" && do {$row_in_progress = external_add_action( $do, $row_in_progress, $dbh_sub);}; 
		} #end switch			
		
		#If row has been dropped dont continue running the transforms
		if (!$row_in_progress) { last; } 
	} #end foreach transform
	return $row_in_progress;
}

sub process_rows {
	my ($sth, $cfg, $timestamp, $qry, $dbh_out, $log_dir) = @_;
	my $query_start_time = [gettimeofday()];
	my $results_chunk  = $sth -> fetchall_arrayref({}, 10000);
	my $numrows = 10000;
	my $sqllog;
	my $sth_out;
	my $prepare_statement;
	my $complete_statement = "";
	my $complete_values = "";
	#Write inserts to a file for an external program to run later
	open ($sqllog, '>>', $log_dir.'tmpsql.txt') or warn "Couldn't open tmpsql.txt for writing: $@$!\n"; 
	while ( ( $results_chunk ) && ( scalar(@$results_chunk) > 0 )  ) {
		timestamp("", $qry->{transname}, $query_start_time, "Source Query");

		$query_start_time = [gettimeofday()];
		
		my $dbh_sub = undef;
		foreach my $check (transforms($qry)) {
			foreach my $switch ( $check->{type} )  {
				if ($switch =~ m"external_add") {
					$dbh_sub = source_dbh($check);
				}
			}
		}

		foreach my $row_of_data (@{$results_chunk}) {
			db 3, "Processing row: ", Dumper $row_of_data;
			my $transformed_row = transform ($row_of_data, $dbh_sub, transforms($qry));
			if ( $transformed_row ) {
				if (!$prepare_statement) {
					$prepare_statement = build_prepare_query($qry, $transformed_row, $cfg);
					$sth_out = $dbh_out->prepare($prepare_statement) or print $errlog "\nError: $DBI::errstr in prepare query " . $prepare_statement;
				}
				my @insert_statement = build_insert_query($qry, $timestamp, $transformed_row);
				$complete_values = join("','", @insert_statement);
				$complete_statement = $prepare_statement;
				$complete_statement =~  s/\([?,]+\)/\('$complete_values'\)/;

				if ( $external_insert ) { 
					print $sqllog $complete_statement, "\n";
				} else {
					print $actionlog $complete_statement, "\n";
					$sth_out->execute(@insert_statement) or print $errlog "\nError: $DBI::errstr in insert query " . $complete_statement;
				}
			}
		} #end row loop
		#if ( $sth->{Active} )
			#{ $results_chunk  = $sth -> fetchall_arrayref({}, 100000);}
		#else
			#{ $results_chunk = undef;}
		$results_chunk = undef;
		if ($numrows < $sth->rows) {
			$results_chunk = $sth -> fetchall_arrayref({}, 10000);
			$numrows += 10000;
		}
	}
	timestamp("", $qry->{transname},  $query_start_time, "Transform");
}


sub timestamp {
	my ($transform_set, $transname, $query_start_time, $description) = @_;
	my $date = `date`;
	chomp $date;
	print $timelog $date, " - ", $transform_set, " - ", $transname, " - ", $description, " -  ", tv_interval( $query_start_time), "\n";
}

db 0, "***** INITIALISE EXTRACT *****";
my @transform_sets = get_transforms();

my $run_start_time = [gettimeofday()];

# Changed by Evan for Historical Reasons
# This will now use the date from the command line if requested, otherwise it will use 'time'
my $timestamp;
if ($historical_date) {
	my ($year,$month,$day) =split(/-/,$historical_date);
	my $perlTime = timelocal("00","00","00",$day,$month-1,$year);
	$timestamp = localtime($perlTime); #historical_date;
} else {
	$timestamp = localtime(time);
}

foreach my $transform_set ( @transform_sets ) {
	
	my $query_start_time;
	my $xml_path;
	if (-f $transforms_dir) {
		$xml_path = $transforms_dir;
	} else {
		$xml_path = $transforms_dir."/".$transform_set;
	}
	db 1, "Starting transform set $transform_set";
	db 1, "Loading configs from $xml_path\n";
	my $cfg = XMLin($xml_path)->{config};
	my $trf = XMLin($xml_path)->{transforms};

	db 0, "***** DATABASES *****";
	my $dbh = source_dbh($cfg->{source});
	db 1,  "Connecting to source database " . $cfg->{source}->{dsn} . " as user " . $cfg->{source}->{user} . " with password '" . $cfg->{source}->{password} . "'";

	my $i = 0;
	my $deleted_count = 0;
	my $i_ins = 0;
	my $i_err = 0;
	#my $checked_db = 0;
	my $dbh_out = target_dbh($cfg);
	db 1, "Connecting to target database " . $cfg->{target}->{dsn} . " as user " . $cfg->{target}->{user} . " with password '" . $cfg->{target}->{password} . "'";;

	#$dbh_out->{Profile} = 1;
	insert_timestamp($dbh_out, $timestamp, $cfg);

	# Running Start Queries
	if (ref($trf->{startquery}) ne "ARRAY") {
		$trf->{startquery} = [$trf->{startquery}];
	}
	if ($trf->{startquery}) { final_queries($dbh_out, @{$trf->{startquery}}) }

	#LOOP THROUGH EACH QUERY
	my $updateDelete;
	db 0, "\n***** TRANSFORMATION AND INSERTION *****";
	my @childs;
	foreach my $qry(queries($trf)) {
		my $pid = fork();
		if ($pid) {
			push(@childs, $pid);
		} elsif ($pid == 0) {
			my $start = time();
			#START FORK
			db 1, "Starting:\t\t".$qry->{transname};

			db 3, "Query looks like: ".  $qry->{select};
			
			if ($historical_insert) {
				# Create Historical Queries 
				db 2, "Historical:\t\t".$qry->{transname};
				my $his_i = 0;
				if (ref($qry->{history}) ne "ARRAY") {
					$qry->{history} = [$qry->{history}];
				}
				while ($qry->{history}[$his_i]) {
					my @history_split = split(/[ ]*\"[ ]*/ ,$qry->{history}[$his_i]);
					if ($history_split[0] eq "Replace") {
						my $replace_string = '';
						if ($history_split[2] eq "%tnq") {
							$replace_string = $historical_date;
						}
						if ($history_split[2] eq "%t") {
							$replace_string = "'".$historical_date."'";
						}
						$history_split[1] =~ s/\(/\\\(/g;
						$history_split[1] =~ s/\)/\\\)/g;
						$qry->{select} =~ s/$history_split[1]/$replace_string/g;
						db 3, $qry->{select};
					}
					$his_i++;
				}
				$his_i = 0;
				if ($qry->{incremental}) {
					if (ref($qry->{incremental}) ne "ARRAY") {
						$qry->{incremental} = [$qry->{incremental}];
					}
					# Create Incremental Queries 
					db 2, "Incremental:\t".$qry->{transname};
					while ($qry->{incremental}[$his_i]) {
						my @history_split = split(/[ ]*\"[ ]*/ ,$qry->{incremental}[$his_i]);
						if ($history_split[0] eq "Replace") {
							my $replace_string = '';
							if ($history_split[2] eq "%t") {
								$replace_string = "'".$historical_date."'";
							} elsif ($history_split[2] eq "%m") {
								my $last_max = "select max(start_date) from ".$qry->{defaulttable};
								my $sth_out = $dbh_out->prepare($last_max);
								my $results = $sth_out->execute or print "\nError: $DBI::errstr in select query " . $last_max . "\n";
								my $result  = $sth_out->fetch();
								if ($result->[0]) { 
									$replace_string = $result->[0];
									$history_split[1] =~ s/\(/\\\(/g;
									$history_split[1] =~ s/\)/\\\)/g;
									$qry->{select} =~ s/$history_split[1]/$replace_string/g;
								}
							}
						}
						db 1, $qry->{select};
						$his_i++;
					}
				}
			}
			
			#---run SQL
			$query_start_time = [gettimeofday()];
			timestamp($transform_set, $qry->{transname}, $query_start_time, "Insertion");
			# Set warning handler
			my $saved_warn_handler = $SIG{__WARN__};
			$SIG{__WARN__} = sub {};
			my $child_dbh = $dbh->clone();
			my $child_dbh_out = $dbh_out->clone();
			$SIG{__WARN__} = $saved_warn_handler;
			$dbh->{InactiveDestroy} = 1;
			$dbh_out->{InactiveDestroy} = 1;
			undef $dbh_out;
			undef $dbh;

			my $sth = $child_dbh->prepare($qry->{select});
			my $result = $sth->execute or print "\nError: $DBI::errstr in select query " . $qry->{select} . "\n";
			if($result) { 
				if ($sth->rows == 0 ) { 
					db 1, "Error:\t\t\t".$qry->{transname}." Query returned 0 rows"; 
					exit(0);
				} 
			} else { 
				db 1, "Error:\t\t\t".$qry->{transname}." Query failed"; 
				exit(0);
			}
			db 1, "Transforms:\t\t".$qry->{transname};
			process_rows($sth, $cfg, $timestamp, $qry, $child_dbh_out, $log_dir); 

			if ($external_insert) {
				db 1, "Insertion:\t\t".$qry->{transname};
				my $insertcmd = "psql -d ".$cfg->{target}->{dbname}." -f ".$log_dir."tmpsql-'.$transforms_dir.'.txt";
				`$insertcmd`;
				`rm $log_dir.tmpsql.txt`;
			}
			#END FORK
			my $end = time();
		        my $time_balance = $end - $start;
			print "\tFinish: \t";
	        	if ($time_balance > 60) {
	        	        my $number = $time_balance / 60;
	        	        my $rounded = sprintf("%.2f", $number);
	        	        print $rounded;
	        	        print "m";
	        	} else {
	        	        print $time_balance;
	        	        print "s"; 
	        	}

			print "\t".$qry->{transname}."\n";
			exit(0);
		} else {
			die "couldn't fork\n";
		}
	} #end foreach query

	#Wait for the forks to finish.
	foreach (@childs) {
		waitpid($_, 0);
	}
	# Updated deleted items
	#my @toDelete = split(/,/, $trf->{tables});
	#print "\n";
	#foreach my $deleteQuery ( @toDelete ) {
	#	$updateDelete = "update ".$deleteQuery." set end_date='".$timestamp."' where touched_date != '".$timestamp."' and end_date='1/1/3000';";
	#	db 1, "Set Deleted Record - ".$deleteQuery."\n";
	#	my $sth_out = $dbh_out->prepare($updateDelete);
	#	if (!$sth_out->execute) {
	#		print "ERROR updating deleted items - $DBI::errstr";
	#	}
	#}
	# Running Final Queries
	if ($trf->{endquery} && ref($trf->{endquery}) ne "ARRAY") {
		$trf->{endquery} = [$trf->{endquery}];
	}
	if ($trf->{endquery}) { final_queries($dbh_out, @{$trf->{endquery}}) }

	#Update timestamp competion flag
	my $endquery = "UPDATE ".$cfg->{timestamp}->{table}. " SET complete='t' WHERE timestamp_id='".$timestamp."';";
	my $sth_out = $dbh_out->prepare($endquery);
	$sth_out->execute;

	# VACUUM RUNS OUT OF MEMORY
	#if ( $cfg->{target}->{dsn} =~ m/Pg/ ) { 
	#	my $vacuumcmd = "psql ".$cfg->{target}->{dbname}." -c \"VACUUM FULL ANALYZE\"";
	#	my $vacuum = `$vacuumcmd`;
	#}
	$dbh->disconnect();
	if ( $cfg->{target}->{dsn} =~ /Mock/ )
		{ print Dumper $dbh_out->{mock_all_history};}
	$dbh_out->disconnect();

}

db 1, "\n\nExtraction successfully completed...";
db 1, "----------------------------------------";


#####################################
# SUBs
#####################################

sub trim ($) {
	my $str = shift;
	$str =~ s/^\s+//;
	$str =~ s/\s+$//;
	return $str;
}


sub member_of { 
	my $val = shift; my @array = @_;
	foreach my $testval ( @array ) { 
		if ( $val eq $testval ) { return 1;} 
	}
	return undef;
}
