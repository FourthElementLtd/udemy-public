(()=>{"use strict";var t,e,n,r=function(){function t(e){this.settings=e,t._instance=this,this.maybe_invalidate_minicart_cache()}return t.instance=function(){if(!t._instance)throw new Error('Class "Cart_Fragments_Handler" has not been instantiated. Please call `Cart_Fragments_Handler.init(<settings>)` before calling `Cart_Fragments_Handler.instance()`.');return t._instance},t.init=function(e){return new t(e)},t.prototype.remove_session_storage_item=function(t){var e;try{null!==(null!==(e=window.sessionStorage)&&void 0!==e?e:null)&&sessionStorage.removeItem(t)}catch(t){console.error("Aelia - Exception occurred while accessing window.sessionStorage. This could be caused by the browser disabling cookies. COOKIES MUST BE ENABLED for the site to work correctly. Exception details below."),console.error(t)}},t.prototype.purge_cart_fragments=function(){var t,e=(null!==(t=this.settings.cart_fragments_params.fragment_name)&&void 0!==t?t:"").trim();e&&this.remove_session_storage_item(e)},t.prototype.maybe_invalidate_minicart_cache=function(){var e;(null!==(e=new URL(window.location.href).searchParams.get(t.ARG_CURRENCY))&&void 0!==e?e:"").length>0&&this.purge_cart_fragments()},t.ARG_CURRENCY="aelia_cs_currency",t}(),o=function(){function t(t){this.settings=t,this.init_event_handlers()}return t.prototype.init_event_handlers=function(){var t=this;jQuery(document).on("submit",".currency_switch_form, .country_switch_form",(function(e){t.purge_cart_fragments()})),jQuery(document).on("aelia_currency_selected aelia_country_selected",(function(e){t.purge_cart_fragments()}))},t.prototype.purge_cart_fragments=function(){this.settings.cart_fragments_handler.purge_cart_fragments()},t}(),i=(t=function(e,n){return t=Object.setPrototypeOf||{__proto__:[]}instanceof Array&&function(t,e){t.__proto__=e}||function(t,e){for(var n in e)Object.prototype.hasOwnProperty.call(e,n)&&(t[n]=e[n])},t(e,n)},function(e,n){if("function"!=typeof n&&null!==n)throw new TypeError("Class extends value "+String(n)+" is not a constructor or null");function r(){this.constructor=e}t(e,n),e.prototype=null===n?Object.create(n):(r.prototype=n.prototype,new r)}),c=function(t){function e(){var e=null!==t&&t.apply(this,arguments)||this;return e.ARG_CURRENCY="aelia_cs_currency",e}return i(e,t),e.prototype.init_event_handlers=function(){var e=this;t.prototype.init_event_handlers.call(this),jQuery(document).on("click",(function(t){jQuery(t.target).closest(".dropdown_selector .dropdown, .dropdown_selector .selected_option, .dropdown_selector .search").length<=0&&e.close_all_dropdowns()})),jQuery(document).on("keyup",".widget_wc_aelia_currencyswitcher_widget .search",(function(t){var n;if(13===(null!==(n=t.which)&&void 0!==n?n:"")){var r=jQuery(t.currentTarget).parents(".dropdown").first().find(".option.selected");r&&e.submit_selection(r.data("value"))}else{var o=jQuery(t.currentTarget).parents(".dropdown").first(),i=jQuery(t.currentTarget).val().toString();e.filter_options_by_search_value(o,i)}}))},e.prototype.toggle_dropdown=function(t,e){var n=e.find(".dropdown");n.is(":visible")?(e.removeClass("active"),n.slideUp(300)):(this.close_all_dropdowns(),this.set_dropdown_position(n),n.slideDown(300,(function(){n.find(".search").trigger("focus")})),e.addClass("active"))},e.prototype.close_all_dropdowns=function(){var t=this,e=this.get_all_dropdowns();e.slideUp(300).removeClass("active"),e.each((function(e,n){t.reset_filtered_options(jQuery(n))}))},e.prototype.set_dropdown_position=function(t){t.css("top","calc(100%)"),t.css("bottom",""),t.css("display","block");var e=t[0].getBoundingClientRect();t.css("display","none"),window.innerHeight<e.y+e.height&&(t.css("top","auto"),t.css("bottom","calc(100%)"))},e.prototype.reset_filtered_options=function(t){t.find(".search").val(""),t.find(".option").removeClass("filter_hidden").removeClass("selected")},e.prototype.filter_options_by_search_value=function(t,e){var n=e.trim().toLowerCase();n?(t.find(".option").removeClass("selected"),t.find(".option").each((function(t,e){var r=jQuery(e),o=r.data("search_data");console.log(o);var i=!1;for(var c in o)if(o[c].toString().toLowerCase().includes(n)){i=!0;break}r.toggleClass("filter_hidden",!i)})),t.find(".option:visible").first().addClass("selected")):this.reset_filtered_options(t)},e}(o),s=function(){var t=function(e,n){return t=Object.setPrototypeOf||{__proto__:[]}instanceof Array&&function(t,e){t.__proto__=e}||function(t,e){for(var n in e)Object.prototype.hasOwnProperty.call(e,n)&&(t[n]=e[n])},t(e,n)};return function(e,n){if("function"!=typeof n&&null!==n)throw new TypeError("Class extends value "+String(n)+" is not a constructor or null");function r(){this.constructor=e}t(e,n),e.prototype=null===n?Object.create(n):(r.prototype=n.prototype,new r)}}(),a=function(t){function e(){var e=null!==t&&t.apply(this,arguments)||this;return e.ARG_CURRENCY="aelia_cs_currency",e}return s(e,t),e.prototype.get_all_dropdowns=function(){return jQuery(".wc_aelia_cs_currency_selector.active").find(".dropdown")},e.prototype.init_event_handlers=function(){var e=this;t.prototype.init_event_handlers.call(this),jQuery(document).on("click",".wc_aelia_cs_currency_selector .selected_currency",(function(t){e.toggle_dropdown(t,jQuery(t.currentTarget).parents(".wc_aelia_cs_currency_selector").first())})),jQuery(document).on("click",".wc_aelia_cs_currency_selector .currency",(function(t){e.submit_selection(jQuery(t.currentTarget).data("value"))})),jQuery(document).on("change",".widget_wc_aelia_currencyswitcher_widget .aelia_cs_currencies",(function(t){t.stopPropagation(),e.submit_selection(jQuery(t.currentTarget).val().toString())}))},e.prototype.submit_selection=function(t){if(t){jQuery(document).trigger("aelia_currency_selected");var e=document.createElement("input");e.type="hidden",e.name=this.ARG_CURRENCY,e.value=t;var n=document.createElement("form");n.classList.add("currency_switch_form"),n.method="post",n.style.display="none !important",n.appendChild(e),document.body.appendChild(n),n.submit()}},e}(c),u=function(){var t=function(e,n){return t=Object.setPrototypeOf||{__proto__:[]}instanceof Array&&function(t,e){t.__proto__=e}||function(t,e){for(var n in e)Object.prototype.hasOwnProperty.call(e,n)&&(t[n]=e[n])},t(e,n)};return function(e,n){if("function"!=typeof n&&null!==n)throw new TypeError("Class extends value "+String(n)+" is not a constructor or null");function r(){this.constructor=e}t(e,n),e.prototype=null===n?Object.create(n):(r.prototype=n.prototype,new r)}}(),l=function(t){function e(){var e=null!==t&&t.apply(this,arguments)||this;return e.ARG_CUSTOMER_COUNTRY="aelia_customer_country",e}return u(e,t),e.prototype.get_all_dropdowns=function(){return jQuery(".wc_aelia_cs_country_selector.active").find(".dropdown")},e.prototype.init_event_handlers=function(){var e=this;t.prototype.init_event_handlers.call(this),jQuery(document).on("click",".wc_aelia_cs_country_selector .selected_country",(function(t){e.toggle_dropdown(t,jQuery(t.currentTarget).parents(".wc_aelia_cs_country_selector").first())})),jQuery(document).on("click",".wc_aelia_cs_country_selector .country",(function(t){e.submit_selection(jQuery(t.currentTarget).data("value"))})),jQuery(document).on("change",".currency_switcher.widget_wc_aelia_country_selector_widget .countries",(function(t){t.stopPropagation(),e.submit_selection(jQuery(t.currentTarget).val().toString())}))},e.prototype.submit_selection=function(t){if(t){jQuery(document).trigger("aelia_currency_selected");var e=document.createElement("input");e.type="hidden",e.name=this.ARG_CUSTOMER_COUNTRY,e.value=t;var n=document.createElement("form");n.classList.add("country_switch_form"),n.method="post",n.style.display="none !important",n.appendChild(e),document.body.appendChild(n),n.submit()}},e}(c),_=function(){function t(t){var e;this.widgets={},this.settings=t;var n=null!==(e=window.wc_cart_fragments_params)&&void 0!==e?e:{ajax_url:"",cart_hash_key:"",fragment_name:"",request_timeout:0};r.init({cart_fragments_params:n});var o={cart_fragments_handler:r.instance()};this.widgets["currency-selector"]=new a(o),this.widgets["country-selector"]=new l(o)}return t.instance=function(){if(!t._instance)throw new Error('Class "Frontend_Scripts_Manager" has not been instantiated. Please call `Frontend_Scripts_Manager.init(<settings>)` before calling `Frontend_Scripts_Manager.instance()`.');return t._instance},t.init=function(e){return new t(e)},t}();e=_.init,n=wc_aelia_currency_switcher_params,"loading"!=document.readyState?e(n):document.addEventListener("DOMContentLoaded",(function(){return e(n)}))})();
//# sourceMappingURL=index.js.map