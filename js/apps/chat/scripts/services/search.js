/**
 * Created by proger on 06.10.2015.
 */

chatApp.factory('searchService', ['$http','$sce','$q','chatConfig', function($http,$sce,$q,chatConfig) {
    return {
        selectContacts : [],
        names : undefined,
        asyncSelected : null,

        getContacts : function(searchVal,searchInGroup,type) {
            var that = this;
            if(typeof type == 'undefined')
                type = 0;
            return $http.get('/profile/'+chatConfig.myId+'/searchChatContact/', {params: {'search': searchVal,'searchGroups':searchInGroup,'type':type}}).then(function(response) {

                that.names = response.data;
                return response.data;
            }, function(error) {
                console.log('Error: getContacts');
            });
        },
        selectMatch : function(index){
            var that = this;
            that.asyncSelected = that.names[index];

        },
        removeFromSelects: function($index)
        {
            this.selectContacts.splice($index,1);
        }
    }
}]);

