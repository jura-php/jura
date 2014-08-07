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

	directive('itemsField', ["Restangular", "$timeout", "$http", "$q", "$rootScope", function(Restangular, $timeout, $http, $q, $rootScope){
		return {
			restrict: 'A',
			require: 'ngModel',
			compile: function (tElm, tAttrs) {
				return function (scope, elm, attrs, controller) {
					scope.data.then(function (data) {
						var items = data.extraData[scope.field.name];
						var size = _.size(items);
						var isMultiple = angular.isDefined(tAttrs.multiple);
						var opts = {
							minimumResultsForSearch: 10,
							multiple: isMultiple
						};

						var toAngular = function (select2Data)
						{
							if(!select2Data) return;

							var model;
							if (opts.multiple)
							{
								model = [];
								_.each(select2Data, function (value, key) {
									model.push(value.id);
								});
							}
							else
							{
								model = select2Data.id;
							}

							return model;
						}

						var toSelect2 = function (angularData)
						{
							var model;
							if (opts.multiple)
							{
								model = [];
								_.each(angularData, function (value, key) {
									model.push(formatValue(value, items[value]));
								});
							}
							else
							{
								model = formatValue(angularData, items[angularData]);
							}

							return model;
						}

						var formatValue = function (key, value)
						{
							if(typeof value === 'object') {
								value = value.label;
							}

							if (key == parseInt(key))
							{
								key = parseInt(key);
							}

							return { id: key, text: value };
						}

						if (size > 35)
						{
							opts.query = function (info)
							{
								var results = [];

								_.each(items, function (value, key) {

									if(typeof value === 'object') {
										value = value.label;
									}

									if (!info.term || value.toLowerCase().indexOf(info.term.toLowerCase()) != -1)
									{
										results.push(formatValue(key, value));
									}
								});

								results = _.sortBy(results, "text");

								info.callback({ results: results });
							};

							opts.initSelection = function(element, callback)
							{

								var id = $(element).val();
								var value = items[id]

								if(typeof value === 'object') {
									value = value.label;
								}

								callback({
									id: id,
									text: value
								});
							};

							opts.minimumInputLength = size < 70 ? 1 : size < 200 ? 2 : 3;
						}
						else
						{
							var results = [];

							_.each(items, function (value, key) {
								results.push(formatValue(key, value));
							});

							results = _.sortBy(results, "text");

							opts.data = results;
						}

						if (controller)
						{
							// Watch the model for programmatic changes
							scope.$watch(attrs.ngModel, function(current, old) {
								if (!current) {
									return;
								}
								if (current === old) {
									return;
								}
								controller.$render();
							}, true);

							controller.$render = function () {
								if (opts.multiple) {
									elm.select2('data', toSelect2(controller.$viewValue));
								} else {
									if (angular.isObject(controller.$viewValue)) {
										elm.select2('data', controller.$viewValue);
									} else if (!controller.$viewValue) {
										elm.select2('data', null);
									} else {
										elm.select2('val', controller.$viewValue);
									}
								}
							};

							// Update valid and dirty statuses
							controller.$parsers.push(function (value) {
								var div = elm.prev();
								div .toggleClass('ng-invalid', !controller.$valid)
									.toggleClass('ng-valid', controller.$valid)
									.toggleClass('ng-invalid-required', !controller.$valid)
									.toggleClass('ng-valid-required', controller.$valid)
									.toggleClass('ng-dirty', controller.$dirty)
									.toggleClass('ng-pristine', controller.$pristine);
								return value;
							});

							// Set the view and model value and update the angular template manually for the ajax/multiple select2.
							elm.bind("change", function () {
								if (scope.$$phase)
								{
									return;
								}

								scope.$apply(function () {
									controller.$setViewValue(toAngular(elm.select2('data')));
								});
							});

							if (opts.initSelection)
							{
								var initSelection = opts.initSelection;
								opts.initSelection = function (element, callback)
								{
									initSelection(element, function (value) {
										controller.$setViewValue(toAngular(value));
										callback(value);
									});
								};
							}

							elm.bind("$destroy", function() {
								elm.select2("destroy");
							});

							attrs.$observe('disabled', function (value) {
								elm.select2('enable', !value);
							});

							attrs.$observe('readonly', function (value) {
								elm.select2('readonly', !!value);
							});

							// Initialize the plugin late so that the injected DOM does not disrupt the template compiler
							scope.$watch("select2Opts", function(current, old) {
								$timeout(function () {
									elm.select2(opts);

									// Set initial value - I'm not sure about this but it seems to need to be there
									elm.val(controller.$viewValue);

									// important!
									controller.$render();

									// Not sure if I should just check for !isSelect OR if I should check for 'tags' key
									if (!opts.initSelection)
									{
										controller.$setViewValue(toAngular(elm.select2('data')));
									}
								});
							}, true);
						}
					});
				}
			}
		};
	}]).

	directive('epic', ['$timeout', function($timeout){
		return {
			restrict: 'A',
			require: 'ngModel',
			link: function(scope, elm, attrs, ngModel) {

				scope.data.then(function(data){
					data = data.data;
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

						$http
						.post(that.field.update_url + "/delete/" + ((data.data.id) ? data.data.id + "/U/" : "0/C/"), {index: index}, {params: {access_token: access_token}})
						.success(function (content) {
							$scope.data.$$v.data[that.field.name] = content.items;
						})
					}

					$scope.sortUp = function (index, access_token) {
						var that = this;

						$http
						.post(that.field.update_url + "/sort/" + ((data.data.id) ? data.data.id + "/U/" : "0/C/"), { from: index, to: index - 1 }, {params: {access_token: access_token}})
						.success(function (content) {
							$scope.data.$$v.data[that.field.name] = content.items;
						});
					};

					$scope.sortDown = function (index, access_token) {
						var that = this;

						$http
						.post(that.field.update_url + "/sort/" + ((data.data.id) ? data.data.id + "/U/" : "0/C/"), { from: index, to: index + 1 }, {params: {access_token: access_token}})
						.success(function (content) {
							$scope.data.$$v.data[that.field.name] = content.items;
						});
					};

					$scope.jdUploadURL = function () {
						return this.field.update_url + "/upload/" + ((data.data.id) ? data.data.id + "/U/" : "0/C/");
					}

					$scope.jdLog = function(content) {
						$scope.uploads.error = content;
					};

					$scope.jdSuccess = function(content) {};

					$scope.jdFinished = function(content, didUpload) {
						var name = this.field.name;

						if (!content)
						{
							return;
						}

						if (content.error) {
							$scope.uploads.error = content.error_description;
						} else {
							$scope.data.$$v.data[name] = content.items;
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
									if (response.refresh)
									{
										$scope.refresh();
									}

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
						if (button.params)
						{
							$scope.data.then(function(data){
								var search = {};

								_.each(button.params, function(value, key) {
									if (value.indexOf(":") > -1)
									{
										value = value.split(':');
										value[1] = data.data[value[1]];
										value = value.join(':');
									}
									else
									{
										value = data.data[value];
									}

									search[key] = value;
								});

								$location.path(button.url).search(search);
								return false;
							})
						}
						else
						{
							$location.path(button.url);
						}
					}

					if(button.type == 'redirectOutside') {
						$scope.data.then(function(data){
							console.log(button);
							var buttonURL = button.url.split(':');

							var tmpUrl = 'http://';

							_.each(buttonURL, function(value, key) {

								if (data.data[value]) {
									tmpUrl += data.data[value];
								} else {
									tmpUrl += value;
								}
							});

							window.open(tmpUrl,'_blank');
						});
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
	}]).

	directive('password', ['$timeout', function($timeout){
		return {
			restrict: 'A',
			controller: function ($scope) {
				$scope.showFields = function () {
					$scope.change = true;
				}
			},
			link: function(scope, elm, attrs) {
				scope.data.then(function(data){
					data = data.data;

					scope.$watch(attrs.password, function(password){
						console.log("change");

						scope.field.validation.required = (password != "");

						// $(scope.field.name + "-confirm-id").pattern = password;
						// $(scope.field.name + "-confirm-id").attr('required', (password != ""));
						// console.log(password);
					});
				});
			}
		}
	}])


