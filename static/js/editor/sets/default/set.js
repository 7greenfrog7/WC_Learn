// ----------------------------------------------------------------------------
// markItUp!
// ----------------------------------------------------------------------------
// Copyright (C) 2008 Jay Salvat
// http://markitup.jaysalvat.com/
// ----------------------------------------------------------------------------
myMarkdownSettings = {
    nameSpace:          'markdown', // Useful to prevent multi-instances CSS conflict
    previewParser:  function(content){
        var html = Markdown(content);
        return html;
    },
    previewInElement : '.markItUpPreviewFrames',
    onShiftEnter:       {keepDefault:false, openWith:'\n\n'},
    markupSet: [
        {name:_t('粗体'), openWith:'**', closeWith:'**', offset:2, className: 'fa fa-bold'},
        {name:_t('斜体'), openWith : '*', closeWith : '*', offset:1, className : 'fa fa-italic'},
        {separator:' ' },        
        {name:_t('引用'), openWith:'> ', offset:2, className : 'fa fa-quote-left'},
        {name:_t('代码'), openWith:'{{{\n', closeWith:'\n}}}', addline:1, className : 'fa fa-code'},
        {separator:' ' },
        {name:_t('普通列表'), openWith:'\n- ', offset:2, className : 'fa fa-list'},
        {name:_t('数字列表'), openWith:function(markItUp) {
            return markItUp.line+'. ';
        }, offset:3, className : 'fa fa-list-ol'},
        {name : _t('标题'), openWith : '\n### ', offset:4, addline:1, className : 'fa fa-h-square'},
        {separator:' ' },
        {name:_t('超链接'), openWith:function() { $.dialog('linkbox') }, className : 'fa fa-link'},
        {name:_t('图片'), openWith:function() { $.dialog('imageBox') }, className : 'fa fa-picture-o' },
        {name:_t('视频'), openWith:function() { $.dialog('videoBox') }, className : 'fa fa-toggle-right' },
        {separator:' '},
        {name : _t('清空'), openWith:function(){
            $('.advanced_editor').val('');
            $('.markItUpPreviewFrames').html('');
        }, className : 'fa fa-eraser'},
        {name:'ToggleMode', call:'toggleMode', className:"toggleMode fa fa-eye"},
        {name : _t('预览模式'), openWith:function(){
            $('.markItUpButton13').toggleClass('cur');
           
            if ($('.markItUpPreviewFrame').css('display') == 'none')
            {
                $('.markItUpPreviewFrame').fadeIn();
                $.cookie('data_editor_preview', true);
            }
            else
            {
                $('.markItUpPreviewFrame').fadeOut();   
                $.cookie('data_editor_preview', false);
            }
            
        }, className : 'fa fa-bars pull-right'},
        {name : _t('编辑器语法帮助'), openWith:function()
        {
            if ($('.markItUpHelper').css('display') == 'none')
            {
                $('.markItUpHelper').fadeIn();
            }
            else
            {
                $('.markItUpHelper').fadeOut();   
            }
        }, className : 'fa fa-question pull-right'}
        
    ]
}

// mIu nameSpace to avoid conflict.
miu = {
    markdownTitle: function(markItUp, char) {
        heading = '';
        n = $.trim(markItUp.selection||markItUp.placeHolder).length;
        for(i = 0; i < n; i++) {
            heading += char;
        }
        return '\n'+heading+'\n';
    }
}