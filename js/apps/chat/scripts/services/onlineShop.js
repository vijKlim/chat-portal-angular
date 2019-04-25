/**
 * Created by proger on 15.10.2015.
 */


chatApp.factory('onlineShop', ['$q', '$http','online','chatConfig', function( $q, $http,online,chatConfig) {

    var onlineShop = {

        flag: false,
        managers: [],

        getManagersShop: function(shopid){
            var deferred = $q.defer();
            var scope = this;
            if(scope.managers.length)
            {
                //console.log('get managers from cache');
                deferred.resolve(scope.managers);
            }else{
                $http.post('/profile/'+chatConfig.myId+'/getIdManagerShop/?shopId='+shopid).success(function(data) {
                    if(!data.error){
                        scope.managers = data.list;
                        //console.log('get managers from server',scope.managers);
                        deferred.resolve(data.list);
                    }

                }).error(function() {
                    deferred.reject();
                });
            }

            return deferred.promise;
        }
//dont work
        //isOnline: function(shopid){
        //    var scope = this;
        //
        //    scope.getManagersShop(shopid).then(function(data){
        //        //console.log(data);
        //        var onlinedManagers = scope.getOnlineManagers(data);
        //        if(onlinedManagers == undefined || onlinedManagers.length == 0)
        //            scope.flag = false;
        //        else
        //            scope.flag = true;
        //    });
        //
        //
        //},
        //connectManager: function(id){
        //    if(this.createRelation(id)){
        //        this.openDialogOnline(id);
        //    }else{
        //        this.openDialogOffline();
        //    }
        //}

        //getOnlineManagers: function(managers)
        //{
        //    var on = [];
        //    //this.scopeChatApp.chatService.onlineUsers.push('1038');
        //    var ids = [];
        //    for(index in managers)
        //    {
        //        ids.push(managers[index].id);
        //    }
        //    on = online.getOnlineUsers(ids);
        //    console.log('online',on,ids,online.allOnlineCommunity);
        //    return on;
        //}

    };


    return onlineShop;
}]);