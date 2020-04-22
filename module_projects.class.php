<?php

class module_projects extends module	{
	const version = 1.0;
	const moduleDesc = "Projects Module";
	public $arrProjTypes = array("dev"=>"Development","comm"=>"Commercial","util"=>"Utility");
	private $inputvars;
	function __construct($act = "display")	{
		if($act == "install"){
			//Run Installer function
			if(!$this->install()){
				return false;
			}else{
				return true;
			}
		}elseif($act == "uninstall"){
			//Run Uninstaller function
			if(!$this->uninstall()){
				return false;
			}else{
				return true;
			}
		}elseif($act == "raw"){
			//Handle AJAX Request
			$this->display("raw");
		}else{
			return true;
		}
	}
	public function admin(){
		global $db;
		$modID = parent::getModuleID();
		$sModule = $_REQUEST['module'];
		if(isset($_REQUEST['mact'])){
			//We're handling the submission of the admin screen
			if($_REQUEST['mact'] == "save"){
				$this->adminSave();
			}elseif($_REQUEST['mact'] == "delete"){
				$this->projDelete();
			}elseif($_REQUEST['mact'] == "addasset"){
				$this->assetSave();
				exit;
			}elseif($_REQUEST['mact'] == "getAssets"){
				echo $this->getAssets($_REQUEST['id'],true);
				exit;
			}
		}else{
			$arrSettings = parent::getModuleParams();
			foreach($arrSettings as $row){
				$key = $row['settingName'];
				$$key = $row['settingVal'];
			}
			//Get a list of Quotes we have in the system
			$sSql = "SELECT pID,projectTitle,projectType from repository_{$modID} ORDER BY projectType,pID";
			$stm = $db->query($sSql);
			$projList = "";
			if($stm){
				while($row = $stm->fetch(PDO::FETCH_ASSOC)){
					$pType = $this->arrProjTypes[$row['projectType']];
					$projList .= "<div><table><tr><td>{$row['projectTitle']} ($pType)</td><td>&nbsp;<a href='javascript:void(0)' onclick='deleteProj({$row['pID']})'>DELETE</a> &nbsp; <a href='?id={$row['pID']}'>EDIT</a></td></tr></table></div>";
				}
			}
			if($projList == ""){
				$projList = "<div>No Project Found</div>";
			}
			//Get the form
			$form = $this->getForm();
			//We're showing the main screen
			$out = <<<END
<!-- ?> -->
	<script type='text/javascript'>
	function saveConfig(){
		$('#btnSave').disabled = true;
		var formObj = $('moduleAdmin');
		var url = '/admin/admin_ajax_modules.php';
		new Ajax.Request(url,{
			   parameters: formObj.serialize(),
			   onSuccess: function(transport, json){
				  var data = transport.responseText.evalJSON(true);
				  if(data['error'] == 0){
					  $('formStat').innerHTML = data['message'];
					  $('btnSave').disabled = false;
				  }else{
					  $('formStat').innerHTML = data['message'];
					  $('btnSave').disabled = false;
				  }
			  },
			  method:'post'
		});
	}
	function saveProj(){
		$('saveProjButton').disabled = true;
		var formObj = $('projAdmin');
		var url = '/admin/admin_ajax_modules.php';
		tinyMCE.triggerSave();
		new Ajax.Request(url,{
			   parameters: formObj.serialize(),
			   onSuccess: function(transport, json){
				  var data = transport.responseText.evalJSON(true);
				  if(data['error'] == 0){
					  $('formStat').innerHTML = "<span class='message'>"+data['message']+"</span>";
					  $('saveProjButton').disabled = false;
					  msgFader('formStat');
					  $('projAdmin').reset();
					  updateProjects();
				  }else{
					  $('formStat').innerHTML = data['message'];
					  $('saveProjButton').disabled = false;
				  }
			  },
			  method:'post'
		});
	}
	function deleteProj(id){
		var url = '/admin/admin_ajax_modules.php';
		var msg = "Are you sure you want to remove this project?";
		if(confirm(msg)){
			new Ajax.Request(url,{
				   parameters: "act=delete&mact=projDelete&id=" + id,
				   onSuccess: function(transport, json){
					  var data = transport.responseText.evalJSON(true);
					  if(data['error'] == 0){
						  $('#formStat').innerHTML = data['message'];
						  $('#saveProjButton').disabled = false;
						  updateProjects();
						  msgFader('#formStat');
					  }else{
						  $('#formStat').innerHTML = data['message'];
						  $('#saveProjButton').disabled = false;
					  }
				  },
				  method:'post'
			});
		}
	}
	function updateProjects(){
	var url = '/admin/admin_ajax_modules.php';
	new Ajax.Request(url,{
		   parameters: "mact=getProjects",
		   onSuccess: function(transport, json){
			  var data = transport.responseText.evalJSON(true);
			  if(data['error'] == 0){
				  $('modp_plist').innerHTML = data['message'];
			  }else{
				  $('formStat').innerHTML = data['message'];
			  }
		  },
		  method:'post'
	});
	}
	function removeAsset(id,pid){
		var url = '/admin/admin_ajax_modules.php';
		new Ajax.Request(url,{
		   parameters: "mact=deleteAsset&aID="+id,
		   onSuccess: function(transport, json){
			  var data = transport.responseText.evalJSON(true);
			  if(data['error'] == 0){
				  setTimeout("updateAssetList("+pid+")",1000);
				  
			  }else{
				  alert(data['message']);
			  }
		  },
		  method:'post'
		});
	}
	function updateAssetList(id){
		var url = '/admin/admin_ajax_modules.php';
		new Ajax.Request(url,{
		   parameters: "mact=getAssets&id="+id,
		   onSuccess: function(transport, json){
			  var data = transport.responseText.evalJSON(true);
			  if(data['message'] != ""){
				  $('assetList').innerHTML = data['message'];
			  }else{
				  alert("Could not get assets!");
			  }
		  },
		  method:'post'
	});
	}
</script>
<div id='modp_plist'>$projList</div>
$form
 <!-- <?php -->
END;
	}
    return $out;
}

