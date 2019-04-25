/**
 * Created by proger on 06.10.2015.
 */

/**
 * Старая версия, не используется. Новая версия: chat/services/onlineShop.js and chat/directives/chat.js name = showOnlineShop
  */

var ConnectShop,connectShop;
$(function(){
    connectShop = new ConnectShop();
});

ConnectShop = function(){
   this.init();
};

ConnectShop.prototype = {
    constructor: ConnectShop,
    scopeChatApp: {},
    init: function(){
        var self = this;
        this.initCache();
        this.domEvents();
        this.createPreload();
    },
    initCache: function(){
        this.cache = {
            body: $('body'),
            btnConnect: $('#connect-shop'),
            btnDefaultConnect: $('#btn-question-shop')
        };
        this.idShop = this.cache.btnConnect.data('shopid');
        this.defaultConnectHref = this.cache.btnDefaultConnect.attr('href');
        this.idBtnConnect = '#connect-shop';
        this.idBtnConnectManager = '.connect_manager';
    },
    domEvents : function(){
        var self = this;
        this.cache.body.on('click',this.idBtnConnect,function(e){
            self.scopeChatApp = angular.element(document.getElementById("echat")).scope();
            self.prepareTypeConnect();
        });
        this.cache.body.on('click', this.idBtnConnectManager, function(e){
            var id = $(this).data('manager');
            $.fancybox.close();
            self.connectManager(id);
        });
    },
    createPreload: function(){
        this.preloader = $('<div>Loading...</div>');
        this.preloader.css({'display':'none','bottom':'130px','right':0, 'backgroundColor':'#29ABE2','textAlign':'center','width':'100px','height':'25px','position':'fixed','color':'white'});
        this.cache.body.append(this.preloader);
    },
    prepareTypeConnect: function(){
        var shopManagersInfo = this.getManagersShop();
        var onlinedManagers = this.getOnlineManagers(shopManagersInfo);
        if(onlinedManagers == undefined) onlinedManagers = 0;

        if(onlinedManagers.length == 1){
            this.connectManager(onlinedManagers[0].id);
        }else if(onlinedManagers.length > 0){
            this.showActiveManager(onlinedManagers);
        }else{
            this.openDialogOffline();
        }
    },
    connectManager: function(id){
        if(this.createRelation(id)){
            this.openDialogOnline(id);
        }else{
            this.openDialogOffline();
        }
    },
    getManagersShop: function(){
        var self = this;
        var result = null;
        $.ajax({
            type: "POST",
            url: "/profile/my/getIdManagerShop?shopId="+self.idShop,
            async:false,
            dataType:'json',
            success: function(msg){
                if(!msg.error){
                    result = msg.list;
                }
                else
                    result = null;
            }
        });
        return result;
    },
    getOnlineManagers: function(managers)
    {
        var online = [];
        //this.scopeChatApp.chatService.onlineUsers.push('1038');
        for(index in managers)
        {
            if(this.scopeChatApp.chatService.onlineUsers.indexOf(managers[index].id) >= 0 )
                online.push(managers[index]);
        }
        return online;
    },
    showActiveManager: function(list)
    {
        var listLI = '';
        for(index in list)
        {
            listLI += '<li><img width="40" height="40" src="'+list[index].logo+'"><a style="margin-left: 15px;" href="javascript:void(0);" class="connect_manager" data-manager="'+list[index].id+'">'+list[index].name+'</a></li>';
        }
        var html = '<div style="margin:0 auto;width:430px;text-align: center;">'
            +'<h5>Выберите менеджера для связи</h5>'
            +'<ul style="list-style: none;margin:0 0 15px 0;padding:0;font-size:12px;">'
            +listLI
            +'</ul>'
            +'</div>';

        $.fancybox(
            html,
            {
                'autoDimensions'    : false,
                'width'             : 450,
                'height'            : 'auto',
                'transitionIn'      : 'none',
                'transitionOut'     : 'none'
            }
        ).trigger('click');
    },
    openDialogOnline: function(id)
    {
        //setInterval(this.preload(), 300);
        var self = this;
        this.scopeChatApp.$apply(function () {
            self.scopeChatApp.showDialog(id);
        });
    },
    //preload: function(){
    //    if($('.echat_dialog').css('display') == 'none')
    //        this.preloader.css('display','block');
    //    else
    //        this.preloader.css('display','block');
    //
    //},
    openDialogOffline: function ()
    {
        var self = this;
        $('#btn-question-shop').fancybox({
            'href'         : self.defaultConnectHref,
            'padding'      : 0,
            'border'       : 0,
            'width'        : 450,
            'height'       : 500,
            'titleShow'    : false,
            'transitionIn' : 'elastic',
            'transitionOut': 'elastic',
            'easingIn'     : 'easeOutBack',
            'easingOut'    : 'easeInBack'
        }).click();
    },
    createRelation: function(id)
    {
        var self = this;
        var result = false;
        $.ajax({
            type: "POST",
            url: "/profile/my/addChatContact/?invitedId="+id,
            dataType:'json',
            async: false,
            success: function(msg){
                if(msg.error)
                    result = false;
                else
                    result = true;
            }
        });
        return result;
    }

};
