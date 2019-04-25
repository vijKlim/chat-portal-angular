/**
 * Created by proger on 19.02.2016.
 */

//include in msgs_window.tpl (html element support-buttons)
chatApp.directive('supportControls',['chatConfig','accountsManager','support', 'msgsManager',
    function(chatConfig,accountsManager,support,msgsManager) {

    return {
        templateUrl: chatConfig.domenApp+'/lib/js/angular/apps/chat/partials/directive/support-buttons.html',
        scope: {
            account: "@",
            contact: "@",
            lastmsg: "@",
            viewtype: "@"
        },
        restrict: 'E',
        controller: ['$scope',function($scope) {
            $scope.support = support;

            $scope.descSupportButton = '';
            $scope.action = {answer:1,notAnswer:2,forwardManager:3,forwardAccount:4,forwardShop:5,all:6};
            $scope.isShowActionFrame = false;
            $scope.accountManagers = [];
            $scope.supportAccounts = [];
            $scope.isforwardM = false;
            $scope.isforwardA = false;
            $scope.styler = {};

            $scope.customization = function(){
                $scope.styler = {
                    positionPopup:null
                };
                switch($scope.viewtype){
                    case '1':
                        $scope.styler.positionPopup = {bottom: '86px',left:'10px'};
                        break;
                    case '2':
                        $scope.styler.positionPopup = {bottom: '86px',left:'10px'};
                        break;
                }
            };
            $scope.customization();
            //менеджер согласен вести беседу
            $scope.takeChat = function(){
                support.selectManager($scope.account,chatConfig.myId).then(function(manager){
                    var contact = angular.fromJson($scope.contact);
                    //console.log($scope.contact,'supp get contact');
                    support.acceptChat(manager,$scope.account,contact.id,contact.type);
                });
            };
            //ф-ция срабатывает когда менеджер нажал кнопку "Не отвечать"
            //ситуции:
            //1.Если данный менеджер не закреплен сейчас за этим контактом то мы просто скрываем панель управления саппорта
            //2. Если данный менеджер быз закреплен на данный момент за контактом, то мы скрываем у него панель управления саппорта
            // ,снимаем его с контакта и уведомляем остальных менеджеров этого контакта
            $scope.notTakeChat = function(){
                support.selectManager($scope.account,chatConfig.myId).then(function(manager){
                    var contact = angular.fromJson($scope.contact);
                    var currentManager = support.getCurrentManagerContact($scope.account,contact.id,contact.type);
                    //если текущим менеджером является он сам тогда создаем внутренне сообщение и уведомляем остальных менеджеров контакта
                    if(parseInt(currentManager.id) == parseInt(manager.id))
                        support.notAcceptChat(manager,$scope.account,contact.id,contact.type);

                    $scope.blockedSupportControls();
                });
            };
            //manager is object, properties: id,name
            $scope.forwardManager = function(nextManager){
                support.selectManager($scope.account,chatConfig.myId).then(function(manager){
                    var contact = angular.fromJson($scope.contact);
                    var currentManager = support.getCurrentManagerContact($scope.account,contact.id,contact.type);
                    //если текущим менеджером является он сам тогда создаем внутренне сообщение и перенаправляем беседу другому менеджеру контакта
                    if(parseInt(currentManager.id) == parseInt(manager.id)){
                        support.forwardManager(currentManager,nextManager,$scope.account,contact.id,contact.type);
                        $scope.isShowActionFrame = !$scope.isShowActionFrame;
                    }


                });
            };
            $scope.forwardAccount = function(nextAccount,nextManager){
                support.selectManager($scope.account,chatConfig.myId).then(function(manager){
                    var contact = angular.fromJson($scope.contact);
                    var currentManager = support.getCurrentManagerContact($scope.account,contact.id,contact.type);
                    //если текущим менеджером является он сам тогда создаем внутренне сообщение и перенаправляем беседу другому менеджеру ругому саппорт контакту
                    if(parseInt(currentManager.id) == parseInt(manager.id)){
                        var msgs = [];
                        msgs = msgsManager.eGetIdSelectMsgs();
                        if(msgs.length == 0){
                            var tmp = angular.fromJson($scope.lastmsg);
                            msgs.push(tmp.id);
                        }
                        //console.log('selected support msgs',msgs,'lsmsgs',$scope.lastmsg,'next manager',nextManager,
                        //    'current manager',currentManager,'contact',contact,'account',$scope.account);
                        support.forwardAccount(currentManager,nextManager,$scope.account,nextAccount,contact.id,contact.type,msgs);
                        $scope.isShowActionFrame = !$scope.isShowActionFrame;
                    }
                });

            };
            $scope.forwardShop = function(){
                support.selectManager($scope.account,chatConfig.myId).then(function(manager) {
                    var contact = angular.fromJson($scope.contact);
                    var currentManager = support.getCurrentManagerContact($scope.account, contact.id, contact.type);
                    //если текущим менеджером является он сам тогда создаем внутренне сообщение и перенаправляем беседу другому менеджеру контакта
                    if (parseInt(currentManager.id) == parseInt(manager.id)) {
                        //console.log( chatConfig.domenApp + "/profile/"+chatConfig.myId+"/tatetChat/#/chat/forward/aid/"
                        //+ $scope.account + "/cid/" + contact.id + "/cty/" + contact.type + "/ity/shop");
                        window.location.href = chatConfig.domenApp + "/profile/"+chatConfig.myId+"/tatetChat/#/chat/forward/aid/"
                        + $scope.account + "/cid/" + contact.id + "/cty/" + contact.type + "/ity/shop";
                    }
                });

            };


            //Если менеджер согласился вести беседу то у всех других менеджеров выводится значение этой переменной
            $scope.informStateTalk = '';
            //переменная нужна для минимизация внутренних проверок дерективы в методе getStateSupportControl()
            //$scope.isCheckStateControls = true;
            //callback срабатывает при закреплении контакта за менеджером services/support.js
            $scope.$watch('support.anchoredContact', function(newval,oldval){
                //console.log('wotcher anchoredContact');
                if (newval != oldval && newval != undefined) {
                    //console.log('wotcher insider');
                    $scope.setStateSupportControls();
                }
            });
            $scope.$watch('contact', function(newval,oldval){
                //console.log('load contact msg');
                if (newval != oldval && newval != undefined) {
                    //console.log('wotcher load contact msg insider');
                    $scope.setStateSupportControls();
                }
            });


            $scope.visAnswer = $scope.visNotAnswer = $scope.visForwardManager = $scope.visForwardAccount = $scope.visForwardShop = $scope.visAll = true;
            //проверка на необходимость вывода саппорт кнопки
            //Все кнопки выводятся если ни один из менеджеров не участвует в беседе или не начета беседа
            //Если менеджер который согласился вести беседу то у него уберается только кнопка "Начать беседу"
            $scope.setStateSupportControls = function()
            {

                var contact = angular.fromJson($scope.contact);
                if(typeof contact != 'undefined' && Object.keys(contact).length > 0)
                {
                    var manager = support.getCurrentManagerContact($scope.account,contact.id,contact.type);
                    if(parseInt(manager.id) > 0){
                        var isI = parseInt(manager.id) == parseInt(chatConfig.myId) ? true : false;
                        $scope.informStateTalk = parseInt(manager.id) > 0  ? "С контактом сейчас работает менеджер "+manager.name : '';

                        $scope.visAnswer = false;
                        $scope.visNotAnswer = $scope.visForwardManager = $scope.visForwardAccount = $scope.visForwardShop = $scope.visAll = isI;
                    }else{
                        $scope.informStateTalk = '';
                        $scope.visAnswer = $scope.visNotAnswer = $scope.visForwardManager = $scope.visForwardAccount = $scope.visForwardShop = $scope.visAll = true;
                    }

                }
            };

            $scope.blockedSupportControls = function()
            {

            };

            $scope.doSupportAction = function(numaction){
                switch(parseInt(numaction)){
                    //менеджер аккаунта соглашается начать беседу с пользователям
                    case $scope.action.answer:
                        $scope.takeChat();
                        break;
                    //менеджер аккаунта отказывается начать беседу с пользователям
                    case $scope.action.notAnswer:
                        $scope.notTakeChat();
                        break;
                    //менеджер аккаунта перенаправляет пользователя другому менеджеру этого же аккаунта
                    case $scope.action.forwardManager:
                        accountsManager.getAccountManagers($scope.account).then(function(data){
                            $scope.accountManagers = data;
                            $scope.isShowActionFrame = true;
                            $scope.isforwardM = true;
                            $scope.isforwardA = false;

                        });
                        break;
                    //менеджер аккаунта перенаправляет пользователя другому аккаунту
                    case $scope.action.forwardAccount:
                        support.getSupports().then(function(data) {
                            $scope.supportAccounts = data;
                            $scope.isShowActionFrame = true;
                            $scope.isforwardM = false;
                            $scope.isforwardA = true;
                        });
                        break;
                    case $scope.action.forwardShop:
                        $scope.forwardShop();
                        break;
                }
            };
            //посказка для менеджеров в виде иконки i
            $scope.showInfo = function($event){
                var title = "Менеджеру отправится последнее собщение."
                    +"Чтоб передать больше сообщений закройте это окно и в"
                    +"списке сообщений виделите сообщения которые хотите передать";
                $scope.$broadcast('tooltipTop', {title:title,targetEl:$event.target});
            };
        }],
        link : function(scope, element, attrs) {

            $(element[0].querySelector('.chat-supports-panel .ul-action-support')).find('li')
                .bind('mouseenter', function(event) {
                    var elm = event.target;
                    switch(parseInt($(elm).attr('data-btnnum'))){
                        case 1:
                            scope.descSupportButton = 'Начать беседу';
                            break;
                        case 2:
                            scope.descSupportButton = 'Не отвечать';
                            break;
                        case 3:
                            scope.descSupportButton = 'Передать другому менеджеру';
                            break;
                        case 4:
                            scope.descSupportButton = 'Передать другому аккаунту';
                            break;
                        case 5:
                            scope.descSupportButton = 'Передать магазину';
                            break;
                    }

                    $(elm).animate({
                        width: "30px",
                        height: "30px",
                        opacity: 0.5
                    }, 200 );
                    scope.$apply();
                })
                .bind('mouseleave', function(event) {
                    var elm = event.target;
                    $(elm).animate({
                        width: "32px",
                        height: "32px",
                        opacity: 1
                    }, 200 );
                })
                .bind('click', function(event) {
                    var elm = event.target;
                    scope.doSupportAction($(elm).attr('data-btnnum'));
                });
        }
    };
}]);