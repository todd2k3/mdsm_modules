<?php

class module_tweets extends module	{
	const version = 1.0;
	const moduleDesc = "Twitter Module";
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
				$this->delQuote();
			}
		}else{
			$arrSettings = parent::getModuleParams();
			foreach($arrSettings as $row){
				$key = $row['settingName'];
				$$key = $row['settingVal'];
			}
			//We're showing the main screen
			$out = <<<END
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
</script>
<form id='moduleAdmin'>
	<div>
		<label>RSS Feed URL</label><br/>
		<input type='text' name='mod_feedUrl' value='$feedUrl' />
	</div>
    <div>
		<label>Posts to show</label><br/>
		<input type='text' name='mod_feedCount' value='$feedCount' />
	</div>
	<div>
		<input type='button' id='btnSave' value='Save' onclick='saveConfig();return false;'/><div id='formStat'></div>
	</div>
	<input type='hidden' name='mact' value='adminSave'/><div id='formStat'></div>
</form>
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
    
	public function display($type = null){
    	//Get Configuration from moduleSettings Table
		$modID = parent::getModuleID();
        $pageSettings = parent::getAppConfig();
		$arrSettings = parent::getModuleParams();
		foreach($arrSettings as $row){
			$key = $row['settingName'];
			$$key = $row['settingVal'];
		}
        //Parse RSS feed and display
		if(!function_exists('fetch_rss')){
        	include($_SERVER['DOCUMENT_ROOT'] . "/modules/support_files/magpie_rss/rss_fetch.inc");
		}
		define('MAGPIE_INPUT_ENCODING', 'UTF-8');
		define('MAGPIE_OUTPUT_ENCODING', 'UTF-8');
        if($feedUrl != ""){
        	$out = "";
        	$rss = fetch_rss($feedUrl);
            $count = 0;
			foreach ($rss->items as $item) {
            	$count++;
				$href = $item['link'];
				$title = preg_replace('/^([a-zA-Z0-9]+:)/','',$item['title']); //Strip the users name from the tweet
				$title = preg_replace('/(@\S*:)/','<span class="repliedto">$1</span>',$title); //Strip the users name from the tweet
                preg_match('(http://[a-zA-Z0-9./]+)',$title,$matches);
                $ftitle = preg_replace('(http://[a-zA-Z0-9./]+)','',$title);
                $link = $matches[0];
				$out .= "<p>$ftitle <a href='$link' target='_blank'>$link</a></p>";
                if($count >= $feedCount){
                	break;
                }
			}
		}else{
        	$out = "No feed configured!";
        }
		return $out;
	}
    public function update(){
    	return true;
    }
	public function install(){
		//Install This module
		global $db;
		$moduleID = parent::instanceCreate(__FILE__);
		if($moduleID !== false){
			//Insert settings
			$sSql = "INSERT INTO moduleSettings (moduleID,settingName,settingVal) VALUES ($moduleID,'feedUrl','none')";
			$db->exec($sSql);
        	$sSql = "INSERT INTO moduleSettings (moduleID,settingName,settingVal) VALUES ($moduleID,'feedCount','1')";
			$db->exec($sSql);
			//Create Repository - SAMPLE
            /*
			$sSql = "CREATE TABLE repository_$moduleID (qID int auto_increment not null primary key,quoteText varchar(255) not null,quoteSource varchar(150) default 'anonymous' not null)";
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
            */
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