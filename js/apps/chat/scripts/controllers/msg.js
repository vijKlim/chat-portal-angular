/**
 * Created by proger on 06.10.2015.
 */

chatApp.controller("msgController", ['$scope', '$window', '$routeParams',
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
    $window.document.title = "Чат. Написать сообщения";

    $scope.disabledCreateGroup = function(){
        var flag = false;
        if($scope.searchService.selectContacts.length > 0){
            angular.forEach($scope.searchService.selectContacts, function(value, key) {
                if(value.type == $scope.chatService.typeContact.Group){
                    flag = true;
                    return;
                }
            });
        }else{
            flag = true;
            $scope.isCreateGroup = false;
        }
        return flag;
    };
    $scope.createGroup = function(){
        if($scope.isCreateGroup){
            var logo = $('#group-img-load').attr('src');
            var members = [];
            angular.forEach($scope.searchService.selectContacts, function(value, key) {
                members.push({'id': value.id});
            });
            members = angular.toJson(members);
            //thread_hash передаем ноль потому что мы создаем группу с нуля а не модифицирем диалог в группу
            $scope.chatService.createGroup(this.newNameGroup,logo,members,0).success(function(data){
                if(!data.error){
                    $scope.group = data.group;
                    $scope.isCreateGroup = false;
                    $scope.switchToGroup = true;
                }else{

                }
            });
        }
    };

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

    if(typeof $scope.$routeParams.id !="undefined")
    {
        $scope.activeAccount = $scope.$routeParams.accountId;
        var type = typeof $scope.$routeParams.typeContact !="undefined" ? $scope.$routeParams.typeContact : chatService.typeContact.Alone;
        $scope.searchService.selectContacts = [];
        contactsManager.getContact($scope.activeAccount, $scope.$routeParams.id,type).then(function(contact){
            if(typeof contact != 'undefined'){
                $scope.searchService.selectContacts.push(contact);
            }else{
                $scope.chatService.getInfoContact($scope.$routeParams.accountId,$scope.$routeParams.id, type).then(function(response) {
                    $scope.searchService.selectContacts.push(response.data);
                }, function(error) {
                    //console.log('Error: getInfoContact');
                });
            }
        });


    }

    if(typeof $scope.$routeParams.invitedId !="undefined")
    {
        $scope.activeAccount = $scope.$routeParams.accountId;
        $scope.chatService.getContactByMembers($scope.$routeParams.accountId,$scope.$routeParams.invitedId).then(function(data){
            var data = data.data;
            if(!data.error){
                $scope.searchService.selectContacts.push(data);
            }
        });

    }
    if(typeof $scope.$routeParams.accountId !="undefined")
    {
        $scope.activeAccount = $scope.$routeParams.accountId;

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