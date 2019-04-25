/**
 * Created by proger on 06.10.2015.
 */

chatApp.controller("contactsController", ['$scope', '$route','$window', '$routeParams', '$location','$http',
                                          'chatService', 'searchService', 'socketService','$timeout','chatConfig','accountsManager','contactsManager',
                                         function($scope,  $route,$window, $routeParams, $location,$http,
                                                  chatService, searchService, socketService,$timeout,chatConfig,accountsManager,contactsManager) {
    $scope.$route       = $route;
    $scope.$routeParams = $routeParams;
    //$scope.contactsState    = '';
    $scope.isSearchOwn     = 1;
    $scope.chatService  = chatService;
    $scope.searchService  = searchService;
    $scope.isglobalSearch = false;
    $scope.titleApp = '';
    $scope.emptyInfo = '';
    $scope.domenApp = chatConfig.domenApp;//global var define app.js

    $window.document.title = "Чат. Мои Контакты";

    var actions = {
        wm : 'writeMsg',
        dl : 'delete',
        bc : 'block',
        cr : 'cancel',
        ac : 'accepted',
        nac: 'no_accepted'
    };
    var params = {
        all   : { type : 'all',
            actions : [
                {sno: 1, name: {alone:'Удалить контакт',group:'Удалить группу'}, type: actions.dl},
                {sno: 1, name: {alone:'Блокировать контакт',group:'Блокировать группу'}, type: actions.bc},
                {sno: 1, name: {alone:'Написать сообщение',group:'Написать сообщение'}, type: actions.wm}
            ]},
        own   : { type : 'own',
            actions : [
                {sno: 1, name: {alone:'Отменить запрос',group:'Отменить запрос'}, type: actions.cr}
            ]},
        other : { type : 'other',
            actions : [
                {sno: 1, name: {alone:'Отменить',group:'Отменить'}, type: actions.nac},
                {sno: 1, name: {alone:'Принять',group:'Принять'},  type: actions.ac}
            ]}
    };

    $scope.activeAccount = 0;
    $scope.countContacts = true;
    $scope.loadContacts = function(param){
        accountsManager.getAccount($scope.activeAccount).getContacts(param.type).then(function(data){
            $scope.contacts = data;
            console.log($scope.contacts);
            $scope.actions  = param.actions;
            $scope.chatService.dataLoaded = true;
            if($scope.contacts.length > 0)
                $scope.countContacts = true;
            else
                $scope.countContacts = false;
        });
    };
    $scope.processContact = function(id, status){
        var contact = null;
        for (var index in $scope.contacts) {
            if($scope.contacts[index].id == id){
                contact = $scope.contacts[index];
                break;
            }
        }
        if(contact){
            switch(status)
            {
                case actions.dl:
                case actions.bc:
                case actions.cr:
                case actions.ac:
                case actions.nac:
                    $scope.updateStatusContact(contact.id, status, contact.type);
                    break;
                case actions.wm:
                    $scope.writeMsg(contact.id, contact.type);
                    break;

            }
        }

    }

    $scope.updateStatusContact = function(id, status, typeContact){
        var local = $scope;
        $http.get('/profile/' +chatConfig.myId+'/updateStatusChatContact/',  {params: { accountId:$scope.activeAccount,contactId: id, status: status, typeContact: typeContact }})
            .success(function(data) {
                for (var index in $scope.contacts) {
                    var deleteId = $scope.contacts[index].id;
                    if(deleteId == id){
                        $scope.contacts.splice(index,1);
                        if($scope.contacts.length == 0)$scope.countContacts = false;
                        break;
                    }
                }
            }).error(function(data, status, header, config) {
                //console.log(status);
            });
    };
    $scope.writeMsg = function(id,type){
        $location.path("/chat/messages/create_msg/accountId/"+$scope.activeAccount+"/id/"+id+"/typeContact/"+type);
    };

    $scope.addToMyContacts = function($event){
        var tElement = $event.target;
        var invitedId = $scope.searchService.asyncSelected.id;
        $http.get('/profile/'+chatConfig.myId+'/createAloneContact/',  {params: { accountId:$scope.activeAccount,invitedId: invitedId }})
            .success(function(data) {
                //console.log('add contact',data);
                if(!data.error){
                    if(data.isnew){
                        $scope.contacts.unshift(data.model);
                        if($scope.contacts.length > 0)$scope.countContacts = true;

                        $scope.searchService.asyncSelected = null;
                        $scope.$broadcast('tooltipActive', {title:data.msg,targetEl:tElement,hideInfo:true});//определенно в derectives/chat.js
                    }else{
                        $scope.searchService.asyncSelected = null;
                        $scope.$broadcast('tooltipActive', {title:data.msg,targetEl:tElement,hideInfo:true});//определенно в derectives/chat.js
                    }
                }else{
                    $scope.$broadcast('tooltipActive', {title:data.msg,targetEl:tElement,hideInfo:true});//определенно в derectives/chat.js
                }
            }).error(function(data, status, header, config) {
                //console.log(status);
            });
    };

    var param = null;
    if(angular.isDefined($scope.contacts)){
    }
    else{

        if(typeof $scope.$routeParams.type !="undefined"){
            switch($scope.$routeParams.type)
            {
                case params.all.type:
                    param = params.all;
                    $scope.isglobalSearch = true;
                    $scope.titleApp = "Мои Контакты";
                    $scope.emptyInfo = "У вас нет контактов";
                    break;
                case params.own.type:
                    param = params.own;
                    $scope.titleApp = "Мои Запросы";
                    $scope.emptyInfo = "Новых запросов нет";
                    break;
                case params.other.type:
                    param = params.other;
                    $scope.titleApp = "Мои Заявки";
                    $scope.emptyInfo = "Новых заявок нет";
                    break;
            }
        }else{
            param = params.all;
        }
        accountsManager.loadAll().then(function(data) {
            $scope.accounts = data;
            $scope.activeAccount = $scope.accounts[0].id;// если у юзера мультиаккаунт то берем его первый аккаунт, для обычного юзера берется его единственный акк
            $scope.loadContacts(param);
        });
    }

     $scope.changeAccount = function(accId){
         $scope.activeAccount = accId;
         $scope.loadContacts(param);
     };
}]);
