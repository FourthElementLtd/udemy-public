!function(e){var t={};function a(n){if(t[n])return t[n].exports;var r=t[n]={i:n,l:!1,exports:{}};return e[n].call(r.exports,r,r.exports,a),r.l=!0,r.exports}a.m=e,a.c=t,a.d=function(e,t,n){a.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:n})},a.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},a.t=function(e,t){if(1&t&&(e=a(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var n=Object.create(null);if(a.r(n),Object.defineProperty(n,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var r in e)a.d(n,r,function(t){return e[t]}.bind(null,r));return n},a.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return a.d(t,"a",t),t},a.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},a.p="",a(a.s=6)}([function(e,t,a){"use strict";a.d(t,"a",(function(){return s}));var n=a(1),r=a(2);a(3);function i(e,t){for(var a=0;a<t.length;a++){var n=t[a];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(e,n.key,n)}}var s=function(){function e(t){!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,e),e.initialize(),this.setCurrentCampaign(e.currentCampaignID)}var t,a,s;return t=e,s=[{key:"initialize",value:function(){e.details={title:"",targetLink:afwcDashboardParams.home_url,slug:"",campaignId:0,shortDescription:"",body:"",status:"Draft",metaData:{}},e.flags={showNotification:0},e.notification={message:"",status:""}}},{key:"newCampaign",value:function(){e.currentCampaignID=-1,new e}},{key:"saveCampaign",value:function(){r.a.requestHandler({requestData:{cmd:"save_campaign",campaign:JSON.stringify(e.details),security:afwcDashboardParams.security,dashboard:"afwc_campaign_controller"},callback:function(t){"Success"==t.ACK&&(t.last_inserted_id?(e.details.campaignId=t.last_inserted_id,n.a.data.campaigns.push(e.details),e.notification.message="Campaign created successfully"):(n.a.data.campaigns=n.a.data.campaigns.map((function(t){return t.campaignId==e.details.campaignId?e.details:t})),e.notification.message="Campaign updated successfully"),e.flags.showNotification=1,e.notification.status="success",e.currentCampaignID=0)}})}},{key:"deleteCampaign",value:function(){r.a.requestHandler({requestData:{cmd:"delete_campaign",campaign_id:e.currentCampaignID,security:afwcDashboardParams.security,dashboard:"afwc_campaign_controller"},callback:function(t){if("Success"==t.ACK){var a=e.details.campaignId;n.a.data.campaigns=n.a.data.campaigns.filter((function(e){return e.campaignId!=a})),e.currentCampaignID=0,e.flags.showNotification=1,e.notification.message="Campaign deleted successfully",e.notification.status="success"}}})}},{key:"details",get:function(){return e._details},set:function(t){e._details=t}}],(a=[{key:"setCurrentCampaign",value:function(t){var a={};t>0&&(a=n.a.data.campaigns.filter((function(e){return e.campaignId==t}))[0]),e.details={title:a.title||"",targetLink:a.targetLink||afwcDashboardParams.home_url,slug:a.slug||"",campaignId:a.campaignId||0,shortDescription:a.shortDescription||"",body:a.body||"",status:a.status||"Draft",metaData:a.metaData||{}}}}])&&i(t.prototype,a),s&&i(t,s),e}();s.initialize()},function(e,t,a){"use strict";a.d(t,"a",(function(){return i}));var n=a(2);function r(e,t){for(var a=0;a<t.length;a++){var n=t[a];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(e,n.key,n)}}var i=function(){function e(t){!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,e),e.data={kpi:{},campaigns:[]},this.params=t||{},this.fetch()}var t,a,i;return t=e,i=[{key:"data",get:function(){return e._data},set:function(t){e._data=t}}],(a=[{key:"fetch",value:function(){n.a.requestHandler({requestData:{cmd:"fetch_dashboard_data",security:afwcDashboardParams.security,dashboard:"afwc_campaign_controller",campaign_status:afwcDashboardParams.campaign_status||""},callback:function(t){"Success"==t.ACK&&(e.data.kpi=t.result.kpi||{},e.data.campaigns=t.result.campaigns||[])}})}}])&&r(t.prototype,a),i&&r(t,i),e}();i.data={kpi:{},campaigns:[]}},function(e,t,a){"use strict";function n(e,t){var a=Object.keys(e);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(e);t&&(n=n.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),a.push.apply(a,n)}return a}function r(e){for(var t=1;t<arguments.length;t++){var a=null!=arguments[t]?arguments[t]:{};t%2?n(Object(a),!0).forEach((function(t){i(e,t,a[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(a)):n(Object(a)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(a,t))}))}return e}function i(e,t,a){return t in e?Object.defineProperty(e,t,{value:a,enumerable:!0,configurable:!0,writable:!0}):e[t]=a,e}function s(e,t){for(var a=0;a<t.length;a++){var n=t[a];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(e,n.key,n)}}a.d(t,"a",(function(){return l}));var l=function(){function e(){!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,e)}var t,a,n;return t=e,n=[{key:"requestHandler",value:function(e){var t=new FormData,a={security:afwcDashboardParams.security};for(var n in a=r(r({},a),e.requestData))t.append(n,a[n]);m.request({method:e.method||"POST",url:afwcDashboardParams.ajaxurl,params:{action:a.dashboard},body:t,withCredentials:e.withCredentials||!1,responseType:e.responseType||"json"}).then((function(t){e.hasOwnProperty("callback")&&e.callback(t)}))}},{key:"getDate",value:function(e){var t,a=new Date,n="";switch(e){case"today":t=a,n=a;break;case"yesterday":t=new Date(a.getFullYear(),a.getMonth(),a.getDate()-1),n=new Date(a.getFullYear(),a.getMonth(),a.getDate()-1);break;case"this_week":t=new Date(a.getFullYear(),a.getMonth(),a.getDate()-(a.getDay()-1)),n=a;break;case"last_week":t=new Date(a.getFullYear(),a.getMonth(),a.getDate()-(a.getDay()-1)-7),n=new Date(a.getFullYear(),a.getMonth(),a.getDate()-(a.getDay()-1)-1);break;case"last_4_week":t=new Date(a.getFullYear(),a.getMonth(),a.getDate()-29),n=a;break;case"this_month":t=new Date(a.getFullYear(),a.getMonth(),1),n=a;break;case"last_month":t=new Date(a.getFullYear(),a.getMonth()-1,1),n=new Date(a.getFullYear(),a.getMonth(),0);break;case"3_months":t=new Date(a.getFullYear(),a.getMonth()-2,1),n=a;break;case"6_months":t=new Date(a.getFullYear(),a.getMonth()-5,1),n=a;break;case"this_year":t=new Date(a.getFullYear(),0,1),n=a;break;case"last_year":t=new Date(a.getFullYear()-1,0,1),n=new Date(a.getFullYear(),0,0);break;default:t=new Date(a.getFullYear(),a.getMonth(),1),n=a}return{startDate:t.toISOString().slice(0,10),endDate:n.toISOString().slice(0,10)}}}],(a=null)&&s(t.prototype,a),n&&s(t,n),e}()},function(e,t,a){"use strict";a.d(t,"a",(function(){return i}));var n=a(0);function r(e,t){for(var a=0;a<t.length;a++){var n=t[a];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(e,n.key,n)}}var i=function(){function e(){!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,e)}var t,a,i;return t=e,(a=[{key:"view",value:function(){return setTimeout((function(){if(n.a.flags.showNotification){var e=document.getElementsByClassName("afwc-notification-close");e.length>0&&e[0].click()}}),8e3),m("div",{class:"fixed inset-0 flex items-end justify-center px-4 py-6 pointer-events-none sm:p-6 sm:items-start sm:justify-end",style:"z-index: 200000;"},m("div",{class:"w-full max-w-sm bg-white rounded-lg shadow-lg pointer-events-auto bottom-0 fixed"},m("div",{class:"overflow-hidden rounded-lg shadow-xs"},m("div",{class:("success"!=n.a.notification.status?"failed"==n.a.notification.status?"bg-red-600":"bg-yellow-300":"bg-green-400")+" p-4"},m("div",{class:"flex items-start"},m("div",{class:"flex-shrink-0"},m("svg",{class:"w-6 h-6 text-gray-700",stroke:"currentColor",fill:"none","stroke-width":"2",viewBox:"0 0 24 24"},m("path",{d:"M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"}))),m("div",{class:"ml-3 w-0 flex-1 pt-0.5"},m("p",{class:"mt-1 text-sm leading-5 text-gray-700"},m("span",{id:"header-text"},n.a.notification.message))),m("div",{class:"flex flex-shrink-0 ml-4"},m("button",{class:"afwc-notification-close inline-flex text-gray-400 transition duration-150 ease-in-out focus:outline-none focus:text-gray-500",onclick:function(){setTimeout(n.a.flags.showNotification=0,2e3)}},m("svg",{class:"w-5 h-5",viewBox:"0 0 20 20",fill:"currentColor"},m("path",{"fill-rule":"evenodd",d:"M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z","clip-rule":"evenodd"})))))))))}}])&&r(t.prototype,a),i&&r(t,i),e}()},,,function(e,t,a){"use strict";a.r(t);var n=a(1),r=a(0);function i(e,t){for(var a=0;a<t.length;a++){var n=t[a];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(e,n.key,n)}}var s=function(){function e(){!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,e)}var t,a,n;return t=e,(a=[{key:"view",value:function(e){return""!=r.a.details.slug&&(r.a.details.targetLink+=(r.a.details.targetLink.match(/[\?]/g)?"&":"?")+"utm_campaign="+r.a.details.slug),r.a.details.targetLink+=(r.a.details.targetLink.match(/[\?]/g)?"&":"?")+afwcDashboardParams.pname+"="+afwcDashboardParams.affiliate_id,m("aside",{class:"fixed inset-0 overflow-hidden",style:"z-index:120000"},m("div",{class:"absolute inset-0 overflow-hidden"},m("div",{class:"absolute inset-0 bg-gray-500 transition-opacity opacity-75"}),m("section",{class:"absolute inset-y-0 right-0 pl-20 max-w-full flex"},m("div",{class:"w-screen max-w-4xl"},m("div",{class:"h-full flex flex-col space-y-6 py-4 bg-white shadow-xl overflow-y-scroll"},m("header",{class:"px-4 sm:px-6"},m("div",{class:"flex items-start justify-between space-x-3"},m("h2",{class:"text-3xl leading-8 text-gray-900"},r.a.details.title||""),m("div",{class:"h-7 flex items-center space-x-3"},m("button",{"aria-label":"Close panel",class:"leading-none text-gray-400 hover:text-gray-500 transition ease-in-out duration-150 p-0",onclick:function(){r.a.currentCampaignID=0}},m("svg",{class:"h-6 w-6",fill:"none",viewBox:"0 0 24 24",stroke:"currentColor"},m("path",{"stroke-linecap":"round","stroke-linejoin":"round","stroke-width":"2",d:"M6 18L18 6M6 6l12 12"})))))),m("div",{class:"relative flex-1 px-4 sm:px-6"},m("div",{class:"absolute inset-0 px-4 sm:px-6"},m("p",{class:"text-lg"},r.a.details.shortDescription||""),m("p",{class:"mt-4"},"Link: ",m("code",null,r.a.details.targetLink||"")),m("div",{class:"mt-12 mb-4"},m.trust(r.a.details.body||"")))))))))}}])&&i(t.prototype,a),n&&i(t,n),e}();function l(e,t){for(var a=0;a<t.length;a++){var n=t[a];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(e,n.key,n)}}var o=function(){function e(t){!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,e),this.params={id:t.attrs.id||0,startDate:t.attrs.startDate||"",endDate:t.attrs.endDate||""}}var t,a,i;return t=e,(a=[{key:"refreshModel",value:function(e){this.params.id=e,this.model=new r.a}},{key:"view",value:function(){var e=this;return m("div",null,m("div",null,m("ul",{class:"text-gray-600 m-0 w-full"},n.a.data.campaigns.map((function(t,a){return m("li",{class:"my-2 overflow-hidden text-gray-400 bg-white shadow-sm hover:bg-gray-50 focus:outline-none focus:bg-gray-50",onclick:function(){r.a.currentCampaignID=t.campaignId,e.refreshModel(r.a.currentCampaignID)}},m("div",{class:"flex items-center pl-4 py-2"},m("div",{class:"text-gray-700 text-lg pr-4 flex-1 sm:truncate leading-6"},t.title),m("div",{class:"mx-2"},m("svg",{class:"h-5 w-5 text-gray-400",fill:"currentColor",viewBox:"0 0 20 20"},m("path",{"fill-rule":"evenodd",d:"M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z","clip-rule":"evenodd"})))))})))),0!==r.a.currentCampaignID?m(s,{id:r.a.currentCampaignID}):"")}}])&&l(t.prototype,a),i&&l(t,i),e}();function c(e,t){for(var a=0;a<t.length;a++){var n=t[a];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(e,n.key,n)}}function u(e,t,a){return t&&c(e.prototype,t),a&&c(e,a),e}r.a.currentCampaignID=0;var f=function(){function e(t){!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,e),this.initialize(t.attrs)}return u(e,[{key:"initialize",value:function(e){this.urlParams=Object.keys(e).length?e:{start_date:new Date((new Date).setDate((new Date).getDate()-30)).toISOString().slice(0,10),end_date:(new Date).toISOString().slice(0,10)},this.model=new n.a(this.urlParams)}}]),u(e,[{key:"onupdate",value:function(e){this.urlParams!=e.attrs&&Object.keys(e.attrs).length&&this.initialize(e.attrs)}},{key:"view",value:function(){return m("div",null,n.a.data.campaigns.length>0?m("[",null,m(o,null)):m("p",{class:"text-xl"},afwcDashboardParams.no_campaign_string))}}]),e}(),d=document.getElementById("afw-campaigns");m.route(d,"/campaigns",{"/campaigns":{view:function(e){return[m(f,e.attrs)]}}})}]);