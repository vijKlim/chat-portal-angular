{include file='protected/modules/pfront/widgets/views/chat/uploader_chat.tpl'}

<style>
    .draggable {
        position: absolute;
        cursor: pointer; }


    .n-resize {
        position: absolute;
        top: -3px;
        left: 0px;
        width: 100%;
        height: 5px;
        cursor: n-resize; }

    .e-resize {
        position: absolute;
        top: 0px;
        left: calc(100% - 2px);
        width: 5px;
        height: 100%;
        cursor: w-resize; }

    .s-resize {
        position: absolute;
        top: calc(100% - 2px);
        left: 0px;
        width: 100%;
        height: 5px;
        cursor: s-resize; }

    .w-resize {
        position: absolute;
        top: 0px;
        left: -3px;
        width: 5px;
        height: 100%;
        cursor: e-resize; }

    .nw-resize {
        position: absolute;
        top: -3px;
        left: -3px;
        width: 7px;
        height: 7px;
        cursor: nw-resize; }

    .ne-resize {
        position: absolute;
        top: -3px;
        left: calc(100% - 4px);
        width: 7px;
        height: 7px;
        cursor: ne-resize; }

    .se-resize {
        position: absolute;
        top: calc(100% - 4px);
        left: calc(100% - 4px);
        width: 7px;
        height: 7px;
        cursor: se-resize; }

    .sw-resize {
        position: absolute;
        top: calc(100% - 4px);
        left: -3px;
        width: 7px;
        height: 7px;
        cursor: sw-resize; }


</style>

<!--container for angular-->
<div id="echat" ng-controller="echatController" style="overflow: hidden;">
    <div id="echat-visible" style="display:none;height:0;">
        {include file='protected/modules/pfront/widgets/views/chat/msgs_window.tpl'}
        {include file='protected/modules/pfront/widgets/views/chat/contacts_window.tpl'}
        {include file='protected/modules/pfront/widgets/views/chat/accounts_window.tpl'}
        {include file='protected/modules/pfront/widgets/views/chat/informer.tpl'}

    </div>
</div>