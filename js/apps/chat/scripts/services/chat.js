/**
 * Created by proger on 06.10.2015.
 */

chatApp.factory('chatService', ['$http','$sce','$q','chatConfig', function($http,$sce,$q,chatConfig) {
    return {
        // переменная для хранения позиции просмотра списка диалогов в чате личный кабинет юзера (используется в dialogs.js and dialog.js)
        scrollPosListDialogs: 0,
        //
        countNewMsg  : 0,
        //стек для хранения id диалогов которым пришли новые сообщения (используется в echat.js)
        stackNewMsgs: [],
        //флаги типа конвертации контента
        tagImgToCode : 1,
        codeToTagImg : 2,
        tagAToCode   : 3,
        codeToTagA   : 4,
        textAToCode  : 5,
        textHrefToTagA: 6,
        //индикатор загрузки данных
        dataLoaded   : false,
        //типы контактов
        typeContact  : {Alone:'alone',Group:'group',Support:'support'},

        //ИСПОЛЬЗУЕТСЯ msg.js,derective/chat.js получить контакт по id-шникам участников контакта
        getInfoContact : function(accountId,id, type){
            return $http.get('/profile/'+chatConfig.myId+'/getInfoChatContact/', {params: {'accountId':accountId,'contactId': id, 'typeContact':type}});
        },
        //api получение информации о пользователях контакта (memberIds is array)
        getInfoChatContactMembers: function(memberIds){
            if(typeof memberIds != 'undefined')
                return $http.get('/profile/'+chatConfig.myId+'/getInfoChatContactMembers/', {params: {'members': angular.toJson(memberIds)}});
        },
        //ИСПОЛЬЗУЕТСЯ msg.js получить контакт по id-шникам участников контакта
        getContactByMembers : function(accountId,invitedId){
            return $http.get('/profile/'+chatConfig.myId+'/getContactByMembers/',  {params: { accountId:accountId,invitedId: invitedId }});
        },
        //ИСПОЛЬЗУЕТСЯ forward-msg.js получить контакт(ы) по id-магазина и id-аккаунта нашего (for support)
        getContactByShop : function(accountId,shopId){
            return $http.get('/profile/'+chatConfig.myId+'/getContactByShop/',  {params: { accountId:accountId,shopId: shopId }});
        },

        //api для получения списка общих групп двух юзеров (на данный момент не используется)
        getCommonGroups: function(mid,fid){
            return $http.get('/profile/'+chatConfig.myId+'/getCommonGroups/', {params: { mid:mid,fid:fid }});
        },

        //api создания группы чата
        createGroup: function(accountId,name,logoGroup,members,thread_hash){
            //Если thread_hash = 0 то значит мы группу создаем с нуля,
            // а если больше нуля тогда мы диалог модифицируем в группу
            return $http.get('/profile/'+chatConfig.myId+'/createGroup/',  {params: { accountId:accountId,nameGroup: name, logoGroup: logoGroup, members: members,thread_hash: thread_hash }});
        },


        //проверка является ли клиент чата саппортом
        isSP: function()
        {
            if(parseInt(chatConfig.sp) == 1)
                return true;
            else
                return false;
        },

        //рендеринг html
        renderHtml: function(html_code) {
                return $sce.trustAsHtml(html_code);
            },
        urlify: function(text) {
            text = text.replace(/&nbsp;/img,' ');
        var urlRegex = /([^href="]https?:\/\/[^\s]+)/img;
        return text.replace(urlRegex, function(url) {
            return '<a href="' + url + '" target="_blank" rel="nofollow">' + url + '</a>';
        })
        // or alternatively
        // return text.replace(urlRegex, '<a href="$1">$1</a>')
        },
        //конвертирование тегов в текст и обратно
        transformTag: function(text, typeTransform){
            //console.log(text);
            switch(typeTransform){
                case this.tagImgToCode:
                    text = text.replace(/(&lt;|<)a.*?(&gt;|>)(&lt;|<)img.*?src="(.*?)".*?(&gt;|>)(&lt;|<)\/a(&gt;|>)/img,'[i]$4[/i]');
                    //text = text.replace(/(&lt;|<)img.*?src="(.*?)".*?(&gt;|>)/img,'[i]$2[/i]');
                    text = jQuery.mbEmoticons.replaceImgTagByCode(text);//jquery.mb.emoticons/jquery.mb.emoticons.js
                    break;
                case this.codeToTagImg:
                    text = text.replace(/\[i\](.*?)\[\/i\]/img,'<a href="$1" target="_blank" rel="nofollow"><img style="display: inline; width: 19px; height: 19px;" src="$1"></a>');
                    text = jQuery.mbEmoticons.replaceByImage(text,true);//jquery.mb.emoticons/jquery.mb.emoticons.js

                    break;
                case this.tagAToCode:
                    text = text.replace(/(&lt;|<)a.*? href="([^"]*)"(.*?)(&gt;|>)(.*?)(&lt;|<)\/a(&gt;|>)/img,'[a][h]$2[/h][c]$5[/c][/a]');
                    break;
                case this.codeToTagA:
                    text = text.replace(/\[a\]\[h\](.*?)\[\/h\]\[c\](.*?)\[\/c\]\[\/a\]/img,'<a href="$1" target="_blank" rel="nofollow">$2</a>');
                    //text = text.replace(/\[a\](.*?)\[\/a\]/img,'<a href="$1" target="_blank" rel="nofollow">$1</a>');
                    break;
                case this.textAToCode:
                    //text = text.replace(/(https?:\/\/[^\s\[<]+)/img,'[a]$1[/a]');
                    break;
                case this.textHrefToTagA:
                    text = text.replace(/&amp;nbsp;/g, "\u00a0");
                    text = this.urlify(text);
                    break;
                default:
                    text = text;
            }
            return text;
        },
        //не которые переводы (используется contacts.js)
        translateStatus: function(status){
            switch(status){
                case 'delete':
                    return 'удаленный контакт';
                case 'block':
                    return 'блокированный контакт';
                case 'cancel':
                    return 'отмененный контакт';
                case 'accepted':
                    return 'подтвержденный контакт';
                case 'no_accepted':
                    return 'неподтвержденный контакт';
                case 'new':
                    return "новый контакт";
            }
        },
        prepareMsg: function(msg,direction){
            var rmsg = '';
            switch(direction){
                case 0:
                    rmsg = this.transformTag(msg,this.tagImgToCode);
                    rmsg = this.transformTag(rmsg,this.tagAToCode);
                    rmsg = this.transformTag(rmsg,this.textAToCode);
                    break;
                case 1:
                    rmsg = this.transformTag(msg, this.codeToTagImg);

                    //console.log('codeToTagImg',rmsg);
                    rmsg = this.transformTag(rmsg, this.codeToTagA);
                    //console.log('codeToTagA1',rmsg);
                    rmsg = this.transformTag(rmsg,this.textHrefToTagA);
                    //console.log('textHrefToTagA',rmsg);
                    break;
            }

            return rmsg;
        },
        //является ли контент Json
        isJson :function(str) {
            try {
                JSON.parse(str);
            } catch (e) {
                return false;
            }
            return true;
        },
        //функция ссылка
        viewProfile: function($event,type,personalInfo){
            $event.preventDefault();
            //console.log(personalInfo);
            switch(type){
                case 'shop':
                    window.location.href = chatConfig.domenApp+"/shop/"+personalInfo.id_external;
                    break;
                case 'customer':
                case 'team':
                    if(typeof personalInfo.members != 'undefined')
                        window.location.href = chatConfig.domenApp+"/profile/"+personalInfo.members[0];
                    else
                        window.location.href = chatConfig.domenApp+"/profile/"+personalInfo.id;
                    break;
            }
            //просмотр профиля если тип контакта не группа
            //if(contact.type == this.typeContact.Alone && chatConfig.isShop == 0)
            //    window.location.href = chatConfig.domenApp+"/profile/"+contact.members[0];
        },

        getPartial: function(dir,file){
            if(dir.length > 0)
                dir = dir+'/';
            return chatConfig.domenApp+'/lib/js/angular/apps/chat/partials/'+dir+file;
        }

    }
}]);