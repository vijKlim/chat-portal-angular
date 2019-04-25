// Create Khmerload namespace is it does not exists and correcting code Proger
var Khmerload = Khmerload || {};

Khmerload.SharedWebSocket = function(obj) {
    var self = this;

    // Master tab is referring tab with hold the connection,
    // and distribute the message to other child tabs.
    var isMaster = false;

    // Create an unique identifier for each tab,
    // the tab identifier will be used to decide which
    // one has the priority to become a master when
    // the previous master has retired
    var tabId = Date.now() + parseInt(Math.random() * 500);

    // Child timer will be used for checking whether
    // the master is still alive.
    var childTimer = null;
    var childTimerInterval = 7000;

    // Master timer will be used for telling the child
    // that the master is still active.
    var masterTimer = null;
    var masterTimerInterval = 3000;
    var masterTimeoutInterval = 5000;

    // Hold the Web Socket Connection, Only if this tab
    // is a master tab, otherwise, it will always be null.
    var webSocketConnection = null;

    // We are using local storage for communicate between tab
    var communicateChannelPrefix = "wss_" + hashString(obj.url);
    var communicateChannelForward = communicateChannelPrefix + "_forward";
    var communicateChannelMasterTimestamp = communicateChannelPrefix + "_timestamp";
    var communicateChannelMasterID = communicateChannelPrefix + "_master";

    // Hashing a string to integer. This will be used for creating
    // a local storage channel for communicate
    function hashString(str) {
        var hash = 0, i, chr, len;
        if (str.length == 0) return hash;

        for (i = 0, len = str.length; i < len; i++) {
            chr   = str.charCodeAt(i);
            hash  = ((hash << 5) - hash) + chr;
            hash |= 0; // Convert to 32bit integer
        }

        return hash;
    }

    // Check if the master is active or not active. We determine
    // if master is active checking the last time stamp that the
    // master has updated.
    function detectMaster() {
        var timestamp = localStorage.getItem(communicateChannelMasterTimestamp);

        if (timestamp === null) {
            return false;
        }

        timestamp = parseInt(timestamp);
        if (Date.now() - timestamp > masterTimeoutInterval) {
            return false;
        }

        return true;
    }

    // Constantly try to check if the master has retired
    function childProcess() {
        if (!detectMaster()) {
            challengeMaster();
        }
    }

    // Attempt to takeover as a master.
    function challengeMaster()
    {
        if (detectMaster()) return false;

        // Promote itself to master
        localStorage.setItem(communicateChannelMasterID, tabId);
        localStorage.setItem(communicateChannelMasterTimestamp, Date.now());
        isMaster = true;

        // Destroy a child timer if exists
        if (childTimer !== null) {
            clearInterval(childTimer);
        }

        // Create a master timer
        masterTimer = setInterval(function() {
            localStorage.setItem(communicateChannelMasterTimestamp, Date.now());
        }, masterTimerInterval);

        // Wait 0.5 seconds for resolving the master first,
        // before try to completely function as a master
        setTimeout(function() {
            if (isMaster) {
                // Now, we are officially a master
                if (typeof obj.master == "function") obj.master();
                makeConnection();
            }
        }, 500);
    }

    function demoteMaster()
    {
        isMaster = false;

        // Create child process again
        childTimer = setInterval(childProcess, childTimerInterval);

        // Destroy master timer
        if (masterTimer !== null) {
            clearInterval(masterTimer);
        }
    }

    function makeConnection()
    {
        // Create a web socket connection
        webSocketConnection = new Khmerload.NativeWebSocket({
            url: obj.url,
            open: function() {
                broadcast("OPEN");
                if (typeof obj.open == "function") obj.open();
            },
            message: function(msg) {
                // Forward the message to other child
                broadcast("MSG " + msg);
                if (typeof obj.message == "function") obj.message(msg);
            },
            close: function() {
                broadcast("CLOSE");
                if (typeof obj.close == "function") obj.close();
            },
            reconnect: obj.reconnect
        });
    }

    function broadcast(message)
    {
        localStorage.setItem(communicateChannelForward, Date.now() + " " + message);
    }

    function isJsonStr(str) {
        try {
            JSON.parse(str);
        } catch (e) {
            return false;
        }
        return true;
    }

    function clearStorageByTimestamp()
    {
        var timestamp = Date.now() - 60*60*24;
        var rxp = /^wss_\-?\d+_(forward|master|timestamp)/;
        var keys = Object.keys(localStorage),
            i = keys.length;

        while ( i-- ) {
            if(keys[i].match(rxp))
            {
                if(localStorage.getItem(keys[i]) && parseInt(localStorage.getItem(keys[i])) < timestamp)
                {
                    localStorage.removeItem(keys[i]);
                }
            }
        }

    }

    clearStorageByTimestamp();

    // Get tab ID
    self.getID = function () {
        return tabId;
    };

    self.send = function(message) {
        if (isMaster) {
            // if we are master, we can directly send using our connection
            webSocketConnection.send(message);
        } else {
            // forward to the master
            broadcast("SEND " + message);
        }
    };

    // Listen to our communicate channel
    window.addEventListener("storage", function(e) {
        // Try to resolve when multiple master is conflicted
        if (e.key == communicateChannelMasterID) {
            var newMaster = parseInt(e.newValue);
            if (isMaster && newMaster < tabId) {
                demoteMaster();
            }
        }

        // Listen to message forward channel
        if (e.key == communicateChannelForward) {
            var raw = e.newValue;
            var delimiterPosition = raw.indexOf(" ");

            // Invalid message, it is not possible to have a message without a space
            if (delimiterPosition < 0) {
                return;
            }

            // Ignore the first token, it is just a timestamp to help make message look unique
            raw = raw.substr(delimiterPosition + 1);

            delimiterPosition = raw.indexOf(" ");
            var command = (delimiterPosition < 0) ? raw : raw.substr(0, delimiterPosition);
            var message = (delimiterPosition < 0) ? "" :  raw.substr(delimiterPosition + 1);

            if (command === "MSG") {
                var jmsg =  message;
                if(isJsonStr(message)){
                    jmsg = JSON.parse(message);
                    jmsg['childChat'] = 1;
                    jmsg = JSON.stringify(jmsg);
                }
                if (typeof obj.message == "function") obj.message(jmsg);
            } else if (command === "SEND") {
                if (isMaster) {
                    webSocketConnection.send(message);
                }
            } else if (command === "OPEN") {
                if (typeof obj.open == "function") obj.open();
            } else if (command === "CLOSE") {
                if (typeof obj.close == "function") obj.close();
            }
        }
    });

    // Check if there is any current master that is active, otherwise,
    // attempt to takeover as a master.
    if (!detectMaster()) {
        challengeMaster();
    }

    // If you haven't become a master, we will become a child tab
    if (!isMaster) {
        childTimer = setInterval(childProcess, childTimerInterval)
    }

    // Return the SharedWebSocket connection
    return this;
};

