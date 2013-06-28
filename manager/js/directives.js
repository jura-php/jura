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
    }]);
