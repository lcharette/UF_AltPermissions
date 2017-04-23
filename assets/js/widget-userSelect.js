/*!
 * userSelect Plugin
 *
 * JQuery plugin for the UserFrosting AltPermission Sprinkle
 *
 * @package UF-AltPermissions
 * @author Louis Charette
 * @link https://github.com/lcharette/UF-AltPermissions
 * @license MIT
 */

(function( $ ){

    'use strict';

    var options = {};

    var methods = {

        /*
         * MAIN/default Method
         */
        main : function(optionsArg) {
            options = $.extend( options, $.fn.userSelect.defaultOptions, optionsArg );
            this.each(function() {
                _initSelect($(this));
            });
            return;
        }
    };

    /**
     * _initSelect function.
     * Setup the select with everything
     *
     * @access public
     * @param mixed element
     * @return void
     */
    function _initSelect(element) {

        $(element).select2({
            width: '100%',
            ajax: {
                url: options.url,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        "filters[info]" : params.term,
                        page: params.page || 0,
                        size: options.perPage
                    };
                },
                processResults: function(data, params) {
                    // parse the results into the format expected by Select2
                    // since we are using custom formatting functions we do not need to
                    // alter the remote JSON data, except to indicate that infinite
                    // scrolling can be used
                    params.page = params.page || 0;

                    return {
                        results: data.rows,
                        pagination: {
                            //`more` require True or False if we have more to load. We check the next page has something
                            more: ((params.page + 1) * 10) < data.count_filtered
                        }
                    };
                },
                cache: true
            },
            escapeMarkup: function(markup) {
                return markup;
            },
            // let our custom formatter work
            minimumInputLength: 0,
            templateResult: _formatSelected,
            templateSelection: _formatOption
        });
    }

    function _formatSelected(item) {
        if (item.loading) return item.text;

        var handlebarTemplate = $(options.dropdownTemplate).html();
        var dropdownTemplateCompiled = Handlebars.compile(handlebarTemplate);
        return dropdownTemplateCompiled(item);
    }

    function _formatOption(item) {
        if (item.text != "") return item.text;
        return item.first_name + " " + item.last_name || item.text;
    }

    /*
     * Main plugin function
     */
    $.fn.userSelect = function(methodOrOptions) {
        if ( methods[methodOrOptions] ) {
            return methods[ methodOrOptions ].apply( this, Array.prototype.slice.call( arguments, 1 ));
        } else if ( typeof methodOrOptions === 'object' || ! methodOrOptions ) {
            // Default to "init"
            return methods.main.apply( this, arguments );
        } else {
            $.error( 'Method ' +  methodOrOptions + ' does not exist on jQuery.userSelect' );
        }
    };

    /*
     * Default plugin options
     */
    $.fn.userSelect.defaultOptions = {
        url: "",
        perPage: 10,
        dropdownTemplate: "#user-select-option"
    };

})( jQuery );
