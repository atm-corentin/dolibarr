<?php
$langs->load('advancedhrm@advancedhrm');
?>


<div id="popupCalculFraisKilometrique" class="popup_block load-boostrap" style="display: none">
	<div id="container" style="text-align: left">
		<?php if (!getDolGlobalString('ADVANCEDHRM_KEYAPI_GOOGLEMAPS')) {?>
			<div class="error">
				<p>
					<?php setEventMessage($langs->trans('advancedhrm_error_googleApiKey_is_empty'),'warnings'); ?>
					<?php echo $langs->trans('advancedhrm_error_googleApiKey_is_empty'); ?>
				</p>
			</div>
		<?php } else { ?>
		<input id="fk_user" type="hidden" value="<?php echo $user->id; ?>" />

		<div style="display: flex;margin:0 auto;">
<!--			MAP     -->
			<div id='map' style='min-height: 640px; min-width: 500px'></div>
			<div style="margin-left: 10px; margin-top: 10px">
				<p style='font-weight: 600;'>Frais kilométriques</p>
				<div class='form-floating mb-3' style="min-width: 500px">
					<input type='text' class='form-control' id='depart' placeholder=<?php echo $langs->transnoentities('startPoint');?>/>
					<label for='depart'><?php echo $langs->transnoentities('startPoint');?></label>
				</div>
				<div class='form-floating'>
					<input type='text' class='form-control' id='arrivee' placeholder=<?php echo $langs->transnoentities('endPoint');?>/>
					<label for='arrivee'><?php echo $langs->transnoentities('endPoint');?></label>
				</div>

				<div class='form-check' style="margin-top: 20px">
					<input class='form-check-input' type='checkbox' value='' id='allerRetour' style="background-color: #a0a0a0;">
					<label class='form-check-label' for='allerRetour'>
						<?php echo $langs->transnoentities('roundTrip');?>
					</label>
				</div>
				<div class='form-check'>
					<input class='form-check-input' type='checkbox' value=''  id='advancedhrm_gmap_AjoutFavoris'  style='background-color: #a0a0a0'>
					<label class='form-check-label' for='advancedhrm_gmap_AjoutFavoris'>
						<?php echo $langs->transnoentities('saveTrip');?>
					</label>
				</div>

				<div id='presCalcul' style='margin-top:50px; min-height: 260px;padding:5px;text-align: center'></div>
					<div style='text-align: center; margin-top: 40px'>
					<a  href='javascript:;' type='button' class='butAction popFavoris' style="color: white"><?php echo $langs->transnoentities('favoritesTrip');?></a>
					<button type='button' class='butAction' id='advancedhrm_gmap_validerParcours'><?php echo $langs->transnoentities('calculateTrip');?></button>
						<button id='advancedhrm_gmap_validerParcoursFinal' type='button' class='butAction'><?php echo $langs->transnoentities('Validate');?></button>
					</div>
			</div>
		</div>
	</div>
</div>
	<div id='popupFavoris' class='popup_block'>
		<div id='tabFavoris'></div>
	</div>

		<?php } ?>
	</div>
</div>