	public function projSave(){
    	global $db;
		global $dp;
        $error = 0;
        $modID = parent::getModuleID();
        $projTitle = $_REQUEST['mod_projTitle'];
        $projType = $_REQUEST['mod_projType'];
        $projDesc = $dp->makeClean($_REQUEST['mod_projDesc']);
		$projBlurb = $dp->makeClean($_REQUEST['mod_projBlurb']);
		$spotlight = $_REQUEST['mod_spotlight'];
		$bNew = false;
		if(isset($_REQUEST['pID'])){
			if($spotlight == 1){
				$sSql = "UPDATE moduleSettings SET settingVal = {$_REQUEST['pID']} WHERE settingName = 'spotlight' AND moduleID = {$this->getModuleID()}";
				$db->query($sSql);
			}
			//Edit mode
			$sSql = "UPDATE repository_{$modID} SET projectTitle = \"$projTitle\",projectType = \"$projType\",projectDesc = \"$projDesc\", projectBlurb = \"$projBlurb\" WHERE pID = {$_REQUEST['pID']}";
		}else{
			$bNew = true;
			//Create Mode
			$sSql = "INSERT INTO repository_{$modID} (projectTitle,projectType,projectDesc,projectBlurb) VALUES (\"$projTitle\",\"$projType\",\"$projDesc\",\"$projBlurb\")";
		}
        $db->exec($sSql);
        $arrError = $db->errorInfo();
        if($arrError[2] != ""){
            $error = 1;
            $message = $arrError[2];
        }else{
        	$message = "Project Saved!";
			$projID = $db->lastInsertId();
			if($projID != "" && $bNew == true){
				$sSql = "UPDATE moduleSettings SET settingVal = $projID WHERE settingName = 'spotlight' AND moduleID = {$this->getModuleID()}";
				$db->query($sSql);
			}
        }
        //Save Module Configuration
		$arr = array('error'=>$error,'message'=>$message,'sql'=>$sSql);
		echo json_encode($arr);
    }
	public function projEdit(){
		echo $this->getForm();
	}
    public function assetSave(){
		//Handle project asset uploads
		global $db;
		$error = 0;
		$arrSettings = parent::getModuleParams();
		foreach($arrSettings as $row){
			$key = $row['settingName'];
			$$key = $row['settingVal'];
		}
		include_once($_SERVER['DOCUMENT_ROOT']."/classes/imageCreate.class.php");
		$img = new imageCreate();
		if($img->error != ""){
			return false;
		}
		$filepath = $_SERVER['DOCUMENT_ROOT'] . "/media/mod{$this->getModuleID()}/";
		$vfilepath = "/media/mod{$this->getModuleID()}/";
		if(!is_dir($filepath)){
			exec("mkdir $filepath");
		}
		if(isset($_FILES['asset']['tmp_name'])){
			$img->name = "asset";
			$img->outname = "projasset";
			$img->id = $this->getNextID("assets_{$this->getModuleID()}");
			$img->maxpix = $fullsize;
			$img->maxthumb = $thumbsize;
			//$img->wmark = $_POST['caption'];
			$img->bThumb = true;
			$img->bCrop = true;
			//Set File path
			$img->storage = $filepath;
			//Set File Virtual Path
			$img->vstorage = $vfilepath;
			//Process Image
			if($img->imageGen()){
				$imgpath = $img->vpath;
				$thmbpath = $img->tpath;
			}
			if($imgpath != "" && $thmbpath != ""){
				$sSql = "INSERT INTO assets_{$this->getModuleID()} (pID, assetPath,assetThumb,AssetDesc) VALUES ({$_REQUEST['pID']},\"$imgpath\",\"$thmbpath\",\"{$_REQUEST['assetDesc']}\")";
				$db->exec($sSql);
        		$arrError = $db->errorInfo();
        		if($arrError[2] != ""){
            		$error = 1;
            		$message = $arrError[2];
        		}else{
        			$message = "Asset Saved!";
        		}
			}else{
				$error = 1;
				$message = "Image handling failed";
			}
		}
		if($error == 0){
			echo "
			<script type='text/javascript'>
			parent.updateAssetList({$_REQUEST['pID']});
			parent.document.getElementById('frmAssets').reset();
			</script>
			";
		}else{
			echo "
			<script type='text/javascript'>
			alert('$message');
			</script>
			";
		}
    }
    public function projDelete(){
    	global $db;
        $error = 0;
        $modID = parent::getModuleID();
    	$pID = $_REQUEST['id'];
        
        $sSql = "DELETE FROM repository_{$modID} WHERE pID = $pID";
        $db->exec($sSql);
        $arrError = $db->errorInfo();
        if($arrError[2] != ""){
            $error = 1;
            $message = "Error: " . $arrError[2];
        }else{
        	$message = "Project Deleted.";
        }
        //Save Module Configuration
		$arr = array('error'=>$error,'message'=>$message,'sql'=>$sSql);
		echo json_encode($arr);
    }
    public function getProjects(){
    	global $db;
        $error = 0;
        $modID = parent::getModuleID();
        //Get a list of Videos we have in the system
		$sSql = "SELECT pID,projectTitle,projectType from repository_{$modID} ORDER by pID ASC";
        $stm = $db->query($sSql);
        while($row = $stm->fetch(PDO::FETCH_ASSOC)){
            $projList .= "<div><table><tr><td>{$row['projectTitle']}</td><td>&nbsp;<a href='javascript:void(0)' onclick='deleteProj({$row['pID']})'>DELETE</a></td></tr></table></div>";
        }
        if($projList == ""){
            $projList = "<div>No Projects Found</div>";
        }
       
		$arr = array('error'=>$error,'message'=>$projList,'sql'=>$sSql);
		echo json_encode($arr);
    }
    
