#!/usr/bin/perl

use strict ;
use Data::Dumper;
use XML::Simple;
use File::Find;
use File::Path;
use Try::Tiny;
use DBI qw(:sql_types); # pour gérer SQLite
my $running_on_windows = ($^O=~/Win/) ? 1:0;

$|=1;

#unlink('mame.sqlite') if -e 'mame.sqlite';
my $sqlite = DBI->connect('dbi:SQLite:mame.sqlite','','',{ RaiseError => 0, AutoCommit => 0 }) or die("Pas de DB");
$sqlite->do("PRAGMA foreign_keys = ON;") or die "Can't enable foreign_keys pragma";

my $sql;

#create table
# parse_mamelistxml();
parse_mameinfo();
# parse_nplayers();
# parse_story();	
# parse_catver();
# parse_cheats();
# parse_history();
# parse_series();
# parse_command();
# parse_languages();
# parse_bestgames();
# parse_mature();
# parse_genre();

$sqlite->commit();
$sqlite->disconnect();
exit;


###################################################### MAME -LISTXML ###########################################

sub parse_mamelistxml {
	if (!-e 'mame.xml') {
		generate_mamexml();
	}
	print "Parse 'mame.xml'... ";

	use XML::Simple;
	
	# create_tables
	create_table_games();
	create_table_games_biosset();
	create_table_games_rom();
	create_table_games_disk();
	create_table_games_sample();
	create_table_games_chip();
	create_table_games_display();
	create_table_games_control();
	create_table_games_dipswitch();
	create_table_games_adjuster();
	create_table_games_softwarelist();
	create_table_games_ramoption();
	create_table_games_configuration();
	create_table_games_category();
	create_table_games_device();

	#parse XML
	my @xml_game;
	my $in_game				= 0;
	my $game_done			= 0;
	my $dipswitch_id		= 1;
	my $configuration_id	= 1;
	my $category_id			= 1;
	my $device_id			= 1;

	my $sth_game = $sqlite->prepare("INSERT INTO games (name,sourcefile,isbios,runnable,cloneof,romof,sampleof,description,year,manufacturer,sound_channels,input_service,input_tilt,input_players,input_buttons,input_coins,driver_status,driver_emulation,driver_color,driver_sound,driver_graphic,driver_cocktail,driver_protection,driver_savestate) VALUES (".join(',', ('?')x24).")");
	my $sth_biosset = $sqlite->prepare("INSERT INTO games_biosset (game,name,description,'default') VALUES (?,?,?,?)");
	my $sth_rom     = $sqlite->prepare("INSERT INTO games_rom (game,name,bios,size,crc,md5,sha1,merge,region,offset,status,optional) VALUES (".join(',', ('?')x12).")");
	my $sth_disk    = $sqlite->prepare("INSERT INTO games_disk (game,name,md5,sha1,merge,region,'index',status,optional) VALUES (".join(',', ('?')x9).")");
	my $sth_sample  = $sqlite->prepare("INSERT INTO games_sample (game,name) VALUES (?,?)");
	my $sth_chip    = $sqlite->prepare("INSERT INTO games_chip (game,name,tag,'type',clock) VALUES (?,?,?,?,?)");
	my $sth_display = $sqlite->prepare("INSERT INTO games_display (game,'type',rotate,flipx,width,height,refresh,pixclock,htotal,hbend,hbstart,vtotal,vbend,vbstart) VALUES (".join(',', ('?')x14).")");
	my $sth_control = $sqlite->prepare("INSERT INTO games_control (game,'type',ways,minimum,maximum,sensitivity,keydelta,reverse) VALUES (".join(',', ('?')x8).")");
	my $sth_dipswitch = $sqlite->prepare("INSERT INTO games_dipswitch (id,game,name,tag,mask) VALUES (?,?,?,?,?)");
	my $sth_dipvalue  = $sqlite->prepare("INSERT INTO games_dipswitch_dipvalue (dipswitch_id,name,'value','default') VALUES (?,?,?,?)");
	my $sth_adjuster  = $sqlite->prepare("INSERT INTO games_adjuster (game,name,'default') VALUES (?,?,?)");
	my $sth_softwarelist = $sqlite->prepare("INSERT INTO games_softwarelist (game,name,status) VALUES (?,?,?)");
	my $sth_ramoption = $sqlite->prepare("INSERT INTO games_ramoption (game,'value','default') VALUES (?,?,?)");
	my $sth_configuration = $sqlite->prepare("INSERT INTO games_configuration (id,game,name,tag,mask) VALUES (?,?,?,?,?)");
	my $sth_confsetting   = $sqlite->prepare("INSERT INTO games_configuration_confsetting (configuration_id,name,'value','default') VALUES (?,?,?,?)");
	my $sth_category      = $sqlite->prepare("INSERT INTO games_category (id,game,name) VALUES (?,?,?)");
	my $sth_category_item = $sqlite->prepare("INSERT INTO games_category_item (category_id,name,'default') VALUES (?,?,?)");
	my $sth_device = $sqlite->prepare("INSERT INTO games_device (id,game,'type','tag','mandatory','interface') VALUES (?,?,?,?,?,?)");
	my $sth_device_instance  = $sqlite->prepare("INSERT INTO games_device_instance (device_id,name,briefname) VALUES (?,?,?)");
	my $sth_device_extension = $sqlite->prepare("INSERT INTO games_device_extension (device_id,name) VALUES (?,?)");

	open(XML,'<mame.xml') or die "Can't find 'mame.xml' ($!)";
	while(<XML>) {
		chomp;
		if		(/<machine\s+name=/) { # start of xml tag <game>
			$in_game = 1;
			push @xml_game, $_;
		} elsif (/<\/machine>/) { # end of xml tag <game>
			push @xml_game, $_;
			$in_game = 0;

			# parse <game> tag
			my $xml;
			try {
				$xml = XMLin(join("\n",@xml_game));
			};
		
			# default value
			$xml->{'isbios'}				= 'no'			if !exists $xml->{'isbios'};
			$xml->{'runnable'}				= 'yes'			if !exists $xml->{'runnable'};
			$xml->{'input'}->{'service'}	= 'no'			if !exists $xml->{'input'}->{'service'};
			$xml->{'input'}->{'tilt'}		= 'no'			if !exists $xml->{'input'}->{'tilt'};

			$sth_game->execute(
				$xml->{'name'},								$xml->{'sourcefile'},
				yesno2bool($xml->{'isbios'}),				yesno2bool($xml->{'runnable'}),
				$xml->{'cloneof'},							$xml->{'romof'},
				$xml->{'sampleof'},							$xml->{'description'},
				$xml->{'year'},								$xml->{'manufacturer'},
				$xml->{'sound'}->{'channels'},				yesno2bool($xml->{'input'}->{'service'}),
				yesno2bool($xml->{'input'}->{'tilt'}),		$xml->{'input'}->{'players'},
				$xml->{'input'}->{'buttons'},				$xml->{'input'}->{'coins'},
				$xml->{'driver'}->{'status'},				$xml->{'driver'}->{'emulation'},
				$xml->{'driver'}->{'color'},				$xml->{'driver'}->{'sound'},
				$xml->{'driver'}->{'graphic'},				$xml->{'driver'}->{'cocktail'},
				$xml->{'driver'}->{'protection'},			$xml->{'driver'}->{'savestate'}
			) or warn "Can't insert game ".$xml->{'name'}."\n";


			$xml->{'biosset'} = { $xml->{'biosset'}->{'name'} => $xml->{'biosset'} } if exists $xml->{'biosset'}->{'name'} ; # only one element in this hash
			foreach my $biosset_name (keys %{$xml->{'biosset'}}) {
				my $shortcut = $xml->{'biosset'}->{$biosset_name};
				$shortcut->{'default'} = 'no' if !exists $shortcut->{'default'};
				$sth_biosset->execute($xml->{'name'}, $biosset_name, $shortcut->{'description'}, $shortcut->{'default'}) or warn "Can't insert biosset";
			} # end each disk

			$xml->{'rom'} = { $xml->{'rom'}->{'name'} => $xml->{'rom'} } if exists $xml->{'rom'}->{'name'} ; # only one element in this hash
			foreach my $rom_name (keys %{$xml->{'rom'}}) {
				my $shortcut = $xml->{'rom'}->{$rom_name};
				$shortcut->{'status'} = 'good' if !exists $shortcut->{'status'};
				$shortcut->{'optional'} = 'no' if !exists $shortcut->{'optional'};
				$sth_rom->execute(
					$xml->{'name'},			$rom_name,				$shortcut->{'bios'},
					$shortcut->{'size'},	$shortcut->{'crc'},		$shortcut->{'md5'},
					$shortcut->{'sha1'},	$shortcut->{'merge'},	$shortcut->{'region'},
					$shortcut->{'offset'},	$shortcut->{'status'},	yesno2bool($shortcut->{'optional'})
				) or warn "Can't insert rom";
			} # end each rom

			$xml->{'disk'} = { $xml->{'disk'}->{'name'} => $xml->{'disk'} } if exists $xml->{'disk'}->{'name'} ; # only one element in this hash
			foreach my $disk_name (keys %{$xml->{'disk'}}) {
				my $shortcut = $xml->{'disk'}->{$disk_name};
				$shortcut->{'status'} = 'good' if !exists $shortcut->{'status'};
				$shortcut->{'optional'} = 'no' if !exists $shortcut->{'optional'};
				$sth_disk->execute(
					$xml->{'name'},			$disk_name,				$shortcut->{'md5'},
					$shortcut->{'sha1'},	$shortcut->{'merge'}, 	$shortcut->{'region'},
					$shortcut->{'index'},	$shortcut->{'status'},	yesno2bool($shortcut->{'optional'})
				) or warn "Can't insert disk";
			} # end each disk

			$xml->{'sample'} = { $xml->{'sample'}->{'name'} => $xml->{'sample'} } if exists $xml->{'sample'}->{'name'} ; # only one element in this hash
			foreach my $sample_name (keys %{$xml->{'sample'}}) {
				$sth_sample->execute($xml->{'name'}, $sample_name) or warn "Can't insert sample";
			} # end each sample

			$xml->{'chip'} = { $xml->{'chip'}->{'name'} => $xml->{'chip'} } if exists $xml->{'chip'}->{'name'} ; # only one element in this hash
			foreach my $chip_name (keys %{$xml->{'chip'}}) {
				my $shortcut = $xml->{'chip'}->{$chip_name};
				$sth_chip->execute(
					$xml->{'name'}, 		$chip_name, 	$shortcut->{'tag'},
					$shortcut->{'type'}, 	$shortcut->{'clock'}
				) or warn "Can't insert chip";
			} # end each chip

			$xml->{'display'} = [ $xml->{'display'} ] if ref $xml->{'display'} eq 'HASH'; # only one element --> convert to array
			foreach my $display (@{$xml->{'display'}}) {
				$display->{'flipx'} = 'no' if !exists $display->{'flipx'};
				$sth_display->execute(
					$xml->{'name'},						$display->{'type'},			$display->{'rotate'},
					yesno2bool($display->{'flipx'}),	$display->{'width'},		$display->{'height'},
					$display->{'refresh'},				$display->{'pixclock'},		$display->{'htotal'},
					$display->{'hbend'},				$display->{'hbstart'},		$display->{'vtotal'},
					$display->{'vbend'},				$display->{'vbstart'}
				) or warn "Can't insert display";
			} # end each display

	
			if		(ref $xml->{'input'}->{'control'} eq 'HASH') { # only one element in this hash
				$xml->{'input'}->{'control'} = { $xml->{'input'}->{'control'}->{'type'} => $xml->{'input'}->{'control'} };

			} elsif (ref $xml->{'input'}->{'control'} eq 'ARRAY') { # multi control
				my %controls ;
				foreach(@{$xml->{'input'}->{'control'}}) {
					$controls{$_->{'type'}} = $_ ;
				}
				$xml->{'input'}->{'control'} = \%controls;
			}

			foreach my $control_type (keys %{$xml->{'input'}->{'control'}}) {
				my $shortcut = $xml->{'input'}->{'control'}->{$control_type};
				$shortcut->{'reverse'} = 'no' if !exists $shortcut->{'reverse'};
				$sth_control->execute(
					$xml->{'name'},				$control_type,			$shortcut->{'ways'},
					$shortcut->{'minimum'},		$shortcut->{'maximum'},	$shortcut->{'sensitivity'},
					$shortcut->{'keydelta'},	yesno2bool($shortcut->{'reverse'})
				) or warn "Can't insert control";
			} # end each control
	

			$xml->{'dipswitch'} = { $xml->{'dipswitch'}->{'name'} => $xml->{'dipswitch'} } if exists $xml->{'dipswitch'}->{'name'} ; # only one element in this hash
			foreach my $dipswitch_name (keys %{$xml->{'dipswitch'}}) {
				my $shortcut = $xml->{'dipswitch'}->{$dipswitch_name};
				$sth_dipswitch->execute(
					$dipswitch_id, $xml->{'name'}, $dipswitch_name, $shortcut->{'tag'}, $shortcut->{'mask'}
				) or warn "Can't insert dipswitch";

				$shortcut->{'dipvalue'} = { $shortcut->{'dipvalue'}->{'name'} => $shortcut->{'dipvalue'} } if exists $shortcut->{'dipvalue'}->{'name'} ; # only one element in this hash
				foreach my $dipvalue_name (keys %{$shortcut->{'dipvalue'}}) {
					my $shortcut2 = $shortcut->{'dipvalue'}->{$dipvalue_name};
					$shortcut2->{'default'} = 'no' if !exists $shortcut2->{'default'};
					$sth_dipvalue->execute(
						$dipswitch_id, $dipvalue_name, $shortcut2->{'value'}, yesno2bool($shortcut2->{'default'})
					) or warn "Can't insert dipswitch_dipvalue";
				} # end each dipvalue

				$dipswitch_id++;
			} # end each dipswitch


			$xml->{'adjuster'} = { $xml->{'adjuster'}->{'name'} => $xml->{'adjuster'} } if exists $xml->{'adjuster'}->{'name'} ; # only one element in this hash
			foreach my $adjuster_name (keys %{$xml->{'adjuster'}}) {
				my $shortcut = $xml->{'adjuster'}->{$adjuster_name};
				$sth_adjuster->execute($xml->{'name'}, $adjuster_name, $shortcut->{'default'}) or warn "Can't insert adjuster";
			} # end each ajuster

			$xml->{'softwarelist'} = { $xml->{'softwarelist'}->{'name'} => $xml->{'softwarelist'} } if exists $xml->{'softwarelist'}->{'name'} ; # only one element in this hash
			foreach my $softwarelist_name (keys %{$xml->{'softwarelist'}}) {
				my $shortcut = $xml->{'softwarelist'}->{$softwarelist_name};
				$sth_softwarelist->execute($xml->{'name'}, $softwarelist_name, $shortcut->{'status'}) or warn "Can't insert sth_softwarelist";
			} # end each softwarelist


			if (exists $xml->{'ramoption'}) {
				if (ref $xml->{'ramoption'} eq 'ARRAY') { # tow or more ramoption
					my %values_seen = ();
					foreach my $t (@{$xml->{'ramoption'}}) {
						my $value = 0;
						my @params = ();
						if (ref $t eq 'HASH') { # default
							@params = ($xml->{'name'}, $t->{'content'}, 1);
							my $value = $t->{'content'};

						} else { # normal
							@params = ($xml->{'name'}, $t, 0);
							my $value = $t;
						}

						$sth_ramoption->execute(@params) if !exists $values_seen{$value} ; # if first time
						$values_seen{$value} = 1;
					}

				} elsif (ref $xml->{'ramoption'} eq 'HASH') { # only one value
					$sth_ramoption->execute($xml->{'name'}, $xml->{'ramoption'}->{'content'}, 1) or warn "Can't insert ramoption";
				}
			}

			
			$xml->{'configuration'} = { $xml->{'configuration'}->{'name'} => $xml->{'configuration'} } if exists $xml->{'configuration'}->{'name'} ; # only one element in this hash
			foreach my $configuration (keys %{$xml->{'configuration'}}) {
				my $shortcut = $xml->{'configuration'}->{$configuration};
				$sth_configuration->execute(
					$configuration_id, $xml->{'name'}, $configuration, $shortcut->{'tag'}, $shortcut->{'mask'}
				) or warn "Can't insert configuration";

				$shortcut->{'confsetting'} = { $shortcut->{'confsetting'}->{'name'} => $shortcut->{'confsetting'} } if exists $shortcut->{'confsetting'}->{'name'} ; # only one element in this hash
				foreach my $confsetting_name (keys %{$shortcut->{'confsetting'}}) {
					my $shortcut2 = $shortcut->{'confsetting'}->{$confsetting_name};
					$shortcut2->{'default'} = 'no' if !exists $shortcut2->{'default'};
					$sth_confsetting->execute(
						$configuration_id, $confsetting_name, $shortcut2->{'value'}, yesno2bool($shortcut2->{'default'})
					) or warn "Can't insert configuration_confsetting";
				} # end each confsetting

				$configuration_id++;
			} # end each configuration


			$xml->{'category'} = { $xml->{'category'}->{'name'} => $xml->{'category'} } if exists $xml->{'category'}->{'name'} ; # only one element in this hash
			foreach my $configuration (keys %{$xml->{'category'}}) {
				my $shortcut = $xml->{'category'}->{$configuration};
				$sth_category->execute($category_id, $xml->{'name'}, $configuration) or warn "Can't insert category";

				$shortcut->{'item'} = { $shortcut->{'item'}->{'name'} => $shortcut->{'item'} } if exists $shortcut->{'item'}->{'name'} ; # only one element in this hash

				foreach my $item_name (keys %{$shortcut->{'item'}}) {
					my $shortcut2 = $shortcut->{'item'}->{$item_name};
					$shortcut2->{'default'} = 'no' if !exists $shortcut2->{'default'};
					$sth_category_item->execute(
						$category_id, $item_name, yesno2bool($shortcut2->{'default'})
					) or warn "Can't insert category_item";
				} # end each category item

				$category_id++;
			} # end each category


			$xml->{'device'} = [ $xml->{'device'}->{'type'} => $xml->{'device'} ] if ref $xml->{'device'} ne 'ARRAY' ; # only one element in this hash
			foreach my $device (@{$xml->{'device'}}) {
				next if !ref $device;
				my $shortcut = $device;

				next if !$shortcut->{'type'} || !$shortcut->{'tag'} ;

				$sth_device->execute(
					$device_id,			$xml->{'name'},				$shortcut->{'type'},
					$shortcut->{'tag'},	$shortcut->{'mandatory'},	$shortcut->{'interface'}
				) or warn "Can't insert device";

				$shortcut->{'instance'} = { $shortcut->{'instance'}->{'name'} => $shortcut->{'instance'} } if exists $shortcut->{'instance'}->{'name'} ; # only one element in this hash

				foreach my $instance_name (keys %{$shortcut->{'instance'}}) {
					my $shortcut2 = $shortcut->{'instance'}->{$instance_name};
					$sth_device_instance->execute(
						$device_id, $instance_name, $shortcut2->{'briefname'}
					) or warn "Can't insert device_instance";
				} # end each device instance

				foreach my $extension_name (keys %{$shortcut->{'extension'}}) {
					$sth_device_extension->execute($device_id, $extension_name) or warn "Can't insert device_extension";
				} # end each device interface

				$device_id++;

			} # end each device

			printf "\r[%05d] Inserting '%s'                      ", ++$game_done,$xml->{'name'} ;
			@xml_game = ();			

		} else {
			push(@xml_game, $_) if $in_game; # save the stream for futur parsing
		}
	}
	close XML;
	print "ok\n";
}


