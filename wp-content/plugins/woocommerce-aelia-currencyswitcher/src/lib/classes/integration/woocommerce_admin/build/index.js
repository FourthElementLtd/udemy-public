!function(){"use strict";var r=window.wp.hooks,e=window.wp.i18n;function i(r){let i=wcSettings.aelia_cs_woocommerce_admin_integration||{};return i==={}?r:[...r,{label:(0,e.__)("Currency",i.text_domain),staticParams:[],param:i.arg_report_currency,showFilters:()=>!0,defaultValue:i.default_report_currency,filters:i.report_currency_options}]}(0,r.addFilter)("woocommerce_admin_revenue_report_filters","Aelia/WC/CurrencySwitcher",i),(0,r.addFilter)("woocommerce_admin_orders_report_filters","Aelia/WC/CurrencySwitcher",i),(0,r.addFilter)("woocommerce_admin_products_report_filters","Aelia/WC/CurrencySwitcher",i),(0,r.addFilter)("woocommerce_admin_categories_report_filters","Aelia/WC/CurrencySwitcher",i),(0,r.addFilter)("woocommerce_admin_taxes_report_filters","Aelia/WC/CurrencySwitcher",i),(0,r.addFilter)("woocommerce_admin_coupons_report_filters","Aelia/WC/CurrencySwitcher",i),(0,r.addFilter)("woocommerce_admin_dashboard_filters","Aelia/WC/CurrencySwitcher",i),(0,r.addFilter)("woocommerce_admin_customers_report_filters","Aelia/WC/CurrencySwitcher",i),(0,r.addFilter)("woocommerce_admin_variations_report_filters","Aelia/WC/CurrencySwitcher",i),(0,r.addFilter)("woocommerce_admin_report_currency","Aelia/WC/CurrencySwitcher",(function(r,e){let i=wcSettings.aelia_cs_woocommerce_admin_integration||{};if(i==={})return r;let t=i.currencies,c=e.report_currency||"";return c&&t[c]&&(r=t[c]),r})),(0,r.addFilter)("woocommerce_admin_persisted_queries","Aelia/WC/CurrencySwitcher",(function(r){return r.push("report_currency"),r}))}();