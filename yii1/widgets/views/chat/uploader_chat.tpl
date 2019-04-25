{*используется angularjs для вставки картинок в сообщение*}
<div style="display: none;">
    {$this->widget('ext.EAjaxUpload.EAjaxUpload', [
    'id'    =>'e_chatupload',
    'config'=>
    [
    'action'            => "/profile/{$Yii->client->id}/uploadImgChat/?id={$Yii->client->id}",
    'allowedExtensions' => ['jpg', 'jpeg', 'gif', 'png']  ,
    'sizeLimit'         => 10485760,
    'minSizeLimit'      => 1024,
    'multiple'          => false,
    'onComplete'        => 'js:function(id, fileName, responseJSON){$("body").find(".load_here").attr("src","/uploads/portal/chat/"+responseJSON.filename).load(function(){ $("#notSelectedAvatar").remove(); }) }'
    ]
    ], true)}
</div>