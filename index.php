<html>
<head>

	<title>WebSQL LiteEditor</title>
	<!-- Links (CSSs, Fonts and related) -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.2.1/css/bootstrap.min.css" />
	<link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
	<link href="//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css" rel="stylesheet">
	<link rel="stylesheet" href="adminlte/plugins/font-awesome/css/font-awesome.min.css">
	<link rel="stylesheet" href="adminlte/dist/css/adminlte.min.css">
	<link rel="stylesheet" href="adminlte/plugins/datepicker/datepicker3.css">
	<link rel="stylesheet" href="adminlte/plugins/daterangepicker/daterangepicker-bs3.css">
	<link rel="stylesheet" href="adminlte/plugins/colorbox/css/colorbox.css">
	<style>
	textarea {
		resize: none;
	}
	.logIndex{
		text-align: center;
    font-weight: bold;
    font-size: 1.4em;
		float: left;
	}
	</style>

	<!-- Scripts -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.2.1/js/bootstrap.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.10.2/moment.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.2/ace.js"></script>
	<script src="adminlte/plugins/colorbox/js/jquery.colorbox-min.js"></script>
	<script src="adminlte/plugins/colorbox/js/jquery.colorbox-pt-BR.js"></script>
	<script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
	<script src="adminlte/dist/js/adminlte.min.js"></script>
	<script src="adminlte/plugins/daterangepicker/daterangepicker.js"></script>
	<script src="adminlte/plugins/datepicker/bootstrap-datepicker.js"></script>
	<script src="adminlte/plugins/database/db.js"></script>

	<script>
	var editor;
	var db;
	var pendingTasks = 0;
	var logIndex = 0;
	var logTemplate = '<div class="card-comment"><span class="logIndex">INDEX</span><div class="comment-text"><span class="username">ACTION<span class="text-muted float-right">DATE</span></span>TEXT</div></div>';
	var resultTable;
	var resultTableDT;
	var allTables = [];
	var allTablesTemplate = '<a href="#" class="nav-link"><i class="nav-icon fa fa-table"></i><p>TABLE</p></a>';
	var listTablestimer;
	var checkPendingTasks;
	var DTOptions = {
  	"searching": false,
		"lengthChange": false,
		"pageLength": 50
	}
	$(document).ready(function(){
		// Premises
		$('[contenteditable]').keydown(function(e) {
			if (e.keyCode === 13) {
				return false;
			}
		});
		$('#sqlArea').keydown(function (e) {
			if (e.ctrlKey && e.keyCode == 13) {
				runCode();
			}
		});
		$('#sqlClear').on('click', function(){
			if(confirm("Clear SQL Window content?")) $('#sqlArea').val("");
		});
		$('#sqlRun').on('click', function(){
			runCode();
		});
		$('#logClear').on('click', function(){
			if(confirm("Clear logs?")){
				$('#logArea').html("");
				logIndex = 0;
			}
		});
		$(".sampleDB-Action").on('click', function(){
			console.log($(this).attr('data-action'))
			loadSampleDB($(this).attr('data-action'));
		});
		$("#saveFile").on('click', function(){
			exportFile();
		});
		$("#uploadFile").on('click', function(){
			$("#uploadFileRaw").trigger('click');
		});
		$("#uploadFileRaw").on('change', function(){
			uploadFile();
		});
		resultTable = $("#resultTable");

		editor = ace.edit("sqlArea");
		editor.session.setMode("ace/mode/sql");
		editor.setTheme("ace/theme/sqlserver");

		db = new Database({
			name: "WebSQL_LiteEditor",
			description: "Default Database",
			size: 10 * 1024 * 1024 //10Mb
		});
		checkPendingTasks = setInterval(function() {
			if(pendingTasks > 0){
				toggleSqlArea(true);
			}
			else{
				toggleSqlArea(false);
			}
		}, 500);
		listTablestimer = setInterval(function() {
			/* List all tables */
			db.query("SELECT name FROM sqlite_master WHERE type='table' and name not like '__Webkit%' order by name asc", function(e){
				$("#tablesListing").html("");
				for(var i = 0; i < e.result.rows.length; i++){
					var html = allTablesTemplate;
					html = html.replace('TABLE', e.result.rows[i].name);
					$("#tablesListing").append(html);
				}
			});
		}, 1500);

		$(".colorbox").colorbox();
	});
	function Timer(callback, delay) {
    var timerId, start, remaining = delay;
    this.pause = function() {
        window.clearTimeout(timerId);
        remaining -= new Date() - start;
    };
    this.resume = function() {
        start = new Date();
        window.clearTimeout(timerId);
        timerId = window.setTimeout(callback, remaining);
    };
    this.resume();
	}

	function runCode(sql){
		pendingTasks ++;
		if(sql != ""){
			if(editor.getSelectedText().length > 0){
				sql = editor.getSelectedText();
			}
			else{
				sql = editor.getValue();
			}
		}
		console.log("RUNNING SQL: " +sql);
		db.query(sql, function(e){
			logAction(e);
			if(e.type == "success"){
				displayResults(e);
			}
			pendingTasks--;
		});
	}

	function logAction(log, total, step){
		var logText = $("#sqlLog");
		var logHTML = logTemplate;
		logHTML = logHTML.replace('DATE', moment().format('DD/MM/YYYY @ HH:MM:SS'));
		logHTML = logHTML.replace('INDEX', ++logIndex);

		if(log.type == "success"){
			logHTML = logHTML.replace('ACTION', 'SUCCESS');
			logText.removeClass("text-danger");
			logText.addClass("text-success");

			if(log.length > 0){
				logText.text("Executed successfuly: " +log.length +" rows selected.");
				logHTML = logHTML.replace('TEXT', +log.length +" rows selected.");
			}
			else{
				logText.text("Executed successfuly: " +log.result.rowsAffected +" rows affected.");
				logHTML = logHTML.replace('TEXT', +log.result.rowsAffected +" rows affected.");
			}
		}
		else{
			logText.removeClass("text-success");
			logText.addClass("text-danger");
			logText.text("ERROR " +log.error.code + " - " +log.error.message);
			logHTML = logHTML.replace('ACTION', 'ERROR');
			logHTML = logHTML.replace('TEXT', log.error.message);
		}
		$("#logArea").prepend(logHTML);
		if(step > 0 && total > 0){
			console.log("Step " +step +"/"+total);
			console.log(log);
		}
		else{
			console.log(log);
		}
	}

	function toggleSqlArea(lock = true){
		var area = $("#sqlAreaLoading");
		if(lock){
			area.css("display", "block");
		}
		else{
			area.css("display", "none");
		}
	}

	function loadSampleDB(method = 'full'){
		switch(method){
			case 'reset':
			if(confirm("This will drop and re-create all sample tables. (All data will be erased) Continue?")){
				/* Drop tables first */
				$.ajax({
					url: 'scripts/drop_tables.sql',
					async: false,
					success: function(e){
						var sqls = e.split('\n');
						pendingTasks += sqls.length;
						for(var i = 0;i < sqls.length; i++){
							db.query(sqls[i], function(e){
								logAction(e);
								pendingTasks--;
							});
						}
					}
				});
				/* Create tables */
				$.ajax({
					url: '/scripts/create_tables.sql',
					async: false,
					success: function(e){
						var sqls = e.split('~');
						pendingTasks += sqls.length;
						for(var i = 0;i < sqls.length; i++){
							db.query(sqls[i], function(e){
								logAction(e);
								pendingTasks--;
							});
						}
					}
				});
			}
			break;
			case 'load':
			if(confirm("This will insert data on sample tables. (This process may take a while) Continue?")){
				/* Insert data into customers table */
				$.ajax({
					url: 'scripts/insert_customers.sql',
					async: false,
					success: function(e){
						var sqls = e.split('\n');
						pendingTasks += sqls.length;
						for(var i = 0;i < sqls.length; i++){
							db.query(sqls[i], function(e){
								logAction(e);
								pendingTasks--;
							});
						}
					}
				});
				/* Insert data into suppliers table */
				$.ajax({
					url: 'scripts/insert_suppliers.sql',
					async: false,
					success: function(e){
						var sqls = e.split('\n');
						pendingTasks += sqls.length;
						for(var i = 0;i < sqls.length; i++){
							db.query(sqls[i], function(e){
								logAction(e);
								pendingTasks--;
							});
						}
					}
				});
				/* Insert data into products table */
				$.ajax({
					url: 'scripts/insert_products.sql',
					async: false,
					success: function(e){
						var sqls = e.split('\n');
						pendingTasks += sqls.length;
						for(var i = 0;i < sqls.length; i++){
							db.query(sqls[i], function(e){
								logAction(e);
								pendingTasks--;
							});
						}
					}
				});
				/* Insert data into orders table */
				$.ajax({
					url: 'scripts/insert_orders.sql',
					async: false,
					success: function(e){
						var sqls = e.split('\n');
						pendingTasks += sqls.length;
						for(var i = 0;i < sqls.length; i++){
							db.query(sqls[i], function(e){
								logAction(e);
								pendingTasks--;
							});
						}
					}
				});
				/* Insert data into order_items table */
				$.ajax({
					url: 'scripts/insert_order_items.sql',
					async: false,
					success: function(e){
						var sqls = e.split('\n');
						pendingTasks += sqls.length;
						for(var i = 0;i < sqls.length; i++){
							db.query(sqls[i], function(e){
								logAction(e);
								pendingTasks--;
							});
						}
					}
				});
			}
			break;
			case 'full':
			if(confirm("This will erase database and it's contents, re-create database and re-populate data. Continue? (This process may take a while)")){
				/* Loads and populate all data and structure */
				$.ajax({
					url: 'scripts/script.sql',
					async: false,
					success: function(e){
						var sqls = e.split('\n');
						pendingTasks += sqls.length;
						for(var i = 0;i < sqls.length; i++){
							db.query(sqls[i], function(e){
								logAction(e);
								pendingTasks--;
							});
						}
					}
				});
			}
			break;
			case 'clear':
			if(confirm("This will erase database and it's contents. Continue? (This process may take a while)")){
				pendingTasks++;
				db.query("SELECT name FROM sqlite_master WHERE type='table' and name not like '__Webkit%'", function(e){
					logAction(e);
					for(var i = 0; i< e.result.rows.length; i++){
						pendingTasks++;
						db.query("DROP TABLE " +e.result.rows[i].name, function(){
							logAction(e);
							pendingTasks--;
						});
					}
					pendingTasks--;
				});
			}
			break;
		}
	}

	function displayResults(e){
		if(resultTableDT instanceof $.fn.dataTable.Api) {
			resultTableDT.destroy();
			resultTableDT = null;
			resultTable.find("thead tr").remove();
			resultTable.find("tbody tr").remove();
		}
		if(e.result.rows.length > 0){
			var headers = [];
			for(var key in e.result.rows[0]){
				headers.push(key);
			}

			setResultHead(headers);

			setResultLines(e);

			resultTableDT = resultTable.DataTable(DTOptions);
		}
	}

	function setResultHead(headers){
		resultTable.find("thead").append("<tr></tr>");
		for(var i = 0; i < headers.length; i++){
			resultTable.find("thead tr").append("<th>" +headers[i] +"</th>");
		}
	}

	function setResultLines(e){
		var headers = [];
		for(var key in e.result.rows[0]){
			headers.push(key);
		}
		for(var i = 0; i < e.result.rows.length; i++){
			var line = "<tr>";
			for(var j = 0; j < headers.length; j++){
				 line += "<td>"  +e.result.rows[i][headers[j]] +"</td>";
			}
			line += "</tr>";
			resultTable.find("tbody").append(line);
		}
	}

	function exportFile(){
		var blob = new Blob([editor.getValue()], {type: "application/sql;charset=utf-8"});
		window.open(window.URL.createObjectURL(blob));
		//saveAs(blob, $("#fileName").text()+".sql");
	}
	function uploadFile(){
		var file = $("#uploadFileRaw")[0].files[0];
		var fileName = file.name;
		var reader = new FileReader();
		reader.onloadend = function(e){
			var content = e.srcElement.result;
			$("#fileName").text(fileName);
			editor.setValue(content, 1);
		}
		reader.readAsText(file);
	}

