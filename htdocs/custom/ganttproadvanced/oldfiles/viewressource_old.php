<?php 

if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");       // For root directory
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php"); // For "custom" 


require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
    // require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcategory.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';

dol_include_once("/ganttproadvanced/lib/ganttproadvanced.lib.php");
dol_include_once("/ganttproadvanced/class/ganttproadvanced.class.php");
dol_include_once("/ganttproadvanced/class/ganttproadvancedutils.class.php");
dol_include_once('/ganttproadvanced/core/modules/modganttproadvanced.class.php');

$langs->loadLangs(array('admin', 'projects', 'mails', 'ganttproadvanced@ganttproadvanced'));

if (!$conf->projet->enabled || !$conf->ganttproadvanced->enabled || empty($user->rights->ganttproadvanced->lire)) {
	accessforbidden();
}



$project 		= new Project($db);
$projectstatic 	= new Project($db);
$objtask 		= new Task($db);
$taskstatic 	= new Task($db);
$userstatic     = new User($db);
$extrafields 	= new ExtraFields($db);
$userextrafields 	= new ExtraFields($db);
$ganttproadvanced  		= new ganttproadvanced($db);
$ganttproadvancedutils  = new ganttproadvancedutils($db);
$form 			= new Form($db);
$formproject 	= new FormProjets($db);
$formcompany   	= new FormCompany($db);
$formother 		= new FormOther($db);

$extrafields->fetch_name_optionals_label($objtask->table_element);
$userextrafields->fetch_name_optionals_label($userstatic->table_element);


$ganttproadvanced->upgradeTheModule();

$moreheadcss = '	<style>
	html, body {
		padding: 0px;
		margin: 0px;
		height: 100%;
	}

	#gantt_here {
		width:100%;
		height: 800px;
		height:calc(100vh - 52px);
	}

	.gantt_grid_scale .gantt_grid_head_cell,
	.gantt_task .gantt_task_scale .gantt_scale_cell {
		font-weight: bold;
		font-size: 14px;
		color: rgba(0, 0, 0, 0.7);
	}

	.resource_marker{
		text-align: center;
	}
	.resource_marker div{
		width: 28px;
		height: 28px;
		border-radius: 15px;
		color: #FFF;
		margin: 3px;
		display: inline-flex;
		justify-content: center;
		align-items: center;
	}
	.resource_marker.workday_ok div {
		background: var(--dhx-gantt-base-colors-success);
	}

	.resource_marker.workday_over div{
		background: var(--dhx-gantt-base-colors-error);
	}

	.folder_row {
		font-weight: bold;
	}

	.highlighted_resource,
	.highlighted_resource.odd
	{
		background-color: rgba(255, 251, 224, 0.6);
	}

	.resource-controls .gantt_layout_content{
		padding: 7px;
		overflow: hidden;
	}
	.resource-controls label{
		margin: 0 10px;
		vertical-align: bottom;
		display: inline-block;
		color: #3e3e3e;
		padding: 2px;
		transition: box-shadow 0.2s;
	}

	.resource-controls label:hover{
		box-shadow: 0 2px rgba(84, 147, 255, 0.42);
	}

	.resource-controls label.active,
	.resource-controls label.active:hover
	{
		box-shadow: 0 2px #5493ffae;
		color: #1f1f1f;
	}

	.resource-controls input{
		vertical-align: top;
	}

	.gantt_task_cell.week_end {
		background-color: #e8e8e87d;
	}

	.gantt_task_row.gantt_selected .gantt_task_cell.week_end {
		background-color: #e8e8e87d !important;
	}


	.group_row,
	.group_row.odd,
	.gantt_task_row.group_row{
		background-color: rgba(232, 232, 232, 0.6);
	}

	.gantt_task_line.gantt_project, .gantt_task_line.gantt_bar_task{
		height: 15px !important;
	}
	.gantt_cell {
	    font-size: 0.75em !important;
	}
	.gantt_task_row, gantt_task_row.odd {
	    height: 21px !important;
	}
</style>';

$moreheadjs = '';

// print '<script src="../../codebase/dhtmlxgantt.js?v=9.0.13"></script>';
// $moreheadjs .= '<link rel="stylesheet" href="../../codebase/dhtmlxgantt.css?v=9.0.13">';
// $moreheadjs .= '<link rel="stylesheet" href="../common/controls_styles.css?v=9.0.13">';


$modganttproadvanced = new modganttproadvanced($db);
$mve = $modganttproadvanced->version;



