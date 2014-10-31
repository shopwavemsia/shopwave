<?php
Class ErrCode
{    
    //General
    const Gen_Success               = '00000';  //Success.
    const Gen_Failed                = '00001';  //Failed.
    const Gen_RecordNotFound        = '00002';  //Record not found.    
    const Gen_DB_Connection_Failed  = '00090';  //DB Connection failed.
    const Gen_Error_Occur           = '00099';  //Try Catch Error Occur
    
    //Verify Auth Token
    const vfAuth_ExpiredToken       = '01001';  //Expired Token.
    const vfAuth_BlackListedMember  = '01002';  //Blackedlisted Member.    
    const vfAuth_TokenMismatch      = '01003';  //Token not match with Login_Id & Mobile_No
    const vfAuth_MemberNotFound     = '01004';  //Member record not found by Login_Id & Mobile_No
    const vfAuth_Failed_to_Get_Token= '01005';
    //Verify Member
    const vfMem_Mandatory_Login     = '02001';  //Login_Id is mandatory
    const vfMem_Mandatory_Email     = '02002';  //Email is mandatory
    const vfMem_Mandatory_Gender    = '02003';  //Gender is mandatory
    const vfMem_Mandatory_FName     = '02004';  //First_Name is mandatory
    const vfMem_Mandatory_LName     = '02005';  //Last_Name is mandtory
    const vfMem_MemberId_Not_Found  = '02006';  //Member_Id not found
    const vfMem_Registeration_Failed= '02007';

    //Verify SMS Pin
    const vfPin_Invalid_Phone       = '03001';
    const vfPin_Template_not_Found  = '03002';
    const vfPin_Invalid_Pin         = '03003';  //Invalid Pin
    const vfPin_Expired_Pin         = '03004';
    const vfPin_Update_Pin_Failed   = '03005';
}
?>

