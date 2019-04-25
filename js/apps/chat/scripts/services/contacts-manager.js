/**
 * Created by proger on 26.01.2016.
 */

chatApp.factory('Contact', ['$http','chatConfig','msgsManager','chatService', function($http, chatConfig,msgsManager,chatService) {

    function additionalProps(contactData) {
        contactData.inOnline = false;
        switch(contactData.type){
            case 'group':
                contactData.onlineMembers = [];
                contactData.onlineMembersInfo = [];
                contactData.visibleGroupMembers = false;
                break;
        }
    }

    function Contact(contactData) {
        additionalProps(contactData);
        if (contactData) {
            this.setData(contactData);
        }
    };
    Contact.prototype = {
        setData: function(contactData) {
            angular.extend(this, contactData);

        },
        getMsgs: function(period){
            //console.log(this.accountId,this.id,this.type,period);
            return msgsManager.loadAll(this.accountId,this.id,this.type,period);
        },
        update: function() {
            return 1;
        },
        //только для группового контакта.
        // Получаем информацию о участниках группы которые на данный момент в онлайне
        loadGroupMembers: function(){

            var scope = this;
            if(scope.type == 'group'){
                if(scope.onlineMembersInfo.length == 0){
                    //идем на сервер за информацией
                    if(scope.onlineMembers.length > 0){
                        chatService.getInfoChatContactMembers(scope.onlineMembers).success(function(data){
                            angular.forEach(data, function(value, key) {
                                scope.onlineMembersInfo.push(value);
                            });
                        });
                    }
                }
                scope.visibleGroupMembers =!  scope.visibleGroupMembers;
            }

        }
    };
    return Contact;
}]);

chatApp.factory('contactsManager', ['$http', '$q', 'Contact','chatConfig',
    function($http, $q, Contact,chatConfig) {

        var contactsManager = {
            which:{all:'all',own:'own',other:'other'},
            type:{alone:'alone',group:'group',support:'support'},
            _pool: {},
            _retrieveInstance: function(accountId,contactId, contactData) {
                var instance = undefined;
                if(this._pool[accountId])
                    instance = this._pool[accountId][contactData.type+contactId];

                if (typeof instance != 'undefined') {
                    instance.setData(contactData);
                } else {
                    contactData.accountId = accountId;
                    instance = new Contact(contactData);
                    if(!this._pool[accountId]){
                        this._pool[accountId] = {};
                    }
                    this._pool[accountId][contactData.type+contactId] = instance;

                }

                return instance;
            },
            loadOne: function(accountId,contactId,contactType)
            {

                return $http.get('/profile/'+chatConfig.myId+'/getChatOneContact/',
                    {params: { accountId:accountId,contactId:contactId,contactType:contactType }})

            },
            _search: function(accountId,contactId,type) {
                var deferred = $q.defer();
                var scope = this;
                var contact = null;
                if(typeof scope._pool[accountId] != 'undefined'){
                    if(typeof scope._pool[accountId][type+contactId] != 'undefined'){
                        contact = scope._pool[accountId][type+contactId];
                        deferred.resolve(contact);
                        return deferred.promise;
                    }

                }

                scope.loadOne(accountId,contactId,type).success(function(data) {
                    contact = scope._retrieveInstance(accountId,data.id, data);
                    //console.log('_search contact',contact);
                    deferred.resolve(contact);
                })
                    .error(function() {
                        deferred.reject();
                    });
                return deferred.promise;

            },
            countAll:function(accountId){
                return $http.get('/profile/'+chatConfig.myId+'/getCountContacts/', {params: { accountId:accountId }});
            },
            countLocal:function(accountId){
                var count = 0;
                var scope = this;
                if(scope._pool[accountId]){
                    angular.forEach(scope._pool[accountId], function(item) {
                        if(item.type != scope.type.support )
                            count++;
                    });
                }
                return count;
            },
            loadAll: function(accountId,which) {
                if(typeof which == 'undefined')
                    which = 'all';
                var deferred = $q.defer();
                var scope = this;
                var contacts = [];
                scope.countAll(accountId).then(function(data){
                    var remouteCount = data.data.count;
                    var localCount = scope.countLocal(accountId);
                    //console.log('counts',remouteCount,localCount);
                    if(parseInt(remouteCount) > 0 && parseInt(remouteCount) == parseInt(localCount) && which == 'all'){
                        angular.forEach(scope._pool[accountId], function(item) {
                            contacts.push(item);
                        });
                        deferred.resolve(contacts);
                    }else{
                        $http.get('/profile/'+chatConfig.myId+'/getChatAccountContacts/',
                            {params: { accountId:accountId,which: which }})
                            .success(function(data) {
                                data.forEach(function(contactData) {
                                    var contact = scope._retrieveInstance(accountId,contactData.id, contactData);
                                    contacts.push(contact);
                                });

                                deferred.resolve(contacts);
                            })
                            .error(function() {
                                deferred.reject();
                            });
                    }
                });
                return deferred.promise;


                //if(scope._pool[accountId] ){
                //    angular.forEach(scope._pool[accountId], function(item) {
                //        contacts.push(item);
                //    });
                //    deferred.resolve(contacts);
                //    return deferred.promise;
                //}else{
                //    $http.get('/profile/'+chatConfig.myId+'/getChatAccountContacts/',
                //        {params: { accountId:accountId,type: which }})
                //        .success(function(data) {
                //            data.forEach(function(contactData) {
                //                var contact = scope._retrieveInstance(accountId,contactData.id, contactData);
                //                contacts.push(contact);
                //            });
                //
                //            deferred.resolve(contacts);
                //        })
                //        .error(function() {
                //            deferred.reject();
                //        });
                //    return deferred.promise;
                //}

            },
            getContact: function(accountId,contactId,type) {
                return this._search(accountId,contactId,type);
            },
            setOnlineStatus: function(accountId,onlines){
                var scope = this;
                angular.forEach(scope._pool[accountId], function(contact, key){
                    contact.inOnline = false;
                    contact.onlineMembersInfo = [];
                    contact.onlineMembers = [];
                    angular.forEach(contact.members, function(member, key){
                        if(onlines.indexOf(member) >= 0){
                            contact.inOnline = true;
                            contact.onlineMembers.push(member);
                        }
                    });

                });
            },
            isOnline: function(id,type){
                var contact = this.getContact(id,type);
                return contact.inOnline;
            },
            findNewShops: function(accountId,onlineCommunity){
                var ids = [];
                angular.forEach(onlineCommunity, function(id, key){
                    ids.push(id);
                });

                var deferred = $q.defer();
                return $http.get('/profile/'+chatConfig.myId+'/findNewShopsInChat/',
                    {params: { accountId:accountId,ids: angular.toJson(ids) }});
            }

        };
        return contactsManager;
    }]);