/**
 * Created by proger on 21.10.2015.
 */
function addContact(el,accountId,id) {
    $.ajax({
        type    : 'GET',
        url     : '/profile/my/createAloneContact',
        data    : { 'accountId':accountId,'invitedId': id},
        dataType: 'JSON',
        success : function (data) {
            if ( !data.error )
                $(el).removeClass('profile-add-icon').addClass('profile-already-add-icon').html(data.msg);
            else {
                var content = $(el).html();
                $(el).html(data.msg);
                setTimeout(function () {
                    $(el).html(content);
                }, 3000);
            }
        },
        error   : function (data) {
            console.log('error');
        }
    });
}


function isInt(value) {
    return !isNaN(value) && (function(x) { return (x | 0) === x; })(parseFloat(value))
}

function bindFancyBox(elements)
{
    elements.forEach(function(element, index){
        $(element).fancybox({
            'href'         : '/profile/auth/getAuthForm/?ref='+encodeURI(window.location.href),
            'padding'      : 0,
            'border'       : 0,
            'width'        : 620,
            'height'       : 260,
            'titleShow'    : false,
            'transitionIn' : 'elastic',
            'transitionOut': 'elastic',
            'easingIn'     : 'easeOutBack',
            'easingOut'    : 'easeInBack'
        });
    });
    //if ( $("#add_contact_chat").length )
    //    $("#add_contact_chat").fancybox({
    //        'padding'      : 0,
    //        'border'       : 0,
    //        'width'        : 620,
    //        'height'       : 260,
    //        'titleShow'    : false,
    //        'transitionIn' : 'elastic',
    //        'transitionOut': 'elastic',
    //        'easingIn'     : 'easeOutBack',
    //        'easingOut'    : 'easeInBack'
    //    });

    //if ( $("#add_msg_chat").length )
    //    $("#add_msg_chat").fancybox({
    //        'padding'      : 0,
    //        'border'       : 0,
    //        'width'        : 620,
    //        'height'       : 260,
    //        'titleShow'    : false,
    //        'transitionIn' : 'elastic',
    //        'transitionOut': 'elastic',
    //        'easingIn'     : 'easeOutBack',
    //        'easingOut'    : 'easeInBack'
    //    });

}
