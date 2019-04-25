/**
 * Created by proger on 13.04.2016.
 */
/*
 http://test3.rixetka.com/profile/3642/tatetChat/#/chat/forward/aid/3642/cid/1000003642/cty/alone/ity/shop
 */
chatApp.controller("forwardController", ['$scope', '$window', '$routeParams',
    '$http', 'msgStorage','chatService','searchService','socketService','contactsManager','msgsManager','chatConfig',
    function($scope, $window, $routeParams,
             $http, msgStorage,chatService,searchService,socketService,contactsManager,msgsManager,chatConfig) {
        $scope.$routeParams = $routeParams;
        $scope.chatService = chatService;
        $scope.socketService = socketService;
        $scope.searchService = searchService;
        $scope.domenApp = chatConfig.domenApp;
        $scope.contentEditor = '';
        $scope.isCreateGroup = 0;
        $scope.newNameGroup = '';
        $scope.switchToGroup = false;
        $scope.group = [];
        $window.document.title = "Чат. Передать сообщение";

        $scope.sendMsg = function($event){
            var tElement = $event.target;
            var receivers = [];
            //Если switchToGroup == true значит созданна группа и получателем сообщения является созданная группа
            if(!$scope.switchToGroup){
                console.log('$scope.searchService.selectContacts',$scope.searchService.selectContacts);
                angular.forEach($scope.searchService.selectContacts, function(value, key) {
                    receivers.push({'id': value.id, 'isOnline':true, 'type':value.type});
                });
            }else{
                if($scope.group){
                    receivers.push({'id':$scope.group.groupId,'isOnline':false, 'type':$scope.chatService.typeContact.Group});
                }
            }

            var msg = $('#editor').html();
            if(receivers.length > 0){
                var accountId = parseInt($scope.activeAccount) > 0 ? $scope.activeAccount : chatConfig.myId;
                msgsManager.sendMsg(accountId,msg,receivers).then(function(data) {
                    if(!data.error) {
                        $('#editor').html('');
                        $scope.$broadcast('tooltipActive', {title:data.info_msg,targetEl:tElement,hideInfo:true});
                    }else{
                        $scope.$broadcast('tooltipActive', {title:data.info_msg,targetEl:tElement,hideInfo:true});
                    }
                });
            }else{
                $scope.$broadcast('tooltipActive', {title:'Нужно добавить получателя сообщения.',targetEl:tElement,hideInfo:true});
            }

        };

        if(typeof $scope.$routeParams.aid !="undefined" &&
            typeof $scope.$routeParams.cid !="undefined" &&
            typeof $scope.$routeParams.cty !="undefined" &&
            typeof $scope.$routeParams.ity !="undefined")
        {

            $scope.activeAccount = $scope.$routeParams.aid;
            $scope.activeContact = {id:$scope.$routeParams.cid, type: $scope.$routeParams.cty};

            $scope.searchService.selectContacts = [];
            contactsManager.getContact($scope.activeAccount, $scope.activeContact.id,$scope.activeContact.type).then(function(contact){
                if(typeof contact != 'undefined'){
                    $scope.activeContact.info = contact;
                }else{
                    $scope.chatService.getInfoContact($scope.activeAccount,$scope.activeContact.id,$scope.activeContact.type).then(function(response) {
                        $scope.activeContact.info = response.data;
                    }, function(error) {
                        //console.log('Error: getInfoContact');
                    });
                }
            });


        }

        if(msgStorage.getMsg() !="")
        {
            //console.log(msgStorage.getMsg());
            $scope.contentEditor = msgStorage.getMsg();
        }
        setTimeout(function(){
            if(chatConfig.isShop == 0) $("#editor").mbSmilesBox({'coveringElement':'.container_editor','prightBtn':160,'ptopBtn':40});//jquery.mb.emoticons/jquery.mb.emoticons.js

        },200);

    }]);