###################################################### MAMEINFO ###########################################

sub parse_mameinfo {
	if (!-e 'mameinfo.dat') {
		print "'mameinfo.dat' not found\nYou can download it at http://mameinfo.mameworld.info\n";
		return ;
	}
	print "Parse 'mameinfo.dat'... ";

	$sqlite->do("DROP TABLE IF EXISTS 'versions'") or die "Can't drop 'versions' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'versions'	(
	'version'		VARCHAR NOT NULL,
	'date_build'	DATE,
	'games'			INTEGER,
	'delta_games'	REAL,
	'drivers'		INTEGER,
	'info'			TEXT,
	PRIMARY KEY (version)
);
EOT
	$sqlite->do($sql) or die "Can't create 'versions' table";

	# create view 'get_last_version'
	$sqlite->do("DROP VIEW IF EXISTS get_last_version") or die "Can't drop view get_last_version on 'versions' table";
	$sqlite->do("CREATE VIEW get_last_version AS SELECT * FROM versions ORDER BY date_build DESC LIMIT 0,1") or die "Can't create view get_last_version on 'versions' table";

	# create view 'get_first_version'
	$sqlite->do("DROP VIEW IF EXISTS get_first_version") or die "Can't drop view get_first_version on 'versions' table";
	$sqlite->do("CREATE VIEW get_first_version AS SELECT * FROM versions ORDER BY date_build ASC LIMIT 0,1") or die "Can't create view get_first_version on 'versions' table";

	# create index on 'date_build'
	$sqlite->do("CREATE INDEX 'versions_date_build' ON 'versions' ('date_build' ASC)") or die "Can't create index on 'versions' table";

	$sqlite->do("DROP TABLE IF EXISTS 'mameinfo'") or die "Can't drop 'mameinfo' table";
	$sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'mameinfo' (
	'game'			VARCHAR NOT NULL,
	'info'			TEXT,
	'romset_size'	INTEGER,
	'romset_file'	INTEGER,
	'romset_zip'	FLOAT,
	PRIMARY KEY (game)
)
EOT
	$sqlite->do($sql) or die "Can't create 'mameinfo' table";

	my (@infos,$rom_name,$rom_type,$romset_size,$romset_file,$romset_zip);
	my $sth1 = $sqlite->prepare("INSERT INTO versions (version,date_build,games,delta_games,drivers,info) VALUES (?,?,?,?,?,?)");
	my $sth2 = $sqlite->prepare("INSERT INTO mameinfo (game,info,romset_size,romset_file,romset_zip) VALUES (?,?,?,?,?)");
	open(MAMEINFO,'<mameinfo.dat') or die "Can't find 'mameinfo.dat' ($!)";
	while(<MAMEINFO>) {
		chomp;
		# mame version
		if		(/^																				# start of line
					\#																			# commentary
					\s{4}(\S+)\s+																# version '0.122u7'
					(January|February|March|April|May|June|July|August|September|October|November|December)\s+	# month of release
					(\d+)																		# day of release
					(?:st|nd|th)\s+																# st, nd or th
					(\d{4})\s+																	# year of release
					(\d+)																		# total number of games
					(?:																	# other informations are optionals
						\s+																
						([\+-]\d+)\s+															# number of games added or deleted
						(\d+)\s*																# total number of drivers
						(.*)																	# other informations
					)?																	# block +game, drivers and info is optional
					$/ix) {																		# end of line
			my $version			= $1;
			my $month			= $2;
			my $day				= $3;
			my $year			= $4;
			my $total_games		= $5;
			my $games_delta		= $6;
			my $total_drivers	= $7;
			my $info			= $8;

			my %months = qw/january 1 february 2 march 3 april 4 may 5 june 6 july 7 august 8 september 9 october 10 november 11 december 12/;
			my $date_build = $year.'-'.sprintf('%02d',$months{lc $month}).'-'.sprintf('%02d',$day); # yyyy-mm-dd

			$sth1->execute($version,$date_build,$total_games,$games_delta,$total_drivers,$info) or warn "Can't insert versions ($version)";

		} elsif	(/^\#/) {	# comment
			next;

		} elsif	(/^\$info=(\S*)$/i) {	# changing rom
			$rom_name = $1;

		} elsif (/^\$mame$/i) {
			$rom_type = 'game';
			next;

		} elsif (/^\$drv$/i) {
			$rom_type = 'drv';
			next;

		} elsif (/^Romset:\s*(\d+)\s*kb\s*\/\s*(\d+)\s*files?\s*\/\s*([\d\.\,]+)\s*zip/i) {			# stock romset infos
			$romset_size	= $1;		# int kb
			$romset_file	= $2;		# int
			$romset_zip		=~ s/,/./;	# convert 2,56 to 2.56
			$romset_zip		= $3;		# float kb

		} elsif (/^\$end$/i) {			# validate info in database
			$sth2->execute($rom_name,trim(join("\n",@infos)),$romset_size,$romset_file,$romset_zip) or warn "Can't insert mameinfo ($rom_name)";
			
			# clear infos
			$rom_name = $rom_type = $romset_size = $romset_file	= $romset_zip = '';
			@infos    = ();

		} else {	# get some text
			push @infos, $_;
		}
	}
	close MAMEINFO;
	print "ok\n";
}

