{literal}
    <div class="modal_msg echat_dialog" style="z-index:9999;display: none;position:fixed;width:280px!important;" pick-out
         data-name="mw" ng-style="{display: visibleMsgs ? 'block' : 'none'}" ce-resize
         xmlns="http://www.w3.org/1999/html">

        <div class="head_modal">
            <div class="avatar_modal"><img ng-src="{{ partnerInfo.logo ? partnerInfo.logo : '/themes/fenix/img/default-user.png'}}"></div>
            <div class="login_modal"><a href="" ng-click="chatService.viewProfile($event, partnerInfo.type,partnerInfo)" target="_blank" >{{partnerInfo.name| limitTo:20}}{{partnerInfo.name.length > 20 ? '...':''}}<span ng-if="partnerInfo.id_external > 0 && chatService.isSP()"> (id: {{partnerInfo.id_external}})</span></a></div>


            <div class="options_modal">
                <a ng-show="thread[0].type == chatService.typeContact.Group && showAddInGroup && !chatConfig.isShop"
                   ng-click="addInGroup()" title="добавить в группу" style="display:inline-block;margin: 12px 9px 0 0;vertical-align: middle;">
                    <i class="add_in_group_icon"></i>
                </a>
                <a ng-hide="thread[0].type === chatService.typeContact.Group || chatConfig.isShop"
                   ng-click="transferDialog()" title="создать группу" style="display:inline-block;margin: 12px 9px 0 0;vertical-align: middle;">
                    <i class="transfer_dialog_icon"></i>
                </a>

                <span class="delete_modal" id="close2" ng-click="visibleMsgs = false"></span>
                <span class="maximaze_chat" ng-click="toMaximizeChat()"></span>
            </div>
        </div>
        <!--если шаблон используется  для мультиаккаунтинга - добавляем информацию о получателе сообщения-->
        <div class="head_modal" style="background-color: #faa732;" ng-if="chatConfig.muacc">
            <!--<div style="width:34px;margin-top: 9px;margin-left: 2px;float: left;color:#fff; font-weight: 600;">Кому </div>-->
            <div class="avatar_modal"><img ng-src="{{ activeAccountInfo.logo ? activeAccountInfo.logo : '/themes/fenix/img/default-user.png'}}"></div>
            <div class="login_modal"><a href="javascript:void(0);">{{activeAccountInfo.name| limitTo:25}}{{activeAccountInfo.name.length > 25 ? '...':''}}</a></div>
        </div>

        <div class="body_modal">

            <!--<div  style="height:313px; " id="for_scrollM">-->
            <div class='scroll-pane' data-action="1" data-spos="echat_msgs" scroll-pane>
                <div>
                    <div ng-hide="thread[0].type == chatService.typeContact.Support" class="ch-select-date-period" >
                        <a href="javascript:void(0)" ng-click="visibleDatePeriods = !visibleDatePeriods">История</a>
                        <div ng-show="visibleDatePeriods">
                            <ul>
                                <li><a href="javascript:void(0)" ng-click="showHistoryMsgs('one-year')">1 год</a></li>
                                <li><a href="javascript:void(0)" ng-click="showHistoryMsgs('six-months')">6 месяцев</a></li>
                                <li><a href="javascript:void(0)" ng-click="showHistoryMsgs('three-months')">3 месяца</a></li>
                                <li><a href="javascript:void(0)" ng-click="showHistoryMsgs('thirty-days')">30 дней</a></li>
                                <li><a href="javascript:void(0)" ng-click="showHistoryMsgs('seven-days')">7 дней</a></li>
                                <li><a href="javascript:void(0)" ng-click="showHistoryMsgs('yesterday')">Вчера</a></li>
                                <li><a href="javascript:void(0)" ng-click="showHistoryMsgs('today')">Сегодня</a></li>
                            </ul>
                        </div>
                    </div>
                    <div ng-hide="chatService.dataLoaded" class="preloader" style=" text-align: center;margin-top: 20px;">
                        <img width="20" height="20" ng-src="themes/fenix/img/ajax-loader.gif"/>
                    </div>

                    <div ng-show="chatService.dataLoaded" ng-repeat="(key, message) in thread">
                        <div ng-if="message.own_msg" ng-show="(message.msg || (!message.msg && message.attach.data)) ? true: false">
                            <div>

                                <div class="message_modal_my" style="width: 83%;" ng-class="(message.is_internal == '1') ? 'ch_internal_msg' : ''" >
                                    <div>
                                        <!--chat.js in dir derectives-->
                                        <select-msg smsg="{id:message.id,msg:message.msg}" ng-if="chatService.isSP() &&  message.is_internal != '1'"></select-msg>
                                        <span style="display:inline;word-wrap:break-word;" ng-if="message.is_internal != '1'" ng-bind-html="chatService.renderHtml(message.msg)"></span>
                                        <span style="display:inline;" ng-if="message.is_internal == '1'" dynamic="message.msg"></span>
                                        <div ng-if="message.is_internal != '1'" style=" margin-top:16px;font-size:9px;font-weight: 600;">
                                            <msg-attach iattach="message.attach.data" vtype="chatSmall" ></msg-attach>
                                            <div style="clear:both;float: right;">
                                                {{message.date_created * 1000 | date:'dd.MM в HH:mm'}}
                                            </div>
                                        </div>
                                    </div>

                                </div>
                                <span style="margin-left: 10px;padding:2px;height:25px;background-color:#faa732;color:#fff;" ng-if="message.support.length > 0">{{message.support.name}}</span>
                            </div>

                        </div>
                        <div ng-if="!message.own_msg" ng-show="(message.msg || (!message.msg && message.attach.data)) ? true: false">
                            <div>
                                <div class="message_modal_me" style="width: 83%;" ng-class="(message.is_internal == '1') ? 'ch_internal_msg' : ''" >
                                    <div >
                                        <!--chat.js in dir derectives-->
                                        <select-msg smsg="{id:message.id,msg:message.msg}" ng-if="chatService.isSP() &&  message.is_internal != '1'"></select-msg>
                                        <span style="display:inline;" ng-if="message.is_internal != '1'" ng-bind-html="chatService.renderHtml(message.msg)"></span>
                                        <span style="display:inline;" ng-if="message.is_internal == '1'" dynamic="message.msg"></span>
                                        <span style="background-color: #0077BE;color:#fff;">{{(message.is_copy == '1') ? ' (копия) ' : ''}}</span>
                                        <div ng-if="message.is_internal != '1'" style=" margin-top:16px;font-size:9px;font-weight: 600;">
                                            <msg-attach iattach="message.attach.data" vtype="chatSmall" ></msg-attach>
                                            <div style="clear:both;float: right;">
                                                {{message.sender.name}}{{message.sender.name ? ' - ': ''}}{{message.date_created * 1000 | date:'dd.MM в HH:mm'}}
                                            </div>

                                        </div>
                                    </div>
                                    <span style="margin-left: 10px;padding:2px;height:25px;background-color:#faa732;color:#fff;" ng-if="message.support.length > 0">{{message.support.name}}</span>
                                </div>
                            </div>

                        </div>

                        <div ng-if="message.wait_msg">
                            <div>
                                <div class="message_modal_me" style="width: 83%;">
                                    <div >
                                        <span style="display:inline;" >
                                            <img width="25" height="25" ng-src="/themes/fenix/img/chat_icons/waitingmsg.gif"/>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <support-controls contact="{{activeContact}}" account="{{activeAccount}}" lastmsg="{{thread[thread.length-1]['is_internal'] == '1' ? thread[thread.length-2] : thread[thread.length-1]}}" viewtype="2" ng-if="chatService.isSP()"></support-controls>
        <div class="write_modal" style="position: relative;">
            <div isolate>
                <!--ng-focus="clearNewMsg()"-->
                <div id="e_editor" contenteditable="true" data-ph="Введите сообщение и нажмите Enter" ng-keypress="sendMsg($event)" ng-focus="informTypedMsg($event,true)" ng-blur="informTypedMsg($event,false)"  paste-media></div>
                <!--<textarea id="e_editor"  placeholder="Введите сообщение и нажмите Enter" ng-keypress="sendMsg($event)" ng-focus="informTypedMsg($event,true)" ng-blur="informTypedMsg($event,false)"  paste-media></textarea>-->
                <div media-preview></div>
                <img class="e_img_storage" data-id-editor="e_editor" data-type-preview="small" imageonload />
                <div image-preview data-type-preview="small"></div>
                <div class="photo_ico_msg" data-id-uploader="e_chatupload" data-type-preview="small" add-image-msg ></div>
            </div>

        </div>
    </div>
{/literal}