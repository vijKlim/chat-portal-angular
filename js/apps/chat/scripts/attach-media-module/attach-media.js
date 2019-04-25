/**
 * Created by proger on 12.09.2016.
 */

var attachMediaModule = angular.module("mediaModule", []);

//эти переменные определены внешне перед подключением приложения
attachMediaModule.constant('chatConfig',
    tatetChat// определенна в ClientChat.php and ChatWidget.php and AdminPanelChatWidget.php
);




attachMediaModule.factory('CImage',function(){

    function CImage(id,src,container)
    {
        if(src && id){
            this.image = src;
            this.id = id;
            this.type = 'image';
            this.container = container;
        }else{
            this.image = undefined;
            this.id = undefined;
            this.type = undefined;
            this.container = undefined;
        }

    }
    return CImage;

});

attachMediaModule.factory('ImagesStorage',['CImage', function(CImage) {
    return {
        countItems: 0,
        items: [],
        isFull: false,
        add: function(id,src,container){
            if(this.items.length < 8){
                this.items.push(new CImage(id,src,container));
                this.countItems++;
                this.isFull = false;
            }else{
                this.isFull = true;
            }

        },
        remove: function(id){
            var that = this;
            angular.forEach(that.items, function(value, key) {
                if(value.id == id){
                    that.items.splice(key, 1);
                    that.countItems--;
                }
            });
            if(that.items.length < 8){
                this.isFull = false;
            }
        }
    }
}]);




attachMediaModule.factory('MediaAPI', ['$http','$sce','$q','chatConfig', function($http,$sce,$q,chatConfig) {
    return {
        mediaContent: null,
        urlMedia: null,
        isStart: null,
        isload: null,
        isContentSpecificUrl: function(text){
            var regYoutube = /^(?:https?:\/\/)?(?:www\.)?(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))((\w|-){11})(?:\S+)?$/img;
            var regTatet = /^https?:\/\/(([^/]*\.)|)tatet\..*(|\/.*)$/img;
            var matchTatet =text.match(regTatet);
            var matchYoutube =text.match(regYoutube);
            if(matchTatet || matchYoutube){
                this.urlMedia = matchTatet ? matchTatet[0] : matchYoutube[0];
                this.isStart = true;
                return true;
            }
            return false;
        },
        getMediaContent: function(url){
            var that = this;
            return $http.get('/profile/'+chatConfig.myId+'/getMediaContent/', {params: {'url': url}}).then(function(response) {
                that.mediaContent = response.data;
                //console.log('isload = '+that.isload);
                //console.log(that.mediaContent);
                that.mediaContent.type = 'media';
                that.isload = false;
            }, function(error) {
                console.log('Error: getMediaContent');
            });
        }
    }
}]);




//Контроллер используется в комментариях = добавление картинок и медиа (omments.js)
attachMediaModule.controller("driveComment",['$scope','MediaAPI','ImagesStorage', function($scope,MediaAPI,ImagesStorage) {
    $scope.ImagesStorage = ImagesStorage;
    $scope.MediaAPI = MediaAPI;

}]);



attachMediaModule.directive("isolate", function(){
    return {
        restrict: 'EA',
        scope: {
            eid: "@"
        },
        controller: ['$scope',function($scope) {

            //this.getEid = function(){
            //    return $scope.eid;
            //};
            $scope.active = false;
            this.setActive = function(flag){
                $scope.active = flag;
            };
            this.getActive = function(){
                return $scope.active;
            };
            // console.log(this.getEid());

        }],
        link: function(scope , element,attr){
            //console.log('isolate = '+scope.active);
        }
    };
});

attachMediaModule.directive("addGroupImage", ['$compile', function($compile){
    return{
        restrict: 'AE',
        link: function(scope , element,attr){
            var idUploader = element.attr('data-id-uploader');
            element.bind("click", function(e){
                $("#"+idUploader+" input:file").trigger('click');
            });
        }
    };
}]);