###################################################### HISTORY ###########################################

sub parse_history {
	if (!-e 'history.dat') {
		print "'history.dat' not found\nYou can download it at http://www.arcade-history.com/index.php?page=download\n";
		return ;
	}
	print "Parse 'history.dat'... ";

	$sqlite->do("DROP TABLE IF EXISTS 'histories'") or die "Can't drop 'histories' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'histories'	(
	'id'		INTEGER NOT NULL,
	'history'	TEXT,
	'link'		TEXT,
	PRIMARY KEY (id)
);
EOT
	$sqlite->do($sql) or die "Can't create 'histories' table";

$sqlite->do("DROP TABLE IF EXISTS 'games_histories'") or die "Can't drop 'games_histories' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'games_histories'	(
	'game'			VARCHAR NOT NULL,
	'history_id'	INTEGER NOT NULL
);
EOT
	$sqlite->do($sql) or die "Can't create 'games_histories' table";
	
	my $i=1;
	my $link ;
	my @roms ;
	my (@infos);
	my $sth1 = $sqlite->prepare("INSERT INTO histories (id,history,link) VALUES (?,?,?)");
	my $sth2 = $sqlite->prepare("INSERT INTO games_histories (game,history_id) VALUES (?,?)");
	open(HISTORY,'<history.dat') or die "Can't find 'history.dat' ($!)";
	while(<HISTORY>) {
		chomp;
		if		(/^(?:\#|\$<a href=\"(.+?)\")/) {	# link to history web page
			$link = $1;
			next;
		} elsif	(/^\$info=(\S*?),?$/i) {	# changing rom
			@roms = split(/,/, $1) ;
		} elsif (/^\$bio$/i) {
			next;
		} elsif (/^\$end$/i) {			# validate info in database
			# save history
			$sth1->execute($i,trim(join("\n",@infos)),$link) or warn "Can't insert histories ($i)";

			# link history and games
			foreach (@roms) {
				$sth2->execute($_,$i) or warn "Can't insert game_histories ($_,$i)";
			}

			$i++;

			# clear infos
			@infos = @roms = ();
			$link  = '';

		} else {	# get some text
			push @infos, $_;
		}
	}
	close HISTORY;
	print "ok\n";
}


