/**
 * Created by proger on 21.01.2016.
 */
//function placeNode(node, top, left) {
//    node.css({
//        position: "absolute",
//        top: top + "px",
//        left: left + "px"
//    });
//}

// To create a empty resizable and draggable box
//chatApp.directive("ceBoxCreator",['$document', '$compile', function($document, $compile) {
//    return {
//        restrict: 'A',
//        link: function($scope, $element, $attrs) {
//            $element.on("click", function($event) {
//
//                var newNode = $compile('<div class="drag-box-chat" ce-drag ce-resize></div>')($scope);
//                placeNode(newNode, $event.pageY - 25, $event.pageX - 25);
//                angular.element($document[0].body).append(newNode);
//            });
//        }
//    }
//}]);

// To manage the drag
//chatApp.directive("ceDrag", ['$document',function($document) {
//    return function($scope, $element, $attr) {
//        var startX = 0,
//            startY = 0;
//        //var newElement = angular.element('<div class="draggable"></div>');
//
//        //$element.append(newElement);
//        $element.on("mousedown", function($event) {
//
//            $event.preventDefault();
//
//            // To keep the last selected box in front
//            startX = $event.pageX - $element.parent().offset().left;
//            startY = $event.pageY - $element.parent().offset().top;
//            $element.parent().on("mousemove", mousemove);
//            $element.parent().on("mouseup", mouseup);
//        });
//
//        function mousemove($event) {
//            console.log($element.parent());
//            placeNode( $element.parent() , $event.pageY - startY , $event.pageX - startX );
//        }
//
//        function mouseup() {
//            $element.parent().off("mousemove", mousemove);
//            $element.parent().off("mouseup", mouseup);
//        }
//    };
//}]);


// To manage the resizers
chatApp.directive("ceResize", ['$document',function($document) {
    return function($scope, $element, $attr) {
        //Reference to the original
        var $mouseDown;
        var minWidth = 250,maxWidth = 1100, minHeight = 400, maxHeight = 800;
        // Function to manage resize up event
        var resizeUp = function($event) {
            var margin = 50,
                lowest = $mouseDown.top + $mouseDown.height - margin,
                top = $event.pageY > lowest ? lowest : $event.pageY,
                height = $mouseDown.top - top + $mouseDown.height;
            if(maxHeight > height && height > minHeight)
                $element.css({
                    top: top + "px",
                    height: height + "px",
                    'font-size':width/100*3 > 14 ? width/100*3+'px' : 14+'px'
                });
        };

        // Function to manage resize right event
        var resizeRight = function($event) {
            var margin = 50,
                leftest = $element[0].offsetLeft + margin,
                width = $event.pageX > leftest ? $event.pageX - $element[0].offsetLeft : margin;
            if(maxWidth > width && width > minWidth){
                //var font = (parseFloat($element.children().css('font-size'))+0.1)+'px';
                //console.log('font size: '+font);
                $element.css({
                    width: width + "px",
                    'font-size':width/100*3 > 14 ? width/100*3+'px' : 14+'px'
                });
            }

        };

        // Function to manage resize down event
        var resizeDown = function($event) {
            var margin = 50,
                uppest = $element[0].offsetTop + margin,
                height = $event.pageY > uppest ? $event.pageY - $element[0].offsetTop : margin;
            if(maxHeight > height && height > minHeight)
                $element.css({
                    height: height + "px",
                    'font-size':width/100*3 > 14 ? width/100*3+'px' : 14+'px'
                });
        };

        // Function to manage resize left event
        function resizeLeft ($event) {
            var margin = 50,
                rightest = $mouseDown.left + $mouseDown.width - margin,
                left = $event.pageX > rightest ? rightest : $event.pageX,
                width = $mouseDown.left - left + $mouseDown.width;
            if(maxWidth > width && width > minWidth)
                $element.css({
                    left: left + "px",
                    width: width + "px",
                    'font-size':width/100*3 > 14 ? width/100*3+'px' : 14+'px'
                });
        };

        var createResizer = function createResizer( className , handlers ){

            newElement = angular.element( '<div class="' + className + '"></div>' );
            $element.append(newElement);
            newElement.on("mousedown", function($event) {

                $document.on("mousemove", mousemove);
                $document.on("mouseup", mouseup);

                //Keep the original event around for up / left resizing
                $mouseDown = $event;
                $mouseDown.top = $element[0].offsetTop;
                $mouseDown.left = $element[0].offsetLeft;
                $mouseDown.width = $element[0].offsetWidth;
                $mouseDown.height = $element[0].offsetHeight;

                function mousemove($event) {
                    $element.css({"border": "solid 1px #C1E0FF"})
                        .children().css({opacity:0});
                    $event.preventDefault();
                    for( var i = 0 ; i < handlers.length ; i++){
                        handlers[i]( $event );
                    }
                }

                function mouseup() {
                    $('.body_modal_online').height( $element.height());
                    $element.css({"border": 'none'})
                        .children().animate({opacity:1},1000);
                    $document.off("mousemove", mousemove);
                    $document.off("mouseup", mouseup);

                }
            });
        };

        createResizer( 'sw-resize' , [ resizeDown , resizeLeft ] );
        createResizer( 'ne-resize' , [ resizeUp   , resizeRight ] );
        createResizer( 'nw-resize' , [ resizeUp   , resizeLeft ] );
        createResizer( 'se-resize' , [ resizeDown ,  resizeRight ] );
        createResizer( 'w-resize' , [ resizeLeft ] );
        createResizer( 'e-resize' , [ resizeRight ] );
        createResizer( 'n-resize' , [ resizeUp ] );
        createResizer( 's-resize' , [ resizeDown ] );
    };

}]);

chatApp.directive('myDraggable', ['$document',
    function ($document) {
        return {
            restrict: 'A',
            replace: false,

            link: function (scope, elm, attrs) {
                var startX, startY, initialMouseX, initialMouseY;

                elm.bind('mousedown', function ($event) {

                        startX = elm.prop('offsetLeft');
                        startY = elm.prop('offsetTop');
                        initialMouseX = $event.clientX;
                        initialMouseY = $event.clientY;
                        $document.bind('mousemove', mousemove);
                        $document.bind('mouseup', mouseup);
                        $event.preventDefault();

                });

                if(parseInt(attrs.isparent) > 0)
                    elm = elm.parent();

                function getMaxPos() {
                    return {
                        max: {
                            x: window.innerWidth - elm.prop('offsetWidth'),
                            y: window.innerHeight - elm.prop('offsetHeight')
                        },
                        min: {
                            x: 0,
                            y: 0
                        }
                    };
                }

                function mousemove($event) {
                    var x = startX + $event.clientX - initialMouseX;
                    var y = startY + $event.clientY - initialMouseY;
                    //console.log("x " + x);

                    var limit = getMaxPos();
                    //console.log(limit);
                    x = (x < limit.max.x) ? ((x > limit.min.x) ? x : limit.min.x) : limit.max.x;
                    y = (y < limit.max.y) ? ((y > limit.min.y) ? y : limit.min.y) : limit.max.y;
                    elm.css({
                        top: y + 'px',
                        left: x + 'px'
                    });
                    $event.preventDefault();
                }

                function mouseup() {
                    $document.unbind('mousemove', mousemove);
                    $document.unbind('mouseup', mouseup);
                }
            }
        };
    }]);