/**
 * Define a reconnecting strategy. There are three
 * possible strategies that user can choose:
 *
 * (1) infinite reconnecting with constant time delay
 *
 *     eg. new ReconnectStrategy(5000);
 *     It will reconnect every 5 seconds.
 *
 *
 * (2) finite reconnecting time with variable time delay
 *
 *      eg. new ReconnectStrategy(5000, 10000, 15000);
 *		It will reconnect three times with 5 seconds, 10 seconds
 *      and 15 seconds respectively.
 *
 * (3) does not reconnect
 *
 *      eg. new ReconnectStrategy(false);
 *      Never connect
 */
Khmerload.ReconnectStrategy = function(option)
{
    var pointer = 0;

    /**
     * Reset the strategy to the fresh state.
     */
    this.reset = function() {
        pointer = 0;
    },

    /**
     * Call the provided callback with delay based on the provided
     * strategy option.
     * @param {boolean,array,number} callback Callback function that will be called according to our strategy
     */
        this.next = function(callback) {
            // (finite reconnecting time with variable time delay
            if (option.constructor === Array) {
                if (pointer < option.length) {
                    setTimeout(callback, option[pointer]);
                    pointer++;
                }
                // infinite reconnecting with constant time delay
            } else if (typeof option === "number") {
                setTimeout(callback, option);
            }
        }
};


Khmerload.NativeWebSocket = function(obj)
{
    var self = this;

    // By default, the reconnect option is off
    if (typeof obj.reconnect === "undefined") {
        obj.reconnect = false;
    }

    // Create a reconnect strategy from given option
    var reconnect = new Khmerload.ReconnectStrategy(obj.reconnect);

    // Connecting new connection
    function makeConnection() {
        var ws = new WebSocket(obj.url);

        ws.onerror = function() {
            if (typeof obj.error === "function") obj.error();
        };

        ws.onmessage = function(msg) {
            if (typeof obj.message === "function") obj.message(msg.data);
        };

        ws.onclose = function() {
            if (typeof obj.close === "function") obj.close();

            // Checking for reconnect strategy
            reconnect.next(makeConnection);
        };

        ws.onopen = function() {
            if (typeof obj.open === "function") obj.open();
            reconnect.reset();
        };

        return ws;
    }

    return makeConnection();
};