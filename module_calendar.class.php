<?php

class module_calendar extends module	{
	const version = 1.0;
	const moduleDesc = "Calendar and Events Module";
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
				$this->delEvent();
			}
		}else{
			$arrSettings = parent::getModuleParams();
			foreach($arrSettings as $row){
				$key = $row['settingName'];
				$$key = $row['settingVal'];
			}
			//Get a list of Quotes we have in the system
			$sSql = "SELECT nID,eventTitle from repository_{$modID} ORDER by nID ASC";
			$stm = $db->query($sSql);
			$quoteList = "";
			if($stm){
				while($row = $stm->fetch(PDO::FETCH_ASSOC)){
					$EventList .= "<div><table><tr><td>{$row['eventTitle']}</td><td>&nbsp;<a href='javascript:void(0)' onclick='editEvent({$row['nID']})'>EDIT</a>&nbsp;<a href='javascript:void(0)' onclick='deleteEvent({$row['nID']})'>DELETE</a></td></tr></table></div>";
				}
			}
			if($EventList == ""){
				$EventList = "<div>No Event Found</div>";
			}
			//We're showing the main screen
			$form = $this->EventForm();
			$out = <<<END
	<script type='text/javascript'>
	function saveConfig(){
		$('btnSave').disabled = true;
		var formObj = $('formConfig');
		var url = '/admin/admin_ajax_modules.php';
		new Ajax.Request(url,{
			   parameters: formObj.serialize(),
			   onSuccess: function(transport, json){
				  var data = transport.responseText.evalJSON(true);
				  if(data['error'] == 0){
					  $('configFormStat').innerHTML = "<span class='message'>" + data['message'] + "</span>";
					  $('btnSave').disabled = false;
				  }else{
					  $('configFormStat').innerHTML = "<span class='error'>" + data['message'] + "</span>";
					  $('btnSave').disabled = false;
				  }
			  },
			  method:'post'
		});
	}
	function saveEvent(){
		$('saveEventButton').disabled = true;
		var formObj = $('EventAdmin');
		tinyMCE.triggerSave();
		var url = '/admin/admin_ajax_modules.php';
		new Ajax.Request(url,{
			   parameters: formObj.serialize(),
			   onSuccess: function(transport, json){
				  var data = transport.responseText.evalJSON(true);
				  if(data['error'] == 0){
					  $('formStat').innerHTML = "<span class='message'>" + data['message'] + "</span>";
					  $('saveEventButton').disabled = false;
					  updateEvent();
					  $('EventAdmin').reset();
					  msgFader('formStat');
				  }else{
					  $('formStat').innerHTML = data['message'];
					  $('saveEventButton').disabled = false;
				  }
			  },
			  method:'post'
		});
	}
	function editEvent(id){
		var url = '/admin/admin_ajax_modules.php';
		new Ajax.Request(url,{
			   parameters: "act=edit&mact=EventEdit&id=" + id,
			   onSuccess: function(transport, json){
				  var data = transport.responseText.evalJSON(true);
				  if(data['error'] == 0){
					  $('formDisplay').innerHTML = data['message'];
					  tinyMCE.init({
						  mode : "textareas",
						  theme : "advanced",
						  skin : "daedalus",
						  plugins : "moduleselect,safari,contextmenu,advimage,advlink,inlinepopups,media,paste,table,searchreplace,imagemanager,filemanager,mdsmselect",
						  theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect",
						  theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,tablecontrols",
						  theme_advanced_buttons3 : "link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor,media,moduleselect,mdsmselect",
						  theme_advanced_buttons4 : "",
						  theme_advanced_toolbar_location : "top",
						  theme_advanced_toolbar_align : "left",
						  editor_deselector : "mceNoEditor",
						  content_css : "//css/style.css,/admin/template/css/editordefault.css",
						  theme_advanced_styles : "Page Headline=pgheadline;Page Subhead=pgsubheader;Body Headline=bodyheadline;Body Subhead=bodysubhead",
						  external_image_list_url : "ajax/getImageList.php",
						  external_link_list_url : "ajax/getFileList.php",
						  convert_urls : false,
						  forced_root_block : "",
						  force_br_newlines : true,
						  force_p_newlines : false
					  });
				  }else{
					  alert(data['message']);
				  }
			  },
			  method:'post'
		});
	}
	function deleteEvent(id){
		var msg = "Are you sure you want to delete this article?";
		if(confirm(msg)){
		var url = '/admin/admin_ajax_modules.php';
		new Ajax.Request(url,{
			   parameters: "act=delete&mact=EventDelete&id=" + id,
			   onSuccess: function(transport, json){
				  var data = transport.responseText.evalJSON(true);
				  if(data['error'] == 0){
					  $('formStat').innerHTML = data['message'];
					  $('saveEventButton').disabled = false;
					  setTimeout("updateEvent()",1000);
					  msgFader('formStat');
				  }else{
					  $('formStat').innerHTML = data['message'];
					  $('saveEventButton').disabled = false;
				  }
			  },
			  method:'post'
		});
		}else{
			return false;
		}
	}
	function updateEvent(){
	var url = '/admin/admin_ajax_modules.php';
	new Ajax.Request(url,{
		   parameters: "mact=getEvent",
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
<div id='modq_qlist'>$EventList</div>
<div id='formDisplay'>
$form
</div>
 <form id='formConfig'>
	<fieldset>
    	<legend>Configuration</legend>
        <div id='configFormStat'></div>
        <div>
        	<label>Number of Articles to Show</label>
            <input type='text' name='mod_eventsToShow' value='$eventsToShow'/>
        </div>
        <div>
        	<label>Date Format (PHP date() function)</label>
            <input type='text' name='mod_dateDisplayFormat' value='$dateDisplayFormat'/>
        </div>
        <div>
        	<input type='button' id='btnSave' value='save' onclick='saveConfig();return false;'/>
            <input type='hidden' name='mact' value='adminSave' />
        </div>
    </fieldset>
</form>
END;
	}
    return $out;
}
	public function EventForm($nid=null){
    	global $db;
        global $dp;
        $error = 0;
        $modID = parent::getModuleID();
        if($nid != null){
        	//Lookup the selected Event article and populate the form
            $sSql = "SELECT * from repository_{$modID} WHERE nID = $nid";
            $stm = $db->query($sSql);
        	$row = $stm->fetch(PDO::FETCH_ASSOC);
            foreach($row as $key => $val){
            	$$key = $dp->makeDirty($val);
            }
            $updateKey = "<input type='hidden' name='nID' value='$nID'/>";
            $button = "<input type='button' id='saveEventButton' class='form_button' value='Update Article' onclick='saveEvent()'/>";
            $dateSelect = $this->getDateSelect($eventDate);
        }else{
        	$updateKey = "";
            $button = "<input type='button' id='saveEventButton' class='form_button' value='Save Article' onclick='saveEvent()'/>";
            $dateSelect = $this->getDateSelect();
        }
        
        $out = <<<EOF
<form id='EventAdmin'>
	<fieldset>
    <div id='formStat'></div>
    <div>
		<label>Event Title</label>
		<input type='text' name='mod_eventTitle' value='$eventTitle' />
	</div>
    <div>
		<label>Event Link</label>
		<input type='text' id='fTarget' name='mod_eventLink' value='$eventLink' /> <input type='button' value='Browse Files' onclick="browseFiles('txtLink')"/>
	</div>
    <div>
		<label>Event Description</label>
		<textarea id='taText' name='mod_eventDesc' cols='40' rows='6'>$eventDesc</textarea>
	</div>
    $dateSelect
    <div>
    	$updateKey
        $button
    </div>
    <input type='hidden' name='mact' value='EventSave'/>
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
	public function EventSave(){
    	global $db;
        global $dp;
        $error = 0;
        $bUpdate = false;
        $modID = parent::getModuleID();
        $eventTitle = $_REQUEST['mod_eventTitle'];
        $eventDesc = $_REQUEST['mod_eventDesc'];
        $eventLink = $_REQUEST['mod_eventLink'];
        $eventDate = $_REQUEST['mod_year'] . "-". $_REQUEST['mod_month'] . "-" . $_REQUEST['mod_day'];
        if(isset($_REQUEST['nID']) && $_REQUEST['nID'] != ""){
        	$bUpdate = true;
        }
        if($bUpdate === false){
        	$sSql = "INSERT INTO repository_{$modID} (eventTitle,eventDesc,eventLink,eventDate) VALUES ('$eventTitle','$eventDesc','$eventLink','$eventDate')";
            $act = "Saved";
        }else{
        	$sSql = "UPDATE repository_{$modID} SET eventTitle = \"$eventTitle\", eventDesc = \"$eventDesc\",eventLink = \"$eventLink\", eventDate = \"$eventDate\" WHERE nID = {$_REQUEST['nID']}";
            $act = "Updated";
        }
        $db->exec($sSql);
        $arrError = $db->errorInfo();
        if($arrError[2] != ""){
            $error = 1;
            $message = $arrError[2];
        }else{
        	$message = "Article $act!";
        }
        //Save Module Configuration
		$arr = array('error'=>$error,'message'=>$message,'sql'=>$sSql);
		echo json_encode($arr);
    }
    public function EventEdit(){
    	global $dp;
        $nID = $_REQUEST['id'];
        $out = $this->EventForm($nID);
        if($out !== false){
        	$arr = array("error"=>0,"message"=>$out);
            echo json_encode($arr);
        }else{
        	$arr = array("error"=>0,"message"=>"Unable to load article.");
            echo json_encode($arr);
        }
    }
    public function EventDelete(){
    	global $db;
        $error = 0;
        $modID = parent::getModuleID();
    	$nID = $_REQUEST['id'];
        
        $sSql = "DELETE FROM repository_{$modID} WHERE nID = $nID";
        $db->exec($sSql);
        $arrError = $db->errorInfo();
        if($arrError[2] != ""){
            $error = 1;
            $message = "Error: " . $arrError[2];
        }else{
        	$message = "Article Deleted!";
        }
        //Save Module Configuration
		$arr = array('error'=>$error,'message'=>$message,'sql'=>$sSql);
		echo json_encode($arr);
    }
    public function getEvent(){
    	global $db;
        $error = 0;
        $modID = parent::getModuleID();
		$sSql = "SELECT nID,eventTitle,eventDesc,eventLink,eventDate from repository_{$modID} ORDER by nID ASC";
        $stm = $db->prepare($sSql);
        $stm->execute();
        while($row = $stm->fetch(PDO::FETCH_ASSOC)){
            $EventList .= "<div><table><tr><td>{$row['eventTitle']}</td><td>&nbsp;<a href='javascript:void(0)' onclick='editEvent({$row['nID']})'>EDIT</a>&nbsp;<a href='javascript:void(0)' onclick='deleteEvent({$row['nID']})'>DELETE</a></td></tr></table></div>";
        }
        if($EventList == ""){
            $EventList = "<div>No Event Found</div>";
        }
       
		$arr = array('error'=>$error,'message'=>$EventList,'sql'=>$sSql);
		echo json_encode($arr);
    }
    
	public function display($type = null){
    	global $db;
        $modID = parent::getModuleID();
        //Javascript function to update the view
        $out = <<<END
<script type='text/javascript'>
var Eventlist;
function getEventArticle(n){
	//Load the current contents into a variable so we don't need a separate ajax call after viewing an article
    Eventlist = $('#Event').html();
	$.getJSON('/includes/moduleapi.php',"id=$modID&nid="+n,function(data){
			  if(data.error == 0){
				  $('#Event').html(data.message);
			  }
	  });
}
function restorList(){
	$('#Event').html(Eventlist);
}
function getArchive(){
	$('#EventItems').html("Loading... please wait.");
	var arch = $('#selArchive').val();
	$.getJSON('/includes/moduleapi.php',"id=$modID&archive="+arch,function(data){
			  if(data.error == 0){
				  $('#EventItems').html(data.message);
			  }
	  });
}
</script>
END;
		
        parent::addToHeader($out);
        $sEvent = $this->getEventList();
        $archiveSel = $this->getArchives();
		$Event = "<div id='Event'>". $archiveSel . "<div id='EventItems'>" . $sEvent . "</div></div>";
        return $Event;
	}
    public function update(){
    	if(isset($_REQUEST['nid']) && $_REQUEST['nid'] != ""){
        	$out = $this->returnEvent($_REQUEST['nid']);
        }
        if(isset($_REQUEST['archive']) && $_REQUEST['archive'] != ""){
        	$out = $this->getEventList($_REQUEST['archive']);
        }
    	return $out;
    }
    private function returnEvent($n){
    	global $db;
        global $dp;
        $arrSettings = parent::getModuleParams();
        foreach($arrSettings as $row){
            $key = $row['settingName'];
            $$key = $row['settingVal'];
        }
        $Event = "";
        $sSql = "SELECT nID,eventTitle,eventDesc,eventLink,eventDate from repository_{$this->getModuleID()} WHERE nID = $n LIMIT 1";
        $stm = $db->query($sSql);
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        $pubDate = date($dateDisplayFormat,strtotime($row['eventDate']));
        $article = $dp->makeDirty($row['eventDesc']);
        $title = $dp->makeDirty($row['eventTitle']);
        $article = <<<EOF
<div id='article'>
<div class='mod_Eventpress_vtitle'>$title</div>
<div class='mod_Eventpress_vdate'>Published $pubDate</div>
<div class='mod_Eventpress_varticle'>$article</div>
<div class='mod_Eventpress_vback'><a href='javascript:void(0)' onclick='restorList()'>Back to Article List</a></div>
</div>
EOF;
        return $article;
    }
    private function getArchives($y=null){
    	global $db;
        if($y == null){
        	$y = date("Y");
        }
        $sSql = "SELECT DISTINCT(date_format(eventDate,'%Y')) as year FROM repository_{$this->getModuleID()}";
        $stm = $db->query($sSql);
        $opts = "<option></option>";
        $bSelected = false;
        while($row = $stm->fetch(PDO::FETCH_ASSOC)){
        	if($row['year'] == $y){
        		$opts .= "<option value='{$row['year']}' selected='selected'>{$row['year']}</option>";
                $bSelected = true;
        	}else{
        		$opts .= "<option value='{$row['year']}'>{$row['year']}</option>";
        	}
        }
        if($bSelected !== false){
        	$opts .= "<option value='all'>view all</option>";
        }else{
        	$opts .= "<option value='all' selected='selected'>view all</option>";
        }
        $out = "<div class='mod_Eventpress_archives'>Archives &nbsp;<select name='archiveYear' id='selArchive' onchange='getArchive()'>$opts</select></div>";
        return $out;
    }
    private function getEventList($y=null){
    	global $db;
        global $dp;
    	$arrSettings = parent::getModuleParams();
        foreach($arrSettings as $row){
            $key = $row['settingName'];
            $$key = $row['settingVal'];
        }
    	if($y == null){
        	//Get the first # records
            $sSql = "SELECT nID,eventTitle,eventDate,eventLink from repository_{$this->getModuleID()} ORDER BY eventDate DESC LIMIT $eventsToShow";
        }else{
        	if(is_numeric($y)){
        		$sSql = "SELECT nID,eventTitle,eventDate,eventLink from repository_{$this->getModuleID()} WHERE date_format(eventDate,'%Y') = $y ORDER BY eventDate DESC LIMIT $eventsToShow";
            }else{
            	//Get all
                $bAll = true;
                $sSql = "SELECT nID,eventTitle,eventDate,eventLink from repository_{$this->getModuleID()} WHERE 1 ORDER BY eventDate DESC";
            }
        }
        $stm = $db->query($sSql);
        if($stm){
        	while($row = $stm->fetch(PDO::FETCH_ASSOC)){
            	if($row['eventLink'] != ""){
            		$list .= "<div class='mod_pEventpress_articlelistitem'><div class='mod_Eventpress_datelist'>" . date($dateDisplayFormat,strtotime($row['eventDate'])) . "</div><div class='mod_Eventpress_title'><a href='{$row['eventLink']}'>{$row['eventTitle']}</a></div></div>";
                }else{
                	$list .= "<div class='mod_pEventpress_articlelistitem'><div class='mod_Eventpress_datelist'>" . date($dateDisplayFormat,strtotime($row['eventDate'])) . "</div><div class='mod_Eventpress_title'><a href='javascript:void(0)' onclick='getEventArticle({$row['nID']})'>{$row['eventTitle']}</a></div></div>";
                }
            }
        }
        return $list;
    }
	public function install(){
		//Install This module
		global $db;
		$moduleID = parent::instanceCreate(__FILE__);
		if($moduleID !== false){
			//Insert settings
			$sSql = "INSERT INTO moduleSettings (moduleID,settingName,settingVal) VALUES ($moduleID,'eventsToShow','10')";
			$db->exec($sSql);
            $sSql = "INSERT INTO moduleSettings (moduleID,settingName,settingVal) VALUES ($moduleID,'dateDisplayFormat','j M Y')";
			$db->exec($sSql);

			//Create Repository - SAMPLE
			$sSql = "CREATE TABLE repository_$moduleID (nID int auto_increment not null primary key,eventTitle varchar(255) not null,eventDesc text not null,eventLink varchar(255), eventDate datetime)";
			$db->exec($sSql);
            $dbErr = $db->errorInfo();
			if($dbErr[2] != ""){
				//Install failed - Uninstall
                parent::setError("Installation Failed: {$dbErr[2]}");
				/*$this->uninstall($moduleID);*/
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
    private function getDateSelect($date = null){
			if($date != ""){
				list($thisdate,$thistime) = split(" ",$date,2);
				list($syear,$smonth,$sday) = split("-",$thisdate);
			}else{
				$syear = date("Y");
				$smonth = date("m");
				$sday = date("d");
			}
			$y = date("Y");
			$arrYears = array();
			for($i=$y;$i>$y-7;$i--){
				$arrYears[] = $i;
			}
			$arrMonths = array("01"=>"January","02"=>"February","03"=>"March","04"=>"April","05"=>"May","06"=>"June","07"=>"July","08"=>"August","09"=>"September","10"=>"October","11"=>"November","12"=>"December");
			$arrDays = array();
			for($i=1;$i<32;$i++){
				$i = sprintf("%02d",$i);
				$arrDays[] = $i;
			}

			//Build Month Select
			$monSelect = "<SELECT NAME='mod_month'>";
			foreach($arrMonths as $key=>$val){
				if($key == $smonth){
					$monSelect .= "<option value=\"$key\" SELECTED>$val</option>";
				}else{
					$monSelect .= "<option value=\"$key\">$val</option>";
				}
			}
			$monSelect .= "</select>";

			//Build Day Select
			$daySelect = "<SELECT NAME='mod_day'>";
			foreach($arrDays as $key){
				if($key == $sday){
					$daySelect .= "<option value=\"$key\" SELECTED>$key</option>";
				}else{
					$daySelect .= "<option value=\"$key\">$key</option>";
				}
			}
			$daySelect .= "</select>";

			//Build Year Select
			$yearSelect = "<SELECT NAME='mod_year'>";
			foreach($arrYears as $key){
				if($key == $syear){
					$yearSelect .= "<option value=\"$key\" SELECTED>$key</option>";
				}else{
					$yearSelect .= "<option value=\"$key\">$key</option>";
				}
			}
			$yearSelect .= "</select>";
			$content = "<div><label>Event Date:</label> $monSelect $daySelect $yearSelect</div>";
			return $content;
	}
}