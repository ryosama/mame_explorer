<div id="suggest-manufacturer">
	<div class="header">
		Manufacturers
		<i id="close-suggest-manufacturer" class="fa fa-close fa-small" title="Close"></i>
	</div>
	<div class="suggest-container"></div>
</div>

<?php

// defautl value
$pageno = isset($_GET['pageno']) ? $_GET['pageno'] : 1;

// search comes from a link manufacturer
foreach (array('manufacturer','sourcefile','nplayers','categorie','language','evaluation','mature','genre') as $criteria) {
	$pageno = 1;
	reset_session_except($criteria);
}

// search comes from a link year
if (isset($_GET['year']) && is_numeric($_GET['year']) && strlen($_GET['year'])>0) {
	$pageno = 1;
	reset_session_except('from_year','to_year');
	$_SESSION['from_year'] = $_SESSION['to_year'] = $_GET['year'];
}


// reset pagination
if (isset($_POST['new_search']) && $_POST['new_search']==1) {
	$pageno = 1;
	reset_session_except('rom_name','manufacturer','from_year','to_year');
}


// build the session info
// init session key
foreach (array('rom_name','hide_clones','manufacturer','from_year','to_year','sourcefile','nplayers','categorie','language','evaluation','mature','genre','order_by','reverse_order','limit','pageno') as $key) {
	if (!isset($_SESSION[$key])) $_SESSION[$key] = '';
	if (isset($_POST[$key])) 	 $_SESSION[$key] = $_POST[$key];
	if (isset($_GET[$key])) 	 $_SESSION[$key] = $_GET[$key];
}

// build checkbox
foreach (array('hide_clones','reverse_order') as $checkbox) {
	
	if (isset($_POST[$checkbox])) {
		$check = sizeof(array_unique($_POST[$checkbox]));
		if 		($check==2)
			$_SESSION[$checkbox] = true;
		elseif 	($check==1)
			$_SESSION[$checkbox] = false;
	}

	if (isset($_GET[$checkbox])) {
		$check = sizeof(array_unique($_GET[$checkbox]));
		if 		($check==2)
			$_SESSION[$checkbox] = true;
		elseif 	($check==1)
			$_SESSION[$checkbox] = false;
	}
}


// default values
if ($_SESSION['from_year'] == '' || !is_numeric($_SESSION['from_year'])) {
	$res = $database->query("SELECT MIN(year) as year FROM games WHERE year not like '%??%' LIMIT 0,1") or die("Unable to query database : ".array_pop($database->errorInfo()));
	$row = $res->fetch(PDO::FETCH_ASSOC);
	$_SESSION['from_year'] = $row['year'];
}

if ($_SESSION['to_year'] == '' || !is_numeric($_SESSION['to_year'])) {
	$res = $database->query("SELECT MAX(year) as year FROM games WHERE year not like '%??%' LIMIT 0,1") or die("Unable to query database : ".array_pop($database->errorInfo()));
	$row = $res->fetch(PDO::FETCH_ASSOC);
	$_SESSION['to_year'] = $row['year'];
}

if ($_SESSION['order_by'] == '')
	$_SESSION['order_by']='name';

if ($_SESSION['limit'] <= 0 || !is_numeric($_SESSION['limit']))
	$_SESSION['limit']=20;

if ($_SESSION['hide_clones'] === '')
	$_SESSION['hide_clones']=true;


// invert date if needed
if ($_SESSION['from_year'] > $_SESSION['to_year']) {
	$tmp = $_SESSION['from_year'];
	$_SESSION['from_year'] = $_SESSION['to_year'];
	$_SESSION['to_year'] = $tmp;
	unset($tmp);
}

?>
<div id="search-bar">

<form name="rom_search" method="POST" action="results.php" accept-charset="utf-8">

<div id="search-rom">
	<input id="rom_name" type="text" name="rom_name" value="<?=$_SESSION['rom_name']?>" placeholder="Name..."/>
</div>

<div id="search-clone">
	<input type="hidden" name="hide_clones[]" value=""/>
	<label for="hide_clones">Hide clones</label><input type="checkbox" name="hide_clones[]" value="1" <?=$_SESSION['hide_clones'] ? 'checked="checked"':''?>/>
</div>

<div id="search-manufacturer">
	<input id="manufacturer" name="manufacturer" type="text" value="<?=$_SESSION['manufacturer']?>" placeholder="Manufacturer..." autocomplete="off">
</div>

<div id="search-year">
	<div id="search-from-year">
		<label for="from_year">From</label>
		<select name="from_year">
		<?php 	$res = $database->query("SELECT DISTINCT(year) as year FROM games ORDER BY year ASC") or die("Unable to query database : ".array_pop($database->errorInfo()));
				while($row = $res->fetch(PDO::FETCH_ASSOC)) {
					$years[] = $row['year'];
				}

				foreach ($years as $year) {
					if ($year && strpos($year,'?') === false) { ?>
						<option value="<?=$year?>"<?=$_SESSION['from_year']==$year?' selected="selected"':''?>><?=$year?></option>
	<?php			}
				} ?>
		</select>
	</div>

	<div id="search-to-year">
	<label for="to_year">to</label>
	<select name="to_year">
	<?php 	foreach ($years as $year) {
				if ($year && strpos($year,'?') === false) { ?>
					<option value="<?=$year?>"<?=$_SESSION['to_year']==$year?' selected="selected"':''?>><?=$year?></option>
	<?php		}
			} ?>
	</select>
	</div>
</div>

<div id="search-order">
	<label for="order_by">Order by</label>
	<select name="order_by">
		<option value="name"<?= 		$_SESSION['order_by']=='name' ? ' selected="selected"':'' 			?>>Name</option>
		<option value="description"<?= 	$_SESSION['order_by']=='description' ? ' selected="selected"':'' 	?>>Description</option>
		<option value="year"<?= 		$_SESSION['order_by']=='year' ? ' selected="selected"':'' 			?>>Year</option>
		<option value="manufacturer"<?= $_SESSION['order_by']=='manufacturer' ? ' selected="selected"':'' 	?>>Manufacturer</option>
	</select>
	<input type="hidden" name="reverse_order[]" value=""/>
	<label for="reverse_order">Reverse order</label><input type="checkbox" name="reverse_order[]" value="1"<?=$_SESSION['reverse_order'] ? 'checked="checked"':''?>/>
</div>


<div id="search-limit">
	<label for="limit">Results per page</label><input type="text" name="limit" value="<?=$_SESSION['limit']?>" size="3"/>
</div>

<div id="search-submit">
	<a id="submit-search" class="btn" href="#">
		<i class="fa fa-search fa-small"></i> Search
	</a>
</div>

<div id="search-options">
	<span class="fa-stack fa-lg" title="More options">
		<i class="fa fa-circle fa-stack-2x"></i>
		<i class="fa fa-bars fa-stack-1x fa-inverse"></i>
	</span>
</div>

<input type="hidden" name="new_search" value="1"/>
<input type="submit" style="display:none;"/>
</form>
</div>