/**
 * Created by proger on 01.02.2016.
 */

chatApp.factory('online', ['$rootScope','socketService','chatService','msgsManager','support', function($rootScope,socketService,chatService,msgsManager,support) {

    var service = {};
    service.onlineUsers = [];
    service.allOnlineCommunity = [];
    service.countAllOnlines = 0;
    service.countOnlineUsers = 0;

    service.setOnline = function(users){
        var scope = this;
        users.forEach(function(user){
            if(scope.onlineUsers.indexOf(''+user) < 0)
                scope.onlineUsers.push(user);

        });

        scope.countOnlineUsers = scope.onlineUsers.length;
        //console.log('Users  is online');
        $rootScope.$apply();
    };
    service.unsetOnline = function(user){
        if(this.onlineUsers.indexOf(user) >= 0)
            this.onlineUsers.splice(this.onlineUsers.indexOf(user),1);
        this.countOnlineUsers = this.onlineUsers.length;
        $rootScope.$apply();
        //console.log('User  is offline',user);
    };
    service.getOnlineUsers = function(users){
        var scope = this;
        var onlines = [];
        if(typeof users != 'undefined'){
            users.forEach(function(user){
                if(scope.onlineUsers.indexOf(''+user) >= 0)
                    onlines.push(user);

            });
        }

        return onlines;
    };

    service.setAllOnlineCommunity = function(onlines){
        service.countAllOnlines = Object.keys(onlines).length;
        service.allOnlineCommunity = onlines;
        $rootScope.$apply();
    };


    service.init = function(accs){

        socketService.subscribe(function(data){
            if(chatService.isJson(data))
                data = angular.fromJson(data);


            switch (data.event) {
                case socketService.events.popen:
                    var users = [data.userId];
                    service.setOnline(users);
                    break;

                case socketService.events.pclose:
                    var user = data.userId;
                    service.unsetOnline(user);
                    break;
                case socketService.events.ponline:
                    var users = data.onlineUsers;
                    service.setOnline(users);
                    //console.log('onlineInfo',data);
                    support.setAnchoredManagerContacts(data.supportInfo);//только для саппорта, информация о контактах саппорта которые на данный момент участвуют в беседах и под какими менеджероми поддержка контактов
                    break;
                case socketService.events.pmessage:

                    msgsManager.addToNotify({
                        accountId: data.accountId,
                        contactId: data.contactId,
                        typeContact: data.typeContact,
                        msg: data.msg,
                        isInternal: 0,
                        childChat: typeof data.childChat == 'undefined' ? 0 : 1
                    });
                    break;
                case socketService.events.iwriting:
                    msgsManager.notifyWritingMsg(data.iswriting);
                    break;
                case socketService.events.sinfoAcceptedChat:
                    //добавить в трид саппорт сообщение
                    //console.log('first sinfoAcceptedChat');
                    support.pushMsgInThread(data.msgdata);
                    //установить менеджера беседы контакта
                    //contact is object = porperties id, type
                    support.assignTalkManager(data.manager, data.accountId, data.contact);
                    break;
                case socketService.events.sinfoNotAcceptedChat:
                    //добавить в трид саппорт сообщение
                    //console.log('first sinfoNotAcceptedChat');
                    support.pushMsgInThread(data.msgdata);
                    //установить менеджера беседы контакта
                    //contact is object = porperties id, type
                    support.unassignTalkManager(data.manager, data.accountId, data.contact);
                    break;
                case socketService.events.sforwardManager:
                    //console.log('forward manager');
                    //support.pushMsgInThread(data.msgdata);
                    msgsManager.addToNotify({
                        accountId: data.accountId,
                        contactId: data.contact.id,
                        typeContact: data.contact.type,
                        msg: data.msgdata,
                        isInternal: 1
                    });
                    //console.log('forward manager data',data);
                    support.changeTalkManager(data.prevManager, data.nextManager, data.accountId, data.contact);
                    break;
                case socketService.events.sforwardAccount:
                    //console.log('forward account');
                    //support.pushMsgInThread(data.msg);
                    msgsManager.addToNotify({
                        accountId: data.nextAccountId,
                        contactId: data.nextContact.id,
                        typeContact: data.nextContact.type,
                        msg: data.msg,
                        isInternal: 1
                    });
                    support.addCopiesInThread(data.nextContact, data.copies);
                    //console.log('forward account data',data);
                    support.changeTalkAccount(data.prevAccountId, data.nextAccountId, data.prevManager, data.nextManager, data.prevContact, data.nextContact);
                    break;
                case socketService.events.sinformUserForwardAccount:
                    //console.log('infor user about forward account');
                    //chatService.getInfoContact(data.currentAccountId,data.contact.id,data.contact.type).success(function(profile){
                    //
                    //    var msg = 'для продолжения общения по данной тематике вам необходимо'+
                    //        ' обратиться <a href="" ng-click="goToDialog('+data.nextContact.id+',\'alone\')">'+data.nextAccount.name+'</a>';
                    //    var msgObj = msgsManager.formatMsg(data.nextContact.id,data.nextContact.type,msg,data.nextAccount.id,data.receiverId);
                    //    var obj = {
                    //        contactId: data.contact.id,
                    //        contact_type: data.contact.type,
                    //        name:profile.name,
                    //        logo:profile.logo,
                    //        msg: msgObj,
                    //        msgtxt: msg,
                    //        count_msgs:1,
                    //        isInternal:0
                    //    };
                    //    msgsManager.addAmountNewMsgs(data.receiverId,obj,true);
                    //});

                    var msg = 'для продолжения общения по данной тематике вам необходимо' +
                        ' обратиться <a href="" ng-click="goToDialog(' + data.nextContact.id + ',\'alone\')">' + data.nextAccount.name + '</a>';
                    var msgObj = msgsManager.formatMsg(data.nextContact.id, data.nextContact.type, msg, data.nextAccount.id, data.receiverId);
                    msgsManager.addToNotify({
                        accountId: data.currentAccountId,
                        contactId: data.contact.id,
                        typeContact: data.contact.type,
                        msg: msgObj,
                        isInternal: 1
                    });
                    break;
                case socketService.events.sinformStateSupportContactsManagement:
                    //console.log('inform State Support Contacts Management',data);
                    support.setAnchoredManagerContacts(data.scm);
                    break;
                case socketService.events.sreportChat:
                    //console.log(data.onlines,'report');
                    service.setAllOnlineCommunity(data.onlines);
                    break;
            }
        });


        socketService.createConnect();
    };


    return service;
}]);