attachMediaModule.directive("addImageMsg", ['$compile', function($compile){
    return{
        restrict: 'AE',
        require: '^isolate',
        link: function(scope , element,attr){
            var idUploader = element.attr('data-id-uploader');
            element.bind("click", function(e){

                $(this).parent().find('.e_img_storage').addClass('load_here');
                $("#"+idUploader+" input:file").trigger('click');
            });
        }
    };
}]);
attachMediaModule.directive('imageonload', function() {
    return {
        restrict: 'AE',
        scope:true,
        require: '^isolate',
        controller: ['$scope','ImagesStorage',function($scope, ImagesStorage) {
            $scope.imgsStorage = ImagesStorage;
        }],
        link: function(scope, element, attrs,isolateController) {
            scope.isolateMediaController = isolateController;
            var idEditor = element.attr('data-id-editor');
            var typePreview = element.attr('data-type-preview');
            element.bind('load', function() {
                if(typePreview =="large"){
                    scope.imgsStorage.add(scope.imgsStorage.countItems+1,$(this).attr('src'),'big');
                    $(this).css('display','none').removeClass('load_here');
                    scope.isolateMediaController.setActive(true);
                    scope.$apply();
                }
                else if(typePreview == 'small'){
                    scope.imgsStorage.add(scope.imgsStorage.countItems+1,$(this).attr('src'),'small');
                    $(this).css('display','none').removeClass('load_here');
                    scope.isolateMediaController.setActive(true);
                    scope.$apply();
                }
                else{
                    var tagA = $('<a  href="'+$(this).attr('src')+'"></a>');
                    var img = $('<img style="display:inline;" src="'+$(this).attr('src')+'">').width('50px').height('50px');
                    tagA.append(img);
                    $('#'+idEditor).append(tagA);
                    $(this).css('display','none').removeClass('load_here');
                    $('#'+idEditor).focusEnd();
                    //$(this).attr('src', '');
                }

            });
        }
    };
});

attachMediaModule.directive('imagePreview',['chatConfig', function (chatConfig) {
    return {
        restrict: 'AE',
        scope: {
            typePreview: "@"
        },
        require: '^isolate',
        templateUrl: chatConfig.domenApp+'/lib/js/angular/apps/chat/partials/image_preview.html',
        link: function(scope, element, attrs,isolateController) {
            scope.isolateMediaController = isolateController;
        },
        controller: ['$scope','$element','ImagesStorage', function($scope,$element, ImagesStorage) {

            $scope.infoFullStack = false;
            $scope.showMediaContainer = false;
            $scope.showPreloader = false;
            $scope.showImagePreview = false;
            $scope.bnspan = "";
            $scope.imgsStorage = ImagesStorage;
            $scope.$watch('imgsStorage.countItems', function(newval,oldval){
                if (newval != oldval && newval != undefined) {

                    if($scope.isolateMediaController.getActive()){
                        $scope.showImageContainer = $scope.imgsStorage.countItems ? true : false;
                        $scope.showPreloader = true;
                        if($scope.typePreview == 'large'){
                            if($scope.imgsStorage.countItems != 1){
                                $scope.bnspam = "span4";
                            }else{
                                $scope.bnspam = "span8";
                            }
                        }
                        else if($scope.typePreview == 'small'){
                            $scope.bnspam = "span1";
                        }


                        $scope.showImagePreview = $scope.imgsStorage.countItems ? true : false;
                        $scope.showPreloader = false;
                        $scope.isolateMediaController.setActive(false);
                    }else{
                        $scope.showImageContainer = false;
                    }

                }
            });
            $scope.$watch('imgsStorage.isFull', function(newval,oldval){
                if (newval != oldval && newval != undefined) {
                    if($scope.isolateMediaController.getActive()){
                        $scope.infoFullStack = true;
                        //$scope.isolateMediaController.setActive(false);
                    }

                }
            });

            $scope.delItem = function(id){
                $scope.imgsStorage.remove(id);
            }

            $scope.generateEnterEvent = function(){

                $scope.$parent.sendMsg($.Event('keypress',{which: 13}), $scope.$parent.partnerInfo.id);
            }



        }]
    }
}]);

