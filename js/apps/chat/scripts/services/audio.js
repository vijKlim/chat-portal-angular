/**
 * Created by proger on 17.11.2015.
 */

chatApp.service('audio', function(){
   var audio;

    function play(url){

        if(audio != null){
            audio.pause();
        }
        audio = new Audio;
        audio.src = "/lib/js/angular/apps/chat/audio/chat-zp3.mp3"//url;
        audio.autoplay = true;
        audio.controls = true;
    }

    return {
        play: play
    }
});