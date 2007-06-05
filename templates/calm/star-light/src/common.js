/*
	common, version 1.0.4 (2005/06/05)
	Copyright 2005, Dean Edwards
	License: http://creativecommons.org/licenses/LGPL/2.1/
*/

// this function may be used to cast any javascript object
//  to a common object
function ICommon(that) {
	if (that != null) {
		that.inherit = Common.prototype.inherit;
		that.specialize = Common.prototype.specialize;
	}
	return that;
};

// sub-classing
ICommon.specialize = function($prototype, $constructor) {
	// initialise class properties
	if (!$prototype) $prototype = {};
	if (!$constructor) $constructor = $prototype.constructor;
	if ($constructor == {}.constructor) $constructor = new Function;
	// build the inheritance chain
	//  insert a dummy constructor between the ancestor
	//  and the new constructor. this allows standard
	//  prototype inheritance plus chained constructor
	//  functions.
	$constructor.valueOf = new Function("return this");
	$constructor.valueOf.prototype = new this.valueOf;
	$constructor.valueOf.prototype.specialize($prototype);
	$constructor.prototype = new $constructor.valueOf;
	$constructor.valueOf.prototype.constructor =
	$constructor.prototype.constructor = $constructor;
	$constructor.ancestor = this;
	$constructor.specialize = arguments.callee;
	$constructor.ancestorOf = this.ancestorOf;
	return $constructor;
};

// root of the inheritance chain
ICommon.valueOf = new Function("return this");

// common interface
ICommon.valueOf.prototype = {
constructor: ICommon,
inherit: function() {
//-
//   Call this method from any other method to call that method's ancestor.
//   If there is no ancestor function then this function will throw an error.
//-
	return arguments.callee.caller.ancestor.apply(this, arguments);
},
specialize: function(that) {
//-
//   Add the interface of another object to this object
//-
	// if this object is the prototype then specialize the /real/ prototype
	if (this == this.constructor.prototype && this.constructor.specialize) {
		return this.constructor.valueOf.prototype.specialize(that);
	}
	// add each of one of the source object's properties to this object
	for (var i in that) {
		switch (i) {
			case "constructor": // don't do this one!
			case "toString":    // do this one maually
			case "valueOf":     // ignore this one...
				continue;
		}
		// implement inheritance
		if (typeof that[i] == "function" && that[i] != this[i]) {
			that[i].ancestor = this[i];
		}
		// add the property
		this[i] = that[i];
	}
	// do the "toString" function manually
	if (that.toString != this.toString && that.toString != {}.toString) {
		that.toString.ancestor = this.toString;
		this.toString = that.toString;
	}
	return this;
}};

// create the root
function Common() {
//--
//   empty constructor function
//--
};
this.Common = ICommon.specialize({
constructor: Common,
toString: function() {
    return "[common " + (this.constructor.className || "Object") + "]";
},
instanceOf: function(klass) {
    return this.constructor == klass || klass.ancestorOf(this.constructor);
}
});
Common.className = "Common";
Common.ancestor = null;
Common.ancestorOf = function(klass) {
	// Is this class an ancestor of the supplied class?
	while (klass && klass.ancestor != this) klass = klass.ancestor;
	return Boolean(klass);
};

// preserve the common prototype so that we can tell when a
//  property of the root class has changed
Common.valueOf.ancestor = ICommon;

// c'est fini!