###################################################### NPLAYERS ###########################################

sub parse_nplayers {
	if (!-e 'nplayers.ini') {
		print "'nplayers.ini' not found\nYou can download it at http://nplayers.arcadebelgium.be\n";
		return ;
	}
	print "Parse 'nplayers.ini'... ";

	$sqlite->do("DROP TABLE IF EXISTS 'nplayers'") or die "Can't drop 'nplayers' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'nplayers' (
	'game'		VARCHAR NOT NULL,
	'players'	VARCHAR NOT NULL,
	PRIMARY KEY (game,players)
);
EOT
	$sqlite->do($sql) or die "Can't create 'nplayers' table";

	my $sth = $sqlite->prepare("INSERT INTO nplayers (game,players) VALUES (?,?)");
	open(NPLAYERS,'<nplayers.ini') or die "Can't find 'nplayers.ini' ($!)";
	while(<NPLAYERS>) {
		chomp;
		if (/^\s*(.*?)\s*=\s*(.*?)\s*$/) {		# example : 88games=4P alt / 2P sim
			my $game			= $1;
			my $nplayers_label	= $2;
			my @t				= split /\s*\/\s*/ , $nplayers_label ;
			foreach my $n (@t) {	# split on /
				$sth->execute($game,$n) or warn "Can't insert nplayers ($game,$n)";
			}
		}
	}
	close NPLAYERS;

	print "ok\n";
}


###################################################### STORY ###########################################

sub parse_story {
	if (!-e 'story.dat') {
		print "'story.dat' not found\nYou can download it at http://www.arcadehits.net/mamescore/home.php?show=files\n";
		return ;
	}
	print "Parse 'story.dat'... ";

	$sqlite->do("DROP TABLE IF EXISTS 'stories'") or die "Can't drop 'stories' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'stories' (
	'game'			VARCHAR NOT NULL,
	'score'			VARCHAR NOT NULL,
	PRIMARY KEY (game)
);
EOT
	$sqlite->do($sql) or die "Can't create 'stories' table";

	my $rom_name ;
	my @score;
	my $sth = $sqlite->prepare("INSERT INTO stories (game,score) VALUES (?,?)");
	open(STORY,'<story.dat') or die "Can't find 'story.dat' ($!)";
	while(<STORY>) {
		chomp;

		if		(/^\#/) {	# comment
			next;
		} elsif	(/^\$info=(\S*)$/i) {	# changing rom
			$rom_name = $1;
		} elsif (/^\$story$/i) {
			next;
		} elsif (/^\$end$/i) {			# validate info in database
			$sth->execute($rom_name,trim(join("\n",@score))) or warn "Can't insert stories ($rom_name)";
			
			# clear infos
			@score		= ();
			$rom_name	= '';
		} else {	# get some text
			push @score, $_;
		}
	}
	close STORY;
	print "ok\n";
}


###################################################### CATVER ###########################################

sub parse_catver {
	if (!-e 'folders/Catver.ini') {
		print "'folders/Catver.ini' not found\nYou can download it at http://www.progettoemma.net/?catlist\n";
		return ;
	}
	print "Parse 'folders/Catver.ini'... ";

	$sqlite->do("DROP TABLE IF EXISTS 'categories'") or die "Can't drop 'catver' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'categories' (
	'game'			VARCHAR NOT NULL,
	'categorie'		VARCHAR NOT NULL,
	'version_added' BOOL NOT NULL,
	PRIMARY KEY (game,categorie)
);
EOT
	$sqlite->do($sql) or die "Can't create 'categories' table";

	my $version_added = 0 ;
	my $sth = $sqlite->prepare("INSERT INTO categories (game,categorie,version_added) VALUES (?,?,?)");
	open(CATVER,'<folders/Catver.ini') or die "Can't find 'folders/Catver.ini' ($!)";
	while(<CATVER>) {
		chomp;
		if (/^\[VerAdded\]$/) {						# VerAdd
			$version_added = 1;
		} elsif (/^\s*(.*?)\s*=\s*(.*?)\s*$/) {		# example : 19xxh=Shooter / Flying Vertical
			my $game			= $1;
			my $categories		= $2;
			my @categories		= split /\s*\/\s*/ , $categories ;
			foreach my $categorie (@categories) {	# split on /
				$sth->execute($game,$categorie,$version_added) or warn "Can't insert categories ($game,$categorie,$version_added)";
			}
		}
	}
	close CATVER;

	print "ok\n";
}


###################################################### CHEATS ###########################################

sub parse_cheats {
	if (!-e 'cheat.7z') {
		print "'cheat.7z' not found\nYou can download it at http://cheat.retrogames.com\n";
		return ;
	}

	my $sevenzip_exe = get_sevenzip_path();
	if (!$sevenzip_exe) {
		print "7-zip executable not found\nYou can download it at http://www.7-zip.org/\n";
		return ;
	}

	$sqlite->do("DROP TABLE IF EXISTS 'cheats'") or die "Can't drop 'cheats' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'cheats' (
	'id'		INTEGER NOT NULL,
	'game'		VARCHAR NOT NULL,
	'cheat'		VARCHAR NOT NULL,
	'comment'	VARCHAR,
	PRIMARY KEY (id)
);
EOT
	$sqlite->do($sql) or die "Can't create 'cheats' table";

	$sqlite->do("DROP TABLE IF EXISTS 'cheat_options'") or die "Can't drop 'cheat_options' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'cheats_options' (
	'cheat_id'	INTEGER NOT NULL,
	'option'	VARCHAR NOT NULL,
	'value'		VARCHAR NOT NULL,
	FOREIGN KEY (cheat_id) REFERENCES cheats(id)
);
EOT
	$sqlite->do($sql) or die "Can't create 'cheat_options' table";

	my $id = 1 ;
	my $game_done = 0 ;

	#my $zip = Archive::SevenZip->new('cheat.7z')
	mkdir('cheats') if !-d 'cheats';
	# extract files into cheats direcory
	print "Extract cheat.7z ... ";
	`"$sevenzip_exe" e cheat.7z -ocheats -y`;
	print "ok\n";

	# parse files
	my $sth1 = $sqlite->prepare("INSERT INTO cheats (id,game,cheat,comment) VALUES (?,?,?,?)");
	my $sth2 = $sqlite->prepare("INSERT INTO cheats_options (cheat_id,option,value) VALUES (?,?,?)");

	find(\&wanted, 'cheats');
	sub wanted {
		next if $_ eq '.' || $_ eq '..' || -d || !/\.xml$/;
		my $xml_file = $_;
		#unless ($xml_file eq 'sf2ce.xml') { next; } # for test
		my	$game = $xml_file;
			$game =~ s/\.xml$//i; # remove '.xml' from the file name to get game name

		# read XML file
		if (-e $xml_file) {
			printf "\r[%05d] Parsing cheats for '%s'                      ", ++$game_done,$game;
			open(XML,$xml_file) or warn "Can't open '$xml_file'";
			my $xml;
			try {
				$xml = XMLin($xml_file);
			} catch {
				next;
			};

			#print $xml_file." ".Dumper($xml);
			if (ref $xml->{'cheat'} ne 'ARRAY') { # there only one cheat -> transform hash to array
				$xml->{'cheat'} = [$xml->{'cheat'}];
			}
			foreach my $cheat (@{$xml->{'cheat'}}) {
				#print Dumper($cheat);
				$sth1->execute($id,$game,$cheat->{'desc'},$cheat->{'comment'}) or warn "Can't insert cheat ($id,$game)";

				if (exists $cheat->{'parameter'}->{'item'}) {
					if (ref $cheat->{'parameter'}->{'item'} ne 'ARRAY') { # there only one parameter -> transform hash to array
						$cheat->{'parameter'}->{'item'} = [$cheat->{'parameter'}->{'item'}];
					}
					foreach my $parameter (@{$cheat->{'parameter'}->{'item'}}) {
						#print "Test ";
						$sth2->execute($id,$parameter->{'content'},$parameter->{'value'}) or warn "Can't insert value in cheats_options table";
					}
				}

				$id++;
			}
			close XML;
			unlink($xml_file);
		} else {
			warn "Can't find '$xml_file'";
			next;
		}
	}

	print "ok\n";
	rmtree('cheats');
}