	public function display($type=null){
    	global $db,$dp;
		global $arrProjTypes;
        $modID = parent::getModuleID();
        //Need to get Project Type from module declaration
		//$type = $interface;
		//Get the available projects for this type
		$arrSettings = parent::getModuleParams();
		foreach($arrSettings as $row){
			$key = $row['settingName'];
			$$key = $row['settingVal'];
		}
		if($type == "spotlight"){
			$sSql = "SELECT a.pID,a.projectTitle,a.projectType,a.projectBlurb,b.assetThumb from repository_{$this->getModuleID()} a JOIN assets_{$this->getModuleID()} b ON a.pID = b.pID WHERE a.pID = $spotlight";
			$stm = $db->query($sSql);
			$row = $stm->fetch(PDO::FETCH_ASSOC);
			$moreLink = "/page/" . strtolower($this->arrProjTypes[$row['projectType']]);
			$proj = "<img class='spotlight' align='left' src='{$row['assetThumb']}' alt='Asset'><div id='spotlightcontent'><b>{$row['projectTitle']}</b><br/>" . $dp->makeDirty($row['projectBlurb']) . "<br/><div id='slMore'>&gt;&gt;<a href='$moreLink'>More Projects</a></div></div>";
			return $proj;
		}
		if($type != "ajax"){
			$sSql = "SELECT pID,projectTitle from repository_{$this->getModuleID()} WHERE projectType = '$type' ORDER BY sortOrder";
			$stm = $db->query($sSql);
			while($row = $stm->fetch(PDO::FETCH_ASSOC)){
				$projList .= "<a id='ptrig_{$row['pID']}' href='javascript:void(0)' onclick='getProject({$row['pID']})'>{$row['projectTitle']}</a><br/>";
			}
			//Get first project
			$sSql = "SELECT pID,projectTitle,projectDesc from repository_{$this->getModuleID()} ORDER BY sortOrder LIMIT 1";
			$stm = $db->query($sSql);
			$row = $stm->fetch(PDO::FETCH_ASSOC);
			$assets = $this->getAssets($row['pID']);
			$projectFrame = <<<EOF
<!-- ?> -->
			<script type='text/javascript'>
			function getProject(id){
				//Reset all triggers to unselected
				$('#project').html("<center><img style='border:none' src='/modules/assets/loading.gif' alt='Loading'/></center>");
				$('#projList').find("a").removeClass("selected");
				var elem = "#ptrig_"+id;
				$(elem).addClass("selected");
				var url = "/includes/moduleapi.php";
				var params = "id=$modID&pid="+id;
				$.getJSON(url,params,function(data){
					if(data.error == 0){
						$("#project").html(data.message);
						$('.asset').colorbox({transition:"fade"});
					}else{
						alert("Unable to load Project");
					}
				});
			}
			$(document).ready(function(){
				$('.asset').colorbox({transition:"fade"});
			});
			</script>
	<div id='projList'>$projList</div>
<!-- <?php -->
EOF;
		}else{
        	$sSql = "SELECT pID,projectTitle,projectDesc from repository_{$this->getModuleID()} WHERE pID = {$_REQUEST['pid']}";
			$stm = $db->query($sSql);
			$row = $stm->fetch(PDO::FETCH_ASSOC);
            $assets = $this->getAssets($_REQUEST['pid'],false);
            $projectFrame = "<h2>".$dp->makeDirty($row['projectTitle']) . "</h2><p>".$dp->makeDirty($row['projectDesc'])."</p><div id='projectAssets'>$assets</div>";
        }
        return $projectFrame;
	}
    public function update(){
    	return $this->display("ajax");
    }
    private function returnProject($id){
    	global $db;
        $sSql = "SELECT pID,projTitle,projDesc from repository_{$this->getModuleID()} WHERE pID = $id";
        $stm = $db->query($sSql);
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        //Get Assets for this project
        $sSql = "SELECT assetID,assetThumb,assetPath from assets_{$this->getModuleID()} WHERE pID = $id";
        $stm = $db->query($sSql);
        while($arow = $stm->fetch(PDO::FETCH_ASSOC)){
        	$assets = "<div class='assetThumb'><img src='{$arow['thumbnail']}' alt='Project Image' /></div>";
        }
        $project = <<<EOF
<h1>{$row['projTitle']}</h1>
{$row['projDesc']}
<div id='projAssets'>$assets</div>
EOF;
        return $project;
    }
    ///////// This function will load the to create/edit a project
    private function getForm(){
		global $db,$dp;
        //Load Config
        $arrSettings = parent::getModuleParams();
		foreach($arrSettings as $row){
			$key = $row['settingName'];
			$$key = $dp->makeDirty($row['settingVal']);
		}
		if(isset($_REQUEST['id'])){
			//We're in edit mode
			$sSql = "SELECT pID,projectTitle,projectType,projectDesc,projectBlurb from repository_{$this->getModuleID()} WHERE pID = {$_REQUEST['id']}";
			$stm = $db->query($sSql);
			$row = $stm->fetch(PDO::FETCH_ASSOC);
			$formTitle = "Editing Project: <span style='color:#ffffff'>{$row['projectTitle']}</span>";
			foreach($row as $key => $val){
				$$key = $val;
			}
			$edtKey = "<input type='hidden' name='pID' value='{$_REQUEST['id']}'/>";
			$assets = $this->getAssets($_REQUEST['id']);
            if($row['pID'] == $spotlight){
            	$sflag = " checked='checked'";
            }else{
            	$sflag = "";
            }
			$aForm = <<<EOF
<!-- ?> -->
<form id='frmAssets' enctype="multipart/form-data" method="post" target="auploader">
<fieldset>
<legend>Add an Asset</legend>
<div>
<label>File:</label>
<input type='file' name='asset' />
</div>
<div>
<label>Caption:</label>
<input type type='text' name='assetDesc' maxlength="150" />
</div>
<div>
<input type='submit' value="Add Asset" />
<input type='hidden' name='mact' value='addasset' /> 
<input type='hidden' name='pID' value='{$_REQUEST['id']}' />
</div>
</fieldset>
</form>
<iframe name='auploader' height="100" width="300" style='display:none' src="about:blank"></iframe>
<!-- <?php -->
EOF;
		}else{
			$edtKey = "";
			$formTitle = "Creating New Project";
			$sflag = "";
		}
		//Get the Project Type Options
		$typeOpts = "<option></option>";
		foreach($this->arrProjTypes as $key => $val){
			if($key == $projectType){
				$typeOpts .= "<option value='$key' selected='selected'>$val</option>";
			}else{
				$typeOpts .= "<option value='$key'>$val</option>";
			}
		}
		
    	$out = <<<EOF
<!-- ?> -->
<script type='text/javascript'>
function checkCount(l){
	var taVal = $('taBlurb').value;
	var cLen = taVal.length;
	if(cLen > l){
		$('countNotice').innerHTML = "<span class='error'>"+l+ " limit exceeded</span>";
	}else{
		rem = l - cLen;
		$('countNotice').innerHTML = "<span class='message'>"+rem+ " characters remaining</span>";
	}
}
</script>
<form id='projAdmin'>
	<fieldset>
    <legend>$formTitle</legend>
    <div id='formStat'></div>
    <div>
		<label>Project Title</label>
		<input type='text' name='mod_projTitle' value='$projectTitle' />
	</div>
    <div>
		<label>Project Type</label>
		<select name='mod_projType'>$typeOpts</select>
	</div>
    <div>
		<label>Project Blurb for Spotlight</label><div id='countNotice'></div>
		<textarea class='mceNoEditor' id='taBlurb' name='mod_projBlurb' cols='50' rows='10' onkeyup='checkCount(200)'>$projectBlurb</textarea>
	</div>
    <div>
		<label>Project Description</label>
		<textarea id='taDescript' name='mod_projDesc' cols='50' rows='10'>$projectDesc</textarea>
	</div>
    <div><input type='checkbox' name='mod_spotlight' value='1' $sflag/> Project Spotlight</div>
    <div>
    	<input type='button' id='saveProjButton' class='form_button' value='Save Project' onclick='saveProj();return false;'/>
    </div>
    <input type='hidden' name='mact' value='projSave'/>
    $edtKey
    </fieldset>
 </form>
 <div id='assetList' style='margin-top: 10px;width:600px;overflow:auto'>$assets</div>
 $aForm
<!-- <?php -->
EOF;
		return $out;
    }
	public function getAssets($pid=null,$admin=null){
		global $db;
		$arrSettings = parent::getModuleParams();
		foreach($arrSettings as $row){
			$key = $row['settingName'];
			$$key = $row['settingVal'];
		}
		//Load Project Assets
		if($pid > 0){
			$sSql = "SELECT assetID,assetPath,assetThumb,assetDesc from assets_{$this->getModuleID()} WHERE pID = $pid";
		}else{
			$sSql = "SELECT assetID,assetPath,assetThumb,assetDesc from assets_{$this->getModuleID()} WHERE pID = {$_REQUEST['id']}";
		}
		$stm = $db->query($sSql);
		if($stm){
			if($stm->rowCount() > 0){
				$assets = "";
				while($row = $stm->fetch(PDO::FETCH_ASSOC)){
					if($admin == true){
						$assets .= "<div style='display:inline-block;margin-right:5px;width:{$thumbsize}px'><a href='{$row['assetPath']}' title='{$row['assetDesc']}' rel='lightbox'><img src='{$row['assetThumb']}'/></a><br/><center><a href='javascript:void(0)' onclick='removeAsset({$row['assetID']},{$_REQUEST['id']})'>DELETE</a></center></div>";
					}else{
						if($admin !== false){
							$admAct = "<br/><center><a href='javascript:void(0)' onclick='removeAsset({$row['assetID']},{$_REQUEST['id']})'>DELETE</a></center>";
						}else{
							$admAct = "";
						}
						$assets .= "<div style='display:inline-block;margin-right:5px;width:{$thumbsize}px'><a href='{$row['assetPath']}' rel='lightbox' class='asset' title='{$row['assetDesc']}'><img src='{$row['assetThumb']}'/></a>$admAct</div>";
					}
				}
			}else{
				if($admin == true){
					$assets = "No Assets for this project";
				}else{
					$assets = "";
				}
			}
		}else{
			if($admin == true){
				$assets = "No Assets for this project";
			}else{
				$assets = "";
			}
		}
		if($pid == null){
			$arr = array("message"=>$assets);
			echo json_encode($arr);
		}else{
			return $assets;
		}
	}
	public function deleteAsset(){
		global $db;
		$error = 0;
		$sSql = "SELECT assetThumb,assetPath from assets_{$this->getModuleID()} WHERE assetID = {$_REQUEST['aID']}";
		$stm = $db->query($sSql);
		$row = $stm->fetch(PDO::FETCH_ASSOC);
		$th = $row['assetThumb'];
		$fs = $row['assetPath'];
		$cmd1 = "rm {$_SERVER['DOCUMENT_ROOT']}$th";
		exec($cmd1,$out,$ret);
		if($ret == 0){
			$cmd2 = "rm {$_SERVER['DOCUMENT_ROOT']}$fs";
			exec($cmd2,$out,$ret);
			if($ret == 0){
				$db->query("DELETE FROM assets_{$this->getModuleID()} WHERE assetID = {$_REQUEST['aID']}");
				if($arrError[2] != ""){
            		$error = 1;
            		$message = $arrError[2];
        		}else{
        			$message = "Asset Removed!";
        		}
			}else{
				$error = 1;
				$message = "Could not DELETE image!";
			}
		}else{
			$error = 1;
			$message = "Could not DELETE thumbnail!";
		}
		$arr = array("error"=>$error,"message"=>$message);
		echo json_encode($arr);
	}
	private function getNextID($t){
		global $db;
		global $dbname;
		$sSql = "SELECT auto_increment as aID from information_schema.TABLES where TABLE_NAME ='$t' and TABLE_SCHEMA='$dbname'";
		$stm = $db->query($sSql);
		$row = $stm->fetch(PDO::FETCH_ASSOC);
		$id = $row['aID'];
		return $id;
	}
	public function install(){
		//Install This module
		global $db;
		$moduleID = parent::instanceCreate(__FILE__);
		if($moduleID !== false){
			//Insert settings
			$sSql = "INSERT INTO moduleSettings (moduleID,settingName,settingVal) VALUES ($moduleID,'thumbsize','75')";
			$db->exec($sSql);
        	$sSql = "INSERT INTO moduleSettings (moduleID,settingName,settingVal) VALUES ($moduleID,'fullsize','600')";
			$db->exec($sSql);
			$sSql = "INSERT INTO moduleSettings (moduleID,settingName,settingVal) VALUES ($moduleID,'spotlight','')";
			$db->exec($sSql);
			//Create Repository - SAMPLE
			$sSql = "CREATE TABLE repository_$moduleID (pID int auto_increment not null primary key,projectTitle varchar(150) not null, projectDesc text not null,projectBlurb varchar(200),projectType varchar(25) not null,sortOrder int default 0)";
			$db->exec($sSql);
            $dbErr = $db->errorInfo();
			if($dbErr[2] != ""){
				//Install failed - Uninstall
                parent::setError("Installation Failed: {$dbErr[2]}");
				//$this->uninstall($moduleID);
				return false;
			}else{
            	$sSql = "CREATE TABLE assets_$moduleID (assetID int auto_increment not null primary key,pID int not null, assetThumb varchar(150), assetPath varchar(150) not null,assetDesc varchar(255))";
				$db->exec($sSql);
            	$dbErr = $db->errorInfo();
                if($dbErr[2] != ""){
					//Install failed - Uninstall
                	parent::setError("Installation Failed: {$dbErr[2]}");
					$this->uninstall($moduleID);
					return false;
                }else{
					return true;
                }
			}
		}else{
			return false;
		}
	}
	public function uninstall($moduleID = null){
		global $db;
		//Uninstall this module (need the moduleID so we know which settings to remove
		if($moduleID == ""){
			$moduleID = $_REQUEST['id'];
		}
		//if module ID still equals nothing just exit
		if($moduleID == ""){
			parent::setError("Unable to Remove Instance");
			return false;
		}else{
			//Drop repository
			$sSql = "DROP TABLE repository_$moduleID";
			$db->exec($sSql);
            //Drop asset table
			$sSql = "DROP TABLE assets_$moduleID";
			$db->exec($sSql);
			//Delete settings
			$sSql = "DELETE from moduleSettings WHERE moduleID = $moduleID";
			$db->exec($sSql);
			$err = $db->errorInfo();
			if($err[2] != ""){
				$error = 1;
				parent::setError("An error occurred while trying to remove the instance records from moduleSettings. ({$err[2]})<br/>");
				return false;
			}else{
				//Delete module instance
				$sSql = "DELETE from modules WHERE moduleID = $moduleID";
				$db->exec($sSql);
				$err = $db->errorInfo();
				if($err[2] != ""){
					parent::setError("An error occurred while trying to remove the instance record from modules. ({$err[2]})");
                    return false;
				}else{
                	return true;
                }
			}
		}
	}
	public function upgrade(){
    	global $db;
		//Version 1.0 won't have anything here
        /*
        if(parent::getInstalledVersion() < 1.1){
        	$modID = parent::getModuleID();
        	$sSql = "ALTER TABLE repository_$modID ADD COLUMN (quoteSource varchar(150) default 'anonymous' not null)";
            $db->exec($sSql);
            $dbErr = $db->errorInfo();
            if($dbErr[2] == ""){
            	parent::updateVersion(1.1);
                return true;
            }else{
            	//rollback
                parent::setError("Unable to UPDATE repository! {$dbErr[2]}");
                return false;
            }
        }
        */
	}
}