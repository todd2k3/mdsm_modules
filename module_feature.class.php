<?php
/* Modified to allow text with the image */
class module_feature extends module	{
	const version = 1.0;
	const moduleDesc = "Feature Module";
	
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
			$sSql = "SELECT imgID,imagePath,featureLink,featureTitle,altText from repository_{$modID} ORDER by imgID ASC";
			$stm = $db->query($sSql);
			$imageList = "";
			if($stm){
				while($row = $stm->fetch(PDO::FETCH_ASSOC)){
					$imageList .= "{$row['featureTitle']} <a href='javascript:void(0)' onclick=\"modImage({$row['imgID']})\">MODIFY</a> | <a href='javascript:void(0)' onclick=\"deleteImage({$row['imgID']})\">DELETE</a><br/>";
				}
			}
			if($imageList == ""){
				$imageList = "<div>No Images Found</div>";
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
			$form = $this->featureform();
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
	function modImage(id){
		tinyMCE.execCommand('mceRemoveControl', true, document.getElementById('taFeature'));
		var url = "/admin/admin_ajax_modules.php?act=modify&mact=imageEdit&id=" + id;
		new Ajax.Request(url,{
			   parameters: "act=modify&mact=imageEdit&id=" + id,
			   onSuccess: function(transport){
				   var response = transport.responseText
				   $('formArea').innerHTML = response;
				   tinyMCE.execCommand("mceAddControl", true, document.getElementById('taFeature'));
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
	function resetForm(){
		tinyMCE.execCommand('mceRemoveControl', true, document.getElementById('taFeature'));
		var url = '/admin/admin_ajax_modules.php';
		new Ajax.Request(url,{
			parameters:"mact=resetForm",
			onSuccess: function(transport){
				var response = transport.responseText;
				$('formArea').innerHTML = response;
				tinyMCE.execCommand('mceAddControl', true, document.getElementById('taFeature'));
			},
			method:'post'
		});
	}
	function triggerSave(){
		tinyMCE.triggerSave();
	}
</script>
<div id='modci_ilist'>$imageList</div>
<div id='formArea'>$form</div>
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
	public function imageEdit(){
    	$out = $this->featureform("update",$_REQUEST['id']);
        return $out;
        //$arr = array("error"=>0,"message"=>$out);
        //echo json_encode($arr);
    }
    public function resetForm(){
    	$out = $this->featureform();
        return $out;
    }
	public function featureform($m="insert",$i=null){
    	global $db;
        $modID = parent::getModuleID();
        if($m == "update"){
        	$sql = "SELECT * from repository_$modID WHERE imgID = $i";
            $rs = $db->query($sql);
            $row = $rs->fetch(PDO::FETCH_ASSOC);
            foreach($row as $f => $v){
            	$$f = $v;
            }
            $modeLabel = "Update";
            $mode = "<input type='hidden' name='mode' value='update' />";
            $ikey = "<input type='hidden' name='imgid' value='$i' />";
        }else{
        	$modeLabel = "Add";
        	$mode = "";
            $ikey = "";
        }
        if($newWin == 1){$nwFlag = "checked='checked'";}else{$nwFlag = "";}
        $out = <<<EOF
<form method='post' id='formAdd' action='/admin/admin_ajax_modules.php' target='uploader' enctype='multipart/form-data'>
	<div id='formStat'></div>
	<fieldset>
		<legend>Add Images</legend>
		<div><label>Image:</label> <input id='txtImage' type='text' name='frm_imagePath' value='$imagePath'/><input type='button' value='Browse Images' onclick="browseImages('txtImage');return false;"/></div>
        <div><label>Feature Title</label> <input type='text' name='frm_featureTitle' value='$featureTitle' /></div>
        <div><label>Feature</label> <textarea id='taFeature' name='frm_feature' cols="50" rows="4">$feature</textarea></div>
        <div><label>Feature Link</label> <input type='text' id='txtFeatureLink' name='frm_featureLink' value='$featureLink' /><input type='button' value='Browse Pages' onclick="browsePages('txtFeatureLink');return false;"/></div>
        <div><label>Open link in new window</label> <input type='checkbox' name='newWin' value='1' $nwFlag/></div>
        <div><input id='saveImageButton' class='form_button' type='submit' value='$modeLabel' onclick='triggerSave();'><input class='form_button' type='button' value='Help' onclick="getHelp('$modClass','module_imagerotator');return false;"></div>
		<input type='hidden' name='mact' value='imageSave'/>
        $mode
        $ikey
	</fieldset>
</form>
EOF;
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
        $mode = $_REQUEST['mode'];
        $modID = parent::getModuleID();
        foreach($_REQUEST as $k => $v){
        	if(strpos($k,"frm_") !== false){
            	$field = str_replace("frm_","",$k);
            	if($mode == "update"){
                	$updt .= "$field = \"$v\",";
                }else{
                	$insf .= "$field,";
                    $insv .= "\"$v\","; 
                }
            } 
        }
        if(!isset($_REQUEST['newWin'])){$newWin = 0;}else{$newWin = 1;}
        if($mode == "update"){
            $updt .= "newWin = \"$newWin\",";
        }else{
            $insf .= "newWin,";
            $insv .= "$newWin,"; 
        }
        
        if($_REQUEST['mode'] == "update"){
        	$sSql = "UPDATE repository_$modID SET ".rtrim($updt,",")." WHERE imgID = {$_REQUEST['imgid']}";
        }else{
        	$sSql = "INSERT INTO repository_$modID (".rtrim($insf,",").") VALUES (".rtrim($insv,",").")";
        }
        $db->exec($sSql);
        $dbErr = $db->errorInfo();
        if($dbErr[2] == ""){
        	if($mode == "update"){
				$message = "feature updated successfully";
            }else{
            	$message = "feature inserted successfully";
            }
        }else{
            $message = "A failure occurred while attempting to insert the database record." . $dbErr[2];
            $error = 1;
        }
        
		if($error == 0){
        $out = <<<EOF
<script type='text/javascript'>
alert('$message');
parent.updateImages();
parent.document.getElementById('formAdd').reset();
parent.resetForm();
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
		$sSql = "SELECT imgID,featureTitle,altText from repository_{$modID} ORDER by imgID ASC";
        $stm = $db->query($sSql);
        while($row = $stm->fetch(PDO::FETCH_ASSOC)){
            $imageList .= "{$row['featureTitle']}  <a href='javascript:void(0)' onclick=\"modImage({$row['imgID']})\">MODIFY</a> | <a href='javascript:void(0)' onclick=\"deleteImage({$row['imgID']})\">DELETE</a><br/>";
        }
        if($imageList == ""){
            $imageList = "<div>No Images Found</div>";
        }
       
		$arr = array('error'=>$error,'message'=>$imageList,'sql'=>$sSql);
		echo json_encode($arr);
    }
    
	public function display($type = null){
    	global $db;
        global $dp;
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
            $sSql = "SELECT imagePath,featureLink,altText,newWin from repository_$modID WHERE 1 = 1 LIMIT 10";
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
        	$sSql = "SELECT imagePath,altText,featureTitle,feature,featureLink,newWin from repository_$modID WHERE 1 = 1 LIMIT 10";
			$stm = $db->query($sSql);
			$arrImgs = array();
			while($row = $stm->fetch(PDO::FETCH_ASSOC)){
				$arrImgs[] = array("path"=>$dp->makeDirty($row['imagePath']),"link"=>$dp->makeDirty($row['imageLink']),"alt"=>$dp->makeDirty($row['altText']),"title"=>$row['featureTitle'],"feature"=>$row['feature'],"featurelink"=>$row['featureLink'],"newwin"=>$row['newWin']);
			}
			$selected = rand(0,count($arrImgs)-1);
			$image = $arrImgs[$selected];
            if($image['newwin'] == 1){$newWin = "target='_blank'";}else{$newWin = "";}
            if($image['link'] != ""){$linkstart = "<a href='{$image['featurelink']}' $newWin>";$linkend="</a>";}else{$linkstart="";$linkend="";}
            //Image and Text
            if($image['featurelink'] != ""){
                $flink = "<a href='{$image['featurelink']}' $newWin><img class='more' border='0' src='/userfiles/more.png' /></a></p>";
            }else{
                $flink = "";
            }
            $featureText = $image['feature'] . $flink;
            $modout = <<<EOF
<div class='featurepic'>
<img src="{$image['path']}" width="{$arrParams['imgWidth']}" height="{$arrParams['imgHeight']}" alt="{$image['altText']}" /></div>
        <div class='featuretext'><div class='featureTitle'>{$image['title']}</div><p>$featureText</p>
        </div>
EOF;
            return $modout;
        }
        return "test";
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
			$sSql = "CREATE TABLE repository_$moduleID (imgID int not null auto_increment primary key,imagePath varchar(150) not null,altText varchar(100),featureTitle varchar(100), feature varchar(255), featureLink varchar(175),newWin tinyint default 0)";
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