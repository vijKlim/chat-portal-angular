/**
 * Created by proger on 06.10.2015.
 */


chatApp.directive("addFindContact", ['$compile','chatService',function($compile,chatService){
    return{
        restrict: 'AE',
        link: function(scope , element,attr){
            element.bind("click", function(e){
                var skip = true;
                if(scope.searchService.selectContacts.length > 0 && scope.searchService.asyncSelected)
                {
                    angular.forEach(scope.searchService.selectContacts, function(value, key) {
                        if (value.id === scope.searchService.asyncSelected.id) {
                            scope.$broadcast('tooltipActive', {title:'Этот контакт уже добавлен',targetEl:element,hideInfo:true});
                            skip = false;
                            return;
                        }
                    });
                }

                if(skip){
                    if(scope.searchService.asyncSelected){
                        if(scope.searchService.asyncSelected.type == 'shop'){
                            var shopObj = {'shopid':scope.searchService.asyncSelected.id,
                                           'shopname':scope.searchService.asyncSelected.name};
                            scope.chatService.getContactByShop(scope.activeAccount,scope.searchService.asyncSelected.id).then(function(data){
                                var data = data.data;
                                if(!data.error){
                                    shopObj.shopContacts = data.shopContacts;
                                    //console.log('data shop',shopObj);
                                    scope.searchService.selectContacts.push(shopObj);
                                }
                            });
                        }
                        else if(scope.searchService.asyncSelected.type == 'alone'){
                            //scope.activeAccount - родительская область видимости,  scope.searchService.asyncSelected.id = юзер id
                            scope.chatService.getContactByMembers(scope.activeAccount,scope.searchService.asyncSelected.id).then(function(data){
                                var data = data.data;
                                if(!data.error){
                                    scope.searchService.selectContacts.push(data);
                                }
                            });
                        }else{
                            scope.searchService.selectContacts.push(scope.searchService.asyncSelected);
                            scope.$broadcast('tooltipActive', {title:'Kонтакт добавлен',targetEl:element, hideInfo:true});
                        }

                    }else{
                        scope.$broadcast('tooltipActive', {title:'Вы не ввели имя контакта!',targetEl:element, hideInfo:true});

                    }
                }
                scope.searchService.asyncSelected = null;
                scope.$digest();

            });
        }
    };
}]);

chatApp.directive("addFindContactMember", ['$compile','chatService',function($compile,chatService){
    return{
        restrict: 'AE',
        link: function(scope , element,attr){
            element.bind("click", function(e){
                var skip = true;
                if(scope.searchService.selectContacts.length > 0 && scope.searchService.asyncSelected)
                {
                    angular.forEach(scope.searchService.selectContacts, function(value, key) {
                        if (value.id === scope.searchService.asyncSelected.id) {
                            scope.$broadcast('tooltipActive', {title:'Этот пользователь уже добавлен',targetEl:element,hideInfo:true});
                            skip = false;
                            return;
                        }
                    });
                }

                if(skip){
                    if(scope.searchService.asyncSelected){

                        scope.searchService.selectContacts.push(scope.searchService.asyncSelected);
                        scope.$broadcast('tooltipActive', {title:'Пользователь добавлен',targetEl:element, hideInfo:true});

                    }else{
                        scope.$broadcast('tooltipActive', {title:'Вы не ввели имя пользователя!',targetEl:element, hideInfo:true});

                    }
                }
                scope.searchService.asyncSelected = null;
                scope.$digest();

            });
        }
    };
}]);

chatApp.directive("chatSearcher",['chatConfig','searchService', function(chatConfig, searchService){

    return {
        templateUrl: chatConfig.domenApp+'/lib/js/angular/apps/chat/partials/directive/chat-search.html',
        restrict: 'E',
        scope: {
            searchType: '='
        },
        controller: ['$scope',function($scope) {
            $scope.searchService = searchService;
            $scope.search = function(q)
            {
                var type = $scope.searchType;
                return searchService.getContacts(q,1,type);
            };

        }]
    };
}]);

chatApp.directive('scrollPane', ['chatService','$timeout',function(chatService,$timeout){
    function link(scope, element, attr){
        var $element = $(element),
            api;
        $element.bind(
            'jsp-scroll-y',
            function(event, scrollPositionY, isAtTop, isAtBottom)
            {
                //получаем позицию промотра списка диалогов и сохраняем в переменной
                if($element.data('spos') == 'dialogs'){
                    chatService.scrollPosListDialogs = scrollPositionY;
                }
                //console.log('#pane1 Handle jsp-scroll-y',
                //    'scrollPositionY=', scrollPositionY,
                //    'isAtTop=', isAtTop,
                //    'isAtBottom=', isAtBottom);
            }
        );
        $element.jScrollPane({autoReinitialise: true});
        api = $element.data('jsp');

        //api.reinitialise();
        scope.$on('refresh', function onRefresh(event, args){
            //api.reinitialise();
            $timeout(function () {
                if(parseInt($element.data('action')) > 0){
                    api.scrollToY(100000);
                }
            }, 500);

        });
        scope.$on('setPositionScroll', function(event, args){
            var pos = chatService.scrollPosListDialogs;
            $timeout(function () {
                //console.log('PositionScroll: '+pos);
                //console.log("высота контента: "+api.getContentHeight());
                api.scrollToY(pos);
            }, 500);

        });

    }
    return {
        restrict: 'A',
        link: link
    };
}]);

