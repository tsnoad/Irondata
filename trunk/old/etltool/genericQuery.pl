#!/usr/bin/perl
#
# udpated - 13/02/2006
# ETL tool for Engineers Australia
# By Looking Glass Solutions - www.lgsolutions.com.au
#

# 
use DBI;
use XML::Simple;
use Switch;

$cfg = XMLin('./config-gen.xml');
use Data::Dumper;

#DEBUG INFO
#print Dumper($cfg);
#print Dumper($trf);

#CONNECT TO INPUT DB
my $dbh = DBI->connect($cfg->{sybase}->{dsn}, $cfg->{sybase}->{user}, $cfg->{sybase}->{password}, { syb_err_handler => \&err_handler });
die "Unable to connect to Source Database"
	unless $dbh;

    my $rc;
    my $sth;
	my $i = 0;
    #select db to use
    $dbh->do("use ". $cfg->{sybase}->{db});
    
#    print "\n####### START QUERY #######\n";
#    print $ARGV[0];
#    print "\n\n";

    #---run SQL
    $sth = $dbh->prepare($ARGV[0]);
    if($sth->execute) {
        if ($sth->rows == 0 ) {
            print "Error: Query returned 0 rows\n";
        } else {
        #Loop for each row returned

		$first = 1;

        while (my $hash = $sth->fetchrow_hashref) {
            %newData = ();
            $newData = $hash;
            %tables = ();
            while (($key,$value) = each(%$newData)) {
				if ($first == 1) {
					print $key."\t";
					$firsttmp .= $value."\t";
					$i = 1;
				} else {
					print $value."\t";
				}
            	#$cols .= " ". $key. ",";
            	#$vals .= " '". $value. "',";
				#print $key . " - " . $value . " :::\n";
            }
			$i++;
			$first = 0;
			print "\n";
			if ($firsttmp) {
				print $firsttmp."\n";
			}
			$firsttmp = "";
        } #end while row
            
        #code to write data back goes here...
            
    } #end if query executed
} #end foreach query


#####################################
sub rtrim ($) {
	my $str = shift;
	$str =~ s/\s+$//;
	$str =~ s/,$//;
	return $str;
}

sub in_array() {
    my $val = shift(@_);
 
    foreach $elem(@_) {
        if($val eq $elem) {
            return 1;
        }
    }
    return 0;
}
