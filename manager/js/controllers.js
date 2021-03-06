'use strict';

/* Controllers */

angular.module('manager.controllers', [])

	.controller('login', ['$rootScope', '$scope', '$routeParams', '$http', '$location', 'Restangular', function($rootScope, $scope, $routeParams, $http, $location, Restangular) {
		$scope.form = {};

		$(function () {
			if ($.cookie("login-user"))
			{
				$scope.form.user = $.cookie("login-user");
			}

			if ($.cookie("login-pass"))
			{
				$scope.form.pass = $.cookie("login-pass");
			}
		});

		$scope.send = function(){
			$scope.form.error = '';

			$http.post(config.api_url + 'token/', $scope.form)
				.success(function(user){
					$http.get(config.api_url + 'structure')
						.success(function(structure){
							$rootScope.structure = structure;
							$rootScope.structure.user = user;

							if ($scope.form.save)
							{
								$.cookie("login-user", $scope.form.user, { expires: 365 * 2 });
								$.cookie("login-pass", $scope.form.pass, { expires: 365 * 2 });
							}

							var uri = $rootScope.redirectPath;

							if (!uri || uri == "/login" || !_.find($rootScope.structure, { uri: uri }))
							{
								uri = '/' + $rootScope.defaultModule().uri;
							}

							$location.path(uri);
						});
				})
				.error(function(error){
					var error_messages = {
						'invalid_client' : 'Usuário ou senha inválida'
					}

					$scope.form.error = error_messages[error.error_description] || error.error_description || "Usuário ou senha inválida";
				})
		}
	}])

	.controller('read', ['$rootScope', '$scope', '$routeParams', '$timeout', '$location', 'Restangular', '$cookieStore', function($rootScope, $scope, $routeParams, $timeout, $location, Restangular, $cookieStore) {

		if(!$rootScope.structure.user) return;
		if(_.where($rootScope.structure.modules, {uri: $routeParams.table}).length < 1) return $location.path($rootScope.defaultModule().uri);

		var table = $routeParams.table;
		var module = _.where($rootScope.structure.modules, {uri: table})[0];
		var Rest = Restangular.all(table)

		if(module.uniqueID) {
			$location.path(table + '/edit/' + module.uniqueID)
		}

		$scope.order = JSON.parse($cookieStore.get(table + '_order') || JSON.stringify({by: module.orderBy, reverse: (module.order === 'ASC') ? false : true}));

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
				orderBy: $scope.order.by || '',
				order: ($scope.order.reverse) ? 'DESC' : 'ASC',
				withExtraData: (_.size($scope.extraData) > 0 ? 0 : 1)
			}).then(function(response){
				$scope.data = response.data;
				$scope.pagination = response.pagination;
				$scope.count = response.count;

				if (response.extraData)
				{
					$scope.extraData = response.extraData;
				}
			});
		}

		if(module) reset()

		$scope.edit = function(e){
			if($rootScope.hasFlag($scope.module.flags, 'ru')) {
				$location.path($scope.module.uri + '/edit/' + this.data.id)
			}

			// $timeout(function(){
			// 	angular.element(e.target).find('a').trigger("click");
			// }, 0, false)
		}

		$scope.doOrder = function(field, pagination){
			if(!$rootScope.hasFlag(field.flags, 'O')) return;

			return $scope.doOrderByRequest(field, pagination);

			// if(pagination.count > 1)

			// console.log($scope.order)

			// if($scope.order.by == field.name) {
			// 	$scope.order.reverse = !$scope.order.reverse;
			// } else {
			// 	$scope.order.by = field.name;
			// 	$scope.order.reverse = false;
			// }
		}

		$scope.doOrderByRequest = function(field, pagination){
			if($scope.order.by == field.name) {
				$scope.order.reverse = !$scope.order.reverse;
			} else {
				$scope.order.by = field.name;
				$scope.order.reverse = false;
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
			if (confirm('Deseja realmente remover os itens selecionados?')) {
				var to_delete = _.map($scope.checkboxes, function(val, key){
					return (val) ? key : false;
				}).join('-');

				Rest.doDELETE(to_delete).then(reset);
			}
		}

		$scope.prevent = function(event) {
			event.stopPropagation();
		}

		$scope.save = function(data, fieldName){
			var patch = {};
			patch[fieldName] = data[fieldName];

			var item = Restangular.restangularizeElement(null, { data: patch }, table + '/' + data.id);
			item.patch();
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
		var save_states = {
			'ready': {
				can_save: true,
				label: 'Salvar',
				icon_class: false,
				button_class: 'pure-button-primary'
			},
			'saving': {
				lock_screen: true,
				label: 'Salvar',
				icon_class: 'icon-spin icon-spinner',
				button_class: 'pure-button-primary'
			},

			'saved': {
				label: 'Salvo',
				icon_class: 'icon-ok',
				button_class: 'pure-button-success'
			},

			'error': {
				label: 'Erro',
				icon_class: 'icon-error',
				button_class: 'pure-button-error'
			},

			'uploading': {
				label: 'Carregando arquivo',
				icon_class: 'icon-spin icon-spinner',
				button_class: 'pure-button-secondary'
			}
		}

		$scope.refresh = function () {
			$scope.data = Restangular.one(table, id).get();
		};

		if(module) {
			// module.uniqueID = 4;
			$scope.acao = 'Editar';
			$scope.actionFlag = 'ru';
			$scope.module = module;
			$scope.uploads = {};
			$scope.data = null;
			$scope.refresh();
		}


		$scope.$watch('data', function(data, oldValue){
			if(!data || !oldValue) return;
			$scope.button_save = save_states['ready'];
		}, true);

		$scope.$watch('uploads.uploading', function(uploading, oldValue){
			if(uploading) {
				$scope.button_save = save_states['uploading'];
			} else {
				$scope.button_save = save_states['ready'];
			}
		}, true);


		$scope.save = function(model){
			console.log('saving...');
			if(!$scope.form.$valid || !$scope.button_save.can_save) return;

			$scope.button_save = save_states['saving'];

			Restangular.restangularizeElement(null, { data: model.data }, table + '/' + model.data.id).put().then(function(){
				$scope.button_save = save_states['saved'];

				if(module.uniqueID) {
					$scope.data = Restangular.one(table, id).get();
				} else {
					$timeout(function(){
						$location.path(table);
					}, 300)
				}
			}, function(response){
				save_states['error']['label'] = response.data.error_description || 'Erro inesperado';

				var alert_message = response.data.error_alert || false;
				$scope.button_save = save_states['error'];

				if (alert_message !== false) {
					alert(alert_message);
				}

				$timeout(function() {
					$scope.button_save = save_states['ready'];	
				}, 1000);				
			});
		};
	}])

	.controller('new', ['$rootScope', '$scope', '$routeParams', '$location', 'Restangular', '$http', '$timeout', function($rootScope, $scope, $routeParams, $location, Restangular, $http, $timeout) {

		if(!$rootScope.structure.user) return;

		var table = $routeParams.table;
		var module = _.where($rootScope.structure.modules, {uri: table})[0];
		var save_states = {
			'ready': {
				can_save: true,
				label: 'Salvar',
				icon_class: false,
				button_class: 'pure-button-primary'
			},
			'saving': {
				lock_screen: true,
				label: 'Salvar',
				icon_class: 'icon-spin icon-spinner',
				button_class: 'pure-button-primary'
			},

			'saved': {
				label: 'Salvo',
				icon_class: 'icon-ok',
				button_class: 'pure-button-success'
			},

			'error': {
				label: 'Erro',
				icon_class: 'icon-error',
				button_class: 'pure-button-error'
			},

			'uploading': {
				label: 'Carregando arquivo',
				icon_class: 'icon-spin icon-spinner',
				button_class: 'pure-button-secondary'
			}
		}

		$scope.refresh = function () {
			$scope.data = Restangular.one(table, "new").get();
		};

		if(module) {
			$scope.acao = 'Criar';
			$scope.actionFlag = 'c';
			$scope.module = module;
			$scope.uploads = {};
			$scope.data = null;
			$scope.refresh();

			if($routeParams.force){
				var force = $routeParams.force.split(':');
				$scope.data.then(function(data){
					try {
						data.data[force[0]] = parseInt(force[1], 10);
					} catch(e) {
						console.error(e)
					}

				})
			}

		}

		$scope.$watch('data', function(data, oldValue){
			if(!data || !oldValue) return;
			$scope.button_save = save_states['ready'];
		}, true)

		$scope.$watch('uploads.uploading', function(uploading, oldValue){
			if(uploading) {
				$scope.button_save = save_states['uploading'];
			} else {
				$scope.button_save = save_states['ready'];
			}
		}, true)

		$scope.save = function(model){
			if(!$scope.form.$valid || !$scope.button_save.can_save) return;
			$scope.button_save = save_states['saving'];

			Restangular.restangularizeElement(null, { data: model.data }, table).post().then(function(response){
				$scope.button_save = save_states['saved'];

				if(module.redirectOnSave || !response.id){
					$location.path(table);
				} else {
					$timeout(function(){
						$location.path(table + '/edit/' + response.id);
					}, 400)
				}

			}, function(response){
				save_states['error']['label'] = response.data.error_description || 'Erro inesperado';
				$scope.button_save = save_states['error'];

				var alert_message = response.data.error_alert || false;

				if (alert_message !== false) {
					alert(alert_message);
				}

				$timeout(function() {
					$scope.button_save = save_states['ready'];	
				}, 1000);

			});
		}

	}])


	.controller('error', ['$scope', function($scope) {

	}])