llxHeader($moreheadcss.$moreheadjs, $modname, '', '', '', '', array(), '', 0, 0);

print '<link rel="stylesheet" href="css/librarygantt.css?v='.$mve.'">';
print '<link rel="stylesheet" href="css/ganttproadvanced_custom.css?v='.$mve.'">';

echo '<script src="js/dhtmlxgantt.js?v='.$mve.'"></script>';

print '<div class="gantt_control" style="display: none" >';
	print '<input type="button" id="default" onclick="toggleGroups(this)" value="Show Resource view">';
print '</div>';

print '<div id="gantt_here"></div>';


print '<script>
	
	$(document).ready(function(){$("default").click();});
	
	var taskData = {
	  "data": [
		{ "id": 1, "text": "Office itinerancy", "type": "project", "start_date": "02-04-2025 00:00", "duration": 17, "progress": 0.4, "owner_id": "5", "parent": 0},
		{ "id": 2, "text": "Office facing", "type": "project", "start_date": "02-04-2025 00:00", "duration": 8, "progress": 0.6, "owner_id": "5", "parent": "1"},
		{ "id": 3, "text": "Furniture installation", "type": "project", "start_date": "11-04-2025 00:00", "duration": 8, "parent": "1", "progress": 0.6, "owner_id": "5"},
		{ "id": 4, "text": "The employee relocation", "type": "project", "start_date": "13-04-2025 00:00", "duration": 5, "parent": "1", "progress": 0.5, "owner_id": "5", "priority":3},
		{ "id": 5, "text": "Interior office", "type": "task", "start_date": "03-04-2025 00:00", "duration": 7, "parent": "2", "progress": 0.6, "owner_id": "6", "priority":1},
		{ "id": 6, "text": "Air conditioners check", "type": "task", "start_date": "03-04-2025 00:00", "duration": 7, "parent": "2", "progress": 0.6, "owner_id": "7", "priority":2},
		{ "id": 7, "text": "Workplaces preparation", "type": "task", "start_date": "12-04-2025 00:00", "duration": 8, "parent": "3", "progress": 0.6, "owner_id": "10"},
		{ "id": 8, "text": "Preparing workplaces", "type": "task", "start_date": "14-04-2025 00:00", "duration": 5, "parent": "4", "progress": 0.5, "owner_id": "9", "priority":1},
		{ "id": 9, "text": "Workplaces importation", "type": "task", "start_date": "21-04-2025 00:00", "duration": 4, "parent": "4", "progress": 0.5, "owner_id": "7"},
		{ "id": 10, "text": "Workplaces exportation", "type": "task", "start_date": "27-04-2025 00:00", "duration": 3, "parent": "4", "progress": 0.5, "owner_id": "8", "priority":2},
		{ "id": 11, "text": "Product launch", "type": "project", "progress": 0.6, "start_date": "02-04-2025 00:00", "duration": 13, "owner_id": "5", "parent": 0},
		{ "id": 12, "text": "Perform Initial testing", "type": "task", "start_date": "03-04-2025 00:00", "duration": 5, "parent": "11", "progress": 1, "owner_id": "7"},
		{ "id": 13, "text": "Development", "type": "project", "start_date": "03-04-2025 00:00", "duration": 11, "parent": "11", "progress": 0.5, "owner_id": "5"},
		{ "id": 14, "text": "Analysis", "type": "task", "start_date": "03-04-2025 00:00", "duration": 6, "parent": "11", "progress": 0.8, "owner_id": "5"},
		{ "id": 15, "text": "Design", "type": "project", "start_date": "03-04-2025 00:00", "duration": 5, "parent": "11", "progress": 0.2, "owner_id": "5"},
		{ "id": 16, "text": "Documentation creation", "type": "task", "start_date": "03-04-2025 00:00", "duration": 7, "parent": "11", "progress": 0, "owner_id": "7", "priority":1},
		{ "id": 17, "text": "Develop System", "type": "task", "start_date": "03-04-2025 00:00", "duration": 2, "parent": "13", "progress": 1, "owner_id": "8", "priority":2},
		{ "id": 25, "text": "Beta Release", "type": "milestone", "start_date": "06-04-2025 00:00", "parent": "13", "progress": 0, "owner_id": "5", "duration": 0},
		{ "id": 18, "text": "Integrate System", "type": "task", "start_date": "10-04-2025 00:00", "duration": 2, "parent": "13", "progress": 0.8, "owner_id": "6", "priority":3},
		{ "id": 19, "text": "Test", "type": "task", "start_date": "13-04-2025 00:00", "duration": 4, "parent": "13", "progress": 0.2, "owner_id": "6"},
		{ "id": 20, "text": "Marketing", "type": "task", "start_date": "13-04-2025 00:00", "duration": 4, "parent": "13", "progress": 0, "owner_id": "8", "priority":1},
		{ "id": 21, "text": "Design database", "type": "task", "start_date": "03-04-2025 00:00", "duration": 4, "parent": "15", "progress": 0.5, "owner_id": "6"},
		{ "id": 22, "text": "Software design", "type": "task", "start_date": "03-04-2025 00:00", "duration": 4, "parent": "15", "progress": 0.1, "owner_id": "8", "priority":1},
		{ "id": 23, "text": "Interface setup", "type": "task", "start_date": "03-04-2025 00:00", "duration": 5, "parent": "15", "progress": 0, "owner_id": "8", "priority":1},
		{ "id": 24, "text": "Release v1.0", "type": "milestone", "start_date": "20-04-2025 00:00", "parent": "11", "progress": 0, "owner_id": "5", "duration": 0}

	  ],
	  "links": [

		{ "id": "2", "source": "2", "target": "3", "type": "0" },
		{ "id": "3", "source": "3", "target": "4", "type": "0" },
		{ "id": "7", "source": "8", "target": "9", "type": "0" },
		{ "id": "8", "source": "9", "target": "10", "type": "0" },
		{ "id": "16", "source": "17", "target": "25", "type": "0" },
		{ "id": "17", "source": "18", "target": "19", "type": "0" },
		{ "id": "18", "source": "19", "target": "20", "type": "0" },
		{ "id": "22", "source": "13", "target": "24", "type": "0" },
		{ "id": "23", "source": "25", "target": "18", "type": "0" }

	  ]
	}



	gantt.plugins({
		grouping: true,
		auto_scheduling: true
	});

	// gantt.message({
	// 	text:[
	// 		"Displaying a resource usage diagram.",
	// 		"The diagram is in sync with the main Gantt.",
	// 		"Columns and resources are fully customizable, the resources can be changed using a public api."
	// 	].join("<br><br>"),
	// 	expire: -1
	// });

	function shouldHighlightTask(task){
		var store = gantt.$resourcesStore;
		var taskResource = task[gantt.config.resource_property],
			selectedResource = store.getSelectedId();
		if(taskResource == selectedResource || store.isChildOf(taskResource, selectedResource)){
			return true;
		}
	}

	gantt.templates.grid_row_class = function(start, end, task){
		var css = [];
		if(gantt.hasChild(task.id)){
			css.push("folder_row");
		}

		if(task.$virtual){
			css.push("group_row")
		}

		if(shouldHighlightTask(task)){
			css.push("highlighted_resource");
		}

		return css.join(" ");
	};

	gantt.templates.task_row_class = function(start, end, task){
		if(shouldHighlightTask(task)){
			return "highlighted_resource";
		}
		return "";
	};

	gantt.templates.timeline_cell_class = function (task, date) {
		if (!gantt.isWorkTime({date: date, task: task}))
			return "week_end";
		return "";
	};

	gantt.templates.resource_cell_class = function(start_date, end_date, resource, tasks){
		var css = [];
		css.push("resource_marker");
		if (tasks.length <= 1) {
			css.push("workday_ok");
		} else {
			css.push("workday_over");
		}
		return css.join(" ");
	};

	gantt.templates.resource_cell_value = function(start_date, end_date, resource, tasks){
		var html = "<div>"
		if(resourceMode == "hours"){
			html += tasks.length * 8;
		}else{
			html += tasks.length;
		}
		html += "</div>";
		return html;
	};

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

	gantt.locale.labels.section_owner = "Owner";
	gantt.config.lightbox.sections = [
		{name: "description", height: 38, map_to: "text", type: "textarea", focus: true},
		{name: "owner", height: 22, map_to: "owner_id", type: "select", options: gantt.serverList("people")},
		{name: "time", type: "duration", map_to: "auto"}
	];

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

	var resourceConfig = {
		scale_height: 30,
		scales: [
			{unit: "day", step: 1, date: "%d %M"}
		],
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
						totalToDo += task.duration;
						totalDone += task.duration * (task.progress || 0);
					});

					var completion = 0;
					if(totalToDo){
						completion = Math.floor((totalDone / totalToDo)*100);
					}

					return Math.floor(completion) + "%";
				}, resize: true
			},
			{
				name: "workload", label: "Workload", align:"center", template: function (resource) {
					var tasks = getResourceTasks(resource.id);
					var totalDuration = 0;
					tasks.forEach(function(task){
						totalDuration += task.duration;
					});

					return (totalDuration || 0) * 8 + "h";
				}, resize: true
			},

			{
				name: "capacity", label: "Capacity", align:"center",template: function (resource) {
					var store = gantt.getDatastore(gantt.config.resource_store);
					var n = store.hasChild(resource.id) ? store.getChildren(resource.id).length : 1

					var state = gantt.getState();

					return gantt.calculateDuration(state.min_date, state.max_date) * n * 8 + "h";
				}
			}

		]
	};

	gantt.config.scales = [
		{unit: "month", step: 1, format: "%F, %Y"},
		{unit: "day", step: 1, format: "%d %M"}
	];

	gantt.config.auto_scheduling = true;
	gantt.config.auto_scheduling_strict = true;
	gantt.config.work_time = true;
	gantt.config.columns = [
		{name: "text", tree: true, width: 200, resize: true},
		{name: "start_date", align: "center", width: 100, resize: true},
		{name: "owner", align: "center", width: 80, label: "Owner", template: function (task) {
			if(task.type == gantt.config.types.project){
				return "";
			}

			var store = gantt.getDatastore(gantt.config.resource_store);
			var owner = store.getItem(task[gantt.config.resource_property]);
			if (owner) {
				return owner.text;
			} else {
				return "Unassigned";
			}
		}, resize: true},
		{name: "duration", width: 60, align: "center", resize: true},
		{name: "add", width: 44}
	];

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
				height: 35,
				cols: [
					{ html:"", group:"grids"},
					{ resizer: true, width: 1},
					{ html:"<label class=\'active\' >Hours per day <input checked type=\'radio\' name=\'resource-mode\' value=\'hours\'></label>" +
					"<label>Tasks per day <input type=\'radio\' name=\'resource-mode\' value=\'tasks\'></label>", css:"resource-controls"}
				]
			},

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

	gantt.$resourcesStore.attachEvent("onAfterSelect", function(id){
		gantt.refreshData();
	});

	gantt.init("gantt_here");

	function toggleGroups(input) {
		gantt.$groupMode = !gantt.$groupMode;
		if (gantt.$groupMode) {
			input.value = "show gantt view";

			var groups = gantt.$resourcesStore.getItems().map(function(item){
				var group = gantt.copy(item);
				group.group_id = group.id;
				group.id = gantt.uid();
				return group;
			});

			gantt.groupBy({
				groups: groups,
				relation_property: gantt.config.resource_property,
				group_id: "group_id",
				group_text: "text"
			});
		} else {
			input.value = "show resource view";
			gantt.groupBy(false);
		}
	}

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

	gantt.$resourcesStore.parse([
		{id: 1, text: "QA", parent:null},
		{id: 2, text: "Development", parent:null},
		{id: 3, text: "Sales", parent:null},
		{id: 4, text: "Other", parent:null},
		{id: 5, text: "Unassigned", parent:4},
		{id: 6, text: "John", parent:1},
		{id: 7, text: "Mike", parent:2},
		{id: 8, text: "Anna", parent:2},
		{id: 9, text: "Bill", parent:3},
		{id: 10, text: "Floe", parent:3}
	]);
	console.log([
		{id: 1, text: "QA", parent:null},
		{id: 2, text: "Development", parent:null},
		{id: 3, text: "Sales", parent:null},
		{id: 4, text: "Other", parent:null},
		{id: 5, text: "Unassigned", parent:4},
		{id: 6, text: "John", parent:1},
		{id: 7, text: "Mike", parent:2},
		{id: 8, text: "Anna", parent:2},
		{id: 9, text: "Bill", parent:3},
		{id: 10, text: "Floe", parent:3}
	]);
	console.log(taskData);
	gantt.parse(taskData);
</script>

<script>
	(function(i,s,o,g,r,a,m){i["GoogleAnalyticsObject"]=r;i[r]=i[r]||function(){
	(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	})(window,document,"script","//www.google-analytics.com/analytics.js","ga");

	ga("create", "UA-11031269-1", "auto");
	ga("send", "pageview");
</script>';

