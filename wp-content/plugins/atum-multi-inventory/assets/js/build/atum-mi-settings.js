!function(e){var t={};function n(r){if(t[r])return t[r].exports;var i=t[r]={i:r,l:!1,exports:{}};return e[r].call(i.exports,i,i.exports,n),i.l=!0,i.exports}n.m=e,n.c=t,n.d=function(e,t,r){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:r})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var r=Object.create(null);if(n.r(r),Object.defineProperty(r,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var i in e)n.d(r,i,function(t){return e[t]}.bind(null,i));return r},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="",n(n.s=25)}({2:function(e,t,n){"use strict";var r=function(){function e(e,t){void 0===t&&(t={}),this.varName=e,this.defaults=t,this.settings={};var n=void 0!==window[e]?window[e]:{};Object.assign(this.settings,t,n)}return e.prototype.get=function(e){if(void 0!==this.settings[e])return this.settings[e]},e.prototype.getAll=function(){return this.settings},e.prototype.delete=function(e){this.settings.hasOwnProperty(e)&&delete this.settings[e]},e}();t.a=r},25:function(e,t,n){"use strict";n.r(t);var r=n(3),i=function(){function e(e,t){var n=this;this.settings=e,this.enhancedSelect=t,this.selectFromDefaults={},this.$settingsWrapper=$(".atum-settings-wrapper"),this.$form=this.$settingsWrapper.find("#atum-settings"),this.$settingsWrapper.on("atum-settings-page-loaded",(function(e,t){"tools"===t?n.initRegionSwitchers():"multi_inventory"===t&&$("#atum_mi_region_restriction_mode").on("change",":radio",(function(e){var t=$("#atum_mi_use_geoprompt");"shipping-zones"!==$(e.currentTarget).val()||t.is(":checked")||t.prop("checked",!0).change()}))}))}return e.prototype.initRegionSwitchers=function(){var e=this,t=this.$form.find(".script-runner.region-switcher");t.find(".select-from").each((function(t,n){var r=$(n),i={};r.find("option").each((function(e,t){var n=$(t).val(),r=$(t).text().trim();n&&(i[n]=r)})),e.selectFromDefaults[r.attr("id")]=i})),t.on("click",".add-row",(function(t){var n=$(t.currentTarget),r=n.closest(".repeatable-row"),i=r.find(".select-from"),o=n.closest(".tool-fields-wrapper");if(i.find("option").length<2)return!1;if(r.siblings(".repeatable-row").length+1>=Object.keys(e.selectFromDefaults[i.attr("id")]).length)return!1;r.find(".select2-hidden-accessible").select2("destroy");var s=r.clone();o.append(s),s.find("select").val(""),s.find(".remove-row").length||s.find(".tool-controls").append('<i class="atum-icon atmi-cross-circle remove-row"></i>'),e.enhancedSelect.maybeRestoreEnhancedSelect(),e.rebuildFromSelects(o)})).on("click",".remove-row",(function(t){var n=$(t.currentTarget).closest(".repeatable-row"),r=n.closest(".tool-fields-wrapper");n.remove(),e.rebuildFromSelects(r)})).on("change",".select-from",(function(t){var n=$(t.currentTarget),r=n.val(),i=n.closest(".tool-fields-wrapper");r&&i.find(".select-from").not(n).find("option").filter('[value="'+r+'"]').remove(),e.rebuildFromSelects(i),e.updateRegionSwitcherInput(n.closest(".region-switcher"))})).on("change",".select-to",(function(t){return e.updateRegionSwitcherInput($(t.currentTarget).closest(".region-switcher"))})).on("click",".tool-runner",(function(t,n){if(void 0===n||void 0===n.force||!0!==n.force){t.stopImmediatePropagation();var r=$(t.currentTarget),i=r.siblings(".tool-fields-wrapper"),o=!0,s="";i.find("select").each((function(t,n){if(!$(n).val())return o=!1,s=e.settings.get("requiredFields"),!1})),o?r.trigger("click",{force:!0}):(i.find(".error-message").remove(),i.append('<em class="error-message">'+s+"</em>"),setTimeout((function(){var e=i.find(".error-message");e.fadeOut((function(){e.remove()}))}),3e3))}})),this.$settingsWrapper.on("atum-settings-script-runner-done",(function(e,t){t.hasClass("region-switcher")&&location.reload()}))},e.prototype.rebuildFromSelects=function(e){var t=this,n=[],r=e.find(".select-from"),i=r.first().attr("id");if(r.each((function(e,t){var r=$(t).val();r&&n.push(r)})),n.length){var o=$.grep(Object.keys(this.selectFromDefaults[i]),(function(e,t){return-1===$.inArray(e,n)}));r.each((function(e,r){var s=$(r),a=s.val(),c=o;s.find("option").each((function(e,t){var r=$(t),i=r.val();i&&i!==a&&$.inArray(i,n)>-1&&r.remove()})),c.length&&$.each(c,(function(e,n){s.find("option").filter('[value="'+n+'"]').length||s.append('<option value="'+n+'">'+t.selectFromDefaults[i][n]+"</option>")}))}))}},e.prototype.updateRegionSwitcherInput=function(e){var t=e.find("input[type=hidden]"),n=[];e.find(".repeatable-row").each((function(e,t){var r=$(t),i=r.find(".select-from").val(),o=r.find(".select-to").val();i&&o&&n.push({from:i,to:o})})),t.val(JSON.stringify(n)),e.find(".tool-runner").prop("disabled",!n.length)},e}(),o=n(2);jQuery((function(e){window.$=e;var t=new o.a("atumMultInvSettingsVars"),n=new r.a;new i(t,n)}))},3:function(e,t,n){"use strict";var r=function(){return(r=Object.assign||function(e){for(var t,n=1,r=arguments.length;n<r;n++)for(var i in t=arguments[n])Object.prototype.hasOwnProperty.call(t,i)&&(e[i]=t[i]);return e}).apply(this,arguments)},i=function(){function e(){var e=this;this.addAtumClasses(),$("body").on("wc-enhanced-select-init",(function(){return e.addAtumClasses()}))}return e.prototype.maybeRestoreEnhancedSelect=function(){$(".select2-container--open").remove(),$("body").trigger("wc-enhanced-select-init")},e.prototype.doSelect2=function(e,t,n){var i=this;void 0===t&&(t={}),void 0===n&&(n=!1),"function"==typeof $.fn.select2&&(t=Object.assign({minimumResultsForSearch:10},t),e.each((function(e,o){var s=$(o),a=r({},t);s.hasClass("atum-select-multiple")&&!1===s.prop("multiple")&&s.prop("multiple",!0),s.hasClass("atum-select2")||(s.addClass("atum-select2"),i.addAtumClasses(s)),n&&s.on("select2:selecting",(function(e){var t=$(e.currentTarget),n=t.val();Array.isArray(n)&&($.inArray("",n)>-1||$.inArray("-1",n)>-1)&&($.each(n,(function(e,t){""!==t&&"-1"!==t||n.splice(e,1)})),t.val(n))})),s.select2(a),s.siblings(".select2-container").addClass("atum-select2"),i.maybeAddTooltip(s)})))},e.prototype.addAtumClasses=function(e){var t=this;void 0===e&&(e=null),(e=e||$("select").filter(".atum-select2, .atum-enhanced-select")).length&&e.each((function(e,n){var r=$(n),i=r.siblings(".select2-container").not(".atum-select2, .atum-enhanced-select");i.length&&(i.addClass(r.hasClass("atum-select2")?"atum-select2":"atum-enhanced-select"),t.maybeAddTooltip(r))})).on("select2:opening",(function(e){var t=$(e.currentTarget).data();if(t.hasOwnProperty("select2")){var n=t.select2.dropdown.$dropdown;n.length&&n.addClass("atum-select2-dropdown")}}))},e.prototype.maybeAddTooltip=function(e){e.hasClass("atum-tooltip")&&e.siblings(".select2-container").find(".select2-selection__rendered").addClass("atum-tooltip")},e}();t.a=i}});