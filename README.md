# MAME Explorer
---------------
It's a responsive web interface (PHP / Perl / SQLite) to retrieve all informations about MAME roms.

Demo http://78.213.180.135:5080/mame

![Screenshot](https://raw.githubusercontent.com/ryosama/mame_explorer/master/images/screenshot_desktop.jpg?raw=true "Screenshot") ![Screenshot on mobile device](https://raw.githubusercontent.com/ryosama/mame_explorer/master/images/screenshot_phone.jpg?raw=true "Screenshot on mobile device")

![Rom Search](https://raw.githubusercontent.com/ryosama/mame_explorer/master/images/rom_search_desktop.jpg?raw=true "Rom Search") ![Rom Search on mobile device](https://raw.githubusercontent.com/ryosama/mame_explorer/master/images/rom_search_phone.jpg?raw=true "Rom Search on mobile device")

# Create Database
-----------------
- To create your database, you have to download mame executable : http://mamedev.org/release.php (32 or 64 bits)
- Go to `sources` directory and launch `perl getfiles.pl` to download latest version of files which work with MAME :
	catver.ini, nplayers.ini, series.ini, languages.ini, bestgames.ini, cheats, history.dat, story.dat,
	mameinfo.dat, catver.ini, command.dat
- Go to `sources` directory and launch `perl mame2sqlite.pl` to create the SQLite database.
- You can also manually download snapshots, titles, control panel,... images packs and unzip them into `sources` directory.
	Go to http://www.mamechannel.it/pages/progettosnaps.php to download images packs