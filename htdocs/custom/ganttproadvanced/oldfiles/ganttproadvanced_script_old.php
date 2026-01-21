<script>
let firstloadof_ganttproadvanced = true;
let filter_data_ganttproadvanced = '';

$(document).ready(function() {

	$('.select_proj_visibl').select2();
	$('#search_tasktype').select2();
	$('.select_affecteduser').select2();
	$('.selected_columns').select2();

	
	$('#search_status, .search_category, #search_customer, #search_userid, #search_tasktype').on('change', function() {
    	ganttproadvanced_refreshfilter();
	});

	$('#selectallprojects').click(function(event) {
		event.preventDefault();
		ganttproadvanced_refreshfilter(selectallornone = 1);
	});
	$('#selectnoneprojects').click(function(event) {
		event.preventDefault();
		ganttproadvanced_refreshfilter(selectallornone = 2);
	});
	
	$('.opensearch').click(function(){
		$('.ganttproadvancedcontainer').removeClass('ganttproadvancedclosedsearchdiv');
		$('.ganttproadvancedfilterdiv').show();
		$('.opensearch').addClass('unvisible');
		$('.closesearch').removeClass('unvisible');
	});

	$('.closesearch').click(function(){
		$('.ganttproadvancedcontainer').addClass('ganttproadvancedclosedsearchdiv');
		$('.ganttproadvancedfilterdiv').hide();
		$('.closesearch').addClass('unvisible');
		$('.opensearch').removeClass('unvisible');
	});

});

$(window).on('load', function() {
	$('#ganttproadvanced_windowganttloaded').val(1);
});

// ------------------------------------------------------------------------------------------- Configuration
	
	<?php 
		if($debutyear > 0 && $debutmonth > 0 && $finyear > 0 && $finmonth > 0) { 
			$prev = dol_get_prev_month($debutmonth, $debutyear);
			$ystart = $prev['year'];
			$mstart = $prev['month'];

			?>

			var ystart = <?php echo (int) $ystart ?>;
			var mstart = <?php echo (int) $mstart ?>;

			var yend = <?php echo (int) $finyear ?>;
			var mend = <?php echo (int) $finmonth ?>;

			if(ystart > 0 && mstart > 0 && yend > 0 && mend > 0) {

				gantt.config.project_start = new Date(ystart, mstart, 1);
				gantt.config.project_end = new Date(yend, mend, 1);

				gantt.config.start_date = new Date(ystart, mstart, 1);
				gantt.config.end_date = new Date(yend, mend, 1);
			}

			<?php
		}
	?>


	gantt.plugins({ undo: true, marker: true, tooltip: true, quick_info: true, auto_scheduling: true });

	gantt.config.progress_start=0;
	gantt.config.progress_end=100;

	
	gantt.config.wheel_scroll_sensitivity = {x: 0.2,y: 0.3};

	gantt.config.auto_scheduling = false; 
	gantt.config.auto_scheduling_strict = true;
	gantt.config.show_errors = false;
	gantt.config.wide_form = true; 
	gantt.config.undo = true;
	gantt.config.redo = false;
	gantt.config.order_branch = false;
	gantt.config.row_height = 24;
	gantt.config.bar_height = 16;
	gantt.config.scale_height = 50;

	gantt.config.auto_types = true;
	gantt.config.min_column_width = 80;
	gantt.config.fit_tasks = false;
	gantt.config.quickinfo_buttons = ["icon_delete", "icon_edit", "icon_clone"];
	gantt.config.date_format = "%Y-%m-%d";
	// gantt.config.order_branch = true;
	gantt.config.order_branch = "marker";

	<?php if($ganttproadvanced->gantt_grid_width > 0) { ?>
		gantt.config.grid_width = <?php echo (int) $ganttproadvanced->gantt_grid_width; ?>;
	<?php } ?>

	// gantt.config.date_format = "%Y-%m-%d %H:%i";
	// gantt.config.date_grid = "%d-%m-%Y";
	// gantt.config.drag_move = false;
	// gantt.config.drag_links = false;
	// gantt.config.drag_project = false;
	// gantt.config.drag_task = false;
	// gantt.config.open_tree_initially = true;

	// gantt.plugins({ tooltip: true }); 
	// gantt.attachEvent('onError', function(errorMessage){
	// debugger;
	// return true;
	// });

	<?php if($ganttproadvanced->excludeweekend > 0) { ?>
		gantt.config.work_time = true;
	<?php } ?>

	gantt.config.scales = [
		{unit: "month", step: 1, format: "%F, %Y"},
		{unit: "day", step: 1, format: "%j %D"}
	];
	<?php if($ganttproadvanced->modify_parenttasks){ ?>
		gantt.config.auto_types = false;
	<?php } ?>

	<?php if(!$ganttproadvanced->showweekend){?>
		gantt.ignore_time = function(date){
		   if(date.getDay() == 0 || date.getDay() == 6)
		      return true;
		};
	<?php } ?>
