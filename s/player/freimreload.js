 (function()
 {
  if( window.localStorage ){
    if(!localStorage.getItem('firstReLoad')){
     localStorage['firstReLoad'] = true;
     window.location.reload();
    } else {
     localStorage.removeItem('firstReLoad');
    }
  }
 })();
