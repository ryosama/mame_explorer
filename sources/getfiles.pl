#!/usr/bin/perl

use strict;
use WWW::Mechanize;
use Data::Dumper;
use File::Path;
use File::Basename;
use File::Find;
use File::Copy;
use URI::URL;
$|=1;

# create directory structures
foreach (qw/bosses cabinets cpanel ends flyers gameover howto icons logo marquees pcb scores select snap titles versus/) {
	if (!-d) { # if not a directory
		mkpath($_) or warn "Coudn't create directory '$_' ($!)";
	}
}

my $urls = [
	{	'url' 	=> 'http://www.progettosnaps.net/catver/',
		'what' 	=> [qr/packs\/pS_CatVer\.zip$/i],		# catver.ini
		'file'	=> 'pS_CatVer\.zip$',
		'where' => './folders'
	},
	{	'url' 	=> 'http://nplayers.arcadebelgium.be/',
		'what' 	=> [qr/files\/nplayers[\d]+.zip$/i], # nplayers.ini
		'file'	=> 'nplayers\d+\.zip$',
		'where' => '.'
	},
	{	'url' 	=> 'http://www.progettosnaps.net/series/',
		'what' 	=> [qr/pS_Series\.zip$/i],	# series.ini
		'file'	=> 'pS_Series\.zip$',
		'where' => './folders'
	},
	{	'url' 	=> 'http://www.progettosnaps.net/languages/',
		'what' 	=> [qr/pS_Languages\.zip$/i],	# languages.ini
		'file'	=> 'pS_Languages\.zip$',
		'where' => './folders'
	},
	{	'url' 	=> 'http://www.progettosnaps.net/bestgames/',
		'what' 	=> [qr/pS_BestGames\.zip$/i],	# bestgames.ini
		'file'	=> 'pS_BestGames\.zip$',
		'where' => './folders'
	},
	{	'url' 	=> 'http://cheat.retrogames.com/',
		'what' 	=> [qr/download\/cheat[\d]+\.zip$/i],	# cheats
		'file'	=> 'cheat\d+\.zip$',
		'where' => '.'
	},
	{	'url' 	=> 'http://www.arcade-history.com/?page=download',
		'what' 	=> [qr/dats\/history[\d]+\.7z$/i],	# history.dat
		'file'	=> 'history\d+\.7z$',
		'where' => '.'
	},
	{	'url' 	=> 'http://www.arcadehits.net/mamescore/home.php?show=files',
		'what' 	=> [qr/rss\/story\.dat$/i],	# story.dat
		'file'	=> 'story\.dat$',
		'where' => '.'
	},
	{	'url' 	=> 'http://mameinfo.mameworld.info/',
		'what' 	=> [qr/download\/Mameinfo[\d]+\.zip$/i],	# mameinfo.dat
		'file'	=> 'Mameinfo\d+\.zip$',
		'where' => '.'
	},

	{	'url' 	=> 'http://www.progettosnaps.net/catver/',
		'what' 	=> [qr/packs\/pS_CatVer\.zip$/i],		# catver.ini
		'file'	=> 'cheat\d+\.zip$',
		'where' => './folders'
	},
	{	'url' 	=> 'https://sites.google.com/site/procyonsjj/home/command-dat/',
		'what' 	=> [qr/command-dat\/commandsh\.zip\?attredirects=0&d=1$/i],	# command.dat
		'file'	=> 'commandsh\.zip$',
		'where' => '.'
	}
];



# fetching files URL
my $mech = WWW::Mechanize->new();
my @to_downloads = ();
foreach (@$urls) {
	printf 'Getting %s ... ', $_->{'url'} ;
	$mech->get($_->{'url'}) or die "Could not get ".$_->{'url'};
	printf "ok\n";
	my @links = $mech->links();
	foreach my $re (@{$_->{'what'}}) {
		foreach my $link (@links) {
			if ($link->url =~ /$re/) {
				my $url = new URI::URL($_->{'url'});
				my $url_to_add = $link->url;

				#print "\$url_to_add='$url_to_add'\n";
				$url_to_add =~ s/^\.\.\///;
				#print "\$url_to_add='$url_to_add'\n";

				if ($link->url =~ /^\//) { # link start with slash --> restart path
					$url_to_add = $url->scheme . '://' . $url->netloc . $url_to_add;

				} elsif ($link->url !~ /^https?:\/\//) { # link does not start with http --> add link to url
					$url_to_add = $url->scheme . '://' . $url->netloc . dirname($url->path) .'/'. $url_to_add;
				}

				printf " -> Found %s\n",$url_to_add;
				push @to_downloads, $url_to_add;
				last; # skip next link for same file

			}
		}
	}
}


# download files into temp directory
mkpath('temp');
foreach (@to_downloads) {
	my 	$url_to_get = $_;
	my 	$file_store = basename($url_to_get);
		$file_store =~ s/\?.*$//;

	printf 'Downloading %s ... ', $url_to_get;

	my $response = $mech->simple_request( HTTP::Request->new(GET => $_) );
	if( $response->is_redirect ) { # redirection
  		$url_to_get = $response->header('Location');
	}

	$mech->get( $url_to_get, ':content_file' => "temp/$file_store" );
	printf "ok\n";
}


UNZIP:

# unzip file to the right place
my $sevenzip_exe = get_sevenzip_path();
find({wanted => \&wanted, no_chdir => 1}, 'temp');
sub wanted {
	next if -d ; # skip if directory
	
	foreach my $url_obj (@{$urls}) {
		#print Dumper($url_obj);
		my $re = $url_obj->{'file'};
		if ($_ =~ /$re/) {
			my $where = $url_obj->{'where'};
			my $need_unzip = 0;
			$need_unzip = 1 if /\.(zip|7z)/i ;
			print ($need_unzip ? 'Unzip':'Copy');
			print " $_ to => $where...";

			if ($need_unzip) { # unzip
				if (/history(\d+).7z$/i) { # double unzip
					`"$sevenzip_exe" e "$_" -otemp -y`;
					`"$sevenzip_exe" e temp/history$1 -o$where -y`;

				} elsif (/Mameinfo(\d+).zip$/i) { # double unzip
					`"$sevenzip_exe" e "$_" -otemp -y`;
					`"$sevenzip_exe" e temp/Mameinfo$1 -otemp -y`;
					`"$sevenzip_exe" e temp/Mameinfo$1.7z -o$where -y`;

				} else {
					`"$sevenzip_exe" e "$_" -o$where -y`;
				}
				
			} else { # just copy
				copy($_, $url_obj->{'where'}.'/'.basename($_));
			}

			print " ok\n";
			last;
		}
	}
}

rmtree('temp'); # cleanup

####################################################################################################
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