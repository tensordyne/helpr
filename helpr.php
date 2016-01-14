<!-- Evan was here -->

<!DOCTYPE HTML><html>
<!-- 
|| HELPR || Hypermedia Extraction, Listing and P2P Reporting: "A Series of Tubes Project!"
Concept: Create Moving HTML that always changes.
-->
 <head>
  <title> || HELPR || Series of Tubes Project! </title>
  <style>.errout { color: red; }</style>
  <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.0/jquery.js"></script>
  <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/underscore.js/1.6.0/underscore-min.js"></script>
  <script src="http://crypto-js.googlecode.com/svn/tags/3.1.2/build/rollups/sha1.js"></script>
  <script type="text/javascript">
  <?php include_once "id.js.php"?>
  
  $(document).ready(function(){
  
  	var self = this; // debugging and useful in other ways.
	var max = { depth: 3, len: 5 }; // max depth and length for all tubes on HELPR webpage.
	var mode = 'hud'; // Heads Up Disp. Other possibility is 'web'. Only difference is screen table is hidden or not.
	var screen$ = $('table:first'), out$ = screen$.find('tr:last > td:first'); 
	var input$ = $('input:first'), bout$ = $('i:first'); 
	var hash = function hash ( str ) {
	
		return CryptoJS.SHA1(str).toString();
	};
	
	var info = ' -> <br>\
	HELPR is a project to create a system for sharing streamed HTML channels. \
	The HTML streamed channels are known as "tubes". A HUD, or "Heads-Up Display", \
	has been programmed to allow one to interact with this tube system. \
	This HUD has a command-line and text output area that are both currently on display. \
	Type "help" for more information.';
	
	// Utility Functions 

	function escapeHtml ( str ) {
	
	    var div = document.createElement('div');
	    div.appendChild(document.createTextNode(str));
	    return div.innerHTML;
	};

	function id ( ) {
	
		return Math.random().toString(36).substr(2);   
	}

	function isInt ( n ) {
	
		return (Number(n) === n && n % 1 === 0);
	}
	
	function isXMLTagName ( tag ) {
	
	    var t = !/^[xX][mM][lL].*/.test(tag); 
	    t = t && /^[a-zA-Z_].*/.test(tag);
	    t = t && /^[a-zA-Z0-9_\-\.]+$/.test(tag);
	    return t; 
	}
	
	// Command-line Terminal. //

	function out ( disp ) {
	
		var output = '<code style="font-size:10; font-family:Courier;">' + disp + '<br></code>';
		out$.prepend(output);
	}
	
	var timeMark = 0;
	
	var term = {
	
		rcount: [0],	// recursive helpr count that helps keep track of interpret recursive calls: 'helpr[0][0]...'. 
		ecount: 0,	// The number of errors so far.
		input: '',	// The current command string.
		output: '',	// The latest output.
		maxline: 1024,	// Max Number of Commands to display.
		
		increment: function ( ) {
		
			// Increment the index count at the last recursive level.
			term.rcount[term.rcount.length - 1]++;
		},
		
		pushCount: function ( ) {
			
			// Add 0 element to end of rcount to start new count when entering
			// a recursive call to .
			term.rcount.push(0); 
		},
		
		dropCount: function ( ) {
		
			// Get rid of last count array element to leave a recursive call of the
			// interpret function.
			term.rcount = term.rcount.slice(0, term.rcount.length - 1);
		},
		
		countString: function ( ) {
		
			// Display the count string.
			var out = '';
			_.each(term.rcount, function(countString) { out += '[' + countString + ']'; });
			return out;
		},
		
		prompt: function ( ) {
		
			term.increment();
			return 'helpr[' + term.countString() + ']: ';
		},
		
		display: function ( msg ) {
		
			if (term.maxline <= term.index) {
				out$.find('code:eq(-1)').remove();
			}
			
			term.output = term.prompt() + escapeHtml(term.input) + ' > ' + msg;
			bout$.html(msg);
			out(term.output)
			console.log(term.output);
		},

		error: function ( msg ) {
		
			var prom = term.prompt();
			term.output = prom + 
				'<b class="errout">error[' + term.ecount + ']:</b> ' + 
				escapeHtml(term.input) + ' > ' + msg;
			term.ecount++;
			term.errout = prom + ' error[' + term.ecount + ']: ' + term.input + ' > ' + msg;
			out(term.output);
			console.log(term.errout);
		},
		
		run: function ( ) {
		
			if (term.maxline <= term.index) {
			
				out$.find('code:eq(-1)').remove();
			}
			
			interpret(term.input, true);
			
			//try { interpret(term.input, true); }
			//catch(e) { term.error(e); }
		}
	};
			
	function setGeometry ( ) {
	
		var u = input$.height(), w = window.innerWidth - 3 * u;
		input$.css({width: w});
		screen$.css({width: w, top: u, left: u});
	}
	
	setGeometry();
	
	// Interpret Command. //
	
	// Work to do and how...
	// I. Timer controls.
	// II. hfrags and tubes.
	// III. Interface to matrix.org
	// IV. Document things.
	
	function check(condition, command, message) {
		var out = command + ": " + message;
		if (!condition) {
			term.error(out);
			return true;
		}
		return false;
	}
	
	// Tube Area. //

	var broker = "broker.php";	
	var hfragContext = null; // tubeContext changed by "tube" command, "hfrag" for hfragContext.
	var index = {};

	// Different types of resources in HELPR:
	// css style
	// html id property mapping: id in hfrag -> "d4..."
	// event object tripple = ('snippet', 'event type', 'JSON state object') 
	// html fragment (hfrag)
	// class property. mapping: class in hfrag -> "c4..."
	// name property. mapping: name in hfrag -> "n4..."
	// tube
	// user
	// "code" object: String that gets turned into function of prototype function ( e , state ) { ...'code text'... };
	
	function joinPath ( pathArray ) { // assumption, 'pathArray' is an array object.
	
		return '/' + pathArray.join('/'); 
	}
	
	function res ( path ) { // assumption, 'path' in string format
	
		return index[hash(path)];
	}
	
	function resource ( p ) {
		
		if (p === undefined) {

			return res('/');
		} 
		
		if (_.isArray(p)) {

			return res(joinPath(p));
		}
		
		if (_.isString(p)) {

			return res(p);
		}
		
		return null;
	}

	function Tube ( init ) {
	
		var self = this;
		this.id = init.id;
		this.path = tubeContext.path.slice();
		this.path.push(this.id);
		this.dir = '/' + this.path.join('/')
		this.handle = hash(this.dir);
		this.type = 'tube';
		this.direction = 'top';			// direction to get rid of tube or hfrag in model if overflow in model.
		this.script = init.script || []; 	// set of commands to do, get from server host based on broker.
		this.state = init.state || "init";	// Tubes have 3 status codes: init, main and fini. Unless changes.
		this.data = init.data || null;		// When tube is init'd use or send with "report" request.
		this.broker = init.broker || broker; 	// This is the dynamic html ajax call to get info to update tube.
		this.model = []; 			// js array with handles to subtubes and hfrags. Like DOM for tube.
		this.depth = tubeContext.depth + 1;	// gets incremented from parent. when > max.depth, stop subtube creation.

		// max length of tube model. At maximum no greater than 'max.len'.
		init.maxlen = init.maxlen || max.len;
		this.maxlen = (max.len <= init.maxlen) ? max.len : init.maxlen;
		
		if (tubeContext.direction === 'top') {
		
			$('<div/>', {id: this.handle}).prependTo('#' + tubeContext.handle);
		
		} else {
				
			$('<div/>', {id: this.handle}).appendTo('#' + tubeContext.handle);
		}

		this.clear = function ( ) { // Clear (delete) the Tube's information from the webpage.
			
			_.each(self.model, function(element) { 
				element.clear();
			});
			
			// Handle stopping scripts in future. 
			
			// Delete self from linking sources from index and parent tube's model.
			delete index[self.handle];
			self.parent().model = _.without(self.parent().model, self.handle);
		};
		
		this.display = function ( ) {
			
			var output = "list: tube<br>";
			output += "- id: " + self.id + "<br>";
			output += "- handle: " + self.handle + "<br>";
			output += "- broker: " + self.broker + "<br>";
			output += "- path: /" + self.path.join('/') + "<br>";
			output += "- state: " + self.state + "<br>";
			output += "- data: " + self.data + "<br>";
			output += "- direction: " + self.direction;
			term.display(output);
		};
		
		this.parent = function ( ) {
		
			return resource(self.path.slice(0,-1)); 
		};
		
		return this;
	}

	var tubeContext = { path: [], direction: 'top', depth: 0 }; // dirty little trick to get things going.
	var root = tubeContext = new Tube({id: "", root: true});
	root.id = "root";
	root.path = [];
	index[root.handle] = root;
	$('#root').attr("id", root.handle);
	
	function HTMLFragment ( init ) {
		
		var self = this;
		this.id = init.id;
		init.html = init.html || "";
		this.type = 'hfrag';		
		this.path = tubeContext.path.slice();
		this.path.push(init.id);
		this.dir = '/' + this.path.join('/');
		this.handle = hash(this.dir);
		
		if (tubeContext.direction === 'top') {
		
			$('<div/>',{id: this.handle}).wrapInner(init.html).prependTo('#' + tubeContext.handle);
		
		} else {
		
			$('<div/>',{id: this.handle}).wrapInner(init.html).appendTo('#' + tubeContext.handle);
		}
		
		this.css = {}; 		// Styles associated with this tube.
		this.handlers = []; 	// event type handlers. have various formats.
		
		this.clear = function ( ) { // Clear (delete) the hfrag's information from the webpage.
		
			$('#' + self.handle).remove();
			// get rid of handlers and css in future too.
			
			// Delete self from linking sources from index and hfrag tube's model.
			delete index[self.handle];
			self.parent().model = _.without(self.parent().model, self.handle);
		};
		
		this.display = function ( ) { 
			
			var output = "list: hfrag<br>";
			output += "- id: " + self.id + "<br>";
			output += "- handle: " + self.handle + "<br>";
			output += "- path: /" + self.path.join('/');
			term.display(output);
		};

		this.parent = function ( ) {
		
			return resource(self.path.slice(0,-1)); 
		};
		
		return this;
	}
	
	// Intrepret Function //
	
	function interpret ( cmd, startChain ) {
	
		var fun = args = output = null; // used in processing cmd.
		var tube = hfrag = temp = null; // DOM manipulation.
		var h = null; 	// HTML$ for use with mounting tubes to hfrags.
		var i = 0;
		
		if (startChain) {
		
			// process other types of strings besides JSON.
			cmd = JSON.parse(cmd);
			
		} else {
		
			// Unparse JSON and then parse to escapeHtml to display.
			// Also need to push on term.rcount to start the recursive 
			// interpret counts.
			
			term.pushCount(); 
			term.input = escapeHtml(JSON.stringify(cmd));
		}
		
		fun = Object.keys(cmd);
		
		if (_.isString(cmd)) {
		
			if (cmd.trim() === '') {
			
				term.display('interpret: set of commands succesfully processed');
				return;
			}
			term.display('interpret: ' + cmd);
			return;
			
		} else if (_.isArray(cmd)) {
			
			if (cmd.length === 0) {
			
				term.display('interpret: null');
				return;
			}
			
			term.display("interpret: set of commands recieved. processing now...");
			_.each(cmd, function(c) {
				interpret(c);
			});
			
			term.input = '""';
			term.display('interpret: set of commands succesfully processed');
			return;
			
		} else if (_.isObject(cmd)) {
			
			if (fun.length === 0) {
			
				term.display('interpret: undefined');
				return;
			}

			if (fun.length === 1) {
			
			 	fun = fun[0]; // function name of command, form { "OP": <... args of func ...> }
			 	args = cmd[fun];
			 	
			} else {
			
				fun = cmd.command;
				args = cmd;
			}
			
			if (!fun) { 
			
				// report some silly error and silently fail, to keep the show going...
				term.error('interpret: command name property missing');
				return;
			}
			
			if (fun === "command") { 
			
				// In case of {"command": "<name of command>", ... } pattern.
				fun = args;
			}
			
			switch(fun) {

				case 'hfrag':
				
					// set the hfragContext to path given as argument
					// {"hfrag": "/path/to/hfrag"}
					
					temp = resource(args) || resource(tubeContext.dir + '/' + args);
					if (temp === undefined) {
					
						term.error(
							'hfrag: could not set HTML Fragment context to "' +
							args + '": does not exist'
						);
						return;
					}
					
					if (temp.type !== 'hfrag') {
					
						term.error('hfrag: resource not an HTML Fragment');
						return;
					}
					
					hfragContext = temp;
					break;
				
				case 'tube': 

					// set the tubeContext to path given as argument
					// {"tube": "/path/to/tube"}
					// {"tube": <resource>} // <resource> is tube/hfrag in tubeContext's model.					

					// Problem if root tube in code with extra / character. To get around it check if 
					// tubeContext.dir === '/'. If so, set temp to '' and then continue, if not
					// set temp to tubeContext.dir.
					
					if (tubeContext.dir === '/') temp = '';
					else temp = tubeContext.dir;
					
					temp = resource(args) || resource(temp + '/' + args);
					if (temp === undefined) {
					
						term.error(
							'tube: could not set tube context to "' +
							args + '": does not exist'
						);
						return;
					}
					
					if (temp.type !== 'tube') {
					
						term.error('tube: resource not a tube');
						return;
					}
					
					term.display('tube: tube context set to: ' + temp.dir);
					tubeContext = temp;
					break;
									
				case 'append': 
				
					// append a tube or hfrag onto contextTube's model.
					// {"append": {"tube": <tube_id>, ...}}
					// {"append": {"hfrag": <hfrag_id>, ... }}

					if (!args.tube && !args.hfrag) {
					
						term.error('append: unknown resource type to create');
						return;
					}

					temp = args.tube || args.hfrag; // check to see if already created
					
					if (temp) {
					
						temp = resource(tubeContext.dir + '/' + temp);
						if (temp) {
						
							// error, handle already exists.
							term.error('append: tube or HTML identifier already exists: ' + temp.id);
							return;
						}
					}
					
					// Check if tubeContext.model.length > tubeContext.max.length
					// If greater, then delete the first tube/hfrag to make a rolling 
					// model list of finite length that adds to the end and deletes
					// from the beginning. 
					
					if (tubeContext.maxlen <= tubeContext.model.length) {
					
						// delete first element in tubeContext's model to 
						// allow for rolling tube contents.
						temp = index[tubeContext.model[0]];
						term.display(
							'append: delete: ' + temp.type + ' ' + temp.dir + 
							' deleted due to length of tube context model being too long'
						);
						temp.clear();
					}
					
					if (args.tube) { // create tube
					
						// check to see if tube is at max depth, thus preventing a subtube from being 
						// created and an error message being displayed.
						
						if (max.depth < tubeContext.depth) {
						
							term.error(
								'append: subtube "' + args.tube + 
								'" not created because max depth reached'
							);
							return;
						}
						
						if (!isXMLTagName(args.tube)) {
						
							term.error('append: identifier not not valid xml tag name: ' + args.tube);
							return;
						}
						args.id = args.tube;
						temp = new Tube(args);
						
					} else if (args.hfrag) { // create hfrag
					
						if (!isXMLTagName(args.hfrag)) {
						
							term.error(
								'append: HTML Fragment: identifier not valid xml tag name: ' +
								args.hfrag
							);
							return;
						}
						args.id = args.hfrag;
						temp = new HTMLFragment(args);
					
					} else {
					
						term.error('append: create: "tube" or "hfrag" property does not exist');
						return;
					}
					
					index[temp.handle] = temp;
					tubeContext.model.push(temp.handle);
					
					// Check if tubeContext.model.length > tubeContext.max.length
					// If greater, then delete tube/hfrag.
					 										
					if (args.tube) {
					
						term.display(
							'append: tube "' + temp.id + 
							'" appended with handle: ' + temp.handle
						);
					}
					
					if (args.hfrag) {
					
						term.display(
							'append: HTML Fragment "' + temp.id + 
							'" appended with handle: ' + temp.handle
						);
					}
					
					break;

				case 'prepend': 
				
					// prepend a tube or hfrag onto contextTube's model.
					// {"prepend": {"tube": <tube_id>, ...}}
					// {"prepend": {"hfrag": <hfrag_id>, ... }}

					if (!args.tube && !args.hfrag) {
					
						term.error('prepend: unknown resource type to create');
						return;
					}

					temp = args.tube || args.hfrag; // check to see if already created
					
					if (temp) {
					
						temp = resource(tubeContext.dir + temp);
						if (temp) {
							// error, handle already exists.
							term.error('prepend: tube or HTML identifier already exists: ' + temp.id);
							return;
						}
					}

					// Check if tubeContext.model.length > tubeContext.max.length
					// If greater, then delete first tube/hfrag to make a rolling 
					// model list of finite length that adds to the beginning and
					// deletes from the end.
					
					if (tubeContext.maxlen <= tubeContext.model.length) {
					
						// delete last element in tubeContext's model to 
						// allow for rolling tube contents.
						temp = index[tubeContext.model[tubeContext.model.length-1]];
						term.display(
							'prepend: delete: ' + temp.type + ' ' + temp.dir + 
							' deleted due to length of tube context model being too long'
						);
						temp.clear();
					}
					
					if (args.tube) { // create tube
					
						// check to see if tube is at max depth, thus preventing a subtube from being 
						// created and an error message being displayed.
						
						if (max.depth < tubeContext.depth) {
						
							term.error(
								'prepend: subtube "' + args.tube + 
								'" not created because max depth reached'
							);
							return;
						}
						
						if (!isXMLTagName(args.tube)) {
						
							term.error('prepend: identifier not not valid xml tag name: ' + args.tube);
							return;
						}
						args.id = args.tube;
						temp = new Tube(args);
						
					} else if (args.hfrag) { // create hfrag
					
						if (!isXMLTagName(args.hfrag)) {
						
							term.error(
								'prepend: HTML Fragment: identifier not valid xml tag name: ' 
								+ args.hfrag
							);
							return;
						}
						args.id = args.hfrag;
						temp = new HTMLFragment(args);
					
					} else {
					
						term.error('prepend: create: "tube" or "hfrag" property does not exist');
						return;
					}
					
					index[temp.handle] = temp;
					tubeContext.model.unshift(temp.handle);
					 										
					if (args.tube) {
					
						term.display(
							'prepend: tube "' + temp.id + 
							'" prepended with handle: ' + temp.handle
						);
					}
					
					if (args.hfrag) {
					
						term.display(
							'prepend: HTML Fragment "' + temp.id + 
							'" prepended with handle: ' + temp.handle
						);
					}
					
					break;
				
				case 'insert':
	
					// insert a tube or hfrag at position <int> in tubeContext's model.
					// {"insert": {"at": <int>, "tube": <tube_id> ... }} 	// ... are args used to init
					// {"insert": {"at": <int>, "hfrag": <hfrag_id> ... }}	// to initiate tube/hfrag.
					
					if (!args.at) {
						
						term.error('insert: no "at" parameter');
						return;
					}
				
					if (!isInt(args.at)) {
					
						term.error(
							'insert: value of "at" parameter not integer: ' + 
							escapeHtml(args.at)
						);
						return;
					}

					// Check to see if args.at too big or too small.

					if (tubeContext.model.length <= args.at) {
					
						term.error(
							'insert: value of "at" parameter too large (' + 
							'greater than tube context model length ' + 
							tubeContext.model.length + '): ' + args.at
						);
						return;
					}

					if (args.at <= 0) {
					
						term.error('insert: value of "at" parameter too small (less than 0): ' + args.at);
						return;
					}
					
					if (args.tube) {
					
						args.id = args.tube;
						temp = new Tube(args);
					
					} else if (args.hfrag) {
					
						args.id = args.hfrag;
						temp = new HTMLFragment(args);
					
					} else {
					
						term.error('insert: "tube" or "hfrag" parameter missing');
						return;
					}
					
					// insert works in the following way. insert changes behaviour depending on 
					// whether the value tubeContext.direction is true or another value. 
					// If true, insert adds to the head of the insert value in the model, at the tail
					// otherwise.

					// If tubeContext.model = [r0, r1, ... rN], insert tube or hfrag between any two r's. 
					// args.at can have value from 0 to N - 1 to insert into model of tubeContext.
					// Example, direction = true, insert q at 0. model becomes [q, r0, ... ] with last 
					// r missing if original model too big. If direction = false, and q is inserted at
					// rN then r0 will be deleted if the model is too long and the model will be
					// [r1, ... rN, q].
					
					if (tubeContext.direction === true) {
					
						tubeContext.model.splice(args.at, 0, temp.handle);
						if (tubeContext.maxlen <= tubeContext.model.length - 1) {
						
							// delete from the end of the model.
							index[tubeContext.model[tubeContext.model.length - 1]].clear();
							term.display('delete: end of tube ' + temp.dir + ' deleted');
						}
						
						return;
						
					} else {
					
						if (tubeContext.maxlen <= tubeContext.model.length - 1) {
						
							// delete from beginning of model.
							index[tubeContext.model[0]].clear();
							term.display('delete: begining of tube ' + temp.dir + ' deleted');
						}
						
						if (args.at === tubeContext.model.length - 1) { 
						
							// append to the end of the model to keep tail
							// ended insertion because insert only does head
							// ended insertion.
							tubeContext.model.append(temp);
						
						} else {
						
							tubeContext.model.splice(args.at, 0, temp.handle);
						}					
					}
					
					index[temp.handle] = temp;
					term.display('insert: ' + temp.type + ' ' + temp.id + ' inserted at: ' + args.at);										
					break;
					
				case 'delete':
				
					// {"delete": true}		// delete tube/hfrag from top of tubeContext's model
					// {"delete": false}		// delete tube/hfrag from bottom of tubeContext's model
					// {"delete": <string>}		// delete tube/hfrag of id <string> in tubeContext's model  
					// {"delete": {"at": <int>}} 	// delete tube/hfrag at location <int> if exists 
					// {"delete": {"path": "/to/tube_or_hfrag"}}
					// {"delete": {"path": "/to/tube_or_hfrag", "css/handler...": ".cssClass"}} // future...
					
					// Get the correct handle depending on the command-line options and store in temp...
					if (args === true) {
					
						temp = tubeContext.model[0];
					
					} else if (args === false) {
					
						temp = index[tubeContext.model[tubeContext.model.length - 1]];
	
					} else if (_.isString(args)) {
						
						if (tubeContext.dir === '/') {
						
							temp = '';
						
						} else {
							
							temp = tubeContext.dir;
						
						}
						
						temp = resource(temp + '/' + args)
						if (temp === undefined) {
						
							term.error(
								'delete: could not find resource "' + 
								args + '" in current tube context to delete'
							);
							return;
						}
						
						temp = temp.handle;
					
					} else if (args.at) {
					
						if (isInt(args.at)) {
						
						 	temp = tubeContext.model[args.at];
						
						} else {
						
							term.error('delete: "at" parameter is not string or integer');
							return;
						} 
					
					} else if (args.path) {

						temp = resource(args.path).handle;
					
					} else {
					
						term.error('delete: format of request invalid');
						return;
					}
					
					temp = index[temp]; // take the handle from above stored in temp and find the
					                    // resource with that handle and store it in temp again for later...
					                    
					if (temp === undefined) {
						
						term.error('delete: resource to delete from model not found');
						return;
					}
					
					term.display('delete: ' + temp.id + ' deleted');
					temp.clear();
					
					break;
					
				case 'index': 
				
					// return hfrag or tube in index.
					// { "index": true }	// print each tube and hfrags in index.
					// { "index": false }	// Give statistics about HELPR system.
					// { "index": <id> }	// Give resource type (tube/hfrag), handle and path of <id>.
					
					if (args === true) {
					
						output = ['index:'];
						_.each(index, function(ind){
						
							output.push('- ' + ind.type + ': ' + ind.handle + ' ' + ind.dir);
						});
						term.display(output.join('<br>'));
						return;
					}
					
					if (args === false) {
					
						temp = 'index: settings of HELPR system:<br>';
						temp += '- max depth: ' + max.depth + '<br>';
						temp += '- max length: ' + max.len;
						term.display(temp);
						return;
					}
					
					if (!_.isString(args)) {
					
						term.error('index: format of handle not character string');
						return;
					}
					
					temp = index[args];
					if (temp === undefined) {
					
						term.display('index: unknown');
						return;
					}
					
					term.display('index: ' + temp.type + ' ' + temp.handle + ' ' + temp.dir);
					break;
					
				case 'hash': 
				
					// return SHA1 of string. Good for figuring out what the ID's of tubes and hfrags are.
					if (!_.isString(args)) {
					
						term.error('hash: path argument not character string');
						return;
					}
					
					term.display('hash: ' + hash(args));
					break;

				case 'error':
				
					// Echo a given error message
					
					term.error(args);
					break;
					
				case 'color': 
				
					// Change color of HUD or css color settings.
					// {"color": {"hud": "blue"}}
					// {"color": {"text": "#DEDBAD"}} // "bg" for background
					// {"command": "color", {"text": "orange", <etc.>}}
					
					if (args.hud) {
					
						try {
						
							screen$.css({ backgroundColor: args.hud });
							
						} catch (e) {
						
							term.error('color: ' + e);
							return;
						}
						
						term.display(
							'color: heads-up display overlay color changed to: ' + 
							args.hud
						);
					}

					if (args.text) {
					
						try {
						
							screen$.css({ color: args.text });
							
						} catch (e) {
						
							term.error('color: ' + e);
							return;
						}
						
						term.display('color: heads-up display text color changed to: ' + args.text);
					}
					
					if (args.bg) {
					
						try {
						
							$('body').css({ backgroundColor: args.bg });
							term.display('color: background color changed to: ' + args.bg);
						
						} catch (e) {
						
							term.error('color: ' + e);
							return;
						}
					}
				
					if (args.err) {
						
						try {
						
							$('.errout').css({ color: args.err });
							term.display('color: error reporting text color changed to: ' + args.err);
						
						} catch (e) {
						
							term.error('color: could not change error reporting text color to: ' + e);
							return;
						}
					}
					
					break;
					
				case 'do':
				
					// Format JSON request for timed events.
					// {"do": {"in": 1000, "act": < JSON request for interpret() > }}
					// {"do": [{"in": 1, "act": "Act One"}, {"in": 2, "act": "Act Two"}]}
									
				case 'css': 
				
					// Formats of JSON request to modify CSS style data.
					// {"css": {"style": ".style to > #use", "sheet": "color: blue;"}} // set css style
					// {"css": {"delete": "#style_name"}} // delete style
					
					break;
					
				case 'load':
				
					// Formats of JSON request to load update to tube from host.
					// {"command": "load", "tube": "Tu6e", event: {<event init>}}
					// {"load": {"tube": "tU63"}}
					// All fields required except <event_init> fields.

					break;
				
				case 'state':
				
					// Formats of JSON request to change state of tube.
					// {"command": "state", "data":<json>, "status":"main"}
					// {"state": {"data": <any json you want!>, "status": ("init"|...) }}
					// tube is only required field. status can be init, main or fini.

					if(check(args.tube, 'state', 'request missing tube property')) break;
					if(check(
						tubes[args.tube],
						'state', 
						'request of non-existent tube with identifier: ' + args.tube
					)) break;
										
					args.status = args.status || 'main';
					
					switch (args.status) {
					
						case 'init': 
							tubeContext.state = 'init';
							// Call initialization routines...
							break;
							
						case 'main': 
							tubeContext.state = 'main';
							break;
							
						case 'fini': 
							// Call finalization routines.
							tubeContext.state = 'fini';
							break;
						
						default: 
							term.error('state: unknown state to change to: ' + args.status);
							break;
					}
					
					break;
				
				case 'list':

					// Formats of JSON request to different resources.
					// {"list": "tube"}, {"list": "hfrag"}, for now...
					
					if (args === "tube" || !args) {
					
						tubeContext.display();
						return;
										
					} else if (!_.isString(args)) {
					
						term.error('list: list type not string');
						return;
					
					} else if (args === "hfrag") {
					
						// make pretty print of hfragContext's displayable properties
						// possible that hfragContext not yet set to anything...
						if (hfragContext === null) {
							term.display('list: (none)');
							return;
						}
						output = "list: hfrag<br>";
						output += "- id: " + hfragContext.id + "<br>";
						output += "- handle: " + hfragContext.handle + "<br>";
						output += "- path: /" + hfragContext.dir;
					
					} else if (args === "model") {
					
						// pretty print tubeContext's model...
						
						if (tubeContext.model.length < 1) {
							term.display('list: tube model empty');
							return;
						}
						
						output = ['list: model of "' + tubeContext.dir + '" :'];
						_.each(tubeContext.model, function(e, i) {
						
							temp = index[e];							
						 	output.push(
						 		'- [' + i + ']: ' 
						 		+ temp.type + ': ' + temp.handle + ' ' + temp.id
						 	);
						});
						
						output = output.join('<br>');
						
					} else { // error! list what?!?
					
						term.error('list: unknown resource to list: ' + args);
						return;
					}
					
					term.display(output);
					break;	 

				case 'path':
				
					// Set the tubeContext or hfragContext to path. 
					// Format of requests: 
					// {"path": "/path_to/tube"} // "/" is root!  
					// {"path": ["path_to_TU63", "TheTu6e"]}


					if (args === "tube") {
					
						term.display('path: tube context path: ' + tubeContext.dir);
						return;

					} else if (args === "hfrag") {
					
						if (hfragContext === null) {
						
							term.display('path: HTML Fragment context does not currently exist');
							return;
						}
						term.display('path: HTML Fragment context path: ' + hfragContext.dir);
						return;
						
					} else if (_.isString(args)) {
					
						mp = index[hash(args)];
						
					} else if (_.isArray(args)) {
					
						args = '/' + args.join('/');
						mp = index[hash(args)];
					
					} else {
					
						term.error('path: path format invalid: not in string or array form');
						return;
					}
					
					if (!mp) {
					
						term.error('path: the tube or hfrag to change context to does not exist: ' + args);
						return;
					}
					
					if (mp.type === "tube") {
					
						tubeContext = mp;
						term.display(
							'path: success setting context to tube with path: ' + 
							'/' + tubeContext.dir
						);
					}
					
					if (mp.type === "hfrag") {
					
						hfragContext = mp;
						term.display('path: success setting context to HTML Fragment with path: ' + 
							'/' + hfragContext.dir
						);
					}
					
					break;					
						
				case 'broker': 
					
					// Formats of JSON request to change broker.
					// {"broker": {"url": "//...", "tube": "tU63"}}
					// All fields required.
					
					if(check(args.url, 'broker', 'request missing "url" property')) break;
					if(check(args.tube, 'broker', 'request missing "tube" property')) break;
					if(check(index[args.tube], 'broker', 'request of non-existent tube identifier: ' + args.tube
					)) break;
					
					index[args.tube].broker = args.url;
					
					term.display('broker: tube "' + index[args.tube].id + 
						'" broker url update: ' + 
						args.url
					);
					
					break;
					
				case 'help': 
				
					// Formats of JSON request for help with webpage system.
					// {"command": "help", "topic": "help me please I am lost!"}
					// {"help": "topic here..."}
					
					if (_.isString(args)) {
					
						topic = args;
						
					} else {
					
						topic = args.topic;
					}
					
					if (!topic) {
					
						term.display (
							'help: Please use this syntax: {"help": "topic"}<br>' +
							'| help topics | about info commands help'
						);
					}
					
					switch(topic) {
					
						case 'me':
						output = 'help: type {"help": "help"}, on the command-line above, if ';
						output += 'you want information on how to use this webpage. ';
           					output += 'Hit the &lt;Ctrl&gt;-key to cycle Heads-Up Display visibility.';
           					term.display(output);
						break;
						
						case 'help': 
						term.display('help: | help topics | "about", "commands", "help", "info"');
						break;
						
						case 'about': 
						term.display('help: helpr version 0.1.0 (C) 2015 Nathan P. Cole');
						break; 
						
						case 'info':
						term.display(info);
						break;
						
						case 'commands':
						
						output = 'help: commands: <br>';
						output += '- append: apppend tube or hfrag to a tube\'s model<br>';
						output += '- broker: change a tube\'s broker setting<br>';
						output += '- delete: delete given tube, hfrag, css or handler<br>';
						output += '- handler: make handler for a given HTML Fragment<br>';
						output += '- insert: insert tube or HTML Fragment in tube context model<br>'
						output += '- help: "Helpr, A Series of Tubes Project!" help facility<br>';
						output += '- login: start using Helpr: first come first serve sign in<br>';
						output += '- logout: keep registered account information secure<br>';
						output += '- list: list pretty-print various system information<br>';
						output += '- load: force load from broker on host to get tube update<br>';
						output += '- prepend: prepend tube or hfrag to a tube\'s model<br>';
						output += '- register: make user id/password combination<br>';
						output += '- replace: replace n\'th position of tube model<br>';
						output += '- report: report back the contents of a tube to host<br>'; 
						output += '- state: change the state information of a tube'; 
						term.display(output);
						
						break;
						
						default: term.display('help: Unknown help topic.');
					}
					
					break;
				
				case 'html': 
				
					// Formats of JSON request to set or modify existing html inside of hfrag.
					// {"html": {"hfrag": "H4gr1d", "select":".jquery #selector > with.mapped.ids" "do":{ ... }}}
					// the "do" property has a number of forms.
					// {"append": "<h1>HTML to append</h1>"} // append html to selected elements
					// {"prepend": "<h1>HTML to prepend</h1>"} // prepend the html to selected elements
					// {"attr": {"prop": "value"}} // change the attribute attr
					// {"call": "event"} // force event on selected elements to occur
					// {"text": "<h1>Set HTML of elements</h1>"} // .html() jQuery wrapper
					// "detach" // remove html from selector
				
					term.display('html: success, this command is under construction...');
					break;

				case 'video':
				
					// Formats of JSON request to manipulate <video> tag element.
					// {"video": {"id": "idofvid", "do": < stuff > }}, where < stuff >, is not well
					// defined but has to do with manipulating video tag elements, change src, 
					// start, stop, etc...
					term.display('video: success, this command is under construction...');
					break;

				case 'audio':
				 
					// Display audio tag in hud command-line.
					// {"audio": {"set": ["loop", ...], "sources": {"src": "//long...", "type": "ogg/audio"}}}
					
					if (!args.sources) {
					
						term.error('audio: "sources" property missing from initialization properties');
						return;
					}
					
					output = term.prompt() + ' &gt; <audio ';
					
					args.preload = args.preload || "preload";
					if (args.preload === "preload" || args.preload === "metadata") {
					
						output += 'preload="' + args.preload + '" ';
						
					} else {
					
						term.error('audio: "preload" property value not valid: ' + args.preload);
						return;
					}
					
					if (args.set) {
						_.each(["autoplay", "controls", "loop", "muted"], function ( e ) {
							if (_.contains(args.set, e)) {
								output += e + ' ';
							}
						});
					}
					
					output += '>'
					_.each(args.sources, function ( e ) {
					
						// format is {"src": "overtherainbow.ogg", "type": "ogg/audio"} // mp3, ogg or wav
						if (!e.src || !e.type) {
							term.error('audio: sources property missing "src" or "type" property');
							return;
						}
						output += '<source src="' + e.src + '" type="' + e.type + '">'
					});
					
					output += '</audio>';
					term.display('audio: success ');
					bout$.html(output);
					
					break;
					
				case 'canvas':
				 
					// Formats of JSON request to manipulate <cavas> tag element. change src, start, stop...				
					term.display('canvas: success, this command is under construction...');
					break;
				
				case 'handler':
				
					// Formats of JSON request for setting event handlers.
					// {"handler": {"type": "mousedown|...", "state": "Initial State", "code": "return;"}}
					// The format of <handler_item> is either array of object.

					break;
				
				case 'login':
				
					// Formats of JSON request to log into system. 
					// IRC style, first come first serve.
					// {"command": "login", "name": <username>, "pass": <password>}
					// {"login": {"login", "name": <username>, "pass": <password>}}
					// <username> and <password> are strings. All fields are required.

					break;
				
				case 'logout':
				
					// Formats of JSON request for setting future timed events.
					// {"command": "logout"}
					// {"logout": "Good Bye!"}
					// Any extra fields ignored. "Good Bye!" is example string that gets printed before
					// logging off.

					break;
				
				case 'report':
				
					// Formats of JSON request to give server tube contents report.
					// {"command": "report", "token": "98908902384", "resource": "resourceId" }

					break; // report on tube contents back to server.
				
				default: break;
			}
			
		} else if (_.isNumber(cmd)) {
		
			timeMark++;
			term.display("init: mark time[" + timeMark + "]: " + Date());
			// Add timer event of string message to root tube.

			
		} else {
		
			term.error("interpret: Could not process command.");
			return;
		}
		
		if (!startChain) {
		
			// Leaving a recursive call to interpret().
			term.dropCount();
		}
	}

	// Heads-Up-Display Event Management. //
	
	var resize = {interupted: false, start: false};
	
	function resizer() {
	
		if (resize.interrupted) {
		
			resize.interrupted = false;
			clearTimeout(resize.timer);
			resize.timer = setTimeout(resizer, 10);
			return;
		} else {
		
			resize.start = false;
			resize.interrupted = false;
			if (mode !== 'web') input$.focus();
			setGeometry();
		}
	}

	screen$.mouseup(function(){input$.focus();});

	input$.focus();
	
	input$.keyup(function(e) {
	
		var key = e.which || e.keyCode;
		if (key === 13) { // enter-key
		
			term.input = $(this).val();
			$(this).val('');
			term.run();
		}
	});

	function loop() {
		if (mode === 'hud') {
		
			mode = 'web';
			screen$.css({visibility: 'hidden'});
		} else {
		
			mode = 'hud';
			screen$.css({visibility: 'visible'});
			input$.focus();
		}
	}
		
	$(window)
		.resize(function(){
		
			if (resize.start == false) {
				
				resize.start = true;
				resize.timer = setTimeout(resizer, 10);
			}
			resize.interrupted = true;
		})
		
		.keyup(function(e) {
		
			var k = e.which || e.keyCode;
			if (k === 113) {  // F2-key to go to different mode.
				loop();
			}
		});
  });

  </script>
  </head>
  <body style="background: white; height: 100%;"> 
   
   <div id="root"> <!-- main tube from host -->
   </div>
   
   <!-- Including style information in html on purpose. -->
   <!-- Reason is to keep css clean for dynamic programming of same. 
        No classes, ids or css except local css. 
     -->
   <table
     style="
     align: left;
     margin: 0px;
     border: 0px;
     padding: 0px;
     border-spacing: 0px;
     font: Courier;
     border-collapse: collapse;
     position: absolute;
     color: black;
     callpadding: 0px;
     cellspacing: 0px;
     -moz-border-radius: 10px;
     -webkit-border-radius: 10px;
     border-radius: 10px;
     background-color: white;">
     <tr>
       <td>     
         <p 
           style="text-align: center; font-size: 25px; font-family: Courier;"> 
	   HELPR: Hypermedia Extraction, Listing and P2P Reporting.
	 </p>
         <p 
           style="
           text-align: center;
           font-size: large;
           font-family: Verdana;">
           || version 0.1.0: "A Series of Tubes Project!" ||
	 </p>
       </td>
     </tr>
     <tr>
       <td>
         <div>
           <input style="font-size:12; font-family:Courier;" type='text' value=''/>
         </div>
       </td>
     </tr>
     <tr>
       <td style="text-align: left; font-family: Fixedsys, Courier;">
         <b>User output:</b> <i></i><hr>
       </td>
     </tr>
     <tr>
       <td>
       </td>
     </tr>
   </table>
  </body>
</html>