// ------------------------------------------------------------------------------------------- Creating Custom Element
	gantt.form_blocks["color"] = {
		render: function (sns) {
	        return "<div class='dhx_cal_ltext' style='height:60px;'>"+"<input class='editor_color' type='color'></div>";
	    },
	    set_value: function (node, value, task) {
	        node.querySelector(".editor_color").value = value || "";
	    },
	    get_value: function (node, task) {
	    	if(!firstloadof_ganttproadvanced)
				setMarkerHeightAsHeightOfTaskRowForAllTasks();
	    	// console.log( node.querySelector(".editor_color").value);
	        return node.querySelector(".editor_color").value;
	    },
	    focus: function (node) {
	        var a = node.querySelector(".editor_color");
	        a.select();
	        a.focus();
	    }
	};

	// --------------------------------------------------------------------------------------------------------------------------------------------------------
	// --------------------------------------------------------------------------------------------------------------------------------------------------------
	// --------------------------------------------------------------------------------------------------------------------------------------------------------

	<?php if($ganttproadvanced->use_task_dependencies) { ?>

		gantt.form_blocks["ganttproadvancedrelatedtask"] = {
			render: function (sns) {
				<?php
					$out = '';
					// $out = img_picto('', 'project');
					// $out .= $ganttproadvancedutils->ganttproadvancedselectProjectTasks($objtask->array_options["options_ganttproadvancedrelatedtask"]);
				?>
		        return '<div class="dhx_cal_ltext container_ganttproadvancedrelatedtask" style="height:60px;"><?php echo dol_escape_js($out);?></div>';
		    },
		    set_value: function (node, value, task) {
				// node.querySelector("#options_ganttproadvancedrelatedtask").value=value;

				var currentmodiftask = 0;

				if(task && (/task/.test(task.id))) {
					currentmodiftask = task.id;
				}
				getOrSetRelatedTasks(currentmodiftask, value);

				// if(task) {
					// node.querySelector("#options_ganttproadvancedrelatedtask").setAttribute("onchange", "changeStartEndDate('"+task.id+"')");
					// if(/task/.test(task.id)) {
					// 	tmptaskid = task.id.replace('task', '');
					// 	console.log(tmptaskid);
					// 	if(node.querySelector('#options_ganttproadvancedrelatedtask option[value="'+tmptaskid+'"]')) {
					// 		node.querySelector('#options_ganttproadvancedrelatedtask option[value="'+tmptaskid+'"]').setAttribute("disabled", true);
					// 	}
					// 	currentmodiftask = value;
					// }
				// }
		    },
		    get_value: function (node, task) {

		    	var valuerelated = '';
		    	if(node.querySelector("#options_ganttproadvancedrelatedtask")) {
		    		valuerelated = node.querySelector("#options_ganttproadvancedrelatedtask").value;
		    	}

		    	task.label_relatedtask = $("select#options_ganttproadvancedrelatedtask option:selected").text();

		        return valuerelated;
		    },
		    focus: function (node, task) {
		    },
		};

		// --------------------------------------------------------------------------------------------------------------------------------------------------------

		gantt.form_blocks["ganttproadvancedtyperelation"] = {
			render: function (sns) {
				<?php
					$typerelation = isset($objtask->array_options["options_ganttproadvancedtyperelation"]) ? $objtask->array_options["options_ganttproadvancedtyperelation"] : '';
					$out = '';
					// if($extrafields->attributes[$objtask->table_element]['enabled']['ganttproadvancedtyperelation'] && ($extrafields->attributes[$objtask->table_element]['list']['ganttproadvancedtyperelation'] == 1 || $extrafields->attributes[$objtask->table_element]['list']['ganttproadvancedtyperelation'] == 3)){
						$out = $ganttproadvanced->showInputFieldGanttpro($extrafields, 'ganttproadvancedtyperelation',  $typerelation, '', $keysuffix='', '', 'width200', 0, $objtask->table_element, 0);
					// }
				?>
		        return '<div class="dhx_cal_ltext select_typerelation"><?php echo $out;?></div>';
		    },
		    set_value: function (node, value, task) {
		    		
	        	// var id_parent = task.parent.replace('task', '');
				// $(node).parents('.gantt_wrap_section').show();
	        	// node.querySelector(".select_typerelation input[name='id_parent']").value=task.parent;
	        	// if(task.id && (/task/.test(task.id))){
		        // 	var id_task = task.id.replace('task', '');
		        // 	node.querySelector(".select_typerelation input[name='id_task']").value=task.id;
	        	// }

				node.querySelector("#options_ganttproadvancedtyperelation").value=value;
				if(task && task.id){
					node.querySelector("#options_ganttproadvancedtyperelation").setAttribute("onchange", "changeStartEndDate('"+task.id+"')");
				}

		    },
		    get_value: function (node, task) {
		    	// task.ganttproadvancedtyperelation = node.querySelector("#options_ganttproadvancedtyperelation").value; 
		    	task.label_typerelation = $("select#options_ganttproadvancedtyperelation option:selected").text();
		        return node.querySelector("#options_ganttproadvancedtyperelation").value;
		    },
		    focus: function (node, task) {
		    }
		};

		// --------------------------------------------------------------------------------------------------------------------------------------------------------

		gantt.form_blocks["ganttproadvancednumberdayslate"] = {
			render: function (sns) {
		        return "<div class='dhx_cal_ltext' style='height:60px;'>"+"<input class='numberdayslate width75' name='options_ganttproadvancednumberdayslate' id='options_ganttproadvancednumberdayslate' type='number' min='0'></div>";
		    },
		    set_value: function (node, value, task) {
		        node.querySelector(".numberdayslate").value = value || "";
		        
		        if(task) {
					node.querySelector("#options_ganttproadvancednumberdayslate").setAttribute("onchange", "changeStartEndDate('"+task.id+"')");
					node.querySelector("#options_ganttproadvancednumberdayslate").setAttribute("onkeyup", "changeStartEndDate('"+task.id+"')");
		        }
		    },
		    get_value: function (node, task) {
		    	// console.log( node.querySelector(".numberdayslate").value);
		        return node.querySelector(".numberdayslate").value;
		    },
		    focus: function (node) {
		        var a = node.querySelector(".numberdayslate");
		        a.select();
		        a.focus();
		    }
		};

	<?php } ?>

	// --------------------------------------------------------------------------------------------------------------------------------------------------------
	// --------------------------------------------------------------------------------------------------------------------------------------------------------
	// --------------------------------------------------------------------------------------------------------------------------------------------------------

	gantt.form_blocks["planned_workload"] = {
		render: function (sns) {
	        return '<div class="dhx_cal_ltext" style="height:60px;">'+
	        	'<?php echo $ganttproadvanced->selectDurationGanttpro('planned_workload', '', 0, 'text');?></div>';
	    },
	    set_value: function (node, value, task) {

	        // $hour = DurationHour(node);
	        // $min = DurationMin(node);
	        if(task.planned_workload){
		        var duration = task.planned_workload.split(':');
	
		        if(duration[0])
			        node.querySelector(".inputhour").value = duration[0] || "";
	
		        if(duration[1])
			        node.querySelector(".inputminute").value = duration[1] || "";
	        }
	        // node.querySelector(".planned_workloadmin").value = valuemin || "";
	    },
	    get_value: function (node, task) {
    	
    	    hour = DurationHour(node);
	        min = DurationMin(node);
	    	
	    	$planned_workload = (hour * 3600)+(min*60);
	    	var h = Math.floor($planned_workload / 3600); //Get whole hours
			$planned_workload -= h * 3600;
			var m = Math.floor($planned_workload / 60); //Get remaining minutes
			$planned_workload -= m * 60;
			$planned_workload = h+':'+m
	    	task.planned_workload = $planned_workload;
	    	return $planned_workload;
	    },
	    focus: function (node) {
	    }
	};
	
	<?php if(floatval(DOL_VERSION) > 14) { ?>
		gantt.form_blocks["budget"] = {
			render: function (sns) {
		        return "<div class='dhx_cal_ltext' style='height:60px;'>"+
		            "<input class='budget' type='number'></div>";
		    },
		    set_value: function (node, value, task) {
		        node.querySelector(".budget").value = value || "";
		    },
		    get_value: function (node, task) {
		    	// console.log(node.querySelector(".budget").value);
		    	task.budget = node.querySelector(".budget").value; 
		        return node.querySelector(".budget").value;
		    },
		    focus: function (node) {
		        // var a = node.querySelector(".budget");
		        // a.select();
		        // a.focus();
		    }
		};
	<?php } ?>

	gantt.form_blocks["progress"] = {
		render: function (sns) {
	        return '<div class="dhx_cal_ltext" style="height:60px;">'+
	        	'<?php echo $ganttproadvanced->selectPercentGanttpro('', 'progress', 0, 1, 0, 100, 1);?></div>';
	    },
	    set_value: function (node, value, task) {
	    	progress = (value*100).toFixed(0);
	    	// console.log(progress);
	        node.querySelector(".progress").value = progress || "";
	    },
	    get_value: function (node, task) {
	    	value = (node.querySelector(".progress").value)/100;
	        return value;
	    },
	    focus: function (node) {
	    }
	};
	
	gantt.form_blocks["contacts"] = {
		render: function (sns) {
	        return '<div class="dhx_cal_ltext" style="height:60px;"><select name="contacts[]" class="contacts" multiple>'+
	        	'<?php echo dol_escape_js($ganttproadvanced->selectForaffecteduserection(1)); ?></select></div>';
	    },
	    set_value: function (node, value, task) {
	    	selectedcontacts = [];
	    	if(value){
	    		value = value.replace(/^,|,$/g,'');
				var selectedcontacts = value.split(',');
	    	}
    		$('.gantt_wrap_section select.contacts').val(selectedcontacts).trigger('change.select2');
	    },
	    get_value: function (node, task) {
	    	// contacts = node.querySelectorAll(".contacts option");
	    	// value = "";

	    	// for (var i = 0; i < contacts.length; i++) {
	    	// 	if(contacts[i].selected ){
	    	// 		// console.log(contacts[i].value);
	    	// 		value += contacts[i].value;
	    	// 		if(i+1 < contacts.length-2){
	    	// 			// console.log('i: '+i);
	    	// 			// console.log('length: '+contacts.length);
	    	// 			value += ',';
	    	// 		}
	    	// 	}
			// }

	    	value = '';
	    	contacts = $('.gantt_wrap_section select.contacts').val();

	    	if(contacts.length > 0) {
	    		value = contacts.join(",");
	    	}
	    	
			value = value.replace(/^,|,$/g,'');
			task.contacts = value;

	        return value;
	    },
	    focus: function (node) {
	    }
	};

	var projects_excluded = <?php echo json_encode($ganttproadvanced->projects_excluded); ?>;

    gantt.form_blocks["datepicker"] = {
        render: function (sns) { //sns - the section's configuration object
            return "<div class='gantt-lb-datepicker'>"+
                "<span class='fromtospan'><?php echo $langs->trans('from'); ?></span>"+
                "<input type='text' name='start'>"+
                "<span class='fromtospan'><?php echo $langs->trans('to'); ?></span>"+
                "<input type='text' name='end'>"+
                "</div>";
        },
        set_value: function (node, value, task, section) {
            //node - an html object related to the html defined above
            //value - a value defined by the map_to property
            //task - the task object
            //section- the section's configuration object
            startDatepicker(node).datepicker({
                dateFormat: "dd/mm/yy",
                onSelect: function (dateStr) {

                    var date = startDatepicker(node).datepicker('getDate');
		 			var d = new Date(date.getFullYear(), date.getMonth()+1, 0);

		 			endDateInput(node).datepicker('destroy');
		 			endDateInput(node).datepicker({
	                	dateFormat: "dd/mm/yy",
		                onSelect: function (dateStr) {
	                	}
		            });

                	<?php if($ganttproadvanced->max_period_task_month) { ?>

                			theprojectid = task.currentprojectid;

		        			if(!theprojectid && task.id) {
								taskparentid = gantt.getParent(task.id);
								if(taskparentid) {
									var tmptaskparent = gantt.getTask(taskparentid);
									theprojectid = tmptaskparent.currentprojectid
								}
		        			}

			        		if(typeof theprojectid === 'undefined' || !(theprojectid in projects_excluded)){

					 			endDateInput(node).datepicker('destroy');
					            endDateInput(node).datepicker({
				                	dateFormat: "dd/mm/yy",
					                minDate: date,
					                maxDate: d,
					                onSelect: function (dateStr) {
					                }
					            });
				 				endDateInput(node).datepicker('change');
			 				}

			        <?php } ?>

                    var endValue = endDateInput(node).datepicker('getDate');
                    var startValue = startDatepicker(node).datepicker('getDate');

                    if(startValue && endValue){
                        if(endValue.valueOf() <= startValue.valueOf()){
                            endDateInput(node).datepicker("setDate", d);
                        }
                    }

                }
            });
 			
            startDatepicker(node).datepicker("setDate", task.start_date);

 			var date = startDatepicker(node).datepicker('getDate');
 			var d = new Date(date.getFullYear(), date.getMonth()+1, 0);

 			endDateInput(node).datepicker('destroy');
 			endDateInput(node).datepicker({
                dateFormat: "dd/mm/yy",
                onSelect: function (dateStr) {
                }
            });

        	<?php if($ganttproadvanced->max_period_task_month) { ?>

        			theprojectid = task.currentprojectid;

        			if(!theprojectid && task.id) {
						taskparentid = gantt.getParent(task.id);
						if(taskparentid) {
							var tmptaskparent = gantt.getTask(taskparentid);
							theprojectid = tmptaskparent.currentprojectid
						}
        			}

	        		if(typeof theprojectid === 'undefined' || !(theprojectid in projects_excluded)){
			 			endDateInput(node).datepicker('destroy');
			            endDateInput(node).datepicker({
			                dateFormat: "dd/mm/yy",
			                minDate: date,
			                maxDate: d,
			                onSelect: function (dateStr) {
			                }
			            });
			 			endDateInput(node).datepicker('change');
		 			}

	        <?php } ?>

	        var end_date = task.end_date;
	        // console.log(task);
			if(!gantt.isTaskExists(task.id) || Math.floor(task.id) == task.id){
				var end_date = d;
				task.end_date = end_date;
				gantt.updateTask(task.id);
				// console.log('Yert');
			}
            endDateInput(node).datepicker("setDate", end_date);

        },
        get_value: function (node, task, section) {
            if(task.start_date && task.end_date) {
                var start = startDatepicker(node).datepicker('getDate');

                var end =  endDateInput(node).datepicker('getDate');
                if(end.valueOf() < start.valueOf()){
                    // end = gantt.calculateEndDate({
                    //     start_date: start, duration: 1, task:task
                    // });
	 				var end = new Date(start.getFullYear(), start.getMonth()+1, 0);
                }
                task.start_date = start;

                // if(end) {
                // 	// end.setHours(23,59,0,0);
                // }

                task.end_date = end;                 
            	task.duration = gantt.calculateDuration(task);
            }
        },
        focus: function (node) {
 
        }
    }
	
	<?php
		if($extrafields->attributes[$objtask->table_element]['label']){
			foreach ($extrafields->attributes[$objtask->table_element]['label'] as $key => $label) {
				if($extrafields->attributes[$objtask->table_element]['enabled'][$key] && ($extrafields->attributes[$objtask->table_element]['list'][$key] == 1 || $extrafields->attributes[$objtask->table_element]['list'][$key] == 3)){
					// if($key != 'ganttproadvancedcolor' && $key != 'ganttproadvancedtyperelation'){
					if(!isset($ganttproadvanced->extrafieldstohide[$key])){
						if($extrafields->attributes[$objtask->table_element]['type'][$key] == 'date' || $extrafields->attributes[$objtask->table_element]['type'][$key] == 'datetime'){
							?>
								gantt.form_blocks["datepicker_<?php  echo $key;?>"] = {
							        render: function (sns) { //sns - the section's configuration object
							            return "<div class='gantt-lb-datepicker'>"+
							                "<input type='text'autocomplete='off' name='<?php echo $key; ?>'>"+
							                "</div>";
							        },
							        set_value: function (node, value, task, section) {
							        	// console.log(task);
								        $(node).find("input[name='<?php echo $key; ?>']").datepicker({
							                dateFormat: "dd/mm/yy"
							            });
							            // console.log(task.<?php echo $key; ?>);
							            $date = task.<?php echo $key; ?>;
							            if($date){
								            $date  = new Date($date);
	            							$(node).find("input[name='<?php echo $key; ?>']").datepicker("setDate", $date);
							            }

							        },
							        get_value: function (node, task, section) {
							        	// console.log('get');
						                var date = $(node).find("input[name='<?php echo $key; ?>']").datepicker('getDate');
						                task.<?php echo $key ?> = date
							        },
							        focus: function (node) {
							        }
							    };
							<?php
						}
						else{
							if($extrafields->attributes[$objtask->table_element]['type'][$key] == 'select' || $extrafields->attributes[$objtask->table_element]['type'][$key] == 'sellist') {
								$valextrafield = isset($objtask->array_options["options_".$key]) ? $objtask->array_options["options_".$key] : '';

								$out = $ganttproadvanced->showInputFieldGanttpro($extrafields, $key,  $valextrafield, '', $keysuffix='', '', 0, 0, $objtask->table_element);
							}
							else
								$out = $extrafields->showInputField($key, $value, '', $keysuffix='', '', 0, 0, $objtask->table_element);
							?>
								gantt.form_blocks["<?php echo $key ?>"] = {
									render: function (sns) {
								        return '<div class="dhx_cal_ltext" style="height:60px;"><?php echo dol_escape_js($out);?></div>';
								    },
								    set_value: function (node, value, task) {
							        	// console.log('champ: <?php echo $key; ?>');
							        	// console.log(task);
							        	// if('<?php echo $key ?>' == 'categinterv') {
							        	// 	console.log('value: '+value);
							        	// }
            							node.querySelector("#options_<?php echo $key; ?>").value=value;
            							if(node.querySelector("select#options_<?php echo $key; ?>")){
            								if($("#options_<?php echo $key ?> option[value='"+value+"']").length > 0) {
	            								node.querySelector("#options_<?php echo $key ?> option[value='"+value+"']").setAttribute('selected', 'selected');
	            								if($("select#options_<?php echo $key; ?>").hasClass('select2-hidden-accessible')) {
	            									$("select#options_<?php echo $key; ?>").trigger('change').select2();
	            								}
            								}
            							}
								    },
								    get_value: function (node, task) {
							          	// console.log(node.querySelector("#options_<?php echo $key ?>").value);
								    	task.<?php echo $key; ?> = node.querySelector("#options_<?php echo $key; ?>").value; 
								    	task.options_<?php echo $key; ?> = $("select#options_<?php echo $key; ?> option:selected").text(); 
								        return node.querySelector("#options_<?php echo $key; ?>").value;
								    },
								    focus: function (node) {
								    }
								};
							<?php
						}
						
					}
				}
			}
		}
	?>

