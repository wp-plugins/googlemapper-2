<!-- Googlemapper Javascript support library, (C) 2008 Sylvain Saucier, GPL Licence

/*
 *	SECTION : VARIABLES
 */

var _log = "Logfile - " + "<?PHP echo __FILE__ ?>" + "\n"; 
var _err = 0;
var _destinationsToTest = 0;	//Number of destinations to tests with google to get routes
var _searchCompleted = 0;		//Used to track the number of requests pending
var _map;						//Google map object
var _start;						//start address, user input
var _startx;					//latitude of _start returned by google maps api
var _starty;					//longitude of _start returned by google maps api
var _directions = new Array();	//array of GDirections objects

/*
 *	SECTION : SUPPORT FUNCTIONS
 */

function wlog(entry) {	//Add an entry to the log
	_log += entry + "\n";
}

function showLog() {	//show raw log
	alert(_log); 
}

function sortByFlyDistance(a, b) {	//pass as argument to sort() the Bureaux Array, use after getFlyDistance populated the Bureaux Array, I should consider building an object.
    var x = a[4];
    var y = b[4];
    return ( (x < y) ? -1 : ( (x > y) ? 1 : 0 ) );
}

function sortByDriveDistance(a, b) {	//pass as argument to sort() the Bureaux Array, use in catchSearches after succelsull retreival of all routes, consider object.
    var x = a[5];
    var y = b[5];
    return ( (x < y) ? -1 : ( (x > y) ? 1 : 0 ) );
}

function debugBureauxFly(){	//append information of bureaux[] fly distance to the log
	for(i = 0; i < Bureaux.length; i++)
		wlog("    " + Bureaux[i][0] + " : " + Bureaux[i][4]);
}

function debugBureauxDrive(){	//append information of bureaux[] fly distance to the log
	for(i = 0; i < Bureaux.length; i++)
		if (Bureaux[i][5] < 99999999) //ignore uninitiated entries
			wlog("    " + Bureaux[i][0] + " : " + Bureaux[i][5]);
}

function getFlyDistance(st, sg, dt, dg){
//wlog("getFlyDistance(" + st + ", " + sg + ", " + dt + ", " + dg + ")");
	if (st > dt) var x = st - dt;	else var x = dt - st;
	if (sg > dg) var y = sg - dg;	else var y = dg - sg;
//	wlog("    return " + Math.sqrt((x*x)+(y*y)));
	return Math.sqrt((x*x)+(y*y));
}

function closeDestinations(factor){ //calculate the number of location withing the range, Bureaux should be in a state sorted by fly distance
	var numberOfTests = 0;
	while(Bureaux[numberOfTests][4] < (Bureaux[0][4] * factor) )
		numberOfTests++;
	return numberOfTests;
}

/*  SECTION : MAIN PROGRAM - ASYCHRONOUS DESIGN, PLEASE READ SCHEMA BEFORE DOING CHANGES
	startSearch() -> maps.google
	maps.google -> validateAndFind()
	launchSearchs -> maps.google
	maps.google -> catchSearchs()
	...as needed
	maps.google -> catchSearchs() */

function help(){
	alert("Veuillez entrer une adresse comprenant ville ou code postal, province et pays.\n\nPour voir les informations de déboguage, entrez !debug dans la barre de recherche.");
}


function startSearch(__start, __map){	//Initiate the asynchronous search sequence
	//initialize variables
	_destinationsToTest = 0;
	_searchCompleted = 0;
	_directions = new Array();
	_map = __map;
	_start = __start;
	
	//check user input
	if (!_start || !_map){ //check if objects exists
		if (_start == ""){ //check if there is a user input
			wlog("    missing address : No input from user.");
			alert("Vous devez d'abord entrer une adresse.");
		}
		else{ //what happens if jabascript objects are missing
			wlog("    bad _start or bad _map. FATAL ERROR");
			alert("Objets invalides. Veuillez contacter le webmestre de ce site pour l'en informer.");
		}
	}
	else{
		if (_start == "!debug"){ //if user input is "!debug" show debug infos instead of launching search
			showLog(); //display the log
		}
		else{ //everything is fine, initiate the search sequence
			wlog("startSearch(" + _start + ", " + _map + ")");
			var geocoder = new GClientGeocoder();
			geocoder.getLatLng(_start, validateAndFind); //ask maps.google the latitude and longitude of source address, user input. Data is sent to validateAndFind() as a coords object.
		}
	}
	return false;
}

