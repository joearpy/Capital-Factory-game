<div id="hall-of-fame" class="hall-of-fame" style="display: none;">
	<div class="header">

		<div class="title">
			<span><b>HALL OF FAME</b></span>
		</div>

		<div class="filters">
			<select id="year" class="year">
				<option value="">Válassz évet</option>
				<option value="2016">2016</option>
				<option value="2015">2015</option>
			</select>

			<select id="month" class="month" disabled="disabled">
				<option value="">Összes hónap</option>
				<option value="1">Január</option>
				<option value="2">Február</option>
				<option value="3">Március</option>
				<option value="4">Április</option>
				<option value="5">Május</option>
				<option value="6">Június</option>
				<option value="7">Július</option>
				<option value="8">Augusztus</option>
				<option value="9">Szepteber</option>
				<option value="10">Október</option>
				<option value="11">November</option>
				<option value="12">December</option>
			</select>

			<select id="week" class="week" disabled="disabled">
				<option value="">Összes hét</option>
				<?php for ($i=1; $i<=53; $i++) : ?>
					<option value="<?=$i;?>"><?=$i;?></option>
				<?php endfor; ?>
			</select>
		</div>
	</div>

	<table id="hall-of-fame-table" class="display" cellspacing="0" width="100%">
		<thead>
			<tr>
				<th class="place">Helyezés</th>
				<th class="name">Név</th>
				<th class="date">Dátum</th>
			</tr>
		</thead>
	</table>
	<div class="clearfix"></div>
</div>