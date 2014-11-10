(function() {

var kalinsApp = angular.module('kalinsUI', []);

//keep track of the average true/false state of an array of booleans and return certain strings based on whether most values are true or false
//(for use in tracking open/close state of our accordion but could be used for any kind of "toggle all" functionality)
kalinsApp.factory('kalinsToggles', ["$rootScope", function($rootScope) {
	
	var kalinsToggles = function(aBooleans, sTrue, sFalse){
		var self = this;
	
		self.aBooleans = aBooleans;
		self.bMostTrue = true;//state for close/open all button
		
		self.sToggleAllTrue = sTrue; //"Close All";
		self.sToggleAllFalse = sFalse; //"Open All";
		self.sToggleAll = self.sToggleAllTrue;//model string to show on close/open all button
		
		$rootScope.$watch(function() {
			return self.aBooleans;
		}, function(){			
			var nTrueCount = 0;
			console.log("watcher!");
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

})();