<script>

var hstart = 0;
var hend = 23;
var nhours = 24;

<?php if($ganttproadvanced->showhoursingantt){

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
	hstart = parseInt('<?php echo $hstart; ?>');
	hend = parseInt('<?php echo $hend; ?>');
	if(hend>hstart){
		nhours = hend - hstart;
	}else nhours = 24;
<?php } ?>



function initialviewmodevariable() {
	var viewbyresource = 0;

	<?php if($viewmode == 'byresource') { ?>
		viewbyresource = 1;
		<?php if($ganttproadvanced->byressource_type_contacts == 'per_principal_contributeur') { ?>
			viewbyresource = 2;
		<?php } ?>
	<?php } ?>

	return viewbyresource;
}

// ------------------------------------------------------------------------------------------- AJAX Functions
function ganttproadvancedActionTask(action, task) {

	var search_projects = $('select[name="search_projects[]"]').val();
	var search_affecteduser = $('select[name="search_affecteduser[]"]').val();
	var search_tasktype = $('select#search_tasktype').val();
	var users_tasks = $('input#users_tasks').val();

	var tmptask_id = task.id;

	var projectprogress = 0;

	var viewbyresource = initialviewmodevariable();

	if(action == 'updatetask' && !viewbyresource) {
		var project = gantt.getTask('project'+task.projectid);
		projectprogress = project.progress;
	}

	if(action == 'updateproject' && viewbyresource) {
		return 1;
	}

	// --------------------------------------------------------------------------------------------------------------------------------
	<?php if($ganttproadvanced->use_task_dependencies) { ?>
		if(action == 'createtask' || action == 'updatetask') {
			varsource = task.ganttproadvancedrelatedtask;
			if(gantt.isTaskExists('task'+varsource) && task.ganttproadvancedtyperelation > 0 && varsource > 0 && !window.wealreadyaddlink) {

				typelinkdetail = getTheLinkTypeIdForJs(task.ganttproadvancedtyperelation);

				gantt.addLink({
					id:task.id,
					source:'task'+varsource,
					target:task.id,
					type:typelinkdetail
				});
			}
		}
	<?php } ?>

	// --------------------------------------------------------------------------------------------------------------------------------
	$.ajax({
        data:{
        	'ajxaction': action
        	,'task': task
        	,'search_affecteduser': search_affecteduser
        	,'search_projects': search_projects
        	,'users_tasks': users_tasks
        	,'search_tasktype': search_tasktype
        	,'projectprogress': projectprogress
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/ganttproadvanced/ganttproadvanced_ajax.php',1)); ?>",
        type:'POST',
        dataType:'json',
        success:function(returned){
            if(returned) {

            	if(returned['msg']) {
            		$(".gantt_message_area .gantt-info").remove();
					gantt.message({type:returned['typemsg'], text:returned['msg']}, ); // warning error info
	            	if(returned['typemsg'] == 'error') {
	            		gantt.undo();
	            		return 0;
	            	}
            	}


            	if(!gantt.isTaskExists(tmptask_id)) return 0;

				var task = gantt.getTask(tmptask_id);

            	if(action == 'createtask') {

					if (typeof task === 'undefined') return 0;

					newtaskid = returned['taskid'];
            		gantt.changeTaskId(task.id, newtaskid);

            		// Clone task
            		if(id_task_clone = tmptask_id && typeof id_tast_toclone !== 'undefined' && id_tast_toclone){
						
						task_toclone = gantt.getTask(id_tast_toclone);
						ganttproadvancedCloneTask(task_toclone, task);

					}

					task.duration = returned['duration_day'];
					task.duration_hour = returned['duration_hour'];

					task.type = gantt.config.types.task;
            		task.projectid = returned['projectid'];
            		task.ref = returned['ref_task'];
            		
            		<?php if(!$ganttproadvanced->use_task_dependencies) { ?>
						gantt.addLink({
							id:task.id,
							source:task.parent,
							target:task.id,
							type:"1"
						});
					<?php } ?>

            		// if(task.type === undefined) return 0;

            		gantt.updateTask(task.id);


            		$('.exportbuttons a').removeClass('hidden');

            	}


				if(action == 'createtask' || action == 'updatetask') {
					tmtask_id = task.id;
					var tmptask = gantt.getTask(tmtask_id);
					var fk_user_id = tmptask.affected_user;


					var markerId = $('.colormarker_'+tmtask_id).data('marker-id');

					console.log('updatetask 1: ');
					console.log(task);

					// console.log(tmptask.type);
					if(tmptask !== undefined && tmptask.type != gantt.config.types.project && tmptask.ganttproadvanceddatejalon !== '' && tmptask.ganttproadvanceddatejalon !== '') {

						var content = getContentInfoTask(tmtask_id, true);
						tmpdatejalon = new Date(tmptask.ganttproadvanceddatejalon);

						if(markerId > 0) {
							var marketoedit = gantt.getMarker(markerId);


							// OLD JALON
							var month = tmpdatejalon.getMonth() + 1; //months from 1-12
							var day = tmpdatejalon.getDate();
							var year = tmpdatejalon.getFullYear();

							newjalondate = year + "-" + month + "-" + day;

							// NEW JALON
							var oldjalondate = '';
							if(marketoedit.start_date) {
								var tmpnewdate = marketoedit.start_date;

								month = tmpnewdate.getMonth() + 1; //months from 1-12
								day = tmpnewdate.getDate();
								year = tmpnewdate.getFullYear();

								oldjalondate = year + "-" + month + "-" + day;
							}

							if(oldjalondate != newjalondate) {
								marketoedit.start_date = tmpdatejalon;
							}

							marketoedit.text = content;
							marketoedit.css = "markertask gantt_scale_cell colormarker_user"+fk_user_id+" colormarker_"+tmtask_id;

							gantt.updateMarker(markerId);

							setMarkerHeightAsHeightOfTaskRow(tmtask_id);

						} else {

							var markerId = gantt.addMarker({  
							    start_date: tmpdatejalon,
							    css: "markertask gantt_scale_cell colormarker_user"+fk_user_id+" colormarker_"+tmtask_id+"", 
							    text: content,
							    title: '' 
							});
							gantt.getMarker(markerId); //->{css:"today", text:"Now", id:...}

							// setMarkerHeightAsHeightOfTaskRow(tmtask_id);

						}
						// $('.colormarker_'+tmtask_id).removeClass('colormarker_user0');

					}

					<?php if(empty($ganttproadvanced->marker_bar_color)) { ?>
						if(returned['csstoadd'] !== '') {
							$('.ganttmarkerstyles style').append(returned['csstoadd']);
						}
					<?php } ?>


					task.duration = returned['duration_day'];
					task.duration_day = returned['duration_day'];
					task.duration_hour = returned['duration_hour'];


					// IF PROGRESS UPDATED TO 100% (trellotasksplus)
					if(returned['task_progress'] && returned['task_progress'] != task.progress) {
						// console.log('Progress 1 :'+returned['task_progress']);
						// console.log('Progress 2 :'+task.progress);
						task.progress = returned['task_progress'];
		        		gantt.updateTask(task.id);
					}
            		

				}


				<?php if($ganttproadvanced->use_task_dependencies) { ?>
					if(action == 'updatetask') {
						// console.log(returned['ganttproadvancedjstoapply']);
						if(returned['ganttproadvancedjstoapply'] != 0) {
							// console.log(returned['ganttproadvancedjstoapply']);
	            			eval(returned['ganttproadvancedjstoapply']);
						}
	            		else {
	            			if(!viewbyresource) {
		            			var project = gantt.getTask('project'+task.projectid);
								ganttproadvancedActionTask('updateproject', project);
	            			}
	            		}
					}
				<?php } ?>



            	// if(returned['colortask']) {
            	// 	var currenttask = gantt.getTask(task.id);
            	// 	currenttask.color = returned['colortask'];
            	// 	$('.gantt_task_line[task_id="'+task.id+'"]').css('background-color', returned['colortask']);
            	// }

            	<?php if($ganttproadvanced->coloredbyuser && (($ganttproadvanced->changecolortaskbyadmin && $user->admin) || !$ganttproadvanced->changecolortaskbyadmin)) { ?>
	            	if(action == 'createtask' || action == 'updatetask') {


						var username = '';
						if(returned['affected_nameuser']) {
							username = returned['affected_nameuser'];
						} else {
							if((/task/.test(task.id))) {
								task.color = '<?php echo dol_escape_js($ganttproadvanced->colorgristask); ?>';
								// $('.gantt_task_line[task_id="'+task.id+'"]').css('background-color', '<?php echo dol_escape_js($ganttproadvanced->colorgristask); ?>');
							}
						}


						if((/task/.test(task.id))) {
							task.affected_nameuser = username;

							if(task.trellotasksplusstatus != task.options_trellotasksplus_colomn) {
								task.trellotasksplusstatus = task.options_trellotasksplus_colomn;
							}
							
							gantt.refreshTask(task.id,task);

							if(returned['changed_colors'] !== undefined) {

								var i;
								var changedcolors = returned['changed_colors'];

								$.each(changedcolors, function(key,valueObj){

									var tmtask_id = 'task'+valueObj;

									if(gantt.isTaskExists(tmtask_id)) {

										var tmptask = gantt.getTask(tmtask_id);

										if(task.affected_user == tmptask.affected_user) {
											tmptask.color = task.color;
											gantt.refreshTask(tmtask_id,tmptask);
										}

									}
								});
								
							}
						}

	            	}
            	<?php } ?>

            	if(returned['selectusers'] !== undefined) {
            		$('#ganttproadvanced_users_as_taskcontact').html(returned['selectusers']);
            		$('#ganttproadvanced_users_as_taskcontact select').select2();
            	}

            	if(viewbyresource && $('#ganttproadvancedforcereloadpage').val() > 0) {
            		window.location.href=window.location.href;
            	}
            }
        }
    });
}

function setMarkerHeightAsHeightOfTaskRow(id_task) {
	// console.log('Executed : set_MarkerHeightAsHeightOfTaskRow');

	if((/task/.test(id_task)) && ('.colormarker_'+id_task).length > 0) {
        var markerElement = document.querySelector('.gantt_marker.colormarker_'+id_task);

        if (markerElement) {
            var taskElement = gantt.getTaskRowNode(id_task);
            if (taskElement) {
                var taskHeight = taskElement.clientHeight;
                var taskOffsetTop = taskElement.offsetTop;
                markerElement.style.height = taskHeight + 'px';
                markerElement.style.top = taskOffsetTop + 'px';
                // Additional styles can be set here

			    markerElement.style.position = 'absolute';
            }

            $(markerElement).addClass('showbarmarker');
        }
    }
}

function setMarkerHeightAsHeightOfTaskRowForAllTasks() {
	// console.log('Executed : setMarkerHeightAsHeightOfTaskRowForAllTasks');
	
	<?php if(empty($ganttproadvanced->show_marker_bar_only_at_top)) { ?>
		timeout = 600;
		
		var taskRows = document.querySelectorAll('.gantt_row_task[data-task-id^="task"]');
		taskRows.forEach(function(row) {
			setTimeout( function() { 
		    	var id_task = row.getAttribute('data-task-id');
		        setMarkerHeightAsHeightOfTaskRow(id_task);
			}, timeout);

			timeout += 5;
	    });
	<?php } ?>
}

function getColumnsToShow(columns) {
	
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

	// if(columns.indexOf("owner_id") > -1) {
	// 	toconcat = [{name:"owner_id", label:"Owner", align:"center", template:function(obj){ return obj.owner_id }, width:48 }];
	// 	values = values.concat(toconcat);
	// }


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

function HideShowColumns(that) {
	var columns = $(that).val();

	var values = getColumnsToShow(columns);
	gantt.config.columns = values;
	gantt.render();

	// columns.forEach(function(item) {
	// 	// console.log(item);
	//     // do something with `item`
	// });

	$.ajax({
        data:{
        	'ajxaction': 'hideshowcolumn',
        	'columns': columns
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/ganttproadvanced/ganttproadvanced_ajax.php',1)); ?>",
        type:'POST',
        dataType:'json',
        success:function(returned){
            if(returned) {
            }
        }
    });
}

function ganttproadvancedCloneTask(task, newtask) {

	var tmptask_id = task.id;
	gantt.updateTask(newtask.id);

	$.ajax({
        data:{
        	'ajxaction': 'clonetask'
        	,'task': task
        	,'newtask': newtask
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/ganttproadvanced/ganttproadvanced_ajax.php',1)); ?>",
        type:'POST',
        dataType:'json',
        success:function(returned){
           
        }
    });

}

function changeStartEndDate(taskid) {

	id = 0;
	if(taskid && (/task/.test(taskid))) {
    	id = taskid.replace('task', '');
	}

	var task = gantt.getTask(taskid);

	var relatedtask = parseInt($('#options_ganttproadvancedrelatedtask').val());
	var typerelation = parseInt($('#options_ganttproadvancedtyperelation').val());
	var numberdayslate = parseInt($('#options_ganttproadvancednumberdayslate').val());


	var start_date = $('input[name="start"]').val();
	var end_date = $('input[name="end"]').val();

	hideorShowtyperelation();

	if(relatedtask > 0 && typerelation > 0){

		$.ajax({
	        data:{
	        	'ajxaction': 'changeStartEndDate',
	        	'id': id,
	        	'relatedtask': relatedtask,
	        	'typerelation': typerelation,
	        	'numberdayslate': numberdayslate,
	        	'start_date': start_date,
	        	'end_date': end_date,
	        },
	        url:"<?php echo dol_escape_js(dol_buildpath('/ganttproadvanced/ganttproadvanced_ajax.php',1)); ?>",
	        type:'POST',
	        dataType:'json',
	        success:function(returned){
	        	// if(typeof(returned) != "undefined" && returned['msgerror']) {
	        	// 	console.log(returned['msgerror']);
	        	// 	gantt.message({type:'error', text:returned['msgerror']}, );
	        	// 	$('#options_ganttproadvancedrelatedtask').val(0).trigger('change');
	            // }
	            if(returned){
	            	// console.log(returned);
	            	if(returned['date_start'])
	            		$('input[name="start"]').datepicker("setDate", returned['date_start']);
	            	if(returned['date_end'])
	            		$('input[name="end"]').datepicker("setDate", returned['date_end']);

	            }
	        }
	    });
	}
}

function getOrSetRelatedTasks(taskid, relatedtask, withmouse = false, typelinkdetail = null) {

	currentmodiftask = 0;
	if(taskid && (/task/.test(taskid))) {
    	currentmodiftask = taskid.replace('task', '');
	}
	idrelatedtask = 0;
	if(relatedtask && (/task/.test(relatedtask))) {
    	idrelatedtask = relatedtask.replace('task', '');
	}

	$.ajax({
        data:{
        	'ajxaction': 'getOrSetRelatedTasks',
        	'currentmodiftask': currentmodiftask,
        	'relatedtask': relatedtask,
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/ganttproadvanced/ganttproadvanced_ajax.php',1)); ?>",
        type:'POST',
        dataType:'json',

        success:function(returned){

        	// console.log(returned['html']);

        	if(typeof(returned) != "undefined" && returned['html']) {

        		if(!withmouse) {
        			$('.container_ganttproadvancedrelatedtask').html(returned['html']);
        		}

        		var tobedisabled = returned['tobedisabled'];

				// console.log('currentmodiftask : '+currentmodiftask);
				// console.log('relatedtask : '+relatedtask);
        		// console.log(tobedisabled);

        		if(tobedisabled) {

        			if(!withmouse) {
		        		$.each( tobedisabled, function( i, task_id ) {
		        			// console.log(task_id);
		        			if(task_id > 0) {
		        				$('#options_ganttproadvancedrelatedtask option[value="'+task_id+'"]').attr("disabled", true);
		        			}
						});
        			} else {
        				if(relatedtask in tobedisabled){
						    gantt.undo();
	            			return 0;
						} else {
						    // console.log("now we will add link to gantt tasks");
						    var sourcetask = gantt.getTask(relatedtask);
						    var targettask = gantt.getTask(taskid);

						    targettask.ganttproadvancedrelatedtask = idrelatedtask;

						    targettask.label_relatedtask = sourcetask.ref;
						    if(sourcetask.text) {
						    	targettask.label_relatedtask += ' - '+sourcetask.text;
						    }

						    var relation_id = typelinkdetail['id'];
						    var relation_label = typelinkdetail['label'];

						    targettask.ganttproadvancedtyperelation = relation_id;
						    targettask.label_typerelation = relation_label;

						    gantt.updateTask(taskid);

						    return 1;
						}
        			}
        		}

            }

            if(!withmouse) {
    			$('#options_ganttproadvancedrelatedtask').select2(); // After we finish tests
            	hideorShowtyperelation();
        	}
        }
    });
}

// ------------------------------------------------------------------------------------------- END AJAX Functions 


function getContentInfoTask(id_task, date=false){ 

	if(!gantt.isTaskExists(id_task))
		return;

	var task = gantt.getTask(id_task);
	
	// if((/project/.test(task.id))) {
	// }
	// console.log(task);

	var desc = task.details || task.text;

	var objid = 0;


	// -------------------------------------------------------------------------------------------------- Par Ressource
	var viewbyresource = initialviewmodevariable();
	// -------------------------------------------------------------------------------------------------- 


		
	if(typeof task.id === 'string' && Math.floor(task.id) != task.id) {
		// objid = task.id.replace(/project|task/ig, "");
		objid = task.id.replace('project', '');
		objid = objid.replace('task', '');
	}
	

	var linobj = '<?php echo dol_escape_js(dol_buildpath('/projet/card.php?id=',1)); ?>';
	if(objid && (/task/.test(task.id))) {
		// linobj = '<?php echo dol_escape_js(dol_buildpath('/projet/tasks/contact.php?id=',1)); ?>';
		linobj = '<?php echo dol_escape_js(dol_buildpath('/projet/tasks/task.php?id=',1)); ?>';
	} 
	else if(viewbyresource) {
		linobj = '';
	}

	if(Math.floor(task.task_rowid) > 0) {
		objid = task.task_rowid;
	}

	var reftask = '';
	if(task.ref !== undefined) {
		reftask = task.ref+' ';
	}
	// Ref & Title task
 	html = '<div class="ganttproadvancedpophover">';
	html += '<span class="gTtTitle"><b>';
			html += reftask;
			if(reftask && task.text != '') {
				html += ' - '+task.text;
			}
		html += ' </b>';
		if(!date && linobj) {
			html += '<a class="externopenlink" target="_blank" href="'+linobj+objid+'">'+
			'<span class="fas fa-external-link-alt"> <i class="opacitymedium"><?php echo dol_escape_js($langs->trans("Show")); ?></i></span>'+
			'</a>';
		}
	html += '</span>';
	

	if(task.tiersname && (/project/.test(task.id))) {
		html += '<div class="oddeven gTILine gTId">';
			html += '<span class="gTaskLabel"><?php echo dol_escape_js($langs->trans("Tiers"))?>: </span>';
			html += '<span class="gTaskText"><b>'+task.tiersname+' </b>';
			html += '</span>';
		html += '</div>';
	}

	if(date){
		var datestart = $.datepicker.formatDate('dd/mm/yy', new Date(task.start_date));
		var dateend = $.datepicker.formatDate('dd/mm/yy', new Date(task.end_date));
		html += '<div class="gTILine gTId">'+
		  '<span class="gTaskLabel"><?php echo dol_escape_js($langs->trans("DateStart"))?>: </span>'+
		  '<span class="gTaskText">'+datestart+'</span>'+
		'</div>'+
		'<div class="gTILine gTId">'+
		  '<span class="gTaskLabel"><?php echo dol_escape_js($langs->trans("DateEnd"))?>: </span>'+
		  '<span class="gTaskText">'+dateend+'</span>'+
		'</div>';
	}

	// Projet
	// if (viewbyresource && task.projectlabel && (/task/.test(task.id))) {
	if (task.projectlabel && (/task/.test(task.id))) {

		// var taskparent = gantt.getTask(task.parent);
		// var objid = taskparent.id.replace(/project|task/ig, "");; 
		// var linobj = '<?php echo dol_escape_js(dol_buildpath('/projet/tasks/task.php?id=',1)); ?>';

		html += '<div class="oddeven gTILine gTId">';
			html += '<span class="gTaskLabel"><?php echo dol_escape_js($langs->trans("Project"))?>: </span>';
			html += '<span class="gTaskText"><b>'+task.projectlabel+' </b>';
			// if(!date){
			// 	html += '<a class="externopenlink" target="_blank" href="'+linobj+objid+'">';
			// 		html += '<span class="fas fa-external-link-alt"> <i class="opacitymedium"><?php echo dol_escape_js($langs->trans("Show")); ?></i></span>';
			// 	html += '</a>';
			// }
			html += '</span>';
		html += '</div>';
	}

	// Enfant de la tâche
	if ((/task/.test(task.parent)) && gantt.isTaskExists(task.parent)) {

		var taskparent = gantt.getTask(task.parent);
		var objid = taskparent.id.replace(/project|task/ig, "");; 
		var linobj = '<?php echo dol_escape_js(dol_buildpath('/projet/tasks/task.php?id=',1)); ?>';

		html += '<div class="oddeven gTILine gTId">';
			html += '<span class="gTaskLabel"><?php echo dol_escape_js($langs->trans("ChildOfTask"))?>: </span>';
			html += '<span class="gTaskText"><b>'+taskparent.ref+' - '+taskparent.text+' </b>';
			if(!date){
				html += '<a class="externopenlink" target="_blank" href="'+linobj+objid+'">';
					html += '<span class="fas fa-external-link-alt"> <i class="opacitymedium"><?php echo dol_escape_js($langs->trans("Show")); ?></i></span>';
				html += '</a>';
			}
			html += '</span>';
		html += '</div>';
	}



	// -------------------------------------------------------------------------------------------------- Par Ressource


	if(viewbyresource && (/project/.test(task.id))) {
		html += '</div>';
		return html;
	}

	// -------------------------------------------------------------------------------------------------- 

  	// '<span class="gTaskText">'+task.duration+' <?php echo dol_escape_js(strtolower(substr($langs->trans("Day"),0,1))); ?></span>'+

  	var duration = '-';
  	if(task.start_date && task.start_date.getFullYear() != 1970 && task.end_date && task.end_date.getFullYear() != 1970)
  		duration = (task.duration_day)+ ' <?php echo dol_escape_js($langs->trans("Days")); ?>';
  		// duration = task.duration + ' <?php echo dol_escape_js($langs->trans("Days")); ?>';
	// Durée
	html += '<div class="oddeven gTILine gTId">'+
	  '<span class="gTaskLabel"><?php echo dol_escape_js($langs->trans("Duration"))?>: </span>'+
	  '<span class="gTaskText">'+duration+'</span>'+
	'</div>';

	// --------------------------------------------------------------------------------------------------------------------------------------------------------
	// --------------------------------------------------------------------------------------------------------------------------------------------------------
	// --------------------------------------------------------------------------------------------------------------------------------------------------------
	
	<?php if($ganttproadvanced->use_task_dependencies) { ?>

		if(task.ganttproadvancedrelatedtask > 0) {

			// Liée à la tâche
			html += '<div class="oddeven gTILine gTId use_task_dependencies_popinfo">';
				html += '<span class="gTaskLabel"><?php echo dol_escape_js($langs->trans("RelatedToTask"))?>: </span>';
				html += '<span class="gTaskText">';
					if (task.ganttproadvancedrelatedtask) {
						var objid = task.ganttproadvancedrelatedtask;
						var linobj = '<?php echo dol_escape_js(dol_buildpath('/projet/tasks/task.php?id=',1)); ?>';

						html += '<b>'+task.label_relatedtask+' </b>';
						html += '<a class="externopenlink" target="_blank" href="'+linobj+objid+'">';
							html += '<span class="fas fa-external-link-alt"> <i class="opacitymedium"><?php echo dol_escape_js($langs->trans("Show")); ?></i></span>';
						html += '</a>';
					}
				html += '</span>';
			html += '</div>';

			// --------------------------------------------------------------------------------------------------------------------------------------------------------

			// Type de relation
			if((/task/.test(task.id)) ) {
				html += '<div class="oddeven gTILine gTId use_task_dependencies_popinfo">'+
				  '<span class="gTaskLabel"><?php echo dol_escape_js($langs->trans('TypeRelation'))?>: </span>'+
				  '<span class="gTaskText">'+task.label_typerelation+'</span>'+
				'</div>';
			}

			// --------------------------------------------------------------------------------------------------------------------------------------------------------

			// Jours de retard
			html += '<div class="oddeven gTILine gTId use_task_dependencies_popinfo">';
				html += '<span class="gTaskLabel"><?php echo dol_escape_js($langs->trans("NumberDaysLate"))?>: </span>';
				html += '<span class="gTaskText">';
					if ((/task/.test(task.id)) && gantt.isTaskExists(task.id)) {
						html += '<b>'+task.ganttproadvancednumberdayslate+' <?php echo dol_escape_js($langs->trans("Days"))?></b>';
					} else {
						html += '<b>0 <?php echo dol_escape_js($langs->trans("Days"))?></b>';
					}
				html += '</span>';
			html += '</div>';

		}

	<?php } ?>

	// --------------------------------------------------------------------------------------------------------------------------------------------------------
	// --------------------------------------------------------------------------------------------------------------------------------------------------------
	// --------------------------------------------------------------------------------------------------------------------------------------------------------


	if (Math.floor(task.id) != task.id && (/task/.test(task.id))) {
		// Charge de travail prévue
		html += '<div class="oddeven gTILine gTId">';
			html += '<span class="gTaskLabel"><?php echo dol_escape_js($langs->trans("PlannedWorkload"))?>: </span>';
			html += '<span class="gTaskText">';
			if(task.planned_workload !== undefined)
				html += task.planned_workload;
			else
				html += '--:--';
			html += '</span>';
		html += '</div>';

	}
	// Temps consommé
	html += '<div class="oddeven gTILine gTId">';
		html += '<span class="gTaskLabel"><?php echo dol_escape_js($langs->trans("TimeSpent"))?>: </span>';
		html += '<span class="gTaskText">';
		if(task.duration_effective !== undefined)
			html += task.duration_effective;
		else
			html += '--:--';
		html += '</span>';
	html += '</div>';

	html += '<div class="oddeven gTILine gTId">'+
	  '<span class="gTaskLabel"><?php echo dol_escape_js($langs->trans("Progress"))?>: </span>'+
	  '<span class="gTaskText">'+Math.round(task.progress*100)+'%</span>'+
	'</div>';

	<?php if($ganttproadvanced->coloredbyuser) { ?>
		if(Math.floor(task.id) != task.id && (/task/.test(task.id))) {
			html += '<div class="oddeven gTILine gTId">';
			html += '<span class="gTaskLabel"><?php echo dol_escape_js($nametypecontact)?>: </span>';
			html += '<span class="gTaskText">';
			if(task.affected_nameuser) 
				html += task.affected_nameuser;
			html += '</span>';
			html += '</div>';
		}
	<?php } ?>

	if(Math.floor(task.id) != task.id && (/task/.test(task.id))) {
		var datejalon = '';
		if(task.ganttproadvanceddatejalon)
			var datejalon = $.datepicker.formatDate('dd/mm/yy', new Date(task.ganttproadvanceddatejalon));
		html += '<div class="oddeven gTILine gTId">';
		html += '<span class="gTaskLabel"><?php echo dol_escape_js($langs->trans("JalonDate"))?>: </span>';
		html += '<span class="gTaskText">';
			html += datejalon;
		html += '</span>';
		html += '</div>';
	}
	// if(task.description){
		html += '<div class="oddeven gTILine gTId">'+
	  '<span class="gTaskLabel"><?php echo dol_escape_js($langs->trans("Description"))?>: </span>'+
	  '<span class="gTaskText">'+task.description+'</span>'+
	'</div>';
	// }
	if(!(/task/.test(task.id))) {
		var note_private = (task.note_private != null) ? task.note_private : '';
		html += '<div class="oddeven gTILine gTId">'+
	  '<span class="gTaskLabel"><?php echo dol_escape_js($langs->trans("NotePrivate"))?>: </span>'+
	  '<span class="gTaskText">'+note_private+'</span>'+
	'</div>';
	}

	budgetcurrency = '';
	<?php if(floatval(DOL_VERSION) > 14) { ?>
		if(task.budget) budgetcurrency = '<?php echo dol_escape_js($langs->getCurrencySymbol($conf->currency))?>';
		// if(task.budget){
			html += '<div class="oddeven gTILine gTId">'+
		  '<span class="gTaskLabel"><?php echo dol_escape_js($langs->trans("Budget"))?>: </span>'+
		  '<span class="gTaskText">'+task.budget+' '+budgetcurrency+'</span>'+
		'</div>';
		// }
	<?php } ?>

	if((/task/.test(task.id))) {
	// if(task.couttotal){
		var tmptotalnull = (task.couttotal != null) ? task.couttotal : '';
		html += '<div class="oddeven gTILine gTId">'+
	  '<span class="gTaskLabel"><?php echo dol_escape_js($langs->trans('totalcoutstemp'))?>: </span>'+
	  '<span class="gTaskText">'+tmptotalnull+'</span>'+
	'</div>';
	// }
	}

	if((/task/.test(task.id))) {
		<?php
		if($extrafields->attributes[$objtask->table_element]['label']){
			foreach ($extrafields->attributes[$objtask->table_element]['label'] as $key => $label) {
				if($extrafields->attributes[$objtask->table_element]['enabled'][$key] && ($extrafields->attributes[$objtask->table_element]['list'][$key] == 1 || $extrafields->attributes[$objtask->table_element]['list'][$key] == 3)){
					// if($key != 'ganttproadvancedcolor' && $key != 'ganttproadvanceddatejalon' && $key != 'ganttproadvancedtyperelation'){ 
					if(!isset($ganttproadvanced->extrafieldstohide[$key]) && $key != 'ganttproadvanceddatejalon'){
						if(
							$extrafields->attributes[$objtask->table_element]['type'][$key] == 'select' 
							|| 
							$extrafields->attributes[$objtask->table_element]['type'][$key] == 'sellist'
							|| 
							$extrafields->attributes[$objtask->table_element]['type'][$key] == 'link'
						){
							?>
							var valueextr = task.options_<?php echo $key ?>;
							<?php
						}else{
							?>
							var valueextr = task.<?php echo $key ?>;
							<?php
						}
						if(($extrafields->attributes[$objtask->table_element]['type'][$key] == 'date' || $extrafields->attributes[$objtask->table_element]['type'][$key] == 'datetime')) { ?>
							valueextr = $.datepicker.formatDate('dd/mm/yy', new Date(task.<?php echo $key ?>));
						<?php } ?>
						
						if(valueextr == undefined || (valueextr && valueextr.indexOf( 'NaN' ) != -1) || (!task.<?php echo $key ?>)) valueextr = '';

						html += '<div class="oddeven gTILine gTId">'+
						  '<span class="gTaskLabel"><?php echo dol_escape_js($langs->trans($label))?>: </span>'+
						  '<span class="gTaskText">'+valueextr+'</span>'+
						'</div>';
						<?php
					}
				}
			}
		}
		?>
	}



	<?php if($trellotasksplus_enabled && 1<0) { ?>

		html += '<div class="oddeven gTILine gTId">'+
		  '<span class="gTaskLabel"><?php echo dol_escape_js($langs->trans("tagstrellotasksplus"))?>: </span>';

	  	html += '<span class="gTaskText">';
		if(task.trellotasksplusstatus != null)  html += task.trellotasksplusstatus;
		html += '</span>';
		html += '</div>';

	<?php } ?>

	// if(pTask.getCategoriesStr()) {
	// 	html += '<div class="gTILine gTIc">'+
	// 	      '<span class="gTaskLabel"><?php echo dol_escape_js($langs->trans("Categories"))?>: </span>'+
	// 	      '<span class="gTaskText">'+pTask.getCategoriesStr()+'</span>'+
	// 	  '</div>';
	// }

	html += '</div>';
   return html; // Label
}

// ------------------------------------------------------------------------------------------- SCALE GANTT */
function setScaleConfig(level) {
	
	switch (level) {

		<?php if($ganttproadvanced->showhoursingantt){?>
			case "hour":

			   	gantt.config.scales = [
			   		{unit: 'month', step: 1, format: '%F, %Y'},
			       	{unit: "day", step: 1, format: "%d %M", css: function(date) { return "ganttproadvanced_gantt_scale_cell_mediumview_day"; }},
		       		{unit: "hour", step: 1, format: "%H", css: function(date) { return "ganttproadvanced_gantt_scale_cell_mediumview_day"; }}
			   	];
			   	gantt.config.scale_height = 50;
			   	gantt.config.min_column_width = 20;

			   	// console.log(gantt.$resourcesStore);

				// gantt.config.resource_store.scales=[
				// 	{unit: "day", step: 1, date: "%d %M", min_column_width: 20},
				// 	{unit: "hour", step: 1, date: "%H", min_column_width: 20}
				// ]

				// gantt.config.resource_render_config = {
				//     scale: {
				//     	unit: "hour", step: 1, date: "%H:%i", min_column_width: 20
				//     },
				//     subscale: {
				//     	unit: "day", step: 1, date: "%d %M", min_column_width: 20
				//     }
				// };
				// gantt.getDatastore(gantt.config.resource_store).refresh();

				// gantt.ext.resourceView.show("resource"); // s'assurer qu'on est sur la bonne vue
				// gantt.render(); // appliquer la mise à jour

			   	break;
		<?php } ?>
		case "day":

		   	console.log('Scale is day');
		   	gantt.config.scales = [
		   		{unit: 'month', step: 1, format: '%F, %Y'},
		       	{unit: "day", step: 1, format: "%d %M", css: function(date) { return "ganttproadvanced_gantt_scale_cell_mediumview_day"; }}
		   	];
		   	gantt.config.scale_height = 50;
		   	gantt.config.min_column_width = 20;

		   	break;
		case "week":

		   	var weekScaleTemplate = function (date) {
		   	var dateToStr = gantt.date.date_to_str("%d %M");
		  	var endDate = gantt.date.add(gantt.date.add(date, 1, "week"), -1, "day");
		     	return dateToStr(date) + " - " + dateToStr(endDate);
		   	};
		   	gantt.config.scales = [
				// {unit: "week", step: 1, format: weekScaleTemplate},
				// {unit: "day", step: 1, format: "%D"}
				{unit: "week", step: 1, format: function (date) {
					var dateToStr = gantt.date.date_to_str("%d %M");
					var endDate = gantt.date.add(date, +6, "day");
					var weekNum = gantt.date.date_to_str("%W")(date);
					return "#" + weekNum + ", " + dateToStr(date) + " - " + dateToStr(endDate);
				}},
		       	{unit: "day", step: 1, format: "%j %D", css: function(date) { return "ganttproadvanced_gantt_scale_cell_mediumview_day"; }}

		   	];
		   	gantt.config.scale_height = 50;
		   	gantt.config.min_column_width = 20;
		   break;
		case "month":
		   	gantt.config.scales = [
		       	// {unit: "month", step: 1, format: "%F, %Y"},
		       	// {unit: "day", step: 1, format: "%j, %D"}
		       	{unit: "month", step: 1, format: "%F, %Y"},
		       	{unit: "day", step: 1, format: "%d", css: function(date) { return "ganttproadvanced_gantt_scale_cell_smallview_day"; }}
				// {unit: "week", step: 1, format: function (date) {
				// 	var dateToStr = gantt.date.date_to_str("%d %M");
				// 	var endDate = gantt.date.add(gantt.date.add(date, 1, "week"), -1, "day");
				// 	return dateToStr(date) + " - " + dateToStr(endDate);
				// }}
		   	];
		   	gantt.config.scale_height = 50;
		   	gantt.config.min_column_width = 10;
		   break;
		case "quarter":

			var quarter_template = function(date){
			  return "Q" + (Math.floor((date.getMonth() / 3)) + 1)
			}
			// gantt.config.scale_offset_minimal = false;
			gantt.config.scales = [
			  {unit: 'year', step: 1, format: '%Y'},
			  {unit: 'quarter', step: 1, template: quarter_template},
	       	  {unit: "day", step: 1, format: "%d", css: function(date) { return "ganttproadvanced_gantt_scale_cell_hidden_day"; }}

			];

			// gantt.config.scales = [
			// 	{
			// 		unit: "quarter", step: 3, format: function (date) {
			// 			var dateToStr = gantt.date.date_to_str("%M %y");
			// 			var endDate = gantt.date.add(gantt.date.add(date, 3, "month"), -1, "day");
			// 			return dateToStr(date) + " - " + dateToStr(endDate);
			// 		}
			// 	},
			// 	{unit: "month", step: 1, format: "%M"},
			// ];
		   	gantt.config.scale_height = 50;
		   	gantt.config.min_column_width = 2;
		   	break;
		case "year":

		   	gantt.config.scales = [
		       	{unit: "year", step: 1, format: "%Y"},
		       	{unit: "month", step: 1, format: "%M"},
       	  		{unit: "day", step: 1, format: "%d", css: function(date) { return "ganttproadvanced_gantt_scale_cell_hidden_day"; }}

		   	];
		   	gantt.config.scale_height = 50;
		   	gantt.config.min_column_width = 2;
		   	break;
	}



	if(level == 'hour'){
		<?php if(!$ganttproadvanced->showweekend){?>
			gantt.ignore_time = function(date){
			   if(date.getDay() == 0 || date.getDay() == 6)
			      return true;
			};
		<?php } ?>
		<?php if($ganttproadvanced->showhoursingantt && !empty($ganttproadvanced->taskworkingtime)){ ?>
			<?php 
				$hourstart1 = '';
				$hourend1 = '';
				$hourstart2 = '';
				$hourend2 = '';

				$taskworkingtime = $ganttproadvanced->taskworkingtime;
				$worktime = explode('|', $taskworkingtime);

				if(!empty($worktime[0])){
					$hourstart = explode('-', $worktime[0]);
					if(!empty($hourstart)){
						$hstart1 = (int)explode(':', $hourstart[0])[0];
						$hend1 = (int)explode(':', $hourstart[1])[0];
					}
				}

				if(!empty($worktime[1])){
					$hourstart = explode('-', $worktime[1]);
					if(!empty($hourstart)){
						$hstart2 = (int)explode(':', $hourstart[0])[0];
						$hend2 = (int)explode(':', $hourstart[1])[0];
					}
				}
			?>

			var hstart1 = '<?php echo $hstart1; ?>';
			var hend1 = '<?php echo $hend1; ?>';
			var hstart2 = '<?php echo $hstart2; ?>';
			var hend2 = '<?php echo $hend2; ?>';

			gantt.ignore_time = function(date){
				<?php if(!$ganttproadvanced->showweekend){?>
				   if(date.getDay() == 0 || date.getDay() == 6)
				      return true;
				<?php } ?>
				if($("input[name='scale']").val() == 'hour'){
					if(hstart1 != ''){
					  	if(date.getHours() < hstart1)
					    	return true;
					}
					if(hend1 != ''){
						if(hstart2){
						  	if(date.getHours() >hend1 && date.getHours() <hstart2)
						    	return true;
						}else{
						  	if(date.getHours() >=hend1)
						    	return true;
						}
					}
					if(hend2 != ''){
						if(date.getHours() >=hend2)
					    	return true;
					}
				}
			};
		<?php } else{ ?>
			<?php if(!$ganttproadvanced->showweekend){?>
				gantt.ignore_time = function(date){
				   if(date.getDay() == 0 || date.getDay() == 6)
				      return true;
				};
			<?php } ?>
		<?php } ?>

	}else{

		<?php if(!$ganttproadvanced->showweekend){?>
			gantt.ignore_time = function(date){
			   if(date.getDay() == 0 || date.getDay() == 6)
			      return true;
			};
		<?php } else{?>
			gantt.ignore_time = function(date){
		    	return false;
			};
		<?php }?>

		gantt.addTaskLayer(function(task) {

			// console.log('hour start: '+hstart);
			// console.log('hour end: '+hend);

		    if (!task.baselines || !task.baselines.length) return;

		    const container = document.createElement("div");
		    container.className = "baseline-layer";

		    task.baselines.forEach(function(baseline) {
		        const start = new Date(baseline.start_date);
		        const end = new Date(baseline.end_date);

		        if(end.getHours()-start.getHours() > nhours) end.setHours(hend, 0, 0);

		        // Only apply if same day and inside working hours
		        const sameDay = start.toDateString() === end.toDateString();
		        // const inWorkHours = start.getHours() >= hstart && end.getHours() <= 17;
		        const isFullWorkDay = start.getHours() <= hstart && end.getHours() >= hstart;

		        // if (sameDay && isFullWorkDay) {
		            const fullWorkStart = new Date(start);
		            fullWorkStart.setHours(hstart, 0, 0, 0);

		            const fullWorkEnd = new Date(start);
		            fullWorkEnd.setHours(hend, 0, 0, 0);

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

		            // const adjustedStart = (start.getHours() <= hstart) ? fullDayStart : start;
					// const baselineLeft = gantt.posFromDate(adjustedStart);


					// const startOffsetPercent = (start.getHours() <= hstart)
					//     ? 0
					//     : (start - fullDayStart) / (fullDayEnd - fullDayStart);

					// const adjustedEnd = (end.getHours() >= hend) ? fullDayEnd : end;
					// const endOffsetPercent = (adjustedEnd - fullDayStart) / (fullDayEnd - fullDayStart);

					// const baselineLeft = gantt.posFromDate(fullDayStart) + fullDayWidth * startOffsetPercent;
					// const baselineWidth = fullDayWidth * (endOffsetPercent - startOffsetPercent);



					// const fullDayStart = new Date(start);
					// fullDayStart.setHours(0, 0, 0, 0);

					// const fullDayEnd = new Date(start);
					// fullDayEnd.setHours(23, 59, 59, 999);
					const fullDayWidth = gantt.posFromDate(fullDayEnd) - gantt.posFromDate(fullDayStart);

					// Adjusted start (for position)
					const offsetStart = (start.getHours() <= hstart)
					  ? 0
					  : (start - fullDayStart) / (fullDayEnd - fullDayStart);

					const baselineLeft = gantt.posFromDate(fullDayStart) + (fullDayWidth * offsetStart);

					// Adjusted width (for working time only)
					const WORK_START = hstart;
					const WORK_END = hend;
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
		            el.style.height = "6px";
		            // el.style.background = "#00aaff";
		            el.style.background = "#bc35d5";
		            el.style.opacity = 1;

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
		// setTimeout(function () {




		// 	gantt.eachTask(function(task) {
		// 		base_lines = data.baselines.filter(b => b.task_id === task.id);
		//         if(base_lines) {
		//             base_lines.forEach(function(baseline) {
		//             	newstart_date = new Date(baseline.start_date.getFullYear(),baseline.start_date.getMonth(),baseline.start_date.getDate());
		//             	newduration = baseline.duration*24/8;
		//             	// if(newduration == 24) newduration = 23;
		//             	newduration = 23;
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

		// 	console.log(data.baselines);
		// 	gantt.render();

	    // }, 500);
	}



	// gantt.config.auto_types = false;

	$('#ganttproadvanced').attr('data-scale',level);

	var viewbyresource = initialviewmodevariable();

	// Change scale in link
	var currentUrl = window.location.href;
	var url = new URL(currentUrl);
	url.searchParams.set("scale", level);
	if(viewbyresource) {
		url.searchParams.set("viewmode", 'byresource');
	}
	var newUrl = url.href; 

	var refresh = window.location.protocol + "//" + window.location.host + window.location.pathname + '?arg=1';    
	window.history.pushState({ path: newUrl }, '', newUrl);


	var tmpurl = window.location.protocol + "//" + window.location.host;

	if($('.tabs #kanban').length > 0) {
		tabkanban = $('.tabs #kanban').attr('href');
		var url = new URL(tmpurl+tabkanban);
		url.searchParams.set("scale", level);
		var newUrl = url.href;
		newUrl = newUrl.replace(tmpurl, "");
		$('.tabs #kanban').attr('href', newUrl);
	}

	if($('.tabs #gantt').length > 0) {
		tabgantt = $('.tabs #gantt').attr('href');
		var url = new URL(tmpurl+tabgantt);
		url.searchParams.set("scale", level);
		var newUrl = url.href;
		newUrl = newUrl.replace(tmpurl, "");
		$('.tabs #gantt').attr('href', newUrl);
	}
}

function LightboxOpened(task_id){
	// if(!$('.gantt_wrap_section select').hasClass("select2-hidden-accessible")) {
		// $('.gantt_wrap_section select').css({'width' : ''}).addClass("maxwidth300").trigger('change').select2();
		// console.log('Executed select2 on users select');
	// }

	<?php if($ganttproadvanced->use_task_dependencies) { ?>
	<?php } ?>

	$('.gantt_wrap_section select').addClass("width300").select2();
}

function checkAlertPeriodDuree(task){
    // var task = gantt.getTask(id);

	// var debut = task.start_date;
	// var fin = task.end_date;
	// // var dure = task.planned_workload;

	// var duration = task.planned_workload.split(':');
	// var hour = duration[0]*3600;
	// var min = duration[1]*60;
	// var dure = hour+min;

	// var datedebut = debut.getTime();
	// var datefin = fin.getTime();

	// var diffDays = Math.abs((datefin - datedebut)/1000);

	// // console.log('diffDays : '+diffDays);
	// // console.log('dure : '+dure);

	// if(dure > 0 && diffDays > dure){

	// 	var periodprevus = duration[0]+":"+duration[1];
	// 	var msgtoshow = "<b>"+task.ref+" "+task.text+"</b><br>"+'<?php echo dol_escape_js($langs->transnoentities("alert_depasse_dure")); ?> ('+periodprevus+')';

	// 	// gantt.message({type:"warning", text:msgtoshow});
	// 	// alert(msgtoshow);
	// }
}


function calculateSummaryProgress(task) {
	// console.log('calculateSummaryProgress : '+task.id);

	// if (task.type != gantt.config.types.project)
	// 	return task.progress;

	// if (Math.floor(task.id) != task.id && (/task/.test(task.id)))

	if ((/task/.test(task.id)) && task.type != gantt.config.types.project)
		return task.progress;

	var totalToDo = 0;
	var totalDone = 0;

	gantt.eachTask(function (child) {
		if (child.type != gantt.config.types.project) {
			totalToDo += child.duration;
			totalDone += (child.progress || 0) * child.duration;
		}
	}, task.id);

	if (!totalToDo) return 0;

	else return totalDone / totalToDo;
}

function refreshSummaryProgress(id, submit) {

	// console.log('refreshSummaryProgress : '+id+gantt.config.root_id);

	if (!gantt.isTaskExists(id))
		return;

	var task = gantt.getTask(id);
	var newProgress = calculateSummaryProgress(task);
	// console.log('change in data base ...'+id+' - Progress : '+newProgress);
	if (newProgress !== task.progress) {
		task.progress = newProgress;

		if (!submit) {
			gantt.refreshTask(id);
			// console.log('refreshTask : '+id);
		} else {
			gantt.updateTask(id);
			// console.log('updateTask : '+id);
		}
	}

	// console.log(gantt.getParent(id));

	if (!submit && gantt.getParent(id) !== gantt.config.root_id) {
		refreshSummaryProgress(gantt.getParent(id), submit);
	}
}

function startDatepicker(node){
    return $(node).find("input[name='start']");
}
function endDateInput(node){
    return $(node).find("input[name='end']");
}

function formatProgress(task){
  	var toreturn = "<span class='progressnumber'>" + Math.round(task.progress * 100) + "<span class='progresspercent'>%</span> </span>";

  	<?php if($ganttproadvanced->coloredbyuser && $ganttproadvanced->t_showuserinchart) { ?>
  		var els = $(".ganttproadvancedfilterdiv input[name='scale']:checked").val();
  		// console.log(els);
	  	if(Math.floor(task.id) != task.id && els != 'quarter' && els != 'year' && task.duration >= 0 && (/task/.test(task.id))) {
		  toreturn += "<span class='affected_nameuser'>" + task.affected_nameuser + " </span>";
		}
	<?php } ?>

  	return toreturn;
}

function selectAffectedUsersChanged(that){

	user_id = that.value;

	$('#ganttproadvancedforcereloadpage').val(1);

	var task_id = $('input[id="ganttproadvanced_popup_task_id"]').val();

	// taskid = taskid.replace('task', '');
    // var task_id = taskid.split('_')[0];



	var currentcolor = $(that).parent('div').parent('div').parent('div').find('.editor_color');
	$(currentcolor).prop("disabled", false);

	if(user_id < 0){
		currentcolor.val('<?php echo dol_escape_js($ganttproadvanced->colorgristask); ?>');
		// $(currentcolor).prop("disabled", true);
	 	return;
	}

	$.ajax({
        data:{
        	'ajxaction': 'get_user_field_color'
        	,'user_id': user_id
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/ganttproadvanced/ganttproadvanced_ajax.php',1)); ?>",
        type:'POST',
        dataType:'json',
        success:function(returned){
            if(returned && returned['colortask']) {
        		currentcolor.val(returned['colortask']);
    			// console.log(currentcolor.val());
        		if(currentcolor.val() != '<?php echo $ganttproadvanced->colorgristask; ?>') {
        			// $(currentcolor).prop("disabled", true);
        		}
            }

            // console.log($('input[name="end"]'));

            var endValue = endDateInput($('.gantt-lb-datepicker')).datepicker('getDate');
		    var startValue = startDatepicker($('.gantt-lb-datepicker')).datepicker('getDate');

			// console.log(startValue);
			// console.log(endValue);

            calculNbTaskByUser(task_id, user_id, startValue, endValue);
        }
    });
}

function calculNbTaskByUser(task_id='', user_id=0, start_date='', end_date='') {
	var project_id = 0;

	if(task_id){
		var task = gantt.getTask(task_id);
		if(task.id){
			project_id = task.projectid;
			start_date = task.start_date;
			end_date = task.end_date;
		}
	}
	
	$.ajax({
        data:{
        	'ajxaction': 'getnbtasks'
        	,'projectid': project_id
        	,'taskid': task_id
        	,'userid': user_id
        	,'start_date': start_date
        	,'end_date': end_date
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/ganttproadvanced/ganttproadvanced_ajax.php',1)); ?>",
        type:'POST',
        success:function(returned){
            if(returned) {
            	$('.totaltasksbyuser .nbtasks').html(returned+' <?php echo $langs->trans('Task') ?>');
            }
        }
    });
}

function DurationHour(node){
    return node.querySelector(".inputhour").value;
}
function DurationMin(node){
    return node.querySelector(".inputminute").value;
}

// ------------------------------------------------------------------------------------------- Collapse Tasks */
function ganttproadvancedCloseAllTasks()
{
    gantt.eachTask(function(task2close){
        if (task2close.$level === 0) {
        	gantt.close(task2close.id);
        }  
    });
}

function ganttproadvancedOpenAllTasks()
{
    gantt.eachTask(function(task2open){
        if (task2open.$level === 0) {
        	gantt.open(task2open.id);
        }
    });
}

// ------------------------------------------------------------------------------------------- Search tasks */
function ganttproadvancedRefreshData() {
    var inputEl = document.getElementById('ganttproadvancedsearch');
    
    // Split input value into multiple search terms (by space or comma)
    filter_data_ganttproadvanced = inputEl.value.trim().toLowerCase().split(/[\s,]+/);
    gantt.refreshData();
    
    // console.log(filter_data_ganttproadvanced);
    setMarkerHeightAsHeightOfTaskRowForAllTasks();
    
    function hasSubstr(parentId) {
        if (!gantt.isTaskExists(parentId)) return;
        
        if(inputEl.value == '') return true;

        var task = gantt.getTask(parentId);
        var taskText = task.text.toLowerCase().trim();
        var taskRef = task.ref.toLowerCase().trim();

        // console.log('taskRef:', taskRef);    // Debug task ref
        
        if (filter_data_ganttproadvanced.length === 0 || filter_data_ganttproadvanced[0] === '') return true;

        // Check if the task text or reference contains any of the search terms
        for (var i = 0; i < filter_data_ganttproadvanced.length; i++) {
            var term = filter_data_ganttproadvanced[i].trim();

            if (term && (taskText.indexOf(term) !== -1 || taskRef.indexOf(term) !== -1)) {
                return true;
            }
        }

        // Check if any of the child tasks match the search term
        var child = gantt.getChildren(parentId);
        for (var i = 0; i < child.length; i++) {
            if (hasSubstr(child[i])) {
                return true;
            }
        }
        
        return false;
    }
    
    gantt.attachEvent("onBeforeTaskDisplay", function(id, task) {
        return hasSubstr(id);
    });
}
// function ganttproadvancedRefreshData_() {

// 	// setTimeout(function() {
// 		var inputEl = document.getElementById('ganttproadvancedsearch');
		 
// 		gantt.refreshData();
		
// 		setMarkerHeightAsHeightOfTaskRowForAllTasks();
		
// 		function hasSubstr(parentId){

// 			if(!gantt.isTaskExists(parentId)) return;
			
// 		  var task = gantt.getTask(parentId);
// 		  // console.log(task);
// 		  if(inputEl.value == '') return true;
// 		  // console.log('id: '+parentId+' -text: '+task.text.toLowerCase());
// 		  // console.log('search: '+inputEl.value.toLowerCase());
// 		  if(task.text.toLowerCase().indexOf(inputEl.value.toLowerCase() ) !== -1 || task.ref.toLowerCase().indexOf(inputEl.value.toLowerCase() ) !== -1)
// 		    return true;
		 
// 		  var child = gantt.getChildren(parentId);
// 		  for (var i = 0; i < child.length; i++) {
// 		    if (hasSubstr(child[i]))
// 		      return true;
// 		  }
// 		  return false;
// 		}
		
// 		gantt.attachEvent("onBeforeTaskDisplay", function(id, task){
// 			// console.log("onBeforeTaskDisplay");
// 		  if (hasSubstr(id))
// 		    return true;
		 
// 		    return false;
// 		});

//   	// }, 1000);
// }

// ------------------------------------------------------------------------------------------- */

function changeperiod(that) {
	var val = $(that).val();
	var id_task = $('input[name="id_task"]').val();
	var id_parent = $('input[name="id_parent"]').val();
	var change_relation = $('.change_relation').val();

	if($('.change_relation').val() == '0'){
		$('.change_relation').val('1');
	}
	if(id_parent){
		var task = gantt.getTask(id_task);
		var parent = gantt.getTask(id_parent);
		var date = '';
    	if(task && task.id && (/task/.test(task.id)))
			var days = task.duration+1;
		else
			var days = parent.duration+1;
		if(val == 0) {
			if(task && task.id && (/task/.test(task.id))){
				$('input[name="start"]').datepicker("setDate", task.start_date);
				$('input[name="end"]').datepicker("setDate", task.end_date);
			}else{
				$('input[name="start"]').datepicker("setDate", parent.start_date);
				$('input[name="end"]').datepicker("setDate", parent.end_date);
			}
		}
		if(val == 1 && ( (task && change_relation == 1 ) || !task) ){
			$('input[name="start"]').datepicker("setDate", parent.end_date);
            date  = new Date(parent.end_date);
            date.setDate(date.getDate() + days);
			$('input[name="end"]').datepicker("setDate", date);
		}
		if(val == 2 && ( (task && change_relation == 1 ) || !task) ){
			$('input[name="end"]').datepicker("setDate", parent.end_date);
			date  = new Date(parent.end_date);
            date.setDate(date.getDate() - days);
			$('input[name="start"]').datepicker("setDate", date);
		}
		if(val == 3 && ( (task && change_relation == 1 ) || !task) ){
			$('input[name="start"]').datepicker("setDate", parent.start_date);
			date  = new Date(parent.start_date);
            date.setDate(date.getDate() + days);
			$('input[name="end"]').datepicker("setDate", date);
		}
		if(val == 4 && ( (task && change_relation == 1 ) || !task) ){
			$('input[name="end"]').datepicker("setDate", parent.start_date);
			date  = new Date(parent.start_date);
            date.setDate(date.getDate() - days);
			$('input[name="start"]').datepicker("setDate", date);
		}

	}
}


function changeInputDatePickerData(that) {
	$(that).addClass('donotsubmit');

	var content = $(that).val().trim().split('/');

	month = content[0]; year = content[1];

	if(month && month.length == 2 && year && year.length == 4) {
		
		$(that).datepicker('option', 'defaultDate', new Date(year, month-1, 1));
        $(that).datepicker('setDate', new Date(year, month-1, 1));

        $('#'+($(that).attr('id'))+'month').val(month);
        $('#'+($(that).attr('id'))+'year').val(year);

        // console.log('month : '+month);
        // console.log('year : '+year);
	}
}

function submitFormWhenChange(wait = 0) {

	var refreshpageautomatically = <?php echo (int) $ganttproadvanced->refreshpageautomatically; ?>;

	// emptyMaxDateInput();

	var timeout = 0;
	if(wait > 0) {
		timeout = 200;
	}

	if($('fieldset .date_picker').hasClass('donotsubmit')) return 0;

	setTimeout(
  		function(){ 
			ganttproadvanced_refreshfilter();

			if(!refreshpageautomatically) return 0;

			$('.ganttproadvancedformindex').submit();

  		}, timeout
  	);
}


function ganttproadvanced_SubmitFormOnChange() {
	var refreshpageautomatically = <?php echo (int) $ganttproadvanced->refreshpageautomatically; ?>;

	if(refreshpageautomatically) {
		$('.trellotasksplusformindex').submit();
	}
}

function ganttproadvanced_refreshfilter(selectallornone = 0) {
	var search_customer 	= $('#search_customer').val();
	var search_userid 		= $('#search_userid').val();
	var search_category 	= $('#search_category').val();
	var search_status 		= $('#search_status').val();
	var search_projects 	= $('select[name="search_projects[]"]').val();
	var search_tasktype 	= $('#search_tasktype').val();
	var search_affecteduser	= $('#search_affecteduser').val();
	var debutyear 			= $('#debutyear').val();
	var debutmonth 			= $('#debutmonth').val();
	var finyear 			= $('#finyear').val();
	var finmonth 			= $('#finmonth').val();

	var refreshpageautomatically = <?php echo (int) $ganttproadvanced->refreshpageautomatically; ?>;

    // $('select[name="search_projects[]"]').select2("val", "");

	$.ajax({
        data:{
        	'ajxaction': 'refreshfilter'
        	,'search_customer': search_customer
			,'search_userid': search_userid
			,'search_category': search_category
			,'search_status': search_status
			,'search_projects': search_projects
			,'search_tasktype': search_tasktype
			,'search_affecteduser': search_affecteduser
			,'debutyear': debutyear
			,'debutmonth': debutmonth
			,'finyear': finyear
			,'finmonth': finmonth
			,'selectallornone': selectallornone
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/ganttproadvanced/ganttproadvanced_ajax.php',1)); ?>",
        type:'POST',
    	dataType:'json',
        success:function(returned){
            if(returned) {

            	// console.log(returned);
            	
				// ------------------------------------------------------------------ Project Select
            	if(returned['selectprojects'] !== undefined) {
            		$('#ganttproadvancedselectprojectsauthorized').html(returned['selectprojects']);
            		$('#ganttproadvancedselectprojectsauthorized select').select2();
            	}

				// ------------------------------------------------------------------ Affect User Select
            	if(returned['selectusers'] !== undefined) {
            		$('#ganttproadvanced_users_as_taskcontact').html(returned['selectusers']);
            		$('#ganttproadvanced_users_as_taskcontact select').select2();
            	}

				// ------------------------------------------------------------------ Refresh page automatically
            	if(refreshpageautomatically) {
			    	$('.ganttproadvancedformindex').submit();
			    }

            }
        }
    });
}

function emptyMaxDateInput() {
	// $('input[name*="search_mindate"], input[name*="search_maxdate"]').val('');
}

// Update comments 
function loadcomments(id_task) {

	$.ajax({
        data:{
        	'ajxaction': 'loadcomment'
        	,'id_task': id_task
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/ganttproadvanced/ganttproadvanced_ajax.php',1)); ?>",
        type:'POST',
        success:function(returned){
            if(returned) {
            	// console.log(returned);
            	$('.kanban_list_comments').html(returned);
            }else{
            	$('.kanban_list_comments').html('');
            }
        }
    });
	$('.cancelcomment').hide();
}

// Mise a jour commentaires
function popcomments(that) {
	$('.gantt_cal_quick_info, .gantt_cal_light, .gantt_tooltip').remove();

	var id_task = $(that).data('id');

	$.ajax({
		data:{
        	'ajxaction': 'addcomment'
        	,'id_task': id_task
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/ganttproadvanced/ganttproadvanced_ajax.php',1)); ?>",
        type:'POST',
        success:function(returned){
			$('.gantt_cal_quick_info').remove();
			$('.gantt_cal_light').remove();
			$('.gantt_tooltip').remove();
			$('#popcomments').remove();
			$('.gantt_cal_cover').remove();
            if(returned) {
            	$('body').append(returned);
            }

            $("#kanban_comments .classfortooltip").tooltip({
				show: { collision: "flipfit", effect:"toggle", delay:50, duration: 20 },
				hide: { delay: 250, duration: 20 },
				tooltipClass: "mytooltip",
				content: function () {
		    		// console.log("Return title for popup");
		            return $(this).prop("title");		/* To force to get title as is */
		   		}
			});

			loadcomments(id_task);
        }
	})
}
	
function countCurrentComments() {

	var id_tache = $('#id_task').val();

	var countcommt = $('.kanban_list_comments>div').length;

	var txtcomm = '';
	if(countcommt > 0) {
		txtcomm = (countcommt < 9) ? countcommt : '+9';
		$('div[task_id="task'+id_tache+'"]').find('span.fa-comment').attr('title', countcommt+' <?php echo $langs->trans("Comments"); ?>');
	} else {
		$('div[task_id="task'+id_tache+'"]').find('span.fa-comment').attr('title','<?php echo $langs->trans("Comments"); ?>');
	}

	$('div[task_id="task'+id_tache+'"]').find('.nb_comments').html(txtcomm);
}

function keypressComment(that) {
	$(that).parent('.kanban_txt_comment').find('.cancelcomment').show();
}

function ganttproadvanced_textarea_autosize(that){

	$(that).css('resize', 'none');
	$(that).height(0);
	$(that).height($(that)[0].scrollHeight);

}


function editcomment (that) {
	$('.update_comment').hide();
	$(that).parents('.kanban_show_comment').find('.update_comment').show();
	$(that).parents('.kanban_show_comment').find('.show_comment').hide();
	ganttproadvanced_textarea_autosize($(that).parents('.kanban_show_comment').find('textarea.update_comment'));
}

function cancelcomment(that) {
	$('.kanban_txt_comment textarea').val('');
	$('.cancelcomment').hide();
}

function closecomments(that) {
	countCurrentComments();

	$('.kanban_txt_comment textarea').val('');
	$('.cancelcomment').hide();
	$('#popcomments').remove();
}

function loadcomments(id_task) {

	$.ajax({
        data:{
        	'ajxaction': 'loadcomment'
        	,'id_task': id_task
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/ganttproadvanced/ganttproadvanced_ajax.php',1)); ?>",
        type:'POST',
        success:function(returned){
            if(returned) {
            	// console.log(returned);
            	$('.kanban_list_comments').html(returned);
            }else{
            	$('.kanban_list_comments').html('');
            }
        }
    });
	$('.cancelcomment').hide();
}

function savecomment(that) {
	var comment = $('#txt_comment').val();
	var id_task = $('#id_task').val();

	if(!comment) return;

	$.ajax({
        data:{
        	'ajxaction': 'savecomment'
        	,'id_task': id_task
        	,'comment': comment
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/ganttproadvanced/ganttproadvanced_ajax.php',1)); ?>",
        type:'POST',
        success:function(returned){
            if(returned) {
				$('#txt_comment').val('');
				$('#txt_comment').text('');
				cancelcomment();
				if(returned['msg']) {
					$.jnotify(returned['msg'], "500", false, { remove: function (){} } );
            	}
            }
        	// closecomments();
        	loadcomments(id_task);
        }
    });
	$('.cancelcomment').hide();
}

function deletecomment(that) {
	var id_comment = $(that).data('id');
	var id_task = $(that).data('task');
	$.ajax({
        data:{
        	'ajxaction': 'deletecomment'
        	,'id_comment': id_comment
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/ganttproadvanced/ganttproadvanced_ajax.php',1)); ?>",
        type:'POST',
        success:function(returned){
            if(returned) {
				if(returned['msg']) {
					$.jnotify(returned['msg'], "500", false, { remove: function (){} } );
            	}
            }
			loadcomments(id_task);
        }
    });
	// $('.cancelcomment').hide();
}

function cancelupdatecomment(that) {
	$('.update_comment').hide();
	$(that).parents('.kanban_show_comment').find('.show_comment').show();
}

function updatecomment(that) {
	var id_comment = $(that).data('id');
	var id_task = $(that).data('task');
	var comment = $('.comment_'+id_comment).val();
	$.ajax({
        data:{
        	'ajxaction': 'updatecomment'
        	,'id_comment': id_comment
        	,'comment': comment
        },
        url:"<?php echo dol_escape_js(dol_buildpath('/ganttproadvanced/ganttproadvanced_ajax.php',1)); ?>",
        type:'POST',
        success:function(returned){
            if(returned) {
				if(returned['msg']) {
					$.jnotify(returned['msg'], "500", false, { remove: function (){} } );
            	}
            }
        	loadcomments(id_task);
        }
    });
	$('.cancelcomment').hide();
}

function checkIfCanBeAddLink(tasktarget, tasksource) {
	var itcanbeadded = true;

	if(gantt.isTaskExists(tasktarget)) {

		var target = gantt.getTask(tasktarget);
		var source = gantt.getTask(tasksource);
		// console.log(parseInt(target.ganttproadvancedrelatedtask));

		if(target && parseInt(target.ganttproadvancedrelatedtask) > 0 || source && parseInt(source.ganttproadvancedrelatedtask) > 0 ) {
			itcanbeadded = false;
		}
	}

	return itcanbeadded;
}

function getTheLinkTypeDetails(link_type) {
	var types = gantt.config.links;

	var typelinkdetail = [];

    switch (link_type){
        case types.finish_to_start:
			relation_id = '<?php echo ganttproadvanced::TYPE_END_START; ?>';
			relation_label = '<?php echo $ganttproadvanced->typesrelation[ganttproadvanced::TYPE_END_START]; ?>';
            break;
        case types.finish_to_finish:
			relation_id = '<?php echo ganttproadvanced::TYPE_END_END; ?>';
			relation_label = '<?php echo $ganttproadvanced->typesrelation[ganttproadvanced::TYPE_END_END]; ?>';
            break;
        case types.start_to_start:
			relation_id = '<?php echo ganttproadvanced::TYPE_START_START; ?>';
			relation_label = '<?php echo $ganttproadvanced->typesrelation[ganttproadvanced::TYPE_START_START]; ?>';
            break;
        case types.start_to_finish:
			relation_id = '<?php echo ganttproadvanced::TYPE_START_END; ?>';
			relation_label = '<?php echo $ganttproadvanced->typesrelation[ganttproadvanced::TYPE_START_END]; ?>';
            break;
    }

	typelinkdetail['id'] = relation_id;
	typelinkdetail['label'] = relation_label;


    return typelinkdetail;
}

function getTheLinkTypeIdForJs(typerelation) {
	var types = gantt.config.links;

	jslinkid = 0;

    switch (typerelation){
        case '<?php echo ganttproadvanced::TYPE_END_START; ?>':
			jslinkid = types.finish_to_start;
            break;
        case '<?php echo ganttproadvanced::TYPE_END_END; ?>':
			jslinkid = types.finish_to_finish;
            break;
        case '<?php echo ganttproadvanced::TYPE_START_START; ?>':
			jslinkid = types.start_to_start;
            break;
        case '<?php echo ganttproadvanced::TYPE_START_END; ?>':
			jslinkid = types.start_to_finish;
            break;
    }

    return jslinkid;
}

function hideorShowtyperelation(){

	var relatedtask = parseInt($('#options_ganttproadvancedrelatedtask').val());
	var typerelation = parseInt($('#options_ganttproadvancedtyperelation').val());
	var numberdayslate = parseInt($('#options_ganttproadvancednumberdayslate').val());

	if(relatedtask > 0){
		$('#options_ganttproadvancedtyperelation').parents('.gantt_wrap_section').removeClass('hidden');
		$('#options_ganttproadvancednumberdayslate').parents('.gantt_wrap_section').removeClass('hidden');
		if(!$('#options_ganttproadvancedtyperelation').val()) {
			$('#options_ganttproadvancedtyperelation').val(<?php echo ganttproadvanced::TYPE_END_START; ?>).trigger('change');
		}
	}else{
		$('#options_ganttproadvancedtyperelation').parents('.gantt_wrap_section').addClass('hidden');
		$('#options_ganttproadvancednumberdayslate').parents('.gantt_wrap_section').addClass('hidden');
	} 

}

function viewmodebyresource_highlightFromSelected() {
    const selected = $('.viewmodebyresource_per_principal_contributeur .gantt_row.gantt_selected');
    if (!selected.length) return;

	// console.log("viewmodebyresource_highlightFromSelected");

    const taskId = selected.attr('task_id');
    const prefix = taskId.split('_')[0];
    const prefix_ = prefix + '_';

    $('.viewmodebyresource_per_principal_contributeur .gantt_row[task_id*="task"]').each(function () {
        const id = $(this).attr('task_id');
        $(this).toggleClass('viewmodebyresource_highlighted', id === prefix || id.startsWith(prefix_));
    });
}

// function refreshBaselinesAfterScaleChange() {
// 	console.log('batchUpdate !!');
//     gantt.batchUpdate(function () {
//         gantt.eachTask(function (task) {
//             gantt.refreshTask(task.id); // force le redraw de la ligne
//         });
//     });
// }


// function refreshBaselinesManuellement() {
//     gantt.batchUpdate(function () {
//     });
// }


function adjustToWorkHours(date) {
    var d = new Date(date);
    var h = d.getHours();
    
    // Si en dehors des heures de travail (9h-17h)
    if(h < 9) {
        d.setHours(9, 0, 0, 0);
    } else if(h >= 17) {
        d.setDate(d.getDate() + 1);
        d.setHours(9, 0, 0, 0);
    }
    
    // Si c'est un week-end
    while([0, 6].includes(d.getDay())) {
        d.setDate(d.getDate() + 1);
        d.setHours(9, 0, 0, 0);
    }
    
    return d;
}

$(window).on('load',function(){
});

</script>