function validateAndFind(coords){ // Validate the coordinates returned by geocoder in startSearch(), find the closest offices, and initiate routes testing
	//write to log and initiate variables
	wlog("validateAndFind" + coords + "");
	_startx = coords.lat();
	_starty = coords.lng();
	b = Bureaux;
	
	//check if coordinates are valid
	if (coords == null) { wlog("    start address cannot be not found"); alert("Impossible de trouver ."); }
	else //coordinates are valid
	{
		for (var i = 0; (b.length) > i; i++){ //populate drive distance
			b[i][4] = getFlyDistance(coords.lat(), coords.lng(), b[i][1], b[i][2]);
			//wlog("    " + b[i][0] + " : " + b[i][4]);
		}
		b.sort(sortByFlyDistance); //sort the array by fly distance
		_destinationsToTest = closeDestinations( 3 ); //calculate if many locations should be considered, the parameter represent the radius of the search, as a factor in relation to the closest location found
		//log everything
		wlog("Array by fly distance"); 
		debugBureauxFly();
		wlog("    closest office : " + b[0][0]);
		//pass results to the function responsible launching the searchs
		launchSearches( _destinationsToTest, coords.lat(), coords.lng() , 3);
	}
}

function launchSearches(n, lat, lon, limit){ // [n = number of offices to try, limit = maximum number of searchs]
if (n > limit) {n = limit; _destinationsToTest = limit;} //limit the number of searchs if necessary
wlog("launchSearches(" + n + ", " + lat + ", " + lon + ", " + _map); //log progress
	while ( n-- > 0 ){ //process erevy entries
		//initiate a new GDirections object in the array
		_directions[n] = new GDirections();
		//load the present test directions
		_directions[n].load("from: " + lat + ", " + lon + " to: " + Bureaux[n][1] + ", " + Bureaux[n][2], { "locale":locale, "getPolyline":false, "getSteps":false });
wlog("    from: " + lat + ", " + lon + " to: " + Bureaux[n][1] + ", " + Bureaux[n][2]);
		//add listener to catch the results, good and bad
		GEvent.addListener(_directions[n], "load", catchSearchs);
		GEvent.addListener(_directions[n], "error", function() {
			var code = _directions[n].getStatus().code;
			var reason="Code "+code;
			if (reasons[code]) {
				reason = "Code "+code +" : "+reasons[code];
			} 
			alert("Failed to obtain directions, "+reason);
		});
	}
}


function catchSearchs(){
	if (++_searchCompleted == _destinationsToTest){ // Test if it is the last result to get
		for(i = 0 ; i < _directions.length ; i++ ){ // Load data to our Array Bureaux[x][5]
			Bureaux[i][5] = _directions[i].getDistance().meters; //the i value works because Bureaux and _directions have the same order
			Bureaux[i][6] = i;
		}
		for(i = 0 ; i < _directions.length ; i++ ){ // Delete unneeded GDirections objects
			_directions[i].clear();
		}
		Bureaux.sort(sortByDriveDistance);			// Sort by driving distance
		// log everything
		wlog("  Array by drive distance");
		debugBureauxDrive();
		wlog("  The closest office is : " + Bureaux[0][0]);
		//load the most optimal route
		var theroute = new GDirections(_map, document.getElementById("sidebar_destination"));
		_map.clearOverlays();
		theroute.load("from: " + _startx + ", " + _starty + " to: " + Bureaux[0][1] + ", " + Bureaux[0][2], { "locale":locale });
	}
}

function HideContent(d) {
	if(d.length < 1) { return; }
	document.getElementById(d).style.display = "none";
}

function ShowContent(d) {
	if(d.length < 1) { return; }
	document.getElementById(d).style.display = "block";
}

function ReverseContentDisplay(d) {
	if(d.length < 1) { return; }
	if(document.getElementById(d).style.display == "none") { document.getElementById(d).style.display = "block"; }
	else { document.getElementById(d).style.display = "none"; }
}

//-->
