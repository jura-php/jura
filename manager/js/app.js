'use strict';


// Declare app level module which depends on filters, and services
angular.module('manager', ['manager.filters', 'manager.services', 'manager.directives', 'manager.controllers', 'restangular'])
    .config(['$routeProvider', 'RestangularProvider', function($routeProvider, Restangular) {

        Restangular.setBaseUrl(config.api_url.replace(/\/$/, ""));

        $routeProvider
            .when('/login', {templateUrl: 'partials/login.html', controller: 'login'})
            .when('/:table', {templateUrl: 'partials/list.html', controller: 'read'})
            .when('/:table/new', {templateUrl: 'partials/edit.html', controller: 'new'})
            .when('/:table/edit/:id', {templateUrl: 'partials/edit.html', controller: 'edit'})

    }])

    .run(['$rootScope', '$location', '$http', '$routeParams', 'Restangular', function($rootScope, $location, $http, $routeParams, Restangular){

        Restangular.setErrorInterceptor(function(response){
            $location.path('/login');
        })

        $rootScope.$watch('structure.user', function(user){
            if(!user) return;
            Restangular.setDefaultRequestParams({access_token: user.access_token});
        })

        $http.get(config.api_url + 'structure')
            .success(function(structure){
                $rootScope.structure = structure;

                if(!structure.user) {
                    $location.path('/login');
                } else {
                    $location.path('/users');
                }
            })

        $rootScope.hasFlag = function(flags, need) {

            if(!flags || !need) return;

            var has = true;
            _.each(need.toString().toLowerCase().split(""), function(flag){
                if(_.indexOf(flags.toString().toLowerCase(), flag) == -1) has = false;
            })

            return has;
        }

    }]);