###################################################### SERIES ###########################################

sub parse_series {
	if (!-e 'folders/series.ini') {
		print "'folders/series.ini' not found\nYou can download it at http://www.progettoemma.net/?series\n";
		return ;
	}
	print "Parse 'folders/series.ini'... ";

	$sqlite->do("DROP TABLE IF EXISTS 'games_series'") or die "Can't drop 'games_series' table";
	$sqlite->do("DROP TABLE IF EXISTS 'series'") or die "Can't drop 'series' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'series' (
	'id'			INTEGER NOT NULL,
	'serie'			TEXT 	NOT NULL,
	PRIMARY KEY (id),
	UNIQUE		(serie)
);
EOT
	$sqlite->do($sql) or die "Can't create 'series' table";

	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'games_series' (
	'game'			TEXT 	NOT NULL,
	'serie_id'		INTEGER NOT NULL,
	FOREIGN KEY (serie_id) REFERENCES series(id)
);
EOT
	$sqlite->do($sql) or die "Can't create 'games_series' table";

	my $serie = '' ;
	my $i = 1;
	my @roms = ();
	my $sth1 = $sqlite->prepare("INSERT INTO series (id,serie) VALUES (?,?)");
	my $sth2 = $sqlite->prepare("INSERT INTO games_series (game,serie_id) VALUES (?,?)");
	open(SERIES,'<folders/series.ini') or die "Can't find 'folders/series.ini' ($!)";
	while(<SERIES>) {
		chomp;
		if (/^\[(?:FOLDER_SETTINGS|ROOT_FOLDER)\]$/i) {
			next;
		} elsif (/^\[(.+?)\]$/) {		# example : [World Heroes]
			if ($serie) { # if previous serie register, save in database
				# save serie name
				$sth1->execute($i,trim($serie)) or warn "Can't insert serie ($i,$serie)";

				# save games in serie
				foreach (@roms) {
					$sth2->execute($_,$i) or warn "Can't insert game_serie ($_,$i)";
				}
				@roms = ();
				$i++;
			}

			$serie = $1; # save new serie
		} elsif ($serie && /^(.{1,15})$/) { # rom name. Example : aerfboo2
			push @roms, $1;
		}
	}
	close SERIES;

	foreach (@roms) {
		$sth2->execute($_,$i) or warn "Can't insert game_serie ($_,$i)";
	}

	print "ok\n";
}


###################################################### COMMAND ###########################################

sub parse_command {
	if (!-e 'command.dat') {
		print "'command.dat' not found\nYou can download it at http://mamecommand.blogfatal.com/\n";
		return ;
	}
	print "Parse 'command.dat'... ";

	$sqlite->do("DROP TABLE IF EXISTS 'command'") or die "Can't drop 'command' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'command' (
	'id'			INTEGER NOT NULL,
	'command'		TEXT NOT NULL,
	PRIMARY KEY (id)
);
EOT
	$sqlite->do($sql) or die "Can't create 'command' table";

$sqlite->do("DROP TABLE IF EXISTS 'games_command'") or die "Can't drop 'games_command' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'games_command' (
	'game'			VARCHAR NOT NULL,
	'command_id'	INTEGER NOT NULL,
	PRIMARY KEY (game,command_id)
);
EOT
	$sqlite->do($sql) or die "Can't create 'games_command' table";

	my @games = () ;
	my @cmd = ();
	my $in_cmd = 0;
	my $i = 1 ;
	my $sth1 = $sqlite->prepare("INSERT INTO command (id,command) VALUES (?,?)");
	my $sth2 = $sqlite->prepare("INSERT INTO games_command (game,command_id) VALUES (?,?)");
	open(COMMAND,'<command.dat') or die "Can't find 'command.dat' ($!)";
	while(<COMMAND>) {
		chomp;
		if (/^\s*#/) { # skip comments
			next;
		} elsif (/^\$info=(.+)/) {		# example : $info=aodk
			# save info new game
			@games = split(/,/,$1) ;
		} elsif (/^\$cmd$/) {		# start of command list
			$in_cmd = 1;
		} elsif (/^\$end$/) {		# end of command list
			# save info in database
			$sth1->execute($i,join("\n",@cmd)) or warn "Can't insert command";

			#print Dumper(\@games);
			foreach my $game (@games) {
				$sth2->execute($game,$i) or warn "Can't insert games_command ($game,$i)";
			}

			$in_cmd = 0;  # reset infos
			@cmd = ();
			$i++;
		} else {		# probably instruction
			push @cmd,$_ if $in_cmd;
		}
	}
	close COMMAND;

	foreach my $game (@games) {
		$sth2->execute($game,$i) or warn "Can't insert games_command ($game,$i)";
	}

	print "ok\n";
}


# TO DO
sub parse_languages {
	if (!-e 'folders/languages.ini') {
		print "'folders/languages.ini' not found\nYou can download it at http://www.progettoemma.net/?languages\n";
		return ;
	}
	print "Parse 'folders/languages.ini'... ";

	$sqlite->do("DROP TABLE IF EXISTS 'games_languages'") or die "Can't drop 'games_languages' table";
	$sqlite->do("DROP TABLE IF EXISTS 'languages'") or die "Can't drop 'languages' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'languages' (
	'id'			INTEGER NOT NULL,
	'language'		TEXT,
	PRIMARY KEY (id),
	UNIQUE		(language)
);
EOT
	$sqlite->do($sql) or die "Can't create 'languages' table";

	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'games_languages' (
	'game'			TEXT NOT NULL,
	'language_id'	INTEGER,
	FOREIGN KEY (language_id) REFERENCES languages(id)
);
EOT
	$sqlite->do($sql) or die "Can't create 'games_languages' table";

	my $language = '' ;
	my $i = 1;
	my @roms = ();
	my $sth1 = $sqlite->prepare("INSERT INTO languages (id,language) VALUES (?,?)");
	my $sth2 = $sqlite->prepare("INSERT INTO games_languages (game,language_id) VALUES (?,?)");
	open(LANGUAGES,'<folders/languages.ini') or die "Can't find 'folders/languages.ini' ($!)";
	while(<LANGUAGES>) {
		chomp;
		if (/^\[(?:FOLDER_SETTINGS|ROOT_FOLDER)\]$/i) {
			next;
		} elsif (/^\[(.+?)\]$/) {		# example : [French]
			if ($language) { # if previous serie register, save in database
				# save language name
				$sth1->execute($i,trim($language)) or warn "Can't insert languages ($i,$language)";

				# save games in languages
				foreach (@roms) {
					$sth2->execute($_,$i) or warn "Can't insert games_languages ($_,$i)";
				}
				@roms = ();
				$i++;
			}

			$language = $1; # save new language
		} elsif ($language && /^(.{1,15})$/) { # rom name. Example : aerfboo2
			push @roms, $1;
		}
	}
	close LANGUAGES;

	foreach (@roms) {
		$sth2->execute($_,$i) or warn "Can't insert games_languages ($_,$i)";
	}

	print "ok\n";
}


