{literal}
    <div class="modal_online" style="z-index:9999;right:48%;height:405px!important;" pick-out data-name="aw"  ng-style="{display: visibleAccounts ? 'block' : 'none'}">
        <div class="head_modal">
            <div class="icon_online"><img ng-src="/themes/fenix/img/chat_icons/online_icon.png"></div>
            <div class="login_modal">
                <a  class="toggle-filter-contacts" >Аккаунты</a>

            </div>
            <div class="options_modal"><span class="delete_modal" id="close1" ng-click="visibleAccounts = false"></span></div>
        </div>
        <div class="body_modal_online">
            <!--<div style="height:335px;" id="for_scrollC">-->
            <div class='scroll-pane' scroll-pane>
                <div>
                    <div  style="clear:both;" class="users_online" ng-repeat="account in accounts" ng-click="showContacts(account.id);">
                        <div class="avatar_user_online"><a href="profile/{{account.id}}/"><img ng-src="{{account.logo}}"></a></div>
                        <div class="login_online_user" style="font-size:11px;white-space: nowrap;">{{account.name}}</div>


                    </div>
                </div>
            </div>

        </div>
        <div class="search_online_modal">
            <textarea rows="" style="overflow:hidden" placeholder="Быстрый поиск контакта" ng-model="searchText"></textarea>
        </div>
    </div>
{/literal}