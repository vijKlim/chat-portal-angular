/**
 * Created by proger on 06.10.2015.
 */


chatApp.controller("dialogController", ['$scope', '$rootScope', '$route','$window', '$routeParams', '$location','$http',
    '$sce','msgStorage','$timeout','msgsManager','chatService','socketService','chatConfig','contactsManager','accountsManager',
    function($scope, $rootScope, $route,$window, $routeParams, $location,$http,
             $sce,msgStorage,$timeout,msgsManager,chatService,socketService,chatConfig,contactsManager,accountsManager) {
        $scope.$route       = $route;
        $scope.$location    = $location;
        $scope.$routeParams = $routeParams;
        $scope.chatService = chatService;
        $scope.msgsManager = msgsManager;
        //$scope.thread      = {};
        $scope.statuses     = { 'delete':'delete', 'spam' : 'spam' };
        $scope.lastAddMsg = {};
        $scope.domenApp = chatConfig.domenApp;
        $scope.activeAccount = 0;

        $window.document.title = "Чат. Мои Cообщения";

        $scope.loadDialogMsgs = function(accountId,contactId, type,period){
            $scope.chatService.dataLoaded = false;
            msgsManager.loadAll(accountId,contactId , type,period).then(function(data) {
                $scope.thread = data.msgs;
                //console.log($scope.thread);
                $scope.partnerInfo = data.personalInfoContact;

                //$scope.partnerInfo.type = $scope.thread[0].type;

                $scope.chatService.dataLoaded = true;

                $scope.$broadcast('refresh', {});//определенно в derectives/chat.js
                if(chatConfig.isShop == 0) $("#editor").mbSmilesBox({'coveringElement':'.container_editor','prightBtn':160,'ptopBtn':40});//jquery.mb.emoticons/jquery.mb.emoticons.js

            });

        };

        $scope.getPeriodMsgs = function(period){
            if(period == undefined){
                period = 'default';
            }
            $scope.loadDialogMsgs($scope.activeAccount,$scope.activeContact.id , $scope.activeContact.type,period);

        };

        $scope.sendMsg = function(){
            if(typeof $scope.$routeParams.contactId !="undefined"){
                var receivers = [];

                receivers.push({'id': $scope.activeContact.id ,'isOnline':true, 'type': $scope.activeContact.type});

                var msg = ''+$('#editor').html();
                msgsManager.sendMsg($scope.activeAccount,msg,receivers).then(function(data) {
                    $('#editor').html('');
                    //console.log(data.entity);
                    $scope.thread.push(data.entity);
                });

                $scope.$broadcast('refresh', {});//определенно в derectives/chat.js
            }
        };

        $scope.deleteAllSelect = function(){
            msgsManager.eClearSelectMsgs();
            $scope.$broadcast('clearSelectedMsgs', {});// directives/chat.js name = selectMsg
        };

        $scope.forwardMessages = function(){
            //var msgs = getPropertySelectedMsgs('msg');
            var msgs = msgsManager.eGetSelectMsgs();
            msgs = msgs.join('<br/>');
            msgStorage.setMsg(msgs);
            $location.path("/chat/messages/create_msg/");

        };

        $scope.updateStatusMsgs = function(status){
            var ids = msgsManager.eGetIdSelectMsgs();
            msgsManager.updateStatus($scope.activeAccount,status,ids).then(function(error) {
                if(!error){
                    angular.forEach(ids, function(vid, kid) {
                        angular.forEach($scope.thread, function(vth, kth) {
                            if(vth.id == vid){
                                $scope.thread.splice(kth, 1);
                            }
                        });
                    });
                    msgsManager.eClearSelectMsgs();
                    $scope.$broadcast('clearSelectedMsgs', {});// directives/chat.js name = selectMsg
                }else{
                    alert("Произошла ошибка, попробуйте позже.");
                }
            });
        };

        $scope.setFocusEditor = function(){

            $timeout(function(){
                msgsManager.eClearSelectMsgs();
                $scope.$broadcast('clearSelectedMsgs', {});// directives/chat.js name = selectMsg
                $('#editor').focus();
            }, 400);

        };

        $scope.showAllDialogs = function(){
            $location.path("/chat/messages/dialogs/"+$scope.activeAccount);
        };

        //слушаем событие (получили обычное сообщение) которое определенно в controllers/echat.js
        $rootScope.$on('SharedEmitCM', function(event, data) {
            //console.log("SHARED EMIT",data);

            if( typeof $scope.thread != 'undefined' &&  $scope.activeContact.id == data.contactId ){
                var msgObj = data.msg;
                msgObj.own_msg = parseInt(msgObj.sender_id) == $scope.activeAccount ? 1 : 0;
                $scope.thread.push(msgObj);
                $scope.$broadcast('refresh', {});
            }
        });

        //слушаем событие (получили внутренее (саппорт) сообщение) которое определенно в controllers/echat.js
        $rootScope.$on('SharedEmitIM', function(event, data) {
            if(typeof $scope.thread != 'undefined' &&
                $scope.thread[0].contactId == data.contactId &&
                $scope.thread[0].type == data.type)
            {
                $scope.thread.push(data);
                $scope.$broadcast('refresh', {});
            }
        });

        //слушаем событие (получили список скопированных  сообщений от саппорта) которое определенно в controllers/echat.js
        $rootScope.$on('SharedEmitRM', function(event, data) {
            if(typeof $scope.thread != 'undefined' &&
                parseInt($scope.thread[0].contactId) == parseInt(data.contact.id) &&
                $scope.thread[0].type == data.contact.type)
            {
                angular.forEach(data.msgs, function(item) {
                    $scope.thread.push(item);
                });
                $scope.$broadcast('refresh', {});
            }
        });


        if(angular.isDefined($scope.thread)){
        }
        else{
            if(typeof $scope.$routeParams.contactId !="undefined"){
                $scope.activeAccount = $scope.$routeParams.accountId;
                accountsManager.loadAll().then(function(data){
                    $scope.accountName = (accountsManager.getAccount($scope.activeAccount)).name;
                    contactsManager.getContact($scope.activeAccount,$routeParams.contactId,$scope.$routeParams.type).then(function(data){
                        $scope.activeContact = data;
                        $scope.loadDialogMsgs($scope.activeAccount,$scope.activeContact.id , $scope.activeContact.type,'default');
                    });
                });


            }
        }
    }]);