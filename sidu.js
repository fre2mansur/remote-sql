$(function(){
  var isDrag = '', pos = 0;
  $('#menuDrag').on('mousedown', function(){ isDrag = 'menu'; });
  $('#sqlsDrag').on('mousedown', function(){ isDrag = 'sqls'; });
  $(document).on('mousedown', '#main .grid .th td b', function(e){
      isDrag = $(this).parent().index() + 1;
      pos = $(this).parent().offset().left;
  });
  $(document).on('mousemove', function(e){
    if (isDrag === 'sqls'){
      $('#' + isDrag).css('height', Math.min(e.clientY, $(window).height()) - $('#sqls').offset().top);
    }else if (isDrag === 'menu'){
      $('#' + isDrag).css({'width': e.clientX, 'padding': e.clientX < 10 ? 0 : 3});
    }else if (isDrag){
        var w = e.clientX - pos;
        var wOld = $('.grid .th td:nth-child('+ isDrag +')').width();
        $('#colGrid input:nth-child('+ isDrag +')').val(w);
        $('.grid td:nth-child('+ isDrag +')').css('width', w); // fix later to change own parent's all children
        var tab = $(this).parents('.grid');
        tab.css('width', tab.width() + w - wOld);
    }
  }).on('mouseup', function(){ isDrag = ''; });
  $('.resize').on('click', function(){
    var e = $('#' + $(this).data('id'));
    if (e.attr('id') === 'menu'){
      if ($(window).width() < 602) return e.toggle();
      e.css('display', 'block');
      var x = e.width(),  X = $(window).width(),  css = 'width';
      !x && e.css('padding', 3);
    }else{
      var x = e.height(), X = $(window).height(), css = 'height';
    }
    x > X - 100 ? e.css(css, 0) && e.css('padding', 0) : e.css(css, x + 100);
  });

  $(document).on('click', '.dbImpExp', function(){
    var tab = get_objs();
    if (!tab) tab = $('input[name="objs[]"]').eq(1).val();
    xwin($(this).data('url') + '&tab=' + tab);
  });
  $(document).on('click', '.delHis', function(){
    $.post('his.php?id=' + $('#sidu0').val(), {cmd: $(this).data('cmd'), ajax: 1, lid: get_objs()}, function(d){
      $('#main').html(d);
    });
  });
  $(document).on('click', '#chart input[type=button]', function(){
    $('#chartTyp').val($(this).val());
    var form = $('#chart').serialize();
    $.post($('#chart').attr('action'), form, function(d){
      $('#main').html(d);
    })
  });
  $(document).on('click', '#objTool b,i.objTool', function(){
    if ($(this).hasClass('confirm') && !confirm($(this).data('confirm'))) return;
    var url = $('#objTool').data('url');
    if (!url) return;
    url += '&objcmd=' + $(this).data('cmd') + '&objs=' + get_objs();
    $.get(url, {ajax:1}, function(d){ $('#main').html(d); });
  });
  $(document).on('click', '.tab-cmd', function(){
    tab_cmd($(this).data('cmd'), '');
  });
  $(document).on('click', '.data-cmd', function(){
    var cmd = $(this).data('cmd'); // save delete
    $('.grid tr:not(.th) td:first-child input:checked').each(function(){
      var me=$(this);
      var data = {};
      var tr = $(this).parents('tr');
      tr.find('input,select,textarea').each(function(){
        if (name = $(this).attr('name')) data[name] = $(this).val();
      });
      if (cmd == 'save') cmd = tr.hasClass('newR') ? 'insert' : 'update';
      $.post($('#dataTab').attr('action'), {cmd:cmd, data:data}, function(d){
        if (!d){
            if (cmd == 'insert') tr.removeClass('newR');
            else if (cmd == 'delete') tr.remove();
            me.prop('checked', 0);
        } else $('#main').append(d);
      });
    });
  });
  $(document).on('click', '#addRow', function(){
    $('#newR').after('<tr class="newR">'+ $('#newR').html() +'</tr>');
  });

  $(document).on('click', 'a.ajax,#menu a,#main a', function(e){
    var url=$(this).attr('href');
    if (e.shiftKey || e.altKey || e.ctrlKey || url == 'conn.php'){
      window.open(url);
    }else{
      $.get(url, 'ajax=1', function(d){ $('#main').html(d); });
    }
    return false;
  });
  $(document).on('click', 'a.goto', function(){
    top.location = this.href;
    return false;
  });
  $('#menu').on('click', 'a', function(){
    $('#menu a').removeClass('on');
    'info'===$(this).attr('title') ? $(this).next('a').addClass('on') : $(this).addClass('on');
  });
  $('.load').each(function(){
      var e=$(this);
      $.get($(this).data('url'), function(d){e.html(d)})
  });
  $('#menu').on('click', '#fref', function(){
      $.get($('#menu').data('url'), function(d){$('#menu').html(d)})
  });
  $('#menu').on('click', '.i-tr, .i-trOpen', function(){
    $(this).toggleClass('i-tr i-trOpen');
    var tree = $('#t' + $(this).data('id'));
    tree.toggleClass('hide');
    if (tree.data('load')){
      $.get('menu.php', tree.data('load'), function(d){
        tree.before('<b>(' + d[0] + ')</b>').html(d[1]);
      }, 'json');
      tree.data('load', 0);
    }
  });

  $(document).on('click', '.show', function(){
    var id = $(this).data('src');
    if (id == 'next') $(this).next().toggleClass('hide');
    else{
      if (!id){
        id = this.id;
        'ID' == id.substr(0, 2) ? id = id.substr(2) : id += 'SH';
        id = '#' + id;
      }
      $(id).toggleClass('hide');
    }
  });
  $(document).on('click', '.hideP', function(){
    $(this).parent().toggleClass('hide');
  });
  $(document).on('click', '.Hpop', function(){
    var isHide = $(this).next().is(':hidden');
    $('.Hpop').next().hide();
    if (isHide) $(this).next().show();
  });
  $(document).on('click', '#menu,#sqls,.tool,input:not(.Hpop),.grid td div', function(e){
    $('.Hpop').next().hide();
  });
  $(document).on('click', '.xwin', function(){
    xwin($(this).data('url'));
  });
  $(document).on('click', '#checkAll', function(){
    $(this).parents('form').find('input:checkbox').prop('checked', $(this).is(':checked'));
  });

  $(document).on('click', '.grid tr:not(.th):not(.off):not(.newR)', function(e){
    if (!e.shiftKey && !e.altKey && !e.ctrlKey){
        $(this).parents('table').find('tr').removeClass('on');
        $(this).addClass('on');
    } else $(this).toggleClass('on');
  });
  $(document).on('click', '.grid tr:not(.th) td div', function(){
    var name = $(this).data('name');
    if (name.length){
      $(this).hide().after('<input type="text" name="' + name + '" value="' + $(this).text().replace(/&/g, '&#38;').replace(/"/g, '&#34;') + '">')
        .siblings('a.fk').hide();
    }
  });
  $(document).on('change', '.grid tr td:not(:first-child) input,.grid select,.grid textarea', function(){
    $(this).parents('tr').find('td:first-child input').prop('checked', true);
    if (this.nodeName == 'TEXTAREA'){
      var prev = $(this).prev();
      if (prev.hasClass('Hpop')) prev.val($(this).val().substr(0, 200));
    }
  });
  $(document).on('click', '.grid.data tr.th td:not(:first-child) div,.xsort', function(){
    tab_cmd('p1', $(this).hasClass('xsort') ? 'del:' + $(this).parents('table').find('tr.th td:nth-child('+ ($(this).parent().index() + 1) +')').text() : $(this).text());
  });
  $(document).on('click', '.hideCol', function(){
    if ($(this).hasClass('allCol')){
      $('.grid td:not(:first-child)').toggleClass('hide');
      $('#colHide b').toggleClass('hide');
      $('#colGrid input').each(function(){
        $(this).val(0 - $(this).val());
      });
      return;
    }
    var idx = $(this).parent().index() + 1;
    $('.grid td:nth-child('+ idx +')').toggleClass('hide');
    $('#colHide b:nth-child('+ idx +')').toggleClass('hide');
    var col = $('#colGrid input:nth-child('+ idx +')');
    col.val(0 - col.val());
  });
  $(document).on('click', '#colHide b', function(){
    var idx = $(this).index() + 1;
    $('.grid td:nth-child('+ idx +')').toggleClass('hide');
    $(this).toggleClass('hide');
    var col = $('#colGrid input:nth-child('+ idx +')');
    col.val(Math.abs(col.val()));
  });
  /*$(document).on('click', '.gridInc', function(){
    resizeCol($(this), 1.25);
  });*/
  /*$(document).on('click', '.gridDec', function(){
    resizeCol($(this), .8)
  });*/

  $('#runA,#runR,#runM').click(function(){
    var mode = $(this).attr('id');
    var sql = (mode == 'runA') ? editor.getValue() : editor.getSelection();
    if (!sql && mode != 'runA'){
      var cur = editor.getCursor().line;
      var arr = editor.getValue().split("\n");
      var start = 0, stop = 0;
      for (i = 0; i < arr.length; i++){
        c = arr[i].trim();
        len = c.length;
        if (!start){
            if (!len) sql = '';
            if (i == cur) start = 1;
        }
        if (!stop){
            if (start && !c.length) stop = 1;
            else if (len) sql += c + "\n";
        }
      }
    }
    if (sql){
      $('#sqlwait').show();
      $.post('sql.php', {id:$('#sidu0').val(), sql:sql, mode:mode, ajax:1}, function(d){
        if (d) $('#main').html(d);
        $('#sqlwait').hide();
      });
      editor.focus();
    }
  });
  $('#sqlLoad').on('click', function(){
    $('#sqlLoadFile').trigger('click');
  });
  $('#sqlLoadFile').on('change', function(){
    var reader=new FileReader();
    reader.onload=function(e){editor.setValue(e.target.result);}
    reader.readAsText(this.files[0]);
  })
  $('#sqlSave').on('click', function(){
    var textToWrite = editor.getValue();
    var textFileAsBlob = new Blob([textToWrite], {type: 'text/plain'});
    var downloadLink = document.createElement('a');
    downloadLink.download = 'sql.sql';
    downloadLink.innerHTML = 'Download File';
    if (window.webkitURL != null){
      // Chrome allows the link to be clicked
      // without actually adding it to the DOM.
      downloadLink.href = window.webkitURL.createObjectURL(textFileAsBlob);
    }else{
      // Firefox requires the link to be added to the DOM
      // before it can be clicked.
      downloadLink.href = window.URL.createObjectURL(textFileAsBlob);
      downloadLink.onclick = destroyClickedElement;
      downloadLink.style.display = 'none';
      document.body.appendChild(downloadLink);
    }
    downloadLink.click();
  });
  $(document).on('click', '#txtRep u', function(){
    var id = $("#txtArea");
    var pos = id[0].selectionStart;
    var txt = id.val();
    var cut = txt.substring(0, pos) + $(this).data('txt');
    id.val(cut + txt.substring(pos));
    setTimeout(function(){id.focus()}, 0);
    pos = cut.length;
    id[0].setSelectionRange(pos, pos);
  });
  $(document).on('click', '.expExt', function(){
    $('input.expExt').each(function(){
      var v = $(this).val();
      var y = $(this).is(':checked');
      if (v == 'csv'){
        y ? $('#csv').show() : $('#csv').hide();
      }else if (v == 'sql'){
        y ? $('#sql').show() : $('#sql').hide();
      }
    });
  });
  $(document).on('change', '#userHost', function(){
    top.location = 'user.php?id=' + $('#sidu0').val() + '&userHost=' + $(this).val() + '&tab=' + $('#curTab').val();
  });

  $('#enc_pwd').click(function(){if ($('#enc').is(':checked')){
    $.get('conn.php?cmd=salt').success(function(salt){
      $('#pwd').val(cms_enc($('#pwd').val(), salt));
      $('#conn').submit();
    });
    return false;
  }});

});

function cms_salt(a,b){
  a=md5(a); b=md5(b);
  if (a.charAt(0)==b.charAt(0)) return sha256(a+b);
  if (a.charAt(0)>b.charAt(0)) return sha256(b+a);
  return sha256(a+b+a);
}
function xwin(url){
  var w=700, h=600, l=(screen.width-w)/2, t=(screen.height-h)/2;
  var xwin=window.open(url, 'sidu', 'scrollbars=yes,resizable=yes,left='+l+',top='+t+',width='+w+',height='+h);
  xwin.focus();
}
function get_objs(){
  return $('input[name="objs[]"]:checked').map(function(){
    return $(this).val();
  }).get().join();
}
function tab_cmd(cmd = '', sort = ''){
  var data = {
    cmd:   cmd,
    sort:  sort,
    pgFm:  $('#pgFm').val(),
    pgSize:$('#pgSize').val(),
    where: $('input[name^="where["]').serialize(),
    grid:  $('input[name^="grid["]').serialize(),
    ajax:  1,
  };
  $.post($('#dataTab').attr('action'), data, function(d){
    $('#main').html(d);
  });
}
/*function resizeCol(e, deg){
  var idx = e.parent().index() + 1;
  var col = $('#colGrid input:nth-child('+ idx +')');
  var w = col.val() * deg;
  col.val(w);
  $('.grid td:nth-child('+ idx +')').css('width', w);
}*/
