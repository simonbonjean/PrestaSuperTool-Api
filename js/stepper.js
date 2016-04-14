$(function(){
  
  jQuery.fn.stepper = function(options, callbacks){
    var items = $(this);
    var userCallbacks = $.extend({
        'increment': null,
        'decincrement':null,
        'init':null}, callbacks);
      
      
    var settings = $.extend({
      'increment'     : 1,
      'displayMode'   : 'between',
      'containerClass': 'stepper',
      'controlsClass' : 'controls',
      'desableClass'  : 'desabled',
      'buttonAddClass': 'add',
      'buttonSubClass': 'sub',
      'buttonClass'   : null,
      'keyRestriction': true,
      'maxValue'      : null,
      'minValue'      : null
    }, options);
    
    var methods = {
      init: function(){
        items.each(function(){
          var item = $(this);
          item.wrap($('<div class="'+settings.containerClass+'">'))
          var container = item.parent();
          
          if(settings.buttonClass != null)
            buttonClass = ' ' +settings.buttonClass;
          else
            buttonClass = '';
          
          var buttonAdd = $('<a href="#" class="'+settings.buttonAddClass+buttonClass+'">+</a>');
          var buttonSub = $('<a href="#" class="'+settings.buttonSubClass+buttonClass+'">-</a>');
          
          switch(settings.displayMode)
          {
            case 'between':
              container.append(buttonAdd);
              container.prepend(buttonSub);
              break;
            case 'left':
              subContainer = $('<div class="'+settings.controlsClass+'">')
              subContainer.append(buttonAdd);
              subContainer.append(buttonSub);
              container.prepend(subContainer);
              break;
            case 'right':
              subContainer = $('<div class="'+settings.controlsClass+'">')
              subContainer.append(buttonAdd);
              subContainer.append(buttonSub);
              container.append(subContainer);
              break;
          }
          
          if(settings.keyRestriction)
          {
            methods.keyRestrict(item);
          }
          if(typeof userCallbacks.init == 'function')
          {
            userCallbacks.init(item, settings);
          }
          item.data('maxValue', settings.maxValue);
          item.data('minValue', settings.minValue);
          methods.desabler(item);
        })
        var buttons = $('.'+settings.containerClass+' .'+settings.buttonAddClass+', .'+settings.containerClass+' .'+settings.buttonSubClass);
        methods.bind(buttons);
      },
      bind: function(buttons){
        buttons.unbind('click').click(function(event){
          event.preventDefault(); 
          event.stopPropagation();

          var item = $(this);
          var input = item.parent().find('input');

          if(item.hasClass(settings.buttonAddClass))
          {
            methods.incrementer(input)
          }
          else if(item.hasClass(settings.buttonSubClass)){
            methods.decrementer(input)
          }
          methods.desabler(input);
        })
      },
      keyRestrict: function(input){
          input.unbind('keydown').keydown(function(event) {
            // Allow: backspace, delete, tab, escape, and enter
            if (event.keyCode == 46 || event.keyCode == 8 || event.keyCode == 9 || event.keyCode == 27 || event.keyCode == 13 || 
               (event.keyCode == 65 && event.ctrlKey === true) || 
               (event.keyCode >= 35 && event.keyCode <= 39)) 
            {
                    return;
            }
            else 
            {
              if (event.shiftKey || (event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 96 || event.keyCode > 105 )) 
              {
                  event.preventDefault(); 
              }   
            }
          });
          input.unbind('change').change(function(event) {
              var input = $(this);
              var value = input.val();
              
              if(!(input.data('maxValue') === null || value <= input.data('maxValue')))
              {
                input.val(input.data('maxValue'));
              }
              if(!(input.data('minValue') === null || value >= input.data('minValue')))
              {
                input.val(input.data('minValue'));
              }
              methods.desabler(input);
          })
      },
      desabler: function(input){
        var container = input.parent();
        container.find('.'+settings.desableClass).removeClass(settings.desableClass)
        var value = input.val();
        if(value == input.data('maxValue'))
        {
          container.find('.'+settings.buttonAddClass).addClass(settings.desableClass)
        }
        if(value == input.data('minValue'))
        {
          container.find('.'+settings.buttonSubClass).addClass(settings.desableClass)
        }
      },
      incrementer: function(input){
        var value = parseInt(input.val());
        if(input.data('maxValue') === null || value+settings.increment <= input.data('maxValue'))
        {
          input.val(value+settings.increment);
        }
        if(typeof userCallbacks.increment == 'function')
        {
          userCallbacks.increment();
        }
      },
      
      decrementer:function(input){
        var value = parseInt(input.val());
        if(input.data('minValue') === null || value-settings.increment >= input.data('minValue'))
        {
          input.val(value-settings.increment);
        }
        if(typeof userCallbacks.decrement == 'function')
        {
          userCallbacks.decrement();
        }
      }
    }
    
    methods.init();
  };

  
  $('input.stepper').livequery(function() {
    $(this).stepper({'minValue': 1}, {init: function(item, settings){
      if(item.attr('quantity') != null)
      {
        settings.maxValue = item.attr('quantity'); 
        if(item.attr('quantity') == 0){
          settings.maxValue = 1;
          settings.minValue = 1;
        }
      }
    }})
  });
})