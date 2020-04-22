<?php

class module_testimonials extends module	{
	const version = 1.0;
	const moduleDesc = "Testimonials Module";
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
			//Get a list of Quotes we have in the system
			$sSql = "SELECT qID,quoteSource from repository_{$modID} ORDER by qID ASC";
			$stm = $db->query($sSql);
			$quoteList = "";
			if($stm){
				while($row = $stm->fetch(PDO::FETCH_ASSOC)){
					$quoteList .= "<div><table><tr><td>".strip_tags($row['quoteSource'])."</td><td>&nbsp;<a href='javascript:void(0)' onclick='deleteQuote({$row['qID']})'>DELETE</a></td></tr></table></div>";
				}
			}
			if($quoteList == ""){
				$quoteList = "<div>No Quotes Found</div>";
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
	function saveQuote(){
		tinyMCE.triggerSave();
		$('saveQuoteButton').disabled = true;
		var formObj = $('quoteAdmin');
		var url = '/admin/admin_ajax_modules.php';
		new Ajax.Request(url,{
			   parameters: formObj.serialize(),
			   onSuccess: function(transport, json){
				  var data = transport.responseText.evalJSON(true);
				  if(data['error'] == 0){
					  $('formStat').innerHTML = data['message'];
					  $('saveQuoteButton').disabled = false;
					  updateQuotes();
					  msgFader('formStat');
					  formObj.reset();
				  }else{
					  $('formStat').innerHTML = data['message'];
					  $('saveQuoteButton').disabled = false;
				  }
			  },
			  method:'post'
		});
	}
	function deleteQuote(id){
		var url = '/admin/admin_ajax_modules.php';
		new Ajax.Request(url,{
			   parameters: "act=delete&mact=quoteDelete&id=" + id,
			   onSuccess: function(transport, json){
				  var data = transport.responseText.evalJSON(true);
				  if(data['error'] == 0){
					  $('formStat').innerHTML = data['message'];
					  $('saveQuoteButton').disabled = false;
					  updateQuotes();
					  msgFader('formStat');
				  }else{
					  $('formStat').innerHTML = data['message'];
					  $('saveQuoteButton').disabled = false;
				  }
			  },
			  method:'post'
		});
	}
	function updateQuotes(){
	var url = '/admin/admin_ajax_modules.php';
	new Ajax.Request(url,{
		   parameters: "mact=getQuotes",
		   onSuccess: function(transport, json){
			  var data = transport.responseText.evalJSON(true);
			  if(data['error'] == 0){
				  $('modq_qlist').innerHTML = data['message'];
			  }else{
				  $('formStat').innerHTML = data['message'];
			  }
		  },
		  method:'post'
	});
}
</script>
<div id='modq_qlist'>$quoteList</div>
<form id='quoteAdmin'>
	<fieldset>
    <div id='formStat'></div>
    <div>
		<label>Synopsis (150 characters max)</label>
		<input type='text' name='mod_quoteSynopsis' value='$quoteText' maxlength='150'/>
	</div>
    <div>
		<label>Testimonial (You must add quotation marks where appropriate)</label>
		<textarea name='mod_quoteText' cols='60' rows='4'>$quoteText</textarea>
	</div>
    <div>
		<label>Source</label>
		<input type='text' name='mod_quoteSource' value='$quoteSource' />
	</div>
    <div>
    	<input type='button' id='saveQuoteButton' class='form_button' value='Save Testimonial' onclick='saveQuote()'/>
    </div>
    <input type='hidden' name='mact' value='quoteSave'/>
    </fieldset>
 </form>
 <a href='javascript:void(0)' onclick="toggleDiv('configForm');">Configure Module</a>
<div style='display:none' id='configForm'>
 <form id='moduleAdmin'>
	<fieldset>
    	<legend>Configuration</legend>
        <div id='configFormStat'></div>
        <div>
        	<label>Testimonials Page</label>
            <input type='text' name='mod_testimonialpage' id='mTarget' value='$testimonialpage'/> <input type='button' value='Browse Pages' onclick="browsePages('fTarget')"/>
        </div>
        <div>
			<input type='button' id='btnSave' value='Save' onclick='saveConfig();return false;'/><div id='formStat'></div>
		</div>
		<input type='hidden' name='mact' value='configSave'/>

    </fieldset>
</form>
</div>
END;
	}
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
	public function adminSave(){
		global $db;
		$error = 0;
		$scount = 0;
		$fcount = 0;
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
	public function quoteSave(){
    	global $db;
        $error = 0;
        $modID = parent::getModuleID();
        $quoteSynopsis = $_REQUEST['mod_quoteSynopsis'];
        $quoteText = $_REQUEST['mod_quoteText'];
        $quoteSource = $_REQUEST['mod_quoteSource'];
        $sSql = "INSERT INTO repository_{$modID} (quoteSynopsis,quoteText,quoteSource) VALUES (\"$quoteSynopsis\",\"$quoteText\",\"$quoteSource\")";
        $db->exec($sSql);
        $arrError = $db->errorInfo();
        if($arrError[2] != ""){
            $error = 1;
            $message = $arrError[2];
        }else{
        	$message = "Testimonial Saved!";
        }
        //Save Module Configuration
		$arr = array('error'=>$error,'message'=>$message,'sql'=>$sSql);
		echo json_encode($arr);
    }
    public function quoteDelete(){
    	global $db;
        $error = 0;
        $modID = parent::getModuleID();
    	$qID = $_REQUEST['id'];
        
        $sSql = "DELETE FROM repository_{$modID} WHERE qID = $qID";
        $db->exec($sSql);
        $arrError = $db->errorInfo();
        if($arrError[2] != ""){
            $error = 1;
            $message = "Error: " . $arrError[2];
        }else{
        	$message = "Testimonial Deleted!";
        }
        //Save Module Configuration
		$arr = array('error'=>$error,'message'=>$message,'sql'=>$sSql);
		echo json_encode($arr);
    }
    public function getQuotes(){
    	global $db;
        $error = 0;
        $modID = parent::getModuleID();
        //Get a list of Videos we have in the system
		$sSql = "SELECT qID,quoteText,quoteSource from repository_{$modID} ORDER by qID ASC";
        $stm = $db->prepare($sSql);
        $stm->execute();
        while($row = $stm->fetch(PDO::FETCH_ASSOC)){
            $quoteList .= "<div><table><tr><td>".strip_tags($row['quoteSource'])."</td><td>&nbsp;<a href='javascript:void(0)' onclick='deleteQuote({$row['qID']})'>DELETE</a></td></tr></table></div>";
        }
        if($quoteList == ""){
            $quoteList = "<div>No Testimonials Found</div>";
        }
       
		$arr = array('error'=>$error,'message'=>$quoteList,'sql'=>$sSql);
		echo json_encode($arr);
    }
    
	public function display($type = null){
    	global $db;
        $modID = parent::getModuleID();
		
		if($type == null){
        	//Page View
        	$sQuote = $this->returnAllQuotes();
			$quote = $sQuote;
		}else{
        	//Widget View
        	$sQuote = $this->returnQuote();
            $quote = $sQuote;
			//Our output for AJAX is formated JSON by moduleapi.php just return text
		}
        return $quote;
	}
    public function update(){
    	return $this->display("ajax");
    }
    private function returnQuote(){
    	global $db;
        $arrQuotes = array();
        $arrSettings = parent::getModuleParams();
		foreach($arrSettings as $row){
			$key = $row['settingName'];
			$$key = $row['settingVal'];
		}
        $sSql = "SELECT qID,quoteSynopsis,quoteSource from repository_{$this->getModuleID()} WHERE 1";
        $stm = $db->query($sSql);
        while($row = $stm->fetch(PDO::FETCH_ASSOC)){
            $arrQuotes[] = array("qID"=>$row['qID'],"quote"=>$row['quoteSynopsis'],"source"=>$row['quoteSource']);
        }
        if(count($arrQuotes > 0)){
            $pick = rand(0,(count($arrQuotes)-1));
            $quote = "<div id='quote'>".$arrQuotes[$pick]['quote'] . " <a href='/page/{$testimonialpage}#q".$arrQuotes[$pick]['qID']."'>Read More...</a><p style='margin-top:10px;' align='right'>~" . $arrQuotes[$pick]['source']."</p></div>";
        }else{
            $quote = "Hello World! ~anonymous";
        }
        return $quote;
    }
    private function returnAllQuotes(){
    	global $db;
        $arrQuotes = array();
        $sSql = "SELECT qID,quoteText,quoteSource from repository_{$this->getModuleID()} WHERE 1 ORDER By displayOrder";
        $stm = $db->query($sSql);
        while($row = $stm->fetch(PDO::FETCH_ASSOC)){
            $out .= "<div class='testimonial'><a name='q{$row['qID']}'></a><div class='testimonial_text'>".trim($row['quoteText'],"\t\n\r ")."</div><div class='testimonial_source'>~{$row['quoteSource']}</div></div>";
        }
        return $out;
    }
	public function install(){
		//Install This module
		global $db;
		$moduleID = parent::instanceCreate(__FILE__);
		if($moduleID !== false){
			//Insert settings
        	$sSql = "INSERT INTO moduleSettings (moduleID,settingName,settingVal) VALUES ($moduleID,'testimonialpage','1')";
			$db->exec($sSql);
			//Create Repository - SAMPLE
			$sSql = "CREATE TABLE repository_$moduleID (qID int auto_increment not null primary key,quoteSynopsis varchar(150) not null,quoteText text not null,quoteSource varchar(150) default 'anonymous' not null,displayOrder int default 0)";
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