sub parse_bestgames {
	if (!-e 'folders/bestgames.ini') {
		print "'folders/bestgames.ini' not found\nYou can download it at http://www.progettoemma.net/?bestgames\n";
		return ;
	}
	print "Parse 'folders/bestgames.ini'... ";

	$sqlite->do("DROP TABLE IF EXISTS 'bestgames'") or die "Can't drop 'bestgames' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'bestgames' (
	'game'			VARCHAR NOT NULL,
	'evaluation'	VARCHAR NOT NULL,
	PRIMARY KEY (game)
);
EOT
	$sqlite->do($sql) or die "Can't create 'bestgames' table";

	my $bestgames = '' ;
	my @roms = ();
	my $sth = $sqlite->prepare("INSERT INTO bestgames (game,evaluation) VALUES (?,?)");
	open(BESTGAMES,'<folders/bestgames.ini') or die "Can't find 'folders/bestgames.ini' ($!)";
	while(<BESTGAMES>) {
		chomp;
		if (/^\[(?:FOLDER_SETTINGS|ROOT_FOLDER)\]$/i) {
			next;
		} elsif (/^\[(.+?)\]$/) {		# example : [0 to 10 (Worst)]
			if ($bestgames) { # if previous bestgame register, save in database
				# save games in bestgame
				foreach (@roms) {
					$sth->execute($_,$bestgames) or warn "Can't insert bestgames ($_,$bestgames)";
				}
				@roms = ();
			}

			$bestgames = $1; # save new bestgames
		} elsif ($bestgames && /^(.{1,15})$/) { # rom name. Example : aerfboo2
			push @roms, $1;
		}
	}
	close BESTGAMES;

	foreach (@roms) {
		$sth->execute($_,$bestgames) or warn "Can't insert bestgames ($_,$bestgames)";
	}

	print "ok\n";
}



sub parse_mature {
	if (!-e 'folders/mature.ini') {
		print "'folders/mature.ini' not found\nYou can download it at http://www.progettoemma.net/?catver\n";
		return ;
	}
	print "Parse 'folders/mature.ini'... ";

	$sqlite->do("DROP TABLE IF EXISTS 'mature'") or die "Can't drop 'mature' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'mature' (
	'game'			VARCHAR NOT NULL,
	PRIMARY KEY (game)
);
EOT
	$sqlite->do($sql) or die "Can't create 'mature' table";

	my $in_root_folder = 0;
	my $sth = $sqlite->prepare("INSERT INTO mature (game) VALUES (?)");
	open(MATURE,'<folders/mature.ini') or die "Can't find 'folders/mature.ini' ($!)";
	while(<MATURE>) {
		chomp;
		if (/^\[(?:FOLDER_SETTINGS|ROOT_FOLDER)\]$/i) {
			$in_root_folder = 1;

		} elsif (/^.{1,15}$/i && $in_root_folder) { # rom name. Example : aerfboo2
			$sth->execute($_) or warn "Can't insert mature ($_)";
		}
	}
	close MATURE;

	print "ok\n";
}


sub parse_genre {
	if (!-e 'folders/genre.ini') {
		print "'folders/genre.ini' not found\nYou can download it at http://www.progettoemma.net/?catver\n";
		return ;
	}
	print "Parse 'folders/genre.ini'... ";

	$sqlite->do("DROP TABLE IF EXISTS 'genre'") or die "Can't drop 'genre' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'genre' (
	'game'	VARCHAR NOT NULL,
	'genre'	VARCHAR NOT NULL,
	PRIMARY KEY (game)
);
EOT
	$sqlite->do($sql) or die "Can't create 'genre' table";

	my $genre = '' ;
	my @roms = ();
	my $sth = $sqlite->prepare("INSERT INTO genre (game,genre) VALUES (?,?)");
	open(GENRE,'<folders/genre.ini') or die "Can't find 'folders/genre.ini' ($!)";
	while(<GENRE>) {
		chomp;
		if (/^\[(?:FOLDER_SETTINGS|ROOT_FOLDER)\]$/i) {
			next;
		} elsif (/^\[(.+?)\]$/) {		# example : [0 to 10 (Worst)]
			if ($genre) { # if previous genre register, save in database
				# save games in genre
				
				foreach (@roms) {
					$sth->execute($_,$genre) or warn "Can't insert genre ($_,$genre)";
				}
				@roms = ();
			}

			$genre = $1; # save new bestgames
		} elsif ($genre && /^(.{1,15})$/) { # rom name. Example : aerfboo2
			push @roms, $1;
		}
	}
	close GENRE;

	foreach (@roms) {
		$sth->execute($_,$genre) or warn "Can't insert genre ($_,$genre)";
	}

	print "ok\n";
}



###################################################### CREATE TABLES ###########################################

sub create_table_games {
		$sqlite->do("DROP TABLE IF EXISTS 'games'") or die "Can't drop 'games' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'games'	(
	'name'					VARCHAR NOT NULL,
	'sourcefile'			VARCHAR,
	'isbios'				BOOL	DEFAULT 0,
	'runnable'				BOOL	DEFAULT 1,
	'cloneof'				VARCHAR,
	'romof'					VARCHAR,
	'sampleof'				VARCHAR,
	'description'			VARCHAR,
	'year'					VARCHAR,
	'manufacturer'			VARCHAR,
	'sound_channels'		INTEGER,
	'input_service'			BOOL,
	'input_tilt'			BOOL,
	'input_players'			INTEGER,
	'input_buttons'			INTEGER,
	'input_coins'			INTEGER,
	'driver_status'			VARCHAR,
	'driver_emulation'		VARCHAR,
	'driver_color'			VARCHAR,
	'driver_sound'			VARCHAR,
	'driver_graphic'		VARCHAR,
	'driver_cocktail'		VARCHAR,
	'driver_protection'		VARCHAR,
	'driver_savestate'		BOOL,
	PRIMARY KEY (name)
);
EOT
	$sqlite->do($sql) or die "Can't create 'games' table";

	my @rows = $sqlite->selectrow_array("SELECT count(*) FROM sqlite_master WHERE type='trigger' AND name='check_enum_games' AND tbl_name='games'") or die $sqlite->errstr;
	if ($rows[0] == 1) { # si un trigger --> on le drop
		$sqlite->do("DROP TRIGGER 'check_enum_games'") or die "Can't drop 'check_enum_games' trigger";
	}
	$sql = <<EOT ;
CREATE TRIGGER 'check_enum_games'
	BEFORE INSERT ON 'games'
	FOR EACH ROW
		WHEN (	SELECT 1
				WHERE (		(new.driver_status='good'			OR new.driver_status='imperfect'		OR new.driver_status='preliminary'		OR new.driver_status=''		OR new.driver_status IS NULL)
						AND	(new.driver_emulation='good'		OR new.driver_emulation='imperfect'		OR new.driver_emulation='preliminary'	OR new.driver_emulation=''	OR new.driver_emulation IS NULL)
						AND (new.driver_color='good'			OR new.driver_color='imperfect'			OR new.driver_color='preliminary'		OR new.driver_color=''		OR new.driver_color IS NULL)
						AND (new.driver_sound='good'			OR new.driver_sound='imperfect'			OR new.driver_sound='preliminary'		OR new.driver_sound=''		OR new.driver_sound IS NULL)
						AND (new.driver_graphic='good'			OR new.driver_graphic='imperfect'		OR new.driver_graphic='preliminary'		OR new.driver_graphic=''	OR new.driver_graphic IS NULL)
						AND (new.driver_cocktail='good'			OR new.driver_cocktail='imperfect'		OR new.driver_cocktail='preliminary'	OR new.driver_cocktail=''	OR new.driver_cocktail IS NULL)
						AND (new.driver_protection='good'		OR new.driver_protection='imperfect'	OR new.driver_protection='preliminary'	OR new.driver_protection='' OR new.driver_protection IS NULL)
						AND (new.driver_savestate='supported'	OR new.driver_savestate='unsupported'	OR new.driver_savestate=''				OR new.driver_savestate IS NULL)
				)
				LIMIT 1
			) IS NULL
		BEGIN
			SELECT RAISE(FAIL,'enum-key violation on table games');
		END;
EOT
	$sqlite->do($sql);
}


sub create_table_games_biosset {
	$sqlite->do("DROP TABLE IF EXISTS 'games_biosset'") or die "Can't drop 'games_biosset' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'games_biosset'	(
	'game'					VARCHAR NOT NULL,
	'name'					VARCHAR NOT NULL,
	'description'			VARCHAR,
	'default'				BOOL DEFAULT 0,
	PRIMARY KEY (game,name),
	FOREIGN KEY (game) REFERENCES games(name)
);
EOT
	$sqlite->do($sql) or die "Can't create 'games_biosset' table";
}


