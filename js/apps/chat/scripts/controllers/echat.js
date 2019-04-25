/**
 * Created by proger on 06.10.2015.
 */
/*

/*
Получить только контакты которые в онлайне
 */
chatApp.filter('filterIsOnline',function(){
    return function (items, criterion) {
        var tmp = {};
        if(items != 'undefined'){
            if(criterion == '!null')
                return items;
            for(var prop in items){
                var item = items[prop];
                if(item.inOnline == criterion){
                    tmp[prop] = item;
                }
            }
            return tmp;
        }

    }
});
/*
Отсортировать контакты в порядке первые идут те кто в онлайне
 */
chatApp.filter('orderObjectBy', function() {
    return function(items, field, reverse) {
        var filtered = [];
        angular.forEach(items, function(item) {
            filtered.push(item);
        });
        filtered.sort(function (a, b) {
            return (a[field] > b[field] ? 1 : -1);
        });
        if(reverse) filtered.reverse();
        return filtered;
    };
});
/*
фильтруем контакты по именам через ввод в поле поиска
 */
chatApp.filter('textSearchFilter',[ function () {
    return function(objects, searchText) {
        if(searchText){
            searchText = searchText.toLowerCase();
            var filtered = [];
            angular.forEach(objects, function(item) {

                if( item.name.toLowerCase().indexOf(searchText) >= 0
                //|| item.logo.toLowerCase().indexOf(searchText) >= 0  можно искать по нескольким полям
                ) {
                    filtered.push(item);
                }
            });
            return filtered;
        }else{
            return objects;
        }

    }
}]);

/*
Контроллер чата который отображается на каждой странице портала
 */
