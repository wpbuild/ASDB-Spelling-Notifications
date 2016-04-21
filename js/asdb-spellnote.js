jQuery(document).ready(function($) {

	function hide_banner_style() {
		$('#banner_img,#banner_txt').hide();
	}
		hide_banner_style();
		var selected_form_style = $("input[name='form_style']:checked").val();

		if( selected_form_style === 'img') {
			$('#banner_img').show();
		}
		if( selected_form_style === 'txt') {
			$('#banner_txt').show();
		}

		$("input[name='form_style']:radio").change(function() {
			hide_banner_style();
			if($(this).val() != '-1') {
				$('#banner_'+$(this).val()).show();
			}
		});
});



/**
 * Webmaster Spelling Notifications, 2015 year
 *
 * @link https://gourl.io/php-spelling-notifications.html
 * @version 1.1.1
 * @license GPLv2
 */

var splloc = window.location,
    spl;
nN = navigator.appName, document.onkeypress = splnote_get_text;

function splnote_create_win() {
    var t = document.createElement("div"),
        e = dde.scrollTop || db.scrollTop,
        n = e + 220 + "px",
        o = Math.floor(dde.clientWidth / 2) - 250 + "px";
    return t.innerHTML = '<div id="splwin"><div id="splwindow" style="top:' + n + "; left:" + o + '";><iframe frameborder="0" name="spl" id="splframe" src="' + splnote_path + '/index.php?spell_note.php"></iframe></div></div></div>', t.firstChild
}

function splnote_position_win(t) {
    t.style.position = "absolute";
    var e = Math.max(dde.scrollHeight, db.scrollHeight, dde.clientHeight),
        n = Math.max(dde.scrollWidth, db.scrollWidth, dde.clientWidth);
    t.style.height = e + "px", t.style.width = n + "px"
}

function splnote_show_win() {
    dde = document.documentElement, db = document.body;
    var t = splnote_create_win();
    splnote_position_win(t), db.appendChild(t)
}

function splnote_get_text(t) {
    return t || (t = window.event), !t.ctrlKey || 10 != t.keyCode && 13 != t.keyCode || splnote_call(), !0
}

function splnote_get_sel_text() {
    if (window.getSelection) e = window.getSelection(), selected_text = e.toString(), full_text = e.anchorNode.textContent, selection_start = e.anchorOffset, selection_end = e.focusOffset;
    else if (document.getSelection) e = document.getSelection(), selected_text = e.toString(), full_text = e.anchorNode.textContent, selection_start = e.anchorOffset, selection_end = e.focusOffset;
    else {
        if (!document.selection) return;
        e = document.selection.createRange(), selected_text = e.text, full_text = e.parentElement().innerText;
        var t = e.duplicate();
        t.moveToElementText(e.parentElement()), t.setEndPoint("EndToEnd", e), selection_start = t.text.length - e.text.length, selection_end = selection_start + selected_text.length
    }
    var e = {
        selected_text: selected_text,
        full_text: full_text,
        selection_start: selection_start,
        selection_end: selection_end
    };
    return e
}

function splnote_get_sel_context(t) {
    return selection_start = t.selection_start, selection_end = t.selection_end, selection_start > selection_end && (tmp = selection_start, selection_start = selection_end, selection_end = tmp), context = t.full_text, context_first = context.substring(0, selection_start), context_second = context.substring(selection_start, selection_end), context_third = context.substring(selection_end, context.length), context = context_first + "<strong>" + context_second + "</strong>" + context_third, context_start = selection_start - 60, context_start < 0 && (context_start = 0), context_end = selection_end + 60, context_end > context.length && (context_end = context.length), context = context.substring(context_start, context_end), context_start = context.indexOf(" ") + 1, context_end = selection_start + 60 < context.length ? context.lastIndexOf(" ", selection_start + 60) : context.length, selection_start = context.indexOf("<strong>"), context_start > selection_start && (context_start = 0), context_start && (context = context.substring(context_start, context_end)), context
}

function splnote_call() {
    var t = splnote_get_sel_text();
    t.selected_text.length > 400 ? alert(splnote_txt1) : 0 == t.selected_text.length ? alert(splnote_txt2) : (spl = splnote_get_sel_context(t), splnote_show_win())
}