// ------------------------------------------------------------------------------------------- Configuring Lightbox Elements
	// gantt.config.lightbox.project_sections = gantt.config.lightbox.sections;

	gantt.config.lightbox.project_sections =[
	    {name:"label", height:70, map_to:"text", type:"textarea", focus:true, default_value:'<?php echo dol_htmlentitiesbr_decode($langs->trans('NewProject')); ?>'},
	    {name:"description", height:70, map_to:"description", type:"textarea"},
	    {name:"note_private", height:70, map_to:"note_private", type:"textarea"},
	];  

// ------------------------------------------------------------------------------------------- Lightbox Controls
	gantt.config.lightbox.sections=[
	    {name:"label", height:70, map_to:"text", type:"textarea", focus:true, default_value:'<?php echo dol_htmlentitiesbr_decode($langs->trans('NewTask')); ?>'},
	  
	    <?php 
	    $jquernamesect = '';

	    if($ganttproadvanced->coloredbyuser) {
	    	$namefield = 'affected_user';

    		$jquernamesect .= 'gantt.locale.labels.section_'.$namefield.' = "'.dol_escape_js($nametypecontact).'";';

    		echo '{name:"'.$namefield.'", height:26, map_to:"'.$namefield.'", type:"select", default_value:"'.$user->id.'", onchange:function(){selectAffectedUsersChanged(this)}, options: [';
	    		echo '{key:-1, label: ""},';
	    		echo $ganttproadvanced->_data_affecteduser;
    		echo ']},';
    		if(($ganttproadvanced->changecolortaskbyadmin && $user->admin) || !$ganttproadvanced->changecolortaskbyadmin)
				echo '{name:"color", label:"Color", height:28, map_to:"color", type:"color",default_value:"'.$colordefaulttask.'"},';
	    } else { 
    		if(($ganttproadvanced->changecolortaskbyadmin && $user->admin) || !$ganttproadvanced->changecolortaskbyadmin)
				echo '{name:"color", label:"Color", height:28, map_to:"color", type:"color",default_value:"'.$colordefaulttask.'"},';
	    }
	    ?>

	    {name:"time", height:72, map_to:"auto", type:"datepicker"},

	    <?php
    	if($ganttproadvanced->use_task_dependencies) {
	    	echo '{name:"ganttproadvancedrelatedtask", height:28, map_to:"ganttproadvancedrelatedtask", type:"ganttproadvancedrelatedtask",default_value:"0"},';
			echo '{name:"ganttproadvancedtyperelation", label:"TypeRelation", height:28, map_to:"ganttproadvancedtyperelation", type:"ganttproadvancedtyperelation",default_value:"'.ganttproadvanced::TYPE_END_START.'"},';
	    	echo '{name:"ganttproadvancednumberdayslate", height:28, map_to:"ganttproadvancednumberdayslate", type:"ganttproadvancednumberdayslate",default_value:"0"},';
    	}
	    ?>

		// {name:"contacts", label:"Contacts", height:28, map_to:"contacts", type:"select", options: [{key:-1, label:""}, <?php echo $ganttproadvanced->selectForaffecteduserection()?>]},
		{name:"contacts", label:"Contacts", height:28, map_to:"contacts", type:"contacts"},

		{name:"planned_workload", label:"PlannedWorkload", height:28, map_to:"planned_workload", type:"planned_workload",default_value:""},
		{name:"progress", label:"ProgressDeclared", height:28, map_to:"progress", type:"progress",default_value:""},
	    {name:"description", height:70, map_to:"description", type:"textarea"},
		<?php if(floatval(DOL_VERSION) > 14) { ?>
			{name:"budget", label:"Budget", height:28, map_to:"budget", type:"budget",default_value:""},
		<?php } ?>
	    {name:"note_private", height:70, map_to:"note_private", type:"textarea"},

		<?php
			if($extrafields->attributes[$objtask->table_element]['label']){
				foreach ($extrafields->attributes[$objtask->table_element]['label'] as $key => $label) {
					if($extrafields->attributes[$objtask->table_element]['enabled'][$key] && ($extrafields->attributes[$objtask->table_element]['list'][$key] == 1 || $extrafields->attributes[$objtask->table_element]['list'][$key] == 3)){
						// if($key != 'ganttproadvancedcolor' && $key != 'ganttproadvancedtyperelation'){
						if(!isset($ganttproadvanced->extrafieldstohide[$key])){
							if($extrafields->attributes[$objtask->table_element]['type'][$key] == 'date' || $extrafields->attributes[$objtask->table_element]['type'][$key] == 'datetime'){
								echo '{name:"datepicker_'.$key.'", label:"'.$label.'", height:28, map_to:"datepicker_'.$key.'", type:"datepicker_'.$key.'",default_value:""},';
							}
							else {
								echo '{name:"'.$key.'", label:"'.$label.'", height:28, map_to:"'.$key.'", type:"'.$key.'",default_value:""},';
							}
						}
					}
				}
			}
		?>
	]

	<?php echo $jquernamesect; ?>

