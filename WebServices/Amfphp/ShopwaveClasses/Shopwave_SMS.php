<?php
    //include dirname(__FILE__) . '/Shopwave_Common.php';
    //include dirname(__FILE__) . '/Shopwave_DBTier.php';    
    //define("Config", "Config.ini");
    
    class SMS
    {
	private $config = Config; 	
	private $MobileNo;
	private $Type;	
	private $smsURL;
	private $Template_Id_REG;
	private $Template_Id;
	private $Pin_Code_Purpose_Type_REG;
	
	public function __construct()
	{
	    $settings 		= parse_ini_file($this->config, TRUE);
            $User 		= $settings['SMS_setting']['sms_Username'];
	    $Pass 		= $settings['SMS_setting']['sms_Password'];
	    $Type 		= $settings['SMS_setting']['sms_Type'];
	    $Signature 		= $settings['SMS_setting']['sms_Signature'];
	    $ServiceId 		= $settings['SMS_setting']['sms_Service_Id'];
	    $ExpInterval 	= $settings['SMS_setting']['sms_Exp_Interval'];
	    $this->Template_Id_REG 		= $settings['SMS_setting']['sms_Template_Id_REG'];
	    $this->Pin_Code_Purpose_Type_REG 	= $settings['Sys_setting']['Pin_Code_Purpose_Type_REG'];	    
	    
	    $this->smsURL	= $settings['SMS_setting']['sms_URL'];
	    $this->smsURL	= str_replace('<sms_Username>',	 	'=' . $User, 		$this->smsURL);
	    $this->smsURL	= str_replace('<sms_Password>',	 	'=' . $Pass, 		$this->smsURL);
	    $this->smsURL	= str_replace('<sms_Type>', 		'=' . $Type, 		$this->smsURL);
	    $this->smsURL	= str_replace('<sms_Signature>',	'=' . $Signature, 	$this->smsURL);
	    $this->smsURL	= str_replace('<sms_Service_Id>', 	'=' . $ServiceId, 	$this->smsURL);
	}
	
	public function Sent()
	{	    
	    if (!is_null($this->MobileNo) && !empty($this->MobileNo)){
		
		$Content = $this->SmsTemplate_Get($this->Template_Id);
		
		if (!is_null($Content) && !empty($Content)){
			$Content = $this->Template_Value_Replace($this->MobileNo, $this->Type, $Content);
			$Content = str_replace(' ', '+', $Content);
		
			$smsURL  = str_replace('<Content>', '='.$Content, $this->smsURL);
			
			$arrlength = count($this->MobileNo);
			if ($arrlength == 1){			
			    $smsURL = str_replace('<Mobile_No>', '=' . $this->MobileNo, $smsURL);			
			    //$status = file_get_contents($smsURL);
			    $status = '60123910924,2582196424,200';
			    $statuses = explode(",", $status);
			    
			    if ($statuses[2] == 200){
			        $this->SMS_Sent_Insert($this->MobileNo, str_replace('+', ' ', $Content), $statuses[2], $statuses[1]);
			    }
			}
			else{
			    for($x=0; $x<$arrlength; $x++){
				$smsURL = str_replace('<Mobile_No>', '=' . $this->MobileNo[$x], $smsURL);				
				$status = file_get_contents($smsURL);
			    }    
			}

			return $status;
		    }
		else{
			return ErrCode::vfPin_Template_not_Found;
		    }
		}
	    else{
		    return ErrCode::vfPin_Invalid_Phone;
		}
	    
	    return $details;
	}
	public function MobileNo($arrValue)
        {
            $this->MobileNo = $arrValue;
        }
	    	
	public function Type($pType)
        {
	    $this->Type = $pType;
	    switch ($pType) {
		case 'REGISTRATION':
		    $this->Template_Id = $this->Template_Id_REG;
		    break;
	    }	
        }
    
	public function SMS_Sent_Insert($Mobile_No, $Content, $Response_Code, $MsgId)
	{
	    try{
	
		$db = $GLOBALS['DBTier'];
		$Updated_By = 'admin';
		$Created_By = 'admin';
		if ($Response_Code = 200) {$Sent_Status = 'SUCCESS';}
		else {$Sent_Status = 'FAILED';}
		$sql = 'INSERT INTO SMS_Sent (Mobile_No,Content,Sent_Status,MsgId,Response_Code,Updated_By,Updated_Date,Created_By,Created_Date)
			VALUES(:Mobile_No,:Content,:Sent_Status,:MsgId,:Response_Code,:Updated_By,now(),:Created_By,now())';
		$db->query($sql);
		$db->bind(':Mobile_No', $Mobile_No);
		$db->bind(':Content', $Content);
		$db->bind(':Sent_Status', $Sent_Status);
		$db->bind(':MsgId', $MsgId);
		$db->bind(':Response_Code', $Response_Code);
		$db->bind(':Updated_By', $Updated_By);
		$db->bind(':Created_By', $Created_By);
		$result = $db->execute();// or die(mysql_error());
		
		if ($result == 1){
		    $New = 'NEW';
		    $sql = 'Update Pin_Code set MsgId = :MsgId, Response_Code = :Response_Code where Mobile_No = :Mobile_No and Status = :Status';
		    $db->query($sql);
		    $db->bind(':MsgId', $MsgId);
		    $db->bind(':Response_Code', $Response_Code);
		    $db->bind(':Mobile_No', $Mobile_No);
		    $db->bind(':Status', $New);
		    $result = $db->execute();
		}
		//$result = $db->single();					
		
		//return $result['Content'];	
	    }
	    catch(PDOException $e)
	    {
		return '';
	    }
	}
	public function SmsTemplate_Get($Template_Id)
	{
	    
	    try{
	
		$db = $GLOBALS['DBTier'];	
		$sql = 'select Content from SMS_template where SMS_Template_Id = :SMS_Template_Id';	
		$db->query($sql);
		$db->bind(':SMS_Template_Id', $Template_Id);	    
		$db->execute();// or die(mysql_error());
		$result = $db->single();
			
		//$db->destroy();
		
		return $result['Content'];	
	    }
	    catch(PDOException $e)
	    {
		return '';
	    }
	}
	
	public function Template_Value_Replace($Mobile_No, $Type, $Content)
	{
	    switch($Type){
		case 'REGISTRATION':
		    $Pin = $this->Pin_Code_Generate($Mobile_No, $this->Pin_Code_Purpose_Type_REG);
		    if (!is_null($Pin)){
			$Content = str_replace('[PIN]', $Pin, $Content);			
		    }
	    }
	    
	    return $Content;
	}
	
	public function Pin_Code_Generate($Mobile_No, $Type)
	{
            $db = $GLOBALS['DBTier'];		 
	    $db->query("CALL Pin_Code_Create(:Mobile_No, :Type, @Pin)");
	  
	    $db->bind(':Mobile_No', $Mobile_No);
            $db->bind(':Type', $Type);
            $db->execute();
            
            $db->query('select @Pin');
            $result = $db->single();
            //$db->destroy();
            
            return $result['@Pin'];
        }
        

//	<<Checking used>>
//
//	public function Pin_Code_Get($Login_Id, $Mobile_No, &$Auth_Token)
//        {        
//            try{
//                $Err = new ErrCode();
//                $db = new DBTier();
//		// Temp commented, pending for sms service; $sql = 'SELECT COUNT(*) FROM Pin_Code where Mobile_No = :Mobile_No';
//                $sql = 'select  member_session.Mobile_No, member.login_id, member_session.Auth_Token
//			from member_session
//			left join Member
//			on member.Member_Id = member_session.member_id
//			where Member.Login_Id = :Login_Id and member_session.Mobile_No = :Mobile_No';
//                $dbCon = $db->getConnection();
//		$stmt = $dbCon->prepare($sql);
//                $stmt->bindParam(':Login_Id', $Login_Id, PDO::PARAM_STR);
//		$stmt->bindParam(':Mobile_No', $Mobile_No, PDO::PARAM_STR);
//                $stmt->execute();// or die(mysql_error());
//		$result = $stmt->fetch();
//                $dbCon = null;		
//                  
//                if (!is_null($result['Auth_Token'])){
//                    $Auth_Token = $result['Auth_Token'];
//                    return ErrCode::Gen_Success;
//                }
//                else{
//                    return ErrCode::Gen_RecordNotFound;
//                }                		
//            }
//            catch(PDOException $e){
//		return $e->getMessage();
//	    }
//        }
        
	//public function Pin_Send($Mobile_No, $Purpose_Type)
	//{
	//    
	//}	
    }   
    
?>