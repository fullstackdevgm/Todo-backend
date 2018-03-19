<h2>Generate Promo Code</h2>
<div class="setting new_promo_code">
	<div class="labeled_control">
		<label class="bold">Months</label>
		<div class="select-wrap new_promo_time_select" style="width:50px;display:inline-block;">
			<select id="new_promo_time">
				<option value="1">1</option>
				<option value="2">2</option>
				<option value="3">3</option>
				<option value="4">4</option>
				<option value="5">5</option>
				<option value="6">6</option>
				<option value="7">7</option>
				<option value="8">8</option>
				<option value="9">9</option>
				<option value="10">10</option>
				<option value="11">11</option>
				<option value="12">12</option>
			</select>
		</div>	
	</div>
	<div class="labeled_control">
		<label class="bold">Description</label>
		<textarea class="promo_note" id="new_promo_note" type="text" placeholder="at least 47 characters" ></textarea>
		<span id="char_count"></span>
	</div>
	<div class="labeled_control generate_promo_code">
		<div class="button disabled" id="generate_promo_code_button">Generate</div>
	</div>	
</div>
<div class="breath-20"></div>
<div class="breath-20"></div>
<h2>Unused Promo Codes</h2>
<div id="unused_promo_codes" class="promo_codes">
</div>
<div class="breath-20"></div>
<div class="breath-20"></div>
<div class="breath-20"></div>
<div class="breath-20"></div>
<h2>Used Promo Codes</h2>
<div id="used_promo_codes" class="promo_codes used">
</div>
<div class="breath-20"></div>
<div class="breath-20"></div>

<script type="text/javascript" src="<?php echo TP_JS_PATH_PROMO_CODE_FUNCTIONS; ?>"></script>

