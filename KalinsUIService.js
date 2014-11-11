(function() {

var kalinsApp = angular.module('kalinsUI', []);

//keep track of the average true/false state of an array of booleans and return certain strings based on whether most values are true or false
//(for use in tracking open/close state of our accordion but could be used for any kind of "toggle all" functionality)
kalinsApp.factory('kalinsToggles', ["$rootScope", function($rootScope) {
	
	var kalinsToggles = function(aBooleans, sTrue, sFalse, sLocalStorageName){
		var self = this;
		
		self.aBooleans = angular.fromJson(localStorage.getItem(sLocalStorageName));
		
		if(!self.aBooleans){
		  self.aBooleans = aBooleans;
		}
		
		self.bMostTrue = true;//state for close/open all button
		
		self.sToggleAllTrue = sTrue; //"Close All";
		self.sToggleAllFalse = sFalse; //"Open All";
		self.sToggleAll = self.sToggleAllTrue;//model string to show on close/open all button
		
		$rootScope.$watch(function() {
			return self.aBooleans;
		}, function(){			
			var nTrueCount = 0;
			//loop to see how many false values we have
			for(var i = 0; i < self.aBooleans.length; i++ ){
				if(self.aBooleans[i]){
					nTrueCount++;
				}
			}
		
			self.bMostTrue = nTrueCount > self.aBooleans.length/2;
			
			//set our toggleAll text to correct value based on whether or not most are true
			if(self.bMostTrue){
				self.sToggleAll = self.sToggleAllTrue;
			}else{
				self.sToggleAll = self.sToggleAllFalse;
			}
			
			localStorage.setItem(sLocalStorageName, angular.toJson(self.aBooleans));
		}, true);
	
		//turn everything to the opposite of what the current average is
		self.toggleAll = function(){
			self.bMostTrue = !self.bMostTrue;
			for(var i = 0; i < self.aBooleans.length; i++ ){
				self.aBooleans[i] = self.bMostTrue;
			}
		}
	}
	
	return kalinsToggles;
}]);


kalinsApp.factory('kalinsAlertManager', ["$filter",function($filter) {
	
	var kalinsAlertManager = function(nMax){
		var self = this;
		self.nMax = nMax;
		self.aAlerts = [];
		self.nTotalAlerts = 0;//the total number of alerts we've shown so far
		
		self.closeAlert = function(index) {
		  self.aAlerts.splice(index, 1);
		};
		
		self.addAlert = function(sAlertMessage, type) {
		  self.nTotalAlerts++;//increment first so it starts at 1 but still represents an accurate total
		  
		  var oNewAlert = {type:type, msg:sAlertMessage, index:self.nTotalAlerts};
		  oNewAlert.index = self.nTotalAlerts;

		  //add new alert to beginning of array so it's shown on top
		  self.aAlerts.unshift(oNewAlert);
		  
		  //if we've gone beyond our maximum, remove last item in array
		  if(self.aAlerts.length > self.nMax){
		    self.closeAlert(self.aAlerts.length - 1);
		  }
		};
	}
	
	return kalinsAlertManager;
}]);

})();