// ------------------------------------------------------------------------------------------- return false to discard the resize action
gantt.attachEvent("onGridResizeEnd", function(old_width, new_width){

	// console.log("old_width : "+old_width);
	// console.log("new_width : "+new_width);
	if(new_width != old_width) {

		$.ajax({
	        data:{
	        	'ajxaction': 'changewidthgrid'
	        	,'new_width': new_width
	        },
	        url:"<?php echo dol_escape_js(dol_buildpath('/ganttproadvanced/ganttproadvanced_ajax.php',1)); ?>",
	        type:'POST',
	        success:function(returned){
	            if(returned) {
	            	console.log(returned);
	            }
	        }
	    });
	    // message = null;
	    // gantt.message(`Grid is now <b>${new_width}</b>px width`);
	}
    return true;
});
// ------------------------------------------------------------------------------------------- onBeforeTaskMove : prevent moving to another sub-branch
	var candrag = 0;
	gantt.attachEvent("onBeforeTaskMove", function(id, parent, tindex){
	    var task = gantt.getTask(id);
	    if(task.parent != parent) {
	    	candrag = 0;
	    	gantt.message({type:"error", text:"<?php echo dol_escape_js($langs->trans("Changingparenttaskprohibited")); ?>"}); // warning error info
	        return false;
	    }

	    candrag = 1;
	    return true;
	});

