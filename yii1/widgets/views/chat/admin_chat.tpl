{include file='protected/modules/pfront/widgets/views/chat/uploader_chat.tpl'}

<!--container for angular-->
<div id="echat" ng-controller="adminEchatController">
    <div id="echat-visible" style="display:none;height:0;">

        {include file='protected/modules/pfront/widgets/views/chat/msgs_window.tpl'}
        {include file='protected/modules/pfront/widgets/views/chat/contacts_window.tpl'}
        {include file='protected/modules/pfront/widgets/views/chat/accounts_window.tpl'}
        {include file='protected/modules/pfront/widgets/views/chat/informer.tpl'}

    </div>
</div>
