!function(t){var e={};function o(n){if(e[n])return e[n].exports;var a=e[n]={i:n,l:!1,exports:{}};return t[n].call(a.exports,a,a.exports,o),a.l=!0,a.exports}o.m=t,o.c=e,o.d=function(t,e,n){o.o(t,e)||Object.defineProperty(t,e,{enumerable:!0,get:n})},o.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},o.t=function(t,e){if(1&e&&(t=o(t)),8&e)return t;if(4&e&&"object"==typeof t&&t&&t.__esModule)return t;var n=Object.create(null);if(o.r(n),Object.defineProperty(n,"default",{enumerable:!0,value:t}),2&e&&"string"!=typeof t)for(var a in t)o.d(n,a,function(e){return t[e]}.bind(null,a));return n},o.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return o.d(e,"a",e),e},o.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},o.p="",o(o.s=27)}({27:function(t,e,o){"use strict";o.r(e);var n=function(){function t(){$("body").hasClass("single-product")&&(this.$selectableDropdown=$(".atum-select-mi"),this.$selectableDropdown.length&&this.doSelectableDropDownUI()),this.$selectableList=$(".atum-select-mi-list"),this.$selectableList.length&&this.doSelectableListUI(),this.$cart=$(".woocommerce-cart-form"),this.$cart.length&&this.doSelectableCartItems(),this.$variationsForm=$(".variations_form"),this.$variationsForm.length&&this.doSelectableVariations()}return t.prototype.doSelectableDropDownUI=function(){var t=this.$selectableDropdown.closest("form").find('[name="quantity"]');this.$selectableDropdown.on("change",(function(e){var o=$(e.currentTarget),n=parseFloat(o.find("option:selected").data("max"));n<0?t.attr("max",""):(t.attr("max",n),parseFloat(t.val())>n&&t.val(n))})).change()},t.prototype.doSelectableListUI=function(){var t=this.$selectableList.closest("form"),e=t.find('[name="quantity"]').prop("readonly",!0),o=t.find('[name^="atum[select-mi]"]'),n=t.find(".atum-select-mi-list__multi-price");o.change((function(){var a=0,r=[];o.each((function(t,e){var o=$(e),n=o.closest(".atum-select-mi-list__item"),i=parseFloat(o.val());a+=i,i>0&&n.find(".woocommerce-Price-amount").length&&r.push(i+" x "+n.find(".woocommerce-Price-amount").text().trim())})),e.val(a),n.length&&(n.text(r.join(" + ")),t.find(":submit").prop("disabled",!r.length))})),n.length&&o.first().change()},t.prototype.doSelectableCartItems=function(){$("body").on("change",".woocommerce-cart-form .atum-mi-qty",(function(t){for(var e,o=$(t.currentTarget),n=parseFloat(o.val()),a=o.closest(".atum-mi-cart-item"),r=a.prev();r.length&&r.hasClass("atum-mi-cart-item");)n+=parseFloat(r.find(".atum-mi-qty").val()),r=r.prev();for(e=r,r=a.next();r.length&&r.hasClass("atum-mi-cart-item");)n+=parseFloat(r.find(".atum-mi-qty").val()),r=r.next();e.length&&e.find(".quantity :input").val(n)}))},t.prototype.doSelectableVariations=function(){var t=this,e=this.$variationsForm.find(".woocommerce-variation.single_variation");e.on("show_variation",(function(o,n,a){t.$variationsForm.find('[name="quantity"]').removeAttr("readonly"),n&&n.hasOwnProperty("atum_mi_selectable")&&(e.append(n.atum_mi_selectable),t.$selectableList=$(".atum-select-mi-list"),t.$selectableList.length&&t.doSelectableListUI(),$("body").hasClass("single-product")&&(t.$selectableDropdown=$(".atum-select-mi"),t.$selectableDropdown.length&&t.doSelectableDropDownUI()))}))},t}();jQuery((function(t){window.$=t,new n}))}});