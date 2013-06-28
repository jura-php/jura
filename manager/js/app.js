'use strict';


// Declare app level module which depends on filters, and services
angular.module('manager', ['manager.filters', 'manager.services', 'manager.directives', 'manager.controllers', 'restangular'])
    .config(['$routeProvider', 'RestangularProvider', function($routeProvider, Restangular) {

        Restangular.setBaseUrl(config.api_url.replace(/\/$/, ""));

        $routeProvider
            .when('/:table', {templateUrl: 'partials/list.html', controller: 'read'})
            .when('/:table/new', {templateUrl: 'partials/edit.html', controller: 'new'})
            .when('/:table/edit/:id', {templateUrl: 'partials/edit.html', controller: 'edit'})

    }])

    .run(['$rootScope', '$location', '$http', '$routeParams', function($rootScope, $location, $http, $routeParams){
        $http.get(config.api_url + 'structure')
            .success(function(structure){
                $rootScope.structure = structure;
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
