'use strict';

/* Directives */


angular.module('manager.directives', []).

	directive('needFlag', ['$rootScope', function($rootScope) {
		return {
			restrict: 'A',
			link: function(scope, elm, attrs) {
				if(!scope.module) return;
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
				scope.data.then(function(){
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
				})
			}
		};

	}]).

	directive('epic', ['$timeout', function($timeout){
		return {
			restrict: 'A',
			require: 'ngModel',
			link: function(scope, elm, attrs, ngModel) {

				scope.data.then(function(data){
					var opts = {
						container: elm.attr('id'),
						textarea: null,
						basePath: 'lib/epic-editor/',
						clientSideStorage: false,
						// localStorageName: 'epiceditor',
						// useNativeFullscreen: true,
						// parser: marked,
						file: {
						//     name: 'epiceditor',
							defaultContent: data[scope.field.name] || "",
						//     autoSave: 100
						},
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

					var editor = new EpicEditor(opts)

					$timeout(function() {

						return editor.load(function(){
							var iFrameEditor = editor.getElement('editor');

							var contents = $('body',iFrameEditor).html();

							$('body', iFrameEditor).blur(function() {

								if (contents!=$(this).html()){
									contents = $(this).html(); // set to new content
									editor.save(); // important!
									var rawContent = editor.exportFile();

									ngModel.$setViewValue(rawContent)
									//console.log('set', rawContent)
									scope.$apply();
								}
							});
						});
					});
				})



			}
		}
	}]).

	directive('date', ['$timeout', function($timeout){
		return {
			restrict: 'A',
			scope: {
				date: '=date'
			},
			link: function(scope, elm, attrs) {

				var exp = new RegExp(/[0-9]{2}\/[0-9]{2}\/[0-9]{4}/);

				scope.$watch('date', function(data){
					if(!data) return;

					$(elm)
						.data('value', data.match(exp)[0])
						.pickadate({
							// format: 'dd/mm/yyyy',
							formatSubmit: 'dd/mm/yyyy',
							clear: 'Limpar',
							onSet: function(){
								var pickadate = this;

								scope.$apply(function(){
									var previous_value = scope.date.match(exp)[0];
									var new_value = pickadate.get('select', 'dd/mm/yyyy');
									scope.date = scope.date.replace(previous_value, new_value)
								})

							}
						})

				})


			}
		}
	}]).

	directive('time', ['$timeout', function($timeout){
		return {
			restrict: 'A',
			scope: {
				time: '=time'
			},
			link: function(scope, elm, attrs) {

				var exp = new RegExp(/[0-9]{2}\:[0-9]{2}\:[0-9]{2}/);

				scope.$watch('time', function(time){
					if(!time) return;

					$(elm)
						.val(time.match(exp)[0].slice(0, 5))
						.pickatime({
							format: 'HH:i',
							formatSubmit: 'HH:i',
							clear: 'Limpar',
							onSet: function(){
								var pickatime = this;

								scope.$apply(function(){
									var previous_value = scope.time.match(exp)[0];
									var new_value = pickatime.get('select', 'HH:i') + ':00';

									scope.time = scope.time.replace(previous_value, new_value)
								})
							}
						})

				})

			}
		}
	}]).

	directive('loader', function(){
		return {
			restrict: 'E',
			replace: true,
			template: '<img class="ajax-loader" ng-show="!data" src="img/ajax-loader.gif" />',
			link: function(scope, element, attrs) {

			}
		}
	}).

	directive('upload', function(){
		return {
			restrict: 'AC',
			controller: function($scope, $http){
				$scope.data.then(function(data){
					$scope.deleteFile = function(index, access_token){
						var that = this;

						$http.post(that.field.update_url + "/delete/" + ((data.id) ? data.id + "/U/" : "0/C/"), {index: index}, {params: {access_token: access_token}})
							.success(function (content) {
								$scope.data.$$v[that.field.name] = content.items;
							})
					}

					$scope.jdUploadURL = function () {
						return this.field.update_url + "/upload/" + ((data.id) ? data.id + "/U/" : "0/C/");
					}

					$scope.jdLog = function(content) {
						$scope.uploads.error = content;
					};

					$scope.jdSuccess = function(content) {};

					$scope.jdFinished = function(content, didUpload) {
						var name = this.field.name;

						if (content.error) {
							$scope.uploads.error = content.error_description;
						} else {
							$scope.data.$$v[name] = content.items;
						}
					};

					$scope.jdAccept = function(){
						return 'image/*';
					}
				})
			}
		}
	}).

	directive('customButton', [function () {
		return {
			restrict: 'A',

			controller: function($scope, $http, $location){
				$scope.doButtonAction = function(button, token, actionFlag, id){

					//type request
					if(button.type == 'request'){
						button.loading = true;

						$http.get(button.url, {params: {access_token: token, flag: actionFlag, id: id}})
							.success(function(response){
								button.loading = false;
								if(!response.error){
									alert(response.message)
								} else {
									alert(response.error_description)
								}
							})
							.error(function (error) {
								button.loading = false;
								alert("Occoreu um erro.");
							})
					}

					//type redirect
					if(button.type == 'redirect'){
						$location.path(button.url)
					}

					//type redirect with param
					if(button.type == 'redirectWithParam'){


						$scope.data.then(function(data){
							_.each(button.param, function(value, key){
								value = value.split(':')
								value[1] = data[value[1]]

								var search = {};
									search[key] = value.join(':');

								$location.path(button.url).search(search);
								return false;
							})
						})
					}

					//type print
					if(button.type == 'print'){
						window.print();
					}

					//type export
					if(button.type == 'export'){
						var use = (/\?/.test(button.url)) ? '&' : '?';
						window.open(button.url + use + 'access_token=' + token + '&flag=' + actionFlag + ((id) ? '&id=' + id : ''));
					}

				}
			}
		};
	}])


