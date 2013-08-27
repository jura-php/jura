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
					$location.path($rootScope.redirectPath || '/' + $rootScope.defaultModule().uri);
				})
				.error(function(error){
					$scope.form.error = error.error_description;
				})
		}
	}])

	.controller('read', ['$rootScope', '$scope', '$routeParams', '$timeout', '$location', 'Restangular', '$cookieStore', function($rootScope, $scope, $routeParams, $timeout, $location, Restangular, $cookieStore) {

		if(!$rootScope.structure.user) return;

		var table = $routeParams.table;
		var module = _.where($rootScope.structure.modules, {uri: table})[0];
		var Rest = Restangular.all(table)

		if(module.uniqueID) {
			$location.path(table + '/edit/' + module.uniqueID)
		}

		$scope.order = JSON.parse($cookieStore.get(table + '_order') || '\{\}');

		$scope.$watch('order', function(order){
			$cookieStore.put(table + '_order', JSON.stringify(order));
		}, true)


		function reset() {
			$scope.actionFlag = 'l';
			$scope.module = module;
			$scope.all_checkboxes = false;
			$scope.checkboxes = {};
			$scope.search_text = $routeParams.search;

			Restangular.all(table).getList({
				page: $routeParams.page || 1,
				search: $routeParams.search || '',
				orderBy: $scope.order.reqBy || '',
				order: ($scope.order.reqReverse) ? 'DESC' : 'ASC'
			}).then(function(response){
				$scope.data = response.data;
				$scope.pagination = response.pagination;
				$scope.count = response.count;
			});
		}

		if(module) reset()

		$scope.edit = function(e){
			$timeout(function(){
				angular.element(e.target).find('a').trigger("click");
			}, 0, false)
		}

		$scope.doOrder = function(field, pagination){
			if(!$rootScope.hasFlag(field.flags, 'O')) return;
			if(pagination.count > 1) return $scope.doOrderByRequest(field, pagination)

			console.log($scope.order)

			if($scope.order.by == field.name) {
				$scope.order.reverse = !$scope.order.reverse;
			} else {
				$scope.order.by = field.name;
				$scope.order.reverse = false;
			}
		}

		$scope.doOrderByRequest = function(field, pagination){
			if($scope.order.reqBy == field.name) {
				$scope.order.reqReverse = !$scope.order.reqReverse;
			} else {
				$scope.order.reqBy = field.name;
				$scope.order.reqReverse = false;
			}

			reset();
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
			var item = Restangular.restangularizeElement(null, this.data, table);
			item.put();
		}

		$scope.urlSearch = function(){
			return $routeParams.search
		}

		$scope.resetSearch = function() {
			$scope.search_text = '';
			$scope.search();
		}

		$scope.search = function(){
			var search = {};

			if($scope.search_text && $scope.search_text.length > 0) {
				search = {search: $scope.search_text};
			}

			$location.path('/' + $scope.module.uri).search(search)
		}

	}])

	.controller('edit', ['$rootScope', '$scope', '$routeParams', '$location', 'Restangular', '$http', '$timeout', function($rootScope, $scope, $routeParams, $location, Restangular, $http, $timeout) {

		if(!$rootScope.structure.user) return;

		var table = $routeParams.table;
		var id = $routeParams.id;
		var module = _.where($rootScope.structure.modules, {uri: table})[0];

		if(module) {
			// module.uniqueID = 4;
			$scope.acao = 'Editar';
			$scope.actionFlag = 'ru';
			$scope.module = module;
			$scope.uploads = {};
			$scope.data = Restangular.one(table, id).get();
		}


		$scope.$watch('data', function(data, oldValue){
			if(!data || !oldValue) return;
			$scope.saved = false;
		}, true)

		$scope.$watch('uploads.uploading', function(data, oldValue){
			$scope.saved = false;
		}, true)

		$scope.save = function(model){
			if(!$scope.form.$valid || $scope.uploads.uploading) return;
			$scope.saving = true;

			model.put().then(function(){
				$scope.saving = false;

				if(module.uniqueID) {
					$scope.saved = true;
					$scope.data = Restangular.one(table, id).get();
				} else {
					$scope.saved = true;
					$timeout(function(){
						$location.path(table);
					}, 300)
				}
			})
		}


	}])


	.controller('new', ['$rootScope', '$scope', '$routeParams', '$location', 'Restangular', '$http', function($rootScope, $scope, $routeParams, $location, Restangular, $http) {

		if(!$rootScope.structure.user) return;

		var table = $routeParams.table;
		var module = _.where($rootScope.structure.modules, {uri: table})[0];

		if(module) {
			$scope.acao = 'Criar';
			$scope.actionFlag = 'c';
			$scope.module = module;
			$scope.uploads = {};
			$scope.data = Restangular.one(table, 'new').get();
		}

		$scope.save = function(model){
			if(!$scope.form.$valid || $scope.uploads.uploading) return;
			$scope.saving = true;

			model.post().then(function(){
				$scope.saving = false;
				$location.path(table);
			})

		}

	}])