chatApp.directive('tooltip', ['$timeout',function($timeout){
    return {
        restrict: 'A',
        link: function(scope, element, attrs){
            scope.$on('tooltipActive', function onRefresh(event, args){

                $(args.targetEl).tooltip({
                    title: args.title,
                    container: 'body',
                    //стили прописанны для топа если нужно передать параметр
                    template: '<div class="tooltip"  role="tooltip" ><div class="tooltip-arrow" style="border-top-color:#0077be !important;"></div><div class="tooltip-inner" style="background-color:#0077be !important;"></div></div>'
                }).tooltip('show');
                $timeout(function () {
                    $(args.targetEl).tooltip('destroy');
                }, 1500);


            });

            scope.$on('tooltipShow', function onRefresh(event, args){
                $(args.targetEl).tooltip({
                    title: args.title,
                    container: 'body',
                    //delay: 1000,
                    //стили прописанны для лефта если нужно передать параметр
                    template: '<div class="tooltip echat-tooltip"   role="tooltip" ><div class="tooltip-arrow" ></div><div class="tooltip-arrow-inner" > </div><div class="tooltip-inner"></div></div>'
                }).tooltip('show');

            });
            scope.$on('tooltipHide', function onRefresh(event, args){
                $(args.targetEl).tooltip('hide');
            });
            scope.$on('tooltipTop', function onRefresh(event, args){
                $(args.targetEl).tooltip({
                    title: args.title,
                    container: 'body',
                    //стили прописанны для лефта если нужно передать параметр
                    template: '<div class="tooltip"   role="tooltip" ><div class="tooltip-arrow" style="border-top-color:#0077be !important;"></div><div class="tooltip-inner" style="background-color:#0077be !important;max-width: 350px !important;"></div></div>'
                }).tooltip('show');

            });
        }
    };
}]);

chatApp.directive('setfocus', ['$interval','$timeout',function($interval,$timeout) {
    return{
        restrict: 'A',
        scope: {
            setfocus: '='
        },
        link: function(scope, element, attrs){
            if (scope.setfocus === true){
                //element[0].focus();
                var informer = $(element[0]);
                var backColor = informer.css("background-color");
                scope.aTimer = $interval(function () {

                    if(!informer.hasClass('add-border')){
                        informer.addClass('add-border').css('backgroundColor','#faa732');
                    }
                    else{
                        informer.removeClass('add-border').css('backgroundColor',backColor);
                    }


                }, 500);
                $timeout(function () {
                    $interval.cancel(scope.aTimer);
                    informer.removeClass('add-border').css('backgroundColor',backColor);
                }, 2000);
            }
        }
    };
}]);



chatApp.directive("chatSetting",['chatConfig', function(chatConfig){
    return {
        templateUrl: chatConfig.domenApp+'/lib/js/angular/apps/chat/partials/directive/chat-setting.html',

        restrict: 'E',
        controller: ['$scope',function($scope) {
                //Загрузка пользовательских настроек чата
            //$scope.toolsManager определена в радительском scope контроллере echat.js =
            $scope.toolsManager.loadAllTools().then(function(data){
            $scope.chatTools = data;
            });
        }]
    };
}]);


chatApp.directive('showOnlineShop',['onlineShop','online','chatConfig', function(onlineShop,online,chatConfig){
    function link(scope, element, attr ){
        scope.onlineShop = onlineShop;
        scope.online = online;
        scope.info = '';
        scope.shopOnline = false;
        scope.managerOnline = 0;
        var $element = $(element);
        var shopid = $element.attr('data-shopid');

        scope.$watch('online.countAllOnlines', function(newval,oldval){
            if (newval != oldval && newval != undefined) {
                scope.onlineShop.getManagersShop(shopid).then(function(data){
                    //console.log('connect shop...');
                    var flag = false;
                    data.forEach(function(item){
                        for(var prop in scope.online.allOnlineCommunity)
                        {
                            if(parseInt(item.id) == parseInt(scope.online.allOnlineCommunity[prop]))
                            {
                                flag = true;
                                scope.managerOnline = parseInt(item.id);
                            }
                        }
                    });
                    if(flag){
                        scope.shopOnline = true;
                        $element.find('#bk-question-online').show();
                        $element.find('#bk-question-offline').hide();
                        scope.info = '(онлайн)';
                    }else{
                        scope.shopOnline = false;
                        $element.find('#bk-question-online').hide();
                        $element.find('#bk-question-offline').show();
                    }

                });
            }
        });

        element.bind("click", function(e){
            if(scope.shopOnline && scope.managerOnline)
                scope.$broadcast('echatController.event.createContactShop', {clientId:chatConfig.myId,shopManagerId:scope.managerOnline});
        });

    }
    return {
        restrict: 'A',
        link: link
    };
}]);

