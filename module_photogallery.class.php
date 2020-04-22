<?php
//Daedalus CMS Version
class module_photogallery extends module	{
	const version = '1.0';
	const moduleDesc = "Photo Gallery Module";
	private $inputvars;

	function __construct($act = "display")	{
		if($act == "install"){
			//Run Installer function
			$this->install();
		}elseif($act == "uninstall"){
			//Run Uninstaller function
			$this->uninstall();
		}elseif($act == "raw"){
			//Handle AJAX Request
			$this->display("raw");
		}else{
			return true;
		}
	}
	public function admin(){
		global $db;
		$sModule = $_REQUEST['module'];
		$mid = parent::getModuleID();
			$arrSettings = parent::getModuleParams();
			$arrAppSettings = parent::getAppConfig();
			foreach($arrSettings as $row){
				$key = $row['settingName'];
				$$key = $row['settingVal'];
			}
			if($thumbMax == "" || $previewMax == ""){
				$configNotice = "<p><span style='color: #cc0000'>You MUST configure the module prior to submitting a photo!</span></p>";
			}else{
				$configNotice = "";
			}
			if($pagealbums == 1){
				$pal_flag = " checked='checked'";
			}else{
				$pal_flag = "";
			}
			if($showcaption == 1){
				$cap_flag = " checked = 'checked'";
			}else{
				$cap_flag = "";
			}
			if($showwidgetcaption == 1){
				$wcap_flag = " checked = 'checked'";
			}else{
				$wcap_flag = "";
			}
			if($unsafenotice == 1){
				$usnflag = " checked = 'checked'";
			}else{
				$usnflag = "";
			}
			
			//Get a list of the projects stored in the system
			if(isset($_REQUEST['aid']) && $_REQUEST['aid'] != ""){
				$sSql = "SELECT elementID,thumbPath from repository_" . parent::getModuleID() . " WHERE albumID = {$_REQUEST['aid']}";
				$rs = $db->prepare($sSql);
				$rs->execute();
				$projList = "";
				while($row = $rs->fetch(PDO::FETCH_ASSOC)){
					$photoList .= "<img src='' alt='Thumb' <a href='javascript:void(0)' onclick='deletePhoto({$row['elementID']},{$_REQUEST['aid']});'>DELETE</a><br/>";
				}
				if($projList == ""){
					$photoList = "No photos Found";
				}
			}else{
				$photoList = "";
			}
			//Set the name of this class
			$classfile = basename(strtolower(__FILE__));
			parent::setModuleClass($classfile);
			//Position Options
			$arrPosition= array("top","bottom");
			$tpOpts = "<option></option>";
			foreach($arrPosition as $val){
				if($val == $thumbnailPos){
					$tpOpts .= "<option value='$val' selected='selected'>$val</option>";
				}else{
					$tpOpts .= "<option value='$val'>$val</option>";
				}
			}
			//We're showing the main screen
			$albumForm = $this->albumForm();
			$photoForm = $this->photoForm();
			$out = <<<END
<!-- ?> -->
<script type='text/javascript'>
function saveConfig(){
	$('btnSave').disabled = true;
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
function deletePhoto(id,aid){
	var params = "pid=" + id + "&aid="+aid+"&mact=delPhoto";
	var url = '/admin/admin_ajax_modules.php';
	new Ajax.Request(url,{
		   parameters: params,
		   onSuccess: function(transport, json){
			  var data = transport.responseText.evalJSON(true);
			  if(data['error'] == 0){
				  $('photoList').innerHTML = data['message'];
				  $('btnSave').disabled = false;
			  }else{
				  $('formStat').innerHTML = data['message'];
				  $('btnSave').disabled = false;
			  }
		  },
		  method:'post'
	});
}
function createAlbum(){
	var formObj = $('frmAlbum');
	var url = '/admin/admin_ajax_modules.php';
	new Ajax.Request(url,{
		   parameters: formObj.serialize(),
		   onSuccess: function(transport, json){
			  var data = transport.responseText.evalJSON(true);
			  if(data['error'] == 0){
				  $('albumList').innerHTML = data['message'];
				  $('btnSave').disabled = false;
			  }else{
				  $('formStat').innerHTML = data['message'];
				  $('btnSave').disabled = false;
			  }
		  },
		  method:'post'
	});
}
function getAlbum(){
	Sortable.destroy('photoList');
	var elem = $('selAlbum');
	var aid = elem.options[elem.selectedIndex].value;
	var url = '/admin/admin_ajax_modules.php';
	new Ajax.Request(url,{
		   parameters: "mact=photoList&aid="+aid+"&admin=1",
		   onSuccess: function(transport, json){
			  var data = transport.responseText.evalJSON(true);
			  if(data['error'] == 0){
				  $('photoList').innerHTML = data['message'];
				  setTimeout("startModSortable()",1000);
			  }else{
				  alert(data['message']);
			  }
		  },
		  method:'post'
	});
}
function deleteAlbum(aid){
	var msg = "Are you sure you want to delete this album an all it's contents?";
	if(confirm(msg)){
		var url = '/admin/admin_ajax_modules.php';
		new Ajax.Request(url,{
			   parameters: "mact=delAlbum&aid="+aid,
			   onSuccess: function(transport, json){
				  var data = transport.responseText.evalJSON(true);
				  if(data['error'] == 0){
					  $('albumList').innerHTML = data['message'];
				  }else{
					  alert(data['message']);
				  }
			  },
			  method:'post'
		});
	}
}
function albumDefault(aid){
	var url = '/admin/admin_ajax_modules.php';
		new Ajax.Request(url,{
			   parameters: "mact=defaultAlbum&aid="+aid,
			   onSuccess: function(transport, json){
				  var data = transport.responseText.evalJSON(true);
				  if(data['error'] == 0){
					  $('albumList').innerHTML = data['message'];
				  }else{
					  alert(data['message']);
				  }
			  },
			  method:'post'
		});
}
function startModSortable(){
	Sortable.create('photoList',{
		tag: 'div',
		handle: 'img.handle',
		constraint: false,
		onUpdate: function(){
			new Ajax.Updater('results','/admin/admin_ajax_modules.php',{postBody: Sortable.serialize('photoList')+"&mid={$mid}&mact=updateorder"});
		}
	});
}
</script>
$configNotice
<div id='photoList'>
$photoList
</div>
<div id='photoForm'>
$photoForm
</div>
<div id='albumForm'>
$albumForm
</div>
<iframe name='uploader' src="about:blank" style='display:none' height='100' width='300'></iframe>
<br/>
<a href='javascript:void(0)' onclick="toggleDiv('configForm')">Module Configuration</a><br/>
<div id='configForm' style='display:none'>
<form id='moduleAdmin'>
	<fieldset>
    	<legend>Configuration</legend>
 	<div>
		<label><b>Thumbnails to show</b></label><br/>
		<input type='text' name='mod_thumbCount' size='4' value='$thumbCount' />
	</div>
    <div>
		<label><b>Expanded View</b></label><br/>
		Max Dimension <input type='text' name='mod_expandMax' size='5' value='$expandMax' />
	</div>
    <div>
		<label><b>Preview</b></label><br/>
		Max Dimension <input type='text' name='mod_previewMax' size='5' value='$previewMax' />
	</div>
    <div>
		<label><b>Thumbnails</b></label><br/>
		Max Dimension <input type='text' name='mod_thumbMax' size='5' value='$thumbMax' />
	</div>
    <div>
		<label><b>Thumbnail Position</b></label> <select name='mod_thumbnailPos' >$tpOpts</select>
	</div>
    <div>
    	<input type='checkbox' name='mod_pagealbums' value='1' $pal_flag/><label><b>Use pages to select albums</b></label><br/>
    </div>
    <div>
    	<input type='checkbox' name='mod_showcaption' value='1' $cap_flag/><label><b>Show caption</b></label><br/>
    </div>
    <div>
        <input type='checkbox' name='mod_showwidgetcaption' value='1' $wcap_flag/><label><b>Show widget caption</b></label>
    </div>
    <div>
    	<label>Notice Text</label><br/><input type='text' id='txtNotice' name='mod_usnotice' value='$usnotice' size='50'/>
	</div>
	<div>
		<input type='button' id='btnSave' value='Save' onclick='saveConfig();return false;'/><div id='formStat'></div>
	</div>
	<input type='hidden' name='mact' value='configSave'/>
    </fieldset>
</form>
</div>
<!-- <? -->
END;
		return $out;
	}
	public function configSave(){
		global $db;
		$error = 0;
		$scount = 0;
		$fcount = 0;
		//If they don't want lightbox, set it to zero
		if(!isset($_REQUEST['mod_showcaption'])){$_REQUEST['mod_showcaption'] = "0";}
		if(!isset($_REQUEST['mod_showwidgetcaption'])){$_REQUEST['mod_showwidgetcaption'] = "0";}else{$_REQUEST['mod_showwidgetcaption'] = "1";}
		if(!isset($_REQUEST['mod_unsafenotice'])){$_REQUEST['mod_unsafenotice'] = "0";}else{$_REQUEST['mod_unsafenotice'] = "1";}
		foreach($_REQUEST as $key => $val){
			if(strpos($key,"mod_") !== false){
				$sSql = "UPDATE moduleSettings set settingVal = '$val' WHERE settingName = '" . str_replace("mod_","",$key) ."' AND moduleID = " . parent::getModuleID();
				$db->exec($sSql);
				$arrError = $db->errorInfo();
				if($arrError[2] == ""){
					$scount++;
				}else{
					$error = 1;
					$strError = $arrError[2];
					$fcount++;
				}
			}
		}
		$message = "$scount Settings applied. $fcount settings failed.";
		//Save Module Configuration
		$arr = array('error'=>$error,'message'=>$message,'sql'=>$sSql);
		echo json_encode($arr);
	}
	private function photoForm($eid=null){
		global $db;
		global $dp;
		$arrSettings = parent::getModuleParams();
		foreach($arrSettings as $row){
			$key = $row['settingName'];
			$$key = $row['settingVal'];
		}
		if($eid != null){
			$stm = $db->query("SELECT * from repository_$mid WHERE elementID = $eid");
			$row = $stm->fetch(PDO::FETCH_ASSOC);
			foreach($row as $key => $val){
				$$key = $dp->makeDirty($val);
			}
			$buttonLabel = "Edit";
			$editKey = "<input type='hidden' name='elementID' value='$eid'/>";
			if($fsPath != ""){
				$fsPreview = "<a href='{$row['fsPath']}' rel='lightbox'>Preview</a>";
			}
			if($photoPath != ""){
				$photoPreview = "<a href='{$row['photoPath']}' rel='lightbox'>Preview</a>";
			}
		}else{
			$buttonLabel = "Save";
			$editKey = "";
		}
		$albumOpts = $this->albumList(true);
		if($lightbox == 1){
			$fsField = "<div><b>Fullsize Photo</b> $fsPreview<br/><input type='file' name='photo' /></div>";
		}else{
			$fsField = "";
		}
		$out = <<<EOF
<!-- ?> -->
<form id='frmPhoto' action='admin_ajax_modules.php?' enctype="multipart/form-data" method="post" target='uploader'>
	<fieldset>
    <legend>Manage Photos</legend>
	<div>
    	<b>Album</b>
        <select id='selAlbum' name='albumID' onchange='getAlbum();'>$albumOpts</select>
    </div>
	$fsField
    <div>
		<b>Initial View (thumbnail generated from this)</b> $photoPreview<br/>
		<input type='file' id='fileThumbnail' name='thumbnail' />
	</div>
    <div>
    	<label>Caption</label>
		<input type='text' id='txtCaption' name='caption' />
	</div>
    <div>
    	<label><input type='checkbox' id='chkExclude' name='exclude' value='1'/> Exclude from widget</label>
	</div>
	<div>
		<input type='submit' class='form_button' id='btnSave' value='$buttonLabel'/>
	</div>
	<input type='hidden' name='mact' value='photoSave'/>
    <input type='hidden' name='modAdminUpdate' value='$sModule'/>
    $editKey
    </fieldset>
</form>
<!-- <? -->
EOF;
		return $out;
	}
	private function albumForm(){
		global $db;
		$mid = parent::getModuleID();
		$albums = $this->albumList();
		$out = <<<EOF
<form id='frmAlbum'>
	<fieldset>
		<legend>Manage Albums</legend>
		<div id='albumList'>
			$albums
		</div>
		<div><label>Album Name</label><input type='text' name='albumName'/></div>
		<div><label><input type='checkbox' name='albumNotice' value='1'/>Notify on View?</label>
		<div><input type='button' value='Create Album' onclick='createAlbum();return false;'/></div>
		<input type='hidden' name='mact' value='addAlbum'/>
	</fieldset>
</form>
EOF;
		return $out;
	}
	private function albumList($adm=false){
		global $db;
		$mid = parent::getModuleID();
		//Get a list of Albums
		$sSql = "SELECT albumID,albumName,albumDefault from albums_$mid";
		$stm = $db->query($sSql);
		if($stm && $stm->rowCount() > 1){
			$bDel = true;
		}else{
			$bDel = false;
		}
		if($adm === true){
			$albums = "<option></option>";
		}
		while($row = $stm->fetch(PDO::FETCH_ASSOC)){
			if($adm == false){
				$albums .= "{$row['albumName']} ";
				if($row['albumDefault'] == 1){$albums .= " (Default)";}else{$albums .= "<a href='javascript:void(0)' onclick='albumDefault({$row['albumID']})'>Make Default</a>";}
				if($bDel === true){$albums .= " <a href='javascript:void(0)' onclick='deleteAlbum({$row['albumID']});'>DELETE</a><br/>";}
			}else{
				$albums .= "<option value='{$row['albumID']}'>{$row['albumName']}</option>";
			}
		}
		return $albums;
	}
	public function photoList($aid=null){
		global $db;
		if($aid == null){
			$ajid = $_REQUEST['aid'];
		}else{
			$ajid = $aid;
		}
		//Get a list of the projects stored in the system
        $sSql = "SELECT elementID,photoPath,thumbPath from repository_" . parent::getModuleID() . " WHERE albumID = $ajid ORDER BY displayOrder";
        $rs = $db->prepare($sSql);
        $rs->execute();
        $photoList = "";
        while($row = $rs->fetch(PDO::FETCH_ASSOC)){
			if(isset($_REQUEST['admin']) && $_REQUEST['admin'] == 1){
            	$photoList .= "<div id='image_{$row['elementID']}'style='display:inline-block;margin-right:5px;margin-bottom:5px;'><a href='{$row['photoPath']}' rel='lightbox'><img class='thumbnail' src='{$row['thumbPath']}' width='75' height='75'/></a><br/><center><img src='/admin/template/images/reorder.png' class='handle'/><a href='javascript:void(0)' onclick='deletePhoto({$row['elementID']},$ajid);'>X</a></center></div>";
			}else{
				$photoList .= "<div class='thumbnail' style='display:inline-block'><a href='{$row['photoPath']}' rel='lightbox'><img class='thumbnail' src='{$row['thumbPath']}'/></a></div>";
			}
        }
		if($photoList == ""){
			$photoList = "No Photos Found";
		}
		if($aid == null){
			$arr = array("error"=>0,"message"=>$photoList);
			echo json_encode($arr);
		}else{
			return $photoList;
		}
	}
	public function photoSave(){
		global $db;
		global $dp;
		$mid = parent::getModuleID();
		if(isset($_REQUEST['elementID'])){
			$mode = "edit";
			$stm = $db->query("SELECT * from repository_$mid WHERE elementID = {$_REQUEST['elementID']}");
			$erow = $stm->fetch(PDO::FETCH_ASSOC); 
		}else{
			$mode = "add";
		}
		include_once(rtrim($_SERVER['DOCUMENT_ROOT'],"/")."/classes/imageCreate.class.php");
        $appConfig = parent::getAppConfig();
		$arrSettings = parent::getModuleParams();
		foreach($arrSettings as $fields){
			$field = $fields['settingName'];
			$$field = $fields['settingVal'];
		}
		$caption = $_REQUEST['caption'];
		if(isset($_REQUEST['exclude']) && $row['exclude'] == 1){
			$exclude = 1;
		}else{
			$exclude = 0;
		}
		$error = 0;
        //Get the next file ID from the repository
		if($mode == "add"){
        	$stm = $db->query("SELECT Auto_increment FROM information_schema.tables WHERE table_name='repository_" . parent::getModuleID() . "';");
        	$row = $stm->fetch(PDO::FETCH_ASSOC);
        	$fid = $row['Auto_increment'];
		}else{
			$fid = $_REQUEST['elementID'];
		}
        if($_FILES['photo']['tmp_name'] != ""){
			if($mod == "edit"){
				$cmd = "rm {$_SERVER['DOCUMENT_ROOT']}{$erow['fsPath']}";
				exec($cmd);
			}
			$ext = substr(strrchr($_FILES['photo']['name'],'.'),1);
			$fullsize = rtrim($_SERVER['DOCUMENT_ROOT'],"/") . "/media/gallery-{$fid}-fs." .  $ext;
			if(move_uploaded_file($_FILES['photo']['tmp_name'],$fullsize)){
				$vfullsize = "/media/gallery-{$fid}-fs." .  $ext;
			}else{
				$vfullsize;
			}
		}
		if($_FILES['thumbnail']['tmp_name'] != ""){
			if($mode == "edit"){
				$cmd1 = "rm {$_SERVER['DOCUMENT_ROOT']}{$erow['photoPath']}";
				$cmd2 = "rm {$_SERVER['DOCUMENT_ROOT']}{$erow['thumbPath']}";
				exec($cmd1);
				exec($cmd2);
			}
			$filepath = rtrim($_SERVER['DOCUMENT_ROOT'],"/") . "/media/";
			$vfilepath = "/media/";

			$img = New imageCreate;
			if($img->error){
				$error = 1;
				echo $img->error;
			}
			$img->bThumb = true; //Create Thumbnail
			$img->name = "thumbnail";
			$img->outname = "gallery";
			$img->id = $fid;
			$img->maxpix = $previewMax;
			$img->bCrop = true;
			$img->maxthumb = $thumbMax;
			//Set File path
			$img->storage = $filepath;
			//Set File Virtual Path
			$img->vstorage = $vfilepath;
			//Process Image
			if($img->imageGen()){
				$imgpath = $img->vpath;
				$thmbpath = $img->tpath;
			}
			if($img->error != ""){
				echo $img->error;
			}
		}
		if($vfullsize != ""){$FSField = ",fsPath";$pFullSize = ",'$vfullsize'";}else{$FSField="";$pFullSize = "";}
		if($imgpath != ""){$IMGField = ",photoPath";$pImgPath = ",'$imgpath'";}else{$IMGField="";$pImagePath = "";}
		if($thmbpath != ""){$THMBField = ",thumbpath";$pThmbPath = ",'$thmbpath'";}else{$THMBField="";$pThmbPath = "";}
		$albumID = $_REQUEST['albumID'];
		
		$sSql = "INSERT INTO repository_" . parent::getModuleID() . " (albumID,caption,exclude$FSField$IMGField$THMBField) VALUES ($albumID,\"$caption\",$exclude$pFullSize$pImgPath$pThmbPath)";
		
		$db->exec($sSql);
		$dbErr = $db->errorInfo();
		if($dbErr[2] != ""){
			$error = 1;
			$message = "Unable to insert photo. ($sSql)";
		}
        //Update Parent window with outcome
        if($error == 1){
        	$out = <<<END
<!-- ?> -->
<script type='text/javascript'>
	parent.document.getElementById('formStat').innerHTML = "$message";
</script>
<!-- <? -->
END;
        }else{
        	$out = <<<END
<!-- ?> -->
<script type='text/javascript'>
	parent.getAlbum();
    parent.document.getElementById('formStat').innerHTML = "New Photo Added!";
    parent.document.getElementById('fileThumbnail').value = "";
	parent.document.getElementById('txtCaption').value = "";
</script>
<!-- <? -->
END;
        }
        echo $out;
	}
	public function addAlbum(){
		global $db;
		$error = 0;
		$modID = parent::getModuleID();
		$sSql = "INSERT INTO albums_$modID (albumName) VALUES (\"{$_REQUEST['albumName']}\");";
		if($db->query($sSql)){
			$out = $this->albumList();
		}else{
			$error = 1;
			parent::setError("Unable to Create Album");
			$out = "An error ocurred while trying to create the album!";
		}
		$arr = array("error"=>$error,"message"=>$out);
		echo json_encode($arr);
	}
	public function delAlbum(){
		global $db;
		$error = 0;
		$mid = parent::getModuleID();
		$aid = $_REQUEST['aid'];
		$sSql = "SELECT elementID from repository_$mid WHERE albumID = $aid";
		$stm = $db->query($sSql);
		if($stm){
			if($stm->rowCount() > 0){
				$iPath = rtrim($_SERVER['DOCUMENT_ROOT'],"/")."/media/pages/". PAGE;
				while($row = $stm->fetch(PDO::FETCH_ASSOC)){
					if(!$this->delPhoto($row['elementID'])){
						$error = 1;
					}
				}
			}
			if($error == 0){
				$sSql = "DELETE FROM albums_$mid WHERE albumID = $aid";
				if($db->query($sSql)){
					$out = $this->albumList();
				}else{
					parent::setError("Failed to DELETE Album");
					$out = "Failed to DELETE Album";
					$error = 1;
				}
			}else{
				$out = parent::getError();
			}
		}
		$arr = array('error'=>$error,'message'=>$out);
		echo json_encode($arr);
	}
	public function defaultAlbum(){
		global $db;
		$mid = parent::getModuleID();
		$aid = $_REQUEST['aid'];
		$db->query("UPDATE albums_$mid SET albumDefault = 0 WHERE 1");
		$db->query("UPDATE albums_$mid SET albumDefault = 1 WHERE albumID = $aid");
		$out = $this->albumList(true);
		$arr = array('error'=>0,'message'=>$out);
		echo json_encode($arr);
	}
	public function delPhoto($pid = null){
		global $db;
		$mid = parent::getModuleID();
		$bajax = 0;
		$error = 0;
		if(isset($_REQUEST['pid']) && $_REQUEST['pid'] != ""){
			$bajax = 1;
			$photoID = $_REQUEST['pid'];
			$albumID = $_REQUEST['aid'];
		}else{
			$photoID = $pid;
		}
		$sSql = "SELECT fsPath, photoPath, thumbPath from repository_$mid where elementID = $photoID";
		
		$stm = $db->query($sSql);
		if($stm){
			$iPath = rtrim($_SERVER['DOCUMENT_ROOT'],"/");
			$row = $stm->fetch(PDO::FETCH_ASSOC);
			if($row['fsPath'] != ""){
				//Remove Full Size Version
				$cmd1 = "rm $iPath" . $row['fsPath'];
				exec($cmd1,$out,$ret);
				if($ret !== 0){
					parent::setError("Failed to DELETE {$row['fsPath']}($cmd1)");
					$error = 1;
				}
			}
			if($row['photoPath'] != ""){
				//Remove Preview Version
				$cmd2 = "rm $iPath" . $row['photoPath'];
				exec($cmd2, $out, $ret);
				if($ret !== 0){
					parent::setError("Failed to DELETE {$row['photoPath']}($cmd2)");
					$error = 1;
				}
			}
			if($row['thumbPath'] != ""){
				//Remove Thumbnail Version
				$cmd3 = "rm $iPath" . $row['thumbPath'];
				exec($cmd3,$out,$ret);
				if($ret !== 0){
					parent::setError("Failed to DELETE {$row['thumbPath']}($cmd3)");
					$error = 1;
				}
			}
		}
		if($error == 0){
			$db->query("DELETE from repository_$mid WHERE elementID = $photoID");
			if($bajax == 1){
				$_REQUEST['admin'] = 1;
				$out = $this->photoList($albumID);
				//Return JSON response
				$arr = array('error'=>0,'message'=>$out);
				echo json_encode($arr);
			}else{
				return true;
			}
		}else{
			if($bajax == 1){
				//Return JSON response
				$arr = array('error'=>1,'message'=>parent::getError());
				echo json_encode($arr);
			}else{
				return false;
			}
		}
	}
	//############## Public Display Function ###############//
	public function display($type = null){
		global $db;
		global $dp;
        $appConfig = parent::getAppConfig();
        $arrSettings = parent::getModuleParams();
		foreach($arrSettings as $row){
			$key = $row['settingName'];
			$$key = $row['settingVal'];
		}
        $mid = parent::getModuleID();
        if($type == "widget")
		{
			$sql = "SELECT elementID,photoPath,caption,DATE_FORMAT(addDate,'%b %D %Y') as add_date from repository_$mid WHERE exclude <> 1 ORDER BY elementID DESC LIMIT 1";
			$stm = $db->query($sql);
			$row = $stm->fetch(PDO::FETCH_ASSOC);
			if($showwidgetcaption == 1){
				if($row['caption'] != "")
				{
					$caption = "<div id='widget_caption'>&quot;{$row['caption']}&quot; added {$row['add_date']}</div>";
				}
				else
				{
					$caption = "<div id='widget_caption'>Added {$row['add_date']}</div>";
				}
			}
			else
			{
				$caption = "";
			}
			$out = "<div id='gallery_widget'>$caption<img src='{$row['photoPath']}' alt='Latest Photo'/></div>";
		}
		else
		{
			if($usnotice != ""){
				$notice = "alert('$usnotice');";
			}else{
				$notice = "";
			}
			if($pagealbums != 1){
			$script = <<<EOF
<!-- <?php -->
var warned = 0;
function selectAlbum(a,n){
	if(n == 1 && warned == 0){
		warned = 1;
		$notice
	}
	$('#slides_'+a).slides({stop});
	$('#selector').find("div.albumOpt").removeClass("selected");
	var url = "/includes/moduleapi.php";
	$.post(url,"&id=$mid&ajax=1&aid="+a,function(data){
		if(data.error == 0){
			$('#viewer').html(data.message);
			$('#as_'+a).addClass("selected");
			startSlides("#slides_"+a);
		}else{
			$('#viewer').html("<span class='error'>"+data.message+"</span>");
		}
	},'json');
}
function startSlides(d){
    $(d).slides({
				preload: true,
				preloadImage: '/userfiles/loading.gif',
				effect: 'fade',
				crossfade: true,
				play: 5000,
				pause: 2500,
				hoverPause: true
		});
}
<!-- ?> -->
EOF;
		//Check to see how many albums we have. If more than one show the selector
		$sSql = "SELECT a.albumID,count(b.elementID) as photoCount,albumName,albumDefault,albumNotice FROM albums_$mid a LEFT JOIN repository_$mid b ON a.albumID = b.albumID GROUP BY a.albumID ORDER BY albumDefault DESC";
		$stm = $db->query($sSql);
		if($stm){
			while($row = $stm->fetch(PDO::FETCH_ASSOC)){
				if($row['photoCount'] > 0){
					if($row['albumDefault'] == 1){
						$selAlbum = $row['albumID'];
                        $selAlbumNotice = $row['albumNotice'];
                        if($selAlbumNotice == 1){$startnotice = $notice . "warned = 1;";}else{$startnotice = "";}
                        $albumSelector .= "<div id='as_{$row['albumID']}' class='albumOpt selected' onclick=\"selectAlbum({$row['albumID']},$selAlbumNotice)\">{$row['albumName']}</div>";
					}
                    else
                    {
                    	$selAlbumNotice = $row['albumNotice'];
						$albumSelector .= "<div id='as_{$row['albumID']}' class='albumOpt' onclick=\"selectAlbum({$row['albumID']},$selAlbumNotice)\">{$row['albumName']}</div>";
                    }
				}
			}
			
		}else{
			if($stm){
				$row = $stm->fetch(PDO::FETCH_ASSOC);
				$selAlbum = $row['albumID'];
			}else{
				$selAlbum = 1;
			}
			$albumSelector = "";
            $photos = $this->getAlbum($selAlbum,$selAlbumNotice);
            $albumSelect = <<<EOF
<div id='selector'>
$albumSelector
</div>
EOF;
			if($arrSettings['thumbnailPos'] == "top"){
				$out = "$albumSelect<div id='viewer'>$photos</div>";
			}else{
				$out = "<div id='viewer'>$photos</div>$albumSelect";
			}
		}
        }else{
        	//Albums are selected by page
            $selAlbum = $type; //The album we want to display
            $selAlbumNotice = ""; //Not using this feature here
            $sSql = "SELECT photoPath from repository_$mid WHERE albumID = (SELECT albumID from albums_$mid WHERE albumName = '$selAlbum') ORDER BY displayOrder";
        	$rs = $db->query($sSql);
        	$row = $rs->fetch(PDO::FETCH_ASSOC);
        	$photos = "<img src='{$row['photoPath']}' alt='Photo' />";
            $script = <<<EOF
<script type='text/javascript'>
var cursor = "{$row['photoPath']}";
function getImage(p){
	if(cursor != p){
    	cursor = p;
		$('#viewer').fadeOut('fast',function(){
        	$('#viewer').html("<img src='"+p+"' alt='Photo' />");
            $('#viewer').fadeIn();
        });
    	
    }else{
    	return false;
    }
}     
</script>       
EOF;
			parent::addToHeader($script);
            $thumbs = $this->getThumbnails($selAlbum);
            if($thumbnailPos == "top"){
				$out = "$script<div id='gallery'><div id='thumbnails'>$thumbs</div><div id='viewer'>$photos</div></div>";
			}else{
				$out = "$script<div id='gallery'><div id='viewer'>$photos</div><div id='thumbnails'>$thumbs</div></div>";
			}
        }
        }
        return $out;
	}
    /*
    public function delPhoto(){
    	global $db;
        $error = 0;
		$aid = $_REQUEST['aid'];
        $sSql = "SELECT photoPath,thumbPath from repository_" . parent::getModuleID() . " WHERE elementID = {$_REQUEST['elementID']}";
        $stm = $db->query($sSql);
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        $cmd1 = "rm {$_SERVER['DOCUMENT_ROOT']}{$row['photoPath']}";
		exec($cmd1);
		$cmd2 = "rm {$_SERVER['DOCUMENT_ROOT']}{$row['thumbPath']}";
        exec($cmd2);
        $db->exec("DELETE from repository_" . parent::getModuleID() . " WHERE elementID = {$_REQUEST['elementID']}");
        $dbErr = $db->errorInfo();
        if($dbErr[2] != ""){
        	$error = 1;
            $message = "Unable to remove photo from repository.";
        }else{
			$message = $this->photoList($aid);
        }
        //Return JSON response
		$arr = array('error'=>$error,'message'=>$message);
		echo json_encode($arr);
    }
	*/
    private function getThumbnails($g){
    	global $db;
    	$mid = parent::getModuleID();
    	$sSql = "SELECT photoPath,thumbPath from repository_$mid WHERE albumID = (SELECT albumID from albums_$mid WHERE albumName = '$g') ORDER BY displayOrder";
        $stm = $db->query($sSql);
       	while($row = $stm->fetch(PDO::FETCH_ASSOC))
        {
        	$thumbs .= "<div class='gallery_thumbnail'><a href='javascript:void(0)' onmouseover=\"getImage('{$row['photoPath']}')\" /><img src='{$row['thumbPath']}' alt='Image Thumbnail'/></a></div>";
        }
        return $thumbs;
    }
	//############ AJAX Album Request Handler
	public function getAlbum($a,$n=0){
		global $db;
		$appConfig = parent::getAppConfig();
        $arrSettings = parent::getModuleParams();
		foreach($arrSettings as $row){
			$key = $row['settingName'];
			$$key = $row['settingVal'];
		}
        $mid = parent::getModuleID();
		$aid = $_REQUEST['aid'];
		$sSql = "SELECT elementID,caption,fsPath,photoPath,thumbPath from repository_$mid WHERE albumID = $a ORDER BY displayOrder";
        $stm = $db->query($sSql);
		$count = 0;
		$tcount = 0;
		if($stm)
        {
			while($row = $stm->fetch(PDO::FETCH_ASSOC))
            {
            	if($showcaption == 1){
                	if($row['caption'] != ""){
                    	$caption = "<div class=\"caption\" style=\"bottom:0\"><p>{$row['caption']}</p></div>";
                    }else{
                    	$caption = "";
                    }
                }else{
                	$caption = "";
                }
				$photos .= "<div class='photo'><img src='{$row['photoPath']}' />$caption</div>\n";
            }
        }else
        {
        	$photos = "No Photos for this album";
        }
        if($arrSettings['thumbnailPos'] == "top"){
			$out = "<div id='slides_{$a}'><div class='slides_container'>" . $photos."</div></div>";
		}else{
			$out = "<div id='slides_{$a}'><div class='slides_container'>" . $photos."</div></div>";
		}
        return $out;
	}
	public function install(){
		//Install This module
		global $db;
		//parent::setPageID($_SESSION['pageid']);
		$moduleID = parent::instanceCreate(__FILE__);
		
		//Insert settings
		$sSql = "INSERT INTO moduleSettings (moduleID,settingName,settingVal) VALUES ($moduleID,'thumbCount','1')";
		$db->exec($sSql);
		$sSql = "INSERT INTO moduleSettings (moduleID,settingName,settingVal) VALUES ($moduleID,'expandMax','')";
		$db->exec($sSql);
        $sSql = "INSERT INTO moduleSettings (moduleID,settingName,settingVal) VALUES ($moduleID,'previewMax','')";
		$db->exec($sSql);
		$sSql = "INSERT INTO moduleSettings (moduleID,settingName,settingVal) VALUES ($moduleID,'thumbMax','')";
		$db->exec($sSql);
		$sSql = "INSERT INTO moduleSettings (moduleID,settingName,settingVal) VALUES ($moduleID,'thumbnailPos','bottom')";
		$db->exec($sSql);
		$sSql = "INSERT INTO moduleSettings (moduleID,settingName,settingVal) VALUES ($moduleID,'lightbox','0')";
		$db->exec($sSql);
        $sSql = "INSERT INTO moduleSettings (moduleID,settingName,settingVal) VALUES ($moduleID,'showcaption','0')";
		$db->exec($sSql);
        $sSql = "INSERT INTO moduleSettings (moduleID,settingName,settingVal) VALUES ($moduleID,'showwidgetcaption','0')";
		$db->exec($sSql);
        $sSql = "INSERT INTO moduleSettings (moduleID,settingName,settingVal) VALUES ($moduleID,'pagealbums','0')";
		$db->exec($sSql);
        $sSql = "INSERT INTO moduleSettings (moduleID,settingName,settingVal) VALUES ($moduleID,'usnotice','Some images may be unsafe for work!')";
		$db->exec($sSql);
		$sSql = "CREATE TABLE albums_$moduleID (albumID int auto_increment not null primary key, albumName varchar(75) not null,albumDefault int default 0,albumNotice tinyint default 0)";
		$db->exec($sSql);
		//Insert the Default Gallery
		$db->exec("INSERT INTO albums_$moduleID (albumName,albumDefault) VALUES ('General',1)");
		$sSql = "CREATE TABLE repository_$moduleID (elementID int auto_increment not null primary key,albumID int not null,caption varchar(150), fsPath varchar(75) not null,photoPath varchar(75) not null, thumbPath varchar(75) not null,addDate timestamp,exclude tinyint default 0,displayOrder int)";
		$db->exec($sSql);
        $err = $db->errorInfo();
		if($err[2] != ""){
        	traceSql($sSql,$err[2],__FILE__,__LINE__); //$tracesql,$traceerror,$file,$line
			//Install failed - Uninstall
			$this->uninstall($moduleID);
			parent::setError("Installation Failed: {$err[2]}");
		}else{
			//Create media folder
			//parent::addMediaSupport();
			return true;
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
			//Clear any files that belong to this module that are store in the repository
			$sSql = "SELECT photoPath,thumbPath from repository_$moduleID";
            $stm = $db->query($sSql);
			if($stm && $stm->rowCount() > 0){
            while($row = $stm->fetch(PDO::FETCH_ASSOC)){
				//Remove Photos
            	$photopath = $row['photoPath'];
                $cmd = "rm " . rtrim($_SERVER['DOCUMENT_ROOT'],"/") . $photopath;
                exec($cmd);
				//Remove Thumbnails
				$thumbpath = $row['thumbPath'];
                $cmd = "rm " . rtrim($_SERVER['DOCUMENT_ROOT'],"/") . $thumbpath;
                exec($cmd);
            }
			}
			//Drop repository
			$sSql = "DROP TABLE albums_$moduleID";
			$db->exec($sSql);
			$sSql = "DROP TABLE repository_$moduleID";
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
					$error = 1;
					$message .= "An error occurred while trying to remove the instance record from modules. ({$err[2]})<br/>";
				}
			}
		}
	}
	public function upgrade(){
		global $db;
		//Upgrade the module to this version (should return true if version is 1.0
	}
	public function version(){
		global $db;
		//get the moduleID
		$moduleID = $_REQUEST['id'];
		//Get the install date
		$sSql = "SELECT date_format(installDate,'%b %e, %Y') as installDate FROM modules WHERE moduleID = $moduleID";
		
		$out = "Module Name: " . module_text::moduleName;
		$out .= "Module Version: " . module_text::version;
		$out .= "Install Date: " . $installDate;
		
		return $out;
	}
	
	public function update(){
    	global $db;
		global $dp;
		//This function is the traffic director for all module update requests or handles it directly
        $mid = $_REQUEST['id'];
		$aid = $_REQUEST['aid'];
		$pid = $_REQUEST['pid'];
        $appConfig = parent::getAppConfig();
		$arrSettings = parent::getModuleParams();
		foreach($arrSettings as $row){
			$key = $row['settingName'];
			$$key = $row['settingVal'];
		}
		//Getting Album
		if($aid != ""){
			$update = $this->getAlbum($aid);
			return $update;
		}else{
		//Getting Photo
        $sSql = "SELECT elementID,fsPath,photoPath,thumbPath from repository_$mid WHERE elementID = $pid";
        $stm = $db->query($sSql);
        $row = $stm->fetch(PDO::FETCH_ASSOC);
		$shareImage = $appConfig['baseurl'] . $row['photoPath'];
		if($lightbox == 1){
			$selImage = "<a href='javascript:void(0)' onclick=\"samLightBox('{$row['fsPath']}');\"><img class='gallery_photo' src='{$row['photoPath']}' alt='Preview'></a>";
		}else{
			$selImage = "<table><tr><td align='center'><img class='gallery_photo' src='{$row['photoPath']}' alt='Preview'></td></tr></table>";
		}
		
		//$arrShare = array("otype"=>"media","type"=>"image","src"=>$shareImage,'href'=>$appConfig['fbTabUrl']);
        //setDataUpdate formats the array as a javascript object that samAJAX will see and use to update the control object on the page
		//parent::setDataUpdate($arrShare);
        return $selImage;
		}
	}
    public function updateorder(){
    	global $db;
        $err = 0;
        $mid = $_REQUEST['mid'];
    	if(isset($_REQUEST['photoList'])){
          foreach($_REQUEST['photoList'] as $key => $val){
              $order = $key +1;
              $sSql = "UPDATE repository_$mid set displayOrder = $order WHERE elementID = $val";
              $db->exec($sSql);
              $dbErr = $db->errorInfo();
              if($dbErr[2] != ""){
                 	$err = 1;
                  	$message = "Failed to update order.";
              }else{
              		$message = "Image order updated!";
              }
          } 
		}else{
        	$message = "Nothing to do!";
        }
        parent::setError($message);
        return $message;
    }
}