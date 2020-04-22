<?php

class module_imagerotator extends module	{
	const version = 1.0;
	const moduleDesc = "Image Rotator Module";
	
	function __construct($act = "display")	{
		parent::setModuleClass(basename(__FILE__));
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
		$modClass = parent::getModuleClass();
		if(isset($_REQUEST['mact'])){
			//We're handling the submission of the admin screen
			if($_REQUEST['mact'] == "save"){
				$this->adminSave();
			}elseif($_REQUEST['mact'] == "delete"){
				$this->delQuote();
			}
		}else{
			$arrSettings = parent::getModuleParams();
			foreach($arrSettings as $row){
				$key = $row['settingName'];
				$$key = $row['settingVal'];
			}
			//Get a list of Quotes we have in the system
			$sSql = "SELECT imgID,imagePath,imageLink,altText from repository_{$modID} ORDER by imgID ASC";
			$stm = $db->query($sSql);
			$imageList = "";
			if($stm){
				while($row = $stm->fetch(PDO::FETCH_ASSOC)){
					$imageList .= "<a href='{$row['imagePath']}' rel='lightbox' title='{$row['altText']}' />{$row['imagePath']}</a> <a href='javascript:void(0)' onclick=\"deleteImage({$row['imgID']})\">DELETE</a><br/>";
				}
			}
			if($imageList == ""){
				$imageList = "<div>No Images Found</div>";
			}
			if($jsSlideShow == 1){
				$jss_flag = "checked='checked'";
			}else{
				$jss_flag = "";
			}
			$arrFrameworks = array("prototype"=>"Prototype.js","jquery"=>"jQuery");
			$options = "<option></option>";
			foreach($arrFrameworks as $key => $val){
				if($jsFramework == $key){
					$options .= "<option value='$key' selected='selected'>$val</options>";
				}else{
					$options .= "<option value='$key'>$val</options>";
				}
			}
			//We're showing the main screen
			$out = <<<END
	<script type='text/javascript'>
	function deleteImage(id){
		var url = '/admin/admin_ajax_modules.php';
		new Ajax.Request(url,{
			   parameters: "act=delete&mact=imageDelete&id=" + id,
			   onSuccess: function(transport, json){
				  var data = transport.responseText.evalJSON(true);
				  if(data['error'] == 0){
					  $('formStat').innerHTML = data['message'];
					  $('saveImageButton').disabled = false;
					  updateImages();
					  msgFader('formStat');
				  }else{
					  $('formStat').innerHTML = data['message'];
					  $('saveImageButton').disabled = false;
				  }
			  },
			  method:'post'
		});
	}
	function updateImages(){
	var url = '/admin/admin_ajax_modules.php';
	new Ajax.Request(url,{
		   parameters: "mact=getImages",
		   onSuccess: function(transport, json){
			  var data = transport.responseText.evalJSON(true);
			  if(data['error'] == 0){
				  $('modci_ilist').innerHTML = data['message'];
			  }else{
				  $('formStat').innerHTML = data['message'];
			  }
		  },
		  method:'post'
	});
	}
	function saveConfig(){
		$('saveButton').disabled = true;
		var url = '/admin/admin_ajax_modules.php';
		new Ajax.Request(url,{
			   parameters: $('formConfig').serialize(),
			   onSuccess: function(transport, json){
				  var data = transport.responseText.evalJSON(true);
				  if(data['error'] == 0){
					  $('configFormStat').innerHTML = "<span class='message'>" + data['message'] + "</span>";
					  $('saveButton').disabled = false;
					  msgFader('configFormStat');
				  }else{
					  $('configFormStat').innerHTML = "<span class='error'>" + data['message'] + "</span>";
					  $('saveButton').disabled = false;
				  }
			  },
			  method:'post'
		});
	}
</script>
<div id='modci_ilist'>$imageList</div>
<form method='post' id='formAdd' action='/admin/admin_ajax_modules.php' target='uploader' enctype='multipart/form-data'>
	<div id='formStat'></div>
	<fieldset>
		<legend>Add Images</legend>
		<div><label>Image:</label> <input type='file' name='image'/></div>
        <div><label>Image Link:</label> <input type='text' id='fTarget' name='imageLink' /> <input type='button' value='Browse Pages' onclick="browsePages('fTarget',true);return false;"/> <input type='button' value='Browse Files' onclick='browseFiles();return false;'/></div>
        <div><label>Open in New Window</label> <input type='checkbox' name='newWin' value='1'/>
		<div><label>Alt Text:</label> <input type='text' name='altText'/><input id='saveImageButton' class='form_button' type='submit' value='Add'><input class='form_button' type='button' value='Help' onclick="getHelp('$modClass','module_imagerotator');return false;"></div>
		<input type='hidden' name='mact' value='imageSave'/>
	</fieldset>
</form>
<form id='formConfig'>
	<fieldset>
    	<legend>Configuration</legend>
        <div id='configFormStat'></div>
        <div>
        	<label>Image Height</label>
            <input type='text' name='mod_imgHeight' value='$imgHeight'/>
        </div>
        <div>
        	<label>Image Width</label>
            <input type='text' name='mod_imgWidth' value='$imgWidth'/>
        </div>
        <div>
        	<input type='checkbox' name='mod_jsSlideShow' id='chkSlideshow' value='1' $jss_flag/> JavaScript Slideshow?
        </div>
        <div>
        	<label>Javascript Framework</label> <select name='mod_jsFramework'>$options</select>
        </div>
        <div>
        	<input type='button' id='saveButton' value='save' onclick='saveConfig();return false;'/>
            <input type='hidden' name='mact' value='adminSave' />
        </div>
    </fieldset>
</form>
<iframe name='uploader' src='about:blank' height='100' width='400' style='display:none'></iframe> 
END;
	}
    return $out;
}
	public function adminSave(){
		global $db;
		$error = 0;
		$scount = 0;
		$fcount = 0;
        if(!isset($_REQUEST['mod_jsSlideShow'])){$_REQUEST['mod_jsSlideShow'] = 0;}
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
	public function imageSave(){
    	global $db;
        global $dp;
        global $dbname;
        $error = 0;
        $modID = parent::getModuleID();
		if(isset($_FILES['image']['tmp_name'])){
			//$stm = $db->query("SELECT MAX(imgID) as nextID from repository_$modID");
            $sSql = "select auto_increment as nextID from information_schema.TABLES where TABLE_NAME ='repository_$modID' and TABLE_SCHEMA='$dbname'";
            $stm = $db->query($sSql);
			$row = $stm->fetch(PDO::FETCH_ASSOC);
			$nextID = $row['nextID'];
			if(is_numeric($nextID)){
				$file_ext = substr($_FILES['image']['name'], strripos($_FILES['image']['name'], '.'));
				$filename = $_SERVER['DOCUMENT_ROOT'] . "/media/rotator-$modID-$nextID$file_ext";
				$vfilename = "/media/rotator-$modID-$nextID$file_ext";
				if(move_uploaded_file($_FILES['image']['tmp_name'],$filename)){
                	if($_POST['altText'] == ""){$altText = "Rotator Image $nextID";}else{$altText = $_POST['altText'];}
                    if($_POST['newWin'] == ""){$newWin = "0";}else{$newWin = 1;}
                    if($_POST['imageLink'] == ""){$imageLink = "";}else{$imageLink = $dp->makeClean($_REQUEST['imageLink']);}
					$sSql = "INSERT INTO repository_$modID (imagePath,imageLink,altText,newWin) VALUES ('$vfilename','$imageLink','$altText','$newWin')";
					$db->exec($sSql);
                    $dbErr = $db->errorInfo();
					if($dbErr[2] == ""){
						$message = "Image Inserted successfully";
					}else{
						$message = "A failure occurred while attempting to insert the database record." . $dbErr[2];
						$error = 1;
					}
				}else{
					$message = "A failure occurred while attempting to handle your upload!";
					$error = 1;
				}
			}
		}else{
			$message = "No file was found!";
			$error = 1;
		}
        
		if($error == 0){
        $out = <<<EOF
<script type='text/javascript'>
alert('$message');
parent.updateImages();
parent.document.getElementById('formAdd').reset();
</script>
EOF;
		}else{
        $out = <<<EOF
<script type='text/javascript'>
alert('$message');
</script>
EOF;
        }
        echo $out;
    }
    public function imageDelete(){
    	global $db;
        $error = 0;
        $modID = parent::getModuleID();
    	$imgID = $_REQUEST['id'];
        $stm = $db->query("select imagePath from repository_$modID WHERE imgID = $imgID");
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        $imgLink = $row['imagePath'];
        $cmd = "rm " . $_SERVER['DOCUMENT_ROOT'] . $imgLink;
        exec($cmd);
        $sSql = "DELETE FROM repository_{$modID} WHERE imgID = $imgID";
        $db->exec($sSql);
        $arrError = $db->errorInfo();
        if($arrError[2] != ""){
            $error = 1;
            $message = "Error: " . $arrError[2];
        }else{
        	$message = "Image Deleted!";
        }
        //Save Module Configuration
		$arr = array('error'=>$error,'message'=>$message,'sql'=>$sSql);
		echo json_encode($arr);
    }
    public function getImages(){
    	global $db;
        $error = 0;
        $modID = parent::getModuleID();
        //Get a list of Videos we have in the system
		$sSql = "SELECT imgID,imagePath,altText from repository_{$modID} ORDER by imgID ASC";
        $stm = $db->query($sSql);
        while($row = $stm->fetch(PDO::FETCH_ASSOC)){
            $imageList .= "<a href='{$row['imagePath']}' rel='lightbox' title=\"{$row['altText']}\" />{$row['imagePath']}</a> <a href='javascript:void(0)' onclick=\"deleteImage({$row['imgID']})\">DELETE</a><br/>";
        }
        if($imageList == ""){
            $imageList = "<div>No Images Found</div>";
        }
       
		$arr = array('error'=>$error,'message'=>$imageList,'sql'=>$sSql);
		echo json_encode($arr);
    }
    
	public function display($type = null){
    	global $db;
        include($_SERVER['DOCUMENT_ROOT'] . "/clases/dataprocessing.class.php");
        $dp = New DataTools();
        $modID = parent::getModuleID();
        $arrParams = parent::getModuleParams();
        foreach($arrParams as $setting){
        	$key = $setting['settingName'];
            $$key = $setting['settingVal'];
        }
        
        if($jsSlideShow == 1){
        	if($jsFramework == "prototype"){
                $script = <<<EOF
    <script type='text/javascript'>
    </script>
EOF;
            }else{
                $script = <<<EOF
    <script type="text/javascript">
		$(document).ready(function() {
    		$('.jsslide').cycle('fade'); // choose your transition type, ex: fade, scrollUp, shuffle, etc...
		});
    </script>
EOF;
            }
            parent::addToFooter($script);
        	//Display them all and let Javascript create a slideshow
            $sSql = "SELECT imagePath,imageLink,altText,newWin from repository_$modID WHERE 1 = 1 LIMIT 10";
			$stm = $db->query($sSql);
            while($row = $stm->fetch(PDO::FETCH_ASSOC)){
            	$altText = str_replace("'","\'",$row['altText']);
            	if($row['newWin'] == 1){$newWin = "target='_blank'";}else{$newWin = "";}
            	if($row['imageLink'] != ""){$linkstart = "<a href='{$row['imageLink']}' $newWin>";$linkend="</a>";}else{$linkstart="";$linkend="";}
            	$images .= "$linkstart<img class=\"jsslide\" src=\"{$row['imagePath']}\" alt=\"{$row['altText']}\" height=\"$imgHeight\" width=\"$imgWidth\" />$linkend";
            }
            return $images;
        }else{
        	//Choose one at Random
        	$sSql = "SELECT imagePath,imageLink,altText,newWin from repository_$modID WHERE 1 = 1 LIMIT 10";
			$stm = $db->query($sSql);
			$arrImgs = array();
			while($row = $stm->fetch(PDO::FETCH_ASSOC)){
				$arrImgs[] = array("path"=>$dp->makeDirty($row['imagePath']),"link"=>$dp->makeDirty($row['imageLink']),"alt"=>$dp->makeDirty($row['altText']),"newwin"=>$row['newWin']);
			}
			$selected = rand(0,count($arrImgs)-1);
			$image = $arrImgs[$selected];
            if($image['newwin'] == 1){$newWin = "target='_blank'";}else{$newWin = "";}
            if($image['link'] != ""){$linkstart = "<a href='{$image['link']}' $newWin>";$linkend="</a>";}else{$linkstart="";$linkend="";}
			return "$linkstart<img src='{$image['path']}' alt=\"{$image['alt']}\" />$linkend";
        }
	}
    public function update(){
    	return $this->display("ajax");
    }
    
	public function install(){
		//Install This module
		global $db;
		$moduleID = parent::instanceCreate(__FILE__);
		if($moduleID !== false){
			//Insert settings
			$sSql = "INSERT INTO moduleSettings (moduleID,settingName,settingVal) VALUES ($moduleID,'jsSlideShow','0')";
			$db->exec($sSql);
            $sSql = "INSERT INTO moduleSettings (moduleID,settingName,settingVal) VALUES ($moduleID,'jsFramework','0')";
			$db->exec($sSql);
			$sSql = "INSERT INTO moduleSettings (moduleID,settingName,settingVal) VALUES ($moduleID,'imgHeight','0')";
			$db->exec($sSql);
            $sSql = "INSERT INTO moduleSettings (moduleID,settingName,settingVal) VALUES ($moduleID,'imgWidth','0')";
			$db->exec($sSql);
			$sSql = "CREATE TABLE repository_$moduleID (imgID int not null auto_increment primary key,imagePath varchar(150) not null,imageLink varchar(150) not null,altText varchar(100),newWin tinyint default 0)";
			$db->exec($sSql);
            $dbErr = $db->errorInfo();
			if($dbErr[2] != ""){
				//Install failed - Uninstall
                parent::setError("Installation Failed: {$dbErr[2]}");
				//$this->uninstall($moduleID);
				return false;
			}else{
				return true;
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