<script>
let firstloadof_ganttproadvanced = true;
let filter_data_ganttproadvanced = '';

$(document).ready(function() {

	// console.log('search_scale : <?php echo $search_scale ?>');
	// console.log('Data 1:');
	// console.log(<?php echo $_data; ?>);

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


	<?php if(!empty($ganttproadvanced->use_abnovo_datejalon)) { ?>
		$(document).on('change', '#options_abnovomoduleetape', function() {

			var tasktxtwithid = $('#ganttproadvanced_popup_task_id').val();
			var abnovomoduleetape = $(this).val();

			$.ajax({
		        data:{
		        	'ajxaction': 'getabnovomoduleetapedate',
		        	'tasktxtwithid': tasktxtwithid,
		        	'abnovomoduleetape': abnovomoduleetape,
		        },
		        url:"<?php echo dol_escape_js(dol_buildpath('/ganttproadvanced/ganttproadvanced_ajax.php',1)); ?>",
		        type:'POST',
		        success:function(etapedate){
	            	// console.log(etapedate);
		            if(etapedate){
		            	$('input[name="ganttproadvanceddatejalon"]').datepicker("setDate", etapedate);
		            }
		        }
		    });
		});
	<?php } ?>

	$(document).on('mousedown', '.viewmodebyresource_per_principal_contributeur .gantt_row[task_id*="task"]', function () {
		// console.log('mousedown viewmodebyresource_highlighted');
	    setTimeout(viewmodebyresource_highlightFromSelected, 300);
	});

});

