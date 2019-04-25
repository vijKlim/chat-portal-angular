/**
 * Created by proger on 29.01.2016.
 */

chatApp.factory('Message', ['$http','chatConfig', function($http, chatConfig) {

    function Message(data) {
        if (data) {
            this.setData(data);
        }
    };
    Message.prototype = {
        setData: function(data) {
            angular.extend(this, data);

        }
    };
    return Message;
}]);

chatApp.factory('msgsManager', ['$http', '$q','$rootScope', 'Message','chatConfig','chatService','MediaAPI','ImagesStorage','socketService',
    function($http, $q,$rootScope, Message,chatConfig,chatService,MediaAPI,ImagesStorage,socketService) {

        var msgsManager = {
            waitingMsg: false,
            iswritingNow: false,
            countNewMsg:0,
            notifyMsg:0,
            notifyMsgHash:[],
            showThread:0,
            //стек для хранения id аккаунта и диалогов аккаунта которым пришли
            // новые сообщения (используется в echat.js)
            stackNewMsgs: {},
            stackNotifyMsg: [],

            //действия саппорта относительно принятых ими сообщений
            supportActionForMessage: {accept:'accept',notAccept:'not_accept',redirect:'redirect',acceptRedirect:'accept_redirect'},

            _retrieveInstance: function(id, msgData) {
                //msgData.msg = chatService.transformTag(msgData.msg,chatService.codeToTagImg);
                //msgData.msg = chatService.transformTag(msgData.msg,chatService.codeToTagA);
                msgData.msg = chatService.prepareMsg(msgData.msg,1);
                msgData.msgSelected = false;
                msgData.answered    = false;
                instance = new Message(msgData);
                return instance;
            },
            loadAll: function(accountId,contactId,type_contact,period) {
                if(typeof period == 'undefined')
                    which = 'default';
                var deferred = $q.defer();
                var scope = this;
                $http.get('/profile/'+chatConfig.myId+'/getChatContactMsgs/',
                    {params: {accountId: accountId, contactId: contactId , typeContact: type_contact, period: period}})
                    .success(function(data) {
                        var msgs = [];
                        if(data.msgs){
                            data.msgs.forEach(function(msgData) {
                                //msgData.accountId = accountId;
                                msgData.contactId = contactId;
                                //msgData.type_contact = type_contact;

                                var msg = scope._retrieveInstance(msgData.id, msgData);
                                msgs.push(msg);
                            });
                        }
                        deferred.resolve({msgs: msgs, personalInfoContact: data.personalInfoContact});
                    })
                    .error(function() {
                        deferred.reject();
                    });
                return deferred.promise;


            },
            _send: function(action,params){
                var deferred = $q.defer();
                var scope = this;

                var attach = scope.prepareAttach();

                if(attach.length < 5 && !params.msg){
                    return null;
                }
                params.attach = attach;

                $http.get('/profile/'+chatConfig.myId+'/'+action+'/',  {params: params}).then(function(data) {
                    var data =  data.data;
                    var ifEvenOne = false;
                    angular.forEach(data.objs, function(value, key) {
                        if(!value.error){
                             value.entity.msg = chatService.prepareMsg(value.entity.msg,1);
                            scope.sendWebSocketPacket(value.entity);
                            ifEvenOne = true;
                        }
                    });
                    if(ifEvenOne){
                        if(Object.keys(scope.stackNewMsgs ).length > 0){
                            var key = data.objs[0].entity.type+data.objs[0].entity.contactId;
                            if(typeof scope.stackNewMsgs[key] != 'undefined'){
                                scope.countNewMsg -= parseInt(scope.stackNewMsgs[key].count_msgs);
                                delete scope.stackNewMsgs[key];
                            }
                        }
                        var output = {};
                        output.info_msg = data.report.info;
                        output.entity = data.objs[0].entity;
                        deferred.resolve(output);
                    }else{
                        deferred.reject();
                    }

                });
                return deferred.promise;
            },
            sendMsg : function(accountId,msg,receivers){
                var action = 'sendMsg';
                var params =  { 'accountId':accountId,
                                'receivers': angular.toJson(receivers),
                                'msg': chatService.prepareMsg(msg,0) };
                return this._send(action,params);
            },

            sendWebSocketPacket : function(data) {
                var receiverId = data.type == chatService.typeContact.Alone ? data.receiver_id : 0;
                var contactId  =  data.contactId;

                data.support = [];
                var msg = {
                    'receiver'  : receiverId,
                    'contactId'  : contactId,
                    'typeContact': data.type,
                    'msg'        : data,
                    'event'      : 'message'
                };
                socketService.send(JSON.stringify(msg));

            },

            prepareAttach: function(){
                var attach = [];
                if(MediaAPI.mediaContent){
                    attach = attach.concat(MediaAPI.mediaContent);
                    MediaAPI.mediaContent = null;
                    MediaAPI.isStart = false;
                }
                if(ImagesStorage.items.length > 0){
                    attach = attach.concat(ImagesStorage.items);
                    ImagesStorage.items = [];
                    ImagesStorage.countItems = 0;
                }
                return angular.toJson(attach);
            },
            addAmountNewMsgs : function(accountId,data){
                var scope = this;
                var added = false;
                var hash = data.contact_type+data.contactId;

                scope.countNewMsg += parseInt(data.count_msgs);
                if(Object.keys(scope.stackNewMsgs ).length > 0){
                    angular.forEach(scope.stackNewMsgs, function(value, key) {
                        if(hash == key ){
                            value.count_msgs = parseInt(value.count_msgs) + parseInt(data.count_msgs);
                            value.msg.push(data.msg);
                            value.isInternal = data.isInternal;
                            if(value.accountIds.indexOf(parseInt(accountId)) < 0){
                                value.accountIds.push(parseInt(accountId));
                            }
                            added = true;
                        }
                    });
                }
                if(!added){
                    scope.stackNewMsgs[hash] = {accountIds:[parseInt(accountId)],
                        accountId:accountId,
                        contactId:data.contactId,
                        contactType:data.contact_type,
                        name:data.name,
                        logo:data.logo,
                        msg:[data.msg],
                        count_msgs:data.count_msgs,
                        //additional:data,
                        isInternal:data.isInternal
                    };

                }
            },
            addToNotify: function(data)
            {
                var scope = this;
                chatService.getInfoContact(data.accountId,data.contactId,data.typeContact).success(function(profile){
                    var obj = {
                        accountId: data.accountId,
                        contactId: data.contactId,
                        contact_type: data.typeContact,
                        name:profile.name,
                        logo:profile.logo,
                        msg: data.msg,
                        count_msgs:1,
                        isInternal: data.isInternal,
                        childChat: data.childChat
                    };

                    scope.stackNotifyMsg.push(obj);
                    scope.notifyMsg += 1;
                });
            },
            getCountNewMsgsContact: function(accountId,contactId,contactType){
                var hash = accountId+contactType+contactId;
                return typeof this.stackNewMsgs[hash] != 'undefined' ? this.stackNewMsgs[hash].count_msgs : 0;
            },
            //установить статус просмотренно для новых сообщений
            setViewed: function(contactId,contactType){
                if(Object.keys(this.stackNewMsgs ).length > 0){
                    var view = null;
                    //если не определенно какого контакта смотреть сообщения то начинаем с начала стека
                    if( contactId == 0 && contactType == ''){
                        view = this.stackNewMsgs[Object.keys(this.stackNewMsgs)[0]];
                        //delete this.stackNewMsgs[Object.keys(this.stackNewMsgs)[0]]; // удаление со стека
                    }else{
                        view = this.stackNewMsgs[contactType+contactId];
                        //delete this.stackNewMsgs[contactType+contactId];// удаление со стека
                    }
                    //if(parseInt(this.countNewMsg) > 0)
                    //    this.countNewMsg = parseInt(this.countNewMsg) - parseInt(view.count_msgs) >= 0 ? parseInt(this.countNewMsg) - parseInt(view.count_msgs) : 0;
                    return this.loadAll(view.accountIds[view.accountIds.length-1],view.contactId,view.contactType,'default').then(function(data){
                        data.viewInfo = {'accountId':view.accountIds[view.accountIds.length-1],'contactId':view.contactId,'contactType':view.contactType};
                        //console.log('data load msg for set viewed',data);
                        return data;
                    });
                }else{
                    return [];
                }

            },
            clearNew: function(contactId,contactType){
                if(typeof this.stackNewMsgs[contactType+contactId] != 'undefined')
                    delete this.stackNewMsgs[contactType+contactId];
            },

            //api получения новых сообщений чата
            loadNewMsgs: function() {
                var scope = this;
                $http.get('/profile/'+chatConfig.myId+'/getNewMsgs/').success(function(data){
                    angular.forEach(data, function(value, key) {
                        angular.forEach(value.newmsgs, function(nm, nk) {

                            scope.addAmountNewMsgs(value.accountId,nm);
                        });
                    });
                    //console.log('newMsgs: ',scope.stackNewMsgs);

                });
            },
            //getIdSelectedMsgs: function(msgs){
            //    var ids = [];
            //    angular.forEach(msgs, function(value, key) {
            //        if(value.msgSelected){
            //            ids.push(value.id);
            //        }
            //    });
            //    return ids;
            //},
            //пул для хранения выделленных сообщений в маленьком чате
            eSelectMsgs:{},
            eHasSelected: false,
            //только для маленького чата, метод используется дерективой select-msg directives/chat.js
            //msg is object, properties id,msg
            toggleSelectMsg: function(msg){
                if(typeof this.eSelectMsgs[parseInt(msg.id)] == 'undefined'){
                    this.eSelectMsgs[parseInt(msg.id)] = msg.msg;
                }else{
                    delete this.eSelectMsgs[parseInt(msg.id)];
                }
                this.eHasSelected = Object.keys(this.eSelectMsgs).length > 0;
            },
            eGetIdSelectMsgs:function(){
                var ids = [];
                angular.forEach(this.eSelectMsgs, function(value, key) {
                    ids.push(key);
                });
                return ids;
            },
            eGetSelectMsgs:function(){
                var msgs = [];
                angular.forEach(this.eSelectMsgs, function(value, key) {
                    msgs.push(value);
                });
                return msgs;
            },
            eClearSelectMsgs: function(){
                this.eSelectMsgs = {};
                this.eHasSelected = false;
            },
            ecountSelectedMsgs: function(){
                return this.eHasSelected ? Object.keys(this.eSelectMsgs).length : 0;
            },
            updateStatus: function(accountId,status,ids){
                var deferred = $q.defer();
                var scope = this;
                //console.log(ids);
                var jids = angular.toJson(ids);

                $http.get('/profile/'+chatConfig.myId+'/updateStatusMsgs/',  {params: {'accountId':accountId, 'ids': jids, 'status': status }}).success(function(data) {
                    //console.log(data);
                    deferred.resolve(data.error);
                }).error(function() {
                    deferred.reject();
                });
                return deferred.promise;
            },

            formatMsg: function(contactId,contactType,msg,senderId,receiverId){
                var obj = {
                    'id':0,'contactId':contactId,'thread_hash':contactId,'type':contactType,
                    'sender_id':senderId,'receiver_id':receiverId,'msg':msg,'is_answered':0,
                    'is_viewed':0,'date_created':0,'group_id':0,'is_internal':1,'is_copy':0
                };
                return obj;
            },
            informTypedMsg: function(partnerInfo,iswriting){
                //console.log('in focus');
                var msg = {
                    'companion'  : partnerInfo.id,
                    'iswriting': iswriting,
                    'event'      : 'info-writing-msg'
                };
                socketService.send(JSON.stringify(msg));
            },
            notifyWritingMsg: function(iswriting)
            {
                var scope = this;
                scope.waitingMsg = !scope.waitingMsg;
                scope.iswritingNow = iswriting;
                $rootScope.$apply();
            }

        };
        return msgsManager;
    }]);