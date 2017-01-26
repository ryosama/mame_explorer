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
				$i = 0;
				while($row = $res->fetch(PDO::FETCH_ASSOC)) {
					if ($row['year'] && strpos($row['year'],'?') === false) { ?>
						<option value="<?=$row['year']?>"<?=$_SESSION['from_year']==$row['year'] ? ' selected="selected"':''?>><?=$row['year']?></option>
		<?php			$i++;
					}
				} ?>
		</select>
	</div>

	<div id="search-to-year">
	<label for="to_year">to</label>
	<select name="to_year">
	<?php 	$res = $database->query("SELECT DISTINCT(year) as year FROM games ORDER BY year ASC") or die("Unable to query database : ".array_pop($database->errorInfo()));
			$j = 0;
			while($row = $res->fetch(PDO::FETCH_ASSOC)) {
				if ($row['year'] && strpos($row['year'],'?') === false) { ?>
					<option value="<?=$row['year']?>"<?=$_SESSION['to_year']==$row['year'] ? ' selected="selected"':''?>><?=$row['year']?></option>
	<?php			$j++;
				}
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

<input type="hidden" name="new_search" value="1"/>
<input type="submit" style="display:none;"/>
</form>
</div>