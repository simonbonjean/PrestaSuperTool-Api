$(function(){
    jQuery.fn.folder = function(options, callbacks){
        var items = $(this);
        var userCallbacks = $.extend({
            'fold': null,
            'unfold':null,
            'action':null},
            callbacks);
        var settings = $.extend({
            'contentSelector'           : '.block_content',
            'titleSelector'             : '.title_block',
            'parentContainerSelector'   : '.block',
            'folderClass'               : 'folder',
            'openClass'                 : 'folder-open',
            'closeClass'                : 'folder-close',
            'excludeClass'              : 'no-fold',
            'puceClass'                 : 'folder-puce'
        }, options);
        var methods = {
            init: function(){
                if(typeof document.alreadyFolded == 'undefined')
                {
                    document.alreadyFolded = {};
                }
                items.each(function(){
                    var item = $(this);
                    if(!item.hasClass(settings.excludeClass) && !item.hasClass(settings.folderClass) && item.parents('.disabled-all-folder').length == 0){
                        var title = item.find(settings.titleSelector);
                        var content = methods.findContent(item);
                        var id = item.attr('id');

                        if(id)
                        {
                            if(document.alreadyFolded[id] === true)
                            {
                                item.removeClass(settings.closeClass)
                                item.addClass(settings.openClass)
                            }
                            else if(document.alreadyFolded[id] === false){
                                item.addClass(settings.closeClass)
                                item.removeClass(settings.openClass)
                            }
                        }


                        if(item.hasClass(settings.closeClass))
                        {
                            content.hide();
                        }
                        else{
                            methods.findContainer(title).addClass(settings.openClass);
                        }
                        methods.addPuce(title);
                        title.click(function(){
                            methods.action($(this));
                        });

                        item.addClass(settings.folderClass)
                    }
                })
            },
            addPuce: function(title){
                title.append('<span class="'+settings.puceClass+'"></span>')
            },
            unfold: function(container){
                if(typeof userCallbacks.unfold == 'function')
                    userCallbacks.unfold(container,methods,settings);

                var id = container.attr('id')
                if(id)
                {
                    document.alreadyFolded[id] = true;
                }

                container.removeClass(settings.closeClass);
                container.addClass(settings.openClass);
            },
            fold: function(container){
                if(typeof userCallbacks.fold == 'function')
                    userCallbacks.fold(container,methods,settings);


                var id = container.attr('id')
                if(id)
                {
                    document.alreadyFolded[id] = false;
                }


                container.removeClass(settings.openClass);
                container.addClass(settings.closeClass);
            },
            findContent: function(container){
                return container.find(settings.contentSelector);
            },
            findContainer: function(title){
                return title.parents(settings.parentContainerSelector);
            },
            action: function(title){
                if(typeof userCallbacks.action == 'function')
                    userCallbacks.action(title);

                var container = methods.findContainer(title);
                if(container.hasClass(settings.closeClass))
                {
                    methods.unfold(container);
                }
                else
                {
                    methods.fold(container);
                }
            }
        }
        methods.init();
    }


    $('.block').folder({},{
        'fold': function(container,methods,settings){methods.findContent(container).hide(600)},
        'unfold': function(container,methods,settings){methods.findContent(container).show(300)}
    });
})