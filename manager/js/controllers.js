'use strict';

/* Controllers */

angular.module('manager.controllers', [])

	.controller('login', ['$rootScope', '$scope', '$routeParams', '$http', '$location', 'Restangular', function($rootScope, $scope, $routeParams, $http, $location, Restangular) {
		$scope.form = {};
		$scope.send = function(){
			$scope.form.error = '';

			$http.post(config.api_url + 'token/', $scope.form)
				.success(function(user){
					$rootScope.structure.user = user;
					$location.path('/' + $rootScope.defaultModule().uri);
				})
				.error(function(error){
					$scope.form.error = error.error_description;
				})
		}
	}])

	.controller('read', ['$rootScope', '$scope', '$routeParams', '$timeout', '$location', 'Restangular', function($rootScope, $scope, $routeParams, $timeout, $location, Restangular) {

		if(!$rootScope.structure.user) return;

		var table = $routeParams.table;
		var module = _.where($rootScope.structure.modules, {uri: table})[0];
		var Rest = Restangular.all(table)

		function reset() {
			$scope.actionFlag = 'l';
			$scope.module = module;
			$scope.all_checkboxes = false;
			$scope.checkboxes = {};

			Rest.getList().then(function(data){
				$scope.data = data;
			});
		}

		if(module) reset()

		$scope.edit = function(e){
			$timeout(function(){
				angular.element(e.target).find('a').trigger("click");
			}, 0, false)
		}

		$scope.hasAtLeastOneCheckboxChecked = function() {
			return _.some($scope.checkboxes);
		}

		$scope.toggleAllCheckboxes = function() {
			_.each($scope.data, function(data){
				$scope.checkboxes[data.id] = $scope.all_checkboxes;
			})
		}

		$scope.removeSelected = function(){
			var to_delete = _.map($scope.checkboxes, function(val, key){
				return (val) ? key : false;
			}).join('-');

			Rest.doDELETE(to_delete).then(reset);
		}

		$scope.save = function(){
			this.data.put();
		}

	}])

	.controller('edit', ['$rootScope', '$scope', '$routeParams', '$location', 'Restangular', function($rootScope, $scope, $routeParams, $location, Restangular) {

		if(!$rootScope.structure.user) return;

		var table = $routeParams.table;
		var id = $routeParams.id;
		var module = _.where($rootScope.structure.modules, {uri: table})[0];

		if(module) {
			$scope.acao = 'Editar';
			$scope.actionFlag = 'u';
			$scope.module = module;
			$scope.data = Restangular.one(table, id).get();
		}

		$scope.save = function(model){
			if(!$scope.form.$valid) return;

			model.put().then(function(){
				$location.path(table);
			})
		}

	}])


	.controller('new', ['$rootScope', '$scope', '$routeParams', '$location', 'Restangular', function($rootScope, $scope, $routeParams, $location, Restangular) {

		if(!$rootScope.structure.user) return;

		var table = $routeParams.table;
		var module = _.where($rootScope.structure.modules, {uri: table})[0];

		if(module) {
			$scope.acao = 'Criar';
			$scope.actionFlag = 'c';
			$scope.module = module;
			Restangular.one(table, 'new').get().then(function(data){
				$scope.data = data;
			});
		}

		$scope.save = function(model){
			console.log($scope.form.$valid)
			if(!$scope.form.$valid) return;

			model.post().then(function(){
				$location.path(table);
			})

		}

	}])