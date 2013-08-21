'use strict';

/* Directives */


angular.module('manager.directives', []).

    directive('needFlag', ['$rootScope', function($rootScope) {
        return {
            restrict: 'A',
            link: function(scope, elm, attrs) {
                if(!$rootScope.hasFlag(scope.module.flags, attrs.needFlag)) {
                    elm.remove();
                }
            }

        }
    }]).

    directive('itemsField', function(){
        return {
            restrict: 'A',
            controller: ["Restangular", '$scope', function(Restangular, $scope) {
                $scope.options = Restangular.all($scope.field.resource_url).getList();
            }]
        }
    }).

    directive('chosen', ['$timeout', function($timeout) {
        var CHOSEN_OPTION_WHITELIST, NG_OPTIONS_REGEXP, chosen, isEmpty, snakeCase;

        NG_OPTIONS_REGEXP = /^\s*(.*?)(?:\s+as\s+(.*?))?(?:\s+group\s+by\s+(.*))?\s+for\s+(?:([\$\w][\$\w\d]*)|(?:\(\s*([\$\w][\$\w\d]*)\s*,\s*([\$\w][\$\w\d]*)\s*\)))\s+in\s+(.*)$/;
        CHOSEN_OPTION_WHITELIST = ['noResultsText', 'allowSingleDeselect', 'disableSearchThreshold', 'disableSearch'];
        snakeCase = function(input) {
            return input.replace(/[A-Z]/g, function($1) {
                return "_" + ($1.toLowerCase());
            });
        };
        isEmpty = function(value) {
            var key, _i, _len;

            if (angular.isArray(value)) {
                return value.length === 0;
            } else if (angular.isObject(value)) {
                for (_i = 0, _len = value.length; _i < _len; _i++) {
                    key = value[_i];
                    if (value.hasOwnProperty(key)) {
                        return false;
                    }
                }
            }
            return true;
        };
        return chosen = {
            restrict: 'A',
            link: function(scope, element, attr) {
                var disableWithMessage, match, options, startLoading, stopLoading, valuesExpr;

                options = scope.$eval(attr.chosen) || {};
                angular.forEach(attr, function(value, key) {
                    if (_.indexOf(CHOSEN_OPTION_WHITELIST, key) >= 0) {
                        return options[snakeCase(key)] = scope.$eval(value);
                    }
                });
                startLoading = function() {
                    return element.addClass('loading').attr('disabled', true).trigger('liszt:updated');
                };
                stopLoading = function() {
                    return element.removeClass('loading').attr('disabled', false).trigger('liszt:updated');
                };
                disableWithMessage = function(message) {
                    return element.empty().append("<option selected>" + message + "</option>").attr('disabled', true).trigger('liszt:updated');
                };
                $timeout(function() {
                    return element.chosen(options);
                });
                if (attr.ngOptions) {
                    match = attr.ngOptions.match(NG_OPTIONS_REGEXP);
                    valuesExpr = match[7];
                    if (angular.isUndefined(scope.$eval(valuesExpr))) {
                        startLoading();
                    }
                    return scope.$watch(valuesExpr, function(newVal, oldVal) {
                        if (newVal !== oldVal) {
                            stopLoading();
                            if (isEmpty(newVal)) {
                                return disableWithMessage(options.no_results_text || 'No values available');
                            }
                        }
                    });
                }
            }
        };

    }]).

    directive('epic', function(){
        return {
            restrict: 'A',
            link: function(scope, elm, attrs) {
                var opts = {
                    container: elm.attr('id'),
                    textarea: elm.find('textarea')[0],
                    basePath: 'lib/epic-editor/',
                    clientSideStorage: false,
                    // localStorageName: 'epiceditor',
                    // useNativeFullscreen: true,
                    // parser: marked,
                    // file: {
                    //     name: 'epiceditor',
                    //     defaultContent: '',
                    //     autoSave: 100
                    // },
                    theme: {
                        base: 'themes/base/epiceditor.css',
                        preview: 'themes/preview/github.css',
                        editor: 'themes/editor/epic-light.css'
                    },
                    button: {
                        preview: true,
                        fullscreen: false,
                        bar: "auto"
                    },
                    focusOnLoad: false,
                    // shortcut: {
                    //     modifier: 18,
                    //     fullscreen: 70,
                    //     preview: 80
                    // },
                    string: {
                        togglePreview: 'Preview',
                        toggleEdit: 'Editar'
                        // toggleFullscreen: 'Enter Fullscreen'
                    },
                    autogrow: true
                }
                var editor = new EpicEditor(opts).load();

            }
        }
    });
