<?php
$langs->load('advancedhrm@advancedhrm');
?>

<div id="popupRestaurantAddress" class="popup_block" style="display: none;">
	<div id="container" style="text-align: left; flex: auto">
		<?php if (!getDolGlobalString('ADVANCEDHRM_KEYAPI_GOOGLEMAPS')) {?>
			<div class="error">
				<p>
					<?php setEventMessage($langs->trans('advancedhrm_error_googleApiKey_is_empty'),'warnings'); ?>
					<?php echo $langs->trans('advancedhrm_error_googleApiKey_is_empty'); ?>
				</p>
			</div>
		<?php } else { ?>
		<input id="fk_user" type="hidden" value="<?php echo $user->id; ?>" />

		<div style="width:348px;margin:0 auto;">
			<div class="wrapper-input">
				<input id="restaurantAddress" class="inputMap" type="text"  placeholder="<?php echo $langs->transnoentities('restaurantAddress');?>" />
			</div>
			<p align="center">
				<input class="button" id="advancedhrm_gmap_validerParcoursRestaurantAddress" type="submit" value="<?php echo $langs->trans("calculateDistance"); ?>"  style="margin-left:30px"/>
			</p>
		</div>
		<?php } ?>
	</div>
</div>
