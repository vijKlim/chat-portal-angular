///**
// * Created by proger on 07.10.2015.
// */
//
//
//delete don't used
//chatApp.factory('MessagesManager', ['$http', '$q', 'chatService','MediaAPI','ImagesStorage','socketService','chatConfig',
//                                    function($http, $q, chatService,MediaAPI,ImagesStorage,socketService,chatConfig) {
//
//    var MessagesManager = {
//        countNewMsg:0,
//        //стек для хранения id аккаунта и диалогов аккаунта которым пришли новые сообщения (используется в echat.js)
//        stackNewMsgs: [],
//
//        chatService:chatService,
//        MediaAPI:MediaAPI,
//        ImagesStorage:ImagesStorage,
//        socketService:socketService,
//
//        _loadMsgs: function(action,parameters) {
//            var deferred = $q.defer();
//            var scope = this;
//            $http.get('/profile/'+chatConfig.myId+'/'+action+'/',  {params: parameters}).success(function(data) {
//                if(data.msgs){
//
//                    data.msgs.forEach(function(msgData) {
//                        msgData.msg = scope.chatService.transformTag(msgData.msg,scope.chatService.codeToTagImg);
//                        msgData.msg = scope.chatService.transformTag(msgData.msg,scope.chatService.codeToTagA);
//                        msgData.msgSelected = false;
//                        msgData.answered    = false;
//                    });
//                    deferred.resolve(data);
//                }
//
//            }).error(function() {
//                deferred.reject();
//            });
//            return deferred.promise;
//        },
//
//        loadAllMsg: function(contactId,type,period){
//            var action = 'getChatThreadMsgs';
//            var params = { contactId: contactId , type_contact: type, period: period};
//            return this._loadMsgs(action,params);
//        },
//        loadMultiAccAllMsg: function(accountId,contactId,type,period){
//            var action = 'getChatMultiAccThreadMsgs';
//            var params = { accountId: accountId, contactId: contactId , type_contact: type, period: period};
//            return this._loadMsgs(action,params);
//        },
//
//        getIdSelectedMsgs: function(msgs){
//            var ids = [];
//            angular.forEach(msgs, function(value, key) {
//                if(value.msgSelected){
//                    ids.push(value.id);
//                }
//            });
//            return ids;
//        },
//        updateStatus: function(status,ids){
//            var deferred = $q.defer();
//            var scope = this;
//            var jids = angular.toJson(ids);
//            $http.get('/profile/'+chatConfig.myId+'/updateStatusMsgs/',  {params: { 'ids': jids, 'status': status }}).success(function(data) {
//                //console.log(data);
//                deferred.resolve(data.error);
//            }).error(function() {
//                deferred.reject();
//            });
//            return deferred.promise;
//        },
//        prepareMsg: function(msg){
//            var msg = this.chatService.transformTag(msg,this.chatService.tagImgToCode);
//            msg = this.chatService.transformTag(msg,this.chatService.tagAToCode);
//            msg = this.chatService.transformTag(msg,this.chatService.textAToCode);
//            return msg;
//        },
//        prepareAttach: function(){
//            var attach = [];
//            if(this.MediaAPI.mediaContent){
//                attach = attach.concat(this.MediaAPI.mediaContent);
//                this.MediaAPI.mediaContent = null;
//                this.MediaAPI.isStart = false;
//            }
//            if(this.ImagesStorage.items.length > 0){
//                attach = attach.concat(this.ImagesStorage.items);
//                this.ImagesStorage.items = [];
//                this.ImagesStorage.countItems = 0;
//            }
//            return angular.toJson(attach);
//        },
//        _send: function(action,params){
//            var deferred = $q.defer();
//            var scope = this;
//
//            var attach = scope.prepareAttach();
//
//            if(attach.length < 5 && !params.msg){
//                return null;
//            }
//            params.attach = attach;
//            $http.get('/profile/'+chatConfig.myId+'/'+action+'/',  {params: params}).then(function(data) {
//                var data =  data.data;
//                var ifEvenOne = false;
//
//                angular.forEach(data.objs, function(value, key) {
//                    if(!value.error){
//                        value.entity.msg = scope.chatService.transformTag(value.entity.msg, scope.chatService.codeToTagImg);
//                        value.entity.msg = scope.chatService.transformTag(value.entity.msg, scope.chatService.codeToTagA);
//                        console.log(value.entity);
//                        scope.sendWebSocketPacket(value.entity);
//                        ifEvenOne = true;
//                    }
//                });
//                $('#editor').html('<p style="color:red;">'+data.report.info+'</p>');
//                if(ifEvenOne){
//                    var output = {};
//                    output.info_msg = data.report.info;
//                    output.entity = data.objs[0].entity;
//                    deferred.resolve(output);
//                }else{
//                    deferred.reject();
//                }
//
//            });
//            return deferred.promise;
//        },
//        sendMsgForMultiAcc: function(accountId,msg,receivers){
//            var action = 'sendMsgForMultiAcc';
//            var params = { 'accountId':accountId,'receivers': angular.toJson(receivers), 'msg': this.prepareMsg(msg) };
//            return this._send(action,params);
//        },
//        sendMsg : function(msg,receivers){
//            var action = 'sendMsg';
//            var params = { 'receivers': angular.toJson(receivers), 'msg': this.prepareMsg(msg) };
//            return this._send(action,params);
//        },
//        sendWebSocketPacket : function(data) {
//            var receiverId = data.type == chatService.typeContact.Alone ? data.receiver_id : 0;
//            var contactId  = data.type == chatService.typeContact.Group ? data.group_id : data.thread_hash;
//            var msg = {'receiver'     : receiverId,
//                       'contactId'  : contactId,
//                       'typeContact': data.type,
//                       'event'      : 'message' };
//            this.socketService.send(JSON.stringify(msg));
//
//        },
//
//        addAmountNewMsgs : function(accountId,contactId,contactType,count){
//            var scope = this;
//            var added = false;
//            scope.countNewMsg += parseInt(count);
//            if(scope.stackNewMsgs.length > 0){
//                angular.forEach(scope.stackNewMsgs, function(value, key) {
//                    if(parseInt(accountId) == value.accountId &&
//                        parseInt(contactId) == parseInt(value.id) &&
//                        contactType == value.type){
//                        value.count_msgs = parseInt(value.count_msgs) + count;
//                        added = true;
//                    }
//                });
//            }
//            if(!added)
//                scope.stackNewMsgs.push({accountId:accountId,contactId:contactId,contactType:contactType,'count_msgs':count});
//
//        }
//
//
//    };
//    return MessagesManager;
//}]);