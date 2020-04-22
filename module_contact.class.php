<?php

class module_contact extends module	{
	const version = 1.0;
	const moduleDesc = "Contact Module";
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
			if($_REQUEST['mact'] == "adminSave"){
				$this->adminSave();
				exit;
			}elseif($_REQUEST['mact'] == "delete"){
				$this->delQuote();
			}
		}else{
			$arrSettings = parent::getModuleParams();
			foreach($arrSettings as $row){
				$key = $row['settingName'];
				$$key = $row['settingVal'];
			}
			if($mailSystem == "exim"){
				$eximChecked = " checked='checked'";
				$postfixChecked = "";
			}else{
				$eximChecked = "";
				$postfixChecked = "checked='checked'";
			}
			$recList = $this->getRecList();
			//We're showing the main screen
			$out = <<<END
<!-- ?> -->
	<script type='text/javascript'>
	function saveConfig(){
		$('btnSave').disabled = true;
		var formObj = $('frmConfig');
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
	function addRecipient(){
		var formObj = $('frmRecipient');
		var url = '/admin/admin_ajax_modules.php';
		new Ajax.Request(url,{
			   parameters: formObj.serialize(),
			   onSuccess: function(transport, json){
				  var data = transport.responseText.evalJSON(true);
				  if(data['error'] == 0){
					  $('recipientList').innerHTML = data['message'];
					  $('frmRecipient').reset();
				  }else{
					  $('recFormStat').innerHTML = data['message'];
				  }
			  },
			  method:'post'
		});
	}
	function delRecipient(rid){
		var url = '/admin/admin_ajax_modules.php';
		var params = "mact=delRecipient&rid="+rid;
		new Ajax.Request(url,{
			   parameters: params,
			   onSuccess: function(transport, json){
				  var data = transport.responseText.evalJSON(true);
				  if(data['error'] == 0){
					  $('recipientList').innerHTML = data['message'];
				  }else{
					  $('recFormStat').innerHTML = data['message'];
				  }
			  },
			  method:'post'
		});
	}
</script>
	<form id='frmConfig'>
    <div id='formStat'></div>
		<div><label>Email Recipient(s):</label><input type='text' name='mod_recipients' value='$recipients'>(comma delimited)</div>
		<div><label>Message Subject:</label><input type='text' name='mod_msgsubject' value='$msgsubject'></div>
		<div><label>Return Message:</label><input type='text' name='mod_returnText' value ='$returnText'></div>
		<div><label>Website name:</label><input type='text' name='mod_siteName' value ='$siteName'></div>
		<div><label>Sender email:</label><input type='text' name='mod_senderMail' value ='$senderMail'></div>
		<div><label>Mail System:</label><input type='radio' name='mod_mailSystem' value ='exim' $eximChecked>Exim <input type='radio' name='mod_mailSystem' value ='postfix' $postfixChecked>PostFix</div>
		<!--div><label>Enable Constant Contact Integration</label><input type='checkbox' name='ConstantContact' value='1' $ccenable /></div>
		<div><label>Constant Contact Login</label><input type='text' name='CCLogin' value='$CCLogin' /></div>
		<div><label>Constant Contact Password</label><input type='text' name='CCPass' value='$CCPass' /></div>
		<div><label>Constant Contact API Key</label><input type='text' name='CCKey' value='$CCKey' /></div>
		<div><label>Constant Contact List ID</label><input type='text' name='CCListID' value='$CCListID' /></div-->
		<div><input id='btnSave' class='form_button' type='button' value='Save Configuration' onclick='saveConfig();return false;'><input class='form_button' type='button' value='Help' onclick="getHelp('$mod_file','mod_contact');return false;"></div>
		<input type='hidden' name='mact' value='adminSave'/>
	</form>
    <form id='frmRecipient'>
    <div id='recFormStat'></div>
    <fieldset>
    <div id='recipientList'>
    $recList
    </div>
    <div>
    	<label>Recipient Name</label><input type='text' name='recName'/>
    </div>
    <div>
    	<label>Recipient Email</label><input type='text' name='recEmail'/>
    </div>
    <div>
    	<input type='button' value='Add' onclick='addRecipient();return false;'/>
    </div>
    <input type='hidden' name='mact' value='addRecipient'/>
    </fieldset>
    </form>
<!-- <?php -->
END;
	}
    return $out;
}
	public function addRecipient(){
		global $db;
		$error = 0;
		$modID = parent::getModuleID();
		$sql = "INSERT INTO repository_$modID (recName,recEmail) VALUES (\"{$_REQUEST['recName']}\",\"{$_REQUEST['recEmail']}\")";
		if($db->query($sql)){
			$message = $this->getRecList();
		}else{
			$message = "INSERT failed.";
			$error = 1;
		}
		$arr = array("error"=>$error,"message"=>$message);
		echo json_encode($arr);
	}
	public function delRecipient(){
		global $db;
		$error = 0;
		$modID = parent::getModuleID();
		$sql = "DELETE FROM repository_$modID WHERE recID = {$_REQUEST['rid']}";
		if($db->query($sql)){
			$message = $this->getRecList();
		}else{
			$message = "FAILED to remove recipient";
			$error = 1;
		}
		$arr = array("error"=>$error,"message"=>$message);
		echo json_encode($arr);
	}
	private function getRecList(){
		global $db;
		$modID = parent::getModuleID();
		$sSql = "SELECT recID,recName,recEmail from repository_$modID WHERE 1";
		$stm = $db->query($sSql);
		while($row = $stm->fetch(PDO::FETCH_ASSOC)){
			$recList .= "{$row['recName']} ({$row['recID']}) ({$row['recEmail']}) <a href='javascript:void(0)' onclick='delRecipient({$row['recID']});return false;'>DELETE</a><br/>";
		}
		if($recList == ""){
			$recList = "No Recipients Defined";
		}
		return $recList;
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
    	global $db;
        $modID = parent::getModuleID();
		if(isset($_REQUEST['sid']) && $_REQUEST['sid'] != ""){
			$recKey = "<input type='hidden' name='sid' value='{$_REQUEST['sid']}'/>";
		}else{
			$recKey = "";
		}
        //Javascript function to update the view
        $out = <<<END
<!-- ?> -->
<!--script type='text/javascript'>
function submitContact(){
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
</script-->
<script type='text/javascript'>
function submitContact(){
	var url = "/includes/moduleapi.php";
	$.post(url,$('#contactForm').serialize() + "&id=$modID&ajax=1",function(data){
		if(data.error == 0){
			$('#contact_form').html(data.message);
		}else{
			$('#formStat').html("<span class='error'>"+data.message+"</span>");
		}
	},'json');
}
</script>

	<div id='contact_form'>
    <div id='formStat'></div>
	<form id='contactForm' action='$url_full_uri' method='post'>
		<div><label>Name*</label><input type='text' name='sender' value='$sender'  size='40'/></div>
		<div><label>Company</label><input type='text' name='company' value='$company' size='50' /></div>
		<div><label>Phone*</label><input type='text' name='phone' value='$phone' size='15' /></div>
		<div><label>Email*</label><input type='text' name='email' value='$email'  size='40'/></div>
		<div style='margin-top: 10px;'>How can we be of further assistance?*<br/>
		<textarea name='comments' cols='60' rows='4'>$comments</textarea></div>
		<div style='margin-top:10px;'><input class='form_button' type='button'  value='send' onclick='submitContact();return false;'/> <input class='form_button' type='reset' value='clear' /></div>
		<input type='hidden' name='module' value='mod_contact'/>
        $recKey
	</form>
	</div>
<!-- <?php -->
END;
	return $out;
	}
    public function update(){
    	//Form Submit
		global $sitename;
        $arrSettings = parent::getModuleParams();
		foreach($arrSettings as $row){
			$key = $row['settingName'];
			$$key = $row['settingVal'];
		}
        if($mailSystem == "exim"){
			include_once(rtrim($_SERVER['DOCUMENT_ROOT'],"/") . "/classes/htmlMailer.class.php"); //Exim Mailer
		}else{
			include_once(rtrim($_SERVER['DOCUMENT_ROOT'],"/") . "/classes/htmlMailer-postfix.class.php"); //Postfix Mailer
		}
		//Recipient Lookup
		if(isset($_REQUEST['sid']) && $_REQUEST['sid'] != ""){
			$recipientLU = $this->lookupRecipient($_REQUEST['sid']);
			if($recipientLU !== false){
				$recipients = $recipientLU;
			}
		}
		//Form Fields
		$arrFields = array("sender"=>"Name","company"=>"Company Name","address"=>"Address","city"=>"City","state"=>"State","zipcode"=>"Zip Code","phone"=>"Phone Number","email"=>"Email Address","comments"=>"Comments");
		//Required Fields
		$arrRequired = array("sender"=>"Name","phone"=>"Phone","email"=>"Email","comments"=>"Comment");
        if(isset($_POST['email'])){
            foreach($_POST as $key => $val){
                if($arrFields[$key] != ""){
                    if($arrRequired[$key] != ""){
                        if($val == ""){ 
                            $msg .= "{$arrRequired[$key]} is required.<br/>\n";
                            parent::setError("Required Fields");
                        }else{
                            $htmlmessage .= "{$arrFields[$key]}: " . stripslashes($val) . "<br/>";
                        } 
                    }else{
                        $htmlmessage .= "{$arrFields[$key]}: " . stripslashes($val) . "<br/>";
                    }
                }
            }
            if(parent::getError() == ""){
                    $newmsg = new HTMLMailer(rtrim($_SERVER['DOCUMENT_ROOT'],"/") . "/classes/mailtemplate.html");
                    $newmsg->createMessage($htmlmessage,$siteName);
                    $newmsg->setVals($recipients, $senderMail,$siteName, $msgsubject);
                    if($newmsg->sendMail()){
                        $msg = "<p>$returnText</p>";
                    }else{
                        $msg .= "<p>An error occurred while sending your message. Please try again later.</p>" . $newmsg->msgerr;
                        parent::setError("Send Failed");
                    }
            }
        }else{
            $msg = "No data to send.";
        }
    	return $msg;
    }
    private function lookupRecipient($id){
		global $db;
		$modID = parent::getModuleID();
		$sSql = "SELECT recEmail from repository_$modID WHERE recID = $id";
		$stm = $db->query($sSql);
		$row = $stm->fetch(PDO::FETCH_ASSOC);
		$recipient = $row['recEmail'];
		if($recipient == ""){
			return false;
		}else{
			return $recipient;
		}
	}
	public function install(){
		//Install This module
		global $db;
		$moduleID = parent::instanceCreate(__FILE__);
		if($moduleID !== false){
			//Insert settings
			$sSql = "INSERT INTO moduleSettings (moduleID,settingName) VALUES ($moduleID,'recipients')";
			$db->exec($sSql);
			$sSql = "INSERT INTO moduleSettings (moduleID,settingName) VALUES ($moduleID,'returnText')";
			$db->exec($sSql);
        	$sSql = "INSERT INTO moduleSettings (moduleID,settingName) VALUES ($moduleID,'msgsubject')";
			$db->exec($sSql);
        	$sSql = "INSERT INTO moduleSettings (moduleID,settingName) VALUES ($moduleID,'siteName')";
			$db->exec($sSql);
			$sSql = "INSERT INTO moduleSettings (moduleID,settingName) VALUES ($moduleID,'senderMail')";
			$db->exec($sSql);
            $sSql = "INSERT INTO moduleSettings (moduleID,settingName) VALUES ($moduleID,'mailSystem')";
			$db->exec($sSql);
			//Create Repository - SAMPLE
			$sSql = "CREATE TABLE repository_$moduleID (recID int auto_increment not null primary key,recName varchar(75) not null,recEmail varchar(100) not null)";
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
            return true;
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