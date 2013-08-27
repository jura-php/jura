'use strict';

/* Filters */

angular.module('manager.filters', []).
	filter('fieldsByFlags', ['$rootScope', function($rootScope) {
		return function(data) {
			var actionFlag = this.actionFlag;

			return _.filter(data, function(item){
				return $rootScope.hasFlag(item.flags, actionFlag);
			});
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
	}).

	filter('pagination', function() {
		return function(input, current, total) {
			var total = parseInt(total);
			var show = 10;
			var min, max;
			if (current <= show / 2){
				min = 1;
				max = Math.min(total, min + show);
			}else if (current >= total - (show / 2)){
				max = total;
				min = Math.max(1, max - show);
			}else{
				min = Math.max(1, current - (show / 2));
				max = Math.min(total, min + show);
			}

			for (var i=min; i<=max; i++){
				input.push(i);
			}

			return input;
		};
	}).

	filter('hora', function(){
		return function(h) {
			if(!h) return '';
			return h.slice(0, 5);
		}
	});