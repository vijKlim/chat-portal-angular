//don't used
///**
// * Created by proger on 23.11.2015.
// */
//
//
//chatApp.constant('accountsSupport',[
//    {id: 1, name:'portal', accounts:[1921,1963,1964,8893] },
//    {id: 2, name:'shops1', accounts: [1921,1963,21270]}
//]);
//
//
//chatApp.controller("adminEchatController",  ['$controller','$scope','$http','$timeout', 'accountsSupport','MessagesManager','$notification','chatService','chatConfig',
//                                            function($controller, $scope,$http,$timeout, accountsSupport,MessagesManager,$notification,chatService,chatConfig) {
//
//    //Наследование контроллера Echat
//    $controller('echatController', {
//        $scope: $scope
//    });
//
//    $scope.visibleAccounts = false;
//
//    $scope.getInfoSupportAccounts = function(contactId){
//        return $http.get('/profile/'+chatConfig.myId+'/getInfoChatContact/', {params: {'contactId': contactId,'isOne':0}});
//    };
//
//    $scope.showAccountsSupport = function(){
//        var accountIds = null;
//        for(index in accountsSupport){
//            // chatConfig.muacc определена глобально и иницилиз. в ChatWidget.php
//            if(accountsSupport[index].id == chatConfig.muacc)
//                var accountIds = accountsSupport[index].accounts;
//        }
//        if(accountIds){
//            $scope.getInfoSupportAccounts(angular.toJson(accountIds)).success(function(data) {
//                $scope.accounts = data;
//                console.log($scope.accounts);
//            });
//            $scope.visibleAccounts = true;
//        }
//
//    }
//
//    $scope.activeAccountId = 0;
//    $scope.showAccountContacts = function(accountId){
//        $scope.chatService.getMultiAccContacts(accountId).success(function(data) {
//            $scope.contacts = data;
//            $scope.setOnline(); //определен в радительском классе
//            $scope.activeAccountId = accountId;
//            $scope.visibleAccounts = false;
//            $scope.visibleContacs = true;
//
//        });
//
//    };
//
//    $scope.loadMsgs = function(contactId,type,period){
//        $scope.chatService.dataLoaded = false;
//        if(period == undefined){
//            period = 'default';
//        }
//        MessagesManager.loadMultiAccAllMsg($scope.activeAccountId,contactId, type, period).then(function(data) {
//            $scope.thread = data.msgs;
//            $scope.partnerInfo = data.personalInfoContact;
//            //если пришло новое сообщение то выводим от кого и какому из аккаунтов
//
//                $scope.getInfoSupportAccounts(angular.toJson([$scope.activeAccountId])).success(function(data) {
//
//                    $scope.toAccount = data[0];
//
//                    $scope.chatService.dataLoaded = true;
//                    $scope.visibleMsgs = true;
//
//                    $scope.$broadcast('refresh', {});
//
//                });
//
//
//        });
//    };
//
//    $scope.notifyNewMsg = function(contactId,senderId,type){
//        $scope.addAmountNewMsgs(contactId,type,1);
//        if(($scope.toolsManager.getTool($scope.toolsManager.audioNotify)).getState())
//            $scope.audio.play();
//        if(($scope.toolsManager.getTool($scope.toolsManager.notifyPanel)).getState()){
//            $scope.chatService.getInfoChatContact(angular.toJson([senderId,$scope.activeAccountId]),type,0).success(function(data){
//                var sender = null;
//                angular.forEach(data, function(value, key) {
//                   if(parseInt(value.id) == parseInt($scope.activeAccountId)){
//                       $scope.toAccount = value;
//                   }else{
//                       sender = value;
//                   }
//                });
//                console.log('sender');console.log(sender);
//                console.log($scope.toAccount);console.log($scope.toAccount);
//                $scope.createNotification(sender);
//
//            });
//        }
//
//    };
//
//    $scope.showDialog = function(contact, period){
//        //при просмотре трида сообщений = обнуляем новые сообщения
//        $scope.setViewedNewMsgs(contact.id,contact.type);
//        contact.count_msgs = 0;
//        var adapter = $scope.chatService.getAdaptedContactData(contact);
//        $scope.showAddInGroup = contact.admin_id == $scope.activeAccountId ? true : false;
//        $scope.loadMsgs(adapter.thread_hash, adapter.type, period);
//    };
//
//    //??????????????????????????????????????????????????????
//    //$scope.showMsg = function(contact)
//    //{
//    //    $scope.visibleReceiver = true;
//    //    $scope.showDialog(contact);
//    //};
//
//
//    $scope.sendMsg = function($event){
//        if ($event.which === 13 && !$event.shiftKey){
//            var receivers = $scope.getReceivers($scope.activeAccountId);//определен в радительском классе
//            var msg = $('#e_editor').html();
//            msg = msg.replace(/<br>/g,'\r\n');
//            console.log(receivers);
//            MessagesManager.sendMsgForMultiAcc($scope.activeAccountId,msg,receivers).then(function(data) {
//                if(!data.error){
//                    $('#e_editor').html('');
//                    $scope.thread.push(data.entity);
//                    $scope.$broadcast('refresh', {});
//                } else
//                    $('#e_editor').html('<p style="color:red;">'+data.info_msg+'</p>');
//            });
//
//        }else if($event.which == 13 && $event.shiftKey){
//            //$($event.target).html($($event.target).html()+'<br>&nbsp;');
//            $($event.target).focusEnd();
//        }
//
//    };
//
//    $scope.createNotification = function(sender){
//
//            var notification = $notification('Новое сообщение '+$scope.toAccount.name, {
//                body: sender.name,
//                delay: 7000,
//                icon: sender.logo
//            });
//
//            notification.$on('show', function(){
//                //console.log('My notification is displayed.');
//            });
//
//            notification.$on('close', function () {
//                //console.log('The user has closed my notification.');
//            });
//
//            notification.$on('error', function () {
//                //console.log('The notification encounters an error.');
//            });
//
//
//    };
//
//    $scope.transferDialog = function(){
//        var params = JSON.stringify({extendThread:$scope.thread[0].thread_hash,idsExistsMembers:[$scope.partnerInfo.id,$scope.activeAccountId]});
//        window.location = chatConfig.domenApp+"/profile/"+chatConfig.myId+"/tatetChat/#/chat/messages/group/action/extend/params/"+params;
//    };
//
//}]);
//
//
