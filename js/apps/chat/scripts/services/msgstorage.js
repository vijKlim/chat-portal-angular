/**
 * Created by proger on 06.10.2015.
 */

chatApp.service('msgStorage', function () {
    var _msg = '';
    return {
        setMsg: function (msg) {
            _msg = msg;
        },
        getMsg: function () {
            return _msg;
        }
    }
});

