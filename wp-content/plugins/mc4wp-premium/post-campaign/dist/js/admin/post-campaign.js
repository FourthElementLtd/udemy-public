/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 6);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["i18n"]; }());

/***/ }),
/* 1 */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["components"]; }());

/***/ }),
/* 2 */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["element"]; }());

/***/ }),
/* 3 */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["data"]; }());

/***/ }),
/* 4 */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["plugins"]; }());

/***/ }),
/* 5 */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["editPost"]; }());

/***/ }),
/* 6 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_plugins__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(4);
/* harmony import */ var _wordpress_plugins__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_plugins__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(1);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_edit_post__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(5);
/* harmony import */ var _wordpress_edit_post__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_edit_post__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(2);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(3);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(0);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__);
function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _iterableToArrayLimit(arr, i) { if (typeof Symbol === "undefined" || !(Symbol.iterator in Object(arr))) return; var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }








var _select = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_4__["select"])('core/editor'),
    isEditedPostDirty = _select.isEditedPostDirty,
    getCurrentPost = _select.getCurrentPost,
    getCurrentPostId = _select.getCurrentPostId,
    isCleanNewPost = _select.isCleanNewPost;

var _dispatch = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_4__["dispatch"])('core/notices'),
    createSuccessNotice = _dispatch.createSuccessNotice,
    createErrorNotice = _dispatch.createErrorNotice;
/**
 * @return {*} react elements
 */


var PostMailchimpCampaignPanel = function PostMailchimpCampaignPanel() {
  var _meta$mc4wp_mailchimp;

  var meta = getCurrentPost().meta;

  var _useState = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["useState"])((_meta$mc4wp_mailchimp = meta.mc4wp_mailchimp_campaign) !== null && _meta$mc4wp_mailchimp !== void 0 ? _meta$mc4wp_mailchimp : {}),
      _useState2 = _slicedToArray(_useState, 2),
      campaign = _useState2[0],
      setCampaign = _useState2[1];

  var _useState3 = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["useState"])(false),
      _useState4 = _slicedToArray(_useState3, 2),
      busy = _useState4[0],
      setBusy = _useState4[1];

  var onClick = function onClick() {
    if (busy) {
      return;
    }

    var postId = getCurrentPostId();

    if (postId === null || isEditedPostDirty() || isCleanNewPost()) {
      window.alert(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('There are unsaved changes. Please save the post first.', 'mailchimp-for-wp'));
      return;
    }

    if (campaign.id && !window.confirm(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('Heads up! This will overwrite the content in your existing Mailchimp campaign. Are you sure you want to proceed?', 'mailchimp-for-wp'))) {
      return;
    }

    setBusy(true);
    fetch("".concat(window.ajaxurl, "?action=mc4wp_create_campaign_of_post&post_id=").concat(postId)).then(function (res) {
      return res.json();
    }).then(function (_ref) {
      var success = _ref.success,
          data = _ref.data;

      if (!success) {
        throw new Error('Not successfully');
      }

      createSuccessNotice(campaign.id ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('The Mailchimp campaign was updated. Go to the', 'mailchimp-for-wp') : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('The Mailchimp campaign is created. Go to the', 'mailchimp-for-wp'), {
        actions: [{
          label: 'campaign.',
          url: 'https://admin.mailchimp.com/campaigns/edit?id=' + data.web_id
        }]
      });
      setCampaign(data);
    })["catch"](function () {
      var message = campaign.id ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('Error updating campaign. Check the debug log for errors.', 'mailchimp-for-wp') : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('Error creating campaign. Check the debug log for errors.', 'mailchimp-for-wp');
      createErrorNotice(message);
    })["finally"](function () {
      //Prevent from infinite loop
      setBusy(false);
    });
  };

  return /*#__PURE__*/React.createElement(_wordpress_edit_post__WEBPACK_IMPORTED_MODULE_2__["PluginDocumentSettingPanel"], {
    name: "mc4wp-post-campaign-panel",
    title: 'Mailchimp for WordPress',
    className: "mc4wp-post-campaign-panel"
  }, /*#__PURE__*/React.createElement("div", {
    className: 'components-panel__row'
  }, campaign.id ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('This post has an email campaign in Mailchimp. Use the button below to update the campaign with the contents of this post.', 'mailchimp-for-wp') : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('Use the button below to create an email campaign in Mailchimp based on the contents of this post.', 'mailchimp-for-wp')), /*#__PURE__*/React.createElement("div", {
    className: 'components-panel__row'
  }, /*#__PURE__*/React.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__["Button"], {
    isBusy: busy,
    onClick: onClick,
    disabled: busy,
    isPrimary: true
  }, campaign.id ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('Update campaign', 'mailchimp-for-wp') : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('Create campaign', 'mailchimp-for-wp'))), campaign.id && /*#__PURE__*/React.createElement("div", {
    className: 'components-panel__row'
  }, /*#__PURE__*/React.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__["ExternalLink"], {
    href: 'https://admin.mailchimp.com/campaigns/edit?id=' + campaign.web_id
  }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('Go to campaign in Mailchimp.', 'mailchimp-for-wp'))));
};

