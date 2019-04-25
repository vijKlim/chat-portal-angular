/**
 * jquery plugin showing profile user hover element
 * Created by proger on 19.10.2015.
 */


(function($, window, undefined){

    var tooltip;
    var arrow;
    var arrowWidth;
    var arrowHeight;
    var content;
    var win;
    var prCache = {};

    function getState(el){
        var s = {};
        var elementHeight = el.outerHeight();
        var elementWidth = el.outerWidth();
        var offset = el.offset();
        s.height = tooltip.outerHeight(true);
        s.width = tooltip.outerWidth(true);
        s.offset = {};
        s.offset.top = offset.top;
        s.offset.left = offset.left;
        s.offset.right = s.offset.left + elementWidth;
        s.offset.bottom = s.offset.top + elementHeight;
        s.offset.hCenter = s.offset.left + Math.floor(elementWidth / 2);
        s.offset.vCenter = s.offset.top + Math.floor(elementHeight / 2);
        s.css = {};
        s.on = {};
        s.off = {};
        s.arrow = {};
        return s;
    };

    function getCenter(s, horizontal) {
        if(horizontal) {
            return s.offset.hCenter + (-s.width / 2);
        } else {
            return s.offset.vCenter + (-s.height / 2);
        }
    };
    function getArrowOffset(s, dir) {
        if(dir == 'left' || dir == 'right') {
            s.arrow.top = Math.floor((s.height / 2) - (arrowHeight / 2));
        } else {
            s.arrow.left = Math.floor((s.width / 2) - (arrowWidth / 2));
        }
        s.arrow[getInverseDirection(dir)] = -arrowHeight/2;
    };
    function getInverseDirection(dir) {
        switch(dir) {
            case 'top':    return 'bottom';
            case 'bottom': return 'top';
            case 'left':   return 'right';
            case 'right':  return 'left';
        }
    };
    function boundTooltip(s,direction) {
        var bound, alternate;
        switch(direction) {
            case 'top':
                bound = win.scrollTop();
                if(s.offset.top - s.height < bound) alternate = 'bottom';
                s.on.top  = s.offset.top - s.height;
                s.off.top = s.on.top;
                s.css.top = s.on.top;
                s.css.left = getCenter(s, true);
                break;
            case 'left':
                bound = win.scrollLeft();
                if(s.offset.left - s.width < bound) alternate = 'right';
                s.on.left  = s.offset.left - s.width;
                s.off.left = s.on.left;
                s.css.top  = getCenter(s, false);
                s.css.left = s.on.left;
                break;
            case 'bottom':
                bound = win.scrollTop() + win.height();
                if(s.offset.bottom + s.height > bound) alternate = 'top';
                s.on.top   = s.offset.bottom;
                s.off.top  = s.offset.bottom;
                s.css.top  = s.on.top;
                s.css.left = getCenter(s, true);
                break;
            case 'right':
                bound = win.scrollLeft() + win.width();
                if(s.offset.right + s.width > bound) alternate = 'left';
                s.on.left  = s.offset.right;
                s.off.left = s.on.left;
                s.css.left = s.on.left;
                s.css.top = getCenter(s, false);
                break;
        }
        if(alternate && !s.over) {
            s.over = true;
            boundTooltip(s, alternate);
        } else {
            s.direction = direction;
            getArrowOffset(s, direction);
            //checkSlide(s, direction);
        }
    };

    function formatDate(timestamp,delimeter, timeToo){
        var formattedDate = null;
        var timestampInMilliSeconds = timestamp*1000;
        var date = new Date(timestampInMilliSeconds);

        var day = (date.getDate() < 10 ? '0' : '') + date.getDate();
        var month = (date.getMonth() < 9 ? '0' : '') + (date.getMonth() + 1);
        var year = date.getFullYear();

        var hours = ((date.getHours() % 12 || 12) < 10 ? '0' : '') + (date.getHours() % 12 || 12);
        var minutes = (date.getMinutes() < 10 ? '0' : '') + date.getMinutes();
        var meridiem = (date.getHours() >= 12) ? 'pm' : 'am';
        if(timeToo == undefined){
            formattedDate = day + delimeter + month + delimeter + year;
        }else{
            formattedDate = day + delimeter + month + delimeter + year + ' at ' + hours + ':' + minutes + ' ' + meridiem;
        }
        return formattedDate;
    }

    var getControls = function(profileId)
    {
        var btns = null;
        $.ajax({
            type: 'GET',
            url: '/pfront/profiles/getChatBtns/?invitedId='+profileId,
            async: false,
            success: function(response){
                btns = response;
            }
        });
        return $(btns);
        //return  $('<div id="profile-controls">'
        //+'<a><span class="profile-icon profile-write-icon">Написать</span></a>'
        //+'<a><span class="profile-icon profile-add-icon">Добавить</span></a>'
        //+'</div>');
    };

    function setContent(data, controls) {
        var main = null;
        var profileHref = '';
        if(parseInt(data.client.is_public))
            profileHref = '<a href="/profile/'+data.client.id+'">'+data.client.name+ '</a>';
        else
            profileHref = '<span>'+data.client.name+'</span>';

        if(data.client.type == 'customer'){
            console.log(data.client.dateadded);
            var dateadded = data.client.dateadded > 0 ? formatDate( data.client.dateadded,'.') : '';
            var html = '<div id="user-profile-header">'+
                '<span class="profile_logo">'+
                '<img src="'+data.client.logo+'" alt="">'+
                '</span><div class="profile_name">'+profileHref+'</div>'+
                '</div>'+

                '<div id="user-profile-main">'+
                '<span style="font-weight:600;">'+data.statusClient+'</span>'+
                '<span>Место в общем рейтенге <strong style="color:#005797;">'+data.client.position_rating+'</strong></span>'+
                '<span>Отзывы о товарах <a href="/profile/'+data.client.id+'/feed/type/price/"><strong style="color:#005797;">'+data.feedPrice+'</strong></a>, о магазинах <a href="/profile/'+data.client.id+'/feed/type/shop/"><strong style="color:#005797;">'+data.feedShop+'</strong></a></span>';

            if(parseInt(data.reviews))
                html += '<span>Обзоров <a href="/profile/'+data.client.id+'/reviews/"><strong style="color:#005797;">'+data.reviews+'</strong></a></span>';

            if(dateadded)
                html +=   '<span>Зарегистрирован '+dateadded+'</span>';

            html+= '<span></span></div>';
            main      = $(html);
        }else{
            var html = '<div id="user-profile-header">'+
            '<span class="profile_logo">'+
            '<img src="'+data.client.logo+'" alt="">'+
            '</span><div class="profile_name">'+profileHref+ '</div></div>';
            main = $(html);
        }
        content.html('');
        controls.prependTo(content);
        main.prependTo(content);

        bindFancyBox(new Array("#add_contact_chat","#add_msg_chat"));// функция определена в скрипте add-chat-contact.js  скрипт скомпилирован в файл all.js
    };

    function animateTooltip(s) {
        var duration = 150;
        tooltip.stop(true, true).css(s.css);
        arrow.attr('style', '').css(s.arrow);
        s.on.opacity = 1;
        s.on.left += 12;
        tooltip.css({
            "opacity":"0",
            "display":"block"
            //"left": s.on.left-45
        }).animate(s.on, {
            duration: duration,
            easing: 'easeOutQuad',
            queue: false
        });
        //tooltip.fadeIn(duration,'swing');
    }

    var fetchData = function( url, params){
        var data = {}; //можно добавить свойства по умолчанию
        if(params) data = $.extend(data, params);
        return $.ajax({
                type: 'GET',
                url: url,
                data: data,
                dataType: 'json',
                async: false

        });

    };

    var fetchProfileStat = function(profileId){
        var params = {};
        var url = '/pfront/profiles/getProfileStat/?profileId='+profileId;
        var ajaxr = undefined;
        var data = {}; //можно добавить свойства по умолчанию
        if(params) data = $.extend(data, params);

        if(prCache[url]){
            return prCache[url];
        }else{
            fetchData(url , params).done(function(response){
                if(!response.error)
                    prCache[url] = response.stats;
            });
            return prCache[url];
        }

    };

    $.fn.profileWidget = function(opts) {

        this.each(function () {
            var el = $(this);
            var profileId = el.attr('data-profile-id');
            if(!profileId) return;
            var state;
            var timer;
            var interval;
            var delay = 100;
            el.unbind('mouseenter').mouseenter(function() {
                if(tooltip.is(":visible")){
                    return;
                }
                clearInterval(interval);
                clearTimeout(timer);

                timer = setTimeout(function() {
                    var response = fetchProfileStat(profileId);
                    if(response.error){
                        return;
                    }
                    var controls = getControls(profileId);
                    setContent(response, controls);
                    state = getState(el);
                    boundTooltip(state,'right');
                    animateTooltip(state);

                }, delay);
            });
            el.unbind('mouseleave').mouseleave(function() {

                clearTimeout(timer);
                clearInterval(interval);
                interval = window.setInterval(function() {
                    if(!state) return;
                    if($(content).is(":hover") || el.is(":hover") ){
                        return;
                    }
                    tooltip.fadeOut(150);
                    state = null;
                    clearInterval(interval);

                }, 500);

            });



        });
    };

    $(document).ready(function() {
        tooltip = $('<div id="tooltip-profile" />').appendTo(document.body).css('position', 'absolute').hide();
        arrow   = $('<div class="arrow-profile" />').appendTo(tooltip);
        content = $('<div class="content-profile" />').appendTo(tooltip);
        win     = $(window);
        arrowWidth = arrow.width();
        arrowHeight = arrow.height();
        $('[profile-widget]').profileWidget();
    });

})(jQuery, window);