// ------------------------------------------------------------------------------------------- onRowDragStart & onRowDragEnd : drag the root node under another root and you want visually inform the user about this
	var drag_id = null;

	gantt.attachEvent("onRowDragStart", function(id, target, e) {
	    drag_id = id;
	    var viewbyresource = initialviewmodevariable();

	    if(viewbyresource) return false;

	    return true;
	});

	gantt.attachEvent("onRowDragEnd", function(id, target) {
	    // console.log('onRowDragEnd');

		if(!candrag) return 0;

	    drag_id = null;

	    var task = gantt.getTask(id);

	    var project = gantt.getTask('project'+task.projectid);

	    var arrsubtasks = [];

	    gantt.eachTask(function getChildTasks(task){ 
	    	tmptaskid = task.id.replace('task', '');
	    	arrsubtasks.push(tmptaskid);
	    }, project.id);

    	// console.log('arrsubtasks : '+arrsubtasks);

	    if(project.id) {
	    	if(arrsubtasks) {
		    	$.ajax({
			        data:{
			        	'ajxaction': 'changeordertasks'
			        	,'projectid': task.projectid
			        	,'arrsubtasks': arrsubtasks
			        },
			        url:"<?php echo dol_escape_js(dol_buildpath('/ganttproadvanced/ganttproadvanced_ajax.php',1)); ?>",
			        type:'POST',
			        success:function(returned){
			            if(returned) {
			            	console.log(returned);
			            }
			        }
			    });
	    	}
	    }

	    gantt.render();
	});
	 
	// gantt.templates.grid_row_class = function(start, end, task){
	//     if(drag_id && task.id != drag_id){
	//         if(task.$level != gantt.getTask(drag_id).$level)
	//             return "cant-drop";
	//         }
	//     return "";
	// };


// ------------------------------------------------------------------------------------------- onLightbox : fires after the user has opened the lightbox
    gantt.attachEvent("onLightbox", function (task_id){

    	$('#ui-datepicker-div').removeClass('month_year_datepicker');
	   	LightboxOpened(task_id);

	   	// console.log('onLightbox');
	   	if(Math.floor(task_id) == task_id) {

			var contentheader = '';

			var datestart = $('.gantt_cal_light_wide input[name="start"]').val();
			var dateend = $('.gantt_cal_light_wide input[name="end"]').val();

			d = new Date(datestart.split("/").reverse().join("-"));
			dd = d.getDate(); mm = gantt.locale.date.month_full[d.getMonth()]; yy = d.getFullYear();
			var newstartdate = ("0" + dd).slice(-2)+" "+mm+" "+yy;

			d = new Date(dateend.split("/").reverse().join("-"));
			dd = d.getDate(); mm = gantt.locale.date.month_full[d.getMonth()]; yy = d.getFullYear();
			var newenddate = ("0" + dd).slice(-2)+" "+mm+" "+yy;

			contentheader = newstartdate + ' - ' + newenddate;
	   		// console.log('contentheader : '+contentheader);

			$('.gantt_cal_light_wide .gantt_cal_ltitle .gantt_time').html(contentheader);
	   }
	});

// ------------------------------------------------------------------------------------------- Todays Marker
	var dateToStr = gantt.date.date_to_str(gantt.config.task_date);
	var markerId = gantt.addMarker({  
	    start_date: new Date(), 
	    css: "today", 
	    text: "<?php echo dol_escape_js($langs->trans("Now")); ?>", 
	    title: dateToStr(new Date()) 
	});
	gantt.getMarker(markerId); //->{css:"today", text:"Now", id:...}


	gantt.templates.progress_text = function (start, end, task) {
		return formatProgress(task);
	};

	gantt.templates.task_class = function (start, end, task) {

		var children = gantt.getChildren(task.id);

		if (task.type == gantt.config.types.project <?php if(!$ganttproadvanced->modify_parenttasks){ ?> || children.length <?php } ?>)
			return "hide_project_progress_drag";
	};

// ------------------------------------------------------------------------------------------- Recalculate progress
	(function dynamicProgress() {

		gantt.attachEvent("onParse", function () {
			// console.log('onParse');
			gantt.eachTask(function (task) {
				task.progress = calculateSummaryProgress(task);
			});
		});
		// gantt.attachEvent("onBeforeTaskUpdate", function(id){
		// 	var task = gantt.getTask(id);
		// 	task.end_date.setHours(23,59,0,0);
		// });

		gantt.attachEvent("onGanttRender", function (id) {
        	setMarkerHeightAsHeightOfTaskRowForAllTasks();
		});

		gantt.attachEvent("onAfterTaskUpdate", function (id) {

			if($('#ganttproadvanced_windowganttloaded').val() <= 0) return 0;

			var task = gantt.getTask(id);
			// console.log('onAfterTaskUpdate : '+task.id);
			// console.log('onAfterTaskUpdate (Parent) : '+task.projectid);

			if (typeof task === 'undefined' || Math.floor(task.id) == task.id) return 0;

			if(task.duration < 0) {
				// gantt.message({type:"error", text:"<?php echo dol_escape_js($langs->trans("StartDateCannotBeAfterEndDate")); ?>"});
				// gantt.undo();
				return;
			}
			// if(id.indexOf("task") >= 0) {
			if((/task/.test(task.id))) {

				checkAlertPeriodDuree(task);

				ganttproadvancedActionTask('updatetask', task);

				// if(Math.floor(id) != id) {
				// 	taskparentid = gantt.getParent(id);
				// 	if(taskparentid) {
				// 		var tmptask = gantt.getTask(taskparentid);

				// 		if(tmptask && taskparentid && Math.floor(tmptask.id) != tmptask.id && (/task/.test(tmptask.id)) ) {

				// 			// tmptask.affected_nameuser = task.affected_nameuser;
				// 			// tmptask.contacts = task.contacts;

				// 			// console.log(tmptask.id);
				// 			// ganttproadvancedActionTask('updatetask', tmptask);
				// 		}
				// 	}
				// }

				<?php if(!$ganttproadvanced->use_task_dependencies) { ?>
					var project = gantt.getTask('project'+task.projectid);
					ganttproadvancedActionTask('updateproject', project);
				<?php } ?>

				// setMarkerHeightAsHeightOfTaskRow(task.id);

			} else if((/project/.test(task.id))) {

				ganttproadvancedActionTask('updateproject', task);
			}
			// gantt.refreshData();
			refreshSummaryProgress(gantt.getParent(id), true);

		});

		<?php if($ganttproadvanced->use_task_dependencies){ ?>
			// gantt.attachEvent("onBeforeTaskDrag", function(id){
			// 	console.log('onBeforeTaskDrag');
		    //     return false;      //denies dragging if the global task index is odd
			// });
		<?php } ?>

		gantt.attachEvent("onTaskDrag", function (id) {
			// console.log('onTaskDrag');
			refreshSummaryProgress(gantt.getParent(id), false);
		});

		// gantt.attachEvent("onTaskDblClick", function(id,e){
		//     console.log('onTaskDblClick');
		//     return true;
		// });



		// ------------------------------------------------------------------------------------------------------------------------------------------
		// ------------------------------------------------------------------------------------------------------------------------------------------


		gantt.attachEvent("onBeforeLinkAdd", function(id, link){
		    // console.log('onBeforeLinkAdd');
		    // console.log(link);

			if(Math.floor(link.id) == link.id) {
			    itcanbeadded = checkIfCanBeAddLink(link.target, link.source);

			    if(itcanbeadded) {
			    	typelinkdetail = getTheLinkTypeDetails(link.type);

			    	getOrSetRelatedTasks(link.target, link.source, true, typelinkdetail);
			    } else {

			    	if(gantt.isTaskExists(link.target)) {
						var target = gantt.getTask(link.target);
						$(".gantt_message_area .gantt-info").remove();
				    	gantt.message({type:"warning", text:"<?php echo $langs->trans("ThereIsADependencyOfTaskFor").' : '; ?>"+target.text});
				    	gantt.selectTask(target.id);
					}

			    	return false;
			    }
		    }

		});

		gantt.attachEvent("onAfterLinkDelete", function(id, link){
		    // console.log('onAfterLinkDelete');
		    // console.log(link);

			if(gantt.isTaskExists(link.target)) {

			    var targettask = gantt.getTask(link.target);

			    targettask.ganttproadvancedrelatedtask = 0;
			    targettask.ganttproadvancedtyperelation = 0;
			    targettask.label_relatedtask = '';
			    targettask.label_typerelation = '';
			    targettask.ganttproadvancednumberdayslate = 0;
			    
			    gantt.updateTask(link.target);
			}

		});

		// gantt.attachEvent("onBeforeLinkUpdate", function(id, link){
		//     console.log('onBeforeLinkUpdate');
		//     console.log(link);

		//     var typelink = getTheLinkTypeDetails(link);
		//     console.log(typelink);
		// });


		// ------------------------------------------------------------------------------------------------------------------------------------------
		// ------------------------------------------------------------------------------------------------------------------------------------------

		gantt.attachEvent("onAfterTaskAdd", function (id) {
			var task = gantt.getTask(id);

			checkAlertPeriodDuree(task);

			if(Math.floor(id) == id) {
				ganttproadvancedActionTask('createtask', task);

			}
			refreshSummaryProgress(gantt.getParent(id), true);
		});

		gantt.$click.buttons.clone=function(id){
			var task = gantt.getTask(id);

			$('.gantt_cal_quick_info').remove();

			var clone = gantt.copy(task);
            clone.id = gantt.uid();
			
			id_task_clone = clone.id;
			id_tast_toclone = task.id;

            gantt.addTask(clone, clone.parent, clone.$index);

            <?php if(!$ganttproadvanced->use_task_dependencies) { ?>
	            gantt.addLink({
	                id:clone.id,
	                source:task.parent,
	                target:clone.id,
	                type:"1"
	            });
        	<?php } ?>

			refreshSummaryProgress(gantt.getParent(id), true);
		};

		// // CLose projet By Hassnae
		gantt.$click.buttons.close=function(id){
			var task = gantt.getTask(id);
			ganttproadvancedActionTask('closeobject', task);
		};


		(function () {
			var idParentBeforeDeleteTask = 0;
			gantt.attachEvent("onBeforeTaskDelete", function (id) {
				// console.log('onBeforeTaskDelete');
				var task = gantt.getTask(id);

				if(gantt.hasChild(task.id)) {
					if((/task/.test(task.id))) {
						gantt.message({type:"error", text:"<?php echo dol_escape_js($langs->trans("TaskHasChild")); ?>"}); // warning error info
					} else {
						gantt.message({type:"error", text:"<?php echo dol_escape_js($langs->trans("CantRemoveProject", $langs->transnoentitiesnoconv("ProjectOverview"))); ?>"}); // warning error info
					}
					return false;
				}

				ganttproadvancedActionTask('deleteobject', task);

				if((/task/.test(task.id))) {
					setTimeout( function() { 
						$('.gantt_marker.markertask.gantt_scale_cell.colormarker_'+task.id).remove();
					}, 500);
				}

				idParentBeforeDeleteTask = gantt.getParent(id);
			});
			gantt.attachEvent("onAfterTaskDelete", function () {
				// console.log('onAfterTaskDelete');
				refreshSummaryProgress(idParentBeforeDeleteTask, true);

	        	setMarkerHeightAsHeightOfTaskRowForAllTasks();
			});
		})();
	})();
