<?php
Class ErrCode
{    
    //General
    const Gen_Success               = '00000';  //Success.
    const Gen_Failed                = '00001';  //Failed.
    const Gen_RecordNotFound        = '00002';  //Record not found.
    
    //Verify Auth Token
    const vfAuth_ExpiredToken       = '01001';  //Expired Token.
    const vfAuth_BlackListedMember  = '01002';  //Blackedlisted Member.    
    const vfAuth_TokenMismatch      = '01003';  //Token not match with Login_Id & Mobile_No
    const vfAuth_MemberNotFound     = '01004';  //Member record not found by Login_Id & Mobile_No
    
    //Verify Member
    const vfMem_MLogin              = '02001';  //Login_Id is mandatory
    const vfMem_MEmail              = '02002';  //Email is mandatory
    const vfMem_MGender             = '02003';  //Gender is mandatory
    const vfMem_MFName              = '02004';  //First_Name is mandatory
    const vfMem_MLName              = '02005';  //Last_Name is mandtory
    
    //Verify SMS Pin
    const vfPin_InvalidPhone        = '03001';
    const vfPin_Template_not_found  = '03002';
    const vfPin_InvalidPin          = '03003';  //Invalid Pin
    
}
?>

