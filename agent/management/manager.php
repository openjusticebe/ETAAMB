#!/usr/bin/php
<?php 
// Parsing options
$DEL_UNUSED_TEXTS = false;
$SET_ANON		  = false;
$ANONYMISE		  = false;
$DETECT_ANON	  = false;
$CLEAN_TYPES	  = false;

$numac_array	  = array();

foreach ($argv as $arg)
	{
	if (preg_match('#\d{10}#',trim($arg)))
		array_push($numac_array,trim($arg));
		
		
	switch ($arg)
		{
		case 'del_unused': $DEL_UNUSED_TEXTS = true; break;
		case 'set_anon': $SET_ANON = true; break;
		case 'anonymise': $ANONYMISE = true; break;
		case 'detect_anon': $DETECT_ANON = true; break;
		case 'clean_types': $CLEAN_TYPES = true; break;

		case 'help'	: 
			echo "
Etaamb DB Content Management Interface
-------------------------------------

  del_unused      remove unused texts 
  set_anon        search & set anonymised texts
  anonymise		  set anon bit on particular numac
  detect_anon     search for texts to anonymise
  clean_types     search and clean document types for correspondance

";
			die;
			break;
		}
	}


// Doc Precalc Module
chdir(dirname(__FILE__));

define('CLASS_DIR','/etaamb/classes/');
$_SERVER['HTTP_HOST'] = '127.0.0.1';
require_once('/etaamb/config.php');
require_once('/etaamb/config.default.php');
require_once(CLASS_DIR.'observer.class.php');
require_once(CLASS_DIR.'connector.class.php');
require_once(CLASS_DIR.'collection.class.php');
require_once(CLASS_DIR.'default_page.class.php');
require_once(CLASS_DIR.'numac.class.php');
require_once(CLASS_DIR.'anoner.class.php');
require_once(CLASS_DIR.'text_renderer.class.php');
require_once(CLASS_DIR.'parser.class.php');


$connector = new connector_class();
$connector->setConfig($DB_CONFIG);
$observer = observer::getInstance();


if ($DEL_UNUSED_TEXTS)
	{
	echo "Deleting unused texts...\n";
	$sql = "delete text from text left join titles using (numac,ln) where titles.numac is null;";
	$connector->exec($sql);
	$sql = sprintf("delete from render_cache where version != %s",numac::render_cache_version());
	$connector->exec($sql);
	}