// ------------------------------------------------------------------------------------------- END Recalculate progress

// ------------------------------------------------------------------------------------------- Columns name
	<?php 
        $selected_columns = array('duration','start_date','end_date');

	  	if(isset($user->conf->GANTTPROADVANCED_GANTT_COLUMS_TO_SHOW)) {
	  		$selected_columns = json_decode($user->conf->GANTTPROADVANCED_GANTT_COLUMS_TO_SHOW);
	  	}
		// if(isset($conf->global->GANTTPROADVANCED_COLUMS_TO_SHOW)) {
		// 	$default_columns = ($conf->global->GANTTPROADVANCED_COLUMS_TO_SHOW != -1) ? array('duration','start_date','end_date') : [];
		//   	$selected_columns = $conf->global->GANTTPROADVANCED_COLUMS_TO_SHOW ? json_decode($conf->global->GANTTPROADVANCED_COLUMS_TO_SHOW) : $default_columns;
	  	// }
	?>
	var selectedcolumns = getColumnsToShow('<?php echo json_encode($selected_columns); ?>');

	gantt.config.columns = selectedcolumns;
	
// ------------------------------------------------------------------------------------------- END Columns name
classhideweekend = '<?php echo $ganttproadvanced->showweekend ? '' : ' hideweekend'?>';
// ------------------------------------------------------------------------------------------- Week-ends
	gantt.templates.timeline_cell_class = function(task,date){
	    if(date.getDay()==0||date.getDay()==6){ 
	        return "weekend"+classhideweekend ;
	    }
	};
// ------------------------------------------------------------------------------------------- END Week-ends


// ------------------------------------------------------------------------------------------- POPUP Content
	gantt.templates.quick_info_date = function(start, end, task){
		var desc = gantt.templates.task_time(start, end, task); 
	   return desc; // Date duration in popup
	};

	gantt.templates.quick_info_content = function(start, end, task){ 

		// if((/project/.test(task.id))) {
		// }

		if (typeof task === 'undefined') return 0;

		html = getContentInfoTask(task.id);

	   	return html;
	};

	// gantt.templates.task_text = function(start, end, task){  
	//   return task.text+" <button>Text</button>";    
	// };

	gantt.templates.quick_info_class = function(start, end, task){ 

		classtoreturn = '';

		if(gantt.hasChild(task.id)) {
			// $('.gantt_qi_big_icon.icon_delete').remove();
			// classtoreturn += 'ganttproadvanced_hide_edit_button';
		}

		if(/project/.test(task.id)) {
			classtoreturn += ' ganttproadvancedhide_delete_clone_buttons';
			var viewbyresource = initialviewmodevariable();
			if(viewbyresource) {
				classtoreturn += ' ganttproadvancedhide_all_buttons';
			}
		}else{
			classtoreturn += ' ganttproadvancedhide_close_buttons';
		}




	    // return task.type == gantt.config.types.milestone ? "milestone_popup" : "";
	    return classtoreturn;
	};

	gantt.showLightbox=function(id){ 
		if(this.callEvent("onBeforeLightbox",[gantt])){

			if(!this.isTaskExists(id)) return;

			var viewbyresource = initialviewmodevariable();
			if(/project/.test(id) && viewbyresource) {
				return;
			}

			var e=this.getTask(id);

			var tmptype = e.type;

			if(Math.floor(id) != id && ((/project/.test(id)) <?php if(!$ganttproadvanced->modify_parenttasks) { ?> || this.hasChild(id) <?php } ?> )) {
				tmptype = 'project';
			}

			n=this.getLightbox(this.getTaskType(tmptype));

			this._center_lightbox(n),this.showCover(),this._fill_lightbox(id,n),this._waiAria.lightboxVisibleAttr(n),this.callEvent("onLightbox",[id])
			// $('.gantt_cal_light select, .gantt_duration .gantt_duration_value, .gantt_duration .gantt_duration_dec, .gantt_duration .gantt_duration_inc').prop("disabled", false);
			$('select option[value="milestone"]').parent('select').parent('div').parent('div').addClass('hidden');
			// $('.gantt_cal_light select, .gantt_duration .gantt_duration_value, .gantt_duration .gantt_duration_inc').hide();
			$('select option[value="milestone"]').parent('select').prop("disabled", true);
		}
	}

	gantt.hideLightbox=function(){
		var t=this.getLightbox();
		t&&(t.style.display="none"),this._waiAria.lightboxHiddenAttr(t),this._lightbox_id=null,this.hideCover(),this.callEvent("onAfterLightbox",[])
	}
