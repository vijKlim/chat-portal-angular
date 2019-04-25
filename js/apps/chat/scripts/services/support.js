/**
 * Created by proger on 09.02.2016.
 */

chatApp.factory('support', ['$http', '$q','chatConfig','chatService','socketService','accountsManager','contactsManager','msgsManager',
    function($http, $q,chatConfig,chatService,socketService,accountsManager,contactsManager,msgsManager) {

        var support = {
            supportsCache:[],
            hasInternalMsg:false,
            internalMsg:null,
            getSupports: function(){
                var deferred = $q.defer();
                var scope = this;
                if(scope.supportsCache.length > 0){
                    deferred.resolve(scope.supportsCache);
                }else{
                    $http.get('/profile/'+chatConfig.myId+'/getSupports/')
                        .success(function(data) {
                            scope.supportsCache = data

                            deferred.resolve(scope.supportsCache);
                        })
                        .error(function() {
                            deferred.reject();
                        });
                }

                return deferred.promise;
            },

            createCopyMsgs: function(accountId,copyForAccountId,copyForManagerId,msgIds){
                return $http.get('/profile/'+chatConfig.myId+'/copyMsgs/',
                    {params: {accountId:accountId,copyForAccountId:copyForAccountId,copyForManagerId:copyForManagerId,msgIds:angular.toJson(msgIds)}});
            },

            //create create a service message
            //notify by websocket this account's managers about some manager start talk chat
            //закрепить менеджера за разговором
            // и уведомить всех других менеджеров этого аккаунта что этот менеджер будет вести разговор
            acceptChat: function(manager,account,contactId,contactType){
                if(chatService.isSP()) {
                    var scope = this;
                    var imsg = "Менеджер " + manager.name + " начал диалог с клиентом";
                    //console.log('internal',manager,account,contactId,contactType,imsg);
                    scope.sendInternalMsg(account, contactId, contactType, imsg).then(function (data) {
                        var data = data.data;

                        if (!data.objs[0].error) {
                            var msgdata = data.objs[0].entity;
                            msgdata.msg = chatService.prepareMsg(msgdata.msg, 1);
                            var socketObj = {
                                event: 'support-accepted-chat',
                                manager: manager,
                                accountId: account,
                                contact: {id: contactId, type: contactType},
                                msg: msgdata
                            };
                            //отправляем на сокет событие, которое уведомляет всех менеджеров этого контакта, что беседу начал манаджер
                            socketService.send(JSON.stringify(socketObj));
                        }
                    });
                }
            },
            notAcceptChat: function(manager,account,contactId,contactType){
                if(chatService.isSP()) {
                    var scope = this;
                    var imsg = "Менеджер " + manager.name + " отказался продолжать диалог с клиентом";
                    scope.sendInternalMsg(account, contactId, contactType, imsg).then(function (data) {
                        var data = data.data;

                        if (!data.objs[0].error) {
                            var msgdata = data.objs[0].entity;
                            msgdata.msg = chatService.prepareMsg(msgdata.msg, 1);
                            var socketObj = {
                                event: 'support-not-accepted-chat',
                                manager: manager,
                                accountId: account,
                                contact: {id: contactId, type: contactType},
                                msg: msgdata
                            };
                            socketService.send(JSON.stringify(socketObj));
                        }
                    });
                }
            },
            forwardManager: function(currentManager,nextManager,accountId,contactId,contactType){
                if(chatService.isSP()) {
                    var scope = this;
                    var imsg = "Менеджер " + currentManager.name + " передал диалог менеджеру "+nextManager.name;

                    scope.sendInternalMsg(accountId, contactId, contactType, imsg).then(function (data) {
                        var data = data.data;

                        if (!data.objs[0].error) {
                            var msgdata = data.objs[0].entity;
                            msgdata.msg = chatService.prepareMsg(msgdata.msg, 1);
                            var socketObj = {
                                event: 'support-forward-manager',
                                currentManager: currentManager,
                                nextManager: nextManager,
                                accountId: accountId,
                                contact: {id: contactId, type: contactType},
                                msg: msgdata
                            };

                            socketService.send(JSON.stringify(socketObj));
                        }
                    });
                }
            },
            forwardAccount: function(currentManager,nextManager,currentAccountId,nextAccount,contactId,contactType,msgs){
                if(chatService.isSP()) {
                    var scope = this;
                    var imsg = "Менеджер " + currentManager.name + " передал диалог в "+nextAccount.name+" менеджеру "+nextManager.name;

                    scope.sendInternalMsg(currentAccountId, contactId, contactType, imsg).then(function (data) {
                        var data = data.data;
                        var msgdata = data.objs[0].entity;
                        msgdata.msg = chatService.prepareMsg(msgdata.msg, 1);
                        if (!data.objs[0].error) {
                            scope.createCopyMsgs(currentAccountId,nextAccount.id,nextManager.id,msgs).then(function(data){
                                var data = data.data;
                                var copyContact = data.infoToCopy.contactCopy;
                                if(parseInt(data.error) == 0){
                                    var copies = data.copies;

                                    var socketSObj = {
                                        event: 'support-forward-account',
                                        prevManager: currentManager,
                                        nextManager: nextManager,
                                        prevAccountId: currentAccountId,
                                        nextAccountId: nextAccount.id,
                                        prevContact: {id: contactId, type: contactType},
                                        nextContact:{id:copyContact,type:'alone'},
                                        msg: msgdata,
                                        copies:copies
                                    };

                                    socketService.send(JSON.stringify(socketSObj));

                                    contactsManager.getContact(currentAccountId,contactId,contactType).then(function(data){

                                        var socketUObj = {
                                            event: 'inform-user-forward-account',
                                            receiverId: data.members[0],
                                            currentAccountId: currentAccountId,
                                            nextAccount: nextAccount,
                                            contact: {id: contactId, type:contactType},
                                            nextContact:{id:copyContact,type:'alone'}
                                        };

                                        socketService.send(JSON.stringify(socketUObj));
                                    });

                                    msgsManager.eClearSelectMsgs();
                                }else{
                                    alert(data.info);
                                }

                            });
                        }

                    });
                }
            },
            //сохраняем данные о сообщении и изменяем состояние переменной hasInternalMsg
            //  это нужно для Вотчера который определен в echat.js  (  $scope.$watch('support.hasInternalMsg'  )
            pushMsgInThread: function(dataMsg){
                if(chatService.isSP()){

                    this.internalMsg = dataMsg;
                    this.hasInternalMsg = !this.hasInternalMsg;
                }
            },
            copiesMsgs : {},
            hasCopies: false,
            //contact is obj {id,type}
            addCopiesInThread: function(contact,msgs){
                if(chatService.isSP()){
                    //console.log('isSupport addMsgsInThread',msgs);
                    this.copiesMsgs.msgs = msgs;
                    this.copiesMsgs.contact = contact;
                    this.hasCopies = !this.hasCopies;
                }
            },
            _send: function(action,params){
                return $http.get('/profile/'+chatConfig.myId+'/'+action+'/',  {params: params});
            },

            sendInternalMsg: function(accountId,contactId,contactType,msg){
                var receivers = [{id:contactId,type: contactType, isOnline: true}];//массив для совместимости метода отправки сообщений с рассылкой сообщений нескольким получателям, isOnline = true чтоб не приходило уведомление на почту
                var action = 'sendMsg';
                var params = { 'accountId':accountId,
                    'receivers': angular.toJson(receivers),
                    'msg': chatService.prepareMsg(msg,0),
                    'attach':angular.toJson([]),
                    'isinternal':1 };// флаг isinternal = 1 указывает что сообщение приватное (для саппорта)
                return this._send(action,params);
            },

            anchoredManagerContact:{},
            anchoredContact: false,
            setAnchoredManagerContacts : function(data){
                if(typeof data != 'undefined')
                    this.anchoredManagerContact = data;
            },
            //установить менеджера беседы контакта
            //contact is object = porperties id, type
            //manager is object = porperties id, name
            assignTalkManager: function(manager,accountId,contact)
            {
                //console.log('assignTalkManager');
                this.anchoredManagerContact[contact.id+contact.type+accountId] = manager;
                this.anchoredContact = !this.anchoredContact;

            },
            //открепить менеджера контакта
            //contact is object = porperties id, type
            //manager is object = porperties id, name
            unassignTalkManager: function(manager,accountId,contact)
            {
                //console.log('unassignTalkManager');
                if(typeof this.anchoredManagerContact[contact.id+contact.type+accountId] != 'undefined'){
                    delete this.anchoredManagerContact[contact.id+contact.type+accountId];
                }
                this.anchoredContact = !this.anchoredContact;

            },
            //смена менеджера контакта
            //contact is object = porperties id, type
            //prevManager,nextManager is objects = porperties id, name
            changeTalkManager: function(prevManager,nextManager,accountId,contact)
            {
                //console.log('changeTalkManager');
                this.anchoredManagerContact[contact.id+contact.type+accountId] = nextManager;

                this.anchoredContact = !this.anchoredContact;

                this.selectManager(accountId,chatConfig.myId).then(function(manager){
                    if(parseInt(manager.id) == parseInt(nextManager.id)){
                        //в срочном порядке уведомить
                    }
                });
            },
            changeTalkAccount: function(prevAccountId,nextAccountId,prevManager,nextManager,prevContact,nextContact)
            {
                //console.log('changeTalkAccount');
                this.anchoredManagerContact[nextContact.id+nextContact.type+nextAccountId] = nextManager;
                delete this.anchoredManagerContact[prevContact.id+prevContact.type+prevAccountId];

                this.anchoredContact = !this.anchoredContact;

                this.selectManager(accountId,chatConfig.myId).then(function(manager){
                    if(parseInt(manager.id) == parseInt(nextManager.id)){
                        //в срочном порядке уведомить
                    }
                });
            },
            //получить информацию о менеджере за кем на данный момент закреплен контакт
            getCurrentManagerContact: function(accountId,contactId,contactType)
            {
                return typeof this.anchoredManagerContact[contactId+contactType+accountId] != 'undefined'
                    ? this.anchoredManagerContact[contactId+contactType+accountId] : {id:0,name:''};
            },

            selectManager: function(accountId,id)
            {
                var deferred = $q.defer();
                accountsManager.getAccountManagers(accountId).then(function(data){
                    var manager = null;
                    angular.forEach(data, function(man) {
                        if(man.id == id)
                            manager = man;
                    });
                    if(manager){
                        deferred.resolve(manager);
                    }else{
                        deferred.reject();
                    }

                });
                return deferred.promise;
            }



        };
        return support;
    }]);
