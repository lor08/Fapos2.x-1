<?php

class WysiBB {

public function common($params) {
return str_replace('</head>', '
<script src="/sys/plugins/before_view_wysibb/public/jquery.wysibb-1.3.1.js"></script>
<link rel="stylesheet" href="/sys/plugins/before_view_wysibb/public/wbbtheme.css" />

<script>
$(document).ready(function() {
 var wbbOpt = {
  buttons: "bold,italic,underline,strike,|,justifyleft,justifycenter,justifyright,|,code,quote,bullist,|,smilebox,video,link,spoiler,fontcolor,fontsize,removeFormat",
  traceTextarea:true
 }
 $("#editor").wysibb(wbbOpt)
 $(".BB_buttons").hide()
})
</script>
</head>', $params);
}

}
?>