// ------------------------------------------------------------------------------------------- END POPUP Content

// ------------------------------------------------------------------------------------------- Translate text 
	
	<?php
		// $day_full = [$langs->trans('SundayMin'), $langs->trans('MondayMin'), $langs->trans('TuesdayMin'), $langs->trans('WednesdayMin'), $langs->trans('ThursdayMin'), $langs->trans('FridayMin'), $langs->trans('SaturdayMin')];
		$day_full = [$langs->trans('Day0'), $langs->trans('Day1'), $langs->trans('Day2'), $langs->trans('Day3'), $langs->trans('Day4'), $langs->trans('Day5'), $langs->trans('Day6')];
		$day_short = [$langs->trans('ShortSunday'), $langs->trans('ShortMonday'), $langs->trans('ShortTuesday'), $langs->trans('ShortWednesday'), $langs->trans('ShortThursday'), $langs->trans('ShortFriday'), $langs->trans('ShortSaturday')];
		for ($i=1; $i <= 12; $i++) { 
			$month_full[] = $langs->trans('Month'.str_pad($i, 2, '0', STR_PAD_LEFT));
			// $month_short[] = $langs->trans('MonthVeryShort'.str_pad($i, 2, '0', STR_PAD_LEFT));
			$month_short[] = $langs->trans('MonthShort'.str_pad($i, 2, '0', STR_PAD_LEFT));
			$month_veryshort[] = $langs->trans('MonthVeryShort'.str_pad($i, 2, '0', STR_PAD_LEFT));

		}
	?>
	gantt.i18n.setLocale({
	    date: {
	        month_full: <?php echo json_encode($month_full); ?>,
	        month_short: <?php echo json_encode($month_short); ?>,
	        month_veryshort: <?php echo json_encode($month_veryshort); ?>,
	        day_full: <?php echo json_encode($day_full); ?>,
	        day_short: <?php echo json_encode($day_short); ?>
	    },
	    labels: {
	        new_task: "<?php echo dol_htmlentitiesbr_decode($langs->trans('NewTask')); ?>",
	        icon_save: "<?php echo $langs->trans('Save'); ?>",
	        icon_cancel: "<?php echo $langs->trans('Cancel'); ?>",
	        icon_details: "<?php echo $langs->trans('Details'); ?>",
	        icon_edit: "<?php echo $langs->trans('Edit'); ?>",
	        icon_delete: "<?php echo $langs->trans('Delete'); ?>",
	        icon_clone: "<?php echo $langs->trans('ToClone'); ?>",
	        icon_close: "<?php echo $langs->trans('Close'); ?>",
	        gantt_clone_btn: "<?php echo $langs->trans('ToClone'); ?>",
	        gantt_close_btn: "<?php echo $langs->trans('Close'); ?>",
	        gantt_save_btn: "<?php echo $langs->trans('Save'); ?>",
	        gantt_cancel_btn: "<?php echo $langs->trans('Cancel'); ?>",
	        gantt_delete_btn: "<?php echo $langs->trans('Delete'); ?>",
	        confirm_closing: "<?php echo $langs->trans('ConfirmDeleteObject'); ?>",// Your changes will be lost, are you sure?
	        confirm_deleting: "<?php echo $langs->trans('ConfirmDeleteObject'); ?>",
	        section_label: "<?php echo $langs->trans('Label'); ?>",
	        section_description: "<?php echo $langs->trans('Description'); ?>",
			<?php if(floatval(DOL_VERSION) > 14) { ?>
		        section_budget: "<?php echo $langs->trans('Budget').' ('.$langs->getCurrencySymbol($conf->currency).')'; ?>",
			<?php } ?>
	        section_contacts: "<?php echo $langs->trans('Contacts'); ?>",
	        section_planned_workload: "<?php echo $langs->trans('PlannedWorkload').' ('.$langs->trans('HourShort').':'.$langs->trans('MinuteShort').')'; ?>",
	        section_progress: "<?php echo $langs->trans('ProgressDeclared'); ?>",
	        section_time: "<?php echo $langs->trans('Period'); ?>",
	        section_color: "<?php echo $langs->trans('Color'); ?>",
	        section_type: "<?php echo $langs->trans('Type'); ?>",
	        section_note_private: "<?php echo $langs->trans('NotePrivate'); ?>",
	        section_ganttproadvancedtyperelation: "<?php echo $langs->trans('TypeRelation'); ?>",
	        section_ganttproadvancedrelatedtask: "<?php echo $langs->trans('RelatedToTask'); ?>",
	        section_ganttproadvancednumberdayslate: "<?php echo $langs->trans('NumberDaysLate'); ?>",
	 		
	        <?php
				foreach ($extrafields->attributes[$objtask->table_element]['label'] as $key => $label) {
					if($extrafields->attributes[$objtask->table_element]['enabled'][$key] && ($extrafields->attributes[$objtask->table_element]['list'][$key] == 1 || $extrafields->attributes[$objtask->table_element]['list'][$key] == 3)){
						if($extrafields->attributes[$objtask->table_element]['type'][$key] == 'date' || $extrafields->attributes[$objtask->table_element]['type'][$key] == 'datetime'){
		        			echo 'section_datepicker_'.$key.': "'.$langs->trans($label).'",';
		        		}else
		        			echo 'section_'.$key.': "'.$langs->trans($label).'",';
					}

				}
	        ?>

	        /* grid columns */
	        column_wbs: "<?php echo $langs->trans('WBS'); ?>",
	        column_text: "<?php echo $langs->trans('LabelTask'); ?>",
	        column_start_date: "<?php echo $langs->trans('DateStart'); ?>",
	        column_duration: "<?php echo $langs->trans('Duration'); ?>",
	        column_add: "",
	 
	        /* link confirmation */
	        link: "<?php echo $langs->trans('Link'); ?>",
	        confirm_link_deleting: "<?php echo dol_escape_js($langs->trans('WillBeDeleted')); ?>",
	        link_start: " (<?php echo $langs->trans('LinkStart'); ?>)",
	        link_end: " (<?php echo $langs->trans('LinkEnd'); ?>)",
	 
	        type_task: "<?php echo $langs->trans('Task'); ?>",
	        type_project: "<?php echo $langs->trans('Project'); ?>",
	        type_milestone: "<?php echo $langs->trans('Milestone'); ?>",
	 
	        minutes: "<?php echo $langs->trans('Minutes'); ?>",
	        hours: "<?php echo $langs->trans('Hours'); ?>",
	        days: "<?php echo $langs->trans('Days'); ?>",
	        weeks: "<?php echo $langs->trans('Week'); ?>",
	        months: "<?php echo $langs->trans('Months'); ?>",
	        years: "<?php echo $langs->trans('Years'); ?>",
	 
	        /* message popup */
	        message_ok: "<?php echo $langs->trans('OK'); ?>",
	        message_cancel: "<?php echo $langs->trans('Cancel'); ?>",
	 
	        /* constraints */
	        section_constraint: "<?php echo $langs->trans('Constraint'); ?>",
	        constraint_type: "<?php echo $langs->trans('Constraint type'); ?>",
	        constraint_date: "<?php echo $langs->trans('Constraint date'); ?>",
	        asap: "<?php echo $langs->trans('As Soon As Possible'); ?>",
	        alap: "<?php echo $langs->trans('As Late As Possible'); ?>",
	        snet: "<?php echo $langs->trans('Start No Earlier Than'); ?>",
	        snlt: "<?php echo $langs->trans('Start No Later Than'); ?>",
	        fnet: "<?php echo $langs->trans('Finish No Earlier Than'); ?>",
	        fnlt: "<?php echo $langs->trans('Finish No Later Than'); ?>",
	        mso: "<?php echo $langs->trans('Must Start On'); ?>",
	        mfo: "<?php echo $langs->trans('Must Finish On'); ?>",
	 
	        /* resource control */
	        resources_filter_placeholder: "<?php echo $langs->trans('Filter'); ?>",
	        resources_filter_label: "<?php echo $langs->trans('hide empty'); ?>"
	    }
	});

