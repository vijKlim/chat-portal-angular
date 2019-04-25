{literal}
    <style>
        #info-online{
            height:auto!important;
            margin-top:0!important;
        }
        #info-online .widget-online-contacts{
            background-color:#EDF1F5;
            width:50px;
            height: auto;
            z-index: 10000;
        }
        #info-online .widget-online-contacts ul{
            width:44px;
            background-color: #EDF1F5;
            margin: 0 0 0 4px!important;
            list-style: none;
        }
        #info-online .widget-online-contacts ul li{
            position: relative;
            line-height: 30px;
            padding-top: 4px;
        }
        #info-online .widget-online-contacts ul li .support_marker{
            position: absolute;
            left:0;
            top:5px;
        }
        #info-online .widget-online-contacts ul li img{
            display:block;
            border-radius: 2px;
            max-height: 40px;
            max-width: 40px;
            border: 2px solid #EDF1F5;
        }
        #info-online .widget-online-contacts ul li img:hover{
            border: 2px solid #faa732;
        }
        #info-online .del_block{
            position: absolute;
            background-color: #faa732;
            top: 10px;
            right: 0;
            padding: 1px 2px;
            line-height: 12px;
            font-size: 12px;
            cursor: pointer;
            color: #fff;
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-weight: bold;
            text-align: center;
            -webkit-transform:rotate(90deg);
            -moz-transform:rotate(90deg);
            transform:rotate(90deg);

        }
        #info-online .del_block:hover{
            background-color: red;
        }

    </style>

    <div  id="info-online">
        <div class="widget-online-contacts" ng-if="msgsManager.stackNewMsgs">
            <ul>
                <li ng-repeat="new in msgsManager.stackNewMsgs">
                    <img ng-if="new.isInternal == '1'" ng-src="{{chatConfig.domenApp+'/favicon.ico'}}" class="support_marker">
                    <img ng-src="{{new.logo}}" title="" data-html="true" data-toggle="tooltip"
                         data-placement="left" tooltip ng-mouseover="showNewMsgInfo($event,new)" ng-click="setViewedThread(new.contactId,new.contactType)">
                    <div class="del_block" ng-click="closeNewMsgBlock($event,new)">x</div>
                </li>
            </ul>
        </div>
    <span class="ch-info-online" id="ch-info-online-contact" ng-click="showWindowContacts()">
        {{online.countOnlineUsers}} <i class="icon-user icon-white"></i></span>
        <span style="display:block;border-bottom: 1px solid #fff;"></span>
        <span blink-informer  class="ch-info-online" id="ch-info-online-msg" ng-click="setViewedThread()">{{msgsManager.countNewMsg}} <i class="icon-comment icon-white"></i></span>
    </div>
{/literal}
