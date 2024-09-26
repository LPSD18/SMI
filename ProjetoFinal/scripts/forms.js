var xmlHttp;

function GetXmlHttpObject() {
  try {
    return new ActiveXObject("Msxml2.XMLHTTP");
  } catch(e) {} // Internet Explorer
  try {
    return new ActiveXObject("Microsoft.XMLHTTP");
  } catch(e) {} // Internet Explorer
  try {
    return new XMLHttpRequest();
  } catch(e) {} // Firefox, Opera 8.0+, Safari
  alert("XMLHttpRequest not supported");
  return null;
}

function CheckFieldAgainstFilter(fieldId, filter) {
  var fieldValue = document.getElementById( fieldId ).value;
  
  if ( filter.test( fieldValue ) ) {
    return fieldValue;
  }
  
  return null;
}

// Captcha has changed
function CheckCaptcha() {
  var b = CheckFieldAgainstFilter( 'captcha', filterCaptcha);
  
  var captchaState = document.getElementById( 'captchaState' );

  if ( b!==null ) {
    captchaState.innerHTML = "Captcha format ok";
    captchaState.style.color = '#00FF00';
    return true;
  }
  else {
    captchaState.innerHTML = "Invalid captcha format";
    captchaState.style.color = '#FF0000';
    return false;
  }
}

// User name has changed
function CheckUserName() {
  var userNameValue = CheckFieldAgainstFilter( 'userName', filterUserName);
	
  if ( userNameValue!==null ) {
    // Preparing the arguments to see if the user name already exists
    var args = "userName="+userNameValue;
  
    // With HTTP GET method
    xmlHttp = GetXmlHttpObject();
    xmlHttp.open("GET", "existsUserName.php?"+args, true);
    xmlHttp.onreadystatechange=CheckUserNameHandleReply;
    xmlHttp.send(null);
    return true;
  }
  else {
    var userName = document.getElementById( 'userName' );
    userName.focus();
    
    var userNameState = document.getElementById( 'userNameState' );
    userNameState.innerHTML = "Invalid user name format";
    userNameState.style.color = '#FF0000';
    return false;
  }
}

function CheckUserNameHandleReply() {	
  if( xmlHttp.readyState === 4 ) {
    var userNameState = document.getElementById( 'userNameState' );
    
    var result = xmlHttp.responseText.split(";");
    
    userNameState.innerHTML = result[1];
    userNameState.style.color = result[0];
  }
}

function CheckPassword(passwordId) {
  var b = CheckFieldAgainstFilter(passwordId, filterPassword);
  
  var passwordState = document.getElementById( passwordId + 'State' );
  
  if ( b!==null ) {
    passwordState.innerHTML = "Password ok";
    passwordState.style.color = '#00FF00';
    return true;
  }
  else {
    passwordState.innerHTML = "Invalid password format";
    passwordState.style.color = '#FF0000';
    return false;
  }
}

// Passwords has changed
function CheckPasswords() {
  var bMain, bSecondary;
          
  bMain = CheckPassword( 'password1' );
  bSecondary = CheckPassword( 'password2' );

  if ( bMain===true && bSecondary===true ) {
    var mainPassworValue = document.getElementById( 'password1' ).value;
    var secondaryPasswordValue = document.getElementById( 'password2' ).value;
    
    var mainPasswordState = document.getElementById( 'password1State' );
    var secondaryPasswordState = document.getElementById( 'password2State' );
    
    secondaryPasswordState.innerHTML = "";

    if ( secondaryPasswordValue===mainPassworValue ) {
      mainPasswordState.innerHTML = "Passwords matches";
      mainPasswordState.style.color = '#00FF00';
      return true;
    }
    else {
      mainPasswordState.innerHTML = "Passwords don't match";
      mainPasswordState.style.color = '#FF0000';
      return false;
    }
  }
  return false;
}

// E-mail has changed
function CheckEmail() {
  var emailValue = CheckFieldAgainstFilter( 'email', filterEmail);
  
  var emailState = document.getElementById( 'emailState' );
  
  if ( emailValue!==null ) {
    var args = "email="+emailValue;
  
    // With HTTP GET method
    xmlHttp = GetXmlHttpObject();
    xmlHttp.open("GET", "existsEmail.php?"+args, true);
    xmlHttp.onreadystatechange=CheckEmailHandleReply;
    xmlHttp.send(null);
    return true;
  }
  else {
    emailState.innerHTML = "Invalid e-mail format";
    emailState.style.color = '#FF0000';
    return false;
  }
}

function CheckEmailHandleReply() {	
  if( xmlHttp.readyState === 4 ) {
    var emailState = document.getElementById( 'emailState' );
    
    var result = xmlHttp.responseText.split(";");
    
    emailState.innerHTML = result[1];
    emailState.style.color = result[0];
  }
}

function FormRegisterUserValidator() {
  if ( CheckCaptcha()===false ) {
    alert( "Invalid captch format" );
    document.getElementById( 'captcha' ).focus();
    return false;
  }
  
  if ( CheckUserName()===false ) {
    alert( "Invalid user format" );
    document.getElementById( 'userName' ).focus();
    return false;
  }
  
  if ( CheckPasswords()===false ) {
    alert( "Invalid password format" );
    document.getElementById( 'password1' ).focus();
    return false;
  }
  
  if ( CheckEmail()===false ) {
    alert( "Invalid email format" );
    document.getElementById( 'email' ).focus();
    return false;
  }
  
  return true;
}

function getContent(scriptName) {
  xmlHttp = GetXmlHttpObject();
  xmlHttp.open("GET", scriptName, true);
  xmlHttp.onreadystatechange = getContentHandleReply;
  xmlHttp.send(null);
}

function getContentHandleReply() {
  if (xmlHttp.readyState === 4) {
    document.getElementById( "contentDiv" ).innerHTML = xmlHttp.responseText;
  }
}