// ------------------------------------------------------------------------------------------- END Translate text 

// ------------------------------------------------------------------------------------------- Mouse wheel zoom */

var zoomConfig = {
	levels: [
		
		// days
		{
			name:"day",
			scale_height: 50,
			min_column_width: 20,
			scales:[
				// {unit: "day", step: 1, format: "%d %M"}
				{unit: 'month', step: 1, format: '%F, %Y'},
		       	{unit: "day", step: 1, format: "%d %S", css: function(date) { return "ganttproadvanced_gantt_scale_cell_mediumview_day"; }}

			]
		},
		// weeks
		{
			name:"week",
			scale_height: 50,
			min_column_width: 20,
			scales:[
				{unit: "week", step: 1, format: function (date) {
					var dateToStr = gantt.date.date_to_str("%d %M");
					var endDate = gantt.date.add(date, +6, "day");
					var weekNum = gantt.date.date_to_str("%W")(date);
					return "#" + weekNum + ", " + dateToStr(date) + " - " + dateToStr(endDate);
				}},
		       	{unit: "day", step: 1, format: "%j %D", css: function(date) { return "ganttproadvanced_gantt_scale_cell_mediumview_day"; }}
			]
		},
		// months
		{
			name:"month",
			scale_height: 50,
			min_column_width: 10,
			scales:[
				{unit: "month", step: 1, format: "%F, %Y"},
		       	{unit: "day", step: 1, format: "%d", css: function(date) { return "ganttproadvanced_gantt_scale_cell_smallview_day"; }}
				// {unit: "week", step: 1, format: function (date) {
				// 	var dateToStr = gantt.date.date_to_str("%d %M");
				// 	var endDate = gantt.date.add(gantt.date.add(date, 1, "week"), -1, "day");
				// 	return dateToStr(date) + " - " + dateToStr(endDate);
				// }}
			]
		},
		// quarters
		{
			name:"quarter",
			height: 50,
			min_column_width: 2,

			scales:[
				{
					unit: "quarter", step: 3, format: function (date) {
						var dateToStr = gantt.date.date_to_str("%M %y");
						var endDate = gantt.date.add(gantt.date.add(date, 3, "month"), -1, "day");
						return dateToStr(date) + " - " + dateToStr(endDate);
					}
				},
				{unit: "month", step: 1, format: "%M"},
	       	  	{unit: "day", step: 1, format: "%d", css: function(date) { return "ganttproadvanced_gantt_scale_cell_hidden_day"; }}

			]
		},
		// years
		{
			name:"year",
			scale_height: 50,
			min_column_width: 2,

			scales:[
				{unit: "year", step: 5, format: function (date) {
					var dateToStr = gantt.date.date_to_str("%Y");
					var endDate = gantt.date.add(gantt.date.add(date, 5, "year"), -1, "day");
					return dateToStr(date) + " - " + dateToStr(endDate);
				}},
	       	  	{unit: "day", step: 1, format: "%d", css: function(date) { return "ganttproadvanced_gantt_scale_cell_hidden_day"; }}
			]
		},
	],
	useKey: "ctrlKey",
	trigger: "wheel",
	element: function(){
		return gantt.$root.querySelector(".gantt_task");
	}
};

gantt.ext.zoom.init(zoomConfig);

var hourToStr = gantt.date.date_to_str("%H:%i");
var hourRangeFormat = function(step){
	return function(date){
		var intervalEnd = new Date(gantt.date.add(date, step, "hour") - 1)
		return hourToStr(date) + " - " + hourToStr(intervalEnd);
	};
};

// gantt.message({
// 	text:"Use <b>ctrl + mousewheel</b> in order to zoom",
// 	expire: 2500
// });
// ------------------------------------------------------------------------------------------- End Mouse wheel zoom */

function ZoomInNow() {
	gantt.showDate(new Date());

	// gantt.ext.zoom.setLevel("day");
	// console.log( $(".gantt_marker.today").offset().left);
	// // $( "div.timeline_cell" ).scrollTop( 300 );
	// gantt.ext.zoom.zoomIn();
	// var x = $(".gantt_marker.today").offset().left-$('.grid_cell').width();
	// var y = $(".gantt_marker.today").offset().top;
	// gantt.scrollTo(x-100, y);

}

// ------------------------------------------------------------------------------------------- SCALE GANTT */
	var els = document.querySelectorAll("input[name='scale']");
	for (var i = 0; i < els.length; i++) {
	    els[i].onclick = function(e){
	        var el = e.target;
	        var value = el.value;
	        setScaleConfig(value);
	        gantt.render();
	    };
	}
	setScaleConfig("<?php echo $search_scale; ?>");
// ------------------------------------------------------------------------------------------- END SCALE GANTT */

// ------------------------------------------------------------------------------------------- Data For GANTT 
	var data = {
		data: [ <?php echo $_data; ?> ],
		links: [ <?php echo $_links; ?> ]
	};
// ------------------------------------------------------------------------------------------- END Data For GANTT 


gantt.init("ganttproadvanced");

$(window).on('load', function() {
	gantt.parse(data);
});

// console.log(gantt.config.end_date);
// console.log('Add marcker Jalon dates !!');

setTimeout( function() { 
	<?php
	if($alldatesjalon){
		foreach ($alldatesjalon as $arr_id => $jalondet) {
			if(isset($jalondet['date'])){
				$tmpdate = $jalondet['date'];
				$tmpuser = isset($jalondet['user']) ? $jalondet['user'] : 0; 
				?>
				var id_task = '<?php echo $arr_id;?>';

				if (gantt.isTaskExists(id_task)){
					var task = gantt.getTask(id_task);
					var content = getContentInfoTask(id_task, true);

					var markerId = gantt.addMarker({  
					    start_date: new Date('<?php echo $tmpdate; ?>'), 
					    css: "markertask gantt_scale_cell colormarker_user<?php echo (int)$tmpuser; ?> colormarker_<?php echo $arr_id; ?>", 
					    text: content,
					    // text: gantt.ext.quickInfo.show(task.id),
					    title: '' 
					});
					gantt.getMarker(markerId); //->{css:"today", text:"Now", id:...}
				}
				<?php
			}
		}
	}
	?>

	setMarkerHeightAsHeightOfTaskRowForAllTasks();

}, 500);


<?php if($ganttproadvanced->scroll_currentday) { ?>
	gantt.showDate(new Date());
<?php } ?>

// ------------------------------------------------------------------------------------------- Tooltip Content */
gantt.ext.tooltips.tooltipFor({
	selector: ".gantt_scale_cell",
	html: function(event, domElement){
		return domElement.innerHTML;
	}
});
gantt.templates.tooltip_text = function(start,end,task){
	var html = getContentInfoTask(task.id, true);
    return html;
};

firstloadof_ganttproadvanced = false;
</script>