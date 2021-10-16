#!/usr/bin/perl
# Author:    Pieterjan  Montens <gnaeus@gnaeus_app.willy.manor>
# Created:   Fri Aug 6 13:34:38 2010 +0000
# Description: Parse and store documents in the database
#
# 1) Get a text
# 2) Parse it
# 3) Register in database

# docs 	:id | numac | pub_date | prom_date | type | source | version
# raws 	:id | numac | pub_date | raw_fr | raw_nl | version
# text 	:id | numac | ln | raw | pure
# titles:id | numac | ln | raw | pure

# Docs, Text, Titles, Sources, Types

use DBI;
use HTML::TreeBuilder;
use HTML::FormatText;
use Text::Unaccent;
use Parallel::ForkManager;
use Encode;
# Fix for docker environment
# use lib '/agent/moniteur_import';
use lib '.';
use Page;
use Autocorrect;
use utf8;
$|=1;
my $version=16;
my $sleep_int=25;
my $loop_num=1;

## Database init
$db = $ENV{'DB_DATA'};
$host = $ENV{'DB_HOST'};
$login = $ENV{'DB_USER'};
$mdp = $ENV{'DB_PASSWORD'};
$port = $ENV{'DB_PORT'};
my $dsn = "DBI:MariaDB:database=$db;port=$port;host=$host";
my $pm = new Parallel::ForkManager($loop_num);

## DICT SETUP ##############
############################
my $nl_file =  './nl_dict';
my $fr_file =  './fr_dict';
$nl_dict = new Autocorrect('nl',$nl_file);
$fr_dict = new Autocorrect('fr',$fr_file);

# Boucle Principale
my $counter= 0;
do {
	# 1) On obtient le texte
    my $dbh = DBI->connect( $dsn, $login, $mdp, {PrintError => 0, RaiseError => 0}
        ) or die "Db Connection Error";

	my $sql = "select raw_pages.numac from raw_pages 
			   left join docs on raw_pages.numac=docs.numac 
			   where docs.numac is null
			    union
			   select raw_pages.numac from raw_pages 
			   left join docs on raw_pages.numac=docs.numac 
			   where docs.version != $version
			   limit 0, 1000";

	my $sth = $dbh->prepare($sql);
	$sth->execute() or die "$DBI::errstr $sql\n";
	while (my ($numac) = $sth->fetchrow_array())
		{
        $pm->start and next;
        my $dbh = DBI->connect( $dsn, $login, $mdp, {PrintError => 0, RaiseError => 0}
            ) or die "Db Connection Error";

		my $sql = "select pub_date, raw_fr, raw_nl from raw_pages
				   where numac = $numac";
		my $sth = $dbh->prepare($sql);
		$sth->execute() or die ("$DBI::errstr for $numac\n Sql:$sql\n");
		my ($pub_date,$raw_fr, $raw_nl) = $sth->fetchrow_array();

        #Encode::_utf8_on($raw_fr);
        #Encode::_utf8_on($raw_nl);

        if ($pub_date eq '')
            {
            print "No content for $numac found \n";
            next;
            }

		my %data = makeDataObject($raw_fr,$raw_nl,$numac,$pub_date);

		# 3) On l'enregistre

		my $sql_doc = "insert into docs(numac,pub_date,prom_date,type,source,version,anonymise) values
					(?,?,?,?,?,$version,?) on duplicate key update 
						pub_date = ?,
						prom_date = ?,
						type = ?,
						source = ?,
						version = $version";

		my $sql_txt = "insert into text(numac,ln,raw,pure, length) values
					(?,?,?,?,?) on duplicate key update raw = ?, pure = ?, length = ?";

		my $sql_title = "insert into titles(numac,ln,raw,pure) values
					(?,?,?,?) on duplicate key update raw = ?, pure = ?";

		my $sql_lang = "update docs set languages = concat_ws(',',languages,?) where numac = ?";

		my $sth_doc = $dbh->prepare($sql_doc);
		my $anonymise = "0";

		if ( $data{norm_title_fr} =~ m/(loi accordant des naturalisations|relative aux noms et pr|reserve de recrutement|article 770|exercer la profession de detective prive|recrutement|etrangers)/i
		   ||$data{norm_title_nl} =~ m/(betreffende de namen en voornamen|wet die naturalisaties verleent|Samenstelling van een wervingreserve|artikel 770|het beroep van prive|aanwerving|vreemdelingen)/i)
			{
		    $anonymise = "1";
			print "ANON            Doc $data{numac} anon bit wil be set\n";
			}
		
		$sth_doc->execute(
			# Insert part
			$data{numac},$data{pub_date},$data{prom_date},
			getType($dbh,$data{type_nl},$data{type_fr}),
			getSource($dbh,$data{source_nl},$data{source_fr}), $anonymise,
			# Update part
			$data{pub_date},$data{prom_date},
			getType($dbh,$data{type_nl},$data{type_fr}),
			getSource($dbh,$data{source_nl},$data{source_fr})) or die "$DBI::errstr";

		my $sth_txt = $dbh->prepare($sql_txt);
			$sth_txt->execute($data{numac},'fr',$data{raw_fr},$data{norm_fr},length($data{norm_fr})
							 ,$data{raw_fr},$data{norm_fr},length($data{norm_fr})) or die "$DBI::errstr text fr";
			$sth_txt->execute($data{numac},'nl',$data{raw_nl},$data{norm_nl},length($data{norm_nl})
							 ,$data{raw_nl},$data{norm_nl},length($data{norm_nl})) or die "$DBI::errstr text nl";

		my $sth_title = $dbh->prepare($sql_title);
		my $sth_lang  = $dbh->prepare($sql_lang);
		if (!$data{raw_title_fr} eq '')
				{
				$sth_title->execute($data{numac},'fr',$data{raw_title_fr},$data{norm_title_fr},$data{raw_title_fr},$data{norm_title_fr}) or die "$DBI::errstr";
				$sth_lang->execute('fr',$data{numac}) or die "$DBI::errstr";
				}
		if (!$data{raw_title_nl} eq '')
				{
				$sth_title->execute($data{numac},'nl',$data{raw_title_nl},$data{norm_title_nl},$data{raw_title_nl},$data{norm_title_nl}) or die "$DBI::errstr";
				$sth_lang->execute('nl',$data{numac}) or die "$DBI::errstr";
				}

        $pm->finish;
		}

    #print "\nWaiting for children..\n";
    $pm->wait_all_children;
	$counter++;
	if ($counter%$sleep_int == 0)
		{
		sleep(1);
		print "z";
		}
	print "+\n" if ($counter%2 == 0);

	$sql = "SELECT count( * ) AS count FROM raw_pages LEFT JOIN docs ON raw_pages.numac = docs.numac WHERE docs.numac IS NULL or docs.version < $version";
    my $dbh = DBI->connect( $dsn, $login, $mdp, {PrintError => 0, RaiseError => 0}
        ) or die "Db Connection Error";
	$sth = $dbh->prepare($sql);
	$sth->execute() or die "$DBI::errstr";
	my ($count) = $sth->fetchrow_array;
	if ($count == 0)
		{
		print "\n Nothing left to parse. See ya.\n\n";
		exit 0;
		}
	else
		{
		print "\n $count to go !!\n";
		}
} while (1 == 1);
exit 0;

