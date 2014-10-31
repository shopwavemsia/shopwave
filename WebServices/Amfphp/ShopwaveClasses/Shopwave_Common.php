<?php

class Util
{           
    function logging($msg, $is_start=false){
	$content = date('Y/m/d H:i:s').' : '.$msg."\r\n";

	if ($is_start)
		$content = "--------------------------------------------------\r\n".                
                $content;        
        
	//file_put_contents ($GLOBALS['opts']['logs_dir'].date('Ymd').".log", $content, FILE_APPEND);
        return file_put_contents (dirname(__FILE__).'/logs/'.date('Ymd').".log", $content, FILE_APPEND);        
    }
    
    public function Debugger_Log()
    {
        return (boolean)$this->GetSetting('Sys_setting','Enable_Debugger_Log');
    }
    
    public function GetSetting($sector, $var)
    {
        $settings = parse_ini_file('Config.ini',1);
        
        return $settings[$sector][$var];
    }
    
    function printini($file, $sector, $var)
    {        
        $file= $file.".ini";
        $is=array();
        $is= parse_ini_file($file, true);
        //trim($is);
        //$is = array_map('trim',$is);
        if(is_array($is) && file_exists($file))
        {
            return trim($is[$sector][$var]);
        }else{
            return "error";
        }
        
    }

    
}

?>