<?php

class module_quotes extends module	{
	const version = 1.0;
	const moduleDesc = "Quotes Module";
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
			$sSql = "SELECT qID,quoteText from repository_{$modID} ORDER by qID ASC";
			$stm = $db->query($sSql);
			$quoteList = "";
			if($stm){
				while($row = $stm->fetch(PDO::FETCH_ASSOC)){
					$quoteList .= "<div><table><tr><td>{$row['quoteText']}</td><td>&nbsp;<a href='javascript:void(0)' onclick='deleteQuote({$row['qID']})'>DELETE</a></td></tr></table></div>";
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
		<label>Quote</label>
		<input type='text' name='mod_quoteText' value='$quoteText' />
	</div>
    <div>
		<label>Source</label>
		<input type='text' name='mod_quoteQource' value='$quoteSource' />
	</div>
    <div>
    	<input type='button' id='saveQuoteButton' class='form_button' value='Save Quote' onclick='saveQuote()'/>
    </div>
    <input type='hidden' name='mact' value='quoteSave'/>
    </fieldset>
 </form>
END;
	}
    return $out;
}

	public function quoteSave(){
    	global $db;
        $error = 0;
        $modID = parent::getModuleID();
        $quoteText = $_REQUEST['mod_quoteText'];
        $sSql = "INSERT INTO repository_{$modID} (quoteText,quoteSource) VALUES ('$quoteText','$quoteSource')";
        $db->exec($sSql);
        $arrError = $db->errorInfo();
        if($arrError[2] != ""){
            $error = 1;
            $message = $arrError[2];
        }else{
        	$message = "Quote Saved!";
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
        	$message = "Quote Deleted!";
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
            $quoteList .= "<div><table><tr><td>{$row['quoteText']}</td><td>&nbsp;<a href='javascript:void(0)' onclick='deleteQuote({$row['qID']})'>DELETE</a></td></tr></table></div>";
        }
        if($quoteList == ""){
            $quoteList = "<div>No Videos Found</div>";
        }
       
		$arr = array('error'=>$error,'message'=>$quoteList,'sql'=>$sSql);
		echo json_encode($arr);
    }
    
	public function display($type = null){
    	global $db;
        $modID = parent::getModuleID();
        //Javascript function to update the view
        $out = <<<END
<script type='text/javascript'>
function getNewQuote(){
new Ajax.Request('/includes/moduleapi.php',{
		   parameters: "id=$modID",
		   onSuccess: function(transport, json){
			  var data = transport.responseText.evalJSON(true);
			  if(data["error"] == 0){
				  $('randomquote').innerHTML = data['message'];
			  }
		  },
		  method:"get"
	  });
}
</script>
END;
		
		if($type == null){
        	parent::addToHeader($out);
        	$sQuote = $this->returnQuote();
			$quote = "<div id='randomquote'>". $sQuote . "<a href='javascript:void(0)' onclick='getNewQuote();'>Get Another</a></div>";
		}else{
        	$sQuote = $this->returnQuote();
            $quote = $sQuote . "<a href='javascript:void(0)' onclick='getNewQuote();'>Get Another</a>";
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
        $sSql = "SELECT qID,quoteText,quoteSource from repository_{$this->getModuleID()} WHERE 1";
        $stm = $db->query($sSql);
        while($row = $stm->fetch(PDO::FETCH_ASSOC)){
            $arrQuotes[] = array("qID"=>$row['qID'],"quote"=>$row['quoteText'],"source"=>$row['quoteSource']);
        }
        if(count($arrQuotes > 0)){
            $pick = rand(0,(count($arrQuotes)-1));
            $quote = $arrQuotes[$pick]['quote'] . "~" . $arrQuotes[$pick]['source'];
        }else{
            $quote = "Hello World! ~anonymous";
        }
        return $quote;
    }
	public function install(){
		//Install This module
		global $db;
		$moduleID = parent::instanceCreate(__FILE__);
		if($moduleID !== false){
			//Insert settings
			/* $sSql = "INSERT INTO moduleSettings (moduleID,settingName,settingVal) VALUES ($moduleID,'feedUrl','none')";
			$db->exec($sSql);
        	$sSql = "INSERT INTO moduleSettings (moduleID,settingName,settingVal) VALUES ($moduleID,'feedCount','1')";
			$db->exec($sSql);
        	$sSql = "INSERT INTO moduleSettings (moduleID,settingName,settingVal) VALUES ($moduleID,'devKey','1')";
			$db->exec($sSql);
			*/
			//Create Repository - SAMPLE
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