## Functions
sub makeDataObject
	{
	my ($pagecontent,$raw_nl,$numac,$pub_date) = @_;

	my $tree_fr = HTML::TreeBuilder->new_from_content($pagecontent); # empty tree
	my $tree_nl = HTML::TreeBuilder->new_from_content($raw_nl); # empty tree


	$plain_fr = HTML::FormatText->new->format($tree_fr);
	$plain_nl = HTML::FormatText->new->format($tree_nl);

    ## Extracting Meta Content
    %pagedata=();
    $pagedata{"raw_fr"} = $plain_fr;
    $pagedata{"norm_fr"} = normalize($plain_fr);
    $pagedata{"raw_nl"} = $plain_nl;
    $pagedata{"norm_nl"} = normalize($plain_nl);

    $pagedata{"pub_date"} = $pub_date;
    $pagedata{"numac"} = $numac;

    $page = new Page ($pagecontent,$numac,$pub_date,'fr', $fr_dict);
	$page_nl = new Page ($raw_nl, $numac, $pub_date,'nl', $nl_dict);

	$pagedata{"prom_date"} = $page->getPromDate();
	$pagedata{"prom_date"} = $page->getPromDate() if ($page_nl->getPromDate() eq '--');
	$pagedata{"prom_date"} = $page_nl->getPromDate() if ($page->getPromDate() eq '--');
	$pagedata{"prom_date"} = '0000-00-00' if ($page->getPromDate() eq '--');

    $pagedata{"raw_title_fr"} = $page->getTitle();
    $pagedata{"norm_title_fr"} = normalize($page->getTitle());
	$pagedata{"raw_title_nl"} = $page_nl->getTitle();
	$pagedata{"norm_title_nl"} = normalize($page_nl->getTitle());

    $pagedata{"type_fr"} = $page->getType(0);
    $pagedata{"type_nl"} = $page_nl->getType(0);
    $nl_index = $page_nl->typeIndex($pagedata{"type_nl"});
    $fr_index = $page->typeIndex($pagedata{"type_fr"});
    $pagedata{"source_fr"} = $page->getSource();
    $pagedata{"source_nl"} = $page_nl->getSource();


    if (($nl_index ne $fr_index) 
        and $pagedata{"raw_title_fr"} ne "" 
        and $pagedata{"raw_title_nl"} ne "")
        {
        $new_nl = "";
        $new_fr = "";
        if (($fr_index < $nl_index and $fr_index != 0) or $nl_index == 0)
            {
            $new_nl = $page_nl->getTypeByIndex($fr_index);
            $new_fr = $pagedata{"type_fr"};
            }
        else
            {
            $new_fr = $page->getTypeByIndex($nl_index);
            $new_nl = $pagedata{"type_nl"};
            }

        print "  TYPE CORR --- $numac || NL($nl_index):".$pagedata{"type_nl"}." => $new_nl, FR($fr_index):".$pagedata{"type_fr"}." => $new_fr\n";
        print "                NL title: ".substr(normalize($page_nl->getTitle()),0,90)."\n";
        print "                FR title: ".substr(normalize($page->getTitle()),0,90)."\n";
        $pagedata{"type_fr"} = $new_fr;
        $pagedata{"type_nl"} = $new_nl;
        }
    if ($pagedata{"type_fr"} eq $pagedata{"type_nl"} 
        and $pagedata{"type_nl"} eq 'document' )
        {
        print "          DOCUMENT      $numac has been set as beign a document\n";
        print "                        NL title: ".substr(normalize($page_nl->getTitle()),0,90)."\n";
        print "                        FR title: ".substr(normalize($page->getTitle()),0,90)."\n";
        }
    if ($pagedata{"type_fr"} eq 'nulnulnulnul' or $pagedata{"type_nl"} eq 'nulnulnulnul')
        {
        print "  NULNUL       $numac has nulnulnulnul title\n";
        die;
        }

	return %pagedata;
	}