attachMediaModule.directive('mediaPreview',['chatConfig', function (chatConfig) {
    return {
        restrict: 'AE',
        scope: true,
        require: '^isolate',
        templateUrl: chatConfig.domenApp+'/lib/js/angular/apps/chat/partials/media_preview.html',
        link: function($scope , element,attr,isolateController){
            $scope.isolateMediaController = isolateController;
        },
        controller: ['$scope','MediaAPI',function($scope, MediaAPI) {
            $scope.showMediaPreview = false;
            $scope.showMediaContainer = false;
            $scope.showPreloader = false;
            $scope.showMimgPreview = true;
            $scope.api = MediaAPI;

            $scope.$watch('api.isStart', function(newval,oldval){
                if (newval != oldval && newval != undefined) {
                    if($scope.isolateMediaController.getActive()) {
                        $scope.showMediaContainer = $scope.api.isStart ? true : false;
                        $scope.showPreloader = $scope.api.isStart ? true : false;
                    }else{
                        $scope.showMediaContainer = false;
                    }

                }
            });
            $scope.$watch('api.mediaContent', function(newval,oldval){
                if (newval != oldval && newval) {
                    if($scope.isolateMediaController.getActive()) {
                        $scope.showMediaPreview = true;
                        $scope.showPreloader = false;
                        //console.log('active');
                        //$scope.isolateMediaController.setActive(false);
                    }else{
                        //console.log('no-active');
                    }

                }
            });

            $scope.delImage = function(){
                $scope.api.mediaContent.image = null;
                $scope.showMimgPreview = false;
            };
            $scope.delMediaPreview = function(){
                $scope.api.mediaContent = null;
                $scope.showMediaContainer = false;
                $scope.api.isload = false;
            }
        }]
    }
}]);
attachMediaModule.directive("pasteMedia", function(){
    return{
        restrict: 'AE',
        scope:true,
        require: '^isolate',
        controller: ['$scope','MediaAPI',function($scope, MediaAPI) {
            $scope.api = MediaAPI;
        }],
        link: function($scope , element,attr,isolateController){
            $scope.isolateMediaController = isolateController;
            element.bind("paste", function(e){
                //e.preventDefault();
                if(!$scope.api.isload){
                    var text = (e.originalEvent || e).clipboardData.getData('text/plain') || prompt('Error paste..');
                    if($scope.api.isContentSpecificUrl(text)){
                        $scope.api.getMediaContent($scope.api.urlMedia);
                        $scope.isolateMediaController.setActive(true);
                        $scope.$apply();
                    }
                }

            });
        }
    };
});

//attachMediaModule.directive("imagesAttach", function () {
//    return {
//        restrict: "E",
//        scope: {
//            iattach: "=iattach"
//        },
//        templateUrl: chatConfig.domenApp+'/lib/js/angular/apps/chat/partials/msg-images.html',
//        controller: ['$scope', function($scope) {
//            console.log($scope.iattach);
//        }]
//    }
//});
attachMediaModule.directive("msgAttach",['chatConfig', function(chatConfig){
    return {
        template: '<ng-include src="getTemplateUrl()"/>',
        //templateUrl: chatConfig.domenApp+'/lib/js/angular/apps/chat/partials/msg-attach.html',

        scope: {
            attach: "=iattach",
            vtype: "@"
        },
        restrict: 'E',
        controller: ['$scope',function($scope) {
            $scope.chatBigAttach = chatConfig.domenApp+'/lib/js/angular/apps/chat/partials/msg-attach.html';
            $scope.chatSmallAttach = chatConfig.domenApp+'/lib/js/angular/apps/chat/partials/e-msg-attach.html';
            $scope.getTemplateUrl = function() {
                if($scope.vtype == 'chatBig'){
                    return $scope.chatBigAttach;
                }
                else if($scope.vtype == 'chatSmall')
                    return $scope.chatSmallAttach;

            }

        }]
    };
}]);
