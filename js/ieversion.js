/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


$(document).ready(function()
{
 if ( $.browser.msie ){
    if($.browser.version == '6.0')
    {   $('html').addClass('ie6');
    }
    else if($.browser.version == '7.0')
    {   $('html').addClass('ie7');
    }
    else if($.browser.version == '8.0')
    {   $('html').addClass('ie8');
    }
    else if($.browser.version == '9.0')
    {   $('html').addClass('ie9');
    }
 }
 else if ( $.browser.webkit )
 { $('html').addClass('webkit');
 }
 else if ( $.browser.mozilla )
 { $('html').addClass('mozilla');
 }
 else if ( $.browser.opera )
 { $('html').addClass('opera');
 }
});