sub create_table_games_rom {
	$sqlite->do("DROP TABLE IF EXISTS 'games_rom'") or die "Can't drop 'games_rom' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'games_rom'	(
	'game'					VARCHAR NOT NULL,
	'name'					VARCHAR NOT NULL,
	'bios'					VARCHAR,
	'size'					INTEGER,
	'crc'					VARCHAR,
	'md5'					VARCHAR,
	'merge'					VARCHAR,
	'sha1'					VARCHAR,
	'region'				VARCHAR,
	'offset'				INTEGER,
	'status'				VARCHAR DEFAULT 'good',
	'optional'				BOOL	DEFAULT 0,
	PRIMARY KEY (game,name),
	FOREIGN KEY (game) REFERENCES games(name)
);
EOT
	$sqlite->do($sql) or die "Can't create 'games_rom' table";

	my @rows = $sqlite->selectrow_array("SELECT count(*) FROM sqlite_master WHERE type='trigger' AND name='check_enum_games_rom' AND tbl_name='games_rom'") or die $sqlite->errstr;
	if ($rows[0] == 1) { # si un trigger --> on le drop
		$sqlite->do("DROP TRIGGER 'check_enum_games_rom'") or die "Can't drop 'check_enum_games_rom' trigger";
	}
	$sql = <<EOT ;
CREATE TRIGGER 'check_enum_games_rom'
	BEFORE INSERT ON 'games_rom'
	FOR EACH ROW
		WHEN (	SELECT 1
				WHERE (new.status='baddump' OR new.status='nodump' OR new.status='good')
				LIMIT 1
			) IS NULL
		BEGIN
			SELECT RAISE(FAIL,'enum-key violation on table games_rom');
		END;
EOT
	$sqlite->do($sql);
}


sub create_table_games_disk {
	$sqlite->do("DROP TABLE IF EXISTS 'games_disk'") or die "Can't drop 'games_disk' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'games_disk'	(
	'game'					VARCHAR NOT NULL,
	'name'					VARCHAR NOT NULL,
	'md5'					VARCHAR,
	'sha1'					VARCHAR,
	'merge'					VARCHAR,
	'region'				VARCHAR,
	'index'					INTEGER,
	'status'				VARCHAR DEFAULT 'good',
	'optional'				BOOL	DEFAULT 0,
	PRIMARY KEY (game,name),
	FOREIGN KEY (game) REFERENCES games(name)
);
EOT
	$sqlite->do($sql) or die "Can't create 'games_disk' table";

	my @rows = $sqlite->selectrow_array("SELECT count(*) FROM sqlite_master WHERE type='trigger' AND name='check_enum_games_disk' AND tbl_name='games_disk'") or die $sqlite->errstr;
	if ($rows[0] == 1) { # si un trigger --> on le drop
		$sqlite->do("DROP TRIGGER 'check_enum_games_disk'") or die "Can't drop 'check_enum_games_disk' trigger";
	}
	$sql = <<EOT ;
CREATE TRIGGER 'check_enum_games_disk'
	BEFORE INSERT ON 'games_disk'
	FOR EACH ROW
		WHEN (	SELECT 1
				WHERE (new.status='baddump' OR new.status='nodump' OR new.status='good')
				LIMIT 1
			) IS NULL
		BEGIN
			SELECT RAISE(FAIL,'enum-key violation on table games_disk');
		END;
EOT
	$sqlite->do($sql);
}


sub create_table_games_sample {
	$sqlite->do("DROP TABLE IF EXISTS 'games_sample'") or die "Can't drop 'games_sample' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'games_sample'	(
	'game'					VARCHAR NOT NULL,
	'name'					VARCHAR NOT NULL,
	PRIMARY KEY (game,name),
	FOREIGN KEY (game) REFERENCES games(name)
);
EOT
	$sqlite->do($sql) or die "Can't create 'games_sample' table";
}



sub create_table_games_chip {
	$sqlite->do("DROP TABLE IF EXISTS 'games_chip'") or die "Can't drop 'games_chip' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'games_chip'	(
	'game'					VARCHAR NOT NULL,
	'name'					VARCHAR NOT NULL,
	'tag'					VARCHAR,
	'type'					VARCHAR,
	'clock'					INTEGER,
	PRIMARY KEY (game,name),
	FOREIGN KEY (game) REFERENCES games(name)
);
EOT
	$sqlite->do($sql) or die "Can't create 'games_chip' table";

	my @rows = $sqlite->selectrow_array("SELECT count(*) FROM sqlite_master WHERE type='trigger' AND name='check_enum_games_chip' AND tbl_name='games_chip'") or die $sqlite->errstr;
	if ($rows[0] == 1) { # si un trigger --> on le drop
		$sqlite->do("DROP TRIGGER 'check_enum_games_chip'") or die "Can't drop 'check_enum_games_chip' trigger";
	}
	$sql = <<EOT ;
CREATE TRIGGER 'check_enum_games_chip'
	BEFORE INSERT ON 'games_chip'
	FOR EACH ROW
		WHEN (	SELECT 1
				WHERE (new.'type'='cpu' OR new.'type'='audio')
				LIMIT 1
			) IS NULL
		BEGIN
			SELECT RAISE(FAIL,'enum-key violation on table games_chip');
		END;
EOT
	$sqlite->do($sql);
}



sub create_table_games_display {
	$sqlite->do("DROP TABLE IF EXISTS 'games_display'") or die "Can't drop 'games_display' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'games_display'	(
	'game'					VARCHAR NOT NULL,
	'type'					VARCHAR,
	'rotate'				INTEGER,
	'flipx'					BOOL DEFAULT 0,
	'width'					INTEGER,
	'height'				INTEGER,
	'refresh'				FLOAT,
	'pixclock'				INTEGER,
	'htotal'				INTEGER,
	'hbend'					INTEGER,
	'hbstart'				INTEGER,
	'vtotal'				INTEGER,
	'vbend'					INTEGER,
	'vbstart'				INTEGER,
	FOREIGN KEY (game) REFERENCES games(name)
);
EOT
	$sqlite->do($sql) or die "Can't create 'games_display' table";

	my @rows = $sqlite->selectrow_array("SELECT count(*) FROM sqlite_master WHERE type='trigger' AND name='check_enum_games_display' AND tbl_name='games_display'") or die $sqlite->errstr;
	if ($rows[0] == 1) { # si un trigger --> on le drop
		$sqlite->do("DROP TRIGGER 'check_enum_games_display'") or die "Can't drop 'check_enum_games_display' trigger";
	}
	$sql = <<EOT ;
CREATE TRIGGER 'check_enum_games_display'
	BEFORE INSERT ON 'games_display'
	FOR EACH ROW
		WHEN (	SELECT 1
				WHERE (		(new.'type'='raster'	OR new.'type'='vector'	OR new.'type'='lcd' OR new.'type'='unknown')
						AND	(new.rotate=0			OR new.rotate=90		OR new.rotate=180	OR new.rotate=270)
				)
				LIMIT 1
			) IS NULL
		BEGIN
			SELECT RAISE(FAIL,'enum-key violation on table games_display');
		END;
EOT
	$sqlite->do($sql);
}

sub create_table_games_control {
	$sqlite->do("DROP TABLE IF EXISTS 'games_control'") or die "Can't drop 'games_control' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'games_control'	(
	'game'					VARCHAR NOT NULL,
	'type'					VARCHAR NOT NULL,
	'ways'					INTEGER,
	'minimum'				INTEGER,
	'maximum'				INTEGER,
	'sensitivity'			INTEGER,
	'keydelta'				INTEGER,
	'reverse'				BOOL,
	PRIMARY KEY (game,type),
	FOREIGN KEY (game) REFERENCES games(name)
);
EOT
	$sqlite->do($sql) or die "Can't create 'games_control' table";
}


sub create_table_games_dipswitch {
	$sqlite->do("DROP TABLE IF EXISTS 'games_dipswitch'") or die "Can't drop 'games_dipswitch' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'games_dipswitch'	(
	'id'					INTEGER	NOT NULL,
	'game'					VARCHAR NOT NULL,
	'name'					VARCHAR NOT NULL,
	'tag'					VARCHAR,
	'mask'					INTEGER,
	PRIMARY KEY (id),
	UNIQUE (game,name),
	FOREIGN KEY (game) REFERENCES games(name)
);
EOT
	$sqlite->do($sql) or die "Can't create 'games_dipswitch' table";

	$sqlite->do("DROP TABLE IF EXISTS 'games_dipswitch_dipvalue'") or die "Can't drop 'games_dipswitch_dipvalue' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'games_dipswitch_dipvalue'	(
	'dipswitch_id'			INTEGER NOT NULL,
	'name'					VARCHAR NOT NULL,
	'value'					INTEGER,
	'default'				BOOL,
	PRIMARY KEY (dipswitch_id,name),
	FOREIGN KEY (dipswitch_id) REFERENCES games_dipswitch(id)
);
EOT
	$sqlite->do($sql) or die "Can't create 'games_dipswitch_dipvalue' table";
}


