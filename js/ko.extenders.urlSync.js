/**
 * An extender that syncs the observable with the address
 * bar (using hash fragment). Will load from URL hash or 
 * querystring (at time of creation) and will
 * update hash when observable changes.
 *
 * Options: string|object
 *   String: Query parameter key to get/set
 *   Object:
 *    - param - Query parameter key to get/set
 *    - read - Callback that formats the value when read from query string
 *    - write - Callback that formats the value when writing to the query string
 *
 * Usage:
 * 
 *   ko.observable(false).extend({ urlSync: "param1" });
 *   ko.observable(false).extend({ urlSync: { param: "param1" }});
 *
 * Dependencies:
 *   Underscore/Lodash.js
 *   URI.js
 *   URI.fragmentQuery.js
 */
ko.extenders.urlSync = function(target, options) {

   if (_.isString(options)) {
      options = { param: options };
   } else {
      options = options || {};
   }
   options.read = options.read || function (value) { return value; };
   options.write = options.write || function (value) { return value; };

   if (_.isUndefined(options.param)) return target;

   // retrieve from URI
   var uri = new URI();
   var paramValueHash = uri.fragment(true)[options.param];
   var paramValueQuery = uri.query(true)[options.param];

   if (!_.isUndefined(paramValueHash)) {
      target(options.read(paramValueHash));
   } else if (!_.isUndefined(paramValueQuery)) {
      target(options.read(paramValueQuery));
   }

   target.subscribe(function(newValue) {
      var uri = new URI();
      var writtenValue = options.write(newValue);

      // remove old
      uri.removeFragment(options.param);
      uri.addFragment(options.param, writtenValue);

      // update hash
      window.location.hash = uri.fragment();
   });

   return target;
};