Object(_wordpress_plugins__WEBPACK_IMPORTED_MODULE_0__["registerPlugin"])('mc4wp-plugin-post-campaign-document-panel', {
  render: PostMailchimpCampaignPanel,
  icon: /*#__PURE__*/React.createElement("svg", null, /*#__PURE__*/React.createElement("path", {
    fill: "#a0a5aa",
    width: "24",
    height: "24",
    viewBox: "0 0 24 24",
    d: "M 8.0097656 0.052734375 A 8 8 0 0 0 0.009765625 8.0527344 A 8 8 0 0 0 8.0097656 16.052734 A 8 8 0 0 0 16.009766 8.0527344 A 8 8 0 0 0 8.0097656 0.052734375 z M 9.2597656 4.171875 C 9.3205456 4.171875 9.9296146 5.0233822 10.611328 6.0664062 C 11.293041 7.1094313 12.296018 8.5331666 12.841797 9.2285156 L 13.833984 10.492188 L 13.316406 11.041016 C 13.031321 11.342334 12.708299 11.587891 12.599609 11.587891 C 12.253798 11.587891 11.266634 10.490156 10.349609 9.0859375 C 9.8610009 8.3377415 9.4126385 7.7229 9.3515625 7.71875 C 9.2904825 7.71455 9.2402344 8.3477011 9.2402344 9.1269531 L 9.2402344 10.544922 L 8.5839844 10.982422 C 8.2233854 11.223015 7.8735746 11.418294 7.8066406 11.417969 C 7.7397106 11.417644 7.4861075 10.997223 7.2421875 10.482422 C 6.9982675 9.9676199 6.6560079 9.3946444 6.4824219 9.2089844 L 6.1679688 8.8710938 L 6.0664062 9.34375 C 5.7203313 10.974656 5.6693219 11.090791 5.0917969 11.505859 C 4.5805569 11.873288 4.2347982 12.017623 4.1914062 11.882812 C 4.1839062 11.859632 4.1482681 11.574497 4.1113281 11.25 C 3.9708341 10.015897 3.5347399 8.7602861 2.8105469 7.5019531 C 2.5672129 7.0791451 2.5711235 7.0651693 2.9765625 6.8320312 C 3.2046215 6.7008903 3.5466561 6.4845105 3.7363281 6.3515625 C 4.0587811 6.1255455 4.1076376 6.1466348 4.4941406 6.6679688 C 4.8138896 7.0992628 4.9275606 7.166285 4.9941406 6.96875 C 5.0960956 6.666263 6.181165 5.8574219 6.484375 5.8574219 C 6.600668 5.8574219 6.8857635 6.1981904 7.1171875 6.6152344 C 7.3486105 7.0322784 7.5790294 7.3728809 7.6308594 7.3730469 C 7.7759584 7.3735219 7.9383234 5.8938023 7.8339844 5.5195312 C 7.7605544 5.2561423 7.8865035 5.0831575 8.4453125 4.6796875 C 8.8327545 4.3999485 9.1989846 4.171875 9.2597656 4.171875 z "
  }))
});

/***/ })
/******/ ]);