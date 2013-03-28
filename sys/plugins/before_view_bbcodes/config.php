<?php 
$editor_set = array (
  0 => 
  array (
    'title' => 'WysiBB (светлая схема)',
    'editor_head' => '<script language="JavaScript" type="text/javascript" src="{{ www_root }}/sys/js/wysibb/jquery.wysibb.min.js"></script>
<script language="JavaScript" type="text/javascript" src="{{ www_root }}/sys/js/wysibb/fapos2.js"></script>
<link rel="stylesheet" href="{{ www_root }}/sys/js/wysibb/wbbtheme.css" />',
    'editor_body' => '<script language="JavaScript" type="text/javascript">
$(document).ready(function() {
	var wbbOpt = {
		smileList: [
		{% for smile in smiles_list %}
			{title:"{{ smile.from }}", img: \'<img src="{{ www_root }}/sys/img/smiles/{{ smiles_set }}/{{ smile.to }}" class="sm">\', bbcode:"{{ smile.from }}"},
		{% endfor %}
		]
	}
	$("#editor").wysibb(wbbOpt)
});
</script>',
    'editor_buttons' => '',
    'editor_text' => 'id="editor"',
    'editor_forum_text' => 'id="editor"',
    'editor_forum_quote' => 'onClick="quoteSelection(\'{{ post.author.name }}\');" onMouseOver="catchSelection(); this.className=\'quoteAuthorOver\'" onMouseOut="this.className=\'quoteAuthor\'"',
    'editor_forum_name' => 'onClick="$(\'#editor\').insertAtCursor(\'<b>{{ post.author.name }}</b>, \', false); return false;"',
    'default' => true,
  ),
  1 => 
  array (
    'title' => 'WysiBB (темная схема)',
    'editor_head' => '<script language="JavaScript" type="text/javascript" src="{{ www_root }}/sys/js/wysibb/jquery.wysibb.min.js"></script>
<script language="JavaScript" type="text/javascript" src="{{ www_root }}/sys/js/wysibb/fapos2.js"></script>
<link rel="stylesheet" href="{{ www_root }}/sys/js/wysibb/wbbtheme_dark.css" />',
    'editor_body' => '<script language="JavaScript" type="text/javascript">
$(document).ready(function() {
	var wbbOpt = {
		smileList: [
		{% for smile in smiles_list %}
			{title:"{{ smile.from }}", img: \'<img src="{{ www_root }}/sys/img/smiles/{{ smiles_set }}/{{ smile.to }}" class="sm">\', bbcode:"{{ smile.from }}"},
		{% endfor %}
		]
	}
	$("#editor").wysibb(wbbOpt)
});
</script>',
    'editor_buttons' => '',
    'editor_text' => 'id="editor" style="background:#333;color:#fff;"',
    'editor_forum_text' => 'id="editor" style="background:#333;color:#fff;"',
    'editor_forum_quote' => 'onClick="quoteSelection(\'{{ post.author.name }}\');" onMouseOver="catchSelection(); this.className=\'quoteAuthorOver\'" onMouseOut="this.className=\'quoteAuthor\'"',
    'editor_forum_name' => 'onClick="$(\'#editor\').insertAtCursor(\'<b>{{ post.author.name }}</b>, \', false); return false;"',
    'default' => false,
  ),
);
?>