/**
 * Created by proger on 17.11.2015.
 */

chatApp.factory('Tool', ['$http','audio','$notification','chatConfig', function($http, audio, $notification,chatConfig) {

    var nameSettings = {
        soundNotification: 'ch_sound_notification',
        notifyInPanel: 'ch_notify_in_panel'
    };
    function Tool(toolData) {
        this.nameSettings = nameSettings;
        if (toolData) {
            this.setData(toolData);
            //if(toolData.name == nameSettings.soundNotification){
            //    this.setAudioSetting(toolData.value);
            //}
           // this.setSettings(toolData.name, toolData.value);

        }
    };
    Tool.prototype = {
        setData: function(toolData) {
            angular.extend(this, toolData);

        },
        update: function() {
            var scope = this;
            $http.get('/profile/'+chatConfig.myId+'/changeToolChat/?idTool=' + scope.id+'&attributes='+ JSON.stringify(scope))
                .success(function(data){
                    if(!data.error){
                        var msg = "";
                        if(scope.getState())
                            var notification = $notification('Оповещение включено', {
                                body: "",
                                delay: 2000
                            });

                        //if(scope.name == scope.nameSettings.soundNotification){
                        //    scope.setAudioSetting(scope.value);
                        //}
                       // this.setSettings(scope.name, scope.value);
                    }
                });
        },
        getState:function(){
            switch(this.type){
                case "checkbox":
                    return this.getStateCheckbox(this.value);
                    break;
            }
        },
        getStateCheckbox: function(value){
            //console.log(value);
            if(value == 'on'){
                return true;
            }else{
                return false;
            }
        }
    };
    return Tool;
}]);

chatApp.factory('toolsManager', ['$http', '$q', 'Tool','chatConfig', function($http, $q, Tool,chatConfig) {
    var toolsManager = {
        _pool: {},
        audioNotify: 1,
        notifyPanel: 2,
        _retrieveInstance: function(toolId, toolData) {
            var instance = this._pool[toolId];

            if (instance) {
                instance.setData(toolData);
            } else {
                instance = new Tool(toolData);
                this._pool[toolId] = instance;
            }

            return instance;
        },
        _search: function(toolId) {
            return this._pool[toolId];
        },
        loadAllTools: function() {
            var deferred = $q.defer();
            var scope = this;
            $http.get('/profile/'+chatConfig.myId+'/getToolsChat/')
                .success(function(toolsArray) {
                    var tools = [];
                    toolsArray.forEach(function(toolData) {
                        var tool = scope._retrieveInstance(toolData.id, toolData);
                        tools.push(tool);
                    });

                    deferred.resolve(tools);
                })
                .error(function() {
                    deferred.reject();
                });
            return deferred.promise;
        },
        getTool: function(toolId) {
            return this._search(toolId);

        }

    };
    return toolsManager;
}]);