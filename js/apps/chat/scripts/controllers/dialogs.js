/**
 * Created by proger on 06.10.2015.
 */

chatApp.controller("dialogsController", ['$scope', '$rootScope', '$route','$window', '$routeParams', '$location','$http','$sce','chatService','chatConfig','accountsManager',
                                        function($scope, $rootScope, $route,$window, $routeParams, $location,$http,$sce,chatService,chatConfig,accountsManager) {
    $scope.$route       = $route;
    $scope.$location    = $location;
    $scope.$routeParams = $routeParams;
    $scope.chatService  = chatService;
    $scope.countInfo = '';
    $scope.domenApp = chatConfig.domenApp;
    $scope.activeAccount = 0;

    $window.document.title = "Чат. Мои Диалоги";

    $scope.getTermination = function(number){
        var endingArray = ['диалог', 'диалога', 'диалогов'];
        var ending = '';
        var number = number % 100;
        if (number>=11 && number<=19) {
            ending=endingArray[2];
        }
        else {
            i = number % 10;
            switch (i)
            {
                case (1): ending = endingArray[0]; break;
                case (2):
                case (3):
                case (4): ending = endingArray[1]; break;
                default: ending=endingArray[2];
            }
        }
        return number+' '+ending;
    };

    $scope.loadThreads = function(accountId){
        $scope.chatService.dataLoaded = false;
        $http.get('/profile/' +chatConfig.myId+'/getChatThreads/',{params: {'accountId':accountId}}).success(function(data) {
            angular.forEach(data, function(value, key) {
                value.msg = $scope.chatService.transformTag(value.msg,$scope.chatService.codeToTagImg);
                value.msg = $scope.chatService.transformTag(value.msg,$scope.chatService.codeToTagA);
            });
            $scope.threads = data;
            //console.log($scope.threads);
            $scope.countInfo = $scope.getTermination($scope.threads.length);
            $scope.countDialog = $scope.threads.length;
            $scope.chatService.dataLoaded = true;
            //востановление состояния позиции скрола при возврате с диалога в диалоги
            $scope.$broadcast('setPositionScroll', {});//определенно в derectives/chat.js
        });
    };
    $scope.showDetailDialog = function(contactId, type){
        $location.path("/chat/messages/dialog/"+$scope.activeAccount+'/'+contactId+'/'+type);
    };
    $scope.createMsg = function(){
        $location.path("/chat/messages/create_msg/accountId/"+$scope.activeAccount);
    };

    //$scope.viewProfile = function($event,thread,who){
    //    $event.preventDefault();
    //    var id = who == 'sender' ? thread.sender_id : thread.receiver_id;
    //    //просмотр профиля если тип контакта не группа
    //    if(thread.type == chatService.typeContact.Alone)
    //        $window.location.href = $scope.domenApp+"/profile/"+id;
    //};

    $scope.changeAccount = function(accId){
        $scope.activeAccount = accId;
        $scope.loadThreads($scope.activeAccount);
    };

    //слушаем событие (получили обычное сообщение) которое определенно в controllers/echat.js
    $rootScope.$on('SharedEmitCM', function(event, data) {
        $scope.changeAccount(data.accountId);
    });

    if(angular.isDefined($scope.threads)){
    }
    else{
        accountsManager.loadAll().then(function(data){
            $scope.accounts = data;
            $scope.activeAccount = typeof $scope.$routeParams.accountId =="undefined" ? $scope.accounts[0].id : $scope.$routeParams.accountId;
            $scope.loadThreads($scope.activeAccount);
        });
    }

}]);
