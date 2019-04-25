/**
 * Created by proger on 29.01.2016.
 */

chatApp.factory('Account', ['$http','chatConfig','contactsManager', function($http, chatConfig,contactsManager) {

    function Account(data) {
        if (data) {
            this.setData(data);
        }
    };
    Account.prototype = {
        managers:[],
        setData: function(data) {
            angular.extend(this, data);
        },
        getContact:function(contactId,contactType){
            return contactsManager.getContact(this.id,contactId,contactType);
        },
        getContacts: function(which){
            var w = typeof which == 'undefined' ? contactsManager.which.all : which;
            return contactsManager.loadAll(this.id,w);
        },
        getSpecificContacts: function(which){
            return contactsManager.loadAll(this.id,which);
        },
        setManagers: function(data){
            var scope = this;
            angular.forEach(data, function(value, key) {
                scope.managers.push({id:value.id,name:value.name});
            });
        },
        getManagers: function(){
            return this.managers;
        }
    };
    return Account;
}]);

chatApp.factory('accountsManager', ['$http', '$q', 'Account','chatConfig','contactsManager',
    function($http, $q, Account,chatConfig,contactsManager) {

        var accountsManager = {

            _pool: {},
            _retrieveInstance: function(accountId, accountData) {
                var instance = this._pool[accountId];
                if (instance) {
                    instance.setData(accountData);
                } else {
                    instance = new Account(accountData);
                    this._pool[accountId] = instance;
                }

                return instance;
            },
            _search: function(accountId) {

                return this._pool[accountId];
            },
            loadAll: function() {
                var deferred = $q.defer();
                var scope = this;
                var accounts = [];
                if(Object.keys(scope._pool).length > 0){
                    angular.forEach(scope._pool, function(item) {
                        accounts.push(item);
                    });
                    deferred.resolve(accounts);
                    return deferred.promise;
                }else{
                    $http.get('/profile/'+chatConfig.myId+'/getChatAccounts/')
                        .success(function(data) {
                            data.forEach(function(accountData) {
                                var account = scope._retrieveInstance(accountData.id, accountData);
                                accounts.push(account);
                            });

                            deferred.resolve(accounts);
                        })
                        .error(function() {
                            deferred.reject();
                        });
                    return deferred.promise;
                }

            },
            getFirst: function(){
                return this._pool[Object.keys(this._pool )[0]];
            },
            getAccount: function(accountId) {
                return this._search(accountId);
            },
            countAccounts: function(){
                return Object.keys(this._pool).length;
            },
            isMultiAccounts: function(){
                return this.countAccounts() > 1 ? true : false;
            },

            getAccountManagers: function(accountId){
                var deferred = $q.defer();
                var scope = this;
                var managers = scope._pool[accountId].getManagers();
                if(typeof managers != undefined && managers.length > 0){
                    deferred.resolve(scope._pool[accountId].getManagers());
                }else{
                    $http.get('/profile/'+chatConfig.myId+'/getAccountManagers/',{params:{accountId:accountId}})
                        .success(function(data) {
                            scope._pool[accountId].setManagers(data);

                            deferred.resolve(scope._pool[accountId].getManagers());
                        })
                        .error(function() {
                            deferred.reject();
                        });
                }

                return deferred.promise;
            }

        };
        return accountsManager;
    }]);