chatApp.directive('selectMsg',['msgsManager', function(msgsManager) {
    return {
        scope: {
            smsg: "=smsg"
        },
        template: '<div class="selector-msg"  data-selected="0" ng-click="selectMsg($event)">'
                  +'<span style="display: block;cursor: pointer;">&#10004;</span>'
                  + '</div>',

        restrict: 'E',
        controller: ['$scope',function($scope) {
            $scope.selectMsg = function($event){
                //console.log('select messages',angular.fromJson($scope.smsg));
                var jelm = $($event.target);
                //var num = jelm.find('.num_msg');
                if(parseInt(jelm.attr('data-selected')) > 0){
                    jelm.css('color','#ddd');
                    jelm.attr('data-selected',0);

                }else{
                    jelm.css('color','#faa732');
                    jelm.attr('data-selected',1);
                }
                msgsManager.toggleSelectMsg($scope.smsg);

                //console.log('selected msgs pool',msgsManager.eSelectMsgs);

            };
        }],
        link : function(scope, element, attrs) {
            scope.$on('clearSelectedMsgs', function(event, args){
                $(element).find('.selector-msg>span').css('color','#ddd');

            });
        }
    };
}]);

//chatApp.directive('bindHtmlCompile', ['$compile', function ($compile) {
//    return {
//        restrict: 'A',
//        link: function (scope, element, attrs) {
//            scope.$watch(function () {
//                return scope.$eval(attrs.bindHtmlCompile);
//            }, function (value) {
//                // In case value is a TrustedValueHolderType, sometimes it
//                // needs to be explicitly called into a string in order to
//                // get the HTML string.
//                element.html(value && value.toString());
//                // If scope is provided use it, otherwise use parent scope
//                var compileScope = scope;
//                if (attrs.bindHtmlScope) {
//                    compileScope = scope.$eval(attrs.bindHtmlScope);
//                }
//                $compile(element.contents())(compileScope);
//            });
//        }
//    };
//}]);
chatApp.directive('dynamic',['$compile', function($compile) {
    return {
        restrict: 'A',
        replace: true,
        link: function (scope, element, attrs) {
            scope.$watch(attrs.dynamic, function(html) {
                element[0].innerHTML = html;
                $compile(element.contents())(scope);
            });
        }
    };
}]);


/**
 * Created by proger on 19.02.2016.
 */

//include in msgs_window.tpl, contacts_window.tpl, accounts_window.ypl (html attribute pick-out)
//выделить окно на котором находится указатель мишы
chatApp.directive('pickOut', function() {

        return {
            restrict: 'A',

            link : function(scope, element, attrs) {

                $(element[0])
                    .bind('mouseenter', function(event) {

                        var elem = $(element[0]);
                       $('.modal_online').each(function( index ) {
                           var zIndexs = {'aw':'1001','cw':'1002','mw':'1003'};
                           if(elem.data('name') != $(this).data('name'))
                               $(this).css({'zIndex':zIndexs[$(this).data('name')]});
                           else
                               $(this).css({'zIndex':9999});
                       });

                    })
                    .bind('mouseleave', function(event) {
                        var zIndexs = {'aw':'9997','cw':'9998','mw':'9999'};
                        $('.modal_online').each(function( index ) {
                            $(this).css({'zIndex':zIndexs[$(this).data('name')]});
                        });

                    })
            }
        };
    });

chatApp.directive('blinkInformer',['msgsManager', function(msgsManager){
    return {
        restrict: 'A',

        controller: ['$scope','$interval',function($scope,$interval) {
            var color1 = '#29ABE2';
            var color2 = '#faa732';
            $scope.activeColor = '';
            $scope.workelement = null;
            var promise;
            $scope.msgsManager = msgsManager;
            $scope.working = function(element,count){
                $scope.workelement = element;
                if(parseInt(count) > 0)
                    $scope.start();
                else
                    $scope.stop();
            };


            $scope.blink = function(){
                if($scope.activeColor == color1){
                    $scope.workelement.css('background-color',color2);
                    $scope.activeColor = color2;
                }
                else{
                    $scope.workelement.css('background-color',color1);
                    $scope.activeColor = color1;
                }
            };

            $scope.start = function(){
                $scope.stop();
                promise = $interval($scope.blink, 1000);
            };
            $scope.stop = function(){
                $interval.cancel(promise);
                $scope.workelement.css('background-color',color1);
            };





        }],
        link : function(scope, element, attrs) {
            scope.working(element,msgsManager.countNewMsg);

            scope.$watch('msgsManager.countNewMsg', function(newval,oldval){
                if (newval != oldval && newval != undefined) {
                    scope.working(element,msgsManager.countNewMsg);
                }
            });
        }
    };
}]);