if ($SET_ANON)
	{
	echo "Set anon bit... \n";
	$anon_titles = array(
			    '%accordant des naturalisations%' 			// Loi accordant des naturalisation
			   ,'%accordant les naturalisations%' 
			   ,'%die de naturalisaties verleent%'
			   ,'%die naturalisaties verleent%'
			   ,'Naturalisations Par acte législatif%' 
			   ,'Naturalisaties Bij wetgevende akte%'

			   ,'%namen en voornamen%' 						// Changement de patronyme
			   ,'%namen en de voornamen%' 
			   ,'%naamsverandering%'
			   ,'%Naamsverandering%'
			   ,'%Changement de nom%'
			   ,'%changement de nom%'

			   ,'%resultats selection%'						// Résultats, liste de recrutement Selor
			   ,'%resultaten selectie%'
			   ,'%uitslagen samenstelling%'
			   ,'%resultats constitution%'
			   ,'constitution une reserve de recrutement%'
			   ,'samenstelling van een wervingsreserve%'
			   ,'%generieke test%'
			   ,'%test generique%'
               ,'toelatingen tot de stage%'
               ,'admissions au stage%'

			   ,'vergunning om het beroep van privedetective%' // Autorisation de detective privé
			   ,'autorisation exercer la profession de detective prive%'

			   ,'burgerlijke eretekens%'					// Décorations civiques
			   ,'decorations civiques%'

			   ,'forces armees%'							// Forces armées
			   ,'krijgsmacht%'
			   ,'%demission de militaires du cadre de reserve%'
			   ,'%ontslag van militairen van het reservekader%'
			   );

	foreach ($anon_titles as $anon)
		{
		$sql = sprintf("select distinct titles.numac from `titles`
						join docs on titles.numac = docs.numac 
						where pure like '%s' and anonymise = false
						and docs.anonymise = false",$anon);
		$numacs = $connector->query($sql,Q_FLAT);
		if (!empty($numacs))
			{
			printf("Setting anon bit and removing cached renders for texts: %s\n",implode(', ',$numacs));
			set_anon($connector, $numacs);
			remove_cache($connector, $numacs);
			}
		}
	}

if ($ANONYMISE)
	{
	foreach ($numac_array as $numac)
		{
		echo "Setting anon bit for $numac.\n";
		$sql = "update docs set anonymise = true where docs.numac = '$numac'";
		$connector->exec($sql);

		echo "Removing stored renders for $numac.\n";
		$sql = "delete from render_cache where numac = '$numac'";
		$connector->exec($sql);
		}

	echo "Anon bit set.\n";
	}

function set_anon($db,$numacs)
	{
	$sql = sprintf("update docs set anonymise = true where docs.numac in ('%s') ",implode("', '",$numacs));
	$db->exec($sql);
	}

function remove_cache($db,$numacs)
	{
	$sql = sprintf("delete from render_cache where numac in ('%s') ", implode("', '",$numacs));
	$db->exec($sql);
	}

if ($DETECT_ANON)
	{
	echo "Enter year to analyse:";
	$year = preg_replace('#\D+#','',fgets(STDIN));
	$sql = sprintf("select numac from docs where anonymise = false and YEAR(prom_date) = %d",$year);
	$numacs = $connector->query($sql,Q_FLAT);
	$count  = count($numacs);
	echo "Texts count for year $year is $count\n";

	echo " 0        50     100%\n ";

	$i=0;
	$check_table = array();
	foreach ($numacs as $numac)
		{
		$text = '';
		$sql = sprintf('select docs.numac as numac, text.pure as text from
				docs join text on docs.numac = text.numac
				where docs.numac = %s and text.ln=\'fr\'',$numac);
		$rawdata = $connector->query($sql);
		if (!isset($rawdata[0]) || !isset($rawdata[0]['text']))
			continue;
		$text = $rawdata[0]['text'];
		$score = anoner::badwords_test($text);
		$wordcount = strlen($text);
		if ($score > $wordcount/4)
			array_push($check_table,$numac);

		$i++;
		if (($i/$count)*100 > 5)
			{
			echo '#';
			$i=0;
			}
		}
	if (!empty($check_table))
		{
		echo "URL's that could be checked:\n";
		foreach ($check_table as $numac)
			printf("http://%s/%d\n",DOMAIN,$numac);
		}
	else	
		echo "No Url's to check\n";
	echo "\nDone.\n";
	}

if ($CLEAN_TYPES)
	{
/*
SELECT concat( ',', id, ' => "', type_nl, '/', type_fr, '/', id, '"' ) FROM `types`
mysql -uroot -proot moniteur < sql > output
*/
	echo "Cleaning types.\n";
	$sql 	= "select id, type_nl, type_fr from `types`";
	$types = $connector->query($sql);
	$ref_table = array();
	$ref_table_rev = array();
	$table_nl = array();
	$table_nl_rev = array();
	$table_fr = array();
	$table_fr_rev = array();
	foreach ($types as $type)
		{
		$id = $type['id'];
		$fr = $type['type_fr'];
		$nl = $type['type_nl'];
		$table_fr_rev[$id] = $fr;
		$table_nl_rev[$id] = $nl;

		if (isset($table_fr[$fr])) array_push($table_fr[$fr],$id);
		else $table_fr[$fr] = array($id);

		if (isset($table_nl[$nl])) array_push($table_nl[$nl],$id);
		else $table_nl[$nl] = array($id);

		$ref_table_rev[$nl.'/'.$fr] = $id;
		$ref_table[$id] = $nl.' / '.$fr;
		}

	$skip = false;
	$skipto = 0;
	startofanalyse:
	foreach($ref_table as $id => $phrase)
		{
		if ($skip && $skipto > $id) continue;
		$output = sprintf("\n -- Analysing %s (%d)\n",$phrase, $id);
		$nl = $table_nl_rev[$id];
		$fr = $table_fr_rev[$id];

		$nl_list = $table_nl[$nl];
		$fr_list = $table_fr[$fr];
		$fr_alts = count($fr_list);
		$nl_alts = count($nl_list);
		if ($fr_alts + $nl_alts >= 3)
			{
			$i=1;
			$output .= sprintf(" Possible alternatives:\n");
			$dones = array();
			if (count($fr_list) > 1)
				{
				foreach ($fr_list as $alt_id)
					{
					$output .= sprintf(" %d) %s\n",$alt_id,$ref_table[$alt_id]);
					$dones[] = $alt_id;
					}
				}
			if (count($nl_list) > 1)
				{
				foreach ($nl_list as $alt_id)
					{
					if (!in_array($alt_id,$dones))
						{
						$output .= sprintf(" %d) %s\n",$alt_id,$ref_table[$alt_id]);
						$dones[] = $alt_id;
						}
					}
				}
			echo $output;
			$continue = true;
			while ($continue)
				{
				echo "\n";
				printf("Enter your choice > ");
				$choice = preg_replace('#\s#','',fgets(STDIN));
				switch ($choice)
					{
					case 'help' :
						echo 
' possible options:
   view     - display the current conflict
   list     - list links of documents using type
   skip		- skip to type id #
   custom   - enter custom type selection mode
   find		- find a type by entering a word
   a number - use the selected type
   exit		- quit tool'."\n";
   						break;
					case 'view' : echo $output;break;
					case 'skip' :
						$skip = true;
						printf("Enter Id to skip to > ");
						$skipto = preg_replace('#\s#','',fgets(STDIN));
						goto startofanalyse;
						break;
					case 'list': 
						get_type_list($connector,$dones);
						break;
					case 'custom': 
						make_custom($connector,$id,$phrase);
						goto choiceloop;
						break;
					case 'find':
						find_type($connector);
						break;
					case 'exit' : die;
					default :
						if (preg_match("#^\d{1,}$#",$choice))
							$continue = false;
					}
				}

			if ($choice != $id)
				{
				printf("-- Replacing type %s (%d) with %s (%d)... ","$nl/$fr",$id,$ref_table[$choice],$choice);
				replace_type($connector,$id,$choice);
				}
			choiceloop:
			}
		}
	}

function find_type($conn)
	{
	printf("Enter type you ar looking for> ");
	$find = preg_replace('#(^\s*|\s*$)#','',fgets(STDIN));
	$res = $conn->query(sprintf("select id from types where type_nl like '%s' or type_fr like '%s'",$find,$find),Q_FLAT);
	foreach ($res as $id)
		{
		$data = $conn->query(sprintf("select id, type_nl, type_fr from types where id='%s'",$id));
		$data = $data[0];
		$fr = $data['type_fr'];
		$nl = $data['type_nl'];
		$id = $data['id'];
		printf("%d) %s / %s \n",$id, $nl, $fr);
		}
	}
function get_type_list($conn,$ids)
	{
	$sql = "select docs.numac as numac from docs  where docs.type = '%s'";
	foreach ($ids as $id)
		{
		$numacs = $conn->query(sprintf($sql,$id),Q_FLAT);
		//if (count($numacs) > 30)
		//	{
		//	printf("- Type %s has more then 30 results (%d).\n",$id,count($numacs));
		//	continue;
		//	}
		printf("- Type %s links (%d):\n",$id,count($numacs));
		$i=0;
		foreach ($numacs as $numac)
			{
			if ($i++ > 3) continue;
			$nl_title = $conn->query(sprintf("select pure as title from titles where ln='nl' and numac='%s'",$numac),Q_FLAT);
			$nl_title = isset($nl_title[0]) ? $nl_title[0] : 'false';
			$fr_title = $conn->query(sprintf("select pure as title from titles where ln='fr' and numac='%s'",$numac),Q_FLAT);
			$fr_title = isset($fr_title[0]) ? $fr_title[0] : 'false';
			printf("  http://%s/fr/%s   - %s / %s\n",DOMAIN,$numac,substr($nl_title,0,40),substr($fr_title,0,40));
			}
		echo "\n";
		}
	}

function make_custom($conn,$id,$phrase)
	{
	printf("Enter custom type for dutch > ");
	$dutch = preg_replace('#(^\s*|\s*$)#','',fgets(STDIN));
	printf("Enter custom type for french > ");
	$french = preg_replace('#(^\s*|\s*$)#','',fgets(STDIN));
	printf("Really replace \"%s\" with \"%s / %s\" (y|n) ?",$phrase,$dutch,$french);
	$choice = preg_replace('#\s#','',fgets(STDIN));
	switch ($choice)
		{
		case "y":
			$newid = new_type($conn,$french,$dutch);
			replace_type($conn,$id,$newid);
			break;
		case "n":
			break;
		}
	return;
	}

function new_type($conn,$fr,$nl)
	{
	$sql = sprintf("insert into types (type_nl,type_fr) values ('%s','%s')",$nl,$fr);
	$conn->exec($sql);
	$res = $conn->query(sprintf("select id from types where type_nl like '%s' and type_fr like '%s'",$nl,$fr),Q_FLAT);
	return $res[0];
	}

function replace_type($conn,$id,$newid)
	{
	$sql = sprintf("update docs set `type` = '%d' where `type` = '%d'", $newid, $id);
	$conn->exec($sql);

	$sql = sprintf("delete from `types` where id = '%d' ", $id);
	$conn->exec($sql);
	printf("Replaced type %s with new type %s\n",$id,$newid);
	return;
	}
