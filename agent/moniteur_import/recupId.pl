#!/usr/bin/perl
# Author:    Pieterjan  Montens <gnaeus@gnaeus_app.willy.manor>
# Created:   Fri Aug 6 13:34:38 2010 +0000
# Description: Document ID getter for belgian official journal
#
# 1) generate date list
# 2) filter out done dates and get 10 dates 
# 3) Per date, get Id's and record

use DateTime;
use LWP::Simple;
use LWP::UserAgent;
use HTTP::Request;
use HTTP::Response;
use DBI;
$| = 1;
use constant TRUE => 1;
use constant FALSE => 0;


############################ Conf Data
$dates_to_parse=400;
$version = 1;

## Database init
$db = $ENV{'DB_DATA'};
$host = $ENV{'DB_HOST'};
$login = $ENV{'DB_USER'};
$mdp = $ENV{'DB_PASSWORD'};
$port = $ENV{'DB_PORT'};
my $dsn = "DBI:MariaDB:database=$db;port=$port;host=$host";
my $dbh = DBI->connect(
    $dsn,
    $login,
    $mdp,
    {PrintError => 0, RaiseError => 0}
) or die "Db Connection Error";

############ Generate date list
print "Getting Done Dates...";
$request = "Select * from done_dates";
my $sth = $dbh->prepare($request);
$sth->execute();
my @done_dates=();

while (my @data = $sth->fetchrow_array()) 
	{
           push(@done_dates,$data[0]);
    }

my $count = @done_dates;
print " $count dates done.\n";

# First date 3/6/1997
print "Generating DateList...\n";
my $start_dt = DateTime->new(year => 1997, month => 6, day => 3);
my $end_dt = get_last_date();

printf("First date : %u-%02u-%02u\n",$start_dt->year(),$start_dt->month(),$start_dt->day());
printf("Last date : %u-%02u-%02u\n",$end_dt->year(),$end_dt->month(),$end_dt->day());

my @list = ();

my %done_dts = map {$_ => 1} @done_dates; # create a hash out of the array values

for (my $dt = $start_dt->clone();$dt <= $end_dt; $dt->add(days=>1))
	{
	$date = sprintf("%u-%02u-%02u",$dt->year(),$dt->month(),$dt->day());
	if (!exists($done_dts{$date}))
		{
		push @list, $dt->clone();
		}
	}
$count = @list;
print " $count dates made \n";

############################ Filter done dates and get 10 of them
print "Getting some work to do...";
@dates_to_do = ();
for (my $cnt = 1;$cnt <= $dates_to_parse; $cnt++)
	{
	my $date = @list[$cnt-1];
	push(@dates_to_do,$date) if ($date);
	}
$count = @dates_to_do;
print " $count dates to parse\n";

############################ Per date, get Id's and record
print "Work Loop started:\n";

## Browser init
my $browser = LWP::UserAgent->new();
$browser->timeout(30);
my $request = '';
my $response = '';
my $contents = '';

foreach (@dates_to_do)
	{
	printf "	Doing %s \n", $_->ymd('/');

	############ Extracting ID
	print "		Extracting ids...";
	$dt_string = sprintf("http://www.ejustice.just.fgov.be/cgi/summary_body.pl?language=%s&pub_date=%u-%02u-%02u",
							'fr',$_->year(),$_->month(),$_->day());
	$request = HTTP::Request->new(GET => $dt_string);
	$response = $browser->request($request);
	if ($response->is_error()) {printf ("	Erreur connexion:%s\n", $response->status_line); next;}
	$contents = $response->content();
	my @ids=();
	while ($contents=~ m/name=(\d{10})/gi)
		{
		push(@ids,$1);
		}
	$count = @ids;
	print " $count ids\n";
		
	########### Recording ID
	print "		Recording ids...";
	foreach $id (@ids)
		{
		$request = sprintf("Insert into raw_ids (doc_id, date, version) values (%u, '%s', %u)",
			$id, 
			sprintf("%u-%02u-%02u",$_->year(),$_->month(),$_->day()),
			$version
			);
		my $sth = $dbh->prepare($request);
		$sth->execute();
		}
	print " done\n";

	######### Setting date done.
	$request = sprintf("Insert into done_dates (date) values ('%s')",
			sprintf("%u-%02u-%02u",$_->year(),$_->month(),$_->day()));
	my $sth = $dbh->prepare($request);
	$sth->execute();

	} 

print "Work Loop done. Quitting\n";
exit;


sub in_array
 {
     my ($arr,$search_for) = @_;
     my %items = map {$_ => 1} @$arr; # create a hash out of the array values
     return (exists($items{$search_for}))?1:0;
 }

sub get_last_date
{
	my $browser = LWP::UserAgent->new();
	$browser->timeout(30);
	my $url = 'http://www.ejustice.just.fgov.be/cgi/summary_body.pl?language=fr&pub_date=';
	my $request = HTTP::Request->new(GET => $url);
	my $response = $browser->request($request);
	if ($response->is_error()) {printf ("	Erreur connexion:%s\n", $response->status_line); return FALSE;}
	my $content = $response->content();
	$content =~ /(?<year>\d{4})-(?<month>\d{2})-(?<day>\d{2})<\/a>/;

	return DateTime->new(year => $+{year}, month => $+{month}, day => $+{day});
}
