'use strict';

// Declare app level module which depends on filters, and services
angular.module('manager', ['manager.filters', 'manager.services', 'manager.directives', 'manager.controllers', 'restangular', 'jdUpload', 'ngCookies'])
	.config(['$routeProvider', 'RestangularProvider', function($routeProvider, Restangular) {

		Restangular.setBaseUrl(config.api_url.replace(/\/$/, ""));

		$routeProvider
			.when('/login', {templateUrl: 'partials/login.html', controller: 'login'})
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
			$location.path('/login');
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
})


