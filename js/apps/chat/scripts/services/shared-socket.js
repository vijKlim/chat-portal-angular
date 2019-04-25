/**
 * Created by proger on 25.04.2016.
 */

chatApp.factory('socketService', ['chatConfig', function(chatConfig) {
    var service = {};

    service.callbackStack = [];
    service.events = {
        popen    :            'open',//событие открытия коннекта
        pclose   :            'close',//событие закрытия коннекта
        ponline  :            'online',//событие генерируется чтоб уведомить пользователя чата о том кто в онлайне
        pmessage :            'message',//событие о новом  сообщении
        iwriting:             'info-writing-msg',//событие информирует что собеседник отвечает на сообщение
        sinfoAcceptedChat:    'support-inform-accepted-chat',
        sinfoNotAcceptedChat: 'support-inform-not-accepted-chat',
        sforwardManager:      'support-forward-manager',
        sforwardAccount:      'support-forward-account',
        sinformUserForwardAccount: 'inform-user-forward-account',
        sinformStateSupportContactsManagement: 'inform-state-support-contacts-management',
        sreportChat: 'report-chat',
        gopen: 'gopen'
    };
    service.connect = function(strConnect) {
        if(this.ws) { return; }

        this.ws = new Khmerload.SharedWebSocket({
            url: strConnect,
            message: function(message) {

            for(index in service.callbackStack)
                service.callbackStack[index](message);
            }
        });
    };
    var sessId = $.cookie("PHPSESSID") ? $.cookie("PHPSESSID") : $.cookie("tatet");
    //wss
    service._wurl = "wss://tatet.ua:8009/hash="+sessId+"&userId="+ chatConfig.myId+"&muacc="+chatConfig.muacc;//change on 8004 and wss on ws and domen on ip 89.184.65.55
    //ws
    //service._wurl = "ws://89.184.65.55:8004/hash="+sessId+"&userId="+ chatConfig.myId+"&muacc="+chatConfig.muacc;
    service.createConnect = function(){
        this.connect(this._wurl);
    };

    service.send = function(message) {
        this.ws.send(message);
    };


    service.subscribe = function(callback) {
        this.callbackStack.push(callback);
    };

    return service;
}])
;