chatApp.controller("echatController",  ['$scope','$rootScope','$sce','$filter','$interval','$window','$http','$timeout','accountsManager','contactsManager','msgsManager','online','audio','toolsManager','$notification','chatConfig','chatService','support','$location',
                    function($scope,$rootScope,$sce, $filter,$interval,$window,$http,$timeout, accountsManager,contactsManager,msgsManager, online, audio,toolsManager, $notification,chatConfig,chatService,support,$location) {

    $scope.chatService = chatService;
    $scope.chatConfig = chatConfig;

    //чтоб можно было слушать watcher нужно создать ссылку на этот сервис в области видимости контроллера
    $scope.online = online;
    $scope.msgsManager = msgsManager;
    $scope.support = support;
    //id активного аккаунта пользователя на данный момент(для мултиаккаунтов)
    $scope.activeAccount = 0;
    $scope.activeAccountInfo = {};
    $scope.activeContact = {};
    //флаг показать окно аккаунтов
    $scope.visibleAccounts = false;
    //флаг показать окно контатков
    $scope.visibleContacts = false;
    //флаг показать окно сообщений контакта
    $scope.visibleMsgs    = false;
    //флаг показывать ли кнопку добавить в группу (только для админов группы выводится)
    $scope.showAddInGroup = false;

    $scope.newShops = {};
    //загрузка настроек происходит в директиве chat-setting chat.js
    $scope.toolsManager = toolsManager;
    $scope.audio = audio;
    $scope.toolsShow = false;
    $scope.toolsManager.loadAllTools();

    $scope.showNewMsgInfo = function($event,newmsg){
        $event.preventDefault();
        var translate_re = /&(nbsp|amp|quot|lt|gt);/g;
        var translate = {
            "nbsp": " ",
            "amp" : "&",
            "quot": "\"",
            "lt"  : "<",
            "gt"  : ">"
        };
        var msgs = '';
        var tmp = '';
        angular.forEach(newmsg.msg, function(item) {
            tmp = tmp.replace(translate_re, function(match, entity) {
                return translate[entity];
            });
            item = item.length > 75 ? item.substring(0,70)+"..." : item;
            msgs += '<div class="i-msg">'+item+'</div>';
        });

        var title = '<div style="width: 250px;">'
                +'<table class="i-header-table">'
                +'<tr><td>от</td><td>'+newmsg.name+'</td></tr>'
                +'<tr><td>кому</td><td>'+accountsManager.getAccount(newmsg.accountId).name+'</td></tr>'
                +'</table>'
                +'<div class="i-body">'
                +msgs
                +'</div>'
                +'<div class="i-footer">Новых сообщений: '+newmsg.count_msgs+'</div>'
                +'</div>';

        $scope.$broadcast('tooltipShow', {title:title,targetEl:$event.target});
    };

    $scope.closeNewMsgBlock = function($event,newmsg)
    {
        //console.log(newmsg);
        var params = {accountId:newmsg.accountId,contactId:newmsg.contactId,contactType: newmsg.contactType};
        $http.get('/profile/'+chatConfig.myId+'/setAnswered/',  {params: params}).then(function(data) {
            if(!data.error){
                if(Object.keys($scope.msgsManager.stackNewMsgs ).length > 0){
                    var key = newmsg.contactType+newmsg.contactId;
                    if(typeof $scope.msgsManager.stackNewMsgs[key] != 'undefined'){
                        $scope.msgsManager.countNewMsg -= parseInt($scope.msgsManager.stackNewMsgs[key].count_msgs);
                        delete $scope.msgsManager.stackNewMsgs[key];
                    }
                }
            }
        });
    };


    //получаем аккаунты пользователя, создаем соединение с websocket и иницилизируем дополнительные параметры
    accountsManager.loadAll().then(function(data){
        $scope.accounts = data;
        msgsManager.loadNewMsgs();
        //console.log('new msg',msgsManager.stackNewMsgs);
        $scope.online.init();
        $scope.init();

    });
    //функция иницилизации
    $scope.init = function(){
        //показать значок информер (выводит информацию о количестве контактов в онлайне и количество новых сообщений,
        // так же с помощью него открываются окна контактов, новых сообщений)
        //для блога пока чат не отображать, но приложение должно быть загруженно чтоб всавка медиа в комментариях работало
        var r = /^blog\./;
        if (r.test(location.hostname)) {
            //console.log(location.hostname);
            $('#echat-visible').css('display','none');
        }else{
            $('#echat-visible').css('display','block'); //find other method
        }

    };
    //показать окно аккаунтов, если у пользователя несколько аккаунтов
    $scope.showAccounts = function(){
        $scope.visibleAccounts = true;
    };
    //показать окно контактов
    $scope.showContacts = function(accountId){
        $scope.activeAccount = accountId;
        var account = accountsManager.getAccount(accountId);
        $scope.activeAccountInfo = {id:account.id,name:account.name,logo:account.logo};
        account.getContacts('all').then(function(data){
        $scope.contacts = data;

        contactsManager.setOnlineStatus(accountId,$scope.online.onlineUsers);
            if(chatService.isSP()) {
                contactsManager.findNewShops($scope.activeAccount,$scope.online.allOnlineCommunity)
                    .then(function(data){

                        var data = data.data.list;
                        if(Object.keys(data).length > 0){
                            $scope.newShops = data;
                            $scope.existsNewShops = true;
                        }else{
                            $scope.existsNewShops = false;
                        }

                    });
            }
            $scope.visibleContacts = true;
        });
    };

    $scope.writeNewShopMsg = function(id){
        location.href = "/profile/"+chatConfig.myId+"/tatetChat/#/chat/messages/create_msg/accountId/"+$scope.activeAccount+"/invitedId/"+id;
    };

    $scope.showWindowContacts = function(){
        if(accountsManager.isMultiAccounts()){
            $scope.showAccounts();
        }else{
            $scope.showContacts($scope.accounts[0].id);
        }
    };

    $scope.showDialog = function(contact,period){
        $scope.activeContact = {id:contact.id,type:contact.type};
        $scope.chatService.dataLoaded = false;
        if(typeof period == 'undefined')
            period = 'default';
        //console.log('contact info',contact);
        contact.getMsgs(period).then(function(data){
            $scope.thread = data.msgs;

            $scope.openContact = contact;
            $scope.partnerInfo = data.personalInfoContact;

            $scope.showAddInGroup = (contact.type == $scope.chatService.typeContact.Group && contact.admin_id == $scope.activeAccount) ? true : false;

            if(chatConfig.isShop == 0) $("#e_editor").mbSmilesBox({'coveringElement':'.write_modal'});//jquery.mb.emoticons/jquery.mb.emoticons.js
            $scope.visibleMsgs = true;
            $scope.chatService.dataLoaded = true;
            $scope.$broadcast('refresh', {});//определенно в derectives/chat.js
        });

    };

    $scope.goToDialog = function(contactId,contactType){
        var period = 'default';
        contactsManager.getContact($scope.activeAccount,contactId,'alone').then(function(data){
            $scope.showDialog(data,period);
        });
    };

    $scope.showHistoryMsgs = function(period){
        $scope.showDialog($scope.openContact,period);
        $scope.visibleDatePeriods = !$scope.visibleDatePeriods;
    };

    $scope.showGroupMembers = function($event, contact){
        $event.stopPropagation();
        contact.loadGroupMembers();
    };

    $scope.sendMsg = function($event){
        if ($event.which === 13 && !$event.shiftKey){
            accountsManager.getAccount($scope.activeAccount)
                .getContact($scope.activeContact.id,$scope.activeContact.type)
                .then(function(contactData){
                    var contact = contactData;
                    contact.inOnline = online.getOnlineUsers(contact.members).length ? true : false;
                    var receivers = [{id:contact.id,type: contact.type, isOnline: contact.inOnline}];//массив для совместимости метода отправки сообщений с рассылкой сообщений нескольким получателям
                    var msg = $('#e_editor').html();
                    msg = msg.replace(/<br>/g,'\r\n');

                    msgsManager.sendMsg($scope.activeAccount,msg,receivers).then(function(data) {
                        if(!data.error){
                            $('#e_editor').html('');
                            $scope.thread.push(data.entity);
                            $scope.$broadcast('refresh', {});//определенно в derectives/chat.js
                        } else
                            $('#e_editor').html('<p style="color:red;">'+data.info_msg+'</p>');
                    });
                });
        }else if($event.which == 13 && $event.shiftKey){
            //$($event.target).html($($event.target).html()+'<br>&nbsp;');
            $($event.target).focusEnd();
        }

    };

    $scope.informTypedMsg = function($event,iswriting){
        msgsManager.informTypedMsg($scope.partnerInfo,iswriting);
    };

    //пересмотреть процедуру фильтрацйии онлайн контактов
    $scope.inOnline ="!null";
    $scope.titleToggleFilter = "";
    $scope.toggleFilterOnline = function(){
        $scope.toolsShow = false;
        if($scope.inOnline == "!null"){
            $scope.inOnline = true;
            $scope.titleToggleFilter = "Контакты (Все "+online.countOnlineUsers+")";
        }
        else{
            $scope.inOnline = "!null";
            $scope.titleToggleFilter = "";
        }
    };

    $scope.setViewedThread = function(contactId,contactType){
       //если не указаны параметры
        if(typeof contactId == 'undefined'){
            contactId = 0;
            contactType = '';
        }
        msgsManager.setViewed(contactId,contactType).then(function(data){
            $scope.thread = data.msgs;

            $scope.activeAccount = data.viewInfo.accountId;
            var account = accountsManager.getAccount($scope.activeAccount);
            $scope.activeAccountInfo = {id:account.id,name:account.name,logo:account.logo};
            $scope.activeContact = {id:data.viewInfo.contactId,type:data.viewInfo.contactType};
            $scope.openContact = {id:data.viewInfo.contactId,type:data.viewInfo.contactType};
            $scope.partnerInfo = data.personalInfoContact;
            $scope.visibleMsgs = true;
            $scope.chatService.dataLoaded = true;
            $scope.$broadcast('refresh', {});//определенно в derectives/chat.js
            //if(msgsManager.stackNewMsgs.length == 0) <- this object stack
            //    $scope.stopAlarm('msg');
        });
    };

    $scope.delInfoWritingMsg = function(refresh){
        angular.forEach($scope.thread, function(item,key) {
            if(item.type === 'fake'){
                $scope.thread.splice(key,1);
                //console.log('where del',$scope.thread);
                if(refresh)
                    $scope.$broadcast('refresh', {});
                return;
            }
        });
    };

    //callback срабатывает при изменении свойства notifyMsg в обьекте msgsManager
    $scope.$watch('msgsManager.notifyMsg', function(newval,oldval){
        if (newval != oldval && newval != undefined) {
            if(($scope.toolsManager.getTool($scope.toolsManager.audioNotify)).getState())
                $scope.audio.play();

            $scope.createNotification();

        }
    });
    //$scope.showWaitingMsg = false;
    $scope.$watch('msgsManager.waitingMsg', function(newval,oldval){
        if (newval != oldval && newval != undefined) {
            if(typeof $scope.thread != 'undefined' /*&& !$scope.showWaitingMsg*/){
                //$scope.showWaitingMsg = true;
                //console.log('wait',$scope.msgsManager.iswritingNow);
                if($scope.msgsManager.iswritingNow){
                    $scope.thread.push({'wait_msg':true,id:0,type:'fake'});
                    //console.log('where add',$scope.thread);
                    $scope.$broadcast('refresh', {});
                }else{
                    $scope.delInfoWritingMsg(true);
                }

            }

        }
    });
    //обновляет счетчик новых сообщений для ссылки сообщения в меню профайла
    $scope.$watch('msgsManager.countNewMsg', function(newval,oldval){
        if (newval != oldval && newval != undefined) {
            var mhref = $("li a[href$='#/chat/messages/dialogs']");
            mhref.html('Сообщения <span style="font-weight: 600;">('+msgsManager.countNewMsg+')</span>');
        }
    });

    //callback срабатывает при создании внутреннего сообщения саппорта (только для саппорта)
    $scope.$watch('support.hasInternalMsg', function(newval,oldval){
        if (newval != oldval && newval != undefined) {

            if(typeof $scope.thread != 'undefined' &&
                $scope.thread[0].contactId == $scope.support.internalMsg.contactId &&
                $scope.thread[0].type == $scope.support.internalMsg.type)
            {
                $scope.thread.push($scope.support.internalMsg);
                $scope.$broadcast('refresh', {});
            }

            $rootScope.$emit('SharedEmitIM', $scope.support.internalMsg); //генерируем событие для уведомления остальных частей чата (чат в профайле)
        }
    });
    //callback срабатывает при передаче копий сообщений саппорта (только для саппорта)
    $scope.$watch('support.hasCopies', function(newval,oldval){
        if (newval != oldval && newval != undefined) {
            //console.log('wotcher hasCopies',$scope.support.copiesMsgs,'thread 0',$scope.thread,'compare',parseInt($scope.thread[0].contactId), parseInt($scope.support.copiesMsgs.contact.id));
            if(typeof $scope.thread != 'undefined' &&
                parseInt($scope.thread[0].contactId) == parseInt($scope.support.copiesMsgs.contact.id) &&
                $scope.thread[0].type == $scope.support.copiesMsgs.contact.type)
            {
                angular.forEach($scope.support.copiesMsgs.msgs, function(item) {
                    $scope.thread.push(item);
                });

                $scope.$broadcast('refresh', {});

            }
            $rootScope.$emit('SharedEmitRM', $scope.support.copiesMsgs);//генерируем событие для уведомления остальных частей чата (чат в профайле)
        }
    });


    //используетсфя в дерективе showOnlineShop в файле directives/chat.js команда scope.$broadcast('createContactShop', {});
    $scope.$on('echatController.event.createContactShop', function(event, args){
        var contactId = Math.min(args.clientId, args.shopManagerId) * Math.pow(10, 9) + Math.max(args.clientId, args.shopManagerId);
        $scope.activeAccount = $scope.accounts[0].id;//args.clientId;

        accountsManager.getAccount($scope.activeAccount).getContact(contactId,'alone').then(function(data){
            $scope.showDialog(data,'default');
        });


    });

    $scope.createNotification = function(){

        var data = msgsManager.stackNotifyMsg.pop();
        var msgObj = data.msg;
        //console.log('notify msg',msg);
            //если сообщение пришло контакту у которого открыт диалог то добавляем сообщение в диалог
        if( typeof $scope.thread != 'undefined' &&  $scope.openContact.id == data.contactId ){
            msgObj.own_msg = parseInt(msgObj.sender_id) == $scope.activeAccount ? 1 : 0;
            $scope.delInfoWritingMsg(true);
            $scope.thread.push(msgObj);
            $scope.$broadcast('refresh', {});
        }
        $rootScope.$emit('SharedEmitCM', data);//генерируем событие для уведомления остальных частей чата (чат в профайле)
        data.msg = msgObj.msg;//ставляем только текстовое сообщение без обьекта
        msgsManager.addAmountNewMsgs(data.accountId,data);

        if(($scope.toolsManager.getTool($scope.toolsManager.notifyPanel)).getState() && !data.childChat){
            var accountName = typeof accountsManager.getAccount(data.accountId) != 'undefined' ? accountsManager.getAccount(data.accountId).name : '';
            var notification = $notification('Новое сообщение '+accountName, {
                body: "от "+data.name,
                delay: 7000,
                icon: data.logo
            });

            notification.$on('show', function(){
                //console.log('My notification is displayed.');
            });
            notification.$on('close', function () {
                //console.log('The user has closed my notification.');
            });
            notification.$on('error', function () {
                //console.log('The notification encounters an error.');
            });
        }


    };

    $scope.transferDialog = function(){
        var params = JSON.stringify({accountId:$scope.activeAccount,extendThread:$scope.activeContact.id,idsExistsMembers:[$scope.partnerInfo.id,$scope.activeAccount]});
        window.location = chatConfig.domenApp+"/profile/"+chatConfig.myId+"/tatetChat/#/chat/messages/group/action/extend/params/"+params;
    };
    $scope.toMaximizeChat = function(){
        window.location = chatConfig.domenApp+"/profile/"+chatConfig.myId+"/tatetChat/#/chat/messages/dialog/"+$scope.activeAccount+"/"+$scope.activeContact.id+"/"+$scope.activeContact.type;
    };

    $scope.addInGroup = function(){
        if($scope.thread[0].type == $scope.chatService.typeContact.Group){
            $scope.visibleMsgs = false;
            $scope.visibleContacs = false;
            var params = JSON.stringify({accountId:$scope.activeAccount,groupId:$scope.activeContact.id});
            window.location = chatConfig.domenApp+"/profile/"+chatConfig.myId+"/tatetChat/#/chat/messages/group/action/add/params/"+params;
        }
    };

    //$scope.clearNewMsg = function(){
    //    console.log('clear new msg');
    //    msgsManager.clearNew($scope.activeContact.id,$scope.activeContact.type);
    //};

}]);