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
	}).

	filter('pagination', function() {
		return function(input, current, total) {
			var total = parseInt(total);
			var show = 10;


			if(total <= show) {
				for (var i=current; i<=total; i++){
					input.push(i);
				}

				return input;
			}

			if(total > show) {
				var remaining = 0;
				var prepend = 0;

				for(var i = 0; i < show; i++) {
					var num = current+i-show/2;
					if(num < 1) {
						remaining++;
					} else if (num > total) {
						prepend++;
					} else {
						input.push(num);
					}
				}

				for (var i = 0; i < remaining; i++) {
					input.push(input[input.length - 1] + 1);
				}

				for (var i = 0; i < prepend; i++) {
					input.unshift(input[0] - 1);
				}

				return input;
			}

		};
	}).

	filter('hora', function(){
		return function(h) {
			if(!h) return '';
			return h.slice(0, 5);
		}
	});