<?php    
    include dirname(__FILE__) . '/Shopwave_DBTier.php';
    include dirname(__FILE__) . '/Shopwave_ErrCode.php';
    
    Global $gDBcon;
    
    class Member
    {	        		              		
        public function MemberCount_Get($Login_Id, $Mobile_No)
        {
	    try
	    {
				$sql = 'SELECT (SELECT COUNT(*)
                        FROM MEMBER WHERE Login_Id= :Login_Id) NumOfLogIn,
                        (SELECT COUNT(*) FROM MEMBER WHERE Mobile_No= :Mobile_No) NumOfMobile';
		
		//$dbCon = $db->getConnection();
		$gDBcon->query($sql);
		$db->bind(':Login_Id', $Login_Id, PDO::PARAM_STR);
		$db->bind(':Mobile_No', $Mobile_No, PDO::PARAM_STR);
		$db->execute();// or die(mysql_error());
		$result = $db->single();
		
		$db->destroy();
		
		if ($result['NumOfLogIn'] > $result['NumOfMobile']){
		    Return 'C3';
		}
		else if ($result['NumOfLogIn'] < $result['NumOfMobile']){
		    return 'C2';
		}
		else if ($result['NumOfLogIn']==1 && $result['NumOfMobile']==1){
		    return 'C1';
		}
		else{
		    return 'C4';
		}

	    }
	    catch(PDOException $e)
	    {
		return $e->getMessage();
	    }	   	   
	}
        
        // Member
        public function Member_Verify_AuthToken($Login_Id, $Mobile_no, $Auth_Token, &$Member_Session_Id)
        {            
            $db = new DBTier();
            
            // Mandatory field checks
            if ($Login_Id == null) return ERR_xx
                else 
                        
            $sql = 'select member_session_id, member_session.Mobile_No, member.login_id, member_session.Auth_Token, member.status,
                case when member_session.expired_token_datetime < now() then 1
                else 0 END as ExpiredToken                
                from member_session
                left join Member
                	on member.Member_Id = member_session.member_id                     
                where Member.Login_Id=:Login_Id and Member_session.Mobile_No= :Mobile_No';
                     
            $db->query($sql);
            $db->bind(':Login_Id', $Login_Id, PDO::PARAM_STR);
	    $db->bind(':Mobile_No', $Mobile_no, PDO::PARAM_STR);	    
            $db->execute();
            $result = $db->single();
            
            $db->destroy();            
            $Member_Session_Id = $result['member_session_id'];
           
            if (!is_null($result['status'])){
                
                if ($result['ExpiredToken'] == 1){
                    return ErrCode:: vfAuth_ExpiredToken;
                }
                elseif ($result['status'] == 'Blacklisted'){
                    return ErrCode:: vfAuth_BlackListedMember;
                }
                elseif ($result['Auth_Token'] != $Auth_Token){                   
                    return ErrCode:: vfAuth_TokenMismatch;
                }
                else{
                    return ErrCode::Gen_Success;
                }
            }
            else{
                return ErrCode:: vfAuth_MemberNotFound;
            }  
           
        }
               
        public function Member_Request_New_Auth_Token($Member_Session_Id, $Login_Id, $Mobile_No, &$Auth_Token)
        {
            $db = new DBTier();        
            $Space = ' ';            
            $Sql = 'Update member_session set Auth_Token = MD5(CONCAT_WS(:Space,CONCAT_WS(:Space, :Login_Id, :Mobile_No), now()))
                    where Member_Session_Id=:Member_Session_Id and Mobile_No=:Mobile_No';
                    
	    $db->query($Sql);
            $db->bind(':Space', $Space, PDO::PARAM_STR);
            $db->bind(':Login_Id', $Login_Id, PDO::PARAM_STR);
            $db->bind(':Member_Session_Id', $Member_Session_Id, PDO::PARAM_STR);
            $db->bind(':Mobile_No', $Mobile_No, PDO::PARAM_STR);
	    
            $stmt_Result = $db->execute();
            $db->destroy();
            
            if ($stmt_Result == 1){                
                $Auth_Token = $this->Member_Session_Get_Auth_Token($Member_Session_Id);
                return ErrCode::Gen_Success;
            }
            else {
                return ErrCode::Gen_RecordNotFound;
            }
        }
               
        public function Member_Session_Get_Auth_Token($Member_Session_Id)
        {
            $db = new DBTier();
            $Sql = "Select Auth_Token from
                    Member_Session where Member_Session_Id = :Member_Session_Id";
            
            $db->query($Sql);
            $db->bind(':Member_Session_Id', $Member_Session_Id);
            $db->execute();
            $Row_Auth_Token = $db->single();
            $Auth_Token = $Row_Auth_Token['Auth_Token'];
            
            $db->destroy();
            
            return $Auth_Token;
        }
               
        public function Member_Get_MemberId($Login_Id)
        {
            $db = new DBTier();
            $Sql = 'select member_id from member where Login_Id=:Login_Id';
            $db->query($Sql);
            $db->bind(':Login_Id',$Login_Id);
	    $db->execute();            
            
            $result = $db->single();
            $db->destroy();
            
	    return $result["member_id"];                       
        }
        
              
        public function UpdateMember($Login_Id,$Password,$PasswordSalt,$Email,$Gender,$First_Name,
                                           $Last_Name,$Race,$Religion,$DOB,$Mobile_No,$Identity_No,$Nationality,$Prefer_Lang,$Is_Complete,
                                           $Status,$Social_ID_Ref,$Social_Type,$Social_Access_Token,
                                           $Mo_Brand,$Mo_Model,$Mo_IMEI,$Mo_OS,$Mo_w_BLE,$Mo_w_NFC,$Mo_w_GPS,$Mo_w_Camera,&$LastInsertId)
        {
            try
            {
                $Member_Id = $this->Member_Get_MemberId($Login_Id);
                
                $db = new DBTier();
		
                $Sql_Mem = 'Update member set mobile_no=:mobile_no where Login_Id=:Login_Id';
                $Sql_Mem_Ses = 'Update member_session set mobile_no=:mobile_no where Member_Id=:Member_Id';
                
		$db->beginTransaction();
		
		$db->query($Sql_Mem);                
                $db->bind(':Login_Id', $Login_Id, PDO::PARAM_STR);
                $db->bind(':mobile_no', $Mobile_No, PDO::PARAM_STR);
	        $Status = $db->execute();
                
                if($Status == 1){
                    $db->query($Sql_Mem_Ses);                
                    $db->bind(':Member_Id', $Member_Id, PDO::PARAM_STR);
                    $db->bind(':mobile_no', $Mobile_No, PDO::PARAM_STR);
                    $Status = $db->execute();
                    
                    $db->endTransaction();                
                }
                else {
                    $db->cancelTransaction();
                }
                
                $db->destroy();
		
                if ($Status == 1){
                    return ErrCode::Gen_Success;  
                }
		else{
                    return ErrCode::Gen_RecordNotFound;
                }
            }
            catch(PDOException $e)
	    {		
		//$dbcon->rollBack();
		return 'error=='.$e->getMessage();
	    }
        }
	public function RegisterNewMember($Login_Id,$Password,$PasswordSalt,$Email,$Gender,$First_Name,
                                           $Last_Name,$Race,$Religion,$DOB,$Mobile_No,$Identity_No,$Nationality,$Prefer_Lang,$Is_Complete,
                                           $Status,$Social_ID_Ref,$Social_Type,$Social_Access_Token,
                                           $Mo_Brand,$Mo_Model,$Mo_IMEI,$Mo_OS,$Mo_w_BLE,$Mo_w_NFC,$Mo_w_GPS,$Mo_w_Camera,&$LastInsertId)
	{
	    try
	    {
		$Member_Code = $Login_Id;
                $Updated_By = 'admin';
		$Created_By = 'admin';
                
                $db = new DBTier();                
		
                $Sql_Insert_Member = 'INSERT INTO Member(Login_Id, Member_Code, Password, PasswordSalt, Email, Gender, First_Name, Last_Name, Race, Religion, DOB, Mobile_No, Joined_Date, Identity_No, Nationality, Prefer_Lang, Is_Complete, Status, Updated_By, Updated_Date, Created_By, Created_Date)
                VALUES (:Login_Id,:Member_Code,:Password,:PasswordSalt,:Email,:Gender,:First_Name,:Last_Name,:Race,:Religion,:DOB,:Mobile_No,now(),
                :Identity_No,:Nationality,:Prefer_Lang,:Is_Complete,:Status,:Updated_By,now(),:Created_By,now())';
		
		$db->beginTransaction();		
		$db->query($Sql_Insert_Member);		
                
		$db->bind(':Login_Id', $Login_Id, PDO::PARAM_STR);
		$db->bind(':Member_Code', $Member_Code, PDO::PARAM_STR);
		$db->bind(':Password', $Password, PDO::PARAM_STR);
		$db->bind(':PasswordSalt', $PasswordSalt, PDO::PARAM_STR);
		$db->bind(':Email', $Email, PDO::PARAM_STR);
		$db->bind(':Gender', $Gender, PDO::PARAM_STR);
		$db->bind(':First_Name', $First_Name, PDO::PARAM_STR);
		$db->bind(':Last_Name', $Last_Name, PDO::PARAM_STR);
		$db->bind(':Race', $Race, PDO::PARAM_STR);
		$db->bind(':Religion', $Religion, PDO::PARAM_STR);
		$db->bind(':DOB', $DOB, PDO::PARAM_STR);
		$db->bind(':Mobile_No', $Mobile_No, PDO::PARAM_STR);
		$db->bind(':Identity_No', $Identity_No, PDO::PARAM_STR);
		$db->bind(':Nationality', $Nationality, PDO::PARAM_STR);
		$db->bind(':Prefer_Lang', $Prefer_Lang, PDO::PARAM_STR);
		$db->bind(':Is_Complete', $Is_Complete, PDO::PARAM_INT);
		$db->bind(':Status', $Status, PDO::PARAM_STR);
		$db->bind(':Updated_By', $Updated_By, PDO::PARAM_STR);
		$db->bind(':Created_By', $Created_By, PDO::PARAM_STR);
	       		
		$stmt_Result = $db->execute();
		$LastInsertId = $db->lastInsertId();

		IF (!is_null($LastInsertId) && !empty($LastInsertId))
		{
		    $Space = ' ';		                        
		    $Sql_Insert_Member_S = 'INSERT INTO Member_Session(Member_Id,Mobile_No,Social_ID_Ref,Social_Type, Social_Access_Token, Status, Last_Activity_DateTime,
                        Auth_Token, Auth_Token_Generated_Datetime, Expired_Token_Datetime,
                        Mo_Brand, Mo_Model, Mo_IMEI, Mo_OS, Mo_w_BLE, Mo_w_NFC, Mo_w_GPS, Mo_w_Camera,
                        Updated_By, Updated_Date, Created_By, Created_Date)
                        VALUES (:Member_Id, :Mobile_No, :Social_ID_Ref, :Social_Type, :Social_Access_Token, :Status, now(),
                        MD5(CONCAT_WS(:Space,CONCAT_WS(:Space, :Login_Id, :Mobile_No), now())), now(), date_add(now(), interval 99 year),
                        :Mo_Brand, :Mo_Model, :Mo_IMEI, :Mo_OS, :Mo_w_BLE, :Mo_w_NFC, :Mo_w_GPS, :Mo_w_Camera, :Updated_By, now(), :Created_By, now());';
		    
		    $stmt_S = $db->query($Sql_Insert_Member_S);
		  
		    $Member_Id = $LastInsertId;		    
		    $db->bind(':Space', $Space, PDO::PARAM_STR);
		    $db->bind(':Login_Id', $Login_Id, PDO::PARAM_STR);
		    $db->bind(':Member_Id', $Member_Id, PDO::PARAM_INT);
		    $db->bind(':Mobile_No', $Mobile_No, PDO::PARAM_STR);
		    $db->bind(':Social_ID_Ref', $Social_ID_Ref, PDO::PARAM_STR);
		    $db->bind(':Social_Type', $Social_Type, PDO::PARAM_STR);
		    $db->bind(':Social_Access_Token', $Social_Access_Token, PDO::PARAM_STR);
		    $db->bind(':Status', $Status, PDO::PARAM_STR);
		    $db->bind(':Mo_Brand', $Mo_Brand, PDO::PARAM_STR);
		    $db->bind(':Mo_Model', $Mo_Model, PDO::PARAM_STR);
		    $db->bind(':Mo_IMEI', $Mo_IMEI, PDO::PARAM_STR);
		    $db->bind(':Mo_OS', $Mo_OS, PDO::PARAM_STR);
		    $db->bind(':Mo_w_BLE', $Mo_w_BLE, PDO::PARAM_INT);
		    $db->bind(':Mo_w_NFC', $Mo_w_NFC, PDO::PARAM_INT);
		    $db->bind(':Mo_w_GPS', $Mo_w_GPS, PDO::PARAM_INT);
		    $db->bind(':Mo_w_Camera', $Mo_w_Camera, PDO::PARAM_INT);
		    $db->bind(':Updated_By', $Updated_By, PDO::PARAM_STR);
		    $db->bind(':Created_By', $Created_By, PDO::PARAM_STR);
		    
		    $stmt_S_Result = $db->execute();                    
		    $LastInsert_Member_Session_Id = $db->lastInsertId();
                    
                    $db->query("Select Auth_Token from
                                Member_Session
                                where Member_Session_Id = :LastInsert_Member_Session_Id");
                    
                    $db->bind(':LastInsert_Member_Session_Id',$LastInsert_Member_Session_Id);
                    $db->execute();
                    $Row_Auth_Token = $db->single();
                    $Auth_Token = $Row_Auth_Token['Auth_Token'];                    

		}
		
		$db->endTransaction();
		$db->destroy();;
		
                if ($stmt_Result == 1){
                    return ErrCode::Gen_Success;
                }
		 else{
                    return ErrCode::Gen_RecordNotFound;
                }
		
	    }
	    catch(PDOException $e)
	    {	
		$db->cancelTransaction();
		return 'error=='.$e->getMessage();
	    }
	}

        public function Auth_Token_Get($Login_Id, $Mobile_No, &$Auth_Token)
	{
            
            try{
                
                $db = new DBTier();
		// Temp off, pending for sms service; $sql = 'SELECT COUNT(*) FROM Pin_Code where Mobile_No = :Mobile_No';
                $sql = 'select  member_session.Mobile_No, member.login_id, member_session.Auth_Token
                        from member_session
                        left join Member
                            on member.Member_Id = member_session.member_id
                        where Member.Login_Id = :Login_Id and member_session.Mobile_No = :Mobile_No';                
		$db->query($sql);
                $db->bind(':Login_Id', $Login_Id, PDO::PARAM_STR);
		$db->bind(':Mobile_No', $Mobile_No, PDO::PARAM_STR);
                $db->execute();
		$result = $db->single();
                $db->destroy();		
                  
                if (!is_null($result['Auth_Token'])){
                    $Auth_Token = $result['Auth_Token'];
                    return ErrCode::Gen_Success;
                }
                else{
                    return ErrCode::Gen_RecordNotFound;
                }                		
            }
            catch(PDOException $e){
		return $e->getMessage();
	    }
        }

        public function _Member_Get_MemberId($Login_Id)
        {
            $obj = new DBTier();
            // return $obj->gets();
            $obj->query('select member_id from member where Login_Id=:Login_Id;');
            $obj->bind(':Login_Id', $Login_Id);
           
            $obj->execute();
            $rows=$obj->single();//(PDO::FETCH_ASSOC);// resultset();                       
            $obj->destroy();
                  
            return $rows["member_id"];            
        }
        

