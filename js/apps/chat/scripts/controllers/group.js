/**
 * Created by proger on 06.10.2015.
 */

chatApp.controller("groupController", ['$scope', '$route','$window', '$routeParams', '$location',
    '$http', 'chatService','searchService','chatConfig',
    function($scope, $route,$window, $routeParams, $location,$http, chatService,searchService,chatConfig) {
        $scope.$routeParams = $routeParams;
        $scope.chatService = chatService;
        $scope.searchService = searchService;
        $scope.domenApp = chatConfig.domenApp;

        $scope.activeAccount = 0;
        //$scope.isCreateGroup = 0;
        $scope.extendThread = 0;
        $scope.newNameGroup = '';
        $scope.existsGroup = null;
        $scope.switchToGroup = false;
        $scope.group = [];
        $scope.headTitle = '';


        $scope.initExtend = function(ids){
            chatService.getInfoChatContactMembers(ids).then(function(response) {
                angular.forEach(response.data, function(value, key) {
                    $scope.searchService.selectContacts.push(value);
                });

            }, function(error) {
                console.log('Error: initExtend');
            });
        };

        $scope.initAdd = function(accountId,groupId){
           if(parseInt(accountId) && parseInt(groupId)){
               chatService.getInfoContact(accountId,groupId,$scope.chatService.typeContact.Group).then(function(response) {
                    $scope.existsGroup = response.data;
               }, function(error) {
                   console.log('Error: initAdd');
               });
           }else{

           }
        };

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
            }
            return flag;
        };
        $scope.selectMembers = function(){
            var members = [];
            angular.forEach($scope.searchService.selectContacts, function(value, key) {
                members.push({'id': value.id});
            });
            return members;
        }
        $scope.createGroup = function($event){
            var element = $event.target;
            var logo = $('#group-img-load').attr('src');
            var members = $scope.selectMembers();
            if(members){
                members = angular.toJson(members);
                if(this.newNameGroup){
                    $scope.chatService.createGroup($scope.activeAccount,this.newNameGroup,logo,members,$scope.extendThread).success(function(data){
                        if(!data.error){
                            $scope.group = data.group;
                            $scope.searchService.selectContacts = [];//чистим выделенные контакты
                            $scope.$broadcast('tooltipActive', {title:data.info,targetEl:element, hideInfo:true});
                        }else{

                        }
                    });
                }
            }

        };

        $scope.addInGroup = function($event,groupId){
            var element = $event.target;
            var members = $scope.selectMembers();
            if(members) {
                members = angular.toJson(members);
                $http.get('/profile/'+chatConfig.myId+'/addMembersInGroup/',  {params: {accountId:$scope.activeAccount, groupId:groupId,members:members}})
                    .success(function(data) {

                        var info = '';
                        angular.forEach(data, function(value, key) {
                            if(parseInt(value.error)){
                                info += 'Ошибка: '+value.msg;
                            }else{
                                info += 'Выполнено: '+value.msg;
                            }
                        });
                        $scope.searchService.selectContacts = [];//чистим выделенные контакты
                        $scope.$broadcast('tooltipActive', {title:info,targetEl:element, hideInfo:true});
                    }).error(function(data, status, header, config) {
                            console.log(status);
                    });
            }
        };

        if(typeof $scope.$routeParams.action !="undefined" && typeof $scope.$routeParams.params !="undefined")
        {
            var action = $scope.$routeParams.action;
            var params = JSON.parse($scope.$routeParams.params);

            switch(action){
                case 'extend':
                    $window.document.title = "Чат. Расширение диалога до группы";
                    $scope.headTitle = "Расширение диалога до группы";
                    $scope.activeAccount = params.accountId;
                    $scope.extendThread = params.extendThread;
                    $scope.searchService.selectContacts = [];
                    $scope.initExtend(params.idsExistsMembers);
                    break;
                case 'add':
                    $window.document.title = "Чат. Добавить в группу участников";
                    $scope.headTitle = "Добавить в группу участников";
                    $scope.activeAccount = params.accountId;
                    $scope.initAdd($scope.activeAccount,params.groupId);
                    break;
            }

        }

    }]);