sub normalize
    {
    my $txt = shift;
	return '' if ($txt eq '');

    my $txt_utf8 = Encode::is_utf8($txt) ? $txt : encode("UTF-8",$txt);
    if (unac_string("UTF-8", $txt_utf8))
        {
        $txt_utf8 = unac_string("UTF-8", $txt_utf8);
        }
    else
        {
        print "Following string can't unaccent:\n\t" . $txt ."\n\n\n";
        die
        }
    $txt = decode_utf8($txt_utf8);
    
	#$txt_utf16 = encode("UTF16",$txt_raw);
	#$txt_unac = unac_string_utf16($txt_utf16);
    #$txt = decode("UTF16",$txt_unac);
    $txt = lc($txt);
    $txt =~ s/\s/ /g;
    $txt =~ s/\w'//g;
    $txt =~ s/[^A-Za-z0-9\.;:\- ]//g;
    return $txt;
    }

sub getSource
	{
	my ($dbh, $src_nl, $src_fr) = @_;
	my $sth = $dbh->prepare("select id from sources where source_nl = ? and source_fr = ?");
	$sth->execute($src_nl,$src_fr) or die "$DBI::errstr";
	if (my ($num) = $sth->fetchrow_array())
		{
		return $num;
		}
	
	$sth = $dbh->prepare("insert into sources (source_nl, source_fr) values (?,?)");
	$sth->execute($src_nl,$src_fr) or die "$DBI::errstr";

	sleep(2);
	$sth = $dbh->prepare("select id from sources where source_nl = ? and source_fr=?");
	$sth->execute($src_nl,$src_fr) or die "$DBI::errstr";
	my ($num) = $sth->fetchrow_array();
	return $num;
	}

sub getType
	{
	my ($dbh, $src_nl, $src_fr) = @_;
	my $sth;
	my $notype;
	if (($src_nl ne "notype") ne ($src_fr ne "notype"))
		{
		$notype=1;
		my $value = ($src_nl eq "notype") ? $src_fr : $src_nl;
		my $lang = ($src_nl eq "notype") ? "fr" : "nl";
		$sth = $dbh->prepare("select id from types where type_$lang = ?");
		$sth->execute($value) or die "$DBI::errstr";
		}
	else
		{
		$notype=0;
		$sth = $dbh->prepare("select id from types where type_nl = ? and type_fr= ?");
		$sth->execute($src_nl,$src_fr) or die "$DBI::errstr";
		}

	if (my ($num) = $sth->fetchrow_array())
		{
		return $num;
		}
	
	$sth = $dbh->prepare("insert into types (type_nl, type_fr) values (?,?)");
    $src_nl_utf8 = $src_nl;
    $src_fr_utf8 = $src_fr;
	$sth->execute($src_nl_utf8,$src_fr_utf8) or die "$DBI::errstr";

	my $sth = $dbh->prepare("select id from types where type_nl = ? and type_fr = ?");
	$sth->execute($src_nl_utf8, $src_fr_utf8) or die "$DBI::errstr";
	my ($num) = $sth->fetchrow_array();
	return $num;
	}
   

#### Peter Norvig simple spelling corrector
#### Perl 5 by reddit user palsecam
sub dedup { %h = map { $_, 1 } @_; keys %h }

sub edits1 {
  $word = shift;
  &dedup(map { ($a, $b) = @$_;
           ( $a . substr($b, 1),
             $a . substr($b, 1, 1) . substr($b, 0, 1) . substr($b, 2),
             map { ($a . $_ . substr($b,1), $a . $_ . $b) } 'a'..'z' )
    } map { [substr($word, 0, $_), substr($word, $_)] } 0..length($word)-1);
}

sub edits2 { &dedup(map { &edits1($_) } &edits1(shift)) }

sub correct {
  $win = shift;
  for (&edits1($win), &edits2($win)) {
 $win = $_ if ($NWORDS{$_} > $NWORDS{$win});
  }
  $win;
}