//      <<Testing used>>
        public function Pin_Code_Generate1($Mobile_No, $Type){
            $input = 5;
            $mydb = new PDO("mysql:host=10.100.100.108;dbname=Shopwave", "root", "shopwave123");
            $proc = $mydb->prepare("CALL Pin_Code_Create(:Mobile_No, :Type, @Pin)");
            $proc->bindParam(':Mobile_No',$Mobile_No);
            $proc->bindParam(':Type',$Type);
            $proc->execute();
            $proc->closeCursor();
            
            $output = $mydb->query("select @Pin")->fetch(PDO::FETCH_ASSOC);
            return $output; 
        }
        
        public function Pin_Code_Generate($Mobile_No, $Type){
            $db = new DBTier();	 
	    $db->query("CALL Pin_Code_Create(:Mobile_No, :Type, @Pin)");
	  
	    $db->bind(':Mobile_No', $Mobile_No);
            $db->bind(':Type', $Type);
            $db->execute();
            
            $db->query('select @Pin');
            $result = $db->single();
            $db->destroy();
            
            return $result['@Pin'];
        
            //$stmt->bindParam(3, $Pin, PDO::PARAM_STR|PDO::PARAM_OUTPUT, 32);
	    
	    // call the stored procedure
	    //$stmt->execute();
	    
	    
	//    $item = array();
	//    while($r = $stmt->fetchAll(PDO::FETCH_OBJ)){
	//	$item[] = $r;
	//    }
	    
	    //$dbCon = null;
	    //return $Pin;
        }
        
	
    }
    
    
?>