$(window).on('load', function() {
	$('#ganttproadvanced_windowganttloaded').val(1);
});
// console.log('Data 2:');
// console.log(<?php echo $_data; ?>);

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


	// console.log('Data 3:');
	// console.log(<?php echo $_data; ?>);


	// gantt.config.start_date = new Date(ystart, mstart, 1);
	// gantt.config.end_date = new Date(yend, mend, 1);


	gantt.plugins({ undo: true, marker: true, tooltip: true, quick_info: true, auto_scheduling: true, baselines: true });

	gantt.config.progress_start=0;
	gantt.config.progress_end=100;

	// <?php if($ganttproadvanced->showhoursingantt){ ?>
	// 	gantt.config.duration_unit = "hour";//an hour
	// 	gantt.config.duration_step = 1; 
	// <?php } ?>

	gantt.config.wheel_scroll_sensitivity = {x: 0.2,y: 0.3};

	gantt.config.auto_scheduling = false;   
	gantt.config.show_baseline = true;
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
	gantt.config.date_format = "%Y-%m-%d %H:%i";
	// gantt.config.order_branch = true;
	gantt.config.order_branch = "marker";

	<?php if($ganttproadvanced->gantt_grid_width > 0) { ?>
		gantt.config.grid_width = <?php echo (int) $ganttproadvanced->gantt_grid_width; ?>;
	<?php } ?>

	// gantt.config.date_format = "%Y-%m-%d %H:%i";
	// gantt.config.date_grid = "%d-%m-%Y";

	<?php if($viewmode == 'byresource' && $ganttproadvanced->byressource_type_contacts == 'per_principal_contributeur') { ?>
		gantt.config.drag_move = false;
	<?php } ?>

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
		gantt.config.skip_off_time = true;
	<?php } ?>

	<?php if($ganttproadvanced->showhoursingantt && $ganttproadvanced->taskworkingtime){?>
		gantt.config.work_time = true;
		gantt.config.skip_off_time = true;
		// var hoursgantt = '<?php echo $hoursgantt ?>';
		// gantt.setWorkTime({day : 0, hours : ["9:00-13:00", "14:00-16:00"]});
	<?php } ?>

	gantt.setWorkTime({day : 6, hours : ["9","17"]});	


	gantt.config.calendars = [ {
        id: "custom",
        worktime: {
            hours: [9, 10, 11, 12, 13, 14, 15, 16], // de 9h à 17h
            days: [1, 2, 3, 4, 5] // jours ouvrables (lundi à vendredi)
        }
    }];


	gantt.config.calendar = "custom"; // Appliquer ce calendrier

	// Appliquer ce calendrier aux baselines
	gantt.config.baselines = {
	    calendar: "custom"
	};



	gantt.config.scales = [
		{unit: "month", step: 1, format: "%F, %Y"},
		{unit: "day", step: 1, format: "%j %D"},
		<?php if($ganttproadvanced->showhoursingantt && $ganttproadvanced->taskworkingtime){?>
			{unit: "hour", step: 1, format: "%H:%m"}
		<?php } ?>
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

	var elscales = document.querySelectorAll("input[name='scale']");
	var sclaletoactive = "<?php echo $search_scale; ?>";

	for (var i = 0; i < elscales.length; i++) {
	    elscales[i].onclick = function(e){
	        var el = e.target;
	        sclaletoactive = el.value;
	    };
	}


	// console.log('Data 4:');
	// console.log(<?php echo $_data; ?>);

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
                "<input type='text' onchange='changeStartEndDate()' name='start'>"+
                "<span class='fromtospan'><?php echo $langs->trans('to'); ?></span>"+
                "<input type='text' onchange='changeStartEndDate()' name='end'>"+
                "</div>";
        },
        set_value: function (node, value, task, section) {
        	console.log('Set val datepicker !!');
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
		                	var endValue = endDateInput(node).datepicker('getDate');
				            var startValue = startDatepicker(node).datepicker('getDate');

							console.log('Set val datepicker in End !!');
				            console.log('startValue: '+startValue);
				            console.log('endValue: '+endValue);
							calculNbTaskByUser(task.id, task.affected_user, startValue, endValue);
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
					                	var endValue = endDateInput(node).datepicker('getDate');
							            var startValue = startDatepicker(node).datepicker('getDate');

										console.log('Set val datepicker in End !!');
							            console.log('startValue: '+startValue);
							            console.log('endValue: '+endValue);
    									calculNbTaskByUser(task.id, task.affected_user, startValue, endValue);
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


        			console.log('Set val datepicker start !!');
                    console.log('startValue: '+startValue);
                    console.log('endValue: '+endValue);

    				calculNbTaskByUser(task.id, task.affected_user, startValue, endValue);
                }
            });
 			
            startDatepicker(node).datepicker("setDate", task.start_date);

 			var date = startDatepicker(node).datepicker('getDate');
 			var d = new Date(date.getFullYear(), date.getMonth()+1, 0);

 			endDateInput(node).datepicker('destroy');
 			endDateInput(node).datepicker({
                dateFormat: "dd/mm/yy",
                onSelect: function (dateStr) {
		            var endValue = endDateInput(node).datepicker('getDate');
		            var startValue = startDatepicker(node).datepicker('getDate');

					console.log('Set val datepicker in End !!');
		            console.log('startValue: '+startValue);
		            console.log('endValue: '+endValue);
					calculNbTaskByUser(task.id, task.affected_user, startValue, endValue);
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
					            var endValue = endDateInput(node).datepicker('getDate');
					            var startValue = startDatepicker(node).datepicker('getDate');

								console.log('Set val datepicker in End !!');
					            console.log('startValue: '+startValue);
					            console.log('endValue: '+endValue);
								calculNbTaskByUser(task.id, task.affected_user, startValue, endValue);
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


			// calculNbTaskByUser(task.id, task.affected_user, startValue, endValue);
            
        },
        get_value: function (node, task, section) {
        	console.log('Get val datepicker !!');

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
        	console.log('focus val datepicker !!');
 
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
							            }
            							$(node).find("input[name='<?php echo $key; ?>']").datepicker("setDate", $date);

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
							$extrafield_type = $extrafields->attributes[$objtask->table_element]['type'][$key];
							
							// ERROR JS WHEN EXTRAFIELD IS LINK TYPE
							if($extrafield_type == 'link') {
								$conf->use_javascript_ajax = 0;
							}

							if(
								$extrafield_type == 'select' 
								|| 
								$extrafield_type == 'sellist' 
								// || 
								// $extrafield_type == 'link' 
							) {
								$valextrafield = isset($objtask->array_options["options_".$key]) ? $objtask->array_options["options_".$key] : '';

								$out = $ganttproadvanced->showInputFieldGanttpro($extrafields, $key,  $valextrafield, '', $keysuffix='', '', 0, 0, $objtask->table_element);
							}
							else {
								$out = $extrafields->showInputField($key, $value, '', $keysuffix='', '', 0, 0, $objtask->table_element);
							}
							?>
								gantt.form_blocks["<?php echo $key ?>"] = {
									render: function (sns) {
								        return '<div class="dhx_cal_ltext" style="height:60px;"><?php echo dol_escape_js($out);?></div>';
								    },
								    set_value: function (node, value, task) {
							        	// console.log('champ: <?php echo $key; ?>');
							        	// console.log(task);
							        	// if('<?php echo $key ?>' == 'categinterv') {
							        		// console.log('value: '+value);
							        	// }
        								node.querySelector("#options_<?php echo $key; ?>").value = value;
            							if(node.querySelector("#options_<?php echo $key; ?>")){
            								if($("#options_<?php echo $key ?> option[value='"+value+"']").length > 0) {
	            								node.querySelector("#options_<?php echo $key ?> option[value='"+value+"']").setAttribute('selected', 'selected');
	            								if($("#options_<?php echo $key; ?>").hasClass('select2-hidden-accessible')) {
	            									$("#options_<?php echo $key; ?>").trigger('change').select2();
	            								}
            								}
            							}
								    },
								    get_value: function (node, task) {
								    	if(node.querySelector("#options_<?php echo $key; ?>")){
								          	// console.log(node.querySelector("#options_<?php echo $key ?>").value);
									    	task.<?php echo $key; ?> = node.querySelector("#options_<?php echo $key; ?>").value; 
									    	task.options_<?php echo $key; ?> = $("#options_<?php echo $key; ?> option:selected").text(); 
									        return node.querySelector("#options_<?php echo $key; ?>").value;
								    	}
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
	    		echo '{key:-1, label: ""},{key:-2, label: ""},';
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
	// console.log('onGridResizeEnd')
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
	    // var viewbyresource = initialviewmodevariable();
	    // if(viewbyresource == 2) return false;

	    var task = gantt.getTask(id);
	    if(task.parent != parent) {
	    	candrag = 0;
	    	gantt.message({type:"error", text:"<?php echo dol_escape_js($langs->trans("Changingparenttaskprohibited")); ?>"}); // warning error info
	        return false;
	    }
	    // console.log('adjustTaskHeightForBaselines in onBeforeTaskMove');
		// gantt.adjustTaskHeightForBaselines(task);

	    candrag = 1;
	    return true;
	});
	

	// console.log('Data 5:');
	// console.log(<?php echo $_data; ?>);

// ------------------------------------------------------------------------------------------- onRowDragStart & onRowDragEnd : drag the root node under another root and you want visually inform the user about this
	var drag_id = null;

	gantt.attachEvent("onRowDragStart", function(id, target, e) {
	    drag_id = id;
	    var viewbyresource = initialviewmodevariable();

	    if(viewbyresource) return false;

	    return true;
	});

	gantt.attachEvent("onRowDragEnd", function(id, target) {
	    console.log('onRowDragEnd');

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
	    // console.log('adjustTaskHeightForBaselines in onRowDragEnd');
		// gantt.adjustTaskHeightForBaselines(task);

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

	   	var popup_task_id = task_id;
	   	// console.log('onLightbox');
    	var task = gantt.getTask(task_id);
    	if(task.id){
    		calculNbTaskByUser(task.id, task.affected_user, task.start_date, task.end_date);
    	}

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
	   		
	   		taskparentid = gantt.getParent(task_id);
	   		popup_task_id = taskparentid;
	   	}


	   	$('#ganttproadvanced_popup_task_id').remove();
	   	hiddeninputs = '<input id="ganttproadvanced_popup_task_id" value="'+popup_task_id+'" type="hidden">';
	   	nbtaskbuyuserinperiod = '<div class="inline-block totaltasksbyuser pull-right"><span class="nbtasks"></b> <?php echo $langs->trans('Tasks') ?></div>';
		$('.gantt_cal_light_wide .gantt_cal_ltitle').append(hiddeninputs);
		if($('.totaltasksbyuser').length>0){
			$('.gantt_cal_light_wide .gantt_cal_ltitle').find('.totaltasksbyuser').html('<span class="nbtasks"></b> <?php echo $langs->trans('Tasks') ?>');
		}else
			$('.gantt_cal_light_wide .gantt_cal_ltitle').append(nbtaskbuyuserinperiod);
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
			console.log('onTaskDrag');
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
	var selectedcolumns = getColumnsToShowResource('<?php echo json_encode($selected_columns); ?>');

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

		var viewbyresource = initialviewmodevariable();

		if(/project/.test(task.id)) {
			classtoreturn += ' ganttproadvancedhide_delete_clone_buttons';
			if(viewbyresource) {
				classtoreturn += ' ganttproadvancedhide_all_buttons';
			}
		}else{
			classtoreturn += ' ganttproadvancedhide_close_buttons';
		}
		
		if(viewbyresource == 2) {
			classtoreturn += ' ganttproadvancedhide_all_buttons';
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
		// // hour
       	// <?php if($ganttproadvanced->showhoursingantt){ ?>

		// {
		// 	name:"hour",
		// 	scale_height: 50,
		// 	min_column_width: 20,
		// 	scales:[
		// 		// {unit: "day", step: 1, format: "%d %M"},
		// 		{unit: 'month', step: 1, format: '%F, %Y'},
		//        	{unit: "day", step: 1, format: "%H", css: function(date) { return "ganttproadvanced_gantt_scale_cell_mediumview_day"; }}
	    //    	 	<?php if($ganttproadvanced->showhoursingantt){ ?>
		//        		,{unit: "hour", step: 1, format: "%H", css: function(date) { return "ganttproadvanced_gantt_scale_cell_mediumview_day"; }}
		//        	<?php }?>	

		// 	]
		// },
       	// <?php }?>
		// days
		{
			name:"day",
			scale_height: 50,
			min_column_width: 20,
			scales:[
				// {unit: "day", step: 1, format: "%d %M"}
				{unit: 'month', step: 1, format: '%F, %Y'},
		       	{unit: "day", step: 1, format: "%d %M", css: function(date) { return "ganttproadvanced_gantt_scale_cell_mediumview_day"; }}
		       	<?php if($ganttproadvanced->showhoursingantt){ ?>
		       		// ,{unit: "hour", step: 1, format: "%H", css: function(date) { return "ganttproadvanced_gantt_scale_cell_mediumview_day"; }}
		       	<?php }?>	

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

	        sclaletoactive = el.value;
	        // if(value == 'hour'){
	        // 	gantt.config.resource_render_config.scale = [
			//         { unit: "day", step: 1, date: "%d %j", min_column_width: 20 },
			//         { unit: "hour", step: 1, date: "%H", min_column_width: 20 }
			//     ];
			//     gantt.render();
        	// 	// gantt.config.resource_render_config = {
			// 	//     scale: {
			// 	//     	unit: "hour", step: 1, date: "%H:%i", min_column_width: 20
			// 	//     },
			// 	//     subscale: {
			// 	//     	unit: "day", step: 1, date: "%d", min_column_width: 20
			// 	//     }
			// 	// };
			// 	// gantt.getDatastore(gantt.config.resource_store).refresh();
				
	        // }else{
	        // 	gantt.config.resource_render_config.scale = [
			//         { unit: "day", step: 1, date: "%d %M", min_column_width: 40 }
			//     ];
			//     gantt.render();
	        // 	// gantt.config.resource_render_config = {
			// 	//     scale: {
			// 	//     	unit: "day", step: 1, date: "%d", min_column_width: 20
			// 	//     },
			// 	// };
			// 	// gantt.getDatastore(gantt.config.resource_store).refresh();
				
	        // }
	        
			// gantt.eachTask(function(task){
	        // 	// gantt.adjustTaskHeightForBaselines(task);
			// });

			// gantt.config.baselines.render_mode = "separateRow";
		
			gantt.render();

	        // refreshBaselinesAfterScaleChange();
	        // setTimeout(function () {

			// 	console.log('Update worktime in baselines !!');
			// 	gantt.eachTask(function(task) {
			// 		base_lines = data.baselines.filter(b => b.task_id === 'task27');

			//         if(base_lines) {
			//             base_lines.forEach(function(baseline) {
			//             	console.log(baseline);
			//                 baseline.original_start = baseline.start_date;
			//                 baseline.original_end = baseline.end_date;
			//                 baseline.start_date = adjustToWorkHours(baseline.start_date);
			//                 baseline.end_date = adjustToWorkHours(baseline.end_date);
			//             	console.log(baseline);
			//             });
			//         }
			//     });


		    //     if (gantt._init_baselines) gantt._init_baselines();
		    // }, 50);

	    };
	}
	setScaleConfig("<?php echo $search_scale; ?>");
// ------------------------------------------------------------------------------------------- END SCALE GANTT */

	// console.log('Data 7:');
	// console.log(<?php echo $_data; ?>);

// ------------------------------------------------------------------------------------------- Data For GANTT 
	var data = {
		data: [ <?php echo $_data; ?> ],
		links: [ <?php echo $_links; ?> ],
		baselines: [ <?php echo $_baselines; ?> ]
	};




// ------------------------------------------------------------------------------------------- Grid Resource

var resourceMode = "hours";
<?php
$taskworkingtime = $ganttproadvanced->taskworkingtime;
$worktime = explode('|', $taskworkingtime);

if(!empty($worktime[0])){
	$hourstart = explode('-', $worktime[0]);
	if(!empty($hourstart)){
		$hstart = (int)explode(':', $hourstart[0])[0];
		$hend = (int)explode(':', $hourstart[1])[0];
	}
}
?>

var hstart = '<?php echo $hstart; ?>';
var hend = '<?php echo $hend; ?>';

gantt.templates.resource_cell_class = function(start_date, end_date, resource, tasks){
	var css = [];
	css.push("resource_marker");

	// if (tasks.length <= 1) {
	// 	css.push("workday_ok");
	// } else {
	// 	// css.push("workday_over");
	// 	css.push("workday_ok");
	// }

	// tasks.forEach(function(task){
	// 	var days = task.daysconsombyuser;
	// 	if(days.length>0){
	// 		days.forEach(function(day){
	// 			if(day){
	// 				var dateToCheck = new Date(day.date);
	// 				if (dateToCheck >= start_date && dateToCheck <= end_date) {
	// 					css.push("workday_ok");
	// 				    // console.log("La date est dans l'intervalle.");
	// 				} else {
	// 					css.push("workday_ok");
	// 				    // console.log("La date est hors de l'intervalle.");
	// 				}
	// 			}
	// 		})
	// 	}
	// })

	var time = 0;

	hourstart = start_date.getHours();
	hourend = end_date.getHours();

	var nbdayinalltask=0;

	tasks.forEach(function(task){
		var days = task.daysconsombyuser;
		var nbtask = 0;
		var nbhourtask = 0;
		if(days && days.length>0){

			days.forEach(function(day){
				if(day){
					var dateToCheck = new Date(day.date);
					date = dateToCheck.getFullYear()+'-'+(dateToCheck.getMonth()+1)+'-'+dateToCheck.getDate();
					datest = start_date.getFullYear()+'-'+(start_date.getMonth()+1)+'-'+start_date.getDate();
					if($('input[name="scale"]:checked').val() == 'day'){
						if (dateToCheck >= start_date && dateToCheck <= end_date) {
							var nbhours = parseInt(day.time)/3600;
							var hour = dateToCheck.getHours();
							if(parseInt(hour) == 0 || hour == '') hour = hstart;
							for (var i = 0; i < nbhours; i++) {
								if((hour+i) >= hstart && (hour+i) < hend){
									time += 3600;
									if(nbhourtask == 0){
										nbhourtask += 1;
										nbdayinalltask += 1;
									}
								}
							}

							// time += parseInt(day.time);
						}
					}else if($('input[name="scale"]:checked').val() == 'hour'){
						var nbhours = parseInt(day.time)/3600;
						hour = dateToCheck.getHours();
						if(parseInt(hour) == 0 || hour == '') hour = hstart;
						if(date == datest){
							for (var i = 0; i < nbhours; i++) {
								if((hour+i) == hourstart && (hour+i) >= hstart && (hour+i) < hend){
									time += 1;
								}
							}
						}
					}
				}
			})
		}
	})

	if($('input[name="scale"]:checked').val() == 'hour'){
		time = parseInt(time);
		if(parseFloat(time) >0){
			if (time <= 1) {
				css.push("gantt_resource_marker_ok");
			} else {
				css.push("gantt_resource_marker_overtime");
			}
		}
	}else{
		time = parseInt((time || 0)/3600);
		if(time != 0){
			if (nbdayinalltask <= 1) {
				css.push("gantt_resource_marker_ok");
			} else {
				css.push("gantt_resource_marker_overtime");
			}
		}
	}


	return css.join(" ");
};




// var renderResourceLine = function (resource, timeline) {
// 	var tasks = gantt.getTaskBy("user", resource.id);
// 	var timetable = calculateResourceLoad(tasks, timeline.getScale());

// 	var row = document.createElement("div");

// 	for (var i = 0; i < timetable.length; i++) {

// 		var day = timetable[i];

// 		var css = "";
// 		if (day.value <= 8) {
// 			css = "gantt_resource_marker gantt_resource_marker_ok";
// 		} else {
// 			css = "gantt_resource_marker gantt_resource_marker_overtime";
// 		}

// 		var sizes = timeline.getItemPosition(resource, day.start_date, day.end_date);
// 		var el = document.createElement('div');
// 		el.className = css;

// 		el.style.cssText = [
// 			'left:' + sizes.left + 'px',
// 			'width:' + sizes.width + 'px',
// 			'position:absolute',
// 			'height:' + (gantt.config.row_height - 1) + 'px',
// 			'line-height:' + sizes.height + 'px',
// 			'top:' + sizes.top + 'px'
// 		].join(";");

// 		el.innerHTML = day.value;
// 		row.appendChild(el);
// 	}
// 	return row;
// };



gantt.templates.resource_cell_value = function(start_date, end_date, resource, tasks){
	var html = "<div>"
	var time = 0;

	hourstart = start_date.getHours();
	hourend = end_date.getHours();


	tasks.forEach(function(task){
		var days = task.daysconsombyuser;
		if(days && days.length>0){
			days.forEach(function(day){
				if(day){
					var dateToCheck = new Date(day.date);
					date = dateToCheck.getFullYear()+'-'+(dateToCheck.getMonth()+1)+'-'+dateToCheck.getDate();
					datest = start_date.getFullYear()+'-'+(start_date.getMonth()+1)+'-'+start_date.getDate();
					if($('input[name="scale"]:checked').val() == 'day'){
						if (dateToCheck >= start_date && dateToCheck <= end_date) {
							var nbhours = parseFloat(day.time)/3600;
							var hour = dateToCheck.getHours();
							if(parseInt(hour) == 0 || hour == '') hour = hstart;

							var nbh = nbhours;
							if(parseFloat(nbhours)>parseInt(nbhours)){
								nbh = parseInt(nbhours);
							}

							for (var i = 0; i < nbh; i++) {
								if((hour+i) >= hstart && (hour+i) < hend){
									time += 3600;
								}
							}

							if(parseFloat(nbhours)>parseInt(nbhours)){
								time += (parseFloat(nbhours)-parseInt(nbhours))*3600;
							}
							// time += parseInt(day.time);
						}
					}else if($('input[name="scale"]:checked').val() == 'hour'){
						var nbhours = parseFloat(day.time)/3600;
						hour = dateToCheck.getHours();
						if(parseInt(hour) == 0 || hour == '') hour = hstart;

						if(date == datest){
							var nbh = nbhours;
							if(parseFloat(nbhours)>parseInt(nbhours)){
								nbh = parseInt(nbhours);
							}

							// if(parseInt(hourstart) == parseInt(hour) && parseFloat(nbhours)>parseInt(nbhours)){
							// 	time += (parseFloat(nbhours)-parseInt(nbhours));
							// }

							for (var i = 0; i < nbh; i++) {
								if((hour+i) == hourstart && (hour+i) >= hstart && (hour+i) < hend){
									time += 1;
								}
							}
							if(parseInt(hourstart) == parseInt(hour+parseInt(nbhours)) && parseFloat(nbhours)>parseInt(nbhours)){
								time += (parseFloat(nbhours)-parseInt(nbhours));
							}
						}
					}
				}
			})
		}
	})
	if(time>0){
		if($('input[name="scale"]:checked').val() == 'hour'){
			htmltime = time;
		}else{
			htmltime = ((time || 0)/3600);
		}

		if(parseFloat(time)>parseInt(time)){
			html += parseFloat(htmltime).toFixed(1);
		}else{
			html += parseFloat(htmltime);
		}	
	}

	html += "</div>";
	return html;
};

gantt.attachEvent("onGanttReady", function(){
	var radios = [].slice.call(gantt.$container.querySelectorAll("input[type=\'radio\']"));
	radios.forEach(function(r){
		gantt.event(r, "change", function(e){
			var radios = [].slice.call(gantt.$container.querySelectorAll("input[type=\'radio\']"));
			radios.forEach(function(r){
				r.parentNode.className = r.parentNode.className.replace("active", "");
			});

			if(this.checked){
				resourceMode = this.value;
				this.parentNode.className += " active";
				gantt.getDatastore(gantt.config.resource_store).refresh();
			}

		});
	});



	// console.log('Update worktime in baselines !!');
	// setTimeout( function() { 
	// 	gantt.eachTask(function(task) {
	// 		base_lines = data.baselines.filter(b => b.task_id === 'task27');

	//         if(base_lines) {
	//             base_lines.forEach(function(baseline) {
	//             	console.log(baseline);
	//                 baseline.original_start = baseline.start_date;
	//                 baseline.original_end = baseline.end_date;
	//                 baseline.start_date = adjustToWorkHours(baseline.start_date);
	//                 baseline.end_date = adjustToWorkHours(baseline.end_date);
	//             	console.log(baseline);
	//             });
	//         }
	//     });
	// }, 500);


});

gantt.$resourcesStore = gantt.createDatastore({
	name: gantt.config.resource_store,
	type: "treeDatastore",
	initItem: function (item) {
		item.parent = item.parent || gantt.config.root_id;
		item[gantt.config.resource_property] = item.parent;
		item.open = true;
		return item;
	}
});

function shouldHighlightResource(resource){
	var selectedTaskId = gantt.getState().selected_task;
	if(gantt.isTaskExists(selectedTaskId)){
		var selectedTask = gantt.getTask(selectedTaskId),
			selectedResource = selectedTask[gantt.config.resource_property];

		if(resource.id == selectedResource){
			return true;
		}else if(gantt.$resourcesStore.isChildOf(selectedResource, resource.id)){
			return true;
		}
	}
	return false;
}

var resourceTemplates = {
	grid_row_class: function(start, end, resource){
		var css = [];
		if(gantt.$resourcesStore.hasChild(resource.id)){
			css.push("folder_row");
			css.push("group_row");
		}
		if(shouldHighlightResource(resource)){
			css.push("highlighted_resource");
		}
		return css.join(" ");
	},
	task_row_class: function(start, end, resource){
		var css = [];
		if(shouldHighlightResource(resource)){
			css.push("highlighted_resource");
		}
		if(gantt.$resourcesStore.hasChild(resource.id)){
			css.push("group_row");
		}

		return css.join(" ");
	}
};

function getResourceTasks(resourceId){
	var store = gantt.getDatastore(gantt.config.resource_store),
		field = gantt.config.resource_property,
		tasks;

	if(store.hasChild(resourceId)){
		tasks = gantt.getTaskBy(field, store.getChildren(resourceId));
	}else{
		tasks = gantt.getTaskBy(field, resourceId);
	}
	return tasks;
}

var scales = [
	{unit: "day", step: 1, date: "%d %j", min_column_width: 20},
];

if('<?php echo $search_scale; ?>' == 'hour'){
	scales = [
		{unit: "day", step: 1, date: "%d %j", min_column_width: 20},
		{unit: "hour", step: 1, date: "%H", min_column_width: 20}
	];
}

var resourceConfig = {
	columns: [
		{
			name: "name", label: "Name", tree:true, width:200, template: function (resource) {
				return resource.text;
			}, resize: true
		},
		{
			name: "progress", label: "Complete", align:"center",template: function (resource) {
				var tasks = getResourceTasks(resource.id);

				var totalToDo = 0,
					totalDone = 0;
				tasks.forEach(function(task){
					totalToDo += task.duration_day;
					totalDone += task.duration_day * (task.progress || 0);
				});

				var completion = 0;
				if(totalToDo){
					completion = Math.floor((totalDone / totalToDo)*100);
				}

				var nbhour = 0;
				var totalDuration = 0;

				tasks.forEach(function(task){
					totalDuration += parseInt(task.duration_effective_time);
					nbhour += parseFloat(task.duration_hour || 0);
				});


				var hourworked = (totalDuration/3600 || 0);

				completion = Math.floor((hourworked / nbhour)*100);

				return Math.floor(completion || 0) + "%";
			}, resize: true
		},
		{
			name: "workload", label: "Workload", align:"center", template: function (resource) {
				var tasks = getResourceTasks(resource.id);
				var totalDuration = 0;
				tasks.forEach(function(task){
					totalDuration += parseInt(task.duration_effective_time);
				});

				return (totalDuration/3600 || 0) + "h";
				// return (totalDuration || 0) * 8 + "h";
			}, resize: true
		},

		{
			name: "capacity", label: "Capacity", align:"center",template: function (resource) {
				var store = gantt.getDatastore(gantt.config.resource_store);
				var n = store.hasChild(resource.id) ? store.getChildren(resource.id).length : 1

				var state = gantt.getState();
				var nbhour = 0;
				// nbhour = gantt.calculateDuration(state.min_date, state.max_date) * n * 8 + "h";
				var tasks = getResourceTasks(resource.id);
				tasks.forEach(function(task){
					nbhour += parseFloat(task.duration_hour);
				});

				return  nbhour+"h";
			}
		}

	]
};

gantt.config.resource_store = "resource";
gantt.config.resource_property = "owner_id";
gantt.config.order_branch = true;
gantt.config.open_tree_initially = true;
gantt.config.scale_height = 50;
gantt.config.layout = {
	css: "gantt_container",
	rows: [
		{
			gravity: 2,
			cols: [
				{view: "grid", group:"grids", scrollY: "scrollVer"},
				{resizer: true, width: 1},
				{view: "timeline", scrollX: "scrollHor", scrollY: "scrollVer"},
				{view: "scrollbar", id: "scrollVer", group:"vertical"}
			]
		},
		{ resizer: true, width: 1, next: "resources"},
		{
			gravity:1,
			id: "resources",
			config: resourceConfig,
			templates: resourceTemplates,
			cols: [
				{ view: "resourceGrid", group:"grids", scrollY: "resourceVScroll" },
				{ resizer: true, width: 1},
				{ view: "resourceTimeline", scrollX: "scrollHor", scrollY: "resourceVScroll"},
				{ view: "scrollbar", id: "resourceVScroll", group:"vertical"}
			]
		},
		{view: "scrollbar", id: "scrollHor"}
	]
};

var resourceMode = "hours";
gantt.attachEvent("onGanttReady", function(){
	var radios = [].slice.call(gantt.$container.querySelectorAll("input[type=\'radio\']"));
	radios.forEach(function(r){
		gantt.event(r, "change", function(e){
			var radios = [].slice.call(gantt.$container.querySelectorAll("input[type=\'radio\']"));
			radios.forEach(function(r){
				r.parentNode.className = r.parentNode.className.replace("active", "");
			});

			if(this.checked){
				resourceMode = this.value;
				this.parentNode.className += " active";
				gantt.getDatastore(gantt.config.resource_store).refresh();
			}
		});
	});
});

gantt.$resourcesStore = gantt.createDatastore({
	name: gantt.config.resource_store,
	type: "treeDatastore",
	initItem: function (item) {
		item.parent = item.parent || gantt.config.root_id;
		item[gantt.config.resource_property] = item.parent;
		item.open = true;
		return item;
	}
});

console.log();




// ------------------------------------------------------------------------------------------- show baslines



// gantt.config.lightbox.milestone_sections = [
// 	{ name: "description", height: 38, map_to: "text", type: "textarea", focus: true },
// 	{ name: "time", type: "duration", map_to: "auto" },
// 	{ name: "baselines", height: 100, type: "baselines", map_to: "baselines" },
// ];

// gantt.config.resize_rows = true;
// gantt.config.row_height = 60;
// gantt.config.min_task_grid_row_height = 5;
// gantt.config.open_split_tasks = true;
gantt.config.baselines = true;
// gantt.config.baselines.render = false;
gantt.config.baselines = {
	// render: "bar",
	render: false,
	render_mode: "separateRow",
	row_height: 9,
	bar_height: 7,
	calendar: "custom",
	// // Optionnel: fonction pour ajuster les dates des baselines
    // date: function(task, baseline) {
    // 	console.log('startDate: '+baseline.start_date);
    //     // Ajuster les heures pour qu'elles soient dans la plage 9h-17h
    //     const adjustTime = (date) => {
    //         const d = new Date(date);
    //         const hours = d.getHours();
            
    //         if (hours < 9) {
    //             d.setHours(9, 0, 0, 0);
    //         } else if (hours >= 17) {
    //             d.setHours(16, 59, 59, 999); // Fin de journée de travail
    //         }
            
    //         // Gestion des week-ends
    //         const day = d.getDay();
    //         if (day === 0 || day === 6) { // Dimanche (0) ou Samedi (6)
    //             const daysToAdd = day === 6 ? 2 : 1;
    //             d.setDate(d.getDate() + daysToAdd);
    //             d.setHours(9, 0, 0, 0); // Début de journée suivante
    //         }
            
    //         return d;
    //     };
        
    //     return {
    //         start_date: adjustTime(baseline.start_date),
    //         end_date: adjustTime(baseline.end_date)
    //     };
    // }
};

gantt.addTaskLayer(function(task) {
    if (!task.baselines || !task.baselines.length) return;

    const container = document.createElement("div");
    container.className = "baseline-layer";

    task.baselines.forEach(function(baseline) {
        const start = new Date(baseline.start_date);
        const end = new Date(baseline.end_date);

        // Only apply if same day and inside working hours
        const sameDay = start.toDateString() === end.toDateString();
        // const inWorkHours = start.getHours() >= 9 && end.getHours() <= 17;
        const isFullWorkDay = start.getHours() <= 9 && end.getHours() >= 17;

        // if (sameDay && isFullWorkDay) {
            const fullWorkStart = new Date(start);
            fullWorkStart.setHours(9, 0, 0, 0);

            const fullWorkEnd = new Date(start);
            fullWorkEnd.setHours(17, 0, 0, 0);

            const fullDayStart = new Date(start);
            fullDayStart.setHours(0, 0, 0, 0);

            const fullDayEnd = new Date(start);
            fullDayEnd.setHours(23, 59, 59, 999);

            const fullWorkDuration = fullWorkEnd - fullWorkStart;
            // const actualSpan = end - start;
            // const widthPercent = actualSpan / fullWorkDuration;

            // const fullDayWidth = gantt.posFromDate(fullDayEnd) - gantt.posFromDate(fullDayStart);
            // const baselineWidth = fullDayWidth * widthPercent;
            // // const baselineLeft = gantt.posFromDate(fullDayStart);

            // const adjustedStart = (start.getHours() <= 9) ? fullDayStart : start;
			// const baselineLeft = gantt.posFromDate(adjustedStart);


			// const startOffsetPercent = (start.getHours() <= 9)
			//     ? 0
			//     : (start - fullDayStart) / (fullDayEnd - fullDayStart);

			// const adjustedEnd = (end.getHours() >= 17) ? fullDayEnd : end;
			// const endOffsetPercent = (adjustedEnd - fullDayStart) / (fullDayEnd - fullDayStart);

			// const baselineLeft = gantt.posFromDate(fullDayStart) + fullDayWidth * startOffsetPercent;
			// const baselineWidth = fullDayWidth * (endOffsetPercent - startOffsetPercent);



			// const fullDayStart = new Date(start);
			// fullDayStart.setHours(0, 0, 0, 0);

			// const fullDayEnd = new Date(start);
			// fullDayEnd.setHours(23, 59, 59, 999);
			const fullDayWidth = gantt.posFromDate(fullDayEnd) - gantt.posFromDate(fullDayStart);

			// Adjusted start (for position)
			const offsetStart = (start.getHours() <= 9)
			  ? 0
			  : (start - fullDayStart) / (fullDayEnd - fullDayStart);

			const baselineLeft = gantt.posFromDate(fullDayStart) + (fullDayWidth * offsetStart);

			// Adjusted width (for working time only)
			const WORK_START = 9;
			const WORK_END = 17;
			const WORK_DURATION_MS = (WORK_END - WORK_START) * 60 * 60 * 1000;

			const actualSpan = end - start;
			const widthPercent = actualSpan / WORK_DURATION_MS;
			const baselineWidth = fullDayWidth * widthPercent;









			

            const el = document.createElement("div");
            el.className = "custom-baseline";
            el.style.position = "absolute";
            el.style.left = baselineLeft + "px";
            el.style.width = baselineWidth + "px";
            el.style.top = (gantt.getTaskTop(task.id) + gantt.config.row_height - 6) + "px";
            el.style.height = "5px";
            // el.style.background = "#00aaff";
            el.style.background = "#bc35d5";
            el.style.opacity = 0.7;

            container.appendChild(el);
        // } else {
        //     // fallback: use actual start/end
        //     const left = gantt.posFromDate(start);
        //     const right = gantt.posFromDate(end);
        //     const width = right - left;

        //     const el = document.createElement("div");
        //     el.className = "custom-baseline";
        //     el.style.position = "absolute";
        //     el.style.left = left + "px";
        //     el.style.width = width + "px";
        //     el.style.top = (gantt.getTaskTop(task.id) + gantt.config.row_height - 6) + "px";
        //     el.style.height = "5px";
        //     // el.style.background = "#ff8800";
        //     el.style.background = "#bc35d5";
        //     el.style.opacity = 0.7;

        //     container.appendChild(el);
        // }
    });

    return container;
});


// console.log(gantt.config.baselines);

// gantt.config.baselines.render_mode = "separateRow";

gantt.templates.baseline_text = function(task, baseline, index) {
// 	console.log('aaaaaaaaaaaaaaaa');
  // return "Plan "+task.ref+" " + (index + 1);
	return '';
};

// setTimeout(function(){

// 	base_lines = data.baselines.filter(b => b.task_id === 'task27');
// 	console.log(base_lines);
// }, 500)

gantt.templates.baseline_class = function(start, end, task, baseline) {
    const workStart = new Date(start);
    const workEnd = new Date(end);
    
    console.log('dddddd');
    console.log('workStart! '+workStart);

    // Ajuster les heures de début
    if (workStart.getHours() < 9) {
        workStart.setHours(9, 0, 0, 0);
    } else if (workStart.getHours() >= 17) {
        workStart.setDate(workStart.getDate() + 1);
        workStart.setHours(9, 0, 0, 0);
    }
    
    // Ajuster les heures de fin
    if (workEnd.getHours() < 9) {
        workEnd.setHours(9, 0, 0, 0);
    } else if (workEnd.getHours() >= 17) {
        workEnd.setHours(17, 0, 0, 0);
    }
    
    // Vérifier si c'est un week-end
    const day = workStart.getDay();
    if (day === 0 || day === 6) {
        return "baseline-outside-worktime";
    }
    
    return "";
};



// gantt.config.baselines.render_mode = "separateRow";

// gantt.config.baselines.render_mode = "taskRow";

// ------------------------------------------------------------------------------------------- END Data For GANTT 



function ajusterBaselinesPourWorkTime(tasks) {
  const workDayHours = 9;

  return tasks.map(task => {
    if (task.baseline_start && task.baseline_end) {
      const start = new Date(task.baseline_start);
      const end = new Date(task.baseline_end);

      // Durée totale en millisecondes
      const dureeMs = end - start;

      // Rapport entre durée réelle (travail) et journée complète (24h)
      const rapport = workDayHours / 24;

      // Nouvelle durée ajustée
      const nouvelleDureeMs = dureeMs * rapport;

      const nouvelleFin = new Date(start.getTime() + nouvelleDureeMs);

      task.baseline_end = nouvelleFin;
    }
    return task;
  });
}




gantt.init("ganttproadvanced");

// setTimeout( function() { 

// 	gantt.eachTask(function(task) {
// 		base_lines = data.baselines.filter(b => b.task_id === task.id);
//         if(base_lines) {
//             base_lines.forEach(function(baseline) {
//             	newstart_date = new Date(baseline.start_date.getFullYear(),baseline.start_date.getMonth(),baseline.start_date.getDate());
//             	newduration = baseline.duration*24/8;
//             	newduration = 23;
//             	// if(newduration == 24) newduration = 23;
//             	// baseline.start_date = new Date(baseline.start_date.getFullYear(),baseline.start_date.getMonth(),baseline.start_date.getDay());

//             	newend_date = new Date(baseline.start_date.getFullYear(),baseline.start_date.getMonth(),baseline.start_date.getDate());;
// 	  			newend_date.setHours(23);

//                 baseline.original_start = baseline.start_date;
//                 baseline.original_end = baseline.end_date;
//                 baseline.start_date = newstart_date;
//                 // baseline.end_date = new Date(newstart_date).addHours(newduration);
//                 baseline.end_date = newend_date;
//                 baseline.duration = newduration;
//                 data.baselines[baseline.id] = baseline;
//             });

//         }
//     });

// 	gantt.render();

// }, 300);

gantt.$resourcesStore.attachEvent("onParse", function(){
	var people = [];
	gantt.$resourcesStore.eachItem(function(res){
		if(!gantt.$resourcesStore.hasChild(res.id)){
			var copy = gantt.copy(res);
			copy.key = res.id;
			copy.label = res.text;
			people.push(copy);
		}
	});
	gantt.updateCollection("people", people);
});



// gantt.$resourcesStore.parse([
// 	{id: 1, text: "QA", parent:null},
// 	{id: 2, text: "Development", parent:null},
// 	{id: 3, text: "Sales", parent:null},
// 	{id: 4, text: "Other", parent:null},
// 	{id: 5, text: "Unassigned", parent:4},
// 	{id: 6, text: "John", parent:1},
// 	{id: 7, text: "Mike", parent:2},
// 	{id: 8, text: "Anna", parent:2},
// 	{id: 9, text: "Bill", parent:3},
// 	{id: 10, text: "Floe", parent:3}
// ]);


setTimeout( function() { 

	// gantt.eachTask(function (task) {
	// 	// // function to recalculate row height
		// gantt.adjustTaskHeightForBaselines(task);
	// });
	const originalRender = gantt.render;
	gantt.render = function() {
	  gantt.eachTask(function(task){
	    // gantt.adjustTaskHeightForBaselines(task);
	  });
	  originalRender.call(gantt);
	};

}, 500);


$(window).on('load', function() {
	gantt.parse(data);
	var datareources = [<?php echo $_data_resource; ?>];
	gantt.$resourcesStore.parse(datareources);
});




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

<?php if($viewmode == 'byresource' && $ganttproadvanced->byressource_type_contacts == 'per_principal_contributeur') { ?>
	// gantt.attachEvent("onTaskClick", function (left, top) {
	//     setTimeout(viewmodebyresource_highlightFromSelected, 300);
	// });
	gantt.attachEvent("onGanttScroll", function (left, top) {
	    setTimeout(viewmodebyresource_highlightFromSelected, 300);
	});
<?php } ?>

<?php if($ganttproadvanced->scroll_currentday) { ?>
	setTimeout( function() { 
		gantt.showDate(new Date());
	}, 500);
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

function getColumnsToShowResource(columns) {
	
	var thtask = '';
	var formatdate = 'dd/mm/y';


	<?php if($ganttproadvanced->showhoursingantt) {?>
		formatdate = 'dd/mm/y h:i';
	<?php } ?>


	thtask += '<span class="gantt_tree_icon gantt_close" onclick="ganttproadvancedCloseAllTasks()"></span>';
	thtask += '<span class="gantt_tree_icon gantt_open" onclick="ganttproadvancedOpenAllTasks()"></span>';
	thtask += '<?php echo $langs->trans('Tasks'); ?>';
	thtask += '<input id="ganttproadvancedsearch" oninput="ganttproadvancedRefreshData();" type="text" placeholder="<?php echo $langs->trans('Search'); ?> A, B, C">';

	var values = [{name:"text", label:thtask,  width:"*", tree:true , 
					template:function(obj){ 
						txt = '';
						if((/project/.test(obj.id))) {
							var viewbyresource = initialviewmodevariable();
							txt += obj.ref;
							if(!viewbyresource) {
								txt += ' - ';
							}
						}
						txt += obj.text;
						return txt 
					}
				}];


    // ---------------------------------------------------------------------------------------------------------------------- 
	if(columns.indexOf("departement") > -1) {
		toconcat = [{name:"departement", label:"<?php echo $langs->trans('departement')?>", align:"center", template:function(obj){ return obj.departement }, width:54 }];
		values = values.concat(toconcat);
	}
	if(columns.indexOf("projet") > -1) {
		toconcat = [{name:"projet", label:"<?php echo $langs->trans('Project')?>", align:"center", template:function(obj){ return obj.ref_projet }, width:60 }];
		values = values.concat(toconcat);
	}

	if(columns.indexOf("duration") > -1) {
		// toconcat = [{name:"duration", label:"<?php echo strtolower(substr($langs->trans("Day"),0,1)); ?>", align:"center", width:45 }];
		toconcat = [{name:"duration", label:"<?php echo $langs->trans("Days"); ?>", align:"center", width:45
					, template:function(obj){ 
						txt = '-';
						if(obj.start_date && obj.start_date.getFullYear() != 1970 && obj.end_date && obj.end_date.getFullYear() != 1970) {
							// txt = obj.duration;
							txt = obj.duration_day;
						}

						// if(obj && obj.id && Math.floor(obj.id) == obj.id) {
						// }

						return txt 
					} 
				}];
		values = values.concat(toconcat);
	}

	<?php if(!empty($ganttproadvanced->showhoursingantt)){ ?>
		if(columns.indexOf("durationhour") > -1) {
			// toconcat = [{name:"duration", label:"<?php echo strtolower(substr($langs->trans("Day"),0,1)); ?>", align:"center", width:45 }];
			toconcat = [{name:"durationhour", label:"<?php echo $langs->trans("Hours"); ?>", align:"center", width:45
						, template:function(obj){ 
							txt = '-';
							if(obj.start_date && obj.start_date.getFullYear() != 1970 && obj.end_date && obj.end_date.getFullYear() != 1970) {
								// txt = obj.duration;
								txt = obj.duration_hour;
							}

							// if(obj && obj.id && Math.floor(obj.id) == obj.id) {
							// }

							return txt 
						} 
					}];
			values = values.concat(toconcat);
		}
	<?php } ?>

	var widthdate = 60;
	<?php if($ganttproadvanced->showhoursingantt){ ?>
		widthdate = 80;
	<?php } ?>


	if(columns.indexOf("start_date") > -1){
		toconcat = [{name:"start_date", label:"<?php echo $langs->trans('DateStart'); ?>", align:"center", width:widthdate ,
						template:function(obj){ 
							txt = '';
							if(obj.start_date && obj.start_date.getFullYear() != 1970){
								var d = new Date(obj.start_date);
								txt = $.datepicker.formatDate('dd/mm/y', d);
								<?php if($ganttproadvanced->showhoursingantt){ ?>
									// Format pour la date seule
									// Format heure
									var hours = d.getHours().toString().padStart(2, '0');
									var minutes = d.getMinutes().toString().padStart(2, '0');
									txt += ' '+hours+':'+minutes;
								<?php }?>
							}

							return txt 
						}
		}];
		values = values.concat(toconcat);
	}

	// ---------------------------------------------------------------------------------------------------------------------- 
	if(columns.indexOf("end_date") > -1){
		toconcat = [{name:"end_date", label:"<?php echo $langs->trans('DateEnd'); ?>", align:"center", width:widthdate ,
						template:function(obj){ 
							// txt = $.datepicker.formatDate('dd/mm/y', new Date(obj.end_date));
							txt = '';
							if(obj.end_date && obj.end_date.getFullYear() != 1970) {
								var d = new Date(obj.end_date);
								txt = $.datepicker.formatDate('dd/mm/y', d);
								// Format pour la date seule
								<?php if($ganttproadvanced->showhoursingantt){ ?>
									// Format heure
									var hours = d.getHours().toString().padStart(2, '0');
									var minutes = d.getMinutes().toString().padStart(2, '0');
									txt += ' '+hours+':'+minutes;
								<?php }?>
								// txt = $.datepicker.formatDate('dd/mm/y', new Date(obj.end_date - 1));
							}
							return txt 
						}
		}];
		values = values.concat(toconcat);
	}

	// ---------------------------------------------------------------------------------------------------------------------- Progress
	if(columns.indexOf("progress") > -1) {
		toconcat = [{name:"progress", label:"<?php echo $langs->trans('%'); ?>", align:"center", template:function(obj){ return (obj.progress*100).toFixed(0)+'<span class=progresspercent>%</span> </span>' }, width:32 }];
		values = values.concat(toconcat);
	}

	// ---------------------------------------------------------------------------------------------------------------------- 
	if(columns.indexOf("dureeffectiv") > -1) {
		toconcat = [{name:"dureeffectiv", label:"H:m", align:"center", template:function(obj){ return obj.duration_effective }, width:48 }];
		values = values.concat(toconcat);
	}



	// ---------------------------------------------------------------------------------------------------------------------- 
	if(columns.indexOf("percentdure") > -1) {
		toconcat = [{name:"percentdure", label:"%<?php echo $langs->trans('Time'); ?>", align:"center", template:function(obj){ return obj.percentdure }, width:48 }];
		values = values.concat(toconcat);
	}

	// ---------------------------------------------------------------------------------------------------------------------- Trellotasksplus Status
	<?php if($trellotasksplus_enabled) { ?>
		if(columns.indexOf("trellotasksplus_status") > -1) {
			toconcat = [{name:"trellotasksplus_status", label:"<?php echo $langs->trans('tagstrellotasksplus'); ?>", width:58, template:function(obj){

					trellotasksplus_status = '';
					if(/task/.test(obj.id)) {
						// console.log(obj.trellotasksplusstatus);
						if(obj.trellotasksplusstatus && obj.trellotasksplusstatus != null) {
							trellotasksplus_status = '<span class="trellotasksplus_statuscolumn">';
							trellotasksplus_status += obj.trellotasksplusstatus;
							trellotasksplus_status += '</span>';
						}
					}
					return trellotasksplus_status;
				}
				
			}];
			values = values.concat(toconcat);
		}
	<?php } ?>

	// ---------------------------------------------------------------------------------------------------------------------- Add
	toconcat = [{name:"add", label:"", width:27 }];
	values = values.concat(toconcat);

	// values = values.concat({name: "owner", align: "center", width: 80, label: "Owner", template: function (task) {
	// 		if(task.type == gantt.config.types.project){
	// 			return "";
	// 		}

	// 		var store = gantt.getDatastore(gantt.config.resource_store);
	// 		var owner = store.getItem(task[gantt.config.resource_property]);
	// 		if (owner) {
	// 			return owner.text;
	// 		} else {
	// 			return "Unassigned";
	// 		}
	// 	}, resize: true}
	// )

	// ---------------------------------------------------------------------------------------------------------------------- Comments
	<?php if(empty($tabviewhour)){ ?>
		toconcat = [{name:"comments", label:"", width:27, template:function(obj){

				comments ='';
				if(/task/.test(obj.id)) {
					// objid = obj.id.replace('task', '');
					objid = obj.task_rowid;
					comments = '<span class="commentscolumn">';
					comments += '<span class="fas fa-comment" title="'+obj.nb_comments+' <?php echo $langs->trans('Comments') ?>" data-id="'+objid+'" onclick="popcomments(this)"></span>';
					comments += '<span class="nb_comments">'+(obj.nb_comments>0 ? (obj.nb_comments > 9 ? '+9' : obj.nb_comments) : '')+'</span>';
					comments += '</span>';
				}
				return comments;
			} 
		}];
		values = values.concat(toconcat);
	<?php } ?>

	return values;
}

// console.log(data.baselines);

firstloadof_ganttproadvanced = false;

if (gantt.$click && gantt.$click.buttons && typeof callback === "function") {
  callback.call(gantt, gantt._quickInfoTask);
} else {
  console.log("Action Quick Info non définie :");
}

</script>