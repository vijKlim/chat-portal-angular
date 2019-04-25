/**
 * Created by proger on 06.10.2015.
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
        if(service.ws) { return; }
//var webSocket = window.WebSocket || window.MozWebSocket;
        var ws = new WebSocket(strConnect);

        ws.onopen = function() {
            //service.callback("Succeeded to open a connection");
            for(index in service.callbackStack)
                service.callbackStack[index]("Succeeded to open a connection");
        };

        ws.onclose = function() {
            ws.close();
        };

        ws.onerror = function() {
            //service.callback("Failed to open a connection");
            for(index in service.callbackStack)
                service.callbackStack[index]("Failed to open a connection");
        };

        ws.onmessage = function(message) {
            //service.callback(message.data);
            for(index in service.callbackStack)
                service.callbackStack[index](message.data);
        };

        this.ws = ws;
    };
    //ws
    console.log('');
    service._wurl = "ws://89.184.65.55:8004/hash="+$.cookie("PHPSESSID")+"&userId="+ chatConfig.myId+"&muacc="+chatConfig.muacc;
    service.check = function(){
        if(!this.ws || this.ws.readyState == 3){

            this.connect(this._wurl);
        }
    };

    service.createConnect = function(){

        this.connect(this._wurl);
        //console.log('create connect websocket');
        if(parseInt(chatConfig.sp) == 1)
            setInterval(this.check, 5 * 60 * 1000 );
    };

    service.send = function(message) {
        this.ws.send(message);
    };


    service.subscribe = function(callback) {
        // service.callback = callback;
        this.callbackStack.push(callback);
    };

    return service;
}])
;//.factory('socketExService', ['socketService','chatConfig', function(socketService,chatConfig) {
//        var extended = angular.extend(socketService, {});
//
//        extended.createConnect = function(){
//            this.connect("ws://89.184.65.55:8004/hash="+hash+"&userId="+ chatConfig.myId);
//            //console.log('create connect websocket');
//        };
//        return extended;
//}]);