sub create_table_games_adjuster {
	$sqlite->do("DROP TABLE IF EXISTS 'games_adjuster'") or die "Can't drop 'games_adjuster' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'games_adjuster'	(
	'game'					VARCHAR NOT NULL,
	'name'					VARCHAR NOT NULL,
	'default'				INTEGER,
	PRIMARY KEY (game,name),
	FOREIGN KEY (game) REFERENCES games(name)
);
EOT
	$sqlite->do($sql) or die "Can't create 'games_adjuster' table";
}

sub create_table_games_softwarelist {
	$sqlite->do("DROP TABLE IF EXISTS 'games_softwarelist'") or die "Can't drop 'games_softwarelist' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'games_softwarelist'	(
	'game'					VARCHAR NOT NULL,
	'name'					VARCHAR NOT NULL,
	'status'				VARCHAR,
	PRIMARY KEY (game,name),
	FOREIGN KEY (game) REFERENCES games(name)
);
EOT
	$sqlite->do($sql) or die "Can't create 'games_softwarelist' table";

	my @rows = $sqlite->selectrow_array("SELECT count(*) FROM sqlite_master WHERE type='trigger' AND name='check_enum_games_softwarelist' AND tbl_name='games_softwarelist'") or die $sqlite->errstr;
	if ($rows[0] == 1) { # si un trigger --> on le drop
		$sqlite->do("DROP TRIGGER 'check_enum_games_softwarelist'") or die "Can't drop 'check_enum_games_softwarelist' trigger";
	}
	$sql = <<EOT ;
CREATE TRIGGER 'check_enum_games_softwarelist'
	BEFORE INSERT ON 'games_softwarelist'
	FOR EACH ROW
		WHEN (	SELECT 1
				WHERE (new.'status'='original'	OR new.'status'='compatible')
				LIMIT 1
			) IS NULL
		BEGIN
			SELECT RAISE(FAIL,'enum-key violation on table games_softwarelist');
		END;
EOT
	$sqlite->do($sql);
}

sub create_table_games_ramoption {
	$sqlite->do("DROP TABLE IF EXISTS 'games_ramoption'") or die "Can't drop 'games_ramoption' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'games_ramoption'	(
	'game'					VARCHAR NOT NULL,	
	'value'					INTEGER,
	'default'				INTEGER,
	PRIMARY KEY (game,value),
	FOREIGN KEY (game) REFERENCES games(name)
);
EOT
	$sqlite->do($sql) or die "Can't create 'games_ramoption' table";
}


sub create_table_games_configuration {
	$sqlite->do("DROP TABLE IF EXISTS 'games_configuration'") or die "Can't drop 'games_configuration' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'games_configuration'	(
	'id'					INTEGER NOT NULL,
	'game'					VARCHAR NOT NULL,	
	'name'					TEXT	NOT NULL,
	'tag'					TEXT,
	'mask'					INTEGER,
	PRIMARY KEY (id),
	UNIQUE		(game,name),
	FOREIGN KEY (game) REFERENCES games(name)
);
EOT
	$sqlite->do($sql) or die "Can't create 'games_configuration' table";

	$sqlite->do("DROP TABLE IF EXISTS 'games_configuration_confsetting'") or die "Can't drop 'games_configuration_confsetting' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'games_configuration_confsetting'	(
	'configuration_id'		INTEGER NOT NULL,
	'name'					VARCHAR NOT NULL,
	'value'					INTEGER,
	'default'				BOOL,
	PRIMARY KEY (configuration_id,name),
	FOREIGN KEY (configuration_id) REFERENCES games_configuration(id)
);
EOT
	$sqlite->do($sql) or die "Can't create 'games_configuration_confsetting' table";
}

sub create_table_games_category {
	$sqlite->do("DROP TABLE IF EXISTS 'games_category'") or die "Can't drop 'games_category' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'games_category'	(
	'id'					INTEGER NOT NULL,
	'game'					VARCHAR NOT NULL,	
	'name'					TEXT,
	PRIMARY KEY (id),
	UNIQUE		(game,name),
	FOREIGN KEY (game) REFERENCES games(name)
);
EOT
	$sqlite->do($sql) or die "Can't create 'games_category' table";

	$sqlite->do("DROP TABLE IF EXISTS 'games_category_item'") or die "Can't drop 'games_category_item' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'games_category_item'	(
	'category_id'			INTEGER NOT NULL,
	'name'					VARCHAR NOT NULL,
	'default'				BOOL,
	PRIMARY KEY (category_id,name),
	FOREIGN KEY (category_id) REFERENCES games_category(id)
);
EOT
	$sqlite->do($sql) or die "Can't create 'games_category_item' table";
}

sub create_table_games_device {
	$sqlite->do("DROP TABLE IF EXISTS 'games_device'") or die "Can't drop 'games_device' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'games_device'	(
	'id'					INTEGER NOT NULL,
	'game'					VARCHAR NOT NULL,	
	'type'					TEXT	NOT NULL,	
	'tag'					TEXT    NOT NULL,
	'mandatory'				INTEGER,
	'interface'				TEXT,
	PRIMARY KEY (id),
	UNIQUE		(game,type,tag),
	FOREIGN KEY (game) REFERENCES games(name)
);
EOT
	$sqlite->do($sql) or die "Can't create 'games_device' table";

	$sqlite->do("DROP TABLE IF EXISTS 'games_device_instance'") or die "Can't drop 'games_device_instance' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'games_device_instance'	(
	'device_id'				INTEGER NOT NULL,
	'name'					TEXT	NOT NULL,
	'briefname'				TEXT,
	PRIMARY KEY (device_id,name),
	FOREIGN KEY (device_id) REFERENCES games_device(id)
);
EOT
	$sqlite->do($sql) or die "Can't create 'games_device_instance' table";

	$sqlite->do("DROP TABLE IF EXISTS 'games_device_extension'") or die "Can't drop 'games_device_extension' table";
	my $sql = <<EOT ;
CREATE TABLE IF NOT EXISTS 'games_device_extension'	(
	'device_id'				INTEGER NOT NULL,
	'name'					TEXT	NOT NULL,
	PRIMARY KEY (device_id,name),
	FOREIGN KEY (device_id) REFERENCES games_device(id)
);
EOT
	$sqlite->do($sql) or die "Can't create 'games_device_extension' table";
}



sub generate_mamexml {
	print "mame.xml not found\n";		
	print "Would you like to I generate it for you ? (YES/no) : ";
	my $input = <>;
	chomp;
	if ($input =~ /Y(:?ES)?/i) {
		my $mame_exe = '';
		if ($running_on_windows) {
			if (-e 'mame.exe') {
				$mame_exe = 'mame.exe';
			} elsif (-e 'mame32.exe') {
				$mame_exe = 'mame32.exe';
			} elsif (-e 'mame64.exe') {
				$mame_exe = 'mame64.exe';
			}
		} else {
			if (-e 'mame') {
				$mame_exe = './mame';
			} elsif (-e 'mame32') {
				$mame_exe = './mame32';
			} elsif (-e 'mame64.exe') {
				$mame_exe = './mame64';
			}
		}

		print "Please wait while generating $mame_exe -listxml > mame.xml ... ";
		`$mame_exe -listxml > mame.xml`;
		if (-e 'mame.xml') {
			print "ok\n";
		} else {
			print "error\n";
			print "I can't continue. Bye."; exit;
		}

	} else {
		print "I can't continue. Bye."; exit;
	}
}


sub get_sevenzip_path {
	my $sevenzip_exe= '';
	if (-e '7zip\7za.exe') {
		$sevenzip_exe = '7zip\7za.exe';
	} elsif (-e 'C:\Program Files\7-Zip\7z.exe') {
		$sevenzip_exe = 'C:\Program Files\7-Zip\7z.exe';
	} elsif (-e 'C:\Program Files\7-Zip\7z.exe') {
		$sevenzip_exe = 'C:\Program Files (x86)\7-Zip\7z.exe';
	} elsif (-e '/usr/bin/7zip') {
		$sevenzip_exe = '/usr/bin/7zip';
	}
	return $sevenzip_exe;
}


###################################################### USEFUL ###########################################

sub trim {
	my $val = shift;
	$val =~ s/(?:^\s+|\s+$)//g;
	return $val ;
}

sub yesno2bool {
	my $val = lc shift;
	return $val eq 'yes' ? 1 : 0;
}