</script>
</head>
<body>
	<div class="wrapper">
		<div class="main-header">
		</div>
		<aside class="main-sidebar sidebar-dark-primary elevation-4">
			<a href="/" class="brand-link">
				<!-- img src="" alt="Logo" class="brand-image" style="opacity: .8" -->
				<span class="brand-text font-weight-light">WebSQL Lite Editor</span>
			</a>
			<div class="sidebar">
				<nav class="mt-2">
					<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
						<li class="nav-header">Databases</li>
						<li class="nav-item has-treeview menu-open">
							<a href="#" class="nav-link">
								<i class="nav-icon fa fa-database"></i>
								<p>
									WebSQL_LiteEditor
									<i class="right fa fa-angle-left"></i>
								</p>
							</a>
							<ul class="nav nav-treeview" id="tablesListing">
							</ul>
						</li>

						<li class="nav-header">Sample database</li>
						<li class="nav-item has-treeview">
							<a href="#" class="nav-link">
								<i class="nav-icon fa fa-grav"></i>
								<p>
									Sample Database
									<i class="right fa fa-angle-left"></i>
								</p>
							</a>
							<ul class="nav nav-treeview" style="display: none;">
								<li class="nav-item">
									<a href="#" class="nav-link sampleDB-Action" data-action="reset">
										<i class="fa fa-circle-o nav-icon"></i>
										<p>Reset tables</p>
									</a>
								</li>
								<li class="nav-item">
									<a href=".#" class="nav-link sampleDB-Action" data-action="load">
										<i class="fa fa-circle-o nav-icon"></i>
										<p>Load data</p>
									</a>
								</li>
								<li class="nav-item">
									<a href="#" class="nav-link sampleDB-Action" data-action="full">
										<i class="fa fa-circle-o nav-icon"></i>
										<p>Restore (reset and load)</p>
									</a>
								</li>
								<li class="nav-item">
									<a href="#" class="nav-link sampleDB-Action" data-action="clear">
										<i class="fa fa-circle-o nav-icon"></i>
										<p>Clear tables</p>
									</a>
								</li>
								<li class="nav-item">
									<a href="/sql-schema.png" class="nav-link colorbox">
										<i class="fa fa-circle-o nav-icon"></i>
										<p>View schema diagram</p>
									</a>
								</li>
							</ul>
						</li>
					</ul>
				</nav>
			</div>
		</aside>
		<div class="content-wrapper">
			<div class="content-header">

			</div>
			<div class="content">
				<div class="container-fluid">
					<div class="row">
						<div class="col-md-12">
							<div class="card card-widget card-primary">
								<div class="card-header">
									<h3 class="card-title" contenteditable="true" id="fileName">script.sql</h3>
									<div class="card-tools">
										<button type="button" class="btn btn-tool" id="saveFile"><i class="fa fa-save" title="Save current file"></i></button>
										<button type="button" class="btn btn-tool" id="uploadFile"><i class="fa fa-upload" title="Upload a new file"></i></button>
										<input type="file" id="uploadFileRaw" style="display: none;" />
									</div>
								</div>
								<div class="card-body">
									<div class="form-control" id="sqlArea" style="height: 150px;"></div>
								</div>
								<div class="card-footer">
									<div class="row">
										<div class="col-md-4">
											<button class="btn btn-danger float-left" id="sqlClear">Clear</button>
										</div>
										<div class="col-md-4">
											<span class="text-muted">STATUS: </span>
											<span class="text-info" id="sqlLog">Waiting</span>
										</div>
										<div class="col-md-4">
											<button class="btn btn-primary float-right" id="sqlRun">Run</button>
										</div>
									</div>
								</div>
								<div class="overlay" style="display: none;" id="sqlAreaLoading">
									<i class="fa fa-refresh fa-spin"></i>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="card card-widget card-success">
								<div class="card-header">
									<h3 class="card-title">Output</h3>
								</div>
								<div class="card-body" style="height: 200px; overflow: auto; padding: 0.5rem;">
										<table id="resultTable" class="table table-striped table-bordered">
											<thead></thead>
											<tbody></tbody>
										</table>
								</div>
								<!-- div class="card-footer">
									<div class="row">
										<div class="col-md-2 text-center"></div>
										<div class="col-md-8 text-center">
										</div>
										<div class="col-md-2 text-center"></div>
									</div>
								</div -->
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="card card-widget card-info">
								<div class="card-header">
									<h3 class="card-title">Log</h3>
								</div>
								<div class="card-body card-body card-comments" id="logArea" style="height: 200px; overflow-y: auto;">

								</div>
								<div class="card-footer">
									<div class="row">
										<div class="col-md-2">
											<button id="logClear" class="btn btn-danger">Clear</button>
										</div>
										<div class="col-md-8 text-center">

										</div>
										<div class="col-md-2 text-center"></div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<footer class="main-footer">
			<div class="float-right d-none d-sm-block">
				<b>Version</b> 1.0.0
			</div>
			<strong>Copyright © 2019 <a href="#">Gabriel</a>.</strong> This application is intended to run on Google Chrome
		</footer>
	</div>
</body>
</html>
