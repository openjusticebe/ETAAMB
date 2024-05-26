#!/usr/bin/perl
# Author:    Pieterjan  Montens <gnaeus@gnaeus_app.willy.manor>
# Created:   Fri Aug 6 13:34:38 2010 +0000
# Description: Raw Page getter
# Changelog:
# Sun May 26 22:07:00 2024 +0001
#   - Adapt to new layout
#
# Db form: id | numac | pub_date | raw_fr | raw_nl
# Get X pages on each run

my $version = 1;
my $page_number = 50;

## Pause between pages
$max_pause = 80;
$min_pause = 30;

use DBI;
use LWP::UserAgent;

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


print "Pageretriever started ".scalar(localtime)."\n";
### Get 10 numacs & pub_dates
$sql = " Select doc_id, date from raw_ids 
		 left join raw_pages on raw_ids.doc_id = raw_pages.numac
		 where raw_pages.version != $version 
		  union
		 Select doc_id, date from raw_ids 
		 left join raw_pages on raw_ids.doc_id = raw_pages.numac
		 where raw_pages.numac is null";

my $sth = $dbh->prepare($sql);
$sth->execute();
my @todo = ();

while (my @data = $sth->fetchrow_array())
	{
	my $res = {};
	$res->{numac} = $data[0];
	$res->{pub_date} = $data[1];
	push @todo,$res;
	}

my $insert = $dbh->prepare("Insert into raw_pages(numac, pub_date, raw_fr, raw_nl, version) values (?,?,?,?,?)");
$sleep = 0;

for my $doc (@todo)
	{
	$numac  = $doc->{"numac"};
	$pub_date = $doc->{"pub_date"};

	print "\tGetting pages for doc $numac, $pub_date..";

	$url_nl = sprintf("https://www.ejustice.just.fgov.be/cgi/article.pl?language=%s&sum_date=%s&lg_txt=%s&caller=sum&numac_search=%s&view_numac=", 'nl', $pub_date, 'n', $numac);

	$url_fr = sprintf("https://www.ejustice.just.fgov.be/cgi/article.pl?language=%s&sum_date=%s&lg_txt=%s&caller=sum&numac_search=%s&view_numac=", 'fr', $pub_date, 'f', $numac);


    $page_nl = getPage($url_nl);
	$page_fr = getPage($url_fr);

	if ($page_nl && $page_fr)
		{
		$insert->execute($numac,$pub_date,$page_fr,$page_nl,$version);
		}
	print " Done\n";


    $sleep = int($min_pause + rand($max_pause - $min_pause));
	printf ("\tSleeping (%s ms)...", $sleep);
    select(undef, undef, undef, $sleep / 1000);
	print " Done\n";
	}

print "\nAll done\n";

## Subs
sub getPage
    {
    my ($url) = @_;
    my $browser = LWP::UserAgent->new();
	$browser->timeout(15);
    my $request = HTTP::Request->new(GET => $url);
	my $response = $browser->request($request);
	if ($response->is_error()) {printf "%s\n", $response->status_line;}
    
    return $response->content();
    }


