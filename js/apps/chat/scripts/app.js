/**
 * Created by proger on 11.08.2015.
 */

var chatApp = angular.module("chatApp", ['ngRoute','ui.bootstrap','notification','mediaModule']);

//эти переменные определены внешне перед подключением приложения
chatApp.constant('chatConfig',
    tatetChat// определенна в ClientChat.php and ChatWidget.php and AdminPanelChatWidget.php
);
//chatApp.run(function ($templateCache, $http) {
//    $http.get(domenApp+'/lib/js/angular/apps/chat/partials/list_contacts.html', { cache: $templateCache });
//});

chatApp.config(['$sceDelegateProvider','$routeProvider','$locationProvider','chatConfig',function($sceDelegateProvider,$routeProvider, $locationProvider,chatConfig){

    $sceDelegateProvider.resourceUrlWhitelist(['**']); //чтоб можно было подгружать с внешних урлов
    $routeProvider
        .when('/chat/contacts/:type',
        {
            templateUrl: chatConfig.domenApp+'/lib/js/angular/apps/chat/partials/list_contacts.html',
            controller: 'contactsController'

        })
        .when('/chat/messages/dialogs',
        {
            templateUrl: chatConfig.domenApp+'/lib/js/angular/apps/chat/partials/list_dialogs.html',
            controller: 'dialogsController'

        })
        .when('/chat/messages/dialogs/:accountId',
        {
            templateUrl: chatConfig.domenApp+'/lib/js/angular/apps/chat/partials/list_dialogs.html',
            controller: 'dialogsController'

        })
        .when('/chat/messages/dialog/:accountId/:contactId/:type',
        {
            templateUrl: chatConfig.domenApp+'/lib/js/angular/apps/chat/partials/dialog.html',
            controller: 'dialogController'

        })
        .when('/chat/messages/create_msg/accountId/:accountId/invitedId/:invitedId',
        {
            templateUrl: chatConfig.domenApp+'/lib/js/angular/apps/chat/partials/create_msg.html',
            controller: 'msgController'

        })
        .when('/chat/messages/create_msg/accountId/:accountId/id/:id/typeContact/:typeContact',
        {
            templateUrl: chatConfig.domenApp+'/lib/js/angular/apps/chat/partials/create_msg.html',
            controller: 'msgController'

        })
        .when('/chat/messages/create_msg/accountId/:accountId',
        {
            templateUrl: chatConfig.domenApp+'/lib/js/angular/apps/chat/partials/create_msg.html',
            controller: 'msgController'

        })
        .when('/chat/messages/create_msg/',
        {
            templateUrl: chatConfig.domenApp+'/lib/js/angular/apps/chat/partials/create_msg.html',
            controller: 'msgController'

        })
        .when('/chat/messages/group/action/:action/params/:params',//'/chat/messages/group/id/:id/hash/:hash',
        {
            templateUrl: chatConfig.domenApp+'/lib/js/angular/apps/chat/partials/group.html',
            controller: 'groupController'

        })
        /*
         * aid = accountId
         * cid = contactId
         * cty = contact type
         * ity = forward for whom (shop and other)
         */
        .when('/chat/forward/aid/:aid/cid/:cid/cty/:cty/ity/:ity',
        {
            templateUrl: chatConfig.domenApp+'/lib/js/angular/apps/chat/partials/forward-msg.html',
            controller: 'forwardController'

        })
        .when('/echat',
        {
            templateUrl: chatConfig.domenApp+'/lib/js/angular/apps/chat/partials/echat.html',
            controller: 'echatController'

        })
        .otherwise({
            redirectTo: function(){
                console.log(window.location.hash);
            }
            //redirectTo: (window.chatSection != undefined && window.chatSubsection != undefined) ? '/chat/'+window.chatSection+'/'+window.chatSubsection : null/*'/echat'*/  //переменые определены перед подключением этого скрипта в tpl

        });
    //то нужно чтоб ангулар не ставил перед # slesh ибо не работают хеш ссылки а также указан префикс чтоб роутинг чата работал
    if(typeof chatConfig.chatProfile != 'undefined' && parseInt(chatConfig.chatProfile) == 0){
        $locationProvider.html5Mode({enabled:true,requireBase:false,rewriteLinks:false});
    }


}]);
