WBBPRESET = {
	buttons: 'bold,italic,underline,strike,|,justifyleft,justifycenter,justifyright,|,smilebox,|,code,quote,spoiler,hide,bullist,numlist,|,link,img,|,fontcolor,fontsize,removeFormat',
	traceTextarea: true,
	imgupload: false,
	allButtons: {
		spoiler : {
			title: CURLANG.spoiler,
			buttonText: 'spoiler',
			transform : {
				'<div><div><b>Сворачиваемый текст</b></div><div style="border: 1px inset ; overflow: auto;">{SELTEXT}</div></div>':"[spoiler]{SELTEXT}[/spoiler]"
			}
		},
		hide : {
			title: 'Скрытый текст',
			buttonText: 'hide',
			transform : {
				'<div><div><b>Скрытый текст</b></div><div style="border: 1px inset ; overflow: auto;">{SELTEXT}</div></div>':"[hide]{SELTEXT}[/hide]"
			}
		},
		quote : {
			transform : { 
				'<div class="bbQuoteBlock"><div class="bbQuoteName"><b>Цитата</b></div><div class="quoteMessage">{SELTEXT}</div></div>':'[quote]{SELTEXT}[/quote]',
				'<div class="bbQuoteBlock"><div class="bbQuoteName"><b>{AUTHOR} пишет:</b></div><div class="quoteMessage">{SELTEXT}</div></div>':'[quote="{AUTHOR}"]{SELTEXT}[/quote]',
				'<div style="" class="bbQuoteBlock"><div class="bbQuoteName"><b>{AUTHOR} пишет:</b></div><div class="quoteMessage">{SELTEXT}</div></div>':'[quote={AUTHOR}]{SELTEXT}[/quote]'
			}
		},
		code: {
			transform: {
				'<div class="bbCodeBlock"><div class="bbCodeName"><b>Code:</b></div><div class="codeMessage" style="border: 1px inset ; overflow: auto;">{SELTEXT}</div></div>':'[code]{SELTEXT}[/code]',
				'<div class="codePHP">{SELTEXT}</div>':'[php]{SELTEXT}[/php]',
				'<div class="codeSQL">{SELTEXT}</div>':'[sql]{SELTEXT}[/sql]',
				'<div class="codeJS">{SELTEXT}</div>':'[js]{SELTEXT}[/js]',
				'<div class="codeCSS">{SELTEXT}</div>':'[css]{SELTEXT}[/css]',
				'<div class="codeHTML">{SELTEXT}</div>':'[html]{SELTEXT}[/html]',
				'<div class="codeHTML codeXML">{SELTEXT}</div>':'[xml]{SELTEXT}[/xml]'
			}
		},
		bullist: {
			transform: {
				'<ul>{SELTEXT}</ul>':'[list]{SELTEXT}[/list]',
				'<li>{SELTEXT}</li>':'[*]{SELTEXT[^\[\]\*]}'
			}
		},
		numlist: {
			transform: {
				'<ol>{SELTEXT}</ol>':'[list=1]{SELTEXT}[/list]',
				'<ol type="a">{SELTEXT}</ol>':'[list=a]{SELTEXT}[/list]',
				'<li>{SELTEXT}</li>':'[*]{SELTEXT[^\[\]\*]}'
			}
		},
		img : {
			transform : {
				'<img src="{SRC}" />':"[imgl]{SRC}[/imgl]",
				'<img style="float:left;" src="{SRC}" />':"[img]{SRC}[/img]"
			}
		}
	}
}
