/*jslint indent: 2, plusplus: true, unparam:true*/
/*global angular, localStorage*/

(function () {
  "use strict";

  var kalinsApp = angular.module('kalinsUI', []);

  //keep track of the average true/false state of an array of booleans and return certain strings based on whether most values are true or false
  //(for use in tracking open/close state of our accordion but could be used for any kind of "toggle all" functionality)
  kalinsApp.factory('kalinsToggles', ["$rootScope", function ($rootScope) {
    var kalinsToggles = function (aBooleans, sTrue, sFalse, sLocalStorageName) {
        var self = this;
        self.aBooleans = angular.fromJson(localStorage.getItem(sLocalStorageName));

        if (!self.aBooleans) {
          self.aBooleans = aBooleans;
        }

        self.bMostTrue = true;//state for close/open all button

        self.sToggleAllTrue = sTrue; //"Close All";
        self.sToggleAllFalse = sFalse; //"Open All";
        self.sToggleAll = self.sToggleAllTrue;//model string to show on close/open all button

        $rootScope.$watch(function () {
          return self.aBooleans;
        }, function () {
          var nTrueCount = 0,
            i = 0;
          //loop to see how many false values we have
          for (i; i < self.aBooleans.length; i++) {
            if (self.aBooleans[i]) {
              nTrueCount++;
            }
          }
          self.bMostTrue = nTrueCount > self.aBooleans.length / 2;

          //set our toggleAll text to correct value based on whether or not most are true
          if (self.bMostTrue) {
            self.sToggleAll = self.sToggleAllTrue;
          } else {
            self.sToggleAll = self.sToggleAllFalse;
          }

          localStorage.setItem(sLocalStorageName, angular.toJson(self.aBooleans));
        }, true);

        //turn everything to the opposite of what the current average is
        self.toggleAll = function () {
          var i = 0;
          self.bMostTrue = !self.bMostTrue;
          for (i; i < self.aBooleans.length; i++) {
            self.aBooleans[i] = self.bMostTrue;
          }
        };
      };

    return kalinsToggles;
  }
    ]);


  kalinsApp.factory('kalinsAlertManager', [function () {

    var kalinsAlertManager = function (nMax) {
      var self = this;
      self.nMax = nMax;
      self.aAlerts = [];
      self.nTotalAlerts = 0;//the total number of alerts we've shown so far

      self.closeAlert = function (index) {
        self.aAlerts.splice(index, 1);
      };

      self.addAlert = function (sAlertMessage, type) {
        self.nTotalAlerts++;//increment first so it starts at 1 but still represents an accurate total

        var oNewAlert = {type: type, msg: sAlertMessage, index: self.nTotalAlerts};
        oNewAlert.index = self.nTotalAlerts;

        //add new alert to beginning of array so it's shown on top
        self.aAlerts.unshift(oNewAlert);

        //if we've gone beyond our maximum, remove last item in array
        if (self.aAlerts.length > self.nMax) {
          self.closeAlert(self.aAlerts.length - 1);
        }
      };
    };

    return kalinsAlertManager;
  }]);

  //prevents the bubbling of whatever event you pass in
  //put this on an element that has a click listener that is inside another element with a click listener
  //more reference: http://stackoverflow.com/questions/14544741/how-can-i-make-an-angularjs-directive-to-stoppropagation/14547223#14547223
  //usage: <i ng-click="InputCtrl.testfunction ($event);" stop-event='click' class="glyphicon glyphicon-info-sign kInfoIcon"></i>
  kalinsApp.directive('stopEvent', function () {
    return {
      restrict: 'A',
      link: function (scope, element, attr) {
        element.bind(attr.stopEvent, function (e) {
          e.stopPropagation();
        });
      }
    };
  });

  //replace all instances of k-help with this longer span
  kalinsApp.directive('kHelp', function () {
    var directive = {};
    directive.restrict = 'E'; //restrict so that this only fires when its an element named k-help
    directive.require = "^str"; //require that the str param be available on the k-help element

    directive.scope = {//put the str var into our current scope so that the template can use it
      str : "@"
    };

    directive.template = '<span tooltip-html-unsafe="<p align=left>{{str}}</p>" tooltip-placement="right" tooltip-trigger tooltip-popup-delay="500" class="glyphicon glyphicon-info-sign kInfoIcon"></span>';
    return directive;
  });
}());