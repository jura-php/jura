'use strict';

// Declare app level module which depends on filters, and services
var Manager = angular.module('manager', ['manager.filters', 'manager.services', 'manager.directives', 'manager.controllers', 'restangular', 'jdUpload', 'ngCookies'])
	.config(['$routeProvider', 'RestangularProvider', function($routeProvider, Restangular) {

		Restangular.setBaseUrl(config.api_url.replace(/\/$/, ""));

		$routeProvider
			.when('/login', {templateUrl: 'partials/login.html', controller: 'login'})
			.when('/error', {templateUrl: 'partials/error.html', controller: 'error'})
			.when('/:table', {templateUrl: 'partials/list.html', controller: 'read'})
			.when('/:table/new', {templateUrl: 'partials/edit.html', controller: 'new'})
			.when('/:table/:page', {templateUrl: 'partials/list.html', controller: 'read'})
			.when('/:table/edit/:id', {templateUrl: 'partials/edit.html', controller: 'edit'})

	}])

	.run(['$rootScope', '$location', '$http', '$routeParams', 'Restangular', function($rootScope, $location, $http, $routeParams, Restangular){

		$rootScope.hasFlag = function(flags, need) {
			if(!flags || !need) return;

			var has = false;
			flags = flags.toString().toLowerCase();

			_.each(need.toString().toLowerCase().split(""), function(flag){
				if(_.indexOf(flags, flag) != -1) has = true;
			});

			return has;
		}

		$rootScope.logout = function() {
			$http.get(config.api_url + 'logout')
				.success(function(structure){
					$location.path('/login');
				})
		}

		Restangular.setErrorInterceptor(function(response){
			if(response.code == 403) {
				$location.path('/login');
			} else {
				// alert('Um alert é constrangedor. Porém, ocorreu um erro com sua requisição.')
			}

		})

		$rootScope.$watch('structure.user', function(user){
			if(!user) return;
			Restangular.setDefaultRequestParams({access_token: user.access_token});
		});

		$rootScope.defaultModule = function () {
			var module = _.where($rootScope.structure.modules, {'default': true})[0];
			if (!module) {
				module = $rootScope.structure.modules[0];
			}

			return module;
		}

		$rootScope.notification = function(title, content){
			if (!window.webkitNotifications) return;

			if (window.webkitNotifications.checkPermission() == 0) { // 0 is PERMISSION_ALLOWED
				var notification = window.webkitNotifications.createNotification('icon.png', title, content);
				notification.show();
			} else {
				window.webkitNotifications.requestPermission();
			}
		}


		$rootScope.partialFieldPath = function(field, action) {
			var default_field_types = [
				'date',
				'datetime',
				'id',
				'imageupload',
				'items',
				'markdown',
				'multipleItems',
				'number',
				'readonly',
				'text',
				'time',
				'toggle',
				'upload'
			];

			var result_type = (action == 'list' || this.module.actionFlag == 'c' || $rootScope.hasFlag(field.flags, 'u')) ?  field.type : 'readonly';

			if(_.indexOf(default_field_types, result_type) !== -1) {
				return 'partials/fields/' + action + '/' + result_type + '.html'
			} else {
				return '../manager/formFields/partials/' + action + '/' + result_type + '.html'
			}

		}


		$rootScope.notification('foo', 'bar')

	}]);



$(function(){
	$.get(config.api_url + 'structure')
		.success(function(structure){

			angular.module('structure', [])
				.run(['$rootScope', '$location', function($rootScope, $location){
					$rootScope.structure = structure;
					$rootScope.redirectPath = $location.path();

					if(!structure.user) {
						$location.path('/login');
					} else {
						$location.path($rootScope.redirectPath || '/' + $rootScope.defaultModule().uri);
					}
				}])


			angular.bootstrap(document, ['manager', 'structure']);

		})
		.error(function(){
			angular.module('structure', [])
				.run(['$location', function($location){
					$location.path('/error');

				}])

			angular.bootstrap(document, ['manager', 'structure']);
		})
})


