'use strict';

/* Filters */

angular.module('manager.filters', []).
    filter('fieldsByFlags', ['$rootScope', function($rootScope) {
        return function(data) {
            var actionFlag = this.actionFlag;

            return _.filter(data, function(item){
                return $rootScope.hasFlag(item.flags, actionFlag);
            })
        }
    }]).

    filter('label', function(){
        return function(data) {
            if(!this.field) return;
            return data.replace('#LABEL#', this.field.label.toLowerCase());
        }
    }).

    filter('range', function() {
        return function(input, start, total) {
            total = parseInt(total);
            for (var i=start; i<=total; i++)
                input.push(i);
            return input;
        };
    });
