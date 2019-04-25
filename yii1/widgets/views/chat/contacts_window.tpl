{literal}
    <!--чтоб добавить перетягивание добавить класс  draggable в елемент class="modal_online"-->
    <div class="modal_online" style="z-index:9999;" pick-out data-name="cw" ng-style="{display: visibleContacts ? 'block' : 'none'}" ce-resize>
        <!--чтоб добавить перетягивание добавить это: my-draggable isparent="1" в елемент class="head_modal"-->
        <div class="head_modal" >
            <div class="icon_online"><img ng-src="/themes/fenix/img/chat_icons/online_icon.png"></div>
            <div class="login_modal">
                <a  class="toggle-filter-contacts" ng-click="toggleFilterOnline()" ng-if="titleToggleFilter">{{titleToggleFilter}}</a>

                <a  class="toggle-filter-contacts" ng-click="toggleFilterOnline()" ng-if="!titleToggleFilter">Контакты (Онлайн {{contactsManager.countOnline}})</a>

                <a ng-click="toolsShow = !toolsShow" style="display:inline-block;margin: 0 7px;vertical-align: middle;"><i class="chat_tools_icon"></i></a>
            </div>
            <div class="options_modal"><span class="delete_modal" id="close1" ng-click="visibleContacts = false"></span></div>
        </div>
        <!--если шаблон используется  для мультиаккаунтинга - добавляем информацию о получателе сообщения-->
        <div class="head_modal" style="background-color: #faa732;" ng-if="chatConfig.muacc">
            <!--<div style="width:34px;margin-top: 9px;margin-left: 2px;float: left;color:#fff; font-weight: 600;">Кому </div>-->
            <div class="avatar_modal"><img ng-src="{{ activeAccountInfo.logo ? activeAccountInfo.logo : '/themes/fenix/img/default-user.png'}}"></div>
            <div class="login_modal"><a href="javascript:void(0);">{{activeAccountInfo.name| limitTo:25}}{{activeAccountInfo.name.length > 25 ? '...':''}}</a></div>
        </div>
        <div class="body_modal_online">
            <!--<div style="height:335px;" id="for_scrollC">-->
            <div class='scroll-pane' data-action="0" data-spos="echat_contacts" scroll-pane>
                <div>
                    <!-- | filter:searchText |orderBy:'-inOnline'  - это работает только с масивами-->
                    <div  style="clear:both;" ng-if="!toolsShow" class="users_online" ng-repeat="contact in contacts | filterIsOnline:inOnline | textSearchFilter:searchText | orderObjectBy:'inOnline':true" ng-click="showDialog(contact);">
                        <div style="height: 54px;">
                            <div class="avatar_user_online"><!--<a href="{{contact.type == chatService.typeContact.Alone  && chatConfig.isShop == 0 ? domenApp+'/profile/'+contact.members[0] : 'javascript:void(0);' }}/">--><img ng-src="{{contact.logo}}"><!--</a>--></div>
                            <div class="login_online_user" style="font-size:11px;white-space: nowrap;">
                                {{contact.name| limitTo:25}}{{contact.name.length > 25 ? '...':''}}<span ng-if="contact.id_external > 0 && chatService.isSP()">(id: {{contact.id_external}})</span>
                                <div ng-if="contact.type == chatService.typeContact.Group" style="line-height: 9px;color: #ccc;">
                                    группа
                                </div>
                            </div>
                            <div class="count_online_msg" >
                                <span ng-if="msgsManager.getCountNewMsgsContact(activeAccount,contact.id,contact.type) > 0"><span>{{msgsManager.getCountNewMsgsContact(activeAccount,contact.id,contact.type)}}</span><img ng-src="themes/fenix/img/chat_icons/count_online_users_msg.png"></span>
                                <span  title="online" ng-if="contact.inOnline " style="background: #faa732 none repeat scroll 0 0;border-radius: 20px;display: inline-block;height: 8px;width: 8px;"></span>

                                <div class="toggle-group-members-list"  ng-if="contact.type == chatService.typeContact.Group && contact.inOnline" ng-click="showGroupMembers($event,contact)">
                                    <i class="icon-g-arrow g-up" ng-if="contact.visibleGroupMembers" ></i>
                                    <i class="icon-g-arrow g-down" ng-if="!contact.visibleGroupMembers" ></i>
                                </div>
                            </div>
                        </div>

                        <div style="clear: both;font-size: 11px;white-space: nowrap;background-color: #ddd;" ng-if="contact.type == chatService.typeContact.Group" ng-show="contact.visibleGroupMembers">
                            <ul>
                                <li ng-repeat="member in contact.onlineMembersInfo">
                                    <img width="30" ng-src="{{member.logo}}">
                                    {{member.name}}
                                </li>
                            </ul>
                        </div>

                    </div>

                    <div ng-if="!toolsShow && existsNewShops && chatService.isSP()" style="text-align: center;background-color: #faa732;color:#fff;">Все магазины в онлайне:</div>
                    <div  style="clear:both;" ng-if="!toolsShow && existsNewShops" class="users_online" ng-repeat="contact in newShops" ng-click="writeNewShopMsg(contact.id);">
                        <div style="height: 54px;">
                            <div class="avatar_user_online"><img ng-src="{{contact.logo}}"></div>
                            <div class="login_online_user" style="font-size:11px;white-space: nowrap;">
                                {{contact.name| limitTo:25}}{{contact.name.length > 25 ? '...':''}}(id: {{contact.id_external}})
                            </div>

                        </div>

                    </div>







                    <!--Настройки чата-->
                    <chat-setting ng-if="toolsShow"></chat-setting>

                </div>



            </div>

        </div>
        <div class="search_online_modal">
            <textarea rows="" style="overflow:hidden" placeholder="Быстрый поиск контакта" ng-model="searchText"></textarea>
        </